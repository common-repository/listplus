<?php

namespace ListPlus\CRUD;

use ListPlus\Helper;
use ListPlus\CRUD\Model;
use ListPlus\CRUD\Post;
use ListPlus\CRUD\Listing_Type;
use \ListPlus\CRUD\Item_Meta;
use \ListPlus\CRUD\Taxonomy;

use \ListPlus\CRUD\Enquiry;
use \ListPlus\CRUD\Review;
use \ListPlus\CRUD\Report;
use \ListPlus\CRUD\Claim;

use \ListPlus\Url;



class Listing implements \ArrayAccess {

	private $data = [
		'ID' => 0,
		'm_id' => 0,
	];

	private $previous_data = [];

	private $post_type = 'listing';

	private $post_fields = [
		'ID',
		'post_author',
		'post_date',
		'post_date_gmt',
		'post_content',
		'post_title',
		'post_name',
		'post_excerpt',
		'post_status',
		'comment_status',
		'ping_status',
		'post_password',
		'post_modified',
		'post_modified_gmt',
		'post_parent',
		'menu_order',
		'post_type',
		'post_mime_type',
		'comment_count',
	];

	private $meta_fields__ = [
		'mid',
		'post_id',
		'listing_type',
		'type_id',
		'item_id', // Custom ID for item.
		'expired',
		'start_date', // Use for listing has start date such as event.
		'end_date', // Use for listing has end date such as event.
		'verified',
		'claimed',
		'price',
		'price_range',
		'region_id',
		'address',
		'address_2',
		'city',
		'zipcode',
		'state',
		'country_code',
		'lat',
		'lng',
		'phone',
		'email',
		'video_url',
		'enquiry_status',

		// 'square_feet', // Use for real estate.
		// 'lot_square_feet',
		// 'bedrooms',
		// 'bathrooms',
		// 'floors',
		// 'half_baths',
		// 'pool',
		// 'garage',
		// 'parking',
		// 'year_built',
		'count_review',
		'rating_score',
		'rating_meta',
		'count_report',
	];

	/**
	 * The field not save when update or inser if $force arg for save method is `false`
	 *
	 * @var array
	 */
	protected $force_updated_fields = [
		'count_review',
		'rating_score',
		'count_report',
	];

	private $tax_fields = [
		'categories' => [
			'tax' => 'listing_cat',
			'multiple' => true,
		],
		'region' => [
			'tax' => 'listing_region',
			'multiple' => false,
		],
	];


	/**
	 * Preset custom fields.
	 *
	 * This is wp custom fields which stored in wp_post_meta table.
	 * The meta key in database start with `_`
	 *
	 * @var array
	 */
	private $custom_fields = [
		'video_url',
		'open_hours',
		'websites',
		// 'enquiry_status',
		'claim_status',
		'comment_status',
		'report_status',
		'review_status',
		// 'media_files',
	];

	private $media_fields = [
		'media_files',
	];

	/**
	 * @var ListPlus\CRUD\Listing_Type
	 */
	private $listing_type = null;

	private static $cache_group = 'listing_items';
	private static $cache_key = 'listing_item_';

	protected static $data_tables = [
		'\ListPlus\CRUD\Item_Meta',
	];

