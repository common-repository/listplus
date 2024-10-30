<?php

namespace ListPlus;

function the_modal( $args ) {
	$args = wp_parse_args(
		$args,
		[
			'id' => '',
			'class' => '',
			'content' => '',
			'title' => '',
			'seconday' => '',
			'primary' => '',
			'primary_type' => 'button',
		]
	);
	?>
	<div class="l-modal <?php echo esc_attr( $args['class'] ); ?>" id="<?php echo esc_attr( $args['id'] ); ?>">
		<div class="lm-drop"></div>
		<div class="lm-wrapper">
			<button class="lm-close"></button>
			<?php if ( $args['title'] ) { ?>
			<header class="lm-header">
				<h2><?php echo esc_html( $args['title'] ); // WPCS: XSS ok. ?></h2>
			</header>
			<?php } ?>
			<div class="lm-content">
				<?php echo $args['content']; // WPCS: XSS ok. ?>
			</div>
			<?php if ( $args['seconday'] || $args['primary'] ) { ?>
			<footer class="lm-footer">
				<?php if ( $args['seconday'] ) { ?>
			  <button type="button" class="action l-btn-seconday"><?php echo $args['seconday']; // WPCS: XSS ok. ?></button>
				<?php } ?>
				<?php if ( $args['primary'] ) { ?>
			  <button type="<?php echo ( $args['primary_type'] ) ? esc_attr( $args['primary_type'] ) : 'button'; ?>" class="action l-btn-primary"><?php echo $args['primary']; // WPCS: XSS ok. ?></button>
				<?php } ?>
			</footer>
			<?php } ?>
		</div>
	</div>
	<?php
}


