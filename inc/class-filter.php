<?php

namespace ListPlus;

class Filter {
	protected $query_vars = [
		'l_t', // Listing Type.
		'l_tid', // Listing Type ID.
		'l_s', // Search keyword.
		'l_what', // Search listing key words.
		'l_where', // Search listing locations.
		'l_pr', // Price range.
		'l_distance', // Distance.
		'l_sort', // Sort.
		'l_quote', // Accerpt quote.
		'l_cat', // Catefories.
		'l_region', // Region.
		'l_att', // Listing dynamic tax.
		'l_qt', // Quick filter term.
		'l_loc', // Location lat,lng.
		'l_min', // Min price.
		'l_max', // Max price.
	];

	protected $requests = [];
	protected $query = null;

	/**
	 * Listing types.
	 *
	 * @var array.
	 */
	protected $listing_types = null;

	/**
	 * Regions.
	 *
	 * @var array
	 */
	protected $regions = null;
	/**
	 * Categories.
	 *
	 * @var array
	 */
	protected $categories = null;

	/**
	 * Categories.
	 *
	 * @var array
	 */
	protected $dynamic_terms = null;

	/**
	 * Current listing type if match.
	 *
	 * @var ListPlus\CRUD\Listing_Type
	 */
	protected $listing_type = null;


	protected $what = null;
	protected $where = null;
	protected $did_setup = false;


	public function __construct() {
		$this->set_request();
		\add_action( 'before_get_listings', [ $this, 'query' ] );
		// Maybe set cookie history.
		add_action( 'wp', [ $this, 'setup_query_data' ] );

		add_action( 'wp_ajax_ajax_filter_listings', [ $this, 'ajax_filter_listings' ] );
		add_action( 'wp_ajax_nopriv_ajax_filter_listings', [ $this, 'ajax_filter_listings' ] );
		add_action( 'wp_footer', [ $this, 'mobile_filter' ] );
	}

	public function mobile_filter() {
		// Use class `mobile-active` to show the mobile filter.
		echo '<div id="l-mobile-filter" class="l-mobile-filter listplus-area l-wrapper">';
			echo '<div class="mobile-actions">';
			echo '<a href="#" class="l-btn-primary">' . __( 'Search', 'list-plus' ) . '</a>';
			echo '<a href="#" class="l-btn-secondary">' . __( 'Cancel', 'list-plus' ) . '</a>';
			echo '</div>';
			\ListPlus()->filter->form( 'mobile' );
		echo '</div>';
	}

	public function ajax_filter_listings() {
		$post = $this->get_user_request( $_POST );
		unset( $post['action'] );
		$post = \array_filter( $post );
		unset( $post['link'] );
		$http_query = http_build_query( $post );
		$this->set_request( $post );
		$this->setup_query_data();

		$html = \ListPlus()->template->get_archives_content();
		$link = get_page_link( \ListPlus()->settings->get( 'listing_page' ) );

		if ( $http_query ) {
			if ( \strpos( $link, '?' ) ) {
				$link .= '&' . $http_query;
			} else {
				$link .= '?' . $http_query;
			}
		}

		$link = \str_replace( home_url( '/' ), '/', $link );

		wp_send_json(
			[
				'title' => __( 'Search Listings', 'list-plus' ),
				'url' => $link,
				'main_form' => \ListPlus()->template->get_content_from_fn( [ $this, 'form_main' ], [ false ] ),
				'more_form' => \ListPlus()->template->get_content_from_fn( [ $this, 'form_more' ], [ false ] ),
				'more_mobile_form' => \ListPlus()->template->get_content_from_fn( [ $this, 'form_more' ], [ false, 'mobile' ] ),
				'html' => $html,
				'markers' => \ListPlus()->query->get_data_for_map(),
			]
		);
	}

	protected function set_cookies() {
		$what = $this->get_var( 'l_what' );
		$where = $this->get_var( 'l_where' );
		$loc = $this->get_var( 'l_loc' );
		$l_tid = $this->get_var( 'l_tid' );
		$max_history = 10;

		$history_what = $this->get_history( 'l_what' );

		if ( $what && ! $l_tid ) {
			$history_what = $this->get_history( 'l_what' );
			$history_what[ $what ] = 1;
			$history_what = array_reverse( $history_what );
			$history_what = array_slice( $history_what, 0, $max_history );
			setcookie( 'l_what', \json_encode( $history_what ), time() + 60 * 60 * 24 * 60, '/' );
		} else {
			$history_what = array_reverse( $history_what );
		}

		$_COOKIE['l_what'] = $history_what;
		$history_where = $this->get_history( 'l_where' );
		if ( $where && ! $loc ) {
			$history_where[ $where ] = 1;
			$history_where = array_reverse( $history_where );
			$history_where = array_slice( $history_where, 0, $max_history );
			setcookie( 'l_where', \json_encode( $history_where ), time() + 60 * 60 * 24 * 60, '/' );
		} else {
			$history_where = array_reverse( $history_where );
		}
		$_COOKIE['l_where'] = $history_where;

	}

	public function get_history( $var ) {
		if ( isset( $_COOKIE[ $var ] ) ) {
			if ( is_string( $_COOKIE[ $var ] ) ) {
				$data = \json_decode( $_COOKIE[ $var ], true ); // WPCS: sanitization ok.
			} else {
				$data = $_COOKIE[ $var ]; // WPCS: sanitization ok.
			}

			if ( ! \is_array( $data ) ) {
				$data  = [];
			}

			// sanitize input data.
			$data = array_map( 'sanitize_text_field', $data );

			return $data;
		}

		return array();
	}

	protected function sanitize_deep( $v ) {
		if ( ! is_array( $v ) ) {
			$v = sanitize_text_field( (string) $v );
		} else {
			foreach ( $v as $kv => $vv ) {
				$v[ $kv ] = $this->sanitize_deep( $vv );
			}
		}
		return $v;
	}

