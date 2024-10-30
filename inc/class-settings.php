<?php

namespace ListPlus;

class Settings
{
    protected  $settings = null ;
    protected  $setting_names = array() ;
    protected  $option_key = 'listplus_settings' ;
    protected  $listing_page_id = null ;
    public function __construct()
    {
        add_action( 'init', array( $this, 'init' ) );
        add_action( 'wp_ajax_listplus_save_settings', array( $this, 'ajax_save_settings' ) );
        
        if ( is_admin() ) {
            add_action( 'admin_notices', [ $this, 'maybe_show_setting_notice' ] );
            if ( isset( $_GET['l_force_settings'] ) ) {
                add_action( 'admin_init', [ $this, 'force_settings' ] );
            }
        }
    
    }
    
    public function force_settings()
    {
        if ( !$this->is_completed_settings() ) {
            return;
        }
        $default = $this->get_default_settings();
        $default_slug = $default['rewrite_listings'];
        if ( !$default_slug ) {
            $default_slug = 'explore';
        }
        $page = get_page_by_path( $default_slug, ARRAY_A );
        
        if ( $page ) {
            $this->settings['listing_page'] = $page['ID'];
        } else {
            $user_id = \get_current_user_id();
            $page_id = wp_insert_post( [
                'post_title'   => __( 'Explore', 'list-plus' ),
                'post_name'    => $default_slug,
                'post_content' => '',
                'post_status'  => 'publish',
                'post_author'  => $user_id,
                'post_type'    => 'page',
            ] );
            if ( $page_id && !\is_wp_error( $page_id ) ) {
                $this->settings['listing_page'] = $page_id;
            }
        }
        
        $this->listing_page_id = $this->settings['listing_page'];
        $this->save();
    }
    
    public function maybe_show_setting_notice()
    {
        if ( !$this->is_completed_settings() ) {
            return;
        }
        ?>
		<div class="notice notice-warning is-dismissible">
			<h3><?php 
        _e( 'ListPlus: You\'re almost done!', 'list-plus' );
        ?></h3>
			<p><?php 
        _e( 'ListPlus requied Listings Page to make it works. Please visit Settings -> Settings and select a page.', 'list-plus' );
        ?></p>
			<p>
				<a href="<?php 
        echo  admin_url( 'edit.php?post_type=listing&page=listing_settings&l_force_settings=1' ) ;
        ?>" class="button-primary"><?php 
        _e( 'Auto Setup', 'list-plus' );
        ?></a>
				<a href="<?php 
        echo  admin_url( 'edit.php?post_type=listing&page=listing_settings' ) ;
        ?>" class="button-secondary"><?php 
        _e( 'Visit Settings Page', 'list-plus' );
        ?></a>
			</p>
		</div>
		<?php 
    }
    
    public function is_completed_settings()
    {
        if ( !$this->listing_page_id ) {
            return true;
        }
        return false;
    }
    
    public function init()
    {
        $defaults = $this->get_default_settings();
        $settings = \get_option( $this->option_key );
        if ( !is_array( $settings ) ) {
            $settings = [];
        }
        $settings = wp_parse_args( $settings, $defaults );
        $listing_page = get_post( $settings['listing_page'] );
        
        if ( $listing_page && 'publish' == $listing_page->post_status ) {
            $this->listing_page_id = $listing_page->ID;
            $settings['rewrite_listings'] = $listing_page->post_name;
        }
        
        $this->settings = $settings;
    }
    
    public function get_settings()
    {
        if ( \is_null( $this->settings ) ) {
            $this->init();
        }
        return $this->settings;
    }
    
