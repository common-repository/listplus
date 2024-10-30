<?php
$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
$review = ListPlus\CRUD\Review::find_one( $id );
$listing = new ListPlus\CRUD\Listing( $review ['post_id'] );
?>
<div>
	<h1 class="wp-heading-inline">
		<?php _e( 'Review Details', 'list-plus' ); ?>
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
		<h3><?php _e( 'Reviewed by', 'list-plus' ); ?></h3>
		<p><?php echo esc_html( $review['email'] ); ?></p>
	</div>
	<div class="d-row">
		<h3><?php _e( 'Created on', 'list-plus' ); ?></h3>
		<p><?php echo esc_html( $review['created_at'] ); ?></p>
	</div>
	<div class="d-row">
		<h3><?php _e( 'Rating', 'list-plus' ); ?></h3>
		<p class="column-rating"><?php
			$max = $review['max'];
			echo sprintf( '<span class="dashicons dashicons-star-filled"></span> <strong>%s</strong>/%d', number_format_i18n( $review->rating, 1 ), $max );
		?></p>
	</div>
	<?php if ( $review['title'] ) { ?>
	<div class="d-row">
		<h3><?php _e( 'Summary', 'list-plus' ); ?></h3>
		<p><?php echo esc_html( $review['title'] ); ?></p>
	</div>
	<?php } ?>

	<div class="d-row">
		<h3><?php _e( 'Review', 'list-plus' ); ?></h3>
		<p><?php echo esc_html( $review['content'] ); ?></p>
	</div>

	<div class="d-row">
		<h3><?php _e( 'IP' ); ?></h3>
		<p><?php echo esc_html( $review['ip'] ); ?></p>
	</div>

</div>
