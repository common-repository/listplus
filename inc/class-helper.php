<?php
namespace ListPlus;

class Helper {

	public static function url( $args = [] ) {
		return esc_url( add_query_arg( $args, 'admin.php' ) );
	}

	public static function rating_atts( $size = 18 ) {
		$max = (int) ListPlus()->settings->get( 'review_max' );
		return ' data-rateit-starwidth="' . $size . '" data-rateit-starheight="' . $size . '"  data-rateit-max="' . $max . '" ';
	}


	public static function to_price_range( $number ) {
		$number = \intval( $number );
		if ( 0 >= $number ) {
			return 0;
		}
		return \strlen( (string) $number );
	}

	/**
	 * Get the price format depending on the currency position.
	 *
	 * @return string
	 */
	public static function get_price_format() {
		$currency_pos = get_option( 'woocommerce_currency_pos' );
		$format       = '%1$s%2$s';

		switch ( $currency_pos ) {
			case 'left':
				$format = '%1$s%2$s';
				break;
			case 'right':
				$format = '%2$s%1$s';
				break;
			case 'left_space':
				$format = '%1$s&nbsp;%2$s';
				break;
			case 'right_space':
				$format = '%2$s&nbsp;%1$s';
				break;
		}

		return apply_filters( 'woocommerce_price_format', $format, $currency_pos );
	}

	public static function get_currency_symbol() {
		return '$';
	}

	public static function price_format( $price, $args = array() ) {
		$args = apply_filters(
			'listing_price_args',
			wp_parse_args(
				$args,
				array(
					'ex_tax_label'       => false,
					'currency'           => '',
					'decimal_separator'  => '.',
					'thousand_separator' => ',',
					'decimals'           => 2,
					'price_format'       => static::get_price_format(),
				)
			)
		);

		$unformatted_price = $price;
		$negative          = $price < 0;
		$price             = apply_filters( 'raw_listing_price', floatval( $negative ? $price * -1 : $price ) );
		$price             = apply_filters( 'formatted_listing_price', number_format( $price, $args['decimals'], $args['decimal_separator'], $args['thousand_separator'] ), $price, $args['decimals'], $args['decimal_separator'], $args['thousand_separator'] );

		if ( apply_filters( 'listing_price_trim_zeros', false ) && $args['decimals'] > 0 ) {
			$price = wc_trim_zeros( $price );
		}

		$formatted_price = ( $negative ? '-' : '' ) . sprintf( $args['price_format'], '<span class="l-price-currency-symbol">' . static::get_currency_symbol( $args['currency'] ) . '</span>', $price );
		$return          = '<span class="l-price amount">' . $formatted_price . '</span>';

		/**
		 * Filters the string of price markup.
		 *
		 * @param string $return            Price HTML markup.
		 * @param string $price             Formatted price.
		 * @param array  $args              Pass on the args.
		 * @param float  $unformatted_price Price as float to allow plugins custom formatting. Since 3.2.0.
		 */
		return apply_filters( 'listing_price', $return, $price, $args, $unformatted_price );
	}

	public static function get_listing_fields() {
		return include LISTPLUS_PATH . '/inc/listing-fields.php';
	}

	public static function get_listing_display_fields() {
		return include LISTPLUS_PATH . '/inc/listing-display-fields.php';
	}

	public static function get_countries() {
		return require LISTPLUS_PATH . '/inc/countries.php';
	}