	protected function get_user_request( $data ) {
		$user_data = [];
		foreach ( $this->query_vars as $var ) {
			$user_data[ $var ] = isset( $data[ $var ] ) ? $this->sanitize_deep( $data[ $var ] ) : null;
		}
		return $user_data;
	}

	protected function set_request( $data = null ) {
		if ( ! is_array( $data ) ) {
			$data = wp_doing_ajax() ? $this->get_user_request( $_POST ) : $this->get_user_request( $_GET ); // WPCS: sanitization ok.
			if ( ! is_array( $data ) ) {
				$data = [];
			}
		}

		if ( empty( $data ) ) {
			return;
		}

		foreach ( $this->query_vars as $var ) {
			$val = isset( $data[ $var ] ) ? $data[ $var ] : null;
			if ( $val ) {
				$this->requests[ $var ] = $val;
			}
		}

		$price_min = (float) $this->get_var( 'l_min' );
		$price_max = (float) $this->get_var( 'l_max' );
		if ( $price_min && $price_max ) {
			if ( $price_min > $price_max ) {
				$t = $price_max;
				$price_max = $price_min;
				$price_min = $t;
			}
			$this->requests['l_min'] = $price_min;
			$this->requests['l_max'] = $price_max;
		}

		$this->set_cookies();
	}

	public function get_extra_filters() {
		$group_filters = [];
		$group_filters['sort'] = [
			'label' => __( 'Sortby', 'list-plus' ),
			'type' => 'radio',
			'name' => 'l_sort',
			'list' => [
				'recommended' => __( 'Recommended', 'list-plus' ),
				'highest_rated' => __( 'Highest rated', 'list-plus' ),
				'most_reviewed' => __( 'Most reviewed', 'list-plus' ),
			],
		];

		if ( $this->get_var( 'l_loc' ) ) {

			$unit = \ListPlus()->settings->distance_unit;

			$defined_distances = [
				'bird_view' => __( "Bird's-eye view", 'list-plus' ),
				'driving' => sprintf( __( 'Driving (5%s.)', 'list-plus' ), $unit ),
				'biking' => sprintf( __( 'Biking (2%s.)', 'list-plus' ), $unit ),
				'walking' => sprintf( __( 'Walking (1%s.)', 'list-plus' ), $unit ),
			];

			$group_filters['distance'] = [
				'label' => __( 'Distance', 'list-plus' ),
				'type' => 'radio',
				'name' => 'l_distance',
				'list' => $defined_distances,
			];
		}

		// Dynamic tax.
		if ( $this->listing_type ) {
			global $wpdb;
			$type = $this->listing_type;
			$cat_terms = \get_terms(
				[
					'taxonomy' => 'listing_cat',
					'include' => $type->restrict_categories,
					'orderby' => 'parent',
				]
			);
			$cat_list = [];
			if ( $cat_terms && ! \is_wp_error( $cat_terms ) ) {
				foreach ( $cat_terms as $t ) {
					$cat_list[ $t->term_id ] = $t->name;
				}
			}

			$group_filters['cat'] = [
				'label' => __( 'Category', 'list-plus' ),
				'type' => 'checkbox',
				'name' => 'l_cat',
				'list' => $cat_list,
			];

			$support_taxs = $type->get_support_taxs();
			foreach ( $support_taxs as $tax ) {
				$tax_args = \ListPlus()->taxonomies->get_custom( $tax );

				if ( 'yes' == $tax_args['exclude_filter'] ) {
					continue;
				}
				$terms = \get_terms(
					[
						'taxonomy' => $tax,
						'orderby' => 'name',
						'order' => 'ASC',
					]
				);
				if ( $terms && ! \is_wp_error( $terms ) ) {
					$new_terms = [];
					$ids = [];
					$list = [];
					foreach ( $terms as $t ) {
						$ids[] = $t->term_id;
						$list[ $t->term_id ] = $t->name;
						$new_terms[ $t->term_id ] = [
							'id' => $t->term_id,
							'name' => $t->name,
							'slug' => $t->slug,
						];
					}
					$sql = '';
					$query = new \ListPlus\CRUD\Query();
					$query->table = $wpdb->prefix . 'lp_tax_relationships';
					$query->select( 'term_id', 'id' )
					->select( 'custom_value', 'name' )
					->group( 'custom_value' )
					->where( 'taxonomy', $tax )
					->where_in( 'term_id', $ids )
					// ->where_not( 'custom_value', '' )
					->order_by( 'custom_value', 'asc' );

					$custom_vars = $query->find();
					if ( ! empty( $custom_vars ) ) {
						$filter_settings = [ 'general' => [] ];
						// var_dump( $custom_vars );
						$old_list = $list;
						$list = [];

						$custom_list = [];
						foreach ( $custom_vars as $ct ) {
							$custom_list[ $ct['id'] ] = $ct;
							$t = $new_terms[ $ct['id'] ];
						}

						foreach ( $old_list as $id => $name ) {
							$list[ $id ] = $name;
							if ( isset( $custom_list[ $id ] ) ) {
								$ct = $custom_list[ $id ];
								if ( $ct['name'] ) {
									$cid = $id . '|' . $ct['name'];
									$list[ $cid ] = [
										'name' => $name,
										'sub' => $ct['name'],
									];
								}
							}
						}
					}

					$group_filters[ $tax ] = [
						'label' => $tax_args['filter_label'],
						'name' => 'l_att[' . $tax_args['term_id'] . ']',
						'id' => $tax_args['term_id'],
						'type' => 'checkbox',
						'list' => $list,
					];
				}
			}
		} else {
			$group_filters['cat'] = [
				'label' => __( 'Category', 'list-plus' ),
				'type' => 'checkbox',
				'tax' => 'listing_cat',
				'name' => 'l_cat',
			];
		}

		return $group_filters;
	}

