<?php
use ListPlus\CRUD\Listing_Type;

$page = wp_unslash( $_REQUEST['page'] ); // WPCS: Input var ok.
$query_args = array(
	'page'   => $page,
	'view' => 'edit-listing',
);
$new_link  = add_query_arg( $query_args, 'admin.php' );

$get_data = Listing_Type::get_all();

?>

<div>
	<h1 class="wp-heading-inline">
		<?php _e( 'Listings', 'list-plus' ); ?>
	</h1>
	<span href="<?php echo $new_link; ?>" class="new-listing-dr page-title-action"><?php _e( 'New Listing', 'list-plus' ); ?>
		<div class="lp-type-links">
			<div class="ls-inner">
				<?php foreach ( $get_data['items'] as $type ) { ?>
					<a href="<?php echo esc_attr( add_query_arg( [ 'listing_type' => $type['post_name'] ], $new_link ) ); ?>"><?php echo esc_html( $type['post_title'] ); ?></a>
				<?php } ?>
			</div>
		</div>
	</span>
	
</div>

<hr class="wp-header-end">

<form class="lp-form--" action="" method="post" enctype="multipart/form-data">

	<?php

	$wp_list_table = new ListPlus\Admin\Listing_Table();

	$wp_list_table->prepare_items();
	$wp_list_table->views();
	$wp_list_table->display();


	?>
</form>
