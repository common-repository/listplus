<?php

use ListPlus\CRUD\Listing_Type;
use ListPlus\CRUD\Listing;
use ListPlus\CRUD\Listing_Dynamic_Tax;
use ListPlus\Taxonomies;
use ListPlus\Helper;
use ListPlus\Post_Types;
use ListPlus\CRUD\Listing_Category;

$fields = [
	// [
	// 	'id' => 'post_title',
	// 	'_type' => 'preset',
	// 	'title' => __( 'Title', 'list-plus' ),
	// 	'type' => 'text',
	// 	'name' => 'post_title',
	// ],
	[
		'id' => 'header',
		'_type' => 'preset',
		'title' => __( 'Listing Header', 'list-plus' ),
		'name' => 'header',
	],
	[
		'id' => 'actions',
		'_type' => 'preset',
		'title' => __( 'Actions', 'list-plus' ),
		'type' => 'text',
		'name' => 'actions',
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
	],
	[
		'id' => 'post_date',
		'_type' => 'preset',
		'type' => 'date',
		'name' => 'post_date',
		'icon' => 'calendar',
		'title' => __( 'Publish Date', 'list-plus' ),
		'atts' => [
			'placeholder' => __( 'Select date...', 'list-plus' ),
		],
	],
	[
		'id' => 'price',
		'_type' => 'preset',
		'type' => 'text',
		'name' => 'price',
		'icon' => 'dollar',
		'title' => __( 'Price', 'list-plus' ),
	],
	[
		'id' => 'price_range',
		'_type' => 'preset',
		'type' => 'select',
		'name' => 'price_range',
		'icon' => 'money-stack',
		'title' => __( 'Price Range', 'list-plus' ),
		'options' => [
			'1' => __( '$', 'list-plus' ),
			'2' => __( '$$', 'list-plus' ),
			'3' => __( '$$$', 'list-plus' ),
			'4' => __( '$$$$', 'list-plus' ),
		],
	],
	[
		'id' => 'post_author',
		'_type' => 'preset',
		'type' => 'select',
		'name' => 'post_author',
		'icon' => 'user',
		'author' => 1,
		'title' => __( 'Author', 'list-plus' ),
		'atts' => [
			'placeholder' => __( 'Select an user', 'list-plus' ),
		],
	],
	[
		'id' => 'claimed',
		'_type' => 'preset',
		'type' => 'select',
		'author' => 1,
		'name' => 'claimed',
		'icon' => 'user',
		'title' => __( 'Claimer', 'list-plus' ),
		'atts' => [
			'placeholder' => __( 'Select an user', 'list-plus' ),
		],
	],
	[
		'id' => 'categories',
		'_type' => 'preset',
		'type' => 'select',
		'tax' => 'listing_cat',
		'name' => 'categories',
		'icon' => 'folder',
		'title' => __( 'Categories', 'list-plus' ),
		'atts' => [
			'multiple' => 'multiple',
		],
	],
	[
		'id' => 'region',
		'_type' => 'preset',
		'type' => 'select',
		'tax' => 'listing_region',
		'name' => 'region',
		'icon' => 'map',
		'title' => __( 'Region', 'list-plus' ),
	],
	[
		'id' => 'email',
		'_type' => 'preset',
		'type' => 'text',
		'name' => 'email',
		'icon' => 'envelope',
		'title' => __( 'Email', 'list-plus' ),
	],
	[
		'id' => 'phone',
		'_type' => 'preset',
		'type' => 'text',
		'name' => 'phone',
		'icon' => 'phone1',
		'title' => __( 'Phone', 'list-plus' ),
	],
	[
		'id' => 'map',
		'_type' => 'preset',
		'title' => __( 'Map', 'list-plus' ),
		'type' => 'map',
		'icon' => 'map',
		'name' => [
			'lat' => 'lat',
			'lng' => 'lng',
			'address' => 'address',
		],
	],
	[
		'id' => 'address',
		'_type' => 'preset',
		'title' => __( 'Address', 'list-plus' ),
		'icon' => 'address',
	],
	[
		'id' => 'websites',
		'_type' => 'preset',
		'type' => 'websites',
		'name' => 'websites',
		'icon' => 'globe',
		'title' => __( 'Websites & Social Networks', 'list-plus' ),
	],

	[
		'id' => 'media_files',
		'_type' => 'preset',
		'type' => 'gallery',
		'name' => 'media_files',
		'icon' => 'pictures',
		'title' => __( 'Gallery', 'list-plus' ),
	],
	[
		'id' => 'video_url',
		'_type' => 'preset',
		'type' => 'text',
		'name' => 'video_url',
		'icon' => 'video',
		'title' => __( 'Video', 'list-plus' ),
	],
	[
		'id' => 'review_sumary',
		'_type' => 'preset',
		'type' => 'text',
		'name' => 'reviews',
		'title' => __( 'Review Summary', 'list-plus' ),
	],

	[
		'id' => 'reviews',
		'_type' => 'preset',
		'type' => 'text',
		'name' => 'reviews',
		'title' => __( 'Reviews', 'list-plus' ),
		'frontend_name' => __( 'Recommended Reviews', 'list-plus' ),
	],

	[
		'id' => 'open_hours',
		'_type' => 'preset',
		'title' => __( 'Open Hours', 'list-plus' ),
		'type' => 'open_hours',
		'name' => 'open_hours',
		'icon' => 'time1',
	],

	[
		'id' => 'enquiry',
		'icon' => 'paper-plane',
		'_type' => 'preset',
		'title' => __( 'Enquiry', 'list-plus' ),
	],

	[
		'id' => 'custom',
		'_type' => 'custom',
		'title' => __( 'Custom Field', 'list-plus' ),
	],

	[
		'id' => 'group',
		'type' => 'group',
		'_type' => 'group',
		'title' => __( 'Group', 'list-plus' ),
	],

];


$all_taxs = \ListPlus()->taxonomies->get_all();
foreach ( $all_taxs as $key => $args ) {
	$fields[] = [
		'id'            => 'tax_' . $key,
		'_type'         => 'preset',
		'type'          => 'dynamic_tax',
		'allow_new'     => $args['allow_new'],
		'tax'           => $key,
		'name'          => 'taxs',
		'icon'          => 'info-circle',
		'title'         => sprintf( 'Tax: %s', $args ['name'] ),
		'frontend_name' => $args ['frontend_name'],
	];
}


return apply_filters( 'listplus_listing_display_fields', $fields );
