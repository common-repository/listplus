<?php

namespace ListPlus;

use ListPlus\CRUD\Listing_Type;

class Request {


	public $routers           = null;
	public $slug              = null;
	public $uri               = null;
	public $uri_parse         = null;
	public $requests          = [];
	public $current_router    = null;
	public $current_router_id = null;

	public $rewrite_listing = null;
	public $rewrite_cat = null;
	public $rewrite_type = null;
	public $rewrite_region = null;
	public $rewrite_tax = null;
	public $archive_page_id = null;

	public function __construct() {
		add_action( 'wp_loaded', [ $this, 'init' ], 3 );
		add_action( 'parse_request', [ $this, 'parse_request' ], 1988 );
		add_action( 'wp', [ $this, 'wp' ], 1988 );
		add_filter( 'paginate_links', [ __CLASS__, 'paginate_links' ], 1988 );

		remove_action( 'template_redirect', 'redirect_canonical' );
		add_filter( 'get_canonical_url', [ $this, 'get_canonical_url' ], 1988, 2 );
	}

	public function get_var( $var, $default = null ) {
		if ( isset( $this->requests[ $var ] ) ) {
			return $this->requests[ $var ];
		}
		return $default;
	}


	public function wp() {
		if ( ! is_admin() ) {
			if ( ! \ListPlus()->query->is_listing_archives() ) {
				return;
			}
			$tax = $this->get_var( 'l_tax' );
			if ( ! $tax ) {
				return;
			}

			global $wp_query;
			global $post;
			$wp_query->is_archive = true;
			$wp_query->is_tax = true;
			$wp_query->is_page = false;
			$wp_query->is_singular = false;
			$wp_query->query['post_type'] = '';
			$wp_query->query_vars['page_id'] = '';
			$wp_query->query_vars['p'] = '';
			$wp_query->query_vars['post_type'] = '';
			$wp_query->query_vars['taxonomy'] = $tax;
			if ( $tax ) {
				$wp_query->query_vars[ $tax ] = $this->get_var( 'term' );
			}
			$post = \get_post( \ListPlus()->settings->get( 'listing_page' ) );
			$wp_query->post = $post;
			$wp_query->posts = [ $post ];
			$term = get_term_by( 'slug', $this->get_var( 'term' ), $this->get_var( 'l_tax' ) );
			if ( $term && ! \is_wp_error( $term ) ) {
				$wp_query->queried_object = $term;
				$wp_query->queried_object_id = $wp_query->queried_object->term_id;
			}
		}
	}

	public function get_canonical_url( $url, $post ) {

		if ( $post->ID == $this->archive_page_id ) {
			$url = null;
		}
		return $url;
	}

