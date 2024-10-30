<?php

namespace ListPlus;

use ListPlus\CRUD\Scheduled_Task;
use ListPlus\CRUD\Item_Meta;
use ListPlus\CRUD\Listing;

class Cron {
	/**
	 * The created time.
	 *
	 * Represents when the queue runner was constructed and used when calculating how long a PHP request has been running.
	 * For this reason it should be as close as possible to the PHP request start time.
	 *
	 * @var int
	 */
	private $created_time;

	public function __construct() {
		$this->created_time = microtime( true );
		add_filter( 'cron_schedules', [ __CLASS__, 'add_wp_cron_schedule' ] );

		if ( ! wp_next_scheduled( 'listplus_action_scheduled_tasks' ) ) {
			wp_schedule_event( time(), 'every_minute', 'listplus_action_scheduled_tasks' );
		}
		add_action( 'listplus_action_scheduled_tasks', array( $this, 'run' ) );

		if ( ! wp_next_scheduled( 'listplus_action_scheduled_builtin_tasks' ) ) {
			wp_schedule_event( time(), 'twice_hourly', 'listplus_action_scheduled_builtin_tasks' );
		}
		add_action( 'listplus_action_scheduled_builtin_tasks', array( $this, 'add_builtin_tasks' ) );

		if ( ! wp_next_scheduled( 'listplus_action_removed_conpleted_tasks' ) ) {
			wp_schedule_event( time() + 30, 'twice_hourly', 'listplus_action_removed_conpleted_tasks' );
		}
		add_action( 'listplus_action_removed_conpleted_tasks', array( $this, 'remove_completed_tasks' ) );

		if ( ! wp_next_scheduled( 'listplus_action_check_built_tasks' ) ) {
			wp_schedule_event( time() + 30, 'hourly', 'listplus_action_check_built_tasks' );
		}
		add_action( 'listplus_action_check_built_tasks', array( $this, 'check_built_tasks' ) );
		// static::task_check_listing_items_status();
		// Builtin tasks: update listing items status.
		add_action( 'listplus_check_listing_status', [ __CLASS__, 'task_check_listing_items_status' ] );

		// Builtin tasks: calc rating score.
		add_action( 'listplus_task_calc_listing_review', [ __CLASS__, 'task_calc_listing_review' ] );
	}


	public function add_builtin_tasks() {
		$this->add_task_once( 60, 'listplus_check_listing_status', [], 120 ); // repeate each 2 mins.
	}


	public function check_built_tasks() {
		// $builtin_tasks = [
		// 'listplus_check_listing_status' => 120,
		// ];
	}

	public static function task_calc_listing_review( $args = [] ) {

		$args = wp_parse_args( $args, [ 'post_id' => 0 ] );
		if ( ! $args['post_id'] ) {
			return;
		}

		$listing = new Listing( $args['post_id'] );
		$listing->calc_rating();

	}

	public static function task_check_listing_items_status( $args = [] ) {
		$now = \current_time( 'mysql', true );
		global $wpdb;
		$table_posts = $wpdb->posts;
		$table_meta = Item_Meta::get_table();
		$status = 'e';

		$sql = "SELECT mt.post_id 
				FROM {$table_meta} as mt 
				LEFT JOIN {$table_posts} as p ON mt.post_id = p.ID 
				WHERE 
					p.post_status = %s
					AND mt.`expired` <= %s
					AND mt.`expired` != %s
					ORDER by mt.`expired` ASC
					LIMIT %d
				";
		$items = $wpdb->get_results( $wpdb->prepare( $sql, 'publish', $now, '0000-00-00 00:00:00', 200 ) );
		if ( $items ) {
			$post_ids = wp_list_pluck( $items, 'post_id' );
			if ( ! empty( $post_ids ) ) {
				$wpdb->query( $wpdb->prepare( "UPDATE {$table_posts} SET post_status = %s WHERE `ID` IN(" . join( ', ', $post_ids ) . ')', 'expired' ) ); // WPCS: db call ok, unprepared SQL ok.
			}
		}

	}


	public function remove_completed_tasks() {
		Scheduled_Task::delete_completed_task();
	}

	public function add_review_task( $post_id ) {
		$this->add_task( 10, 'listplus_task_calc_listing_review', [ 'post_id' => $post_id ] );
	}

	/**
	 * Add schedule task.
	 *
	 * @param int    $delay Seconds to delay from now.
	 * @param string $hook hook ID.
	 * @param array  $args
	 * @param int    $recurrence How often the event should subsequently recur.
	 * @return void
	 */
	public function add_task( $delay, $hook, $args = [], $recurrence = 0 ) {
		$now = current_time( 'mysql', true );
		$timestamp_now = \strtotime( $now );
		if ( 'now' != $delay ) {
			$timestamp_now += absint( $delay );
		}
		$task                   = new Scheduled_Task();
		$task['hook']           = $hook;
		$task['status']         = 'pending';
		$task['args']           = $args;
		$task['recurrence']     = intval( $recurrence );
		$task['date_scheduled'] = \date( 'Y-m-d H:i:s', $timestamp_now );
		$task['created_at']     = $now;
		$task->save();
	}

	public function add_task_once( $delay, $hook, $args = [], $recurrence = 0 ) {
		$now = current_time( 'mysql', true );
		$timestamp_now = \strtotime( $now );
		if ( 'now' != $delay ) {
			$timestamp_now += absint( $delay );
		}
		$task = Scheduled_Task::find_one_by( 'hook', $hook );
		if ( $task ) {
			return true;
		}
		$task = new Scheduled_Task();

		$task['hook']           = $hook;
		$task['status']         = 'pending';
		$task['args']           = $args;
		$task['recurrence']     = intval( $recurrence );
		$task['date_scheduled'] = \date( 'Y-m-d H:i:s', $timestamp_now );
		$task['created_at']     = $now;
		$task->save();
	}

