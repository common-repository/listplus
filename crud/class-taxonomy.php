<?php

namespace ListPlus\CRUD;

/**
 * @see https://developer.wordpress.org/reference/functions/wp_insert_term/
 */
class Taxonomy implements \ArrayAccess {

	protected $tax = '';
	protected $force_check_for_update = null;

	protected $data = [];

	protected $term_keys = [
		'term_id',
		'name',
		'description',
		'slug',
		'parent',
		'count',
		'taxonomy',
		'custom_order',
		'custom_value',
	];

	protected $meta_keys = [];

	public function __construct( $data = array() ) {
		$this->tax = static::type();

		if ( ! $data || is_null( $data ) ) {
			$data = [];
		}

		if ( $data instanceof \WP_Term ) {
			$this->setup( (array) $data );
		} elseif ( \is_numeric( $data ) ) {
			$this->setup_term( $data );
		} elseif ( \is_string( $data ) ) {
			$this->setup_term( $data, 'slug' );
		} elseif ( is_array( $data ) ) {
			$this->setup( $data );
		}

		if ( isset( $this->data['taxonomy'] ) && $this->data['taxonomy'] ) {
			$this->tax = $this->data['taxonomy'];
		}
	}

	public function __set( $name, $value ) {
		$this->data[ $name ] = $value;
	}

	public function __get( $name ) {
		if ( array_key_exists( $name, $this->data ) ) {
			return $this->data[ $name ];
		}
		return null;
	}

	public static function type() {
		return 'listing_type';
	}

	public function add_meta_key( $key ) {
		$this->meta_keys[] = $key;
	}

	protected function setup( $data ) {

		foreach ( $this->term_keys as $k ) {
			if ( isset( $data[ $k ] ) ) {
				$this->data[ $k ] = $data[ $k ];
			}
		}

		if ( isset( $this->data['term_id'] ) && $this->data['term_id'] ) {
			foreach ( $this->meta_keys as $k ) {
				$this->data[ $k ] = get_term_meta( $this->get_id(), '_' . $k, true );
				if ( isset( $data[ $k ] ) ) {
					$this->data[ $k ] = $data[ $k ];
				}
			}
		} else {
			foreach ( $this->meta_keys as $k ) {
				if ( isset( $data[ $k ] ) ) {
					$this->data[ $k ] = $data[ $k ];
				}
			}
		}

	}

	public function get_id() {
		if ( isset( $this->data['term_id'] ) ) {
			return $this->data['term_id'];
		}
		return 0;
	}

	public function get_slug() {
		if ( isset( $this->data['slug'] ) ) {
			return $this->data['slug'];
		}
		return '';
	}

	public function get_name() {
		if ( isset( $this->data['name'] ) ) {
			return $this->data['name'];
		}
		return '';
	}

	public function get_icon() {
		if ( isset( $this->data['icon_svg'] ) ) {
			return $this->data['icon_svg'];
		}
		if ( isset( $this->data['icon'] ) ) {
			$icon_id = $this->data['icon'];
		} else {
			$icon_id = get_term_meta( $this->get_id(), '_icon', true );
		}

		$svg = \ListPlus()->icons->the_icon_svg( $icon_id );
		$this->data['icon_svg'] = $svg;
		return $this->data['icon_svg'];
	}
	public function get_image() {
		if ( isset( $this->data['image'] ) ) {
			return $this->data['image'];
		}
		$this->data['image'] = get_term_meta( $this->get_id(), '_image', true );
		return $this->data['image'];
	}

	protected function setup_term( $id, $field = 'id' ) {
		$term = get_term_by( $field, $id, $this->tax, ARRAY_A );

		if ( $term ) {
			foreach ( $this->term_keys as $k ) {
				$this->data[ $k ] = isset( $term[ $k ] ) ? $term[ $k ] : null;
			}
		}

		foreach ( $this->meta_keys as $k ) {
			$this->data[ $k ] = get_term_meta( $this->get_id(), '_' . $k, true );
		}
	}

	public function get_url( $paged = 0 ) {
		$suport_tags = [
			'id' => 'term_id',
			'term_id' => 'term_id',
			'slug' => 'slug',
			'taxonomy' => 'taxonomy',
		];

		$tax = str_replace( 'listing_', '', $this->data['taxonomy'] );
		$args = [];
		foreach ( $suport_tags as $k => $v ) {
			$args[ $k ] = isset( $this->data[ $v ] ) ? $this->data[ $v ] : '';
		}

		if ( ! $paged ) {
			return \ListPlus()->url->to_url( $tax, $args );
		} else {
			return \ListPlus()->url->to_url( $tax . '/paging', $args );
		}

	}

	public function get_icon_html() {
		$html  = '';
		if ( $this->get_image() ) {
			$url = wp_get_attachment_url( $this->get_image() );
			$html .= '<span class="l-icon img"><img src="' . \esc_url( $url ) . '" alt=""></span>';
		} elseif ( $this->get_icon() ) {
			$html .= '<span class="l-icon svg">' . $this->get_icon() . '</span>';
		}
		return $html;
	}

