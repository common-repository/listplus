<?php

namespace ListPlus\CRUD;

/**
 * Base class for building models.
 */
class Model implements \ArrayAccess {

	protected $data = array();
	static protected $fields = [];
	static protected $query = null;
	private static $_names = array();

	/**
	 * Constructor.
	 *
	 * @param array $properties
	 */
	public function __construct( array $properties = array() ) {
		$this->data = $properties;
	}

	public function __set( $name, $value ) {
		$this->data[ $name ] = $value;
	}

	public function __get( $name ) {
		if ( array_key_exists( $name, $this->data ) ) {
			return $this->data[ $name ];
		}
		return null;
	}

	public function __call( $name, $arguments ) {
		if ( 'get_' == \substr( $name, 0, 4 ) ) {
			$key = \substr( $name, 4 );
			if ( isset( $this->data[ $key ] ) ) {
				return $this->data[ $key ];
			}
			return ull;
		}
	}

	/**
	 * Get the column used as the primary key, defaults to 'id'.
	 *
	 * @return string
	 */
	public static function get_primary_key() {
		return 'ID';
	}

	/**
	 * Overwrite this in your concrete class. Returns the table name used to
	 * store models of this class.
	 *
	 * @return string
	 */
	public static function get_table() {
		global $wpdb;
		return $wpdb->prefix . 'posts';
	}

	/**
	 * Get an array of fields to search during a search query.
	 *
	 * @return array
	 */
	public static function get_searchable_fields() {
		return array();
	}


	/**
	 * Return the value of the primary key.
	 *
	 * @return integer
	 */
	public function primary_key() {
		return static::get_primary_key();
	}

	public static function get_default_fields() {
		$fields = [];
		if ( property_exists( \get_called_class(), 'fields' ) ) {
			foreach ( static::$fields as $f ) {
				$fields[ $f ] = null;
			}
		}
		return $fields;
	}

	public function get_id() {
		$key = static::get_primary_key();
		if ( isset( $this->data[ $key ] ) ) {
			return $this->data[ $key ];
		}
		return null;
	}


	/**
	 * Save this model to the database. Will create a new record if the ID
	 * property isn't set, or update an existing record if the ID property is
	 * set.
	 *
	 * @return self
	 */
	public function save() {
		global $wpdb;
		$primary_key = static::get_primary_key();

		if ( \method_exists( $this, 'prepare_data' ) ) {
			$this->data = $this->prepare_data();
		}

		// Allow only data in the field list if set.
		if ( is_array( static::$fields ) && ! empty( static::$fields ) ) {
			$allow_data = [];
			foreach ( static::$fields as $k ) {
				if ( isset( $this->data[ $k ] ) ) {
					$allow_data[ $k ] = $this->data[ $k ];
				}
			}

			$this->data = $allow_data;
		}

		foreach ( $this->data as $k => $v ) {
			if ( \is_array( $v ) ) {
				$this->data[ $k ] = json_encode( $v );
			}
		}

		// Insert or update?
		if ( ! isset( $this->data[ $primary_key ] ) || ! $this->data[ $primary_key ] ) {
			$r = $wpdb->insert( $this->get_table(), $this->data );
			if ( $r ) {
				$this->data[ $primary_key ] = $wpdb->insert_id;
			} else {
				\ListPlus()->error->add( 'insert_' . $this->get_table(), __( 'Can not insert data.' ) );
				// \ListPlus()->error->add( 'insert_msg' . $this->get_table(), $r->get_error_messages() );
			}
		} else {
			$wpdb->update( static::get_table(), $this->data, array( $primary_key => $this->data[ $primary_key ] ) );
		}

		return $this;
	}

	/**
	 * Create a new model from the given data.
	 *
	 * @param [type] $properties
	 * @return self
	 */
	public static function create( $properties ) {
		return new static( $properties );
	}

	/**
	 * Delete the model from the database. Returns true if it was successful
	 * or false if it was not.
	 *
	 * @return boolean
	 */
	public function delete() {
		global $wpdb;
		$id = $this->data[ static::get_primary_key() ];
		return $wpdb->delete( static::get_table(), array( static::get_primary_key() => $id ) );
	}


	public static function delete_one( $id ) {
		global $wpdb;
		return $wpdb->delete( static::get_table(), array( static::get_primary_key() => $id ) );
	}

	public static function delete_by_key( $key, $id ) {
		global $wpdb;
		return $wpdb->delete( static::get_table(), array( $key => $id ) );
	}

	public static function delete_many( $ids ) {
		global $wpdb;
		return $wpdb->delete( static::get_table(), array( static::get_primary_key() => $ids ) );
	}


	/**
	 * Find a specific model by a given property value.
	 *
	 * @param  string $property
	 * @param  string $value
	 * @return false|self
	 */
	public static function find_one_by( $property, $value ) {
		global $wpdb;

		// Escape the value.
		$value = esc_sql( $value );

		// Get the table name.
		$table = static::get_table();

		// Get the item.
		$obj = $wpdb->get_row( "SELECT * FROM `{$table}` WHERE `{$property}` = '{$value}'", ARRAY_A );

		// Return false if no item was found, or a new model.
		return ( $obj ? static::create( $obj ) : false );
	}

	/**
	 * Find a specific model by it's unique ID.
	 *
	 * @param  integer $id
	 * @return false|self
	 */
	public static function find_one( $id ) {
		return static::find_one_by( static::get_primary_key(), (int) $id );
	}

	/**
	 * Start a query to find models matching specific criteria.
	 *
	 * @return ListPlus\CRUD\Query
	 */
	public static function query() {
		$class = get_called_class();

		if ( isset( self::$_names[ $class ] ) ) {
			return self::$_names[ $class ];
		}

		$query = new Query( $class );
		$query->set_searchable_fields( static::get_searchable_fields() );
		$query->set_primary_key( static::get_primary_key() );
		self::$_names[ $class ] = $query;
		return self::$_names[ $class ];
	}

	/**
	 * Return EVERY instance of this model from the database, with NO filtering.
	 *
	 * @return array
	 */
	public static function all() {
		global $wpdb;

		// Get the table name.
		$table = static::get_table();

		// Get the items.
		$results = $wpdb->get_results( "SELECT * FROM `{$table}`" );

		foreach ( $results as $index => $result ) {
			$results[ $index ] = static::create( (array) $result );
		}

		return $results;
	}


	public function to_array() {
		return $this->data;
	}


	public function offsetSet( $offset, $value ) {
		if ( is_null( $offset ) ) {
			$this->data[] = $value;
		} else {
			$this->data[ $offset ] = $value;
		}
	}

	public function offsetExists( $offset ) {
		return isset( $this->data[ $offset ] );
	}

	public function offsetUnset( $offset ) {
		unset( $this->data[ $offset ] );
	}

	public function offsetGet( $offset ) {
		return isset( $this->data[ $offset ] ) ? $this->data[ $offset ] : null;
	}
}
