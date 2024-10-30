<?php

namespace ListPlus\CRUD;

use ListPlus\CRUD\Taxonomy;
use ListPlus\Helper;

class Listing_Type extends Taxonomy {

	protected $meta_keys = [
		'singular_name',
		'status',
		'restrict_categories',
		'allow_categories',
		'allow_region',
		'allow_menu',
		'support_taxs',
		'fields',
		'quick_filters',
		'tax_highlight',
		'highlight_limit',

		'support_price',
		'support_price_range',
		'single_layout',
		'single_sidebar',
		'single_main',
		'has_form',
		'icon',
	];

	protected $fields = null;
	private $support_taxs = [];
	static $all_types = null;

	public function __construct( $data = array() ) {
		parent::__construct( $data );
	}

	public function valid() {
		return isset( $this->data['slug'] ) && $this->data['slug'];
	}

	public static function type() {
		return 'listing_type';
	}

	public static function get_all_active() {
		$data = [];
		$all = static::get_all();
		foreach ( $all['items'] as $item ) {
			if ( 'active' === $item['status'] ) {
				$data[] = $item;
			}
		}
		return $data;
	}

	public function is_allow( $thing ) {
		if ( isset( $this->data[ 'allow_' . $thing ] ) && 'yes' === $this->data[ 'allow_' . $thing ] ) {
			return true;
		}
		return false;
	}

	public function is_support_cat() {
		return $this->is_allow( 'categories' );
	}

	public function is_support_menu() {
		return $this->is_allow( 'menu' );
	}

	public function is_support_region() {
		return $this->is_allow( 'region' );
	}

	public function is_support_tax( $tax ) {
		$support_taxs = $this->get_support_taxs();
		if ( ! empty( $support_taxs ) && \in_array( $tax, $support_taxs, true ) ) {
			return true;
		}
		return false;
	}

	public function get_support_taxs() {
		$this->get_fields();
		return $this->support_taxs;
	}

	public function get_custom_meta_fields() {
		$fields = [];
		foreach ( (array) $this->get_fields() as $field ) {
			if ( 'custom' == $field['_type'] ) {
				$fields[] = $field;
			}
		}
		return $fields;
	}

	protected function pre_save_data() {
		$fields = $this->get_fields();
		$this->data['support_price'] = '';
		$this->data['support_price_range'] = '';
		foreach ( (array) $fields as $f ) {
			if ( isset( $f['name'] ) ) {
				if ( 'price' == $f['name'] ) {
					$this->data['support_price'] = 'yes';
				}
				if ( 'price_range' == $f['name'] ) {
					$this->data['support_price_range'] = 'yes';
				}
			}
		}

		$has_form = '';
		$single_main = $this->single_main;
		if ( \is_string( $single_main ) ) {
			$single_main = \json_decode( $single_main, true );
		}

		$single_sidebar = $this->single_sidebar;
		if ( \is_string( $single_sidebar ) ) {
			$single_sidebar = \json_decode( $single_sidebar, true );
		}

		$form_fields = [ 'enquiry', 'claim', 'review', 'report' ];
		if ( \is_array( $single_main ) ) {
			foreach ( $single_main as $field ) {
				if ( ! is_array( $field ) ) {
					continue;
				}
				if ( in_array( $field['id'], $form_fields, true ) ) {
					$has_form = 'yes';
				}
			}
		}

		if ( \is_array( $single_sidebar ) ) {
			foreach ( $single_sidebar as $field ) {
				if ( ! is_array( $field ) ) {
					continue;
				}
				if ( in_array( $field['id'], $form_fields, true ) ) {
					$has_form = 'yes';
				}
			}
		}

		$this->data['has_form'] = $has_form;

	}