	public function run() {
		// update_option( 'listing_action_scheduler_run_queue', date( 'Y-m-d H:i:s' ) );
		$processed_actions = 0;
		$batch_size = 5;
		do {
			$processed_actions_in_batch = $this->do_batch( $batch_size );
			$processed_actions         += $processed_actions_in_batch;
			// update_option( 'listing_action_scheduler_all', $processed_actions );
		} while ( $processed_actions_in_batch > 0 && ! $this->batch_limits_exceeded( $processed_actions ) ); // keep going until we run out of actions, time, or memory.

	}

	protected function do_batch( $size ) {
		$processed_actions = 0;
		$tasks = Scheduled_Task::get_cron_tasks( $size );
		// update_option( 'listing_action_scheduler_c', count( $tasks ) );
		foreach ( (array) $tasks as $task ) {
			$args = \json_decode( $task['args'], true );
			$task['date_started'] = current_time( 'mysql', true );
			$task['status'] = 'doing';
			$task->save();
			do_action( $task['hook'], $args );
			// Check if is recurring task.
			if ( $task['recurrence'] ) {
				$timestamp_now = \current_time( 'timestamp', true ) + $this->get_time_limit() + 20;
				$task['status'] = 'pending';
				$task['date_scheduled'] = \date( 'Y-m-d H:i:s', $timestamp_now );
				$task['date_completed'] = current_time( 'mysql', true );
				// $task->save();
				// $this->add_task( $this->get_time_limit() + 20, $task['hook'], $args, $task['recurrence'] );
				// $task->delete();
			} else {
				$task['status'] = 'completed';
				$task['date_completed'] = current_time( 'mysql', true );
			}

			$task->save();

			$processed_actions++;
			if ( $this->batch_limits_exceeded( $processed_actions ) ) {
				// update_option( 'listing_action_scheduler_pa', $processed_actions );
				return $processed_actions; // or break in the loop.
			}
		}
		// update_option( 'listing_action_scheduler_end', $processed_actions );
		return $processed_actions;
	}

	/**
	 * See if the batch limits have been exceeded, which is when memory usage is almost at
	 * the maximum limit, or the time to process more actions will exceed the max time limit.
	 *
	 * @param int $processed_actions The number of actions processed so far - used to determine the likelihood of exceeding the time limit if processing another action.
	 * @return bool
	 */
	protected function batch_limits_exceeded( $processed_actions ) {
		return $this->memory_exceeded() || $this->time_likely_to_be_exceeded( $processed_actions );
	}

	public static function add_wp_cron_schedule( $schedules ) {
		$schedules['every_minute'] = array(
			'interval' => 60, // in seconds.
			'display'  => __( 'Every minute' ),
		);
		$schedules['twice_hourly'] = array(
			'interval' => 30 * 60, // in seconds.
			'display'  => __( 'Twice Hourly' ),
		);
		$schedules['every_5_minutes'] = array(
			'interval' => 60 * 5, // in seconds.
			'display'  => __( 'Every 5 minutes' ),
		);
		return $schedules;
	}

	protected function memory_exceeded() {
		$memory_limit    = $this->get_memory_limit() * 0.90;
		$current_memory  = memory_get_usage( true );
		$memory_exceeded = $current_memory >= $memory_limit;
		return $memory_exceeded;
	}

	protected function get_memory_limit() {
		if ( function_exists( 'ini_get' ) ) {
			$memory_limit = ini_get( 'memory_limit' );
		} else {
			$memory_limit = '128M'; // Sensible default.
		}

		if ( ! $memory_limit || -1 === $memory_limit || '-1' === $memory_limit ) {
			// Unlimited, set to 32GB.
			$memory_limit = '32G';
		}
		return wp_convert_hr_to_bytes( $memory_limit );
	}

	/**
	 * Check if the host's max execution time is (likely) to be exceeded if processing more actions.
	 *
	 * @param int $processed_actions The number of actions processed so far - used to determine the likelihood of exceeding the time limit if processing another action.
	 * @return bool
	 */
	protected function time_likely_to_be_exceeded( $processed_actions ) {

		$execution_time        = $this->get_execution_time();
		$max_execution_time    = $this->get_time_limit();
		$time_per_action       = $execution_time / $processed_actions;
		$estimated_time        = $execution_time + ( $time_per_action * 3 );
		$likely_to_be_exceeded = $estimated_time > $max_execution_time;

		return $likely_to_be_exceeded;
	}

	/**
	 * Get the number of seconds the process has been running.
	 *
	 * @return int The number of seconds.
	 */
	protected function get_execution_time() {
		$execution_time = microtime( true ) - $this->created_time;

		// Get the CPU time if the hosting environment uses it rather than wall-clock time to calculate a process's execution time.
		if ( function_exists( 'getrusage' ) && apply_filters( 'action_scheduler_use_cpu_execution_time', defined( 'PANTHEON_ENVIRONMENT' ) ) ) {
			$resource_usages = getrusage();

			if ( isset( $resource_usages['ru_stime.tv_usec'], $resource_usages['ru_stime.tv_usec'] ) ) {
				$execution_time = $resource_usages['ru_stime.tv_sec'] + ( $resource_usages['ru_stime.tv_usec'] / 1000000 );
			}
		}

		return $execution_time;
	}

	/**
	 * Get the maximum number of seconds a batch can run for.
	 *
	 * @return int The number of seconds.
	 */
	protected function get_time_limit() {
		$time_limit = 30;
		return absint( apply_filters( 'action_scheduler_queue_runner_time_limit', $time_limit ) );
	}
}
