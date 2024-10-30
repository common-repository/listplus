<?php
$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
$claim = ListPlus\CRUD\Claim::find_one( $id );
$listing = new ListPlus\CRUD\Listing( $claim ['post_id'] );
$current_url = $_SERVER['REQUEST_URI'];
$action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : false;
$message = false;
if ( $action && in_array( $action, [ 'approved', 'pending', 'rejected' ], true ) ) {
	if ( \method_exists( $claim, 'mark_' . $action ) ) {
		$message = __( 'Status updated.', 'list-plus' );
		if ( $claim->status != $action ) { // If status changed.
			\call_user_func_array( [ $claim, 'mark_' . $action ], [] );
		}
	}
}

?>
<div>
	<h1 class="wp-heading-inline">
		<?php _e( 'Claim Details', 'list-plus' ); ?>
	</h1>
	<a href="javascript:void();" onclick="history.back();" class="page-title-action"><?php _e( 'Back', 'list-plus' ); ?></a>
</div>

<hr class="wp-header-end">
<?php  if ( $message ) {
	?>
	<div class="notice notice-success is-dismissible">
		<p><?php echo $message; // WPCS: XSS ok. ?></p>
	</div>
<?php } ?>

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
		<h3><?php _e( 'Status' ); ?></h3>
		<p><?php

		switch ( $claim->status ) {
			case 'approved':
				echo '<span title="' . __( 'Approved', 'list-plus' ) . '" class="color-approved dashicons dashicons-yes-alt"></span>' . __( 'Approved', 'list-plus' );
				break;
			case 'pending':
				echo '<span title="' . __( 'Pending', 'list-plus' ) . '" class="color-pending dashicons dashicons-warning"></span>' . __( 'Pending', 'list-plus' );
				break;
			case 'rejected':
				echo '<span title="' . __( 'Rejected', 'list-plus' ) . '" class="color-rejected dashicons dashicons-dismiss"></span>' . __( 'Rejected', 'list-plus' );
				break;
			default:
				echo esc_html( $item['status'] );

		}

		?></p>
	</div>
	<div class="d-row">
		<h3><?php _e( 'Submited by' ); ?></h3>
		<p><?php echo esc_html( $claim['email'] ); ?></p>
	</div>
	<div class="d-row">
		<h3><?php _e( 'Created on', 'list-plus' ); ?></h3>
		<p><?php echo esc_html( $claim['created_at'] ); ?></p>
	</div>
	<?php if ( $claim['title'] ) { ?>
	<div class="d-row">
		<h3><?php _e( 'Summary', 'list-plus' ); ?></h3>
		<p><?php echo esc_html( $claim['title'] ); ?></p>
	</div>
	<?php } ?>

	<div class="d-row">
		<h3><?php _e( 'Message', 'list-plus' ); ?></h3>
		<div><?php echo apply_filters( 'listing_enquiery_content', $claim['content'] ); ?></div>
	</div>

	<div class="d-row">
		<h3><?php _e( 'IP', 'list-plus' ); ?></h3>
		<p><?php echo esc_html( $claim['ip'] ); ?></p>
	</div>

	<?php
	$actions = [
		'approved' => __( 'Approved', 'list-plus' ),
		'pending' => __( 'Pending', 'list-plus' ),
		'rejected' => __( 'Rejected', 'list-plus' ),
	];

	foreach ( $actions as $act => $action_label ) {
		$class = 'button-primary';
		if ( 'approved' !== $act ) {
			$class = 'button-secondary';
		}

		if ( $act == $claim->status ) {
			continue;
		}
		printf(
			' <a href="%1$s" class="%2$s">%3$s</a> ',
			esc_url(
				add_query_arg(
					[
						'action' => $act,
						'id' => $claim->get_id(),
					],
					$current_url
				)
			),
			$class,
			$action_label
		); // WPCS: XSS ok.
	}
	?>
</div>