    public function ajax_save_settings()
    {
        if ( !\ListPlus()->permissions->is_user_admin() ) {
            die( 'Access denied!' );
        }
        $success_html = '<div class="lp-success"><div class="success-msg">' . __( 'Settings saved.', 'list-plus' ) . '</div></div>';
        $respond = [
            'success'      => true,
            'success_html' => $success_html,
        ];
        $post = wp_unslash( $_POST );
        $setting_tabs = ListPlus()->settings->get_setting_tabs();
        $current_tab = ( isset( $post['current_tab'] ) ? $post['current_tab'] : false );
        if ( !$current_tab ) {
            wp_send_json( $respond );
        }
        $this->get_fied_names( $setting_tabs[$current_tab]['fields'] );
        $respond['field_names'] = $this->setting_names;
        $respond['fields'] = $setting_tabs[$current_tab]['fields'];
        $data = [];
        foreach ( $this->setting_names as $name ) {
            $b_pos = \strpos( $name, '[' );
            if ( $b_pos > 0 ) {
                $name = \substr( $name, 0, $b_pos );
            }
            $data[$name] = ( isset( $post[$name] ) ? $post[$name] : '' );
        }
        $respond['data'] = $data;
        update_option( $this->option_key, \array_merge( $this->get_settings(), $data ) );
        flush_rewrite_rules();
        wp_send_json( $respond );
    }
    
    public function save()
    {
        update_option( $this->option_key, $this->settings );
    }
    
    protected function get_fied_names( $fields )
    {
        foreach ( $fields as $field ) {
            if ( isset( $field['name'] ) ) {
                
                if ( is_array( $field['name'] ) ) {
                    $this->setting_names = \array_merge( $this->setting_names, \array_values( $field['name'] ) );
                } else {
                    $this->setting_names[] = $field['name'];
                }
            
            }
            if ( isset( $field['fields'] ) && !empty($field['fields']) ) {
                $this->get_fied_names( $field['fields'] );
            }
        }
    }
    
    protected function get_default_settings()
    {
        $settings = [
            'listing_page'             => '',
            'submit_page'              => '',
            'listings_per_page'        => 12,
            'distance_unit'            => 'km',
            'listing_status'           => 'pending_review',
            'gmap_api'                 => 'AIzaSyAjxwou0Aae9BOLTSoXeaV0mb9rxx_kB-4',
            'rewrite_listing'          => 'listing',
            'rewrite_listings'         => 'explore',
            'rewrite_listing_cat'      => 'cat',
            'rewrite_listing_type'     => 'type',
            'rewrite_listing_region'   => 'region',
            'rewrite_listing_tax'      => 'features',
            'single_desc_more'         => 1,
            'enquiry_title'            => true,
            'no_thumbnail'             => true,
            'no_comments'              => true,
            'review_max'               => 5,
            'review_title'             => true,
            'review_number'            => 20,
            'review_default_status'    => 'approved',
            'filter_price_range'       => true,
            'filter_price'             => true,
            'recaptcha_enable'         => true,
            'recaptcha_key'            => '',
            'recaptcha_scret'          => '',
            'email_enquiry_send_admin' => 'yes',
            'email_enquiry_send_owner' => 'yes',
            'user_page'                => '',
            'use_wc_dashboard'         => true,
            'purchase_to_claim'        => '',
            'purchase_to_submit'       => true,
            'wc_pricing_page'          => '',
        ];
        flush_rewrite_rules();
        return \apply_filters( 'listplus_settings_default', $settings );
    }
    
    public function get( $key, $default = null )
    {
        if ( \is_null( $this->settings ) ) {
            $this->init();
        }
        if ( isset( $this->settings[$key] ) ) {
            return $this->settings[$key];
        }
        return $default;
    }
    
    public function __set( $name, $value )
    {
        if ( property_exists( $this, $name ) ) {
            $this->name = $value;
        }
        $this->settings[$name] = $value;
    }
    
    public function __get( $name )
    {
        if ( \is_null( $this->settings ) ) {
            $this->init();
        }
        if ( property_exists( $this, $name ) ) {
            return $this->{$name};
        }
        if ( method_exists( $this, 'get_' . $name ) ) {
            return \call_user_func_array( [ $this, 'get_' . $name ], [] );
        }
        if ( array_key_exists( $name, $this->settings ) ) {
            return $this->settings[$name];
        }
        return null;
    }
    
    public function get_use_wc_dashboard()
    {
        return ( isset( $this->settings['use_wc_dashboard'] ) ? $this->settings['use_wc_dashboard'] : null );
    }
    
    public function get_user_page()
    {
        return ( isset( $this->settings['user_page'] ) ? $this->settings['user_page'] : null );
    }
    
