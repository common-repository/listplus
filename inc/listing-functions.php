<?php
namespace ListPlus;

use ListPlus;

global $listing;

function setup_listing( $post = null, $skip_cache = false ) {
	global $listing;
	$id = false;
	if ( is_numeric( $post ) ) {
		$id = $post;
	} elseif ( $post instanceof \WP_Post ) {
		$id = $post->ID;
	} elseif ( $post instanceof ListPlus\CRUD\Listing ) {
		$id = $post->get_id();
		$listing = $post;
		return;
	}
	$listing = get_listing( $id );
}

function get_listing( $id = null, $listing_type = null ) {
	if ( ! $id ) {
		global $post;
		if ( $post ) {
			$id = $post->ID;
			$listing_type = $post->post_type;
		}
	}

	if ( ! $id ) {
		if ( isset( $_GET['listing_id'] ) && absint( $_GET['listing_id'] ) ) {
			$id = absint( $_GET['listing_id'] );
		}
	}

	if ( $listing_type ) {
		$get_args = [
			'ID' => $id,
			'listing_type' => $listing_type,
		];
	} else {
		$get_args = $id;
	}

	$listing = \ListPlus\CRUD\Listing::cache_get( $id );

	if ( ! $listing ) {
		$listing = new ListPlus\CRUD\Listing( $get_args );
		\ListPlus\CRUD\Listing::cache_set( $listing );
	}

	return $listing;
}



/**
 * Get listing type.
 *
 * @param mixed $listing_type
 * @return ListPlus\CRUD\Listing_Type
 */
function get_listing_type( $listing_type ) {
	$type = false;
	$group_name = 'listing_type';
	if ( \is_numeric( $listing_type ) ) {
		$key = 'listing_type_id_' . $listing_type;
		$type = \wp_cache_get( $key, $group_name );
		if ( $type ) {
			return $type;
		}
	} else {
		$key = 'listing_type_slug_' . $listing_type;
		$type = \wp_cache_get( $key, $group_name );
		if ( $type ) {
			return $type;
		}
	}

	$type = new ListPlus\CRUD\Listing_Type( $listing_type );
	if ( $type->get_id() ) {
		wp_cache_set( 'listing_type_id_' . $type->get_id(), $type, $group_name );
		wp_cache_set( 'listing_type_slug_' . $type->get_slug(), $type, $group_name );
	}
	return $type;
}


function get_editing_listing() {
	$listing_type = isset( $_REQUEST['listing_type'] ) ? sanitize_text_field( $_REQUEST['listing_type'] ) : ''; // WPCS: Input var ok.
	$id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : false;
	$item = get_listing( $id, $listing_type );
	return $item;
}


/**
 * Get listing type for editing listing.
 *
 * @return \ListPlus\CRUD\Listing_Type
 */
function get_type_for_editing_listing() {
	$item = get_editing_listing();
	$listing_type = isset( $_REQUEST['listing_type'] ) ? sanitize_text_field( $_REQUEST['listing_type'] ) : ''; // WPCS: Input var ok.
	if ( $item->get_id() ) {
		$listing_type = $item['listing_type'];
	}
	$query_args['listing_type'] = $listing_type;
	$type = get_listing_type( $listing_type );
	return $type;
}


function get_theme_slug() {
	return apply_filters( 'listing_theme_slug_for_templates', get_option( 'template' ) );
}


/**
 * Function to get the client IP address
 *
 * @return string
 */
function get_client_ip() {
	$ipaddress = '';
	if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) ) {
		$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
	} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} elseif ( isset( $_SERVER['HTTP_X_FORWARDED'] ) ) {
		$ipaddress = $_SERVER['HTTP_X_FORWARDED'];
	} elseif ( isset( $_SERVER['HTTP_FORWARDED_FOR'] ) ) {
		$ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
	} elseif ( isset( $_SERVER['HTTP_FORWARDED'] ) ) {
		$ipaddress = $_SERVER['HTTP_FORWARDED'];
	} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
		$ipaddress = $_SERVER['REMOTE_ADDR'];
	} else {
		$ipaddress = 'UNKNOWN';
	}
	return $ipaddress;
}

