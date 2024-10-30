<?php

namespace ListPlus\CRUD;

/**
 * Base class for building models.
 */
class Review extends Model {

	static protected $fields = [
		'id',
		'post_id',
		'user_id',
		'status',
		'highlight',
		'weight',
		'rating',
		'ip',
		'email',
		'name',
		'title',
		'content',
		'meta',
		'created_at',
		'updated_at',
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
	 * Get an array of fields to search during a search query.
	 *
	 * @return array
	 */
	public static function get_searchable_fields() {
		return [ 'created_at', 'rating', 'weight', 'email', 'user_id', 'post_id' ];
	}


	/**
	 * Overwrite this in your concrete class. Returns the table name used to
	 * store models of this class.
	 *
	 * @return string
	 */
	public static function get_table() {
		global $wpdb;
		return $wpdb->prefix . 'lp_reviews';
	}

	public function to_approved() {
		$this->data['status'] = 'approved';
		if ( $this->get_id() ) {
			$this->save();
		}
	}

	public function to_pending() {
		$this->data['status'] = 'pending';
		if ( $this->get_id() ) {
			$this->save();
		}
	}

	public function to_reject() {
		$this->data['status'] = 'reject';
		if ( $this->get_id() ) {
			$this->save();
		}
	}

	public function get_name() {
		if ( ! $this->data['name'] ) {
			if ( $this->data['user_id'] ) {
				return get_the_author_meta( 'display_name', $this->data['user_id'] );
			}
		}
		return $this->data['name'];
	}

	public function get_user_rating_count() {
		if ( ! $this->data['name'] ) {
			if ( $this->data['user_id'] ) {
				return get_the_author_meta( 'display_name', $this->data['user_id'] );
			}
		}
		return $this->data['name'];
	}



}
