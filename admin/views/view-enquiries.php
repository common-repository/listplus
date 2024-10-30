<?php
use ListPlus\CRUD\Listing_Type;
$list_table = new ListPlus\Admin\Enquiries_List_Table();
$listing = null;
$listing_id = $list_table->get_query_args( 'listing_id' );
if ( $listing_id ) {
	$listing = ListPlus\get_listing( $listing_id );
}

?>
<div>
	<h1 class="wp-heading-inline">
		<?php if ( ! $listing ) { ?>
			<?php _e( 'Enquiries', 'list-plus' ); ?>
		<?php } else { ?>
			<?php printf( __( 'Enquiries for: <em>%s</em>', 'list-plus' ), esc_html( $listing->get_name() ) ); ?>
		<?php } ?>
	</h1>
</div>

<hr class="wp-header-end">

<form class="lp-form--" action="" method="post" enctype="multipart/form-data">
	<?php

	$list_table->prepare_items();
	$list_table->views();
	$list_table->display();
	?>
</form>
