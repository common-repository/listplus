<?php

namespace ListPlus\CRUD;

/**
 * Base class for building models.
 *
 * @author Brandon Wamboldt <brandon.wamboldt@gmail.com>
 */
class Post extends Model {

	/**
	 * Get the column used as the primary key, defaults to 'id'.
	 *
	 * @return string
	 */
	public static function get_primary_key() {
		return 'ID';
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

}