	public function get_var( $var, $default = null ) {
		$pos = \strpos( $var, '[' );
		if ( false !== $pos ) {
			$var = \substr( $var, 0, $pos );
		}
		if ( isset( $this->requests[ $var ] ) ) {
			return $this->requests[ $var ];
		}
		return $default;
	}

	protected function get_check_list_li( $args, $values ) {
		$val = $args['val'];
		$input_type = $args['type'];
		$input_name = $args['input_name'];
		$label = $args['label'];
		$checked = '';
		if ( ! empty( $values ) ) {
			$checked = \in_array( (string) $val, $values, true ) ? ' checked="checked" ' : '';
		}
		$li = '<li>';

		if ( ! is_array( $label ) ) {
			$html_label = esc_html( $label );
		} else {
			$html_label = esc_html( $label['name'] ) . '<em class="l-sub-name">' . esc_html( $label['sub'] ) . '</em>';
		}

		$li .= '<label class="l-checkbox"><input type="' . $input_type . '" ' . $checked . ' value="' . esc_attr( $val ) . '" name="' . esc_attr( $input_name ) . '"/><span class="cb-name">' . $html_label . '</span></label>';

		$li .= '</li>';
		return $li;
	}

	protected function the_check_list( $args, $button = false, $input_price = false ) {
		$args = wp_parse_args(
			$args,
			[
				'name' => '',
				'label' => '',
				'id' => '',
				'type' => 'radio',
				'list' => [],
				'tax' => '',
				'_type' => '',
			]
		);

		if ( 'price' == $args['_type'] ) {
			if ( ! \ListPlus()->settings->get( 'filter_price' ) && ! \ListPlus()->settings->get( 'filter_price_range' ) ) {
				return;
			}
		}

		$values = $this->get_var( $args['name'] );
		if ( ! \is_array( $values ) ) {
			$values = (array) $values;
		}
		if ( $args['id'] ) {
			$values = isset( $values[ $args['id'] ] ) ? (array) $values[ $args['id'] ] : [];
		}

		$max_li = 5;
		$list = $args['list'] ? $args['list'] : [];
		$input_type = ( 'radio' == $args['type'] ) ? 'radio' : 'checkbox';
		$li_list = [];
		$input_name = $args['name'];
		if ( 'checkbox' == $input_type ) {
			$input_name .= '[]';
		}

		if ( $args['tax'] ) {
			$terms = get_terms( [ 'taxonomy' => $args['tax'] ] );
			foreach ( $terms as $t ) {
				$list[ $t->term_id ] = $t->name;
			}
		}

		foreach ( (array) $list as $val => $name ) {
			$li_list[] = $this->get_check_list_li(
				[
					'val' => $val,
					'input_name' => $input_name,
					'type' => $input_type,
					'label' => $name,
				],
				$values
			);
		}
		if ( empty( $li_list ) ) {
			return;
		}

		$show_list = true;
		if ( 'price' == $args['_type'] ) {
			if ( ! \ListPlus()->settings->get( 'filter_price_range' ) ) {
				$show_list = false;
			}
		}

		?>
		<div class="lg-sub-list">
			<?php if ( $args['label'] ) { ?>
			<span><?php echo $args['label']; ?></span>
			<?php } ?>
					<ul>
			<?php
			if ( $show_list ) {
				if ( count( $list ) > $max_li ) {
					$check_list = array_slice( $li_list, 0, $max_li );
					$modal_list = array_slice( $li_list, $max_li );
					echo join( "\n", $check_list );
					echo '<li>';
					$modal_id = uniqid( 'lm-' );
					echo '<a class="lm-trigger" data-selector="#' . $modal_id . '" href="#">See all</a>';
					$modal_html = '<div class="lg-sub-list"><ul>' . join( "\n", $modal_list ) . '</ul></div>';
					$modal_args = [
						'id' => $modal_id,
						'class' => 'lm-m lm-filter-atts',
						'title' => sprintf( __( 'More %s', 'list-plus' ), $args['label'] ),
						'content' => $modal_html,
						'primary' => __( 'Search', 'list-plus' ),
						'primary_type' => 'submit',
					];
					\ListPlus\the_modal( $modal_args );
					echo '</li>';
				} else {
					echo join( "\n", $li_list );
				}
			}

			if ( 'price' == $args['_type'] && \ListPlus()->settings->get( 'filter_price' ) ) {
				$price_filter = $this->get_price_filter();
				?>
				<li>
					<input type="text" class='l-quick-input' name="l_min" data-label="<?php echo esc_attr( $price_filter['min_label'] ); ?>" value="<?php echo esc_attr( $price_filter['min'] ); ?>" placeholder="min">
				</li>
				<li>
					<input type="text" class='l-quick-input' name="l_max" data-label="<?php echo esc_attr( $price_filter['max_label'] ); ?>" value="<?php echo esc_attr( $price_filter['max'] ); ?>" placeholder="max">
				</li>
				<?php
			}
			?>
			</ul>
			<?php if ( $button ) { ?>
				<span class="l-q-btn l-btn-primary" ><?php _e( 'Save', 'list-plus' ); ?></span>
			<?php } ?>
		</div>
		<?php
	}

	public function the_extra_fields() {
		foreach ( $this->get_extra_filters() as $key => $args ) {
			$this->the_check_list( $args );
		}
	}

	public function get_price_range() {
		return [
			'name' => 'l_pr',
			'type' => 'checkbox',
			'list' => [
				'1' => __( '$', 'list-plus' ),
				'2' => __( '$$', 'list-plus' ),
				'3' => __( '$$$', 'list-plus' ),
				'4' => __( '$$$$', 'list-plus' ),
			],
		];
	}

