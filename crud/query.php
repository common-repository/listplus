<?php

namespace ListPlus\CRUD;

/**
 * Progressively build up a query to get results using an easy to understand
 */
class Query {

	/**
	 * @var string
	 */
	const ORDER_ASCENDING = 'ASC';

	/**
	 * @var string
	 */
	const ORDER_DESCENDING = 'DESC';

	/**
	 * @var integer
	 */
	protected $page = 0;

	/**
	 * @var integer
	 */
	protected $limit = 0;

	/**
	 * @var integer
	 */
	protected $offset = 0;

	/**
	 * @var array
	 */
	protected $where = [];

	/**
	 * @var array
	 */
	protected $group = [];

	/**
	 * @var string
	 */
	protected $sort_by = [];

	/**
	 * @var string
	 */
	protected $order = 'ASC';

	/**
	 * @var string|null
	 */
	protected $search_term = null;

	/**
	 * @var array
	 */
	protected $search_fields = [];

	/**
	 * @var object
	 */
	protected $model = null;

	/**
	 * @var string
	 */
	protected $primary_key = null;

	/**
	 * @var array
	 */
	protected $select = array();
	protected $select_raw = array();
	protected $join = array();
	protected $table_as = null;
	protected $having = null;

	protected $cache = [];
	protected $cache_group = 'listing_query';

	public $count_var = null;
	public $table = null;

	/**
	 * Last Query
	 *
	 * @var string
	 */
	public $request = null;
	public $request_hash = null;
	public $found_rows = null;
	public $total_pages = null;
	public $rows = [];

	/**
	 * @param string $model
	 */
	public function __construct( $model = null ) {
		$this->model = $model;

		if ( $model ) {
			if ( \method_exists( $model, 'get_primary_key' ) ) {
				$this->primary_key = $model::get_primary_key();
			}
		}
	}

	/**
	 * Return the string representation of the query.
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->compose_query();
	}

	/**
	 * Set the fields to include in the search.
	 *
	 * @param  array $fields
	 */
	public function set_searchable_fields( array $fields ) {
		$this->search_fields = $fields;
	}

	/**
	 * Set the primary key column.
	 *
	 * @param string $primary_key
	 */
	public function set_primary_key( $primary_key ) {
		$this->primary_key = $primary_key;
	}

	/**
	 * Set count for count query.
	 *
	 * @param  string $field
	 * @return self
	 */
	public function count_var( $field ) {
		$this->count_var = $field;
		return $this;
	}

	/**
	 * Set the page for current query.
	 *
	 * @param  integer $page
	 * @return self
	 */
	public function page( $page ) {
		$this->page = (int) $page;
		return $this;
	}


	public function table_as( $as = null ) {
		$this->table_as = $as;
		return $this;
	}


	/**
	 * Set select fields
	 *
	 * @param string $field
	 * @param string $as
	 * @return self
	 */
	public function select( $field, $as = null ) {
		$this->select[ $field ] = $as;
		return $this;
	}

	/**
	 * Set select raw clause.
	 *
	 * @param string $string_select
	 * @return self
	 */
	public function select_raw( $string_select ) {
		$this->select_raw[] = $string_select;
		return $this;
	}

	/**
	 * Group fields
	 *
	 * @param string $field
	 * @param bool   $remove
	 * @return self
	 */
	public function group( $field, $remove = false ) {
		if ( $remove ) {
			unset( $this->group[ $field ] );
		} else {
			$this->group[ $field ] = $field;
		}
		return $this;
	}

	/**
	 * Set raw join
	 *
	 * @param string $sql
	 * @return self
	 */
	public function join_raw( $sql ) {
		$this->join[] = [
			'sql' => $sql,
			'type' => 'raw',
		];
		return $this;
	}

	/**
	 * Set left join
	 *
	 * @param string $table_name
	 * @param string $conditon
	 * @param string $table_as
	 * @return self
	 */
	public function left_join( $table_name, $conditon, $table_as = null ) {
		$this->join[] = [
			'table' => $table_name,
			'condition' => $conditon,
			'type' => 'left',
			'as' => $table_as,
		];
		return $this;
	}

