<?php

namespace ListPlus\CRUD;

/**
 * Base class for building models.
 */
class Report extends Model {

	static protected $fields = [
		'id',
		'post_id',
		'user_id',
		'status',
		'email',
		'reason',
		'meta',
		'ip',
		'created_at',
	];

	/**
	 * Get the column used as the primary key, defaults to 'id'.
	 *
	 * @return string
	 */
	public static function get_primary_key() {
		return 'id';
	}

	/**
	 * Overwrite this in your concrete class. Returns the table name used to
	 * store models of this class.
	 *
	 * @return string
	 */
	public static function get_table() {
		global $wpdb;
		return $wpdb->prefix . 'lp_reports';
	}

	public function mark_read() {
		$this->data['status'] = 'read';
		if ( $this->get_id() ) {
			$this->save();
		}
	}

	public function mark_unread() {
		$this->data['status'] = 'unread';
		if ( $this->get_id() ) {
			$this->save();
		}
	}

}
