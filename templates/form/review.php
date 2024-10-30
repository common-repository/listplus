<?php
$listing = ListPlus\get_listing();
$action = get_query_var( 'action' );
$rating = get_query_var( 'rating' );
?>
<h2 class="l-form-title"><?php _e( 'Write a Review', 'list-plus' ); ?></h2>
<form class="lp-form l-ajax-form l-review-form" action="" method="post" enctype="multipart/form-data">
	<?php
	wp_referer_field();

	ListPlus()->form->reset();
	ListPlus()->form->hidden(
		[
			'action' => 'listplus_save_review',
			'post_id' => $listing->get_id(),
		]
	);

	$name_args = [
		'type' => 'text',
		'name' => 'name',
		'title' => __( 'Your Name', 'list-plus' ),
		'atts' => [
			'required' => 'true',
		],
	];

	$email_args = [
		'type' => 'text',
		'name' => 'email',
		'title' => __( 'Your email', 'list-plus' ),
		'atts' => [
			'required' => 'true',
		],
	];

	if ( is_user_logged_in() ) {
		$name_args['value'] = get_the_author_meta( 'user_login', get_current_user_id() );
		$email_args['value'] = get_the_author_meta( 'user_email', get_current_user_id() );
		$name_args['atts']['disabled'] = 'disabled';
		$email_args['atts']['disabled'] = 'disabled';
	}
	$fields = [];

	$fields[] = [
		'type' => 'rating',
		'name' => 'rating',
		'title' => __( 'Your rating', 'list-plus' ),
		'value' => (int) $rating,
		'options' => ListPlus()->settings->get_review_ratings(),
		'atts' => [
			'class' => 'alert_required',
			'data-validate-msg' => __( 'Please select a rating', 'list-plus' ),
		],
	];

	if ( ListPlus()->settings->get( 'review_title' ) ) {
		$fields[] = [
			'type' => 'text',
			'name' => 'title',
			'title' => __( 'Summary', 'list-plus' ),
			'atts' => [
				'required' => 'true',
			],
		];
	}

	$fields[] = [
		'type' => 'textarea',
		'name' => 'content',
		'title' => __( 'Your review', 'list-plus' ),
		'atts' => [
			'required' => 'true',
			'rows' => 8,
		],
	];

	$fields[] = $name_args;
	$fields[] = $email_args;

	ListPlus()->form->render(
		[
			'fields' => $fields,
		]
	);

	?>


	<div class="l-form-actions">
		<input type="submit" class="button button-primary button-large" value="<?php esc_attr_e( 'Submit', 'list-plus' ); ?>">
	</div>
</form>