	public function __construct( $data = array() ) {

		if ( $data instanceof $this ) {
			$data = $data->to_array();
			$this->previous_data = $data;
			$this->setup( $data );

		} elseif ( $data instanceof \WP_Post ) {
			$data = $this->get_data_by( $data->ID );
			if ( $data ) {
				$this->previous_data = $data;
				$data['post_id'] = $data['ID'];
				$this->setup( $data );
			}
		} elseif ( is_numeric( $data ) ) {
			$data = $this->get_data_by( $data );
			if ( $data ) {
				$this->previous_data = $data;
				$data['post_id'] = $data['ID'];
				$this->setup( $data );
			}
		} elseif ( isset( $data['ID'] ) && $data['ID'] ) {
			if ( ! isset( $data['_skip_check'] ) || ! $data['_skip_check'] ) {
				$skips = [ 'post_id', 'mid' ];
				$org_data = $this->get_data_by( $data['ID'] );
				$this->previous_data = $org_data;
			} else {
				$org_data = $data;
			}

			if ( $org_data ) {
				$skips[] = 'listing_type';
				$org_data['post_id'] = $org_data['ID'];
				$this->setup( $org_data );
			} else {
				$data['ID'] = null;
			}

			$this->setup( $data, $skips );

		} else {
			$this->setup( $data );
		}

		// Sanitize some fields.
		if ( isset( $this->data['expired'] ) && '0000-00-00 00:00:00' == $this->data['expired'] ) {
			$this->data['expired'] = '';
		}

		// Get Youtube id.
		if ( isset( $this->data['video_url'] ) && $this->data['video_url'] ) {
			preg_match( '%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $this->data['video_url'], $match );
			if ( ! empty( $match ) ) {
				$this->data['youtube_id'] = $match[1];
			}
		}

	}

	public function get_default_fields() {
		$fields = [];
		foreach ( $this->post_fields as $field ) {
			$fields[ $field ] = null;
		}
		if ( is_array( static::$data_tables ) ) {
			foreach ( static::$data_tables as $model_class ) {
				$fields = \array_merge( $fields, $model_class::get_default_fields() );
			}
		}

		foreach ( $this->custom_fields as $field ) {
			$fields[ $field ] = null;
		}

		return $fields;
	}

	/**
	 * Create a new model from the given data.
	 *
	 * @param array $properties
	 * @return self
	 */
	public static function create( $properties ) {
		return new static( $properties );
	}

	public function __set( $name, $value ) {
		$this->data[ $name ] = $value;
	}

	public function __get( $name ) {
		if ( 'post_type' == $name ) {
			return $this->post_type;
		}
		if ( 'media_files' == $name ) {
			if ( ! isset( $this->data['media_files'] ) || ! $this->data['media_files'] ) {
				return $this->get_gallery();
			}
			return $this->data['media_files'];
		}

		// Check if the key is built in taxonomies.
		if ( in_array( $name, [ 'categories', 'region' ], true ) ) {
			if ( isset( $this->tax_fields[ $name ] ) ) {
				$tax = $this->tax_fields[ $name ]['tax'];
				if ( ! isset( $this->data['taxonomies'] ) ) {
					return null;
				}
				return isset( $this->data['taxonomies'][ $tax ] ) ? $this->data['taxonomies'][ $tax ] : null;
			}
		}

		if ( array_key_exists( $name, $this->data ) ) {
			return $this->data[ $name ];
		}
		return null;
	}

	public function get_previous( $field ) {
		if ( ! empty( $this->previous_data ) && isset( $this->previous_data[ $field ] ) ) {
			return $this->previous_data[ $field ];
		}
		return null;
	}

	public static function cache_get( $id ) {
		$key = static::$cache_key . $id;
		return wp_cache_get( $key, static::$cache_group );
	}

	public static function cache_set( $listing ) {
		$key = static::$cache_key . $listing->get_id();
		wp_cache_set( $key, $listing, static::$cache_group );
	}
	public static function cache_delete( $id ) {
		$key = static::$cache_key . $id;
		wp_cache_delete( $key, static::$cache_group );
	}

	private function setup( $data, $skips = [] ) {
		$type = $this->get_type();
		if ( ! $type ) {
			if ( isset( $data['listing_type'] ) ) {
				$type = $data['listing_type'];
			}
		}

		$this->listing_type = new Listing_Type( $type );
		if ( $this->listing_type->get_id() ) {
			$this->type_id = $this->listing_type->get_id();
		} elseif ( isset( $data['type_id'] ) && $data['type_id'] ) {
			$this->listing_type = new Listing_Type( $data['type_id'] );
			$this->type_id = $this->listing_type->get_id();
		}

		$this->setup_fields( $data, $skips = [] );
		$this->set_custom_fields( $data );
		$this->setup_taxonomies( $data );

	}

	private function setup_fields( $data, $skips = [] ) {
		foreach ( $this->get_default_fields() as $key => $val ) {
			if ( empty( $skips ) || ! \in_array( $key, $skips, true ) ) {
				if ( isset( $data[ $key ] ) ) {
					$this->data[ $key ] = $data[ $key ];
				}
			}
		}
	}

	private function set_custom_fields( $data ) {

		// Built-in custom fields.
		foreach ( $this->custom_fields as $key ) {
			if ( isset( $data[ $key ] ) ) {
				$this->data[ $key ] = isset( $data[ $key ] ) ? $data[ $key ] : '';
			} else {
				$this->data[ $key ] = get_post_meta( $this->get_id(), '_' . $key, true );
			}
		}

		// Dynamic custom fields.
		foreach ( $this->listing_type->get_custom_meta_fields() as $field ) {
			if ( isset( $field['name'] ) && $field['name'] ) {
				$key = $field['name'];
				if ( isset( $data[ $key ] ) ) {
					$this->data[ $key ] = isset( $data[ $key ] ) ? $data[ $key ] : '';
				} else {
					$this->data[ $key ] = get_post_meta( $this->get_id(), '_' . $key, true );
				}
			}
		}

	}

	protected function sort_terms_by( $order, $terms ) {
		if ( empty( $order ) || ! is_array( $order ) ) {
			return $terms;
		}

		$term_keys = [];
		$sorted_terms = [];
		foreach ( $terms as $t ) {
			$term_keys[ $t->term_id ] = $t;
		}

		foreach ( $order as $tid ) {
			if ( isset( $term_keys[ $tid ] ) ) {
				$sorted_terms[ $tid ] = $term_keys[ $tid ];
				unset( $term_keys[ $tid ] );
			}
		}

		if ( count( $term_keys ) > 0 ) {
			$sorted_terms = \array_merge( $sorted_terms, $term_keys );
		}

		return $sorted_terms;
	}

	protected function setup_taxonomies( $data ) {

		$taxonomies = [];
		// For set new dynamic taxs.
		$data_taxonomies_terms = isset( $data['taxonomies'] ) && is_array( $data['taxonomies'] ) ? $data['taxonomies'] : false;
		$all_listing_taxs = \array_merge( wp_list_pluck( $this->tax_fields, 'tax' ), $this->listing_type->get_support_taxs() );

		// wp_send_json( $this->listing_type->to_array() );
		// wp_send_json( $all_listing_taxs );
		if ( $data_taxonomies_terms ) { // If submit new.
			foreach ( $all_listing_taxs as $tax_name ) {
				$terms = isset( $data_taxonomies_terms[ $tax_name ] ) ? $data_taxonomies_terms[ $tax_name ] : [];
				$taxonomies[ $tax_name ] = [];
				if ( ! empty( $terms ) ) {
					if ( isset( $taxonomies[ $tax_name ] ) ) {
						$taxonomies[ $tax_name ] = \array_merge( $taxonomies[ $tax_name ], $terms );
					} else {
						$taxonomies[ $tax_name ] = $terms;
					}
				}
			}
		} else {
			// For existing fixed taxs.
			foreach ( $this->tax_fields as $input_name => $tax_args ) {
				$tax_name = $tax_args['tax'];
				$multiple = isset( $tax_args['multiple'] ) ? $tax_args['multiple'] : false;

				if ( $this->get_id() ) { // get existing taxonomy terms.
					$terms = get_the_terms( $this->get_id(), $tax_name );

					$meta_name = $tax_name . '__order';
					$this->custom_fields[] = $meta_name;
					$order_value = get_post_meta( $this->get_id(), '_' . $meta_name, true );
					$this->data[ $meta_name ] = $order_value;

					// ----
					if ( $terms && ! \is_wp_error( $terms ) ) {
						$terms = $this->sort_terms_by( $order_value, $terms );
						if ( ! $multiple ) {
							$terms = array_slice( $terms, 0, 1 );
						}
						$taxonomies[ $tax_name ] = $terms;
					}
				}
			}

			foreach ( $this->get_existing_taxs() as $term ) {
				$tax_name = $term['taxonomy'];
				if ( ! isset( $taxonomies[ $tax_name ] ) ) {
					$taxonomies[ $tax_name ] = [];
				}
				$taxonomies[ $tax_name ][] = $term;
			}
		}

		$this->data['taxonomies'] = $taxonomies;

	}

	public function get_type() {
		return isset( $this->data['listing_type'] ) ? $this->data['listing_type'] : false;
	}

	public function get_listing_type() {
		return $this->listing_type;
	}

	public function support_price() {
		if ( 'yes' == $this->get_listing_type()->support_price ) {
			return true;
		}

		return false;
	}
	public function support_price_range() {
		if ( 'yes' == $this->get_listing_type()->support_price_range ) {
			return true;
		}
		return false;
	}

	public function get_meta( $key ) {
		if ( isset( $this->data[ 'custom__' . $key ] ) ) {
			return $this->data[ 'custom__' . $key ];
		}
		return get_post_meta( $this->get_id(), $key, true );
	}

	public function is_existing_listing() {
		return $this->get_id() && $this->data['post_type'] == $this->post_type;
	}

	public function is_claimable( $user_id = null ) {

		if ( $this->claimed ) {
			return false; // Already claimed.
		}

		if ( ! $this->claims_open() ) { // Claim disabled by admin.
			return false;
		}

		return \apply_filters( 'listplus_listing_claimable', true, $this );

	}

	private function get_data_by( $id ) {
		global $wpdb;
		$sql = " FROM {$wpdb->posts} as p LEFT JOIN {$wpdb->prefix}lp_item_meta AS im ON ( im.post_id = p.ID ) 
		WHERE p.ID = %d ";
		$sql_get = "SELECT * {$sql} LIMIT 1";
		$row = $wpdb->get_row( $wpdb->prepare( $sql_get, $id ), ARRAY_A );

		if ( is_array( $row ) && $row['post_type'] != $this->post_type ) {
			$row = null;
		}

		return $row;
	}

	private function get_meta_by_id( $id ) {
		$meta = Item_Meta::find_one_by( 'post_id', $id );
		if ( ! $meta ) {
			return [];
		}
		return $meta->to_array();
	}

	public function get_slug() {
		if ( isset( $this->data['post_name'] ) ) {
			return $this->data['post_name'];
		}
		return '';
	}

	public function get_name() {
		if ( isset( $this->data['post_title'] ) ) {
			return $this->data['post_title'];
		}
		return '';
	}

	public function get_id() {
		if ( isset( $this->data['ID'] ) ) {
			return (int) $this->data['ID'];
		}
		return 0;
	}

	public function handle_media_upload() {
		$all_upload_ids = [];
		$post_id = $this->get_id();
		if ( ! $this->get_id() ) {
			return;
		}
		foreach ( $this->media_fields as $input_name ) {
			$upload_ids = Helper::handle_upload( $input_name, $this->get_id() );
			if ( $upload_ids ) {
				$all_upload_ids = \array_merge( $all_upload_ids, $upload_ids );
			}

			$media_to_save = [];
			$media_to_delete = [];

			// Old media files.
			$current_order = isset( $_POST[ $input_name . '_order' ] ) ? $_POST[ $input_name . '_order' ] : [];
			$current_media = isset( $_POST[ $input_name  ] ) ? $_POST[ $input_name ] : [];

			$order = 0;
			// Set order media items.
			foreach ( $current_order as $key => $v ) {
				if ( isset( $current_media[ $key ] ) ) {
					$media_to_save[ $order ] = $current_media[ $key ];
					$order ++;
				} elseif ( isset( $all_upload_ids[ $key ] ) ) {
					$media_to_save[ $order ] = $all_upload_ids[ $key ];
					$order ++;
				}
			}

			$current_gallery = $this->get_gallery( true );

			if ( ! empty( $media_to_save ) ) {
				// Update media file order.
				foreach ( $media_to_save as $index => $m_id ) {
					wp_update_post(
						[
							'ID' => $m_id,
							'menu_order' => $index,
						]
					);
				}

				$media_to_save_keys = \array_flip( $media_to_save );
				// Check if uploaded images removed by author then delete its from db and file.
				foreach ( $current_gallery as $id ) {
					if ( ! isset( $media_to_save_keys[ $id ] ) ) {
						$k = $media_to_save_keys[ $id ];
						$attachment = get_post( $id );
						if ( $attachment->post_parent == $post_id ) {
							wp_delete_attachment( $id, true );
							$media_to_delete[] = $id;
						}
					}
				}

				// `media_files` is main key for listing gallery.
				if ( 'media_files' == $input_name ) {
					// set first item as post thumbnail.
					\update_post_meta( $this->get_id(), '_media_files', $media_to_save );
					$first_id = \array_shift( $media_to_save );
					if ( $first_id ) {
						\set_post_thumbnail( $this->get_id(), $first_id );
					}
				}
			}
		}
	}


	public function get_gallery( $force = false ) {

		if ( ! $force ) {
			if ( isset( $this->data['media_files'] ) && is_array( $this->data['media_files'] ) ) {
				return $this->data['media_files'];
			}
		}

		if ( ! $this->get_id() ) {
			return [];
		}

		if ( ! $force ) {
			$this->data['media_files']  = get_post_meta( $this->get_id(), '_media_files', true );
			if ( is_array( $this->data['media_files'] ) ) {
				$this->data['media_count'] = count( $this->data['media_files'] );
				return $this->data['media_files'];
			}
		}

		$query = new \WP_Query(
			[
				'post_parent'    => $this->get_id(),
				'post_type'      => 'attachment',
				'post_status'      => 'any',
				'posts_per_page' => -1,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
				'fields'         => 'ids',
			]
		);

		$this->data['media_files'] = is_array( $query->posts ) ? $query->posts : [];
		update_post_meta( $this->get_id(), '_media_files', $this->data['media_files'] );
		$this->data['media_count'] = $query->found_posts;
		return $this->data['media_files'];

	}

	public function count_enquiries() {
		if ( ! $this->get_id() ) {
			return 0;
		}
		return Enquiry::query()->where( 'post_id', $this->get_id() )->count();
	}

	public function count_reports() {
		if ( ! $this->get_id() ) {
			return 0;
		}
		return Report::query()->where( 'post_id', $this->get_id() )->count();
	}

	public function count_reviews() {
		if ( ! $this->get_id() ) {
			return 0;
		}
		return Review::query()->where( 'post_id', $this->get_id() )->count();
	}

	public function count_claims() {
		if ( ! $this->get_id() ) {
			return 0;
		}
		return Claim::query()->where( 'post_id', $this->get_id() )->count();
	}


	public function get_nonce_action() {
		$action = 'new_listing';
		if ( $this->get_id() ) {
			$action = 'edit_listing_' . $this->get_id();
		}
	}

	public function nonce() {
		return wp_create_nonce( $this->get_nonce_action() );
	}

	public function verify_nonce( $nonce = null ) {

		if ( \is_null( $nonce ) ) {
			$nonce = wp_unslash( $_REQUEST['_nonce'] );
		}

		return wp_verify_nonce( $nonce, $this->get_nonce_action() );
	}

	public function can_edit( $user_id = false ) {
		$can = false;
		if ( \current_user_can( 'administrator' ) ) {
			$can = true;
		} else {

			if ( $user_id ) {
				$user = \get_user_by( 'id', $user_id );
			} else {
				$user  = wp_get_current_user();
			}

			if ( ! $user ) {
				return \apply_filters( 'listing_can_edit', false, $this->get_id(), $this );
			}

			if ( $user->has_cap( 'listing_manager' ) ) {
				$can = true;
			}

			if ( ! $can ) {
				if ( $this->get_id() > 0 ) { // If listing already exists.
					if ( $this->claimed > 0 ) {
						// If listing was claimed and claimed by current user then can edit.
						if ( $this->claimed == $user->ID ) {
							$can = true;
						}
					} else {
						// If this listing submitted by current user then can edit.
						if ( $this->post_author == $user->ID ) {
							$can = true;
						}
					}
				} else {
					$can = true; // If new listing.
				}
			}
		} // end if user not is admin.

		return \apply_filters( 'listing_can_edit', $can, $this->get_id(), $this );
	}


	public function get_terms( $tax ) {
		if ( ! isset( $this->data['taxonomies'] ) ) {
			return null;
		}

		if ( ! isset( $this->data['taxonomies'][ $tax ] ) || empty( $this->data['taxonomies'][ $tax ] ) ) {
			return null;
		}
		return $this->data['taxonomies'][ $tax ];
	}

	public function get_existing_taxs() {
		if ( ! $this->get_id() ) {
			return [];
		}

		global $wpdb;
		$post_id = $this->get_id();
		$cache_key = 'listing_custom_terms_' . $post_id;
		$cache_group = 'listing_taxonomies';
		if ( \wp_cache_get( $cache_key, $cache_group ) ) {
			return \wp_cache_get( $cache_key, $cache_group );
		}

		$table = $wpdb->prefix . 'lp_tax_relationships';
		$sql = "SELECT
		ltr.*, t.name, t.slug
		FROM {$table} as ltr
		LEFT JOIN $wpdb->terms as t ON ( ltr.term_id = t.term_id )
		WHERE ltr.post_id = %d 
		ORDER BY custom_order ASC";
		$terms = $wpdb->get_results( $wpdb->prepare( $sql, $post_id ), ARRAY_A );
		$index_terms = [];
		if ( \is_array( $terms ) && ! \is_wp_error( $terms ) ) {
			foreach ( $terms as $t ) {
				$index_terms[ 't_' . $t['term_id'] ] = $t;
			}
		}
		wp_cache_set( $cache_key, $index_terms, $cache_group );
		return $index_terms;
	}

	private function save_taxs() {
		global $wpdb;
		$table       = $wpdb->prefix . 'lp_tax_relationships';
		$post_id     = $this->get_id();
		$existing_tax_terms = $this->get_existing_taxs();

		// wp_send_json(  $existing_tax_terms );
		// Set listing Type.
		\wp_set_object_terms( $post_id, (int) $this->data['type_id'], 'listing_type' );

		foreach ( (array) $this->data['taxonomies'] as $tax_name => $terms ) {
			$tax = \ListPlus()->taxonomies->get_custom( $tax_name );
			if ( ! $tax ) {
				\ListPlus()->error->add( $tax_name . '_support', __( $tax_name . ' do not support.', 'list-plus' ) );
				continue;
			}

			$new_custom_terms = [];
			$update_custom_terms = [];

			$array_terms = [];
			foreach ( $terms as $term ) {
				$term = wp_parse_args(
					$term,
					[
						'term_id' => '',
						'name' => '',
						'taxonomy' => '',
						'custom_value' => '',
						'custom_order'  => 0,
						'post_id'       => '',
					]
				);

				$term['taxonomy'] = $tax_name;
				$term['post_id'] = $post_id;
				$term_info = false;
				$term_id = $term['term_id'];
				if ( $term['term_id'] ) {
					$term_info = \term_exists( (int) $term['term_id'], $tax_name );
				}

				if ( ! $term_info && $term['name'] ) {
					$term_info = \term_exists( $term['name'], $tax_name );
				}

				if ( ! $term_info ) {
					if ( ! $tax['allow_new'] ) {
						\ListPlus()->error->add( $tax_name . '_new', __( $tax_name . ' do not allow add new.', 'list-plus' ) );
						continue; // Skip if this tax not allow add new.
					} else {
						$term_info = \wp_insert_term( $term['name'], $tax_name );
						if ( \is_wp_error( $term_info ) ) {
							\ListPlus()->error->add( $tax_name . '_insert_' . $term['name'], \sprintf( __( '%1$s insert error.', 'list-plus' ), $tax_name ) );
							continue;
						}

						$term_id = $term_info['term_id'];
						$term['term_id'] = $term_id;
						if ( isset( $existing_tax_terms[ 't_' . $term_id ] ) ) {
							$update_custom_terms[ $term_id ] = $term;
						} else {
							$new_custom_terms[ $term_id ] = $term;
						}
					}
				} else {
					if ( ! $term_info ) {
						\ListPlus()->error->add( $tax_name . 'no_term' . $term['name'], \sprintf( __( '%1$s no term', 'list-plus' ), $tax_name ) );
						continue;
					}

					$term_id = $term_info['term_id'];
					$term['term_id'] = $term_id;
					if ( isset( $existing_tax_terms[ 't_' . $term_id ] ) ) {
						$update_custom_terms[ $term_id ] = $term;
					} else {
						$new_custom_terms[ $term_id ] = $term;
					}
				}

				unset( $existing_tax_terms[ 't_' . $term_id ] );
				$array_terms[] = (int) $term_info['term_id'];
			}

			\wp_set_object_terms( $post_id, empty( $array_terms ) ? '' : $array_terms, $tax_name );

			$meta_name = $tax_name . '__order';
			$this->custom_fields[] = $meta_name;
			$this->data[ $meta_name ] = $array_terms;

			if ( $tax['_builtin'] ) {
				continue;
			}

			foreach ( $update_custom_terms as $term ) {
				$data = [
					'term_id' => $term['term_id'],
					'taxonomy' => $term['taxonomy'],
					'custom_value' => $term['custom_value'],
					'custom_order'  => $term['custom_order'],
					'post_id'       => $post_id,
				];
				$wpdb->update(
					$table,
					$data,
					[
						'term_id' => $data['term_id'],
						'post_id' => $this->get_id(),
					]
				);
			}

			foreach ( $new_custom_terms as $term ) {
				$data = [
					'term_id' => $term['term_id'],
					'taxonomy' => $term['taxonomy'],
					'custom_value' => $term['custom_value'],
					'custom_order'  => $term['custom_order'],
					'post_id'       => $post_id,
				];
				$wpdb->insert( $table, $data );
			}
		} // end foreach tax.

		// Delete the terms if it removed.
		if ( ! empty( $existing_tax_terms ) ) {
			$ids = \wp_list_pluck( $existing_tax_terms, 'term_id' );
			$sql = " DELETE FROM {$table} WHERE term_id IN(" . join( ',', $ids ) . ") AND post_id = {$post_id} ";
			$wpdb->query( $sql );
		}

	}



	private function validate_term_ids( $values ) {
		$return = [];
		if ( \is_array( $values ) ) {
			foreach ( $values as $v ) {
				$v = intval( $v );
				if ( $v > 0 ) {
					$return[] = $v;
				}
			}
			if ( empty( $return ) ) {
				$return = null;
			}
		} else {
			$return = intval( $values );
			if ( $return < 1 ) {
				$return = null;
			}
		}

		return $return;
	}


	public function save( $force = false ) {

		$post_data = [];
		$meta_fields = [];
		foreach ( $this->post_fields as $key ) {
			if ( isset( $this->data[ $key ] ) ) {
				$post_data[ $key ] = $this->data[ $key ];
			}
		}

		$post_data['post_type'] = $this->post_type;

		// Set listing Type.
		// $this->data['type_id'] = (int) $term_type['term_id'];
		// Set listing author if not set.
		if ( ! isset( $post_data['post_author'] ) || ! $post_data['post_author'] ) {
			$user = wp_get_current_user();
			if ( $user ) {
				if ( ! $this->post_author ) {
					$this->post_author = $user->ID;
				}
			}
			$post_data['post_author'] = $user->ID;
		}

		$is_new = false;

		if ( $this->get_id() ) {
			$post_data['ID'] = $this->get_id();
			$post_data['post_date_gmt'] = $post_data['post_date'];
			\wp_update_post( $post_data );
		} else {
			$is_new  = true;
			$pid = \wp_insert_post( $post_data );
			if ( $pid && ! \is_wp_error( $pid ) ) {
				$this->data['ID'] = $pid;
			} else {
				\ListPlus()->error->add( 'insert_post', __( 'New listing error.', 'list-plus' ) );
			}
		}

		if ( $this->get_id() ) {
			$meta_fields = $this->data;
			$meta_fields['post_id'] = $this->get_id();

			if ( ! $force ) {

				if ( isset( $meta_fields['lat'] ) && ! \strlen( $meta_fields['lat'] ) ) {
					$meta_fields['lat'] = null;
				}
				if ( isset( $meta_fields['lng'] ) && ! \strlen( $meta_fields['lng'] ) ) {
					$meta_fields['lng'] = null;
				}

				$regions = $this->get_terms( 'listing_region' );

				if ( $regions && ! empty( $regions ) ) {
					$region = (array) current( $regions );
					\reset( $regions );
					$meta_fields['region_id'] = $region['term_id'];
				}

				foreach ( $this->force_updated_fields as $fk ) {
					unset( $meta_fields[ $fk ] );
				}
			}

			$meta = new Item_Meta( $meta_fields );
			$meta->save();
			$this->setup_fields( $meta->to_array() );
			$this->save_taxs();
			// Save custom fields.
			foreach ( $this->custom_fields as $key ) {
				$value = isset( $this->data[ $key ] ) ? $this->data[ $key ] : '';
				update_post_meta( $this->get_id(), '_' . $key, $value );
			}

			// Dynamic custom fields.
			foreach ( $this->listing_type->get_custom_meta_fields() as $field ) {
				if ( isset( $field['name'] ) && $field['name'] ) {
					$key = $field['name'];
					$value = isset( $this->data[ $key ] ) ? $this->data[ $key ] : '';
					update_post_meta( $this->get_id(), '_' . $key, $value );
				}
			}

			$previous_status = $this->get_previous( 'post_status' );
			// Check if status changed.
			if ( $previous_status && $previous_status != $this->post_status ) {
				do_action( 'listplus_listing_status_changed', $previous_status, $this->post_status, $this, $is_new );
				do_action( 'listplus_listing_status_changed_to_' . $this->post_status, $this->post_status, $this, $is_new );
			}

			if ( $is_new ) {
				do_action( 'listplus_new_listing_saved', $this );
			} else {
				do_action( 'listplus_listing_updated', $this );
			}

			do_action( 'listplus_listing_saved', $this );
			$this->previous_data = $this->to_array();

			// Clear cache.
			static::cache_delete( $this->get_id() );

			return true;
		}

		// Reset data when changed.
		if ( $this->get_id() ) {
			$data = $this->get_data_by( $this->get_id() );
			$this->setup_fields( $data );
		}

		return false;
	}

	public function dupplicate( $new_data = [] ) {

		$this->get_gallery();
		$this->data['post_title'] .= ' (Copy)';
		$this->data['post_name'] = '';

		if ( is_array( $new_data ) && ! empty( $new_data ) ) {
			foreach ( $new_data as $k => $v ) {
				$this->data[ $k ] = $v;
			}
		}

		$this->data['ID'] = null;
		$this->data['post_id'] = null;
		$this->data['mid'] = null;

		$this->save();

		if ( ! empty( $this->data['media_files'] ) ) {
			$first_id = \array_shift( $this->data['media_files'] );
			if ( $first_id ) {
				\set_post_thumbnail( $this->get_id(), $first_id );
			}
		}

	}

	public function delete() {
		if ( $this->get_id() ) {
			wp_delete_post( $this->get_id(), true );
			Item_Meta::delete_by_key( 'post_id', $this->get_id() );
			do_action( 'listplus_listing_deleted', $this->get_id() );
		}
	}

	public function to_array() {
		return $this->data;
	}

	public function get_view_link( $router = null, $args = [] ) {
		$args['name'] = $this->get_slug();
		if ( ! $router ) {
			$router = 'listing';
		}
		return \ListPlus()->request->to_url( $router, $args );
	}

	public function get_review_link( $page = 1 ) {
		$args = [
			'name' => $this->get_slug(),
			'r_paged' => $page,
		];
		return \ListPlus()->request->to_url( 'reviews_page', $args );
	}

	/**
	 * Update meta fields in table item meta
	 * when listing alreay exists.
	 *
	 * @param array $fields
	 * @return bool|mixed
	 */
	public function update_meta_fields( $fields = [] ) {
		if ( ! $this->get_id() ) {
			return false;
		}
		if ( empty( $fields ) ) {
			return false;
		}
		$meta_id = $this->mid;
		$table = Item_Meta::get_table();
		$fields['post_id'] = $this->get_id();
		global $wpdb;

		if ( $meta_id > 0 ) {
			return $wpdb->update( $table, $fields, [ 'mid' => $meta_id ] ); // WPCS: db call ok, cache ok.
		} else {
			$wpdb->insert( $table, $fields ); // WPCS: db call ok.
			$meta_id = $wpdb->insert_id;
			if ( \is_numeric( $meta_id ) ) {
				$this->data['mid'] = $meta_id;
			}
		}
	}

	public function calc_rating() {

		if ( ! $this->get_id() ) {
			return false;
		}
		global $wpdb;

		$max = ListPlus()->settings->get( 'review_max' );
		$id = $this->get_id();
		$table_review = Review::get_table();
		$sql = " SELECT 
					SUM(
					
						CASE WHEN `weight` <= 0 THEN
							1 * `rating`
						ELSE
							`rating` * `weight` 
						END 
					
					) as tr, 
					sum( 
						CASE WHEN `weight` <= 0 THEN
							1 * `max`
						ELSE
							`weight` * `max`
						END 
					) as tn, 
					count(id) as tc 
				FROM {$table_review} 
				WHERE post_id = %d AND `status` = %s
				";
		$row = $wpdb->get_row( $wpdb->prepare( $sql, $id, 'approved' ), ARRAY_A ); // WPCS: db call ok, cache ok.

		if ( $row && isset( $row['tn'] ) && $row['tn'] > 0 ) {
			$score = ( $row['tr'] / $row['tn'] ) * $max;
			$this->data['count_review'] = $row['tc'];
			$this->data['rating_score'] = $score;
			// Save only needed data.
			$this->update_meta_fields(
				[
					'count_review' => $row['tc'],
					'rating_score' => $score,
				]
			);
		}

		return false;
	}

	/**
	 * Get the column used as the primary key, defaults to 'id'.
	 *
	 * @return string
	 */
	public static function get_primary_key() {
		return 'ID';
	}

	public function comments_open() {
		return \comments_open( $this->get_id() );
	}

	public function enquiries_open() {
		return 'disabled' != $this->enquiry_status;
	}
	public function claims_open() {
		return 'disabled' != $this->claim_status;
	}
	public function reports_open() {
		return 'disabled' != $this->report_status;
	}
	public function reviews_open() {
		return 'disabled' != $this->review_status;
	}

	/**
	 * Overwrite this in your concrete class. Returns the table name used to
	 * store models of this class.
	 *
	 * @return string
	 */
	public static function get_table() {
		global $wpdb;
		return $wpdb->posts;
	}

	public function offsetSet( $offset, $value ) {
		if ( is_null( $offset ) ) {
			$this->data[] = $value;
		} else {
			$this->data[ $offset ] = $value;
		}
	}

	public function offsetExists( $offset ) {
		return isset( $this->data[ $offset ] );
	}

	public function offsetUnset( $offset ) {
		unset( $this->data[ $offset ] );
	}

	public function offsetGet( $offset ) {
		return isset( $this->data[ $offset ] ) ? $this->data[ $offset ] : null;
	}

}