	public function to_html( $tag = 'li' ) {
		$html = '<' . $tag . '>';
		$html .= $this->get_icon_html();
		$html .= '<span class="t-name">' . esc_html( $this->get_name() ) . '</span>';

		if ( isset( $this->data['custom_value'] ) && $this->data['custom_value'] ) {
			$html .= '<span class="t-custom-val">' . esc_html( $this->data['custom_value'] ) . '</span>';
		}

		$html .= '</' . $tag . '>';
		return $html;
	}


	public function get_nonce_action() {
		$tax = static::type();
		$action = 'new_' . $tax;
		if ( $this->get_id() ) {
			$action = 'edit_' . $tax . '_' . $this->get_id();
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

	public function delete() {
		return static::delete_by_id( $this->get_id() );
	}

	public static function delete_by_id( $id ) {
		return wp_delete_term( $id, static::type() );
	}

	/**
	 * Do somthing with data before save.
	 *
	 * @return void
	 */
	protected function pre_save_data() {
	}

	public function force_update_if_exists( $check = true ) {
		$this->force_check_for_update = $check;
	}

	public function save() {

		if ( ! $this->data['taxonomy'] ) {
			$tax = static::type();
		} else {
			$tax = $this->data['taxonomy'];
		}

		$this->pre_save_data();

		$term_data = [];
		$term_data['taxonomy'] = $tax;
		foreach ( $this->term_keys as $k ) {
			$term_data[ $k ] = isset( $this->data[ $k ] ) ? $this->data[ $k ] : null;
		}

		$is_new = $this->get_id() ? false : true;
		if ( $this->force_check_for_update ) {
			if ( $this->get_id() ) {
				$existing_term = \get_term( $this->get_id(), $tax, ARRAY_A );
				if ( ! $existing_term || is_wp_error( $existing_term ) ) {
					$this->data['term_id'] = '';
					$is_new = false;
				} else {
					// If have same slug for this term.
					if ( $this->get_slug() == $existing_term->slug ) {
						$is_new = false;
						$this->data['term_id'] = $existing_term['term_id'];
						$this->data['slug'] = $existing_term['slug'];
					}
				}
			}

			// check if slug alreay exists.
			if ( $is_new ) {
				if ( $this->get_slug() ) {
					$existing_term = \get_term_by( 'slug', $this->get_slug(), $tax, ARRAY_A );
				} else {
					$existing_term = \get_term_by( 'name', $this->get_slug(), $tax, ARRAY_A );
				}

				if ( $existing_term ) {
					$is_new = false;
					$this->data['term_id'] = $existing_term['term_id'];
					$this->data['slug'] = $existing_term['slug'];
				}
			}
		}

		if ( ! $is_new ) {
			$result = wp_update_term( $this->get_id(), $tax, $term_data );
		} else {
			$result = wp_insert_term( $this->data['name'], $tax, $term_data );	
		}

		if ( $result && ! \is_wp_error( $result ) ) {

			foreach ( $this->meta_keys as $k ) {
				$meta_value = isset( $this->data[ $k ] ) ? $this->data[ $k ] : '';
				update_term_meta( $result['term_id'], '_' . $k, $meta_value );
			}

			$this->setup_term( $result['term_id'] );

			if ( 'listing_type' == $tax ) {
				global $wpdb;
				$old_slug = get_term_meta( $result['term_id'], '_old_slug', true );
				update_term_meta( $result['term_id'], '_old_slug', $this->data['slug'] );
				if ( $old_slug != $this->data['slug'] ) {
					$table = \ListPlus\CRUD\Item_Meta::get_table();
					$sql = "UPDATE {$table} SET `listing_type` = %s WHERE `type_id` = %d ";
					$wpdb->query( $wpdb->prepare( $sql, $this->data['slug'], $result['term_id'] ) );
				}
			}

			return true;
		} else {
			if ( \is_wp_error( $result ) ) {
				\ListPlus()->error->add( 'term_save_error', $result->get_error_message() );
			}
			return false;
		}

	}


	/**
	 * Get terms.
	 *
	 * @see https://developer.wordpress.org/reference/classes/wp_term_query/__construct/
	 *
	 * @param array $args
	 * @return array
	 */
	public static function query( $args = [], $count = false ) {
		$tax = static::type();

		$args['taxonomy'] = $tax;
		if ( ! isset( $args['hide_empty'] ) ) {
			$args['hide_empty'] = false;
		}

		$terms = get_terms( $args );
		$results = [
			'items' => [],
			'found' => -1,
		];

		foreach ( (array) $terms as $term ) {
			$results['items'][] = new static( $term );
		}

		if ( $count ) {
			$results['found'] = wp_count_terms( $tax, $args );
		}

		return $results;
	}

	public function to_array() {
		return $this->data;
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