	public function get_fields() {
		if ( ! is_null( $this->fields ) ) {
			return $this->fields;
		}

		$support_taxs = [];

		$all_fields = [];
		foreach ( Helper::get_listing_fields() as $key => $field ) {
			$all_fields[ $field['id'] ] = $field;
		}

		$fields = isset( $this->data['fields'] ) ? $this->data['fields'] : [];

		if ( ! is_array( $fields ) ) {
			$fields = \json_decode( $fields, true );
		}

		foreach ( (array) $fields as $index => $field ) {
			$o_field = false;
			if ( isset( $all_fields[ $field['id'] ] ) ) {
				$o_field = $all_fields[ $field['id'] ];
				if ( isset( $field['title'] ) ) {
					$field['_title'] = $field['title'];
				}
				$field['title'] = $o_field['title'];
				$field['type'] = isset( $o_field['type'] ) ? $o_field['type'] : '';
				$field['atts'] = [];

				if ( isset( $o_field['atts'] ) ) {
					$field['atts'] = $o_field['atts'];
				}
				if ( isset( $o_field['options'] ) ) {
					$field['options'] = $o_field['options'];
				}
				if ( isset( $o_field['name'] ) ) {
					$field['name'] = $o_field['name'];
				}
			}

			if ( ! isset( $field['atts'] ) || ! \is_array( $field['atts'] ) ) {
				$field['atts'] = [];
			}

			if ( isset( $field['custom'] ) ) {
				$field['custom'] = wp_parse_args(
					$field['custom'],
					[
						'label'        => '',
						'placeholder'  => '',
						'desc'         => '',
						'name'         => '',
						'options'      => '',
						'required'     => '',
						'required_msg' => '',
						'show_front'   => '',
					]
				);

				if ( $field['custom']['label'] ) {
					$field['title'] = $field['custom']['label'];
				}
				if ( $field['custom']['desc'] ) {
					$field['desc'] = $field['custom']['desc'];
				}
				if ( $field['custom']['placeholder'] ) {
					$field['atts']['placeholder'] = $field['custom']['placeholder'];
				}
				if ( 'custom' == $field['_type'] ) {
					if ( $field['custom']['name'] ) {
						$field['name'] = 'custom__' . $field['custom']['name'];
					}

					if ( 'checkbox' == $field['id'] ) {
						if ( $field['custom']['label'] ) {
							$field['checkbox_label'] = $field['custom']['label'];
						} else {
							$field['checkbox_label'] = $field['title'];
						}
						unset( $field['title'] );
					}

					// Sanitize custom select options.
					if ( \in_array( $field['id'], [ 'select', 'checkbox', 'radio' ], true ) ) {
						$field['options'] = [];
						$options = \explode( "\n", $field['custom']['options'] );
						foreach ( $options as $key ) {
							$key = trim( $key );
							if ( \strpos( $key, ':' ) ) {
								$kv = explode( ':', $key );
								$field['options'][ trim( $kv[0] ) ] = trim( $kv[1] );
							} else {
								$field['options'][ $key ] = $key;
							}
						}
					}
				}
			}

			if ( isset( $field['type'] ) && 'dynamic_tax' == $field['type'] ) {
				$support_taxs[ $field['tax'] ] = $field['tax'];
			}
			$fields[ $index ] = $field;
		}
		if ( ! \is_array( $fields ) ) {
			$fields = [];
		}

		$this->support_taxs = $support_taxs;
		$this->fields = $fields;
		return $this->fields;
	}


	public static function get_all() {

		if ( ! \is_null( static::$all_types ) ) {
			return static::$all_types;
		}

		$get_status = [ 'active', 'deactive' ];

		$query_args = [
			'taxonomy'     => static::type(),
			'orderby'      => 'name',
			'hide_empty'   => false,
			'order'        => 'asc',
			// 'meta_key'     => '_status',
			// 'meta_value'   => $get_status,
			// 'meta_compare' => 'IN',
		];

		$query = new \WP_Term_Query( $query_args );

		$items = [];

		foreach ( (array) $query->get_terms() as $p ) {
			$item = new static( $p );
			$items[] = $item;
		}

		static::$all_types = [
			'items' => $items,
			'total_pages' => 1,
			'found_posts' => wp_count_terms( static::type(), $query_args ),
		];

		return static::$all_types;
	}

}
