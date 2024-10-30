<?php

namespace ListPlus\CRUD;

/**
 * Base class for building models.
 *
 * @author Brandon Wamboldt <brandon.wamboldt@gmail.com>
 */
class Item_Meta extends Model {

	protected $data = array();
	static protected $fields = [
		'mid',
		'post_id',
		'listing_type',
		'type_id',
		'item_id', // Custom ID for item.
		'order_id',
		'product_id',
		'order_item_id',
		'expired',
		'start_date', // Use for listing has start date such as event.
		'end_date', // Use for listing has end date such as event.
		'verified',
		'claimed',
		'is_featured',
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
		'enquiry_status',

		'count_review',
		'rating_score',
		'rating_meta',
		'count_report',
	];

	/**
	 * Get the column used as the primary key, defaults to 'id'.
	 *
	 * @return string
	 */
	public static function get_primary_key() {
		return 'mid';
	}

	/**
	 * Overwrite this in your concrete class. Returns the table name used to
	 * store models of this class.
	 *
	 * @return string
	 */
	public static function get_table() {
		global $wpdb;
		return $wpdb->prefix . 'lp_item_meta';
	}

}
