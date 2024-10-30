<?php

use ListPlus\CRUD\Listing_Type;
use ListPlus\CRUD\Listing;
use ListPlus\CRUD\Listing_Dynamic_Tax;
use ListPlus\Taxonomies;
use ListPlus\Helper;
use ListPlus\Post_Types;
use ListPlus\CRUD\Listing_Category;

$fields = [
	[
		'id' => 'post_title',
		'_type' => 'preset',
		'title' => __( 'Title', 'list-plus' ),
		'type' => 'text',
		'name' => 'post_title',
		'validate' => 'text',
		'invalid_msg' => __( 'Please enter listing title.', 'list-plus' ),
	],
	[
		'id' => 'post_content',
		'_type' => 'preset',
		'type' => 'editor',
		'name' => 'post_content',
		'title' => __( 'Description', 'list-plus' ),
		'atts' => [
			'rows' => 10,
		],
		'validate' => 'html',
		'invalid_msg' => __( 'Please enter listing description.', 'list-plus' ),
	],
	[
		'id' => 'post_date',
		'_type' => 'preset',
		'type' => 'date',
		'name' => 'post_date',
		'title' => __( 'Publish Date', 'list-plus' ),
		'atts' => [
			'placeholder' => __( 'Select date...', 'list-plus' ),
		],
		'validate' => 'datetime',
		'invalid_msg' => __( 'Please select publish date.', 'list-plus' ),
	],
	[
		'id' => 'price',
		'_type' => 'preset',
		'type' => 'text',
		'name' => 'price',
		'title' => __( 'Price', 'list-plus' ),
		'validate' => 'float',
		'invalid_msg' => __( 'Please enter listing price.', 'list-plus' ),
	],
	[
		'id' => 'price_range',
		'_type' => 'preset',
		'type' => 'select',
		'name' => 'price_range',
		'title' => __( 'Price Range', 'list-plus' ),
		'options' => [
			'1' => __( '$', 'list-plus' ),
			'2' => __( '$$', 'list-plus' ),
			'3' => __( '$$$', 'list-plus' ),
			'4' => __( '$$$$', 'list-plus' ),
		],
		'validate' => 'int',
		'invalid_msg' => __( 'Please select price range.', 'list-plus' ),
	],
	[
		'id' => 'categories',
		'_type' => 'preset',
		// 'type' => 'select',
		'type' => 'list_sort',
		'tax' => 'listing_cat',
		'name' => 'categories',
		'title' => __( 'Categories', 'list-plus' ),
		'atts' => [
			'multiple' => 'multiple',
		],
		'invalid_msg' => __( 'Please select categories.', 'list-plus' ),
	],
	[
		'id' => 'region',
		'_type' => 'preset',
		'type' => 'select',
		'tax' => 'listing_region',
		'name' => 'region',
		'title' => __( 'Region', 'list-plus' ),
		'validate' => 'int',
		'invalid_msg' => __( 'Please select region.', 'list-plus' ),
	],
	[
		'id' => 'email',
		'_type' => 'preset',
		'type' => 'text',
		'name' => 'email',
		'title' => __( 'Email', 'list-plus' ),
		'validate' => 'email',
		'invalid_msg' => __( 'Please enter valid email.', 'list-plus' ),
	],
	[
		'id' => 'phone',
		'_type' => 'preset',
		'type' => 'text',
		'name' => 'phone',
		'title' => __( 'Phone', 'list-plus' ),
		'validate' => 'text',
		'invalid_msg' => __( 'Please enter phone number.', 'list-plus' ),
	],
	[
		'id' => 'map',
		'_type' => 'preset',
		'title' => __( 'Map', 'list-plus' ),
		'type' => 'map',
		'name' => [
			'lat' => 'lat',
			'lng' => 'lng',
			'address' => 'address',
		],
	],
	[
		'id' => 'websites',
		'_type' => 'preset',
		'type' => 'websites',
		'name' => 'websites',
		'title' => __( 'Websites & Social Networks', 'list-plus' ),
		'invalid_msg' => __( 'Please enter your website or social networks.', 'list-plus' ),
	],

	[
		'id' => 'media_files',
		'_type' => 'preset',
		'type' => 'gallery',
		'name' => 'media_files',
		'title' => __( 'Gallery', 'list-plus' ),
		'invalid_msg' => __( 'Please upload your media files.', 'list-plus' ),
	],
	[
		'id' => 'video_url',
		'_type' => 'preset',
		'type' => 'text',
		'name' => 'video_url',
		'title' => __( 'Video URL', 'list-plus' ),
		'validate' => 'text',
		'invalid_msg' => __( 'Please enter video url.', 'list-plus' ),
	],

	[
		'id' => 'open_hours',
		'_type' => 'preset',
		'title' => __( 'Open Hours', 'list-plus' ),
		'type' => 'open_hours',
		'name' => 'open_hours',
		'validate' => 'open_hours',
		'invalid_msg' => __( 'Please enter open hours.', 'list-plus' ),
	],

	[
		'id' => 'text',
		'_type' => 'custom',
		'title' => __( 'Text', 'list-plus' ),
	],
	[
		'id' => 'select',
		'_type' => 'custom',
		'type' => 'select',
		'input_options' => 'yes',
		'title' => __( 'Select', 'list-plus' ),
	],
	[
		'id' => 'textarea',
		'type' => 'textarea',
		'_type' => 'custom',
		'title' => __( 'Textarea', 'list-plus' ),
	],
	[
		'id' => 'checkbox',
		'type' => 'checkbox',
		'_type' => 'custom',
		'title' => __( 'Checkbox', 'list-plus' ),
		'type' => 'checkbox',
		'checked_value' => 'yes',
	],
	[
		'id' => 'editor',
		'type' => 'editor',
		'_type' => 'custom',
		'title' => __( 'Edtitor', 'list-plus' ),
	],

];


$all_taxs = \ListPlus()->taxonomies->get_all();
foreach ( $all_taxs as $key => $args ) {
	$fields[] = [
		'id'        => 'tax_' . $key,
		'_type'     => 'preset',
		'type'      => 'dynamic_tax',
		'allow_new' => $args['allow_new'],
		'tax'       => $key,
		'name'      => $key,
		'title'     => sprintf( __( 'Tax: %s', 'list-plus' ), $args ['name'] ),
	];
}


return apply_filters( 'listplus_listing_fields', $fields );
