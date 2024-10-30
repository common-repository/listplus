<?php

namespace ListPlus\CRUD;

use ListPlus\CRUD\Taxonomy;

class Listing_Dynamic_Tax extends Taxonomy {

	public static $curent_tax = null;

	public static function set_current_tax( $tax ) {
		self::$curent_tax = $tax;
	}

	public static function type() {
		return self::$curent_tax;
	}

}