	public function get_routers() {
		$routers = [
			// Single listing.
			'listing' => [
				'router' => $this->rewrite_listing . '/:name',
				'query_vars' => [
					'post_type' => 'listing',
				],
				'fallback' => [ // when not using permalink.
					'post_type' => 'listing',
					$this->rewrite_listing => ':name',
				],
			],
			// Listing review form.
			'write-a-review' => [
				'router' => $this->rewrite_listing . '/:name/write-a-review/',
				'is_single' => true,
				'query_vars' => [
					'post_type' => 'listing',
					'action' => 'write-a-review',
				],
				'fallback' => [ // when not using permalink.
					$this->rewrite_listing => ':name',
					'action' => 'write-a-review',
				],
			],

			// Listing review form with defined rating score.
			'write-a-review-rating' => [
				'router' => $this->rewrite_listing . '/:name/write-a-review/:rating/',
				'is_single' => true,
				'query_vars' => [
					'post_type' => 'listing',
					'action' => 'write-a-review',
				],
				'fallback' => [ // when not using permalink.
					$this->rewrite_listing => ':name',
					'rating' => ':rating',
					'action' => 'write-a-review',
				],
			],

			// Single listing send and enquery form.
			'send-an-enquiry' => [
				'router' => $this->rewrite_listing . '/:name/enquiry/',
				'is_single' => true,
				'query_vars' => [
					'post_type' => 'listing',
					'action' => 'enquiry',
				],
				'fallback' => [ // when not using permalink.
					$this->rewrite_listing => ':name',
					'action' => 'enquiry',
				],
			],
			// Alias of `send-an-enquiry`.
			'enquiry' => 'send-an-enquiry',

			// Single claim listing form.
			'claim' => [
				'router' => $this->rewrite_listing . '/:name/claim/',
				'is_single' => true,
				'query_vars' => [
					'post_type' => 'listing',
					'action' => 'claim',
				],
				'fallback' => [ // when not using permalink.
					$this->rewrite_listing => ':name',
					'action' => 'claim',
				],
			],

			// Single report listing form.
			'report' => [
				'router' => $this->rewrite_listing . '/:name/report/',
				'is_single' => true,
				'query_vars' => [
					'post_type' => 'listing',
					'action' => 'report',
				],
				'fallback' => [ // when not using permalink.
					$this->rewrite_listing => ':name',
					'action' => 'report',
				],
			],

			// Single listing reviews.
			'reviews' => [
				'router' => $this->rewrite_listing . '/:name/reviews/',
				'is_single' => true,
				'query_vars' => [
					'post_type' => 'listing',
					'action' => 'reviews',
				],
				'fallback' => [ // when not using permalink.
					$this->rewrite_listing => ':name',
					'action' => 'reviews',
				],
			],

			// Single listing reviews with paging.
			'reviews_page' => [
				'router' => $this->rewrite_listing . '/:name/reviews/:r_paged',
				'is_single' => true,
				'query_vars' => [
					'post_type' => 'listing',
					'action' => 'reviews',
				],
				'fallback' => [ // when not using permalink.
					$this->rewrite_listing => ':name',
					'r_paged' => ':r_paged',
					'action' => 'reviews',
				],
			],

			// Single listing photos.
			'photos' => [
				'router' => $this->rewrite_listing . '/:name/photos/',
				'is_single' => true,
				'query_vars' => [
					'post_type' => 'listing',
					'action' => 'photos',
				],
				'fallback' => [ // when not using permalink.
					$this->rewrite_listing => ':name',
					'action' => 'photos',
				],
			],

			// Single listing photos with paging.
			'photos_page' => [
				'router' => $this->rewrite_listing . '/:name/photos/:p_paged',
				'is_single' => true,
				'query_vars' => [
					'post_type' => 'listing',
					'action' => 'photos',
				],
				'fallback' => [ // when not using permalink.
					$this->rewrite_listing => ':name',
					'p_paged' => ':p_paged',
					'action' => 'photos',
				],
			],

			// Single listing action.
			'action' => [
				'router' => $this->rewrite_listing . '/:name/action/:action/',
				'is_single' => true,
				'query_vars' => [
					'post_type' => 'listing',
				],
				'fallback' => [ // when not using permalink.
					$this->rewrite_listing => ':name',
					'action' => ':action',
				],
			],

		];

		$lisings_slug = \ListPlus()->settings->get( 'rewrite_listings', 'listings' );

		$routers['listings'] = [
			'router' => $lisings_slug . '/', // #name mean it will match any string include `/`.
			'query_vars' => [
				'page_id' => $this->archive_page_id,
			],
			'fallback' => [ // when not using permalink.
				'page_id' => $this->archive_page_id,
			],
		];

		$routers['listings_page'] = [
			'router' => $lisings_slug . '/paged/:paged', // #name mean it will match any string include `/`.
			'query_vars' => [
				'page_id' => $this->archive_page_id,
			],
			'fallback' => [ // when not using permalink.
				'paged' => ':paged',
				'page_id' => $this->archive_page_id,
			],
		];

		foreach ( $this->get_builtin_taxs() as $tax ) {
			$slug = \ListPlus()->settings->get( 'rewrite_' . $tax );
			$routers[ $tax ] = [
				'router' => $slug . '/:term/', // #name mean it will match any string include `/`.
				'query_vars' => [
					'l_post_type' => 'listing',
					'l_tax' => $tax,
					'page_id' => $this->archive_page_id,
				],
				'fallback' => [ // when not using permalink.
					'l_post_type' => 'listing',
					'term' => ':term',
					'l_tax' => $tax,
					'page_id' => $this->archive_page_id,
				],
			];

			$routers[ $tax . '_page' ] = [
				'router' => $slug . '/:term/paged/:paged', // #name mean it will match any string include `/`.
				'query_vars' => [
					'l_post_type' => 'listing',
					'l_tax' => $tax,
					'page_id' => $this->archive_page_id,
				],
				'fallback' => [ // when not using permalink.
					'l_post_type' => 'listing',
					'term' => ':term',
					'paged' => ':paged',
					'l_tax' => $tax,
					'page_id' => $this->archive_page_id,
				],
			];
		}

		$this->routers = $routers;
		return $this->routers;
	}


