<?php

namespace ListPlus;

class Post_Types
{
    public function __construct()
    {
        add_action( 'init', [ __CLASS__, 'registers' ] );
        
        if ( \is_admin() ) {
            /**
             * @see http://justintadlock.com/archives/2011/06/27/custom-columns-for-custom-post-typess
             */
            add_filter( 'manage_edit-listing_columns', [ __CLASS__, 'listing_columns' ] );
            add_action(
                'manage_listing_posts_custom_column',
                [ __CLASS__, 'listing_content_column' ],
                10,
                2
            );
            /**
             * Add more filter to admin listing
             *
             * @see https://pluginrepublic.com/how-to-filter-custom-post-type-by-meta-field/
             */
            add_action( 'restrict_manage_posts', [ __CLASS__, 'listing_filter_inputs' ] );
        }
        
        add_action( 'init', [ __CLASS__, 'listing_post_status' ] );
        add_action( 'views_edit-listing', [ __CLASS__, 'listing_views' ] );
        // $views = apply_filters( "views_{$this->screen->id}", $views );
        
        if ( is_admin() ) {
            add_filter(
                'display_post_states',
                array( $this, 'add_display_post_states' ),
                1989,
                2
            );
            add_filter(
                'page_row_actions',
                array( $this, 'page_row_actions' ),
                1989,
                2
            );
            add_filter(
                'post_row_actions',
                array( $this, 'page_row_actions' ),
                1989,
                2
            );
            add_filter(
                'bulk_actions-edit-listing',
                array( $this, 'add_bulk_actions' ),
                1989,
                2
            );
            add_filter(
                'handle_bulk_actions-edit-listing',
                array( $this, 'handle_bulk_actions' ),
                1989,
                3
            );
            // $sendback = apply_filters( 'handle_bulk_actions-' . get_current_screen()->id, $sendback, $doaction, $post_ids ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
            // $sendback = apply_filters( 'handle_bulk_actions-' . get_current_screen()->id, $sendback, $doaction, $post_ids ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
            // $actions = apply_filters( 'page_row_actions', $actions, $post );
        }
        
        add_filter(
            'post_type_link',
            [ __CLASS__, 'post_type_link' ],
            1989,
            2
        );
        add_filter(
            'wp_insert_post_data',
            [ __CLASS__, 'wp_insert_post_data' ],
            1989,
            2
        );
        // \wp_delete_post($postid, $force_delete);
        /**
         * Delete meta when the post deleted.
         */
        add_action( 'delete_post', function ( $post_id ) {
            \ListPlus\CRUD\Item_Meta::delete_by_key( 'post_id', $post_id );
            \ListPlus\CRUD\Listing::cache_delete( $post_id );
            global  $wpdb ;
            $sql = "DELETE FROM {$wpdb->prefix}lp_tax_relationships where post_id = %d ";
            $sql = $wpdb->prepare( $sql, $post_id );
            $wpdb->query( $sql );
        } );
        // Disable listing thumbnail.
        add_action(
            'has_post_thumbnail',
            [ $this, 'maybe_disable_thumbnail' ],
            1989,
            2
        );
        // Disable comments.
        add_action(
            'comments_open',
            [ $this, 'maybe_disable_comment' ],
            1989,
            2
        );
    }
    
    public function maybe_disable_thumbnail( $has_thumbnail, $post )
    {
        if ( is_singular( 'listing' ) ) {
            if ( ListPlus()->settings->get( 'no_thumbnail' ) ) {
                $has_thumbnail = false;
            }
        }
        return $has_thumbnail;
    }
    
    public function maybe_disable_comment( $open, $post_id )
    {
        if ( is_singular( 'listing' ) ) {
            if ( ListPlus()->settings->get( 'no_comments' ) ) {
                $open = false;
            }
        }
        return $open;
    }
    
