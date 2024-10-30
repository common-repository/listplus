<?php

/**
 * Plugin Name: ListPlus
 * Plugin URI: https://listpluswp.com/
 * Description: Build unlimited listing directory on your WordPress. Fast and Simple.
 * Version: 1.0.4
 * Author: ListPlus
 * Author URI: https://github.com/listplus/
 * Text Domain: list-plus
 * Domain Path: languages/
 *
 */
if ( !defined( 'ABSPATH' ) ) {
    return;
}
define( 'LISTPLUS_PATH', dirname( __FILE__ ) );
define( 'LISTPLUS_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );
use  ListPlus\CRUD\Model ;
use  ListPlus\Admin\Menu ;
use  ListPlus\Post_Types ;
use  ListPlus\Taxonomies ;
use  ListPlus\Error ;
use  ListPlus\Form ;
use  ListPlus\Ajax_Form ;
use  ListPlus\Icons ;
use  ListPlus\Cron ;
use  ListPlus\Request ;
use  ListPlus\Template ;
use  ListPlus\Frontend ;
use  ListPlus\Settings ;
use  ListPlus\Permissions ;
use  ListPlus\Query ;
use  ListPlus\Filter ;
class ListPlus
{
    private static  $_instance = null ;
    protected  $initialized = null ;
    /**
     *
     * @var ListPlus\Error
     */
    public  $error ;
    /**
     * Admin Menu
     *
     * @var ListPlus\Admin\Menu
     */
    public  $menu ;
    /**
     * ACF From.
     *
     * @var ListPlus\ACF\Form_Front
     */
    public  $form ;
    /**
     *
     * @var ListPlus\Taxonomies
     */
    public  $taxonomies ;
    /**
     *
     * @var ListPlus\Post_Types
     */
    public  $post_types ;
    /**
     *
     * @var ListPlus\Icons
     */
    public  $icons ;
    /**
     *
     * @var ListPlus\Cron
     */
    public  $cron ;
    /**
     *
     * @var ListPlus\Request
     */
    public  $request ;
    /**
     *
     * @var ListPlus\Frontend
     */
    public  $frontend ;
    /**
     *
     * @var ListPlus\Template
     */
    public  $template ;
    /**
     *
     * @var ListPlus\Settings
     */
    public  $settings ;
    /**
     *
     * @var ListPlus\Permissions
     */
    public  $permissions ;
    /**
     *
     * @var ListPlus\Query
     */
    public  $query ;
    /**
     *
     * @var ListPlus\Filter
     */
    public  $filter ;
    /**
     *
     * @var ListPlus\Mailer
     */
    public  $mailer ;
    /**
     * Dynamic method data.
     *
     * @var array
     */
    private  $data = array() ;
    public static function get_instance()
    {
        
        if ( is_null( static::$_instance ) ) {
            static::$_instance = new static();
            static::$_instance->init();
            static::$_instance->initialized = true;
        }
        
        return static::$_instance;
    }
    
    public function __set( $name, $value )
    {
        if ( property_exists( $this, $name ) ) {
            $this->name = $value;
        }
        $this->data[$name] = $value;
    }
    
    public function __get( $name )
    {
        if ( property_exists( $this, $name ) ) {
            return $this->{$name};
        }
        if ( array_key_exists( $name, $this->data ) ) {
            return $this->data[$name];
        }
        return null;
    }
    
    public function __unset( $name )
    {
        unset( $this->data[$name] );
    }
    
    public static function __callStatic( $name, $arguments )
    {
        if ( method_exists( static::$_instance, $name ) ) {
            return call_user_func_array( [ static::$_instance, $name ], $arguments );
        }
    }
    
    public function get( $name )
    {
        if ( method_exists( static::$_instance, 'get_' . $name ) ) {
            return call_user_func_array( [ static::$_instance, 'get_' . $name ], [] );
        }
        if ( !did_action( 'listplus_loaded' ) ) {
            wp_die( sprintf( __( 'Please call %1$s method after hook %2$s', 'list-plus' ), '<code>' . __CLASS__ . '::get</code>', '<code>listplus_loaded</code>' ) );
        }
        if ( property_exists( static::$_instance, $name ) ) {
            return static::$_instance->{$name};
        }
        if ( array_key_exists( $name, static::$_instance->data ) ) {
            return static::$_instance->data[$name];
        }
        return null;
    }
    
    public function set( $name, $value )
    {
        static::$_instance->{$name} = $value;
    }
    
    public function get_path()
    {
        return LISTPLUS_PATH;
    }
    
    public function get_url()
    {
        return LISTPLUS_URL;
    }
    
    public function template_path()
    {
        return 'listing/';
    }
    
    private function load()
    {
        require_once LISTPLUS_PATH . '/inc/fs.php';
        require_once LISTPLUS_PATH . '/inc/class-error.php';
        require_once LISTPLUS_PATH . '/crud/query.php';
        require_once LISTPLUS_PATH . '/crud/class-model.php';
        require_once LISTPLUS_PATH . '/crud/class-enquiry.php';
        require_once LISTPLUS_PATH . '/crud/class-review.php';
        require_once LISTPLUS_PATH . '/crud/class-report.php';
        require_once LISTPLUS_PATH . '/crud/class-claim.php';
        require_once LISTPLUS_PATH . '/crud/class-taxonomy.php';
        require_once LISTPLUS_PATH . '/crud/class-post.php';
        require_once LISTPLUS_PATH . '/crud/class-item-meta.php';
        require_once LISTPLUS_PATH . '/crud/class-scheduled-task.php';
        require_once LISTPLUS_PATH . '/crud/class-listing-type.php';
        require_once LISTPLUS_PATH . '/crud/class-listing-category.php';
        require_once LISTPLUS_PATH . '/crud/class-listing-dynamic-tax.php';
        require_once LISTPLUS_PATH . '/crud/class-listing.php';
        require_once LISTPLUS_PATH . '/inc/class-listing-display.php';
        require_once LISTPLUS_PATH . '/inc/class-settings.php';
        require_once LISTPLUS_PATH . '/inc/class-query.php';
        require_once LISTPLUS_PATH . '/inc/class-validate.php';
        require_once LISTPLUS_PATH . '/inc/class-filter.php';
        require_once LISTPLUS_PATH . '/inc/class-permissions.php';
        require_once LISTPLUS_PATH . '/inc/class-frontend.php';
        require_once LISTPLUS_PATH . '/inc/class-template.php';
        require_once LISTPLUS_PATH . '/inc/class-request.php';
        require_once LISTPLUS_PATH . '/inc/class-cron.php';
        require_once LISTPLUS_PATH . '/inc/class-icons.php';
        require_once LISTPLUS_PATH . '/inc/listing-functions.php';
        require_once LISTPLUS_PATH . '/inc/template-functions.php';
        require_once LISTPLUS_PATH . '/inc/class-helper.php';
        require_once LISTPLUS_PATH . '/admin/inc/class-listing_type-table.php';
        require_once LISTPLUS_PATH . '/admin/inc/class-listing-table.php';
        require_once LISTPLUS_PATH . '/admin/inc/class-enquires-list-table.php';
        require_once LISTPLUS_PATH . '/admin/inc/class-reviews-list-table.php';
        require_once LISTPLUS_PATH . '/admin/inc/class-reports-list-table.php';
        require_once LISTPLUS_PATH . '/admin/inc/class-claim-entries-list-table.php';
        require_once LISTPLUS_PATH . '/inc/class-post-types.php';
        require_once LISTPLUS_PATH . '/inc/class-taxonomies.php';
        require_once LISTPLUS_PATH . '/inc/class-form.php';
        require_once LISTPLUS_PATH . '/inc/class-ajax-form.php';
        require_once LISTPLUS_PATH . '/admin/class-menu.php';
        require_once LISTPLUS_PATH . '/admin/view-table.php';
        // Widgets.
        require_once LISTPLUS_PATH . '/inc/class-sidebar.php';
        require_once LISTPLUS_PATH . '/inc/class-widget-map.php';
        require_once LISTPLUS_PATH . '/inc/class-widget-single.php';
        require_once LISTPLUS_PATH . '/inc/class-widget-filter.php';
    }
    
    /**
     * Check if is listing pages.
     *
     * @return boolean
     */
    public function is_listing_forms()
    {
        $is = false;
        
        if ( is_admin() ) {
            $current_screen = get_current_screen();
            if ( strpos( $current_screen->id, 'listing', 1 ) ) {
                $is = true;
            }
            // Or custom listing taxs.
            if ( strpos( $current_screen->id, 'ltx', 1 ) ) {
                $is = true;
            }
        } else {
            $submit_page_id = $this->settings->get( 'submit_page' );
            // for test.
            if ( $submit_page_id ) {
                if ( is_page( $submit_page_id ) ) {
                    $is = true;
                }
            }
        }
        
        return apply_filters( 'listplus_is_listing_forms', $is );
    }
    
    /**
     * Check if is submit listing page.
     *
     * @return boolean
     */
    public function is_submit_listing_form()
    {
        $is = false;
        
        if ( is_admin() ) {
            $current_screen = get_current_screen();
            if ( 'listing_page_listing_edit' == $current_screen->id ) {
                $is = true;
            }
        } else {
            if ( is_page( $this->form->submit_page_id ) ) {
                $is = true;
            }
        }
        
        return apply_filters( 'listplus_is_submit_listing_form', $is );
    }
    
    /**
     * Check if is add new or edit listing type page.
     *
     * @return boolean
     */
    public function is_edit_listing_type()
    {
        $is = false;
        
        if ( is_admin() ) {
            $current_screen = get_current_screen();
            if ( 'listing_page_listing_types' == $current_screen->id ) {
                $is = true;
            }
        }
        
        return apply_filters( 'listplus_is_edit_listing_type', $is );
    }
    
    private function int_cron()
    {
        $this->cron = new Cron();
    }
    
    public static function install()
    {
        require_once LISTPLUS_PATH . '/inc/class-db.php';
        $db = new \ListPlus\Database();
        $db->install();
        do_action( 'listplus_install' );
    }
    
    public static function uninstall()
    {
        do_action( 'listplus_uninstall' );
        require_once LISTPLUS_PATH . '/inc/class-db.php';
        $db = new \ListPlus\Database();
        $db->uninstall();
    }
    
    private function init()
    {
        if ( $this->initialized ) {
            return;
        }
        $this->load();
        $this->request = new Request();
        $this->menu = new Menu();
        $this->icons = new Icons();
        $this->error = new Error();
        $this->post_types = new Post_Types();
        $this->taxonomies = new Taxonomies();
        $this->form = new Form();
        new Ajax_Form();
        $this->template = new Template();
        $this->frontend = new Frontend();
        $this->settings = new Settings();
        $this->permissions = new Permissions();
        $this->query = new Query();
        $this->filter = new Filter();
        $this->int_cron();
        register_activation_hook( __FILE__, array( __CLASS__, 'install' ) );
        register_deactivation_hook( __FILE__, array( __CLASS__, 'uninstall' ) );
        load_plugin_textdomain( 'list-plus', false, LISTPLUS_PATH . '/languages' );
        /**
         *  Same the hook `the_content`
         *
         * @TODO: do not effect content by plugins
         *
         * 8 WP_Embed:run_shortcode
         * 8 WP_Embed:autoembed
         * 10 wptexturize
         * 10 wpautop
         * 10 shortcode_unautop
         * 10 prepend_attachment
         * 10 wp_make_content_images_responsive
         * 11 capital_P_dangit
         * 11 do_shortcode
         * 20 convert_smilies
         */
        global  $wp_embed ;
        $content_tags = [
            'the_listing_content',
            'listing_review_content',
            'listing_enquiery_content',
            'listing_report_content'
        ];
        foreach ( $content_tags as $tag ) {
            add_filter( $tag, array( $wp_embed, 'run_shortcode' ), 8 );
            add_filter( $tag, array( $wp_embed, 'autoembed' ), 8 );
            add_filter( $tag, 'wptexturize' );
            add_filter( $tag, 'wpautop' );
            add_filter( $tag, 'shortcode_unautop' );
            add_filter( $tag, 'wp_make_content_images_responsive' );
            add_filter( $tag, 'capital_P_dangit' );
            add_filter( $tag, 'do_shortcode' );
            add_filter( $tag, 'convert_smilies' );
        }
        add_action( 'widgets_init', [ $this, 'widgets_init' ] );
        // ListPlus fully loaded.
        do_action( 'listplus_loaded' );
        // Test Theme support.
        // add_theme_support( 'listing' );
    }
    
    public function widgets_init()
    {
        register_widget( 'ListPlus_Map_Widget' );
        register_widget( 'ListPlus_Listing_Details_Widget' );
        register_widget( 'ListPlus_Filter_Widget' );
    }

}
/**
 * ListPlus
 *
 * @return ListPlus
 */
function ListPlus()
{
    return ListPlus::get_instance();
}

ListPlus();