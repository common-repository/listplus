<?php

namespace ListPlus;

class Template
{
    public function __construct()
    {
        \add_action( 'wp_loaded', [ $this, 'load' ] );
    }
    
    public function load()
    {
        if ( \is_admin() ) {
            // Do not run this hooks in admin.
            return;
        }
        // if ( $this->is_theme_support() ) {
        // \add_filter( 'template_include', [ $this, 'template_include' ] );
        // \add_action( 'listing_before_main_content', [ $this, 'hook_before_main_content' ] );
        // \add_action( 'listing_after_main_content', [ $this, 'hook_after_main_content' ] );
        // } else {
        // \add_filter( 'the_title', [ $this, 'unsuported_the_title' ], 50, 2 );
        // \add_filter( 'the_content', [ $this, 'unsuported_the_content' ] );
        // \add_filter( 'template_include', [ $this, 'unsuported_untemplate_include' ], 99 );
        // }
        \add_filter(
            'the_title',
            [ $this, 'unsuported_the_title' ],
            50,
            2
        );
        \add_filter( 'the_content', [ $this, 'unsuported_the_content' ] );
        \add_filter( 'template_include', [ $this, 'unsuported_template_include' ], 99 );
        // Content hooks.
        \add_action( 'listplus_listing_content', [ $this, 'the_main' ], 15 );
        \add_action( 'listplus_listing_sidebar', [ $this, 'the_sidebar' ], 35 );
    }
    
    public function unsuported_template_include( $template )
    {
        // Check if is listing archives page.
        
        if ( ListPlus()->query->is_listing_archives() ) {
            return get_page_template();
            // return get_single_template();
        }
        
        return $template;
    }
    
    public function is_theme_support()
    {
        return \get_theme_support( 'listing' );
    }
    
    public function hook_before_main_content()
    {
        $this->get_part( 'global/wrapper-start.php' );
    }
    
    public function hook_after_main_content()
    {
        $this->get_part( 'global/wrapper-end.php' );
    }
    
    /**
     * All template dirs to locate template files.
     *
     * @return array
     */
    public function get_template_dirs()
    {
        $check_dirs = array(
            trailingslashit( get_stylesheet_directory() ) . ListPlus()->template_path(),
            trailingslashit( get_stylesheet_directory() ),
            trailingslashit( get_template_directory() ) . ListPlus()->template_path(),
            trailingslashit( get_template_directory() ),
            trailingslashit( ListPlus()->get_path() ) . 'templates/'
        );
        return $check_dirs;
    }
    
    public function locate_template( $template )
    {
        $file = \apply_filters( 'listplus_locate_template', null, $template );
        if ( \file_exists( $file ) ) {
            // If has filter template.
            return $file;
        }
        foreach ( $this->get_template_dirs() as $dir ) {
            $file = $dir . $template;
            if ( \file_exists( $file ) ) {
                return $file;
            }
        }
        return false;
    }
    
    public function get_part( $template = '' )
    {
        $_template_file = $this->locate_template( $template );
        $content = '';
        if ( $_template_file ) {
            include $_template_file;
        }
    }
    
    public function load_template( $template = '', $args = array() )
    {
        $_template_file = $this->locate_template( $template );
        \extract( $args, EXTR_SKIP );
        // phpcs:ignore
        $_templte_content = null;
        
        if ( $_template_file ) {
            \ob_start();
            include $_template_file;
            $_templte_content = \ob_get_contents();
            \ob_end_clean();
        }
        
        return $_templte_content;
    }
    
    public function get_layout_class()
    {
        $listing = \ListPlus\get_listing();
        $layout = $listing->get_listing_type()->single_layout;
        $show_sidebar = false;
        $classes = 'l-layout-wrapper';
        
        if ( $this->is_theme_support() ) {
            if ( $layout && false !== strpos( $layout, 'sidebar' ) ) {
                $show_sidebar = true;
            }
            if ( $show_sidebar ) {
                $classes .= ' gt-1-col ' . esc_attr( $layout );
            }
        } else {
            $classes .= ' full-width no-sp';
        }
        
        return $classes;
    }
    
