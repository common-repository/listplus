<?php

namespace ListPlus\Admin;

use  ListPlus\CRUD\Listing_Type ;
class Menu
{
    public static  $page ;
    public static  $view ;
    public static  $show_form = null ;
    public function __construct()
    {
        add_action( 'admin_init', array( __CLASS__, 'setup_args' ), 1 );
        add_action( 'admin_menu', array( __CLASS__, 'menus' ), 1989 );
        add_action( 'submenu_file', array( __CLASS__, 'menu_highlight' ), 1989 );
        add_action(
            'get_edit_post_link',
            array( __CLASS__, 'get_edit_post_link' ),
            1989,
            3
        );
        add_action(
            'admin_title',
            array( __CLASS__, 'admin_title' ),
            1989,
            2
        );
        add_action( 'admin_print_footer_scripts', array( __CLASS__, 'new_listing_btn' ), 1989 );
    }
    
    public static function new_listing_btn()
    {
        $screen = get_current_screen();
        
        if ( $screen && 'edit-listing' == $screen->id ) {
            $active_types = Listing_Type::get_all_active();
            $new_link = \admin_url( 'edit.php?post_type=listing&page=listing_edit' );
            ?>
			<span id="new-listing-dr" class="new-listing-dr page-title-action"><?php 
            _e( 'New Listing', 'list-plus' );
            ?>
				<div class="lp-type-links">
					<div class="ls-inner">
						<?php 
            foreach ( $active_types as $type ) {
                ?>
							<a href="<?php 
                echo  esc_attr( \add_query_arg( [
                    'listing_type' => $type->get_slug(),
                ], $new_link ) ) ;
                ?>"><?php 
                echo  esc_html( $type['name'] ) ;
                ?></a>
						<?php 
            }
            ?>
					</div>
				</div>
			</span>
			<script type="text/javascript">
				jQuery( document ).ready( function( $ ) {
					//console.log( "Abcsa" );
					$( '.wrap .page-title-action' ).replaceWith( $( '#new-listing-dr' )  );
				} );			
			</script>
			<?php 
        }
    
    }
    
    public static function admin_title( $admin_title, $title )
    {
        $screen = get_current_screen();
        switch ( $screen->id ) {
            case 'listing_page_listing_edit':
                
                if ( isset( $_GET['id'] ) && $_GET['id'] > 0 ) {
                    $admin_title = __( 'Edit Listing', 'list-plus' ) . $admin_title;
                } else {
                    $admin_title = __( 'New Listing', 'list-plus' ) . $admin_title;
                }
                
                break;
            case 'listing_page_listing_types':
                
                if ( isset( $_GET['id'] ) && $_GET['id'] > 0 ) {
                    $admin_title = __( 'Edit Listing Type', 'list-plus' ) . $admin_title;
                } else {
                    $admin_title = __( 'New Listing Type', 'list-plus' ) . $admin_title;
                }
                
                break;
        }
        return $admin_title;
    }
    
    public static function get_edit_post_link( $link, $post_id, $context )
    {
        $post = get_post( $post_id );
        if ( 'listing' == $post->post_type ) {
            $link = \add_query_arg( [
                'post_type' => 'listing',
                'page'      => 'listing_edit',
                'id'        => $post_id,
            ], \admin_url( 'edit.php' ) );
        }
        return $link;
    }
    
    public static function menu_highlight()
    {
        global  $parent_file, $submenu_file, $post_type ;
        $screen = get_current_screen();
        
        if ( 'listing_page_listing_edit' == $screen->id ) {
            $parent_file = 'edit.php?post_type=listing';
            // WPCS: override ok.
            $submenu_file = 'edit.php?post_type=listing';
            // WPCS: override ok.
        }
        
        switch ( $post_type ) {
            case 'listing':
                
                if ( $screen && ListPlus()->taxonomies->is_custom( $screen->taxonomy ) ) {
                    $submenu_file = 'edit-tags.php?taxonomy=listing_tax&amp;post_type=listing';
                    // WPCS: override ok.
                    $parent_file = 'edit.php?post_type=listing';
                    // WPCS: override ok.
                }
                
                break;
        }
        return $submenu_file;
    }
    
