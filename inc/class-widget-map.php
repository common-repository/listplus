<?php

/**
 * Class WPDocs_New_Widget
 */
class ListPlus_Map_Widget extends \WP_Widget {

	/**
	 * Constructs the new widget.
	 *
	 * @see WP_Widget::__construct()
	 */
	public function __construct() {
		// Instantiate the parent object.
		parent::__construct(
			'listings-map',
			__( 'Listings Map', 'list-plus' ),
			[
				'description' => __( 'Display Listings Map', 'list-plus' ),
				'classname' => 'widget-listings-map',
			]
		);
	}

	/**
	 * The widget's HTML output.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Display arguments including before_title, after_title,
	 *                        before_widget, and after_widget.
	 * @param array $instance The settings for the particular instance of the widget.
	 */
	public function widget( $args, $instance ) {
		if ( ! ListPlus()->query->is_listing_archives() ) {
			return;
		}
		$instance = wp_parse_args(
			$instance,
			[
				'title' => '',
				'height' => '',
				'sticky_type' => '',
			]
		);
		$instance['height'] = trim( $instance['height'] );
		preg_match_all( '/^(\d+|\d*\.\d+)(\w*|%*)$/', $instance['height'], $matches );
		$unit = '';
		$number = '';
		if ( $matches && isset( $matches[1][0] ) ) {
			$number = $matches[1][0];
			$unit = $matches[2][0];
		} else {
			$number = floatval( $instance['height'] );
			$unit = 'px';
		}
		if ( ! in_array( $unit, [ 'em', 'px', 'rem', 'em', '%' ], true ) ) {
			$unit = 'px';
		}
		if ( ! $unit ) {
			$unit  = 'px';
		}

		if ( ! $number || $number <= 0 ) {
			$number = '150';
			$unit = '%';
		}

		$type = $instance['sticky_type'];
		// Sticky widget or sidebar.
		echo $args['before_widget'];
		echo '<div class="listings-map-wrapper" style="padding-top: ' . $number . $unit . ';">';
		echo '<div class="listings-map inside-widget" data-sticky="' . esc_attr( $type ) . '"></div>';
		echo '</div>';
		echo $args['after_widget'];
	}

	/**
	 * The widget update handler.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance The new instance of the widget.
	 * @param array $old_instance The old instance of the widget.
	 * @return array The updated instance of the widget.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = sanitize_text_field( $new_instance['title'] );
		$instance['height'] = sanitize_text_field( $new_instance['height'] );
		$instance['sticky_type'] = sanitize_text_field( $new_instance['sticky_type'] );
		return $instance;
	}

	/**
	 * Output the admin widget options form HTML.
	 *
	 * @param array $instance The current widget settings.
	 */
	public function form( $instance ) {
		$instance = wp_parse_args(
			$instance,
			[
				'title' => '',
				'height' => '',
				'sticky_type' => '',
			]
		);
		$sticky_type = esc_attr( $instance['sticky_type'] );
		$title       = esc_attr( $instance['title'] );
		$height      = esc_attr( $instance['height'] );
		if ( ! $sticky_type ) {
			$sticky_type  = 'sidebar';
		}
		?>
	<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>">
			<?php _e( 'Title:', 'list-plus' ); ?>
		</label>
		<input class="widefat" for="<?php echo $this->get_field_id( 'title' ); ?>"  name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" />
	</p>
	<p>
		<label for="<?php echo $this->get_field_id( 'height' ); ?>">
			<?php _e( 'Height:', 'list-plus' ); ?>
		</label>
		<input class="small-text" style="width: 65px" size="10" for="<?php echo $this->get_field_id( 'height' ); ?>"  name="<?php echo $this->get_field_name( 'height' ); ?>" type="text" value="<?php echo $height; ?>" />
	</p>
	<p>
		<label>
			<input <?php checked( $sticky_type, 'sidebar' ); ?> class="widefat"  name="<?php echo $this->get_field_name( 'sticky_type' ); ?>" type="radio" value="sidebar" />
			<?php _e( 'Sticky sidebar', 'list-plus' ); ?>
		</label>
	</p>
	<p>
		<label>
			<input class="widefat"  <?php checked( $sticky_type, 'widget' ); ?>  name="<?php echo $this->get_field_name( 'sticky_type' ); ?>" type="radio" value="widget" />
			<?php _e( 'Sticky this widget', 'list-plus' ); ?>
		</label>
	</p>
	<p>
		<label>
			<input class="widefat"  <?php checked( $sticky_type, 'no' ); ?> name="<?php echo $this->get_field_name( 'sticky_type' ); ?>" type="radio" value="no" />
			<?php _e( 'No sticky', 'list-plus' ); ?>
		</label>
	</p>
		<?php
	}
}
