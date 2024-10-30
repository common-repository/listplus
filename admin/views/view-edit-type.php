<?php
$page = wp_unslash( $_REQUEST['page'] ); // WPCS: Input var ok.
$query_args = array(
	'post_type'   => 'listing',
	'page'   => 'listing_types',
);
$link  = esc_url( add_query_arg( $query_args, 'edit.php' ) );
$query_args['view'] = 'edit-type';
$new_link  = esc_url( add_query_arg( $query_args, 'edit.php' ) );
$id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : false;
$item = new ListPlus\CRUD\Listing_Type( $id );

?>

<div class="lp-container">

<div>
	<h1 class="wp-heading-inline"><?php
	if ( $item->get_id() ) {
		printf( __( 'Editing Listing Type: <em>%s</em>', 'list-plus' ), esc_html( $item['name'] ) );
	} else {
		_e( 'New Listing Type', 'list-plus' );
	}
	?></h1>
</div>

<hr class="wp-header-end">
<p class="lp-before-form-actions">
	<a class="button-secondary" href="<?php echo $link; ?>"><?php _e( 'Back to list', 'list-plus' ); ?></a>
	<?php if ( $item->get_id() ) { ?>
	<a class="button-secondary" href="<?php echo $new_link; ?>"><?php _e( 'New', 'list-plus' ); ?></a>
	<?php } ?>
</p>