    /**
     * For theme support by `add_theme_support('lising')`.
     *
     * @param string $template
     * @return string template path.
     */
    public function template_include( $template )
    {
        
        if ( is_singular( 'listing' ) ) {
            $file = $this->locate_template( 'single-listing.php' );
            if ( $file ) {
                return $file;
            }
        }
        
        return $template;
    }
    
    public function unsuported_the_title( $title, $id )
    {
        if ( !is_singular( 'listing' ) ) {
            return $title;
        }
        global  $post ;
        $listing = \ListPlus\get_listing();
        
        if ( $listing->is_existing_listing() && $listing->get_id() == $id ) {
            $img = \ListPlus()->get_url() . '/assets/images/correct-star.png';
            if ( $img ) {
                $img = '<span class="icon-verifed"><img src="' . $img . '" alt="' . \esc_attr( __( 'Verified', 'list-plus' ) ) . '"/></span>';
            }
            $title .= '<span class="l-verified">' . $img . '</span>';
        }
        
        return $title;
    }
    
    public function get_content_from_fn( $cb, $args = array() )
    {
        $content = null;
        \ob_start();
        $c = call_user_func_array( $cb, $args );
        
        if ( $c ) {
            $content = $c;
        } else {
            $content = \ob_get_contents();
        }
        
        \ob_end_clean();
        return $content;
    }
    
    public function get_archives_content()
    {
        $loop_data = \ListPlus()->query->get_data_for_loop();
        $content = '';
        $content .= $this->load_template( 'filter.php', $loop_data );
        $content .= $this->load_template( 'archives.php', $loop_data );
        if ( $content ) {
            $content = "<div class='listplus-area l-wrapper'>\n" . $content . "\n</div>";
        }
        return $content;
    }
    
    /**
     * For unsupported theme.
     *
     * @param string $content
     * @return string
     */
    public function unsuported_the_content( $content )
    {
        if ( ListPlus()->query->is_listing_archives() ) {
            return $this->get_archives_content();
        }
        if ( !is_singular( 'listing' ) ) {
            return $content;
        }
        global  $post ;
        
        if ( 'listing' === $post->post_type ) {
            $content = null;
            $data = \ListPlus()->query->get_data_for_single();
            $current_action = ListPlus()->frontend->get_query_var( 'action' );
            switch ( $current_action ) {
                case 'photos':
                    $content = $this->load_template( 'single/photos.php', $data );
                    break;
            }
            
            if ( \is_null( $content ) ) {
                $layout_class = ListPlus()->template->get_layout_class();
                $data['layout_class'] = $layout_class;
                $content = $this->load_template( 'single-listing.php', $data );
            }
        
        }
        
        if ( $content ) {
            $content = "<div class='listplus-area l-wrapper'>\n" . $content . "\n</div>";
        }
        return $content;
    }
    
    public function the_main()
    {
        $listing = \ListPlus\get_listing();
        $layout = $listing->get_listing_type()->single_layout;
        echo  '<div class="l-single-main">' ;
        echo  '<div class="l-inner">' ;
        $display = new \ListPlus\Listing_Display();
        $main_fields = $display->get_listing()->get_listing_type()->single_main;
        $main_fields = json_decode( $main_fields, true );
        $display->render( $main_fields );
        // \ListPlus()->template->get_part( 'form/claim.php' );
        // \ListPlus()->template->get_part( 'form/report.php' );
        // \ListPlus()->template->get_part( 'reviews.php' );
        // \ListPlus()->template->get_part( 'form/review.php' );
        echo  '</div>' ;
        echo  '</div>' ;
    }
    
    public function the_sidebar()
    {
        echo  '<div class="l-single-sidebar">' ;
        echo  '<div class="l-inner">' ;
        $display = new \ListPlus\Listing_Display();
        $sidebar_fields = $display->get_listing()->get_listing_type()->single_sidebar;
        $sidebar_fields = json_decode( $sidebar_fields, true );
        $display->render( $sidebar_fields );
        echo  '</div>' ;
        echo  '</div>' ;
    }

}