	public function get_builtin_taxs() {
		$taxs = [ 'listing_cat', 'listing_type', 'listing_region' ];
		return $taxs;
	}

	public static function paginate_links( $link ) {
		$link = remove_query_arg( [ 'action', 'listing_id', 'page' ], $link );
		return $link;
	}

	public function init() {
		$this->rewrite_listing = \ListPlus()->settings->get( 'rewrite_listing' );
		$this->archive_page_id = \ListPlus()->settings->get( 'listing_page' );
		$this->uri = $this->validate_uri( $this->get_request_uri() );
		$this->get_routers();
	}

	/**
	 * Undocumented function
	 *
	 * @param WP $wp
	 * @return void
	 */
	public function parse_request( $wp ) {

		$uri_parse = \explode( '/', $this->uri );
		$uri_parse = \array_filter( $uri_parse, 'strlen' );
		$this->uri_parse = $uri_parse;
		foreach ( $this->routers as $id => $router ) {
			$args = $router;
			if ( ! \is_array( $router ) ) {
				if ( isset( $this->routers[ $router ] ) ) {
					$args = $this->routers[ $router ];
				} else {
					continue;
				}
			}
			$check = $this->check_match( $args['router'], $this->uri );
			if ( $check['match'] ) {
				$this->current_router = $args;
				$this->current_router_id = $id;
				$this->requests = \array_merge( $args['query_vars'], $check['requests'] );
				break;
			}
		} // End check routers.

		if ( $this->current_router_id ) {
			$wp->query_vars['error'] = '';
			$wp->query_vars['post_type'] = '';
			$wp->query_vars['post_name'] = '';
			$wp->query_vars['post_name'] = '';
			$wp->query_vars['post_format'] = '';
			unset( $wp->query_vars['attachment'] );
			unset( $wp->query_vars['post_name'] );
			// For seach listing page.
			if ( isset( $wp->query_vars['s'] ) && $wp->query_vars['s'] ) {
				$wp->query_vars['_s'] = $wp->query_vars['s'];
				$wp->query_vars['s'] = '';
			}
			foreach ( $this->requests as $var => $value ) {
				$wp->query_vars[ $var ] = $value;
			}

			if ( isset( $wp->query_vars['page_id'] ) && $wp->query_vars['page_id'] == $this->archive_page_id ) {
				add_filter( 'redirect_canonical', '__return_false' );
			}
		}

		global $wp_rewrite;
		if ( ! $wp_rewrite->using_permalinks() ) {
			foreach ( $_GET as $k => $v ) {
				$wp->query_vars[ $k ] = $v;
			}
		}

		// if ( ! is_admin() ) {
		// var_dump( $wp->query_vars );
		// var_dump( $this->current_router_id );
		// var_dump( $this->uri );
		// }
	}