    public static function setup_args()
    {
        $page = ( isset( $_GET['page'] ) ? sanitize_title( $_GET['page'] ) : '' );
        if ( !$page ) {
            return;
        }
        if ( \strpos( $page, 'listing' ) !== false ) {
            self::$show_form = true;
        }
        self::$view = ( isset( $_GET['view'] ) ? sanitize_title( $_GET['view'] ) : '' );
        self::$page = \str_replace( 'listing_', '', $page );
        if ( self::$show_form ) {
        }
    }
    
    public static function display()
    {
        $page = self::$page;
        $view = self::$view;
        
        if ( 'edit' === $page ) {
            $view = 'edit-listing';
        } else {
            if ( !$view ) {
                $view = $page;
            }
        }
        
        $view_path = LISTPLUS_PATH . '/admin/views/view-' . $view . '.php';
        if ( !\file_exists( $view_path ) ) {
            $view_path = LISTPLUS_PATH . '/admin/views/view-list.php';
        }
        $view_path = \apply_filters( __CLASS__ . '_tempalte_path', $view_path );
        echo  '<div class="wrap lp-area">' ;
        include $view_path;
        echo  '</div>' ;
    }
    
    static function menus()
    {
        global  $submenu, $menu ;
        add_submenu_page(
            null,
            __( 'New Listing', 'list-plus' ),
            __( 'New Listing', 'list-plus' ),
            'manage_listing',
            'listing_edit',
            array( __CLASS__, 'display' )
        );
        add_submenu_page(
            'edit.php?post_type=listing',
            __( 'Listing Types', 'list-plus' ),
            __( 'Listing Types', 'list-plus' ),
            'manage_listing',
            'listing_types',
            array( __CLASS__, 'display' )
        );
        add_submenu_page(
            'edit.php?post_type=listing',
            __( 'Enquiries', 'list-plus' ),
            __( 'Enquiries', 'list-plus' ),
            'manage_listing',
            'listing_enquiries',
            array( __CLASS__, 'display' )
        );
        add_submenu_page(
            'edit.php?post_type=listing',
            __( 'Claim Entries', 'list-plus' ),
            __( 'Claim Entries', 'list-plus' ),
            'manage_listing',
            'listing_claim_entries',
            array( __CLASS__, 'display' )
        );
        add_submenu_page(
            'edit.php?post_type=listing',
            __( 'Reviews', 'list-plus' ),
            __( 'Reviews', 'list-plus' ),
            'manage_listing',
            'listing_reviews',
            array( __CLASS__, 'display' )
        );
        add_submenu_page(
            'edit.php?post_type=listing',
            __( 'Reports', 'list-plus' ),
            __( 'Reports', 'list-plus' ),
            'manage_listing',
            'listing_reports',
            array( __CLASS__, 'display' )
        );
        add_submenu_page(
            'edit.php?post_type=listing',
            __( 'Settings', 'list-plus' ),
            __( 'Settings', 'list-plus' ),
            'manage_listing',
            'listing_settings',
            array( __CLASS__, 'display' )
        );
        // Hide all custom taxonomies.
        $main_menu_key = 'edit.php?post_type=listing';
        if ( isset( $submenu[$main_menu_key] ) ) {
            foreach ( $submenu[$main_menu_key] as $index => $args ) {
                $url = \html_entity_decode( $args[2] );
                
                if ( 'post-new.php?post_type=listing' == $url ) {
                    unset( $submenu[$main_menu_key][$index] );
                } else {
                    $url_parse = \parse_url( \html_entity_decode( $url ) );
                    
                    if ( isset( $url_parse['query'] ) ) {
                        $parse_args = wp_parse_args( $url_parse['query'], [
                            'taxonomy' => '',
                        ] );
                        
                        if ( $parse_args['taxonomy'] && ListPlus()->taxonomies->is_custom( $parse_args['taxonomy'] ) ) {
                            unset( $submenu[$main_menu_key][$index] );
                            $submenu[null][] = $args;
                        }
                    
                    }
                
                }
            
            }
        }
    }

}