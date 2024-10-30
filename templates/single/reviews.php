<?php
$listing = ListPlus\get_listing();
$current = get_query_var( 'r_paged' );
$limit = ListPlus()->settings->get( 'review_number', 20 );
ListPlus\CRUD\Review::query()
	->where( 'post_id', $listing->get_id() )
	->limit( 2 )
	->page( $current )
	->order_by( 'created_at', 'desc' );

$reviews = ListPlus\CRUD\Review::query()->find();


// var_dump( get_query_var( 'listing_router' ) );
// global $wp_rewrite;
// var_dump( $wp_rewrite->rules );
?>
<div class="l-reviews-wrapper">
	<ul class="l-reviews">
		<li class="curren-user-review">
			<div class="r-item">
				<div class="r-author">
					<?php
					$user = wp_get_current_user();
					$avatar = \get_avatar_url( $user->email );
					echo '<img src="' . $avatar . '" alt=""/>';
					?>
				</div>

				<div class="r-main">
					<div class="r-placeholder">
						<div class="r-rating">
							<div id="l-current-user-rating" data-link="<?php echo esc_url(
								$listing->get_view_link(
									'write-a-review-rating',
									[
										'rating' => '__NUMBER__',
									]
								)
							); ?>" data-rateit-step="1" class="rateit svg" <?php echo ListPlus\Helper::rating_atts( 35 ); ?>></div>
							<span class="l-rating-text"></span>
						</div>
						<div class="l-r-desc">
							<?php printf( __( 'Start your review of <a href="%1$s"><strong>%2$s</strong></a>', 'list-plus' ), $listing->get_view_link(), $listing->get_name() ); ?>
						</div>
					</div>
				</div>
			</div><!-- /.review-item -->
		</li>

	<?php foreach ( (array) $reviews as $review ) { ?>
		<li>
			<div class="r-item">
				<div class="r-author">
					<?php
					$avatar = \get_avatar_url( $review->email );
					echo '<img src="' . $avatar . '" alt=""/>';
					?>
				</div>

				<div class="r-main">
					<div class="r-meta">
						<div class="r-rating"><div class="rateit svg" data-preset="<?php echo ceil( $review->rating ); ?>" <?php echo ListPlus\Helper::rating_atts(); ?> data-rateit-value="<?php echo floatval( $review->rating ); ?>" data-rateit-ispreset="true" data-rateit-readonly="true"></div></div>
						<span class="sep">/</span>
						<span class="r-author"><?php echo esc_html( $review->get_name() ); ?></span>
						<span class="sep">/</span>
						<time datetime="<?php echo esc_attr( $review->created_at ); ?>" class="r-date"><?php echo date_i18n( get_option( 'date_format' ), strtotime( $review->created_at ) ); ?></time>
					</div>
					<div class="r-content">
						<?php echo apply_filters( 'listing_review_content', $review->content ); ?>
					</div>

				</div>
			</div><!-- /.review-item -->
		</li>
	<?php } ?>
	</ul>

	<?php
	$total_pages   = ListPlus\CRUD\Review::query()->get_max_pages();
	if ( $total_pages > 0 ) {

		?>
		<nav class="l-pagination l-review-pagination" data-id="<?php echo $listing->get_id(); ?>">
			<?php
				echo paginate_links(
					apply_filters(
						'listing_pagination_args',
						array( // WPCS: XSS ok.
							'base'         => $listing->get_review_link( '%#%' ),
							'format'       => '',
							'add_args'     => false,
							'current'      => max( 1, $current ),
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
