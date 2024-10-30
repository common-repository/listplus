<?php
namespace ListPlus\Admin;

use ListPlus\CRUD\Listing;
use ListPlus\Admin\Helper;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Listing_Table extends \WP_List_Table {

	private $post_type = 'listing';

	public function __construct() {
		// Set parent defaults.
		parent::__construct(
			array(
				'singular' => __( 'Listing', 'list-plus' ),     // Singular name of the listed records.
				'plural'   => __( 'Listings', 'list-plus' ),    // Plural name of the listed records.
				'ajax'     => false, // Does this table support ajax?
			)
		);
	}

	public function get_columns() {
		$columns = array(
			'cb'       => '<input type="checkbox" />', // Render a checkbox instead of text.
			'title'    => _x( 'Name', 'Column label', 'list-plus' ),
			'slug'    => _x( 'Slug', 'Column label', 'list-plus' ),
			'count'   => _x( 'Count', 'Column label', 'list-plus' ),
			'description'   => _x( 'Description', 'Column label', 'list-plus' ),
		);
		return $columns;
	}


	protected function get_sortable_columns() {
		$sortable_columns = array(
			'title'    => array( 'title', false ),
			'count'   => array( 'rating', false ),
		);

		return $sortable_columns;
	}


	protected function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'count':
			case 'slug':
			case 'description':
				return esc_html( $item[ $column_name ] );
			default:
				// return print_r( $item, true ); // Show the whole array for troubleshooting purposes.
		}
	}


	protected function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			$this->_args['singular'],  // Let's simply repurpose the table's singular label ("movie").
			$item['ID']                // The value of the checkbox should be the record's ID.
		);
	}


	protected function column_title( $item ) {
		$page = wp_unslash( $_REQUEST['page'] ); // WPCS: Input var ok.

		// Build edit row action.
		$edit_query_args = array(
			'page'   => $page,
			'view' => 'edit-listing',
			'id'  => $item->get_id(),
			'_nonce' => $item->nonce(),
		);

		$actions['edit'] = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( add_query_arg( $edit_query_args, 'admin.php' ) ),
			_x( 'Edit', 'List table row action', 'list-plus' )
		);

		// Build delete row action.
		$delete_query_args = array(
			'page'   => $page,
			'action' => 'delete',
			'id'  => $item->get_id(),
		);

		$actions['delete'] = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( add_query_arg( $delete_query_args, 'admin.php' ) ),
			_x( 'Delete', 'List table row action', 'list-plus' )
		);

		// Return the title contents.
		return sprintf(
			'%1$s <span style="color:silver;">(id:%2$s)</span>%3$s',
			$item['post_title'],
			$item->get_id(),
			$this->row_actions( $actions )
		);
	}


	protected function get_bulk_actions() {
		$actions = array(
			'delete' => _x( 'Delete', 'List table bulk action', 'list-plus' ),
		);

		return $actions;
	}


	public function prepare_items() {
		global $wpdb; // This is used only if making any database queries.

		/*
		 * First, lets decide how many records per page to show
		 */
		$per_page = 5;

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

		$sql = " FROM {$wpdb->prefix}lp_item_meta AS im LEFT JOIN {$wpdb->posts} as p ON ( im.post_id  = p.ID ) ";
		$sql_count = "SELECT count(im.post_id) as total {$sql}";
		$sql_get = "SELECT * {$sql} LIMIT 100";

		$rows = $wpdb->get_results( $sql_get, ARRAY_A );
		//$rows = $wpdb->get_results( $wpdb->prepare( $sql_get, $this->post_type ), ARRAY_A );
		foreach ( $rows as $row ) {
			$this->items[] = new Listing( $row );
		}

		//$total_items = $wpdb->get_var( $wpdb->prepare( $sql_count, $this->post_type ) );
		$total_items = $wpdb->get_var( $sql_count );
		$total_page = ceil( $total_items / $per_page );

		/**
		 * REQUIRED. We also have to register our pagination options & calculations.
		 */
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,                     // WE have to calculate the total number of items.
				'per_page'    => $per_page,                        // WE have to determine how many items to show on a page.
				'total_pages' => 1, // WE have to calculate the total number of pages.
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