	/**
	 * Remove slash (/) before and after string.
	 *
	 * @param string $string
	 * @return string
	 */
	private function validate_uri( $string ) {
		if ( '/' == \substr( $string, 0, 1 ) ) {
			$string = substr( $string, 1 );
		}

		if ( '/' == \substr( $string, -1 ) ) {
			$string = substr( $string, 0, \strlen( $string ) - 1 );
		}

		return $string;
	}

	private function get_request_uri() {
		global $wp_rewrite;
		if ( ! $wp_rewrite->using_permalinks() ) {
			$uri = isset( $_GET['l'] ) ? sanitize_text_field( $_GET['l'] ) : ''; // WPCS: sanitization ok.
		} else {
			$document_root = realpath( $_SERVER['DOCUMENT_ROOT'] );
			$getcwd = realpath( getcwd() );
			$base = str_replace( '\\', '/', str_replace( $document_root, '', $getcwd ) . '/' );
			$uri = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
			if ( ( $base !== $uri ) && ( substr( $uri, -1 ) === '/' ) ) {
				$uri = substr( $uri, 0, ( strlen( $uri ) - 1 ) );
			}
		}

		if ( '' === $uri ) {
			$uri = '/';
		}
		return $uri;
	}

	public function check_match( $router ) {
		$router = $this->validate_uri( $router );
		$setting_parse = explode( '/', $router );
		$check = false;
		$match_router = null;
		$requests = [];
		$found = false;

		if ( ! $found && count( $this->uri_parse ) == count( $setting_parse ) ) {
			$check = true;
			foreach ( $setting_parse as $index => $value ) {
				// If is varivable string.
				if ( ':' == \substr( $value, 0, 1 ) ) {
					$arg_name = substr( $value, 1 );
					$requests[ $arg_name ] = $this->uri_parse[ $index ];
				} else { // If is plain string.
					if ( $value != $this->uri_parse[ $index ] ) {
						$check = false;
					}
				}
			}
			if ( $check ) {
				$match_router = $router;
			}
		}

		return [
			'router' => $match_router,
			'match' => $check,
			'requests' => $requests,
		];
	}

	private function set_requests( $data = [], $type = 'GET' ) {
		switch ( \strtoupper( $type ) ) {
			case 'REQUEST':
				foreach ( $data as $key => $value ) {
					$_REQUEST[ $key ] = $value;
				}
				break;
			case 'POST':
				foreach ( $data as $key => $value ) {
					$_POST[ $key ] = $value;
				}
				break;
			default:
				foreach ( $data as $key => $value ) {
					$_GET[ $key ] = $value;
				}
		}
	}

	public function to_url( $router_id, $args = [] ) {
		if ( empty( $this->routers ) ) {
			$this->get_routers();
		}
		$home = trailingslashit( \home_url() );
		$settings = isset( $this->routers[ $router_id ] ) ? $this->routers[ $router_id ] : false;
		if ( $settings && ! \is_array( $settings ) ) {
			$settings = isset( $this->routers[ $settings ] ) ? $this->routers[ $settings ] : false;
		}

		if ( ! $settings ) {
			return add_query_arg( $args, $home );
		}

		global $wp_rewrite;
		if ( ! \get_option( 'permalink_structure' ) ) {
			if ( isset( $settings['fallback'] ) ) {
				$new_args = [];
				foreach ( $settings['fallback'] as $k => $v ) {
					$k = \str_replace( '{slug}', $this->slug, $k );
					$var_val = $v;
					$var_key = $k;
					if ( ':' == \substr( $v, 0, 1 ) ) {
						$var_key = \substr( $v, 1 );
						$var_val = isset( $args[ $var_key ] ) ? $args[ $var_key ] : '';
					}
					$new_args[ $k ] = $var_val;
				}
				return add_query_arg( $new_args, $home );
			}
		}

		$path = $settings['router'];
		foreach ( (array) $args as $k => $v ) {
			// $k = \str_replace( '{slug}', $this->slug, $k );
			$path = \str_replace( ':' . $k, $v, $path );
		}

		return $home . $path;
	}


