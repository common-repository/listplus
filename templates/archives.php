<?php
/**
 * Dont forget id `#l-listings` for fixed the listing map.
 */
?>
<div class="l-listings-wrapper">
	<div id="l-listings" class="l-listings">
	<?php
	// echo var_dump( $found_rows );
	// echo $request;
	global $listing;

	if ( ! empty( $listings ) ) {
		foreach ( $listings as $index => $listing ) {
			$display = new ListPlus\Listing_Display( $listing );
			$classes = [
				'l-' . $listing->get_id(),
				'l-loop-item',
				'status-' . $listing->post_status,
			];
			?>
		<div data-index="<?php echo (int) $index; ?>" class="<?php echo esc_attr( join( ' ', $classes ) ); ?>">
			<div class="l-loop-thumb <?php echo has_post_thumbnail( $listing->get_id() ) ? 'has-thumb' : 'no-thumb'; ?>">
				<?php echo get_the_post_thumbnail( $listing->get_id() ); ?>
			</div>
			<div class="l-loop-main">
				<div class="l-loop-top l-table">
					<div class="l-cell l-tm">
						<?php $display->loop( 'title_link' ); ?>
						<div class="l-review-summary"><?php $display->loop( 'review_sumary' ); ?></div>
						<div class="l-loop-meta">
							<?php $display->loop( 'price' ); ?>
							<?php $display->loop( 'price_range' ); ?>
							<?php $display->loop( 'categories_short' ); ?>
						</div>
					</div>
					<div class="l-cell l-im">
						<?php $display->loop( 'phone' ); ?>
						<?php $display->loop( 'address' ); ?>
					</div>
				</div>
				<?php $display->loop( 'highlighs_short' ); ?>
				<?php $display->loop( 'excerpt' ); ?>
			</div>
		</div>
			<?php
		}
	} else {
		?>
	<p class="l-not-found"><?php esc_html_e( 'Sorry, but nothing matched your search terms. Please try again with different keywords.', 'list-plus' ); ?></p>
		<?php
	}
	?>
	</div>

<?php
if ( $total_pages > 0 ) {
	?>
		<nav class="l-pagination" data-id="<?php echo $listing->get_id(); ?>">
		<?php
			echo paginate_links(
				apply_filters(
					'listing_pagination_args',
					array( // WPCS: XSS ok.
						'base'         => ListPlus()->request->get_paging_base(),
						'format'       => ListPlus()->request->get_paging_format(),
						'add_args'     => false,
						'current'      => max( 1, ListPlus()->request->get_paged() ),
						'total'        => $total_pages,
						'prev_text'    => __( 'Previous', 'list-plus' ),
						'next_text'    => __( 'Next', 'list-plus' ),
						'type'         => 'list',
						'end_size'     => 1,
						'mid_size'     => 2,
					)
				)
			);
		?>
		</nav>
	<?php } ?>
</div>
