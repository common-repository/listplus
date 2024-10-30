<?php
namespace ListPlus;

class Query {

	protected $main_query = null;
	protected $post = null;
	public $listings = null;
	protected $is_getting_lissting = null;
	public $query = null;
	protected $count = 0;

	/**
	 * Skip main query flag.
	 * Use for 3rd-party if they want to change the main query.
	 *
	 * @var boolean
	 */
	public $skip_main_query = false;

	public function __construct() {
	}

	public function is_submit_page() {
		if ( \is_page() ) {
			$page_id = ListPlus()->settings->get( 'submit_page' );
			if ( ! $page_id ) {
				return false;
			}
			return \is_page( $page_id );
		}
		return false;
	}

	public function is_single() {
		return \is_singular( 'listing' );
	}

	public function is_listing_archives( $tax = null ) {
		if ( \wp_doing_ajax() ) {
			return true;
		}
		if ( get_the_ID() == \ListPlus()->settings->get( 'listing_page' ) ) {
			return true;
		}
		if ( \is_singular( 'listing' ) ) {
			return false;
		}
		if ( ! \ListPlus()->request->current_router ) {
			return false;
		}
		if ( isset( \ListPlus()->request->current_router['is_single'] ) && \ListPlus()->request->current_router['is_single'] ) {
			return false;
		}
		return true;
	}


	public function get_data_for_single() {
		$data = [
			'listing' => \ListPlus\get_listing(),
		];
		return $data;
	}

	public function to_sql_in( $values ) {
		$sql = '';
		$vars = [];
		foreach ( (array) $values as $k => $v ) {
			if ( \is_numeric( $v ) ) {
				$vars[] = $v;
			} else {
				$v = \esc_sql( $v );
				if ( $v ) {
					$vars[] = "'{$v}'";
				}
			}
		}

		if ( ! empty( $vars ) ) {
			return ' (' . join( ', ', $vars ) . ') ';
		}
		return false;
	}


	public function is_tax( $taxonomy = null ) {
		if ( ! $taxonomy ) {
			$taxonomy = \get_query_var( 'l_tax' );
		}

		if ( ! $taxonomy ) {
			return false;
		}

		if ( ! \ListPlus()->taxonomies->is_listing_tax( $taxonomy ) ) {
			return false;
		}

		return true;
	}

	protected function parse_tax_query() {

		if ( ! $this->is_tax() ) {
			return null;
		}

		$terms = [];
		$terms[] = \get_query_var( 'term' );
		$taxonomy = \get_query_var( 'l_tax' );

		$tax_query = [
			// 'relation' => 'AND',
			[
				'taxonomy'      => $taxonomy,
				'field'         => 'slug',
				'terms'         => $terms,
				'operator'      => 'IN',
			],
		];

		$tax_query = new \WP_Tax_Query( $tax_query );
		return $tax_query->get_sql( 'p', 'ID' );
	}

	public function get_listings() {
		if ( ! $this->is_listing_archives() ) {
			$this->count = 0;
			return [];
		}

		global $wpdb;
		if ( ! is_null( $this->listings ) ) {
			return $this->listings;
		}

		if ( is_null( $this->query ) ) {
			$this->query = new \ListPlus\CRUD\Query( new \ListPlus\CRUD\Listing( [] ) );
			$this->query->set_searchable_fields( [] );
			$this->query->set_primary_key( 'ID' );
		}
		$this->query->reset();
		$this->skip_main_query = false;
		do_action_ref_array( 'before_get_listings', array( &$this ) );

		if ( ! $this->skip_main_query ) {
			$table_meta = \ListPlus\CRUD\Item_Meta::get_table();
			$status = [ 'publish', 'claimed' ];
			$paged = ListPlus()->request->get_paged();
			$per_page = ListPlus()->settings->get( 'listings_per_page', 25 );

			$this->query->table_as( 'p' )
				->select( 'DISTINCT( p.id )' )
				->select( 'p.*' )
				->select( 'lmt.*' )
				->where( 'p.post_type', 'listing' )
				->limit( $per_page )
				->page( $paged )
				->count_var( ' COUNT( DISTINCT( p.id ) ) as tt_rows' );

			$tq = $this->parse_tax_query();
			if ( $tq && $tq['join'] && $tq['where'] ) {
				$this->query->join_raw( $tq['join'] );
				$this->query->where_raw( $tq['where'] );
			}

			$this->query->left_join( $table_meta, '( p.ID = lmt.post_id )', 'lmt' )
				->where_in( 'p.post_status', $status )
				->order_by( 'p.post_date', 'DESC' );
		}

		$this->listings = $this->query->find();
		$this->count = $this->query->count();
		return $this->listings;

	}

	public function get_listings__backup() {
		global $wpdb;

		if ( is_null( $this->query ) ) {
			$this->query = new \ListPlus\CRUD\Query( new \ListPlus\CRUD\Listing( [] ) );
			$this->query->set_searchable_fields( [] );
			$this->query->set_primary_key( 'ID' );
		}
		$this->query->reset();
		$table_meta = \ListPlus\CRUD\Item_Meta::get_table();
		$status = [ 'publish', 'claimed' ];

		$paged = ListPlus()->request->get_paged();

		$this->query->table_as( 'p' )
			->select( 'DISTINCT( p.id )' )
			->select( 'p.*' )
			->select( 'lmt.*' )
			->limit( 4 )
			->page( $paged )
			->count_var( ' COUNT( DISTINCT( p.id ) ) as tt_rows' );

		$tq = $this->parse_tax_query();
		if ( $tq && $tq['join'] && $tq['where'] ) {
			$this->query->join_raw( $tq['join'] );
			$this->query->where_raw( $tq['where'] );
		}

		$this->query->left_join( $table_meta, '( p.ID = lmt.post_id )', 'lmt' )
		->where( 'p.post_type', 'listing' )
			->where_in( 'p.post_status', $status )
			->order_by( 'p.post_date', 'DESC' );

		$listings = $this->query->find();
		$this->count = $this->query->count();
		return $listings;

	}

	public function get_data_for_loop() {
		$listings = [];

		$data = [
			'listings' => $this->get_listings(),
			'post_count' => $this->count,
			'found_rows' => $this->query->count(),
			'total_pages' => $this->query->get_max_pages(),
			'request' => $this->query->request,
		];
		return $data;
	}

	public function get_data_for_map() {

		if ( empty( $this->listings ) ) {
			$this->get_listings();
		}

		$map_data = [];
		foreach ( (array) $this->listings as $listing ) {
			$map_data[] = [
				'title' => $listing->post_title,
				'url' => $listing->get_view_link(),
				'thumbnail_html' => get_the_post_thumbnail( $listing->get_id() ),
				'loc' => [
					'lat' => (float) $listing->lat,
					'lng' => (float) $listing->lng,
				],
			];
		}

		return $map_data;

	}








}
