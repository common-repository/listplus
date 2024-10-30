<?php

$new_link = get_permalink();

if ( ! isset( $alllowed_types ) ) {
	$alllowed_types = [];
}

if ( $can_edit ) {
	?>
	<div class="lp-warning"><?php _e( 'You have not permission to edit this listing.', 'list-plus' ); ?></div>
	<?php
}

echo '<p class="l-select-title">' . __( 'Select listing type to continue...', 'list-plus' ) . '</p>';
echo '<div class="l-select-listing">';
foreach ( $active_types as $active_type ) {

	$id = $active_type->get_id();
	if ( ! empty( $alllowed_types ) && ! isset( $alllowed_types[ $id ] ) ) {
		$link = \add_query_arg( [ 'select-plan' => $active_type->get_slug() ], $new_link );
		$text = _x( 'Purchase', 'purchase to submit this listing', 'list-plus' );
	} else {
		$link = \add_query_arg( [ 'listing_type' => $active_type->get_slug() ], $new_link );
		$text = __( 'Add', 'list-plus' );
	}

	?> 
	<div class="l-select-type">
		<a  href="<?php echo esc_attr( $link ); ?>" class="l-s-type-inner">
			<?php echo $active_type->get_icon_html(); ?>
			<span class="l-s-type-title"><?php echo esc_html( $active_type['name'] ); ?></span>
			<span class="l-s-add" ><?php echo $text; ?></span>
		</a>
	</div>
<?php }
echo '</div>';