	public static function download_image( $url, $post_id = 0 ) {
		if ( ! $url || empty( $url ) ) {
			return false;
		}
		$name = false;
		// These files need to be included as dependencies when on the front end.
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		$file_array = array();
		// Download file to temp location.
		$file_array['tmp_name'] = download_url( $url );

		// If error storing temporarily, return the error.
		if ( empty( $file_array['tmp_name'] ) || is_wp_error( $file_array['tmp_name'] ) ) {
			return false;
		}

		if ( $name ) {
			$file_array['name'] = $name;
		} else {
			// Set variables for storage, fix file filename for query strings.
			preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $file_array['tmp_name'], $matches );
			if ( ! empty( $matches ) ) {
				$file_array['name'] = basename( $matches[0] );
			} else {
				$bname = \basename( $url );
				$bname = explode( '.', $bname );
				$ext = 'jpeg';
				if ( count( $bname ) > 1 ) {
					$ext = $bname[1];
				}
				$file_array['name'] = uniqid( 'image-' ) . '.' . $ext;
			}
		}

		// Do the validation and storage stuff.
		$id = media_handle_sideload( $file_array, $post_id );

		// If error storing permanently, unlink.
		if ( is_wp_error( $id ) ) {
			@unlink( $file_array['tmp_name'] );
			return false;
		}

		return $id;
	}

	public static function upload_image( $url, $post_id ) {
		if ( ! $url || empty( $url ) ) {
			return false;
		}
		// These files need to be included as dependencies when on the front end.
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		$file_array = array();
		// Download file to temp location.
		$file_array['tmp_name'] = download_url( $url );

		// If error storing temporarily, return the error.
		if ( empty( $file_array['tmp_name'] ) || is_wp_error( $file_array['tmp_name'] ) ) {
			return false;
		}

		if ( $name ) {
			$file_array['name'] = $name;
		} else {
			// Set variables for storage, fix file filename for query strings.
			preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $file_array['tmp_name'], $matches );
			if ( ! empty( $matches ) ) {
				$file_array['name'] = basename( $matches[0] );
			} else {
				$file_array['name'] = uniqid( 'store-' ) . '.jpeg';
			}
		}

		// Do the validation and storage stuff.
		$id = media_handle_sideload( $file_array, 0 );

		// If error storing permanently, unlink.
		if ( is_wp_error( $id ) ) {
			@unlink( $file_array['tmp_name'] );
			return false;
		}

		return $id;
	}

	public static function handle_upload( $input_name, $post_id = 0, $post_data = [] ) {
		$file_array = isset( $_FILES[  $input_name ] ) ? $_FILES[ $input_name ] : false;
		if ( ! $file_array || empty( $file_array ) ) {
			return false;
		}

		if ( ! isset( $file_array['name'] ) ) {
			return false;
		}

		// These files need to be included as dependencies when on the front end.
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$upload_files = [];
		$upload_ids = [];
		if ( is_array( $file_array['name'] ) ) {
			foreach ( $file_array['name'] as $index => $name ) {
				$file = [
					'name' => $name,
					'type' => $file_array['type'][ $index ],
					'tmp_name' => $file_array['tmp_name'][ $index ],
					'error' => $file_array['error'][ $index ],
					'size' => $file_array['size'][ $index ],
				];

				$id = media_handle_sideload( $file, $post_id, null, $post_data );
				if ( $id && ! \is_wp_error( $id ) ) {
					$upload_ids[ $index ] = $id;
				}
			}

			return empty( $upload_ids ) ? false : $upload_ids;
		} else {
			$id = media_handle_sideload( $file_array, $post_id, null, $post_data );
			if ( $id && ! \is_wp_error( $id ) ) {
				return $id;
			}
			return false;
		}

	}

	public static function to_html_attrs( $attrs = [] ) {
		$html = '';
		foreach ( $attrs as $k => $v ) {

			switch ( \strtolower( $k ) ) {
				case 'class':
					if ( \is_array( $v ) ) {
						$v = join( ' ', $v );
					}
					break;
				case 'id':
					if ( \is_array( $v ) ) {
						$v = join( '-', $v );
					}
					break;
				default:
					if ( \is_array( $v ) ) {
						$v = wp_json_encode( $v );
					}
			}

			$html .= " {$k}=\"" . \esc_attr( $v ) . '" ';
		}
		return $html;
	}


}


