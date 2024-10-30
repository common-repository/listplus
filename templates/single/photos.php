<div class="l-single-photos">
<?php
foreach ( (array) $listing->media_files as $att_id ) {
	$url = wp_get_attachment_image_src( $att_id, 'medium' );
	if ( $url ) {
		$url = $url[0];
	}
	$full = wp_get_attachment_url( $att_id );
	if ( $url ) {
		?>
		<div class="l-photo-item">
			<div class="img-inner" data-thumb="<?php echo esc_url( $url ); ?>"  data-src="<?php echo esc_attr( $full ); ?>">
				<img src="<?php echo esc_url( $url ); ?>" alt=""/>
			</div>
		</div>
	<?php } ?>
<?php } ?>
</div>
