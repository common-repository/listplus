<?php
$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
$enquiry = ListPlus\CRUD\Enquiry::find_one( $id );
$listing = new ListPlus\CRUD\Listing( $enquiry ['post_id'] );
$enquiry->mark_read();
?>
<div>
	<h1 class="wp-heading-inline">
		<?php _e( 'Enquiry Details', 'list-plus' ); ?>
	</h1>
	<a href="javascript:void();" onclick="history.back();" class="page-title-action"><?php _e( 'Back', 'list-plus' ); ?></a>
</div>

<hr class="wp-header-end">

<div class="lp-container-max lp-details">
	<div class="d-row">
		<h3><?php _e( 'Listing Item', 'list-plus' ); ?></h3>
		<p><?php
		if ( ! $listing->is_existing_listing() ) {
			echo '<em>' . __( 'Listing item deleted.', 'list-plus' ) . '</em>';
		} else {
			echo '<a target="_blank" href="' . esc_url( get_edit_post_link( $listing->get_id() ) ) . '">' . esc_html( $listing->get_name() ) . '</a>';
		}
		?></p>
	</div>
	<div class="d-row">
		<h3><?php _e( 'Submited by' ); ?></h3>
		<p><?php echo esc_html( $enquiry['email'] ); ?></p>
	</div>
	<div class="d-row">
		<h3><?php _e( 'Created on', 'list-plus' ); ?></h3>
		<p><?php echo esc_html( $enquiry['created_at'] ); ?></p>
	</div>
	<?php if ( $enquiry['title'] ) { ?>
	<div class="d-row">
		<h3><?php _e( 'Summary' ); ?></h3>
		<p><?php echo esc_html( $enquiry['title'] ); ?></p>
	</div>
	<?php } ?>

	<div class="d-row">
		<h3><?php _e( 'Message', 'list-plus' ); ?></h3>
		<div><?php echo apply_filters( 'listing_enquiery_content', $enquiry['content'] ); ?></div>
	</div>

	<div class="d-row">
		<h3><?php _e( 'IP', 'list-plus' ); ?></h3>
		<p><?php echo esc_html( $enquiry['ip'] ); ?></p>
	</div>

</div>
