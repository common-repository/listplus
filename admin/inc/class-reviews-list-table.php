<?php
namespace ListPlus\Admin;

use ListPlus\Admin\Helper;
use ListPlus\CRUD\Review;
use ListPlus\CRUD\Listing;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Reviews_List_Table extends \WP_List_Table {

	protected $table = '';

	public function __construct() {
		// Set parent defaults.
		parent::__construct(
			array(
				'singular' => __( 'Review', 'list-plus' ),     // Singular name of the listed records.
				'plural'   => __( 'Reviews', 'list-plus' ),    // Plural name of the listed records.
				'ajax'     => false, // Does this table support ajax?
			)
		);
		global $wpdb;
		$this->table = Review::get_table();
	}

	public function get_columns() {
		$columns = array(
			'cb'       => '<input type="checkbox" />', // Render a checkbox instead of text.
			'title'    => _x( 'Listing name', 'Column label', 'list-plus' ),
			'rating'    => _x( 'Rating', 'Column label', 'list-plus' ),
			'status'    => _x( 'Status', 'Column label', 'list-plus' ),
			'email'    => _x( 'By', 'Column label', 'list-plus' ),
			'content'   => _x( 'Review', 'Column label', 'list-plus' ),
			'created_at'   => _x( 'Created at', 'Column label', 'list-plus' ),
		);
		return $columns;
	}


	protected function get_sortable_columns() {
		$sortable_columns = array(
			'rating'  => array( 'rating', false ),
			'created_at'  => array( 'created_at', false ),
		);

		return $sortable_columns;
	}


	protected function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'rating':
				$max = $item['max'];
				return \sprintf( '<span class="dashicons dashicons-star-filled"></span> <strong>%s</strong>/%d', number_format_i18n( $item->rating, 1 ), $max );
			case 'created_at':
				$diff = human_time_diff( \strtotime( $item[ $column_name ] ), current_time( 'timestamp' ) );
				return sprintf( __( '%s ago', 'list-plus' ), $diff ) . '<br/><em>' . esc_html( $item[ $column_name ] ) . '</em>';
			break;
			default:
		}
	}


	protected function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			'ids',  // Let's simply repurpose the table's singular label ("movie").
			$item->get_id() // The value of the checkbox should be the record's ID.
		);
	}

	protected function column_status( $item ) {
		switch ( $item['status'] ) {
			case 'approved':
				return '<span title="' . __( 'Approved', 'list-plus' ) . '" class="color-approved dashicons dashicons-yes-alt"></span>' . __( 'Approved', 'list-plus' );
			case 'pending':
				return '<span title="' . __( 'Pending', 'list-plus' ) . '" class="color-pending dashicons dashicons-warning"></span>' . __( 'Pending', 'list-plus' );
			case 'rejected':
				return '<span title="' . __( 'Rejected', 'list-plus' ) . '" class="color-rejected dashicons dashicons-dismiss"></span>' . __( 'Rejected', 'list-plus' );

			default:
				return esc_html( $item['status'] );

		}
	}


	protected function column_content( $item ) {
		$html = '';
		if ( $item['title'] ) {
			$html .= '<p><strong>' . esc_html( $item['title'] ) . '</strong></p>';
		}

		$html .= esc_html( wp_trim_words( $item['content'], 30 ) );
		return $html;
	}

	protected function column_email( $item ) {
		$user = \get_user_by( 'email', $item['email'] );
		$name = $item['name'];
		if ( $user ) {
			$link = \admin_url( 'user-edit.php?user_id=' . $user->ID );
			if ( $user->display_name ) {
				$name = $user->display_name;
			} else {
				$name = $user->user_login;
			}
			$name = '<a target="_blank" href="' . esc_url( $link ) . '">' . $name . '</a>';
		}

		return sprintf( '<strong>%s</strong> <br/> %s ', $name, esc_html( $item['email'] ) );
	}

	protected function column_title( $item ) {
		$page = isset( $_REQUEST['page'] ) ? wp_unslash( $_REQUEST['page'] ) : ''; // WPCS: Input var ok.
		$post_type = isset( $_REQUEST['post_type'] ) ? wp_unslash( $_REQUEST['post_type'] ) : ''; // WPCS: Input var ok.

		// Build edit row action.
		$view_query_args = array(
			'page'   => $page,
			'post_type' => $post_type,
			'view' => 'review-details',
			'id'  => $item['id'],
			'_nonce' => '',
		);

		$actions['id'] = sprintf(
			'<span style="color: #666; font-weight: bold;">#%1$s</span>',
			$item->get_id()
		);

		$view_link = esc_url( add_query_arg( $view_query_args, 'edit.php' ) );
		$actions['view'] = sprintf(
			'<a href="%1$s">%2$s</a>',
			$view_link,
			_x( 'View', 'List table row action', 'list-plus' )
		);

		// Build delete row action.
		$delete_query_args = array(
			'page'   => $page,
			'post_type' => $post_type,
			'action' => 'delete',
			'id'  => $item['id'],
		);

		$actions['delete'] = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( add_query_arg( $delete_query_args, 'edit.php' ) ),
			_x( 'Delete', 'List table row action', 'list-plus' )
		);

		$listing = new Listing( $item['post_id'] );

		$title = $listing->get_name();
		$title = '<a href="' . $view_link . '">' . esc_html( $title ) . '</a>';

		// Return the title contents.
		return sprintf(
			'<strong>%1$s</strong>%2$s',
			$title,
			$this->row_actions( $actions )
		);
	}


	protected function get_bulk_actions() {
		$actions = array(
			'approved' => _x( 'Approved reviews', 'List table bulk action', 'list-plus' ),
			'pending' => _x( 'Pending reviews', 'List table bulk action', 'list-plus' ),
			'rejected' => _x( 'Rejected reviews', 'List table bulk action', 'list-plus' ),
			'delete' => _x( 'Delete', 'List table bulk action', 'list-plus' ),
		);
		return $actions;
	}


	public function get_query_args( $name ) {
		if ( isset( $_REQUEST[ $name ] ) ) {
			return sanitize_text_field( $_REQUEST[ $name ] );
		}
		return null;
	}


	public function process_bulk_action() {

		$action = $this->current_action();
		if ( ! $action ) {
			return;
		}

		$ids = isset( $_POST['ids'] ) ? $_POST['ids'] : null;
		if ( is_null( $ids ) ) {
			$ids = isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : null;
			if ( $ids ) {
				$ids = [ $ids ];
			}
		}

		if ( ! \is_array( $ids ) || empty( $ids ) ) {
			return;
		}

		$ids = array_map( 'absint', $ids );
		$ids = array_unique( $ids );

		global $wpdb;

		$in = ' (' . join( ', ', $ids ) . ') ';
		$sql = false;
		$message = '';
		$n = count( $ids );
		switch ( $action ) {
			case 'approved':
				$sql = "UPDATE {$this->table} SET `status`= 'approved' WHERE id IN {$in}";
				$message = sprintf( __( '%d item(s) updated.', 'list-plus' ), $n );
				break;
			case 'pending':
				$sql = "UPDATE {$this->table} SET `status`= 'pending' WHERE id IN {$in}";
				$message = sprintf( __( '%d item(s) updated.', 'list-plus' ), $n );
				break;
			case 'rejected':
					$sql = "UPDATE {$this->table} SET `status`= 'rejected' WHERE id IN {$in}";
					$message = sprintf( __( '%d item(s) updated.', 'list-plus' ), $n );
				break;
			case 'delete':
				$sql = "DELETE FROM {$this->table} WHERE id IN {$in}";
				$message = sprintf( __( '%d item(s) deleted.', 'list-plus' ), $n );
				break;
		}

		if ( $sql ) {
			$wpdb->query( $sql ); // phpcs:ignore
			$posts_ids = $wpdb->get_results( "SELECT distinct( post_id ) as pid FROM {$this->table} WHERE id IN {$in}", ARRAY_A );
			if ( $posts_ids ) {
				foreach ( $posts_ids as $row ) {
					\ListPlus()->cron->add_review_task( $row['pid'] );
				}
			}

			?>
			<div class="notice notice-success is-dismissible">
				<p><?php echo $message; // WPCS: XSS ok. ?></p>
			</div>
			<?php
		}

	}

	protected function get_edit_link( $args, $label, $class = '' ) {
		$url = add_query_arg( $args, 'edit.php' );

		$class_html   = '';
		$aria_current = '';
		if ( ! empty( $class ) ) {
			$class_html = sprintf(
				' class="%s"',
				esc_attr( $class )
			);

			if ( 'current' === $class ) {
				$aria_current = ' aria-current="page"';
			}
		}

		return sprintf(
			'<a href="%s"%s%s>%s</a>',
			esc_url( $url ),
			$class_html,
			$aria_current,
			$label
		);
	}

	/**
	 * Get an associative array ( id => link ) with the list
	 * of views available on this table.
	 *
	 * @since 3.1.0
	 *
	 * @return array
	 */
	protected function get_views() {
		$views = [];
		global $wpdb;
		$status = [
			'all' => __( 'All', 'list-plus' ),
			'approved' => __( 'Approved', 'list-plus' ),
			'pending' => __( 'Pending', 'list-plus' ),
			'rejected' => __( 'Rejected', 'list-plus' ),
		];

		$current_status = isset( $_REQUEST['status'] ) ? $_REQUEST['status'] : 'all';
		if ( ! $current_status || ! isset( $status[ $current_status ] ) ) {
			$current_status = 'all';
		}

		$listing_id = (int) $this->get_query_args( 'listing_id' );

		foreach ( $status as $k => $label ) {

			if ( 'all' != $k ) {
				Review::query()->where( 'status', $k );
			}

			if ( $listing_id ) {
				Review::query()->where( 'post_id', $listing_id );
			}

			$n = Review::query()->count();

			$class = '';
			if ( $current_status == $k ) {
				$class = 'current';
			}

			$inner_html = $label . sprintf( '<span class="count">(%s)</span>', number_format_i18n( $n ) );
			$args = [
				'post_type' => 'listing',
				'page' => 'listing_reviews',
				'status' => $k,
			];
			$views[ $k ] = $this->get_edit_link( $args, $inner_html, $class );

		} // End loop status.

		return $views;
	}


	public function prepare_items() {
		global $wpdb; // This is used only if making any database queries.

		$this->process_bulk_action();

		$current_page = $this->get_pagenum();
		$listing_id = $this->get_query_args( 'listing_id' );
		$status = $this->get_query_args( 'status' );
		$orderby = $this->get_query_args( 'orderby' );
		$order = $this->get_query_args( 'order' );
		$order = \strtolower( $order );
		if ( ! $order || 'asc' != $order ) {
			$order = 'desc';
		}

		if ( $orderby ) {
			$allow_orders = $this->get_sortable_columns();
			if ( ! isset( $allow_orders[ $orderby ] ) ) {
				$orderby = '';
			}
		}

		if ( ! $orderby ) {
			$orderby = 'id';
			$order = 'desc';
		}

		if ( 'all' == $status ) {
			$status = null;
		}

		/*
		 * First, lets decide how many records per page to show.
		 */
		$per_page = 30;

		$start_at = $per_page * ( $current_page - 1 );

		/*
		 * REQUIRED. Now we need to define our column headers. This includes a complete
		 * array of columns to be displayed (slugs & titles), a list of columns
		 * to keep hidden, and a list of columns that are sortable. Each of these
		 * can be defined in another method (as we've done here) before being
		 * used to build the value for our _column_headers property.
		 */
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		/*
		 * REQUIRED. Finally, we build an array to be used by the class for column
		 * headers. The $this->_column_headers property takes an array which contains
		 * three other arrays. One for all columns, one for hidden columns, and one
		 * for sortable columns.
		 */
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->items = [];

		$where = '';
		if ( $listing_id ) {
			Review::query()->where( 'post_id', $listing_id );
		}
		if ( $status ) {
			Review::query()->where( 'status', $status );
		}

		Review::query()->limit( $per_page )->page( $current_page );

		if ( $orderby ) {
			Review::query()->order_by( $orderby, $order );
		}

		$this->items = Review::query()->find(); // WPCS: unprepared SQL ok, db call ok.
		$total_items = Review::query()->count();
		$total_pages = Review::query()->get_max_pages();

		/**
		 * REQUIRED. We also have to register our pagination options & calculations.
		 */
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,                     // WE have to calculate the total number of items.
				'per_page'    => $per_page,                        // WE have to determine how many items to show on a page.
				'total_pages' => $total_pages, // WE have to calculate the total number of pages.
			)
		);
	}

	/**
	 * Callback to allow sorting of example data.
	 *
	 * @param string $a First value.
	 * @param string $b Second value.
	 *
	 * @return int
	 */
	protected function usort_reorder( $a, $b ) {
		// If no sort, default to title.
		$orderby = ! empty( $_REQUEST['orderby'] ) ? wp_unslash( $_REQUEST['orderby'] ) : 'title'; // WPCS: Input var ok.

		// If no order, default to asc.
		$order = ! empty( $_REQUEST['order'] ) ? wp_unslash( $_REQUEST['order'] ) : 'asc'; // WPCS: Input var ok.

		// Determine sort order.
		$result = strcmp( $a[ $orderby ], $b[ $orderby ] );

		return ( 'asc' === $order ) ? $result : - $result;
	}
}
