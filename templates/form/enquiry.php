<?php
$listing = ListPlus\get_listing();
$action = get_query_var( 'action' );
?>
<h2 class="l-form-title"><?php _e( 'Send an Enquery', 'list-plus' ); ?></h2>
<?php

if ( $action ) {
	$view = $listing->get_view_link();
	?>
	<p class="l-form-link">
		<?php
		printf( __( 'Back to %s', 'list-plus' ), '<a href="' . esc_url( $view ) . '" class="l-back"><strong>' . esc_html( $listing->get_name() ) . '</strong></a>' );
		?>
	</p>
	<?php
}
?>
<form class="lp-form l-ajax-form l-enquiry-form" action="" method="post" enctype="multipart/form-data">
	<?php
	wp_referer_field();
	ListPlus()->form->hidden(
		[
			'action' => 'listplus_save_enquiry',
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

	$fields = [ $name_args, $email_args ];

	if ( ListPlus()->settings->get( 'enquiry_title' ) ) {
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
		'title' => __( 'Your message ?', 'list-plus' ),
		'atts' => [
			'required' => 'true',
			'rows' => 5,
		],
	];

	ListPlus()->form->render(
		[
			'fields' => $fields,
		]
	);

	?>
	<input type="submit" class="button button-primary button-large" value="<?php esc_attr_e( 'Send message', 'list-plus' ); ?>">
</form>