	/**
	 * Set right join
	 *
	 * @param string $table_name
	 * @param string $conditon
	 * @param string $table_as
	 * @return self
	 */
	public function right_join( $table_name, $conditon, $table_as = null ) {
		$this->join[] = [
			'table' => $table_name,
			'condition' => $conditon,
			'type' => 'right',
			'as' => $table_as,
		];
		return $this;
	}

	/**
	 * Set inner join
	 *
	 * @param string $table_name
	 * @param string $conditon
	 * @param string $table_as
	 * @return self
	 */
	public function inner_join( $table_name, $conditon, $table_as = null ) {
		$this->join[] = [
			'table' => $table_name,
			'condition' => $conditon,
			'type' => 'inner',
			'as' => $table_as,
		];
		return $this;
	}



	/**
	 * Set the maximum number of results to return at once.
	 *
	 * @param  integer $limit
	 * @return self
	 */
	public function limit( $limit ) {
		$this->limit = (int) $limit;
		return $this;
	}

	/**
	 * Set the offset to use when calculating results.
	 *
	 * @param  integer $offset
	 * @return self
	 */
	public function offset( $offset ) {
		$this->offset = (int) $offset;
		return $this;
	}

	/**
	 * Set the column we should sort by.
	 *
	 * @param  string $by
	 * @param  string $order
	 * @return self
	 */
	public function sort_by( $by, $order = null ) {
		$this->sort_by[ $by ] = $order;
		return $this;
	}

	/**
	 * Set the column we should sort by.
	 *
	 * @param  string $by
	 * @param  string $order
	 * @return self
	 */
	public function order_by( $by, $order = null ) {
		return $this->sort_by( $by, $order );
	}

	/**
	 * Having clause.
	 *
	 * @param  string $clause
	 * @return self
	 */
	public function having( $clause ) {
		$this->having = $clause;
		return $this;
	}

	/**
	 * Add where raw
	 *
	 * @param  string $sql
	 * @return self
	 */
	public function where_raw( $sql ) {
		$this->where[] = array(
			'type' => 'raw',
			'sql' => $sql,
		);
		return $this;
	}

	/**
	 * Add a `=` clause to the search query.
	 *
	 * @param  string $column
	 * @param  string $value
	 * @return self
	 */
	public function where( $column, $value ) {
		$this->where[] = array(
			'type' => 'where',
			'column' => $column,
			'value' => $value,
		);
		return $this;
	}

	/**
	 * Add a `!=` clause to the search query.
	 *
	 * @param  string $column
	 * @param  string $value
	 * @return self
	 */
	public function where_not( $column, $value ) {
		$this->where[] = array(
			'type' => 'not',
			'column' => $column,
			'value' => $value,
		);
		return $this;
	}

	/**
	 * Add a `LIKE` clause to the search query.
	 *
	 * @param  string $column
	 * @param  string $value
	 * @return self
	 */
	public function where_like( $column, $value ) {
		$this->where[] = array(
			'type' => 'like',
			'column' => $column,
			'value' => $value,
		);
		return $this;
	}

	/**
	 * Add a `NOT LIKE` clause to the search query.
	 *
	 * @param  string $column
	 * @param  string $value
	 * @return self
	 */
	public function where_not_like( $column, $value ) {
		$this->where[] = array(
			'type' => 'not_like',
			'column' => $column,
			'value' => $value,
		);
		return $this;
	}

	/**
	 * Add a `<` clause to the search query.
	 *
	 * @param  string $column
	 * @param  string $value
	 * @return self
	 */
	public function where_lt( $column, $value ) {
		$this->where[] = array(
			'type' => 'lt',
			'column' => $column,
			'value' => $value,
		);
		return $this;
	}

	/**
	 * Add a `<=` clause to the search query.
	 *
	 * @param  string $column
	 * @param  string $value
	 * @return self
	 */
	public function where_lte( $column, $value ) {
		$this->where[] = array(
			'type' => 'lte',
			'column' => $column,
			'value' => $value,
		);
		return $this;
	}