    /**
     * Add more bulk actions for listing table
     *
     * @param array $sendback
     * @param array $doaction
     * @param array $post_ids
     * @return array
     */
    public function handle_bulk_actions( $sendback, $doaction, $post_ids )
    {
        switch ( $doaction ) {
            case 'duplicate':
                foreach ( (array) $post_ids as $post_id ) {
                    $listing = new \ListPlus\CRUD\Listing( $post_id );
                    if ( $listing->is_existing_listing() ) {
                        $listing->dupplicate();
                    }
                }
                break;
            case 'publish':
            case 'pending_review':
            case 'pending_payment':
            case 'rejected':
            case 'expired':
                $count = 0;
                $locked = 0;
                foreach ( (array) $post_ids as $post_id ) {
                    if ( !current_user_can( 'manage_listing' ) ) {
                        wp_die( __( 'Sorry, you are not allowed to update this listing status.', 'list-plus' ) );
                    }
                    if ( !wp_update_post( [
                        'ID'          => $post_id,
                        'post_status' => $doaction,
                    ] ) ) {
                        wp_die( __( 'Error in updating status', 'list-plus' ) );
                    }
                    $count++;
                }
                $sendback = add_query_arg( array(
                    'updated' => $count,
                    'ids'     => join( ',', $post_ids ),
                    'locked'  => $locked,
                ), $sendback );
                break;
        }
        return $sendback;
    }
    
    /**
     * Add more bulk actions
     *
     * @param array $actions
     * @return array
     */
    public function add_bulk_actions( $actions )
    {
        $new_actions = [];
        $new_actions['publish'] = __( 'Change status to publish', 'list-plus' );
        $new_actions['pending_review'] = __( 'Change status to pending review', 'list-plus' );
        $new_actions['rejected'] = __( 'Change status to rejected', 'list-plus' );
        $new_actions['expired'] = __( 'Change status to expired', 'list-plus' );
        $new_actions['duplicate'] = __( 'Duplicate', 'list-plus' );
        $actions = \array_merge( $new_actions, $actions );
        return $actions;
    }
    
    /**
     * Change status from pending to pending review.
     *
     * @param array $data
     * @param array $postarr
     * @return array
     */
    public static function wp_insert_post_data( $data, $postarr )
    {
        if ( 'listing' == $data['post_type'] ) {
            if ( 'pending' == $data['post_status'] ) {
                $data['post_status'] = 'pending_review';
            }
        }
        return $data;
    }
    
    public static function post_type_link( $post_link, $post )
    {
        if ( 'listing' != $post->post_type ) {
            return $post_link;
        }
        return \ListPlus()->request->to_url( 'listing', [
            'name' => $post->post_name,
        ] );
    }
    
    public function page_row_actions( $actions, $post )
    {
        
        if ( 'listing' == $post->post_type ) {
            $new_actions = [ '<span class="ls-id">' . sprintf( __( '#%s', 'list-plus' ), $post->ID ) . '</span>' ];
            return \array_merge( $new_actions, $actions );
        }
        
        return $actions;
    }
    
    public function add_display_post_states( $post_states, $post )
    {
        if ( 'listing' == $post->post_type ) {
            $post_states = [];
        }
        return $post_states;
    }
    
    public static function get_status()
    {
        $status = [];
        $status['publish'] = __( 'Publish', 'list-status' );
        $status['pending_review'] = __( 'Pending', 'list-status' );
        $status['claimed'] = __( 'Claimed', 'list-status' );
        $status['rejected'] = __( 'Rejected', 'list-status' );
        $status['expired'] = __( 'Expired', 'list-status' );
        $status['trash'] = __( 'Trash', 'list-status' );
        $status['draft'] = __( 'Draft', 'list-status' );
        return $status;
    }
    
    public static function listing_views( $views )
    {
        $screen = get_current_screen();
        $post_type = $screen->post_type;
        if ( 'listing' === $post_type ) {
            // Add more custom link status here.
        }
        return $views;
    }
    