	public function setup_types() {

		if ( ! is_null( $this->listing_types ) ) {
			return $this->listing_types;
		}

		$what = $this->get_var( 'l_what' );
		$what = \strtolower( $what );
		$id = (int) $this->get_var( 'l_tid' );
		if ( $id > 0 ) {
			$type = new \ListPlus\CRUD\Listing_Type( $id );
			if ( $type->get_id() ) {
				$this->listing_type = $type;
				$this->listing_types = [ $type ];
				return $this->listing_types;
			}
		}
		$search_what = sanitize_title_with_dashes( $what );
		if ( ! $search_what ) {
			$this->listing_type = false;
			$this->listing_types = [];
			return $this->listing_types;
		}
		$search_what = \strtolower( $search_what );
		$search_what = explode( '-', $search_what );
		$all_active = \ListPlus\CRUD\Listing_Type::get_all_active();
		$scores = [];
		$search_results = [];

		foreach ( $all_active  as $t ) {
			$count_m = 0;
			$t_name = \strtolower( $t->name );
			if ( $t_name == $what ) { // extra match.
				$this->listing_type = $t;
				$this->listing_types = [ $t ];
				return $this->listing_types;
			}
			foreach ( $search_what as $k ) {
				if ( false !== \strpos( $t_name, $k ) ) {
					$count_m ++;
				}
			}

			$t_name = sanitize_title_with_dashes( $t_name );
			$t_array = explode( '-', $t_name );
			$tc = count( $t_array );
			$found = false;

			// Check if full match all words of listing type.
			if ( $tc >= $count_m && count( $search_what ) == $tc ) {
				$count = 0;
				foreach ( $t_array as $ti => $tv ) {
					if ( isset( $search_what [ $ti ] ) ) {
						if ( $tv == $search_what [ $ti ] ) {
							$count ++;
						}
					}
				}

				if ( $count == $tc ) {
					$scores[] = 99999;
					$found = true;
					$this->listing_type = $t;
					$this->listing_types = [ $t ];
					return $this->listing_types;
				}
			}

			if ( ! $found && $count_m > 0 ) {
				$scores[] = $count_m;
				$search_results[] = $t;
			}
		}

		if ( empty( $search_results ) ) {
			$this->listing_type = false;
			$this->listing_types = [];
			return $this->listing_types;
		}

		array_multisort( $scores, SORT_DESC, $search_results );
		$this->listing_type = array_slice( $search_results, 0, 1 );
		$this->listing_types = $search_results;
		return $this->listing_types;

	}

	public function setup_regions() {
		if ( ! is_null( $this->regions ) ) {
			return $this->regions;
		}

		$where = $this->get_var( 'l_where' );
		$search_where = sanitize_text_field( $where );
		if ( ! $search_where ) {
			$this->regions = [];
			return $this->regions;
		}
		$terms = get_terms(
			[
				'taxonomy' => 'listing_region',
				'search' => $search_where,
				'number' => 5,
			]
		);

		if ( ! $terms || \is_wp_error( $terms ) ) {
			$this->regions = [];
			return $this->regions;
		}

		$this->regions = $terms;
		return $this->regions;
	}

	protected function parse_dymanic_tax_query() {

		if ( empty( $this->dynamic_terms ) ) {
			return;
		}

		$atts       = $this->get_var( 'l_att' );
		$tax_query  = [];
		$or         = [];
		$table_name = 'lt';
		global $wpdb;

		foreach ( $this->dynamic_terms as $dt ) {
			$term_ids = [];
			foreach ( $atts[ $dt->term_id ] as $tv ) {
				$tva = explode( '|', $tv );
				$n = (int) $tva[0];
				$term_ids[ $n ] = $n;
				if ( count( $tva ) > 1 && $tva[1] ) {
					$or[] = $wpdb->prepare( " ( {$table_name}.term_id = %d AND {$table_name}.custom_value = %s )  ", $tva[0], $tva[1] );
				} else {
					$or[] = $wpdb->prepare( " ( {$table_name}.term_id = %d )  ", $tva[0] );
				}
			}
		}

		if ( ! empty( $or ) ) {
			return ' AND ( ' . join( "\nOR\n", $or ) . ' ) ';
		}

		return null;

	}



	/**
	 * Check if the terms are suitable for searching.
	 *
	 * Uses an array of stopwords (terms) that are excluded from the separate
	 * term matching when searching for posts. The list of English stopwords is
	 * the approximate search engines list, and is translatable.
	 *
	 * @since 3.7.0
	 *
	 * @param string[] $terms Array of terms to check.
	 * @return array Terms that are not stopwords.
	 */
	protected function parse_search_terms( $terms ) {
		$strtolower = function_exists( 'mb_strtolower' ) ? 'mb_strtolower' : 'strtolower';
		$checked    = array();

		$stopwords = $this->get_search_stopwords();

		foreach ( $terms as $term ) {
			// keep before/after spaces when term is for exact match
			if ( preg_match( '/^".+"$/', $term ) ) {
				$term = trim( $term, "\"'" );
			} else {
				$term = trim( $term, "\"' " );
			}

			// Avoid single A-Z and single dashes.
			if ( ! $term || ( 1 === strlen( $term ) && preg_match( '/^[a-z\-]$/i', $term ) ) ) {
				continue;
			}

			if ( in_array( call_user_func( $strtolower, $term ), $stopwords, true ) ) {
				continue;
			}

			$checked[] = $term;
		}

		return $checked;
	}