	/**
	 * Add a `>` clause to the search query.
	 *
	 * @param  string $column
	 * @param  string $value
	 * @return self
	 */
	public function where_gt( $column, $value ) {
		$this->where[] = array(
			'type' => 'gt',
			'column' => $column,
			'value' => $value,
		);
		return $this;
	}

	/**
	 * Add a `>=` clause to the search query.
	 *
	 * @param  string $column
	 * @param  string $value
	 * @return self
	 */
	public function where_gte( $column, $value ) {
		$this->where[] = array(
			'type' => 'gte',
			'column' => $column,
			'value' => $value,
		);
		return $this;
	}

	/**
	 * Add an `IN` clause to the search query.
	 *
	 * @param  string $column
	 * @param  array  $in
	 * @return self
	 */
	public function where_in( $column, array $in ) {
		$this->where[] = array(
			'type' => 'in',
			'column' => $column,
			'value' => $in,
		);
		return $this;
	}

	/**
	 * Add a `NOT IN` clause to the search query.
	 *
	 * @param  string $column
	 * @param  array  $not_in
	 * @return self
	 */
	public function where_not_in( $column, array $not_in ) {
		$this->where[] = array(
			'type' => 'not_in',
			'column' => $column,
			'value' => $not_in,
		);
		return $this;
	}

	/**
	 * Add an OR statement to the where clause (e.g. (var = foo OR var = bar OR
	 * var = baz)).
	 *
	 * @param  array $where
	 * @return self
	 */
	public function where_any( array $where ) {
		$this->where[] = array(
			'type' => 'any',
			'where' => $where,
		);
		return $this;
	}

	/**
	 * Add an AND statement to the where clause (e.g. (var1 = foo AND var2 = bar
	 * AND var3 = baz)).
	 *
	 * @param  array $where
	 * @return self
	 */
	public function where_all( array $where ) {
		$this->where[] = array(
			'type' => 'all',
			'where' => $where,
		);

		return $this;
	}

	/**
	 * Get models where any of the designated fields match the given value.
	 *
	 * @param  string $search_term
	 * @return self
	 */
	public function search( $search_term ) {
		$this->search_term = $search_term;
		return $this;
	}

	/**
	 * Runs the same query as find, but with no limit and don't retrieve the
	 * results, just the total items found.
	 *
	 * @return integer
	 */
	public function total_count() {
		return $this->find( true );
	}

	/**
	 * Count number rows.
	 *
	 * @return int
	 */
	public function count() {
		$this->query();
		return (int) $this->found_rows;
	}

	/**
	 * Count number pages.
	 *
	 * @return int
	 */
	public function get_max_pages() {
		$total = $this->found_rows;
		if ( ! $total ) {
			return 0;
		}
		if ( $this->limit <= 0 ) {
			return 1;
		}
		$max_page = ceil( $total / $this->limit );
		return $max_page;
	}

	protected function query() {
		global $wpdb;

		$sql = $this->compose_query();
		$this->request = $sql;
		$hash = \md5( $sql );

		$cache = \wp_cache_get( $hash, $this->cache_group );
		if ( false !== $cache ) {
			$this->rows = $cache['rows'];
			$this->found_rows = $cache['found_rows'];
			return;
		}

		$is_listing = false;
		$model = $this->model;
		if ( $model && $model instanceof \ListPlus\CRUD\Listing ) {
			$is_listing = true;
		}
		$rows = [];
		$raw_rows = $wpdb->get_results( $sql, ARRAY_A ); // WPCS: db call ok, cache ok, unprepared SQL ok.
		$found_rows = $wpdb->get_var( 'SELECT FOUND_ROWS();' );
		if ( $model && $raw_rows ) {
			foreach ( $raw_rows as $index => $result ) {
				if ( $is_listing ) {
					$result['_skip_check'] = true;
				}
				$rows[] = $model::create( $result );
			}
		} else {
			$rows = ! empty( $raw_rows ) ? $raw_rows : [];
		}

		$this->rows = $rows;
		$this->found_rows = $found_rows;

		\wp_cache_set(
			$hash,
			[
				'rows' => $this->rows,
				'found_rows' => $this->found_rows,
			],
			$this->cache_group
		);

	}