    /**
     * Add 'Unread' post status.
     */
    public static function listing_post_status()
    {
        global  $wp_post_statuses ;
        foreach ( static::get_status() as $status => $label ) {
            if ( !isset( $wp_post_statuses[$status] ) ) {
                register_post_status( $status, array(
                    'label'                     => $label,
                    'public'                    => true,
                    'exclude_from_search'       => false,
                    'show_in_admin_all_list'    => true,
                    'show_in_admin_status_list' => true,
                    'label_count'               => _n_noop( $label . ' <span class="count">(%s)</span>', $label . ' <span class="count">(%s)</span>' ),
                ) );
            }
        }
    }
    
    public static function listing_filter_inputs( $columns )
    {
        global  $typenow ;
        global  $wp_query ;
        
        if ( 'listing' == $typenow ) {
            // Your custom post type slug.
            $current_plugin = '';
            \wp_dropdown_categories( [
                'show_option_none'  => __( 'Select category', 'list-plus' ),
                'option_none_value' => '',
                'taxonomy'          => 'listing_cat',
                'name'              => 'listing_cat',
                'value_field'       => 'slug',
                'selected'          => \get_query_var( 'listing_cat' ),
                'hierarchical'      => 1,
            ] );
            \wp_dropdown_categories( [
                'show_option_none'  => __( 'Select type', 'list-plus' ),
                'taxonomy'          => 'listing_type',
                'option_none_value' => '',
                'name'              => 'listing_type',
                'value_field'       => 'slug',
                'selected'          => \get_query_var( 'listing_type' ),
                'hierarchical'      => 1,
            ] );
        }
    
    }
    
    public static function listing_columns( $columns )
    {
        $new_columns = [];
        $new_columns['cb'] = $columns['cb'];
        $new_columns['thumb'] = '<span class="dashicons dashicons-format-image"></span>';
        $new_columns['title'] = $columns['title'];
        $new_columns['status'] = __( 'Status', 'list-plus' );
        $new_columns['rating'] = __( 'Rating', 'list-plus' );
        $new_columns['verified'] = '<span title="' . \esc_attr__( 'Verified', 'list-plus' ) . '" class="dashicons dashicons-yes-alt"></span>';
        $new_columns['taxonomy-listing_type'] = __( 'Type', 'list-plus' );
        $new_columns['taxonomy-listing_region'] = __( 'Region', 'list-plus' );
        foreach ( $columns as $k => $title ) {
            if ( !isset( $new_columns[$k] ) ) {
                $new_columns[$k] = $columns[$k];
            }
        }
        return $new_columns;
    }
    
    public static function listing_content_column( $column, $post_id )
    {
        global  $post, $listing ;
        \ListPlus\setup_listing( $post );
        switch ( $column ) {
            case 'rating':
                $max = 5;
                
                if ( $listing['rating_score'] > 0 ) {
                    echo  \sprintf( '<span class="dashicons dashicons-star-filled"></span> <strong>%s</strong>/%d', number_format_i18n( $listing['rating_score'], 1 ), $max ) ;
                } else {
                    _e( 'N/A', 'list-plus' );
                }
                
                break;
            case 'verified':
                
                if ( $listing['verified'] ) {
                    echo  '<span class="ls-verified dashicons dashicons-yes-alt"></span>' ;
                } else {
                    echo  '<span class="ls-unverified dashicons dashicons-marker"></span>' ;
                }
                
                break;
            case 'status':
                $all_status = static::get_status();
                
                if ( isset( $all_status[$post->post_status] ) ) {
                    echo  '<span class="ls-status stt-' . esc_attr( $post->post_status ) . '">' ;
                    echo  esc_html( $all_status[$post->post_status] ) ;
                    echo  '</span>' ;
                } else {
                    echo  '<span class="ls-status stt-unknown">' ;
                    echo  esc_html( $post->post_status ) ;
                    echo  '</span>' ;
                }
                
                break;
            case 'thumb':
                $thumbnail = get_the_post_thumbnail( $post_id, 'post-thumbnail', '' );
                
                if ( $thumbnail ) {
                    echo  $thumbnail ;
                } else {
                    echo  '<span class="ls-placeholder-img"><span class="dashicons dashicons-format-image"></span></span>' ;
                }
                
                break;
        }
    }
    