    public function get_days()
    {
        $days = [
            'mon' => __( 'Mon', 'list-plus' ),
            'tue' => __( 'Tue', 'list-plus' ),
            'wed' => __( 'Wed', 'list-plus' ),
            'thu' => __( 'Thu', 'list-plus' ),
            'fri' => __( 'Fri', 'list-plus' ),
            'sat' => __( 'Sat', 'list-plus' ),
            'sun' => __( 'Sun', 'list-plus' ),
        ];
        return $days;
    }
    
    public function get_review_ratings()
    {
        $ratings = [
            1 => __( 'Bad', 'list-plus' ),
            2 => __( 'Not bad', 'list-plus' ),
            3 => __( 'Ok', 'list-plus' ),
            4 => __( 'Good', 'list-plus' ),
            5 => __( 'Excellent', 'list-plus' ),
        ];
        return $ratings;
    }
    
    public function get_setting_tabs()
    {
        $setting_tabs = [];
        $setting_tabs['general'] = [
            'id'     => 'general',
            'label'  => __( 'General', 'list-plus' ),
            'fields' => $this->get_general_setting_fields(),
        ];
        $setting_tabs['listing'] = [
            'id'     => 'listing',
            'label'  => __( 'Listing', 'list-plus' ),
            'fields' => $this->get_listing_setting_fields(),
        ];
        return \apply_filters( 'listplus_get_setting_tabs', $setting_tabs );
    }
    
    protected function get_general_setting_fields()
    {
        $fields = [
            [
                'type'   => 'box',
                'title'  => __( 'General', 'list-plus' ),
                'fields' => [
                [
                'type'  => 'pages',
                'name'  => 'listing_page',
                'title' => __( 'Listings Page', 'list-plus' ),
            ],
                [
                'type'  => 'pages',
                'name'  => 'submit_page',
                'title' => __( 'Submit Page', 'list-plus' ),
            ],
                [
                'type'  => 'number',
                'name'  => 'listings_per_page',
                'title' => __( 'Listing per page', 'list-plus' ),
            ],
                [
                'type'    => 'select',
                'name'    => 'distance_unit',
                'title'   => __( 'Distace unit', 'list-plus' ),
                'options' => [
                'km' => __( 'Km (Kilomet)', 'list-plus' ),
                'mi' => __( 'Mi (Mile)', 'list-plus' ),
            ],
            ],
                [
                'type'    => 'select',
                'name'    => 'listing_status',
                'title'   => __( 'Default listing status', 'list-plus' ),
                'options' => \ListPlus\Post_Types::get_status(),
            ]
            ],
            ],
            // end box.
            [
                'type'   => 'box',
                'title'  => __( 'Permalinks', 'list-plus' ),
                'fields' => [
                // [
                // 'type' => 'text',
                // 'name' => 'rewrite_listings',
                // 'default' => 'explore',
                // 'title' => __( 'Listings Slug', 'list-plus' ),
                // 'atts' => [
                // 'placeholder' => 'explore',
                // ],
                // ],
                [
                    'type'  => 'text',
                    'name'  => 'rewrite_listing',
                    'title' => __( 'Single Listing Slug', 'list-plus' ),
                    'atts'  => [
                    'placeholder' => 'listing',
                ],
                ],
                [
                    'type'  => 'text',
                    'name'  => 'rewrite_listing_cat',
                    'title' => __( 'Listing Category', 'list-plus' ),
                    'atts'  => [
                    'placeholder' => 'cat',
                ],
                ],
                [
                    'type'  => 'text',
                    'name'  => 'rewrite_listing_type',
                    'title' => __( 'Listing Type', 'list-plus' ),
                    'atts'  => [
                    'placeholder' => 'type',
                ],
                ],
                [
                    'type'  => 'text',
                    'name'  => 'rewrite_listing_region',
                    'title' => __( 'Listing Region', 'list-plus' ),
                    'atts'  => [
                    'placeholder' => 'region',
                ],
                ],
            ],
            ],
            // end box.
            [
                'type'   => 'box',
                'title'  => __( 'Google Map', 'list-plus' ),
                'fields' => [ [
                'type'    => 'text',
                'name'    => 'gmap_api',
                'default' => '',
                'title'   => __( 'Google Map API', 'list-plus' ),
                'atts'    => [
                'placeholder' => __( 'Your Google Map API key', 'list-plus' ),
            ],
            ] ],
            ],
            // end box.
            [
                'type'   => 'box',
                'title'  => __( 'reCAPTCHA', 'list-plus' ),
                'fields' => [ [
                'type'          => 'checkbox',
                'name'          => 'recaptcha_enable',
                'title'         => __( 'Enable?', 'list-plus' ),
                'checked_value' => 'yes',
            ], [
                'type'    => 'text',
                'name'    => 'recaptcha_key',
                'default' => '',
                'title'   => __( 'Recaptcha key', 'list-plus' ),
                'atts'    => [
                'placeholder' => __( 'Your key here', 'list-plus' ),
            ],
            ], [
                'type'    => 'text',
                'name'    => 'recaptcha_scret',
                'default' => '',
                'title'   => __( 'Recaptcha scret', 'list-plus' ),
                'atts'    => [
                'placeholder' => __( 'Your scret key here', 'list-plus' ),
            ],
            ] ],
            ],
        ];
        return \apply_filters( 'listplus_general_setting_fields', $fields );
    }
    
