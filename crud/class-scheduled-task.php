<?php

namespace ListPlus\CRUD;

/**
 * Base class for building models.
 *
 * @author Brandon Wamboldt <brandon.wamboldt@gmail.com>
 */
class Scheduled_Task extends Model {

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
		return $wpdb->prefix . 'lp_scheduled_tasks';
	}

	public static function get_cron_tasks( $limit = 25 ) {
		global $wpdb;

		// Get the table name.
		$table = static::get_table();
		$now = current_time( 'mysql', true );

		// Get the items.
		$results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE `status`='pending' and `date_scheduled` <= %s ORDER BY date_scheduled ASC LIMIT %d", $now, $limit ) );

		foreach ( $results as $index => $result ) {
			$results[ $index ] = static::create( (array) $result );
		}

		return $results;
	}

	public static function delete_completed_task( $limit = 3000 ) {
		global $wpdb;
		$table = static::get_table();
		$past = \strtotime( 'yesterday' );
		$past_20_min = \strtotime( '20 minutes ago' );
		$wpdb->query( $wpdb->prepare( "UPDATE `{$table}` SET `status`='pending' WHERE `status`='doing' and `date_started` <= %s ORDER BY date_started ASC LIMIT %d", $past_20_min, $limit ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM `{$table}` WHERE `status`='completed' and `date_completed` <= %s ORDER BY date_completed ASC LIMIT %d", $past, $limit ) );
	}

}