    public static function registers()
    {
        $labels = array(
            'name'                  => _x( 'Listings', 'Post type general name', 'list-plus' ),
            'singular_name'         => _x( 'Listing', 'Post type singular name', 'list-plus' ),
            'menu_name'             => _x( 'Listings', 'Admin Menu text', 'list-plus' ),
            'name_admin_bar'        => _x( 'Listing', 'Add New on Toolbar', 'list-plus' ),
            'add_new'               => __( 'Add New', 'list-plus' ),
            'add_new_item'          => __( 'Add New Listing', 'list-plus' ),
            'new_item'              => __( 'New Listing', 'list-plus' ),
            'edit_item'             => __( 'Edit Listing', 'list-plus' ),
            'view_item'             => __( 'View Listing', 'list-plus' ),
            'all_items'             => __( 'All Listings', 'list-plus' ),
            'search_items'          => __( 'Search Listings', 'list-plus' ),
            'parent_item_colon'     => __( 'Parent Listings:', 'list-plus' ),
            'not_found'             => __( 'No listings found.', 'list-plus' ),
            'not_found_in_trash'    => __( 'No listings found in Trash.', 'list-plus' ),
            'featured_image'        => _x( 'Listing Cover Image', 'Overrides the “Featured Image” phrase for this post type. Added in 4.3', 'list-plus' ),
            'set_featured_image'    => _x( 'Set cover image', 'Overrides the “Set featured image” phrase for this post type. Added in 4.3', 'list-plus' ),
            'remove_featured_image' => _x( 'Remove cover image', 'Overrides the “Remove featured image” phrase for this post type. Added in 4.3', 'list-plus' ),
            'use_featured_image'    => _x( 'Use as cover image', 'Overrides the “Use as featured image” phrase for this post type. Added in 4.3', 'list-plus' ),
            'archives'              => _x( 'Listing archives', 'The post type archive label used in nav menus. Default “Post Archives”. Added in 4.4', 'list-plus' ),
            'insert_into_item'      => _x( 'Insert into book', 'Overrides the “Insert into post”/”Insert into page” phrase (used when inserting media into a post). Added in 4.4', 'list-plus' ),
            'uploaded_to_this_item' => _x( 'Uploaded to this book', 'Overrides the “Uploaded to this post”/”Uploaded to this page” phrase (used when viewing media attached to a post). Added in 4.4', 'list-plus' ),
            'filter_items_list'     => _x( 'Filter listings list', 'Screen reader text for the filter links heading on the post type listing screen. Default “Filter posts list”/”Filter pages list”. Added in 4.4', 'list-plus' ),
            'items_list_navigation' => _x( 'Listings list navigation', 'Screen reader text for the pagination heading on the post type listing screen. Default “Posts list navigation”/”Pages list navigation”. Added in 4.4', 'list-plus' ),
            'items_list'            => _x( 'Listings list', 'Screen reader text for the items list heading on the post type listing screen. Default “Posts list”/”Pages list”. Added in 4.4', 'list-plus' ),
        );
        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array(
            'slug' => 'listing',
        ),
            'capability_type'    => 'listing',
            'capabilities'       => array(
            'edit_post'          => 'edit_listing',
            'read_post'          => 'read_listing',
            'delete_post'        => 'delete_listing',
            'edit_posts'         => 'edit_listings',
            'edit_others_posts'  => 'edit_others_listings',
            'publish_posts'      => 'publish_listings',
            'read_private_posts' => 'read_private_listings',
            'create_posts'       => 'create_listings',
        ),
            'map_meta_cap'       => true,
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'menu_icon'          => 'dashicons-list-view',
            'supports'           => array( 'title', 'author' ),
            'rewrite'            => [
            'slug'       => ListPlus()->settings->get( 'rewrite_listing', 'listing' ),
            'with_front' => false,
        ],
        );
        register_post_type( 'listing', $args );
    }

}