    protected function get_listing_setting_fields()
    {
        $fields = [
            [
                'type'   => 'box',
                'title'  => __( 'Single Listing', 'list-plus' ),
                'fields' => [
                [
                'type'           => 'select',
                'name'           => 'single_desc_more',
                'title'          => __( 'Toggle description', 'list-plus' ),
                'no_option_none' => true,
                'options'        => [
                ''    => __( 'No', 'list-plus' ),
                'yes' => __( 'Yes', 'list-plus' ),
            ],
                'atts'           => [
                'class' => 'no-select2',
            ],
            ],
                [
                'type'           => 'select',
                'name'           => 'enquiry_title',
                'title'          => __( 'Show enquiry form title', 'list-plus' ),
                'no_option_none' => true,
                'options'        => [
                ''    => __( 'No', 'list-plus' ),
                'yes' => __( 'Yes', 'list-plus' ),
            ],
                'atts'           => [
                'class' => 'no-select2',
            ],
            ],
                [
                'type'           => 'select',
                'name'           => 'no_thumbnail',
                'title'          => __( 'Disable listing thumbnail?', 'list-plus' ),
                'no_option_none' => true,
                'options'        => [
                ''    => __( 'No', 'list-plus' ),
                'yes' => __( 'Yes', 'list-plus' ),
            ],
                'atts'           => [
                'class' => 'no-select2',
            ],
            ],
                [
                'type'           => 'select',
                'name'           => 'no_comments',
                'title'          => __( 'Disable listing comments?', 'list-plus' ),
                'no_option_none' => true,
                'options'        => [
                ''    => __( 'No', 'list-plus' ),
                'yes' => __( 'Yes', 'list-plus' ),
            ],
                'atts'           => [
                'class' => 'no-select2',
            ],
            ]
            ],
            ],
            // end box.
            [
                'type'   => 'box',
                'title'  => __( 'Reviews', 'list-plus' ),
                'fields' => [
                [
                'type'  => 'number',
                'name'  => 'review_max',
                'title' => __( 'Max rating score', 'list-plus' ),
                'atts'  => [
                'placeholder' => 5,
            ],
            ],
                [
                'type'  => 'number',
                'name'  => 'review_number',
                'title' => __( 'Reviews per page', 'list-plus' ),
                'atts'  => [
                'placeholder' => 20,
            ],
            ],
                [
                'type'           => 'select',
                'name'           => 'review_default_status',
                'title'          => __( 'Default review status', 'list-plus' ),
                'no_option_none' => true,
                'options'        => [
                'approved' => __( 'Approved', 'list-plus' ),
                'pending'  => __( 'pending', 'list-plus' ),
                'rejected' => __( 'rejected', 'list-plus' ),
            ],
                'atts'           => [
                'class' => 'no-select2',
            ],
            ],
                [
                'type'           => 'select',
                'name'           => 'review_title',
                'title'          => __( 'Hide form title?', 'list-plus' ),
                'no_option_none' => true,
                'options'        => [
                ''    => __( 'No', 'list-plus' ),
                'yes' => __( 'Yes', 'list-plus' ),
            ],
                'atts'           => [
                'class' => 'no-select2',
            ],
            ]
            ],
            ],
        ];
        return \apply_filters( 'listplus_general_setting_fields', $fields );
    }

}