<form class="lp-form lp-main-form" action="" method="post" enctype="multipart/form-data">
	<?php
	wp_referer_field();
	ListPlus()->form->set_data( $item->to_array() );
	ListPlus()->form->hidden(
		[
			'term_id' => $id,
			'action' => 'listplus_save_listing_type',
		]
	);

	$tax_fields = [];
	$options = [];

	foreach ( ListPlus()->taxonomies->get_all() as $key => $tax ) {
		$options[ $key ] = $tax['name'];
	}

	$tax_fields[] = [
		'type' => 'list_sort',
		'name' => 'support_taxs',
		'options' => $options,
	];

	?>
	<nav class="nav-tab-wrapper l-tab-wrapper">
		<a href="#" data-selector=".tab-general" class="nav-tab nav-tab-active"><?php _e( 'General', 'list-plus' ); ?></a>
		<a href="#" data-selector=".tab-fields" class="nav-tab "><?php _e( 'Fields', 'list-plus' ); ?></a>
		<a href="#" data-selector=".tab-single" class="nav-tab "><?php _e( 'Single Layout', 'list-plus' ); ?></a>
		<a href="#" data-selector=".tab-filter" class="nav-tab "><?php _e( 'Filter', 'list-plus' ); ?></a>
	</nav>
	<?php

	echo '<div class="l-tab-content tab-general active">';

	ListPlus()->form->render(
		[
			'fields' => [
				[
					'type' => 'box',
					'title' => __( 'General', 'list-plus' ),
					'fields' => [
						[
							'type' => 'text',
							'name' => 'name',
							'title' => __( 'Plural Name', 'list-plus' ),
						],
						[
							'type' => 'text',
							'name' => 'singular_name',
							'title' => __( 'Singular Name', 'list-plus' ),
						],
						[
							'type' => 'group',
							'fields' => [
								[
									'type' => 'icon',
									'name' => 'icon',
									'title' => __( 'Icon', 'list-plus' ),
									'atts' => [
										'placeholder' => __( 'Select an icon...', 'list-plus' ),
									],
								],
								[
									'type' => 'select',
									'name' => 'status',
									'options' => [
										'active' => __( 'Active', 'list-plus' ),
										'deactive' => __( 'Deactive', 'list-plus' ),
									],
									'title' => __( 'Status', 'list-plus' ),
								],
							],
						],
						[
							'type' => 'text',
							'name' => 'slug',
							'title' => __( 'Slug', 'list-plus' ),
						],
						[
							'type' => 'textarea',
							'name' => 'description',
							'title' => __( 'Description', 'list-plus' ),
							'atts' => [
								'rows' => 5,
							],
						],


						[
							'type' => 'group',
							'fields' => [
								[
									'type' => 'select',
									'name' => 'tax_highlight',
									'tax' => 'listing_tax',
									'title' => __( 'Taxonomy Highlights', 'list-plus' ),
								],
								[
									'type' => 'number',
									'name' => 'highlight_limit',
									'title' => __( 'Limit highlight terms.', 'list-plus' ),
									'default' => 2,
								],
							],
						],

						[
							'type' => 'select',
							'tax' => 'listing_cat',
							'name' => 'restrict_categories',
							'title' => __( 'Category restrictions', 'list-plus' ),
							'desc' => __( 'Category restrictions for this listing type. All selected categories will be allowed. Leave empty to allow all.', 'list-plus' ),
							'atts' => [
								'multiple' => 'multiple',
								'placeholder' => __( 'Select categories...', 'list-plus' ),
							],
						],
					],
				], // end box.

			], // end form fields.
		]
	);

	echo '</div>';

	echo '<div class="l-tab-content tab-fields">';
	ListPlus()->form->render(
		[
			'fields' => [

				[
					// 'type' => 'custom_fields_content',
					'type' => 'form_builder',
					'name' => 'fields',
					'data_key' => 'listing_fields',
					'title' => __( 'Fields', 'list-plus' ),
					'fields' => \ListPlus\Helper::get_listing_fields(),
				],
			], // end form fields.
		]
	);
	echo '</div>';

	echo '<div class="l-tab-content tab-single">';
	ListPlus()->form->render(
		[
			'fields' => [

				// [
				// 'type' => 'box',
				// 'title' => __( 'Layout', 'list-plus' ),
				// 'fields' => [
				// [
				// 'id' => 'single_layout',
				// 'type' => 'select',
				// 'name' => 'single_layout',
				// 'title' => __( 'Layout', 'list-plus' ),
				// 'default' => 'full-width',
				// 'options' => [
				// 'full-width' => __( 'Default - Single column', 'list-plus' ),
				// 'two-columns' => __( 'Two columns', 'list-plus' ),
				// 'content-sidebar' => __( 'Two thirds / One third', 'list-plus' ),
				// 'sidebar-content' => __( 'One third / Two thirds', 'list-plus' ),
				// ],
				// ],
				// ],
				// ],
				[
					'type' => 'box',
					'title' => __( 'Main Column', 'list-plus' ),
					'fields' => [
						[
							'type' => 'display_builder',
							'name' => 'single_main',
							'data_key' => 'single_main',
							// 'title' => __( 'Main Column', 'list-plus' ),
							'fields' => \ListPlus\Helper::get_listing_display_fields(),
						],
					],
				],

				[
					'type' => 'box',
					'title' => __( 'Sidebar Column', 'list-plus' ),
					'fields' => [
						[
							'type' => 'display_builder',
							'name' => 'single_sidebar',
							'data_key' => 'single_sidebar',
							// 'title' => __( 'Sidebar Column', 'list-plus' ),
							'fields' => \ListPlus\Helper::get_listing_display_fields(),
						],
					],
				],


			], // end form fields.
		]
	);
	echo '</div>';


	echo '<div class="l-tab-content tab-filter">';
	ListPlus()->form->render(
		[
			'fields' => [
				[
					'type' => 'box',
					'title' => __( 'Quick Filters', 'list-plus' ),
					'fields' => [
						[
							'id' => 'quick_filters',
							'type' => 'textarea',
							'name' => 'quick_filters',
							'desc' => __( 'Enter taxonomy <strong>term_id</strong>, example: 1,2,3. Separated by commas.', 'list-plus' ),
							'default' => '',
							'atts' => [
								'rows' => 10,
							],
						],
					],
				],
			], // end form fields.
		]
	);
	echo '</div>';




	?>
	<input type="submit" class="button button-primary button-large" value="Save">

</form>
</div>
