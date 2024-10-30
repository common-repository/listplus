<?php
use ListPlus\Helper;
use ListPlus\Post_Types;
use ListPlus\CRUD\Listing_Category;

$page = sanitize_text_field( $_REQUEST['page'] ); // WPCS: Input var ok.
$item = ListPlus\get_editing_listing();
$item->get_gallery();
$query_args = [
	'post_type'   => 'listing',
];
$link  = esc_url( add_query_arg( $query_args, 'edit.php' ) );
$query_args['view'] = 'edit-type';
$query_args['page'] = 'listing_edit';
$query_args['listing_type'] = $item['listing_type'];
$type = \ListPlus\get_type_for_editing_listing();
$listing_type = $item['listing_type'];
$new_link  = esc_url( add_query_arg( $query_args, 'admin.php' ) );


$quick_actions = [
	'listing_enquiries' => [
		'count' => 'count_enquiries',
		'text' => __( 'Equeries (%s)', 'list-plus' ),
	],
	'listing_reviews' => [
		'count' => 'count_reviews',
		'text' => __( 'Reviews (%s)', 'list-plus' ),
	],
	'listing_claim_entries' => [
		'count' => 'count_claims',
		'text' => __( 'Claims (%s)', 'list-plus' ),
	],
	'listing_reports' => [
		'count' => 'count_reports',
		'text' => __( 'Reports (%s)', 'list-plus' ),
	],
];

$view_link = get_permalink( $item->get_id() );

?>
<div class="lp-container">
	<div>
		<h1 class="wp-heading-inline"><?php
		if ( $item->get_id() ) {
			printf( __( 'Editing %1$s Listing: <em>%2$s</em>', 'list-plus' ), $type->get_name(), esc_html( $item['post_title'] ) );
		} else {
			printf( __( 'New %s Listing', 'list-plus' ), $type->get_name() );
		}
		?></h1>
	</div>

	<hr class="wp-header-end">

	<p class="lp-before-form-actions">
		<a class="button-secondary" href="<?php echo $link; ?>"><?php _e( 'Back to list', 'list-plus' ); ?></a>
		<?php if ( $item->get_id() ) { ?>
			<a class="button-secondary" href="<?php echo $new_link; ?>"><?php _e( 'New', 'list-plus' ); ?></a>
			<a class="button-secondary" target="_blank" href="<?php echo $view_link; ?>"><?php _e( 'View', 'list-plus' ); ?></a>
			<?php

			foreach ( $quick_actions as $q_page => $q_args ) {
				$n = 0;
				if ( $q_args['count'] ) {
					if ( is_callable( [ $item, $q_args['count'] ] ) ) {
						$n = call_user_func_array( [ $item, $q_args['count'] ], [] );
					}
				}
				$link = esc_url(
					add_query_arg(
						[
							'post_type' => 'listing',
							'page' => $q_page,
							'listing_id' => $item->get_id(),
						],
						'edit.php'
					)
				);
				$text = sprintf( $q_args['text'], $n );

				printf( '<a class="button-secondary" href="%1$s">%2$s</a> ', $link, $text );

			}
			?>
		<?php } ?>
	</p>

	<form class="lp-form lp-main-form" action="" method="post" enctype="multipart/form-data">
	<?php
	// $item->calc_rating();
	// $item->get_gallery();
	// echo '<div class="lp-debug-form">';
	// // var_dump( ListPlus()->icons->get_icons() );
	// // var_dump( $type->get_fields() );
	// // var_dump( $type->get_support_taxs() );
	// var_dump( $item->to_array() );
	// // var_dump( $item->get_existing_taxs() );
	// echo '</div>';
	wp_referer_field();

	ListPlus()->form->set_validate_field( false );
	ListPlus()->form->set_data( $item->to_array() );

	ListPlus()->form->hidden(
		[
			'ID' => $item->get_id(),
			'action' => 'listplus_save_listing',
			'listing_type' => $listing_type,
			'admin_dashboard' => '1',
		]
	);


	ListPlus()->form->render(
		[
			'fields' => [

				[
					'type' => 'box',
					// 'wrapper_class' => [ 'no-border' ],
					'no_border' => true,
					// 'title' => __( 'Listing Infomation', 'list-plus' ),
					'fields' => $type->get_fields(),
				],

				[
					'type' => 'box',
					'title' => __( 'Advanced', 'list-plus' ),
					'fields' => [
						[
							'id' => 'post_status',
							'type' => 'select',
							'name' => 'post_status',
							'options' => Post_Types::get_status(),
							'title' => __( 'Status', 'list-plus' ),
						],

						[
							'id' => 'expired',
							'_type' => 'preset',
							'type' => 'date',
							'name' => 'expired',
							'title' => __( 'Expiration date', 'list-plus' ),
							'atts' => [
								'placeholder' => __( 'Select date...', 'list-plus' ),
							],
						],

						[
							'id' => 'post_name',
							'type' => 'text',
							'name' => 'post_name',
							'title' => __( 'Slug', 'list-plus' ),
						],

						[
							'id' => 'post_author',
							'type' => 'select',
							'name' => 'post_author',
							'author' => 1, // is author field type.
							'title' => __( 'Author', 'list-plus' ),
							'atts' => [
								'placeholder' => __( 'Select an user', 'list-plus' ),
							],
						],

						[
							'id' => 'claimed',
							'type' => 'select',
							'author' => 1, // is author field type.
							'name' => 'claimed',
							'title' => __( 'Claimer', 'list-plus' ),
							'atts' => [
								'placeholder' => __( 'Select an user', 'list-plus' ),
							],
						],

						[
							'type' => 'group',
							'fields' => [
								[
									'type' => 'checkbox',
									'name' => 'verified',
									'checkbox_label' => __( 'Verified', 'list-plus' ),
									'checked_value' => 'yes',
								],
								[
									'type' => 'checkbox',
									'name' => 'is_featured',
									'checkbox_label' => __( 'Featured this listing', 'list-plus' ),
									'checked_value' => 'yes',
								],

							],
						],

						[
							'type' => 'group',
							'fields' => [
								[
									'type' => 'checkbox',
									'name' => 'enquiry_status',
									'checkbox_label' => __( 'Disable enqueries', 'list-plus' ),
									'checked_value' => 'disabled',
								],
								[
									'type' => 'checkbox',
									'name' => 'claim_status',
									'checkbox_label' => __( 'Disable claim', 'list-plus' ),
									'checked_value' => 'disabled',
								],
							],
						],

						[
							'type' => 'group',
							'fields' => [
								[
									'type' => 'checkbox',
									'name' => 'review_status',
									'checkbox_label' => __( 'Disable reviews', 'list-plus' ),
									'checked_value' => 'disabled',
								],
								[
									'type' => 'checkbox',
									'name' => 'report_status',
									'checkbox_label' => __( 'Disable report', 'list-plus' ),
									'checked_value' => 'disabled',
								],
							],
						],


						// [
						// 'type' => 'group',
						// 'fields' => [
						// [
						// 'type' => 'checkbox',
						// 'name' => 'comment_status',
						// 'checkbox_label' => __( 'Disable comments', 'list-plus' ),
						// 'checked_value' => 'disabled',
						// ],
						// ],
						// ],
					],
				],




			], // end form fields.
		]
	);

	?>
	<input type="submit" class="button button-primary" value="Save">

	</form>
</div>
