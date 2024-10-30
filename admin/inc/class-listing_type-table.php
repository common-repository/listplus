<?php
namespace ListPlus\Admin;

use ListPlus\CRUD\Listing_Type;
use ListPlus\Admin\Helper;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Listing_Type_Table extends \WP_List_Table {

	private $taxonomy = 'listing_type';

	public function __construct() {
		// Set parent defaults.
		parent::__construct(
			array(
				'singular' => __( 'Listing Type', 'list-plus' ),     // Singular name of the listed records.
				'plural'   => __( 'Listing Types', 'list-plus' ),    // Plural name of the listed records.
				'ajax'     => false,       // Does this table support ajax?
			)
		);
	}

	public function get_columns() {
		$columns = array(
			'cb'          => '<input type = "checkbox" />', // Render a checkbox instead of text.
			'thumb'       => '<span class="dashicons dashicons-format-image"></span>',
			'title'       => _x( 'Name', 'Column label', 'list-plus' ),
			'term_id'     => _x( 'ID', 'Column label', 'list-plus' ),
			'slug'        => _x( 'Slug', 'Column label', 'list-plus' ),
			'status'      => _x( 'Status', 'Column label', 'list-plus' ),
			'count'       => _x( 'Count', 'Column label', 'list-plus' ),
			'description' => _x( 'Description', 'Column label', 'list-plus' ),
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
			case 'thumb':
				$icon  = get_term_meta( $item->get_id(), '_icon', true );
				$image  = get_term_meta( $item->get_id(), '_image', true );
				if ( $icon ) {
					echo '<span class="ls-svg-icon">' . \ListPlus()->icons->the_icon_svg( $icon ) . '</span>';
				} elseif ( $image && $src = wp_get_attachment_thumb_url( $image ) ) {
					echo '<img src="' . esc_url( $src ) . '" alt="">';
				} else {
					echo '<span class="ls-placeholder-img"><span class="dashicons dashicons-format-image"></span></span>';
				}
				break;
			case 'term_id':
			case 'count':
			case 'description':
			case 'slug':
			case 'status':
				return esc_html( $item[ $column_name ] );
			default:
				// return print_r( $item, true ); // Show the whole array for troubleshooting purposes.
		}
	}


	protected function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			'ids',  // Let's simply repurpose the table's singular label ("movie").
			$item->get_id()                // The value of the checkbox should be the record's ID.
		);
	}

	protected function column_title( $item ) {
		$page = wp_unslash( $_REQUEST['page'] ); // WPCS: Input var ok.
		// Build edit row action.
		$edit_query_args = array(
			'page'      => 'listing_types',
			'post_type' => 'listing',
			'view'      => 'edit-type',
			'id'        => $item->get_id(),
			'_nonce'    => $item->nonce(),
		);

		$edit_link = esc_url( add_query_arg( $edit_query_args, 'edit.php' ) );

		$actions['edit'] = sprintf(
			'<a href="%1$s">%2$s</a>',
			$edit_link,
			_x( 'Edit', 'List table row action', 'list-plus' )
		);

		// Build delete row action.
		$delete_query_args = array(
			'page'      => 'listing_types',
			'post_type' => 'listing',
			'action'    => 'delete',
			'id'        => $item->get_id(),
			'_nonce'    => $item->nonce(),
		);

		$actions['delete'] = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( add_query_arg( $delete_query_args, 'edit.php' ) ),
			_x( 'Delete', 'List table row action', 'list-plus' )
		);

		// $view_link = ListPlus()->request->to_url( 'archives_type', [ 'listing_type' => $item->get_slug() ] );
		$view_link = \ListPlus()->request->get_term_link( $item->get_id() );

		$actions['view'] = sprintf(
			'<a target="_blank" href="%1$s">%2$s</a>',
			esc_url( $view_link ),
			_x( 'View', 'List table row action', 'list-plus' )
		);

		// Return the title contents.
		return sprintf(
			'<a class="row-title" href="%1$s">%2$s</a> %3$s',
			$edit_link,
			$item['name'],
			$this->row_actions( $actions )
		);
	}

	protected function get_bulk_actions() {
		$actions = array(
			'duplicate' => _x( 'Duplicate', 'List table bulk action', 'list-plus' ),
			'delete' => _x( 'Delete', 'List table bulk action', 'list-plus' ),
		);

		return $actions;
	}


	protected function do_bulk_actions() {
		switch ( $this->current_action() ) {
			case 'delete':
				if ( isset( $_REQUEST['ids'] ) ) {
					$delete_ids = $_REQUEST['ids'];
					foreach ( (array) $delete_ids as $id ) {
						Listing_Type::delete_by_id( $id );
					}
				}

				if ( isset( $_GET['id'] ) && $_GET['id'] ) {
					Listing_Type::delete_by_id( $_GET['id'] );
				}
				break;
			case 'duplicate':
				if ( isset( $_REQUEST['ids'] ) ) {
					foreach ( (array) $_REQUEST['ids'] as $id ) {
						$type = new Listing_Type( (int) $id );
						if ( $type->get_id() ) {
							$type['term_id'] = null;
							$type['slug'] = null;
							$type['name'] = $type['name'] . ' (copy)';
							$type->save();
						}
					}
				}
				break;
		}
		// Detect when a bulk action is being triggered.
		if ( 'delete' === $this->current_action() ) {

			// die();
		}
	}


	public function prepare_items() {
		global $wpdb; // This is used only if making any database queries.

		$this->do_bulk_actions();

		/*
		 * First, lets decide how many records per page to show
		 */
		$per_page = 9999999;

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

		/*
		 * REQUIRED. Now we can add our *sorted* data to the items property, where
		 * it can be used by the rest of the class.
		 */

		$get_data = Listing_Type::get_all();
		$this->items = $get_data['items'];

		/**
		 * REQUIRED. We also have to register our pagination options & calculations.
		 */
		$this->set_pagination_args(
			array(
				'total_items' => $get_data['found_posts'],                     // WE have to calculate the total number of items.
				'per_page'    => $per_page,                        // WE have to determine how many items to show on a page.
				'total_pages' => $get_data['total_pages'], // WE have to calculate the total number of pages.
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
