<?php
$page = wp_unslash( $_REQUEST['page'] ); // WPCS: Input var ok.
$query_args = array(
	'page'   => $page,
	'post_type'   => 'listing',
	'view' => 'edit-type',
);
$new_link  = esc_url( add_query_arg( $query_args, 'edit.php' ) );

?>

<div>
	<h1 class="wp-heading-inline">
		<?php _e( 'Listing Types', 'list-plus' ); ?>
	</h1>
	<a href="<?php echo $new_link; ?>" class="hide-if-no-js page-title-action"><?php _e( 'New Listing Type', 'list-plus' ); ?></a>
</div>

<hr class="wp-header-end">

<form class="lp-form--" action="" method="post" enctype="multipart/form-data">

	<?php

	// $lt = new ListPlus\CRUD\Listing_Type( 19 );
	// // $lt['name'] = 'Cleaning';
	// // $lt->save();
	// global $post_type, $taxonomy, $action, $tax;
	// $taxonomy = 'listing_type';
	// $post_type = 'listing';
	// $action = '';
	// $tax = get_taxonomy( 'listing_type' );

	$wp_list_table = new ListPlus\Admin\Listing_Type_Table();

	$wp_list_table->prepare_items();
	$wp_list_table->views();
	$wp_list_table->display();


	?>
</form>