	/**
	 * Retrieve stopwords used when parsing search terms.
	 *
	 * @since 3.7.0
	 *
	 * @return array Stopwords.
	 */
	protected function get_search_stopwords() {
		if ( isset( $this->stopwords ) ) {
			return $this->stopwords;
		}

		/*
		 * translators: This is a comma-separated list of very common words that should be excluded from a search,
		 * like a, an, and the. These are usually called "stopwords". You should not simply translate these individual
		 * words into your language. Instead, look for and provide commonly accepted stopwords in your language.
		 */
		$words = explode(
			',',
			_x(
				'about,an,are,as,at,be,by,com,for,from,how,in,is,it,of,on,or,that,the,this,to,was,what,when,where,who,will,with,www',
				'Comma-separated list of search stopwords in your language'
			)
		);

		$stopwords = array();
		foreach ( $words as $word ) {
			$word = trim( $word, "\r\n\t " );
			if ( $word ) {
				$stopwords[] = $word;
			}
		}

		/**
		 * Filters stopwords used when parsing search terms.
		 *
		 * @since 3.7.0
		 *
		 * @param string[] $stopwords Array of stopwords.
		 */
		$this->stopwords = apply_filters( 'wp_search_stopwords', $stopwords );
		return $this->stopwords;
	}

	protected function setup_quick_terms() {
		if ( ! $this->listing_type || ! $this->listing_type->quick_filters ) {
			return;
		}
			$quick_term_ids = $this->get_var( 'l_qt' );
		if ( empty( $quick_term_ids ) ) {
			return;
		}

		$term_ids = explode( ',', $this->listing_type->quick_filters );
		$ids = [];
		foreach ( $term_ids as $id ) {
			$id = absint( $id );
			if ( $id > 0 ) {
				$ids[ $id ] = $id;
			}
		}

		$submit_ids = [];

		foreach ( $quick_term_ids as $index => $id ) {
			if ( isset( $ids[ $id ] ) ) {
				$submit_ids[] = $id;
			}
		}

		if ( empty( $submit_ids ) ) {
			return;
		}

		$terms = \get_terms(
			[
				'include' => $submit_ids,
			]
		);

		if ( ! $terms || \is_wp_error( $terms ) ) {
			return;
		}

		foreach ( $terms as $t ) {
			switch ( $t->taxonomy ) {
				case 'listing_cat':
					$this->categories[] = $t->term_id;
					break;
				case 'listing_region':
					$this->regions[] = $t;
					break;
				default:
					$this->dynamic_terms[] = $t;
					break;
			}
		}

	}

	protected function setup_dynamic_terms() {
		$atts = $this->get_var( 'l_att' );
		$this->dynamic_terms = [];
		if ( empty( $atts ) ) {
			return null;
		}

		$d_tax_ids = \array_keys( $atts );
		$d_terms = \get_terms(
			[
				'hide_empty' => false,
				'taxonomy' => 'listing_tax',
				'include' => join( ',', $d_tax_ids ),
			]
		);

		if ( ! $d_terms || \is_wp_error( $d_terms ) ) {
			return;
		}

		$this->dynamic_terms = $d_terms;
	}

	public function setup_query_data() {
		if ( $this->did_setup ) {
			return;
		}
		$this->what = $this->get_var( 'l_what' );
		$this->where = $this->get_var( 'l_where' );
		$this->setup_regions();
		$this->setup_types();
		$this->setup_dynamic_terms();

		// Listing cats.
		$cat_ids = $this->get_var( 'l_cat' );
		$this->categories = [];
		if ( ! empty( $cat_ids ) ) {
			foreach ( $cat_ids as $id ) {
				$id = absint( $id );
				if ( $id > 0 ) {
					$this->categories[] = $id;
				}
			}
		}

		$this->setup_quick_terms();

		$this->did_setup = true;
	}

	protected function parse_tax_query() {
		// Skip if using filter.
		if ( ! empty( $this->dynamic_terms ) ) {
			return;
		}

		// Skip if not is tax page.
		if ( ! ListPlus()->query->is_tax() ) {
			return null;
		}

		$terms = [];
		$terms[] = \get_query_var( 'term' );
		$taxonomy = \get_query_var( 'l_tax' );

		$tax_query = [
			// 'relation' => 'AND',
			[
				'taxonomy'      => $taxonomy,
				'field'         => 'slug',
				'terms'         => $terms,
				'operator'      => 'IN',
			],
		];

		$tax_query = new \WP_Tax_Query( $tax_query );
		return $tax_query->get_sql( 'p', 'ID' );
	}


