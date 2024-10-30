<?php
$current_tab = isset( $_REQUEST['tab'] ) ? wp_unslash( $_REQUEST['tab'] ) : ''; // WPCS: Input var ok.
$page = wp_unslash( $_REQUEST['page'] ); // WPCS: Input var ok.
$query_args = array(
	'post_type'   => 'listing',
	'page'   => 'listing_settings',
);
$link = add_query_arg( $query_args, 'edit.php' );


if ( ! $current_tab ) {
	$current_tab = 'general';
}

$setting_tabs = ListPlus()->settings->get_setting_tabs();

?>

<nav class="nav-tab-wrapper woo-nav-tab-wrapper">
	<?php foreach ( $setting_tabs as $tab ) { ?>
		<a href="<?php echo add_query_arg( [ 'tab' => $tab['id'] ], $link ); ?>" class="nav-tab <?php echo ( $tab['id'] == $current_tab ) ? 'nav-tab-active' : ''; ?>"><?php echo $tab['label']; ?></a>
	<?php } ?>

</nav>

<hr class="wp-header-end">


<form class="lp-form lp-form-lable-left lp-max mt-top-2 lp-main-form notice-bottom" action="" method="post" enctype="multipart/form-data">
	<?php

	wp_referer_field();

	ListPlus()->form->set_data( ListPlus()->settings->get_settings() );

	ListPlus()->form->hidden(
		[
			'action' => 'listplus_save_settings',
			'current_tab' => $current_tab,
		]
	);

	$fields = isset( $setting_tabs[ $current_tab ] ) ? $setting_tabs[ $current_tab ]['fields'] : false;

	if ( $fields ) {
		ListPlus()->form->render(
			[
				'fields' => $fields,
			]
		);
	} else {
		echo '<p>';
		_e( 'Not settings found', 'list-plus' );
		echo '</p>';
	}

	?>
	<input type="submit" class="button button-primary button-large" value="<?php esc_attr_e( 'Save Changes', 'list-plus' ); ?>">
</form>

