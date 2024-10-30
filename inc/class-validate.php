<?php

namespace ListPlus;

class Validate {
	protected $fields = [];
	protected $submited_data = [];
	protected $submited_files = [];
	protected $data = [];
	protected $errors = [];

	public function __construct( $fields, $submited_data = [], $submited_files = [] ) {
		$this->fields = $fields;
		$this->submited_data = (array) $submited_data;
		$this->submited_files = (array) $submited_files;
		$this->run();
	}

	public function sanitize_text( $str ) {
		return \sanitize_text_field( $str );
	}
	public function sanitize_number( $number ) {
		return \is_numeric( $number ) ? $number : null;
	}
	public function sanitize_int( $number ) {
		if ( ! \is_numeric( $number ) ) {
			return null;
		}
		return (int) $number;
	}
	public function sanitize_float( $number ) {
		if ( ! \is_numeric( $number ) ) {
			return null;
		}
		return (float) $number;
	}
	public function sanitize_html( $str ) {
		if ( ! $str ) {
			return null;
		}
		return \wp_kses_post( $str );
	}

	public function sanitize_email( $str ) {
		$email = \sanitize_email( $str );
		return $email;
	}

	public function sanitize_not_empty( $val ) {
		return ! empty( $val );
	}

	public function sanitize_array_number( $array ) {
		$new_array = [];
		foreach ( (array) $array as $k => $v ) {
			$n = $this->sanitize_number( $v );
			if ( ! is_null( $n ) ) {
				$new_array[ $k ] = $n;
			}
		}

		return empty( $new_array ) ? null : $new_array;
	}

	public function sanitize_datetime( $datetime ) {
		$format = 'Y-m-d H:i:s';
		$d = \DateTime::createFromFormat( $format, $datetime );
		return $d && $d->format( $format ) == $datetime ? $datetime : null;
	}

	public function sanitize_url( $url ) {
		$url = \esc_url( $url );
		return $url ? $url : null;
	}

	/**
	 * Undocumented function
	 *
	 * @param string $hour
	 * @return string|null
	 */
	public function sanitize_hour( $hour ) {

		$hour = $this->sanitize_text( $hour );
		if ( ! $hour ) {
			return null;
		}
		$hour = \strtoupper( $hour );

		$ah = explode( ':', $hour );
		$h = null;
		$m = null;
		$a = null;
		preg_match( '/([0-9]+)/', $ah[0], $matches );
		if ( ! empty( $matches ) ) {
			$h = $this->sanitize_int( $matches[1] );
		}

		if ( is_null( $h ) ) {
			return null;
		}

		if ( isset( $ah[1] ) ) {
			preg_match( '/([0-9]+)/', $ah[1], $matches );
			if ( ! empty( $matches ) ) {
				$m = $this->sanitize_int( $matches[1] );
			}
		}

		if ( \strpos( $hour, 'PM' ) ) {
			$a = 'PM';
		} else {
			$a = 'AM';
		}

		if ( $h > 24 ) {
			$h = 24;
		}
		if ( $h < 0 ) {
			$h = 0;
		}

		if ( ! $a ) {
			if ( $h <= 12 ) {
				$a = 'AM';
			}
		}

		if ( $h > 12 ) {
			$h = $h - 12;
			$a = 'PM';
		}

		return sprintf( '%02d:%02d %s', $h, $m, $a );
	}

	/**
	 * Check if this field is required.
	 *
	 * @param array $field
	 * @return boolean
	 */
	public function is_preset( $field ) {
		if ( isset( $field['_type'] ) && 'preset' == $field['_type'] ) {
			return true;
		}
		return false;
	}

	/**
	 * Check if this field is required.
	 *
	 * @param array $field
	 * @return boolean
	 */
	public function is_required( $field ) {
		$required = false;
		if ( isset( $field['custom'] ) ) {
			if ( isset( $field['custom']['required'] ) ) {
				$required = $field['custom']['required'];
			}
		}
		return $required;
	}

	/**
	 * Get error|invalid message.
	 *
	 * @param array $field
	 * @return string|null
	 */
	protected function get_error_message( $field, $key = null ) {
		if ( isset( $field['custom'] ) && isset( $field['custom']['required_msg'] ) && $field['custom']['required_msg'] ) {
			return $field['custom']['required_msg'];
		}
		if ( isset( $field['invalid_msg'] ) ) {
			if ( ! \is_array( $field['invalid_msg'] ) ) {
				return $field['invalid_msg'];
			}

			if ( $key ) {
				if ( isset( $field['invalid_msg'][ $key ] ) ) {
					return $field['invalid_msg'][ $key ];
				}
			}

			return $field['invalid_msg'];
		}
		return null;
	}