	public function query( $listing_query ) {

		if ( empty( $this->requests ) ) {
			return; // skip if not using filter.
		}

		$listing_query->skip_main_query = true;
		global $wpdb;
		$lt_table = $wpdb->prefix . 'lp_tax_relationships';
		$listing_query->query->table = $wpdb->posts;

		$table_meta = \ListPlus\CRUD\Item_Meta::get_table();
		$public_status = [ 'publish', 'claimed' ];
		$paged = ListPlus()->request->get_paged();
		$tax_where = $this->parse_dymanic_tax_query();
		$per_page = ListPlus()->settings->get( 'listings_per_page', 25 );

		$listing_query->query->table_as( 'p' )
			->select( 'DISTINCT( p.ID )' )
			->select( 'p.*' )
			->select( 'mt.*' )
			->where( 'p.post_type', 'listing' )
			->where_in( 'p.post_status', $public_status )
			->limit( $per_page )
			->page( $paged )
			->count_var( ' COUNT( DISTINCT( p.ID ) ) as tt_rows' );

		$listing_query->query->left_join( $table_meta, 'p.ID = mt.post_id', 'mt' );
		if ( $tax_where ) {
			$listing_query->query->left_join( $lt_table, 'p.ID = lt.post_id', 'lt' );
			$listing_query->query->where_raw( $tax_where );
		}

		$tq = $this->parse_tax_query();
		if ( $tq && $tq['join'] && $tq['where'] ) {
			$listing_query->query->join_raw( $tq['join'] );
			$listing_query->query->where_raw( $tq['where'] );
		}

		// Listing categories.
		if ( ! empty( $this->categories ) ) {
			$tax_query = [
				[
					'taxonomy'      => 'listing_cat',
					'field'         => 'term_id',
					'terms'         => $this->categories,
					'operator'      => 'IN',
				],
			];

			$tax_query = new \WP_Tax_Query( $tax_query );
			$tq = $tax_query->get_sql( 'p', 'ID' );
			if ( $tq && $tq['join'] && $tq['where'] ) {
				$listing_query->query->join_raw( $tq['join'] );
				$listing_query->query->where_raw( $tq['where'] );
			}
		}

		// Lising Type and search what.
		$what_or_sql = [];
		$what = sanitize_text_field( $this->get_var( 'l_what' ) );
		$l_tid = sanitize_text_field( $this->get_var( 'l_tid' ) );
		if ( $l_tid && $this->listing_type ) {
			$what_or_sql[] = ' mt.type_id IN ' . $listing_query->to_sql_in( [ $this->listing_type->get_id() ] );
		} elseif ( $what ) {
			$what = esc_sql( $what );
			$n = 0;
			if ( $this->listing_types ) {
				$n = count( $this->listing_types );
			}

			if ( $n > 0 ) {
				$type_ids = wp_list_pluck( $this->listing_types, 'term_id' );
				$what_or_sql[] = ' mt.type_id IN ' . $listing_query->to_sql_in( $type_ids );
			} else {
				$what_or_sql[] = ' p.post_title LIKE \'%' . $what . '%\' ';
			}

			if ( ! empty( $what_or_sql ) ) {
				$search_what = ' AND ( ' . join( ' OR ', $what_or_sql ) . ' ) ';
				$listing_query->query->where_raw( $search_what );
			}
		}

		if ( ! empty( $what_or_sql ) ) {
			$search_what = ' AND ( ' . join( ' OR ', $what_or_sql ) . ' ) ';
			$listing_query->query->where_raw( $search_what );
		}

		// Need to improve.
		if ( $what ) {

		} else {
			if ( $what ) {
				$listing_query->query->where_like( 'p.post_title', $what );
			}
		}

		// Regions and search where.
		$where = $this->get_var( 'l_where' );
		if ( $where ) {
			$search_where = '';
			$where = esc_sql( $where );
			$what_or_sql = [];
			if ( $this->regions && count( $this->regions ) ) {
				$regions_ids = wp_list_pluck( $this->regions, 'term_id' );
				$what_or_sql[] = ' mt.region_id IN ' . $listing_query->to_sql_in( $regions_ids );
			}
			$what_or_sql[] = ' mt.address LIKE \'%' . $where . '%\' ';
			$what_or_sql[] = ' mt.zipcode = \'' . $where . '\' ';

			if ( ! empty( $what_or_sql ) ) {
				$search_where .= ' AND ( ';
				$search_where .= join( ' OR ', $what_or_sql );
				$search_where .= ' ) ';
				$listing_query->query->where_raw( $search_where );
			}
		}

		// Price ranges.
		$price_range = $this->get_var( 'l_pr' );
		if ( $price_range && ! empty( $price_range ) ) {
			$price_range  = array_map( 'absint', $price_range );
			$listing_query->query->where_in( 'mt.price_range', $price_range );
		}

		// Price Limit by values.
		$min = (float) $this->get_var( 'l_min' );
		$max = (float) $this->get_var( 'l_max' );
		if ( $min && $max ) {
			$listing_query->query->where_raw( $wpdb->prepare( ' AND ( mt.price >= %d AND mt.price <= %d ) ', $min, $max ) );
		} elseif ( $min ) {
			$listing_query->query->where_raw( $wpdb->prepare( ' AND mt.price >= %d ', $min ) );
		} elseif ( $max ) {
			$listing_query->query->where_raw( $wpdb->prepare( ' AND mt.price <= %d ', $max ) );
		}

		// Distance.
		$distance = $this->get_var( 'l_distance' );
		$loc = $this->get_var( 'l_loc' );
		if ( $loc ) {
			$loc = explode( ',', $loc );
			if ( count( $loc ) == 2 ) {
				$loc[0] = (float) $loc[0];
				$loc[1] = (float) $loc[1];
			} else {
				$loc = false;
			}
		}

		// Skip use current user location if searching regions.
		if ( empty( $this->regions ) && $distance && $loc && count( $loc ) == 2 ) {
			$unit = \ListPlus()->settings->get( 'distance_unit', 'km' );
			$lat = $loc[0];
			$lng = $loc[1];

			switch ( $distance ) {
				case 'biking':
					$proximity  = 2; // km, mi.
					break;
				case 'biking':
					$proximity  = 5; // km.
					break;
				case 'driving':
					$proximity  = 10; // km.
					break;
				default: // Bird's-eye view.
					$proximity  = 50; // km.
			}
			$earth_radius = $unit == 'mi' ? 3959 : 6371;
			$listing_query->query->select_raw(
				$wpdb->prepare(
					'( %s * acos(
					cos( radians(%s) ) *
					cos( radians( mt.lat ) ) *
					cos( radians( mt.lng ) - radians(%s) ) +
					sin( radians( %s) ) *
					sin( radians( mt.lat ) )
				) ) as `distance` ',
					$earth_radius,
					$lat,
					$lng,
					$lat
				)
			);
			$listing_query->query->having( $wpdb->prepare( 'distance < %s', $proximity ) );
		}

		// Sort.
		$sort = $this->get_var( 'l_sort' );
		switch ( $sort ) {
			case 'highest_rated':
				$listing_query->query->order_by( 'rating_score', 'desc' );
				break;
			case 'most_reviewed':
				$listing_query->query->order_by( 'count_review', 'desc' );
				break;
		}

	}

	public function form( $mod = '', $submit = false ) {
		$action = get_page_link( \ListPlus()->settings->get( 'listing_page' ) );
		?>
		<form  autocomplete="off" class="l-form l-filters l-filters-all" action="<?php echo esc_url( $action ); ?>" method="get">
			<?php
			$this->form_main( false, $mod );
			$this->form_more( false, $mod );
			if ( $submit ) {
				?>
				<div class="lf-action">
					<button class="lf-submit l-btn-primary" type="submit"><?php _e( 'Search', 'list-plus' ); ?></button>
				</div>
				<?php
			}
			?>
		</form>
		<?php
	}

	public function form_main( $form_tag = true ) {

		$action = get_page_link( \ListPlus()->settings->get( 'listing_page' ) );
		$what = $this->get_var( 'l_what' );
		$type_id = $this->get_var( 'l_tid' );
		$where = $this->get_var( 'l_where' );
		$quote = $this->get_var( 'l_quote' );
		$slider_svg = ListPlus()->icons->the_icon_svg( 'equalizer2' );

		$distance = $this->get_var( 'l_distance' );
		$sort = $this->get_var( 'l_sort' );
		$cats = $this->get_var( 'l_cat' );
		$type = $this->listing_type;
		$types  = $this->setup_types();
		if ( $type ) {
			$what = $type->name;
		}
		$active_more_filter = false;

		if ( ( $distance && 'bird_view' != $distance ) || ( $sort && 'recommended' !== $sort ) || ! empty( $cats ) ) {
			$active_more_filter = true;
		}

		$all_active = \ListPlus\CRUD\Listing_Type::get_all_active();
		$clock_svg = \ListPlus()->icons->the_icon_svg( 'time1' );
		$loc = $this->get_var( 'l_loc' );
		if ( $form_tag ) {
			$action = get_page_link( \ListPlus()->settings->get( 'listing_page' ) );
			?>
			<form class="l-form l-filters l-filters-main" autocomplete="off" action="<?php echo esc_url( $action ); ?>" method="get">
			<?php } ?>
			<div class="lf-main">
				<div class="lf-main-input">
					<div class="lf-main-f lf-type">
						<input type="search" autocomplete="off" class="l-visible ls-search l-what" value="<?php echo \esc_attr( $what ); ?>" name="l_what" placeholder="<?php esc_attr_e( 'What are you looking for?', 'list-plus' ); ?>" />
						<input type="hidden" autocomplete="off" class="l-builtin-hidden" name="l_tid" value="<?php echo \esc_attr( $type_id ); ?>" />
						<?php if ( $all_active ) { ?>
						<ul class="l-main-dropdown"> 
							<?php
							foreach ( $all_active as $at ) {
								$svg = '';
								if ( $at->icon ) {
									$svg = \ListPlus()->icons->the_icon_svg( $at->icon );
								}
								?>
							<li><a href="#" title="<?php echo esc_attr( $at->name ); ?>" data-value="<?php echo esc_attr( $at->term_id ); ?>" class="builtin f-icon"><?php echo $svg . esc_html( $at->name ); ?></a></li>
							<?php } ?>
							<?php
							foreach ( $this->get_history( 'l_what' ) as $label => $check ) {
								?>
							<li><a href="#" title="<?php echo esc_attr( $label ); ?>"  class="f-icon"><?php echo $clock_svg . esc_html( $label ); ?></a></li>
							<?php } ?>
						</ul>
						<?php } ?>
					</div>
					<div class="lf-main-f lf-region">
						<input type="search" class="l-visible ls-search l-where" autocomplete="off" value="<?php echo \esc_attr( $where ); ?>" name="l_where" placeholder="<?php esc_attr_e( 'Address, city, state or zip', 'list-plus' ); ?>" />
						<input type="hidden" class="l-your-loaction l-builtin-hidden"  autocomplete="off" value="<?php echo esc_attr( $loc ); ?>" name="l_loc">
						<span class="l-current-loc l-ask-location">
							<svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
							viewBox="0 0 469.333 469.333" style="enable-background:new 0 0 469.333 469.333;" xml:space="preserve">
							<path d="M234.667,149.333c-47.147,0-85.333,38.187-85.333,85.333S187.52,320,234.667,320S320,281.813,320,234.667
								S281.813,149.333,234.667,149.333z M425.387,213.333C415.573,124.373,344.96,53.76,256,43.947V0h-42.667v43.947
								C124.373,53.76,53.76,124.373,43.947,213.333H0V256h43.947c9.813,88.96,80.427,159.573,169.387,169.387v43.947H256v-43.947
								C344.96,415.573,415.573,344.96,425.387,256h43.947v-42.667H425.387L425.387,213.333z M234.667,384
								c-82.453,0-149.333-66.88-149.333-149.333s66.88-149.333,149.333-149.333S384,152.213,384,234.667S317.12,384,234.667,384z"/>
							</svg>
						</span>
						<?php
						$where_histoty = $this->get_history( 'l_where' );
						if ( ! empty( $where_histoty ) ) {
							?>
						<ul class="l-main-dropdown"> 
							<?php foreach ( $where_histoty as $label => $check ) {
								?>
							<li><a href="#" title="<?php echo esc_attr( $label ); ?>" class="f-icon"><?php echo $clock_svg . esc_html( $label ); ?></a></li>
						<?php } ?>
						</ul>
						<?php } ?>
					</div>
				</div>
				
				<div class="lf-action">
					<button class="lf-submit" title="<?php \esc_attr_e( 'Search', 'list-plus' ); ?>" type="submit"><?php _e( 'Search', 'list-plus' ); ?></button>
				</div>
			</div>
		<?php if ( $form_tag ) { ?>
		</form>
		<?php } ?>
		<?php
	}

	public function get_price_filter() {
		$min_label = '';
		$max_label = '';
		$min = (float) $this->get_var( 'l_min' );
		$max = (float) $this->get_var( 'l_max' );
		if ( $min > 0 ) {
			$min_label = \ListPlus\Helper::price_format( $min );
		} else {
			$min = '';
		}

		if ( $max > 0 ) {
			$max_label = \ListPlus\Helper::price_format( $max );
		} else {
			$max = '';
		}

		if ( $min_label && ! $max_label ) {
			$min_label = sprintf( __( 'Min %s', 'list-plus' ), $min_label );
		}

		if ( $max && ! $min_label ) {
			$max_label = sprintf( __( 'Max %s', 'list-plus' ), $max_label );
		}

		return [
			'min' => $min,
			'max' => $max,
			'min_label' => $min_label,
			'max_label' => $max_label,
		];
	}

	public function form_more( $form_tag = true, $mod = '' ) {

		$slider_svg = ListPlus()->icons->the_icon_svg( 'equalizer2' );
		$distance = $this->get_var( 'l_distance' );
		$sort = $this->get_var( 'l_sort' );
		$cats = $this->get_var( 'l_cat' );
		$atts = $this->get_var( 'l_att' );
		$type = $this->listing_type;
		$active_more_filter = false;

		if ( ( $distance && 'bird_view' != $distance ) || ( $sort && 'recommended' !== $sort ) || ! empty( $cats ) || ! empty( $atts ) ) {
			$active_more_filter = true;
		}

		$all_active = \ListPlus\CRUD\Listing_Type::get_all_active();
		$clock_svg = \ListPlus()->icons->the_icon_svg( 'time1' );
		$loc = $this->get_var( 'l_loc' );

		$price_filter = $this->get_price_filter();

		if ( $form_tag ) {
			$action = get_page_link( \ListPlus()->settings->get( 'listing_page' ) );
			?>
		<form class="l-form l-filters l-filters-more" action="<?php echo esc_url( $action ); ?>" method="get">
		<?php } ?>
			<div class="lf-more-wrapper">
				<?php if ( 'mobile' != $mod ) { ?>
				<div class="lf-quick">
					<span class="l-quick-btn l-filter-all <?php echo $active_more_filter ? 'selected' : ''; ?> f-icon"><?php echo $slider_svg; ?><span><?php _e( 'All', 'list-plus' ); ?></span></span>
					<?php if ( \ListPlus()->settings->get( 'filter_price' ) || \ListPlus()->settings->get( 'filter_price_range' ) ) { ?>
					<span class="l-quick-dropdown"><span class="l-quick-btn l-quick-label"><?php _e( 'Price', 'list-plus' ); ?></span>
						<?php
						$range_settings = $this->get_price_range();
						$range_settings['_type'] = 'price';
						$this->the_check_list( $range_settings, true );
						?>
					</span>
					<?php } ?>
					<?php
					if ( $type ) {
						$this->more_quick_filters( $type->quick_filters );
					}
					?>
				</div>
				<?php } else { ?>
				<?php if ( \ListPlus()->settings->get( 'filter_price' ) ) { ?>
				<div class="l-price-inputs">
					<div>
						<input type="text" class='l-quick-input' name="l_min" data-label="<?php echo esc_attr( $price_filter['min_label'] ); ?>" value="<?php echo esc_attr( $price_filter['min'] ); ?>" placeholder="<?php esc_attr_e( 'Min Price', 'list-plus' ); ?>">
					</div>
					<div>
						<input type="text" class='l-quick-input' name="l_max" data-label="<?php echo esc_attr( $price_filter['max_label'] ); ?>"  value="<?php echo esc_attr( $price_filter['max'] ); ?>" placeholder="<?php esc_attr_e( 'Max Price', 'list-plus' ); ?>">
					</div>
				</div>
				<?php } ?>
				<?php if ( \ListPlus()->settings->get( 'filter_price_range' ) ) { ?>
				<div class="price-levels">
					<?php
					$this->the_check_list( $this->get_price_range(), true );
					?>
				</div>
				<?php } ?>

				<?php if ( $type ) { ?>
				<div class="lf-quick">
					<?php
					$this->more_quick_filters( $type->quick_filters );
					?>
				</div>
				<?php } ?>
					
				<?php } ?>

				<div class="lf-subs <?php echo $active_more_filter ? 'active' : ''; ?>">
					<?php
					$this->the_extra_fields();
					?>	
				</div>
			</div>
			<?php if ( $form_tag ) { ?>
		</form>
		<?php } ?>
		<?php
	}

	public function more_quick_filters( $string_terms ) {
		$term_ids = explode( ',', $string_terms );
		$ids = [];
		foreach ( $term_ids as $id ) {
			$id = absint( $id );
			if ( $id > 0 ) {
				$ids[] = $id;
			}
		}
		if ( ! empty( $ids ) ) {
			$qt = $this->get_var( 'l_qt' );
			if ( ! \is_array( $qt ) ) {
				$qt = [];
			}
			$terms = \get_terms(
				[
					'include' => $ids,
				]
			);

			if ( $terms && ! \is_wp_error( $terms ) ) {
				foreach ( $terms as $t ) {
					$current = false;
					if ( ! empty( $qt ) ) {
						if ( \in_array( (string) $t->term_id, $qt, true ) ) {
							$current = $t->term_id;
						}
					}
					echo '<label class="l-quick-btn"><input name="l_qt[]" value="' . esc_attr( $t->term_id ) . '" ' . checked( $current, $t->term_id, false ) . ' type="checkbox"/>' . esc_html( $t->name ) . '</label>';
				}
			}
		}
	}

}

