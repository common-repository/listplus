<?php

namespace ListPlus\CRUD;

/**
 * Base class for building models.
 */
class Claim extends Model {

	static protected $fields = [
		'id',
		'post_id',
		'user_id',
		'status',
		'ip',
		'email',
		'content',
		'meta',
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
		return $wpdb->prefix . 'lp_claim_entries';
	}

	public function get_listing_id() {
		return isset( $this->data['post_id'] ) ? $this->data['post_id'] : null;
	}

	public function mark_approved() {
		$this->data['status'] = 'approved';
		if ( $this->get_id() ) {
			$this->save();
			$ls_id = $this->get_listing_id();
			$listing = new \ListPlus\CRUD\Listing( $ls_id );
			if ( $listing->is_existing_listing() ) {
				$listing->update_meta_fields( [ 'claimed' => $this->user_id ] );
			}
		}
	}

	public function mark_pending() {
		$this->data['status'] = 'pending';
		if ( $this->get_id() ) {
			$this->save();
			$ls_id = $this->get_listing_id();
			$listing = new \ListPlus\CRUD\Listing( $ls_id );
			if ( $listing->is_existing_listing() ) {
				$listing->update_meta_fields( [ 'claimed' => 0 ] );
			}
		}
	}

	public function mark_rejected() {
		$this->data['status'] = 'rejected';
		if ( $this->get_id() ) {
			$this->save();
			$ls_id = $this->get_listing_id();
			$listing = new \ListPlus\CRUD\Listing( $ls_id );
			if ( $listing->is_existing_listing() ) {
				$listing->update_meta_fields( [ 'claimed' => 0 ] );
			}
		}
	}

}
