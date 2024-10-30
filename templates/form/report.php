<?php
$listing = ListPlus\get_listing();
$action = get_query_var( 'action' );
?>
<h2 class="l-form-title"><?php _e( 'Report', 'list-plus' ); ?></h2>
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
<form class="lp-form l-ajax-form l-report-form" action="" method="post" enctype="multipart/form-data">
	<?php
	wp_referer_field();
	$listing = ListPlus\get_listing();
	ListPlus()->form->hidden(
		[
			'action' => 'listplus_save_report',
			'post_id' => $listing->get_id(),
		]
	);

	$fields = [];

	$fields[] = [
		'type' => 'textarea',
		'name' => 'reason',
		'atts' => [
			'required' => 'true',
			'rows' => 8,
			'placeholder' => __( 'What\'s wrong with this listing?', 'list-plus' ),
		],
	];

	ListPlus()->form->render(
		[
			'fields' => $fields,
		]
	);

	?>
	<input type="submit" class="button button-primary button-large" value="<?php esc_attr_e( 'Submit', 'list-plus' ); ?>">
</form>