	/**
	 * Compose & execute our query.
	 *
	 * @return array|null
	 */
	public function find() {
		$this->query();
		return $this->rows;
	}

	public function reset() {
		$this->page         = 0;
		$this->limit        = 0;
		$this->offset       = 0;
		$this->select       = [];
		$this->select_raw   = [];
		$this->where        = [];
		$this->join         = [];
		$this->sort_by      = [];
		$this->order        = '';
		$this->search_term  = null;
		$this->table_as     = null;
		$this->having       = null;
		$this->group        = [];
		$this->cache        = [];

		$this->request      = null;
		$this->request_hash = null;
		$this->found_rows   = null;
		$this->total_pages  = null;
		$this->rows         = [];
	}

	protected function sanitize_column( $column ) {
		if ( \strpos( $column, '(' ) ) {
			return $column;
		}
		if ( \strpos( $column, '.' ) ) {
			return $column;
		}
		$column = \str_replace( '`', '', $column );
		$column_sql = '';
		if ( \strpos( $column, '.' ) ) {
			$arr = explode( '.', $column );
			return " `{$arr[0]}`.`{$arr[1]}` ";
		}
		return "`{$column}`";
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


	/**
	 * Compose the actual SQL query from all of our filters and options.
	 *
	 * @return string
	 */
	public function compose_query() {
		if ( $this->model ) {
			$model  = $this->model;
			$table  = $model::get_table();
		} else {
			$table  = $this->table;
		}

		$where  = '';
		$order  = '';
		$limit  = '';
		$offset = '';
		global $wpdb;

		// Search.
		if ( ! empty( $this->search_term ) ) {
			$where .= ' AND (';

			foreach ( $this->search_fields as $field ) {
				$field = $this->sanitize_column( $field );
				$where .= " {$field } LIKE '%" . esc_sql( $this->search_term ) . "%' OR ";
			}

			$where = substr( $where, 0, -4 ) . ')';
		}

		// Where.
		foreach ( $this->where as $q ) {

			$column = isset( $q['column'] ) ? $this->sanitize_column( $q['column'] ) : '';

			switch ( $q['type'] ) {
				case 'where':
					$placeholder = ' %s';
					if ( \is_numeric( $q['value'] ) ) {
						$placeholder = '%d';
					}
					$where .= $wpdb->prepare( " AND {$column} = {$placeholder} ", $q['value'] );
					break;
				case 'not': // where_not.
					$where .= ' AND ' . $column . ' != "' . esc_sql( $q['value'] ) . '"';
					break;

				case 'like':    // where_like.
					$where .= ' AND ' . $column . ' LIKE \'' . esc_sql( $q['value'] ) . '\'';
					break;

				case 'not_like': // where_not_like.
					$where .= ' AND ' . $column . ' NOT LIKE \'' . esc_sql( $q['value'] ) . '\'';
					break;

				case 'lt':// where_lt.
					$where .= ' AND ' . $column . ' < "' . esc_sql( $q['value'] ) . '"';
					break;
				case 'lte':// where_lte.
					$where .= ' AND ' . $column . ' <= "' . esc_sql( $q['value'] ) . '"';
					break;
				case 'gt':// where_gt.
					$where .= ' AND ' . $column . ' > "' . esc_sql( $q['value'] ) . '"';
					break;
				case 'gte':// where_gte.
					$where .= ' AND ' . $column . ' >= "' . esc_sql( $q['value'] ) . '"';
					break;
				case 'in':// where_in.
					$in_value = $this->to_sql_in( $q['value'] );
					if ( $in_value ) {
						$where .= ' AND ' . $column . ' IN ' . $in_value;
					}
					break;
				case 'not_in':
					$in_value = $this->to_sql_in( $q['value'] );
					if ( $in_value ) {
						$where .= ' AND ' . $column . ' NOT IN ' . $in_value;
					}
					break;
				case 'any':// where_any.
					$where .= ' AND (';

					$subs = [];
					foreach ( $q['where'] as $c => $value ) {
						$c = $this->sanitize_column( $c );
						$subs[] = '' . $c . ' = "' . esc_sql( $value ) . '" ';
					}

					$where .= join( ' OR ', $subs );

					$where .= ')';
					break;
				case 'all':// where_all.
					$where .= ' AND (';

					$subs = [];
					foreach ( $q['where'] as $c => $value ) {
						$c = $this->sanitize_column( $c );
						$subs[] = '' . $c . ' = "' . esc_sql( $value ) . '" ';
					}

					$where .= join( ' AND ', $subs );

					$where = substr( $where, 0, -5 ) . ')';
					break;
				case 'raw':
					$where .= $q['sql'];
					break;

			}
		}

		// Finish where clause.
		if ( ! empty( $where ) ) {
			$where = ' WHERE ' . substr( $where, 5 );
		}

		// Order.
		if ( $this->sort_by && ! empty( $this->sort_by ) ) {
			$order = [];
			foreach ( $this->sort_by as $by => $sort ) {
				if ( $sort ) {
					$by = $this->sanitize_column( $by );
					$order[] = " {$by} {$sort} ";
				}
			}
			$order = join( ', ', $order );
			if ( $order ) {
				$order = " ORDER BY {$order} ";
			}
		}

		if ( $this->limit > 0 ) {
			if ( $this->page <= 0 ) {
				$this->page = 1;
			}
			$start_at = $this->limit * ( $this->page - 1 );
			$limit = " LIMIT {$start_at}, {$this->limit} ";
		} else {
			// Limit.
			if ( $this->limit > 0 ) {
				$limit = ' LIMIT ' . $this->limit;
			}
		}

		// Offset.
		if ( $this->offset > 0 ) {
			$offset = ' OFFSET ' . $this->offset;
		}

		$join = '';
		if ( ! empty( $this->join ) ) {
			foreach ( $this->join as $tbn => $jargs ) {
				$as = isset( $jargs['as'] ) && $jargs['as'] ? " AS {$jargs['as']}  " : '';
				$table_name = isset( $jargs['table'] ) && $jargs['table'] ? $jargs['table'] : '';
				switch ( strtolower( $jargs['type'] ) ) {
					case 'left':
						$join .= " LEFT JOIN {$table_name} {$as} ON {$jargs['condition']} ";
						break;
					case 'right':
						$join .= " RIGHT JOIN {$table_name} {$as} ON {$jargs['condition']} ";
						break;
					case 'inner':
						$join .= " INNER JOIN {$table_name} {$as} ON {$jargs['condition']} ";
						break;
					case 'raw':
						$join .= $jargs['sql'];
						break;
				}
			}
		}

		$table_as = ( $this->table_as ) ? " AS `{$this->table_as}`  " : '';

		// Group.
		$group = '';
		if ( ! empty( $this->group ) ) {
			$group = [];
			foreach ( $this->group as $f ) {
				$f = $this->sanitize_column( $f );
				$group[] = $f;
			}
			$group = ' GROUP BY ' . join( ', ', $group ) . ' ';
		}

		// Having.
		$having = '';
		if ( ! empty( $this->having ) ) {
			$having = " HAVING {$this->having} ";
		}

		$select_fields = [];
		if ( ! empty( $this->select ) ) {
			foreach ( $this->select as $f => $a ) {
				$f = $this->sanitize_column( $f );
				if ( $a ) {
					$select_fields[] = " {$f} as {$a} ";
				} else {
					$select_fields[] = " {$f}";
				}
			}
		}

		if ( ! empty( $this->select_raw ) ) {
			foreach ( $this->select_raw as $sr ) {
				$select_fields[] = " {$sr} ";
			}
		}

		if ( empty( $select_fields ) ) {
			$select_fields = ' * ';
		} else {
			$select_fields = join( ', ', $select_fields );
		}

		return apply_filters( 'listing_query', "SELECT SQL_CALC_FOUND_ROWS {$select_fields} FROM `{$table}`{$table_as}{$join}{$where}{$group}{$having}{$order}{$limit}{$offset}", $this->model );
	}
}