	/**
	 * Get term link.
	 *
	 * @see get_term_link()
	 *
	 * @param WP_Term $term
	 * @param string  $taxonomy
	 * @return string
	 */
	public function get_term_link( $term, $taxonomy = '' ) {
		global $wp_rewrite;

		if ( ! is_object( $term ) ) {
			if ( is_int( $term ) ) {
				$term = get_term( $term, $taxonomy );
			} else {
				$term = get_term_by( 'slug', $term, $taxonomy );
			}
		}

		if ( ! is_object( $term ) ) {
			$term = new WP_Error( 'invalid_term', __( 'Invalid term.' ) );
		}

		if ( is_wp_error( $term ) ) {
			return $term;
		}

		$taxonomy = $term->taxonomy;
		$check_tax = \ListPlus()->taxonomies->is_listing_tax( $taxonomy );

		$termlink = '';

		$slug = $term->slug;
		$t  = get_taxonomy( $taxonomy );

		if ( 2 == $check_tax ) {
			$rewrite_slug = \ListPlus()->settings->get( 'rewrite_listing_tax' );
		} elseif ( 1 == $check_tax ) {
			$rewrite_slug = \ListPlus()->settings->get( 'rewrite_' . $taxonomy );
		} else {
			$rewrite_slug = $t->rewrite['slug'];
		}

		$router_args = isset( $this->routers[ $taxonomy ] ) ? $this->routers[ $taxonomy ] : false;
		if ( ! $router_args ) {
			if ( ! $wp_rewrite->using_permalinks() ) {
				$termlink = "?{$rewrite_slug}={$slug}";
				$termlink = home_url( $termlink );
			} else {
				$termlink = $slug;
				$termlink = $rewrite_slug . '/' . $termlink;
				$termlink = home_url( user_trailingslashit( $termlink, 'category' ) );
			}
		} else {
			$term->term = $term->slug;
			return $this->to_url( $taxonomy, (array) $term );
		}

		return $termlink;
	}

	public function get_paged( $var = 'paged' ) {
		$current = (int) \get_query_var( $var );
		$current = max( 0, $current );
		return $current;
	}


	public function get_paging_format() {
		global $wp_rewrite;
		$request_uri = $_SERVER['REQUEST_URI'];
		if ( $wp_rewrite->using_permalinks() ) {
			return 'paged/%#%/';
		} else {
			return 'paged=%#%';
		}
	}

	public function get_paging_base() {
		global $wp_rewrite;
		$request_uri = false;
		if ( \wp_doing_ajax() ) {
			$request_uri = isset( $_REQUEST['link'] ) ? wp_unslash( $_REQUEST['link'] ) : false;
		}
		if ( ! $request_uri ) {
			$request_uri = $_SERVER['REQUEST_URI'];
		}

		$pattern1 = '/paged\/([0-9\/])*/i';
		$request_uri = preg_replace( $pattern1, '%_%', $request_uri );

		$pattern2 = '/paged=([0-9\/])*/i';
		$request_uri = preg_replace( $pattern2, '%_%', $request_uri );

		if ( false === \strpos( $request_uri, '%_%' ) ) {
			if ( ! $wp_rewrite->using_permalinks() ) {
				$request_uri = explode( '?', $request_uri );
				$request_uri[0] = trailingslashit( $request_uri[0] );
				if ( ! isset( $request_uri[1] ) ) {
					$request_uri[1] = '?%_%';
				} else {
					$request_uri[1] .= '&%_%';
				}
				$request_uri = join( '?', $request_uri );
			} else {
				$request_uri = explode( '?', $request_uri );
				$request_uri[0] = trailingslashit( $request_uri[0] );
				$request_uri[0] .= '%_%';
				$request_uri = join( '?', $request_uri );
			}
		}

		return $request_uri;
	}

}