	/**
	 * $get value from submitted data.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function get_val( $key, $default = null ) {
		if ( isset( $this->submited_data[ $key ] ) ) {
			return $this->submited_data[ $key ];
		}
		return $default;
	}

	public function add_error( $code, $message = null ) {
		if ( ! $code ) {
			return;
		}
		$this->errors[ $code ] = $message;
	}
	public function remove_error( $code ) {
		unset( $this->errors[ $code ] );
	}

	public function field_validate( $field, $sanitize_type = 'text' ) {
		$name = $field['name'];
		if ( ! $name ) {
			return;
		}
		$val = $this->get_val( $name );
		$method = 'sanitize_' . $sanitize_type;
		if ( \is_callable( [ $this, $method ] ) ) {
			$val = \call_user_func_array( [ $this, $method ], [ $val ] );
		} else {
			$val = $this->sanitize_text( $val );
		}

		if ( $this->is_required( $field ) ) {
			if ( ! $val ) {
				$this->add_error( $name, $this->get_error_message( $field ) );
			}
		}
		$this->data[ $name ] = $val;
	}

	public function field_post_title( $field ) {
		$this->field_validate( $field, 'text' );
	}

	public function field_post_content( $field ) {
		$this->field_validate( $field, 'html' );
	}

	public function field_post_date( $field ) {
		$this->field_validate( $field, 'datetime' );
	}

	public function field_price( $field ) {
		$this->field_validate( $field, 'float' );
	}

	public function field_price_range( $field ) {
		$this->field_validate( $field, 'int' );
	}

	public function field_categories( $field ) {
		$name = $field['name'];
		$tax = $field['tax'];
		$values     = $this->get_val( $name );
		$values = $this->sanitize_array_number( $values );
		if ( ! isset( $this->data['taxonomies'] ) ) {
			$this->data['taxonomies'] = [];
		}
		$this->data['taxonomies'][ $tax ] = [];
		if ( ! empty( $values ) ) {
			foreach ( $values as $cat_id ) {
				$this->data['taxonomies'][ $tax ][] = [
					'term_id' => $cat_id,
				];
			}
		}
	}

	public function field_region( $field ) {
		$name = $field['name'];
		$tax = $field['tax'];
		$value     = $this->get_val( $name );
		$value = $this->sanitize_int( $value );
		$this->data['taxonomies'][ $tax ] = [];
		if ( $value ) {
			$this->data['taxonomies'][ $tax ][] = [
				'term_id' => $value,
			];
		}

		$is_required = $this->is_required( $field );
		if ( $is_required ) {
			if ( empty( $value ) ) {
				$this->add_error( $name, $this->get_error_message( $field ) );
			}
		}
	}

	public function field_email( $field ) {
		$this->field_validate( $field, 'email' );
	}

	public function field_phone( $field ) {
		$this->field_validate( $field, 'text' );
	}

	public function field_map( $field ) {
		$names       = $field['name'];
		$lat_name    = $names['lat'];
		$lng_name    = $names['lng'];
		$addr_name   = $names['address'];
		$is_required = $this->is_required( $field );
		$lat_val     = $this->get_val( $lat_name );
		$lng_val     = $this->get_val( $lng_name );
		$addr_val    = $this->get_val( $addr_name );
		$lat_val     = $this->sanitize_number( $lat_val );
		$lng_val     = $this->sanitize_number( $lng_val );
		$addr_val    = $this->sanitize_text( $addr_val );

		$has_fill_val = ( $lat_val || $lng_val || $addr_val ) ? true : false;
		if ( $has_fill_val || $is_required ) {

			if ( ! $lat_val ) {
				$this->add_error( $lat_name, '' );
			}

			if ( ! $lng_val ) {
				$this->add_error( $lng_name, '' );
			}
			if ( ! $addr_val ) {
				$this->add_error( $addr_name, '' );
			}
		}

		$this->data[ $lat_name ] = $lat_val;
		$this->data[ $lng_name ] = $lng_val;
		$this->data[ $addr_name ] = $addr_val;

	}


	public function field_websites( $field ) {
		$name = $field['name'];
		$val = $this->get_val( $name );
		$websites = [];
		foreach ( (array) $val as $k => $v ) {
			$v = $this->sanitize_url( $v );
			if ( $v ) {
				$websites[ $k ] = $v;
			}
		}

		$is_required = $this->is_required( $field );
		if ( $is_required ) {
			if ( empty( $websites ) ) {
				$this->add_error( $name, $this->get_error_message( $field ) );
			}
		}

		$this->data[ $name ] = $websites;
	}

	public function field_video_url( $field ) {
		$this->field_validate( $field, 'url' );
	}

	public function field_open_hours( $field ) {
		$name         = $field['name'];
		$open_hours   = [];
		$is_required  = $this->is_required( $field );
		$submit_hours = $this->get_val( $name );
		$days         = ListPlus()->settings->get_days();
		foreach ( $days as $day => $label ) {
			$d_data = isset( $submit_hours[ $day ] ) ? $submit_hours[ $day ] : null;
			if ( ! $d_data ) {
				continue;
			}

			$d_data = wp_parse_args(
				$d_data,
				[
					'satus' => '',
					'hours' => [],
				]
			);

			if ( in_array( $d_data['status'], [ 'all_day', 'closed', 'enter' ], true ) ) {

				if ( 'enter' == $d_data['status'] ) {
					$day_hours = [];
					foreach ( (array) $d_data['hours'] as $hi => $h ) {
						$h = wp_parse_args(
							$h,
							[
								'from' => null,
								'to' => null,
							]
						);
						$from = $this->sanitize_hour( $h['from'] );
						$to = $this->sanitize_hour( $h['to'] );

						if ( ! $from || ! $to ) {
							continue;
						}

						$day_hours[] = [
							'from' => $from,
							'to' => $to,
						];

					} // end loop hour.

					if ( ! empty( $day_hours ) ) {
						$open_hours[ $day ] = [
							'status' => $d_data['status'],
							'hours' => $day_hours,
						];
					}
				} else {
					$open_hours[ $day ] = [
						'status' => $d_data['status'],
						'hours' => [],
					];
				}
			}
		}

		if ( $is_required ) {
			if ( empty( $open_hours ) ) {
				$this->add_error( $name, $this->get_error_message( $field ) );
			}
		}

		$this->data[ $name ] = $open_hours;

	}

	public function field_media_files( $field ) {
		$name = $field['name'];
		$is_required  = $this->is_required( $field );
		$media_files = $this->get_val( $name );
		$media_files = $this->sanitize_array_number( $media_files );
		// Just check if have upload file(s).
		$upload_files = isset( $_FILES[ $name ] ) ? true : null; // WPCS: sanitization ok.
		if ( $is_required ) {
			if ( ! $media_files && ! $upload_files ) {
				$this->add_error( $name, $this->get_error_message( $field ) );
			}
		}

	}

	public function field_dynamic_tax( $field ) {
		$id         = $field['id'];
		$tax        = $field['tax'];
		$name       = $field['name'];
		$tax_name   = $tax;

		$submit_name = 'tax_' . $tax_name;
		$submit_terms = $this->get_val( $submit_name );
		$terms = [];

		if ( ! empty( $submit_terms ) ) {
			$i = 0;
			foreach ( $submit_terms  as $key => $submit_term ) {
				$submit_term = wp_parse_args(
					$submit_term,
					[
						'term_id' => '',
						'name' => '',
						'taxonomy' => $tax_name,
						'custom_value' => '',
						'custom_order'  => $i,
						'post_id'       => '',
					]
				);

				if ( ! \is_numeric( $submit_term['term_id'] ) ) {
					$submit_term['term_id'] = '';
				}
				$submit_term['term_id'] = (int) $submit_term['term_id'];
				$tax = \ListPlus()->taxonomies->get_custom( $submit_term['taxonomy'] );
				if ( ! $tax ) {
					continue;
				}
				if ( ! $tax['allow_new'] && ! $submit_term['term_id'] ) {
					continue; // Skip if this tax not allow add new.
				}

				$submit_term['name'] = $this->sanitize_text( $submit_term['name'] );
				$submit_term['custom_value'] = $this->sanitize_text( $submit_term['custom_value'] );
				if ( $submit_term['term_id'] || $submit_term['name'] ) {
					$terms[] = $submit_term;
					$i++;
				}
			}
		}

		$is_required  = $this->is_required( $field );
		if ( $is_required ) {
			if ( empty( $terms ) ) {
				$this->add_error( $id, $this->get_error_message( $field ) );
			}
		}

		if ( ! isset( $this->data['taxonomies'] ) ) {
			$this->data['taxonomies'] = [];
		}

		$this->data['taxonomies'][ $tax_name ] = $terms;

	}


	public function field_custom( $field ) {
		$name = $field['name'];
		$is_required  = $this->is_required( $field );
		$val = $this->get_val( $name );
		$val = $this->sanitize_text( $val );
		if ( $is_required ) {
			if ( ! $val ) {
				$this->add_error( $id, $this->get_error_message( $field ) );
			}
		}
		$this->data[ $name ] = $val;
	}


	protected function run() {
		foreach ( $this->fields as $field ) {
			$cb = null;
			$id = $field['id'];

			if ( 'dynamic_tax' == $field['type'] ) {
				$cb = [ $this, 'field_dynamic_tax' ];
			} elseif ( \method_exists( $this, 'field_' . $id ) ) {
				$cb = [ $this, 'field_' . $id ];
			} else {
				$cb = [ $this, 'field_' . $id ];
			}

			if ( ! \is_callable( $cb ) ) {
				$cb = [ $this, 'field_custom' ];
			}

			$cb = \apply_filters( 'listing_field_validate_cb', $cb, $field );
			if ( \is_callable( $cb ) ) {
				\call_user_func_array( $cb, [ $field, $this ] );
			}
		}
	}

	public function ok() {
		return empty( $this->errors );
	}

	public function get_error_codes() {
		return \array_keys( $this->errors );
	}

	public function get_data() {
		return $this->data;
	}



}
