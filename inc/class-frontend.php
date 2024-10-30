<?php

namespace ListPlus;

class Frontend {
	public function __construct() {

		if ( ! is_admin() ) {
			add_action( 'wp_enqueue_scripts', [ $this, 'load_scripts' ] );
		}
	}

	public function get_query_var( $name, $default = null, $type = 'wp' ) {
		switch ( \strtolower( $type ) ) {
			case 'request':
				return isset( $_REQUEST[ $name ] ) ? sanitize_text_field( $_REQUEST[ $name ] ) : $default;
				break;
			case 'get':
				return isset( $_GET[ $name ] ) ? sanitize_text_field( $_GET[ $name ] ) : $default;
				break;
			case 'post':
				return isset( $_POST[ $name ] ) ? sanitize_text_field( $_POST[ $name ] ) : $default;
				break;
			default:
				return get_query_var( $name, null );
		}
	}

	public function get_action() {
		return $this->get_query_var( 'action' );
	}

	public function load_scripts( $hook ) {

		$gmap_api = ListPlus()->settings->get( 'gmap_api' );

		$js_data = [
			'ajax_url' => \admin_url( 'admin-ajax.php' ),
			'ajax_nonce' => \wp_create_nonce( 'ajax_nonce' ),
			'nav' => [
				ListPlus()->icons->the_icon_svg( 'chevron-left' ),
				ListPlus()->icons->the_icon_svg( 'chevron-right' ),
			],
			'reviews' => \ListPlus()->settings->get_review_ratings(),
			'confirm_del' => __( 'Are you sure?', 'list-plus' ),
			'current_loc_txt' => __( 'Your location', 'list-plus' ),
			'listings_url' => get_page_link( \ListPlus()->settings->get( 'listing_page' ) ),
			'markers' => \ListPlus()->query->get_data_for_map(),
			'marker_icons' => [
				'red' => ListPlus()->get_url() . '/assets/images/marker-red.png',
				'blue' => ListPlus()->get_url() . '/assets/images/marker-blue.png',
			],
		];

		if ( \is_singular( 'listing' ) ) {
			$current_action = ListPlus()->frontend->get_query_var( 'action' );
			$js_data['current_action'] = $current_action;
			$js_data['current_permalink'] = \get_permalink();
		}

		$css_deps = [ 'rateit', 'lightslider', 'lightgallery', 'select2' ];
		$js_deps = [ 'jquery', 'gmap', 'listing-google-map', 'sticky', 'lightgallery', 'lightslider', 'owl-carousel', 'rateit', 'select2' ];

		// Js files.
		wp_register_script( 'gmap', 'https://maps.googleapis.com/maps/api/js?libraries=places&key=' . $gmap_api, array(), false, true );
		wp_register_script( 'owl-carousel', ListPlus()->get_url() . '/assets/owl-carousel/owl.carousel.min.js', array( 'jquery' ), false, true );
		wp_register_script( 'lightslider', ListPlus()->get_url() . '/assets/lightslider/js/lightslider.js', array( 'jquery' ), false, true );
		wp_register_script( 'lightgallery', ListPlus()->get_url() . '/assets/lightgallery/js/lightgallery-all.js', array( 'jquery' ), false, true );
		wp_register_script( 'rateit', ListPlus()->get_url() . '/assets/rateit/jquery.rateit.js', array( 'jquery' ), false, true );
		wp_register_script( 'select2', LISTPLUS_URL . '/assets/js/select2.full.min.js', array(), false, true );
		wp_register_script( 'listing-google-map', LISTPLUS_URL . '/assets/js/frontend-google-map.js', array(), false, true );
		wp_register_script( 'sticky', LISTPLUS_URL . '/assets/js/sticky.js', array(), false, true );

		$show_captcha = false;
		if ( \is_singular( 'listing' ) && ListPlus()->settings->get( 'recaptcha_enable' ) ) {
			$singlar_aciton = ListPlus()->request->get_var( 'action' );
			if ( \in_array( $singlar_aciton, [ 'write-a-review', 'send-an-enquiry', 'claim', 'report' ], true ) ) {
				$show_captcha = true;
			}
			if ( ! $show_captcha ) {
				$listing = \ListPlus\get_listing();
				if ( $listing->get_listing_type()->has_form ) {
					$show_captcha = true;
				}
			}
		}

		$show_captcha  = \apply_filters( 'listplus_frontend_show_captcha', $show_captcha );
		if ( $show_captcha ) {
			$js_deps[] = 'recaptcha';
			$js_data['recaptcha_key'] = ListPlus()->settings->get( 'recaptcha_key' );
			wp_register_script( 'recaptcha', 'https://www.google.com/recaptcha/api.js?render=' . \ListPlus()->settings->get( 'recaptcha_key' ), array(), false, true );
		}

		wp_enqueue_script( 'listing', ListPlus()->get_url() . '/assets/js/frontend.js', $js_deps, false, true );
		wp_enqueue_script( 'listing' );

		// Css files.
		wp_register_style( 'lightslider', ListPlus()->get_url() . '/assets/lightslider/css/lightslider.css', false );
		wp_register_style( 'lightgallery', ListPlus()->get_url() . '/assets/lightgallery/css/lightgallery.css', false );
		wp_register_style( 'rateit', ListPlus()->get_url() . '/assets/rateit/rateit.css', false );
		wp_register_style( 'select2', LISTPLUS_URL . '/assets/css/select2.css', false, '1.0.0' );

		wp_register_style( 'listing', ListPlus()->get_url() . '/assets/css/frontend.css', $css_deps );

		wp_enqueue_style( 'listing' );

		wp_localize_script(
			'listing',
			'ListPlus_Front',
			$js_data
		);

		$css = '';
		$template = \ListPlus\get_theme_slug();

		switch ( $template ) {
			case 'twentyten':
				$css = '#content { margin-right: 20px; }';
				break;
			default:
				$css = '';
				break;
		}

		if ( $css ) {
			wp_add_inline_style( 'listing', $css );
		}

	}

}
