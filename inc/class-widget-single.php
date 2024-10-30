<?php

/**
 * Class WPDocs_New_Widget
 */
class ListPlus_Listing_Details_Widget extends \WP_Widget {

	/**
	 * Constructs the new widget.
	 *
	 * @see WP_Widget::__construct()
	 */
	public function __construct() {
		// Instantiate the parent object.
		parent::__construct(
			'listings-single',
			__( 'Listing Details', 'list-plus' ),
			[
				'description' => __( 'Display listing details in your single listing.', 'list-plus' ),
				'classname' => 'widget-listings-single',
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
		if ( ! is_singular( 'listing' ) ) {
			return;
		}

		$display = new \ListPlus\Listing_Display();
		$sidebar_fields = $display->get_listing()->get_listing_type()->single_sidebar;
		$sidebar_fields = json_decode( $sidebar_fields, true );
		$instance = wp_parse_args(
			$instance,
			[
				'title' => '',
				'widget' => '',
				'hide_heading' => '',
			]
		);

		if ( 'yes' == $instance['hide_heading'] ) {
			$display->no_heading = true;
		}

		if ( 'yes' == $instance['widget'] ) {
			$display->no_heading = true;
			foreach ( $sidebar_fields as $key => $field ) {
				$field = $display->parse_args( $field );
				$text = $display->get_item_heading( $field );

				if ( 'price' == $field['id'] ) {
					if ( ! $display->listing->support_price() ) {
						continue;
					}
				}

				if ( 'price_range' == $field['id'] ) {
					if ( ! $display->listing->support_price_range() ) {
						continue;
					}
				}

				if ( $text ) {
					$icon = $display->get_icon( $field, 'i' );
					echo $args['before_title'] . $icon . esc_html( $text ) . $args['after_title'];
				}
				$display->render( [ $field ] );
			}
		} else {
			if ( ! empty( $sidebar_fields ) ) {
				echo $args['before_widget'];
				$title = isset( $instance['title'] ) && $instance['title'] ? $instance['title'] : '';
				echo '<div class="listplus-area">';
				if ( $title ) {
					echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
				}

				$display->render( $sidebar_fields );
				echo '</div>';
				echo $args['after_widget'];
			}
		}

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
		$instance['widget'] = sanitize_text_field( $new_instance['widget'] );
		$instance['hide_heading'] = sanitize_text_field( $new_instance['hide_heading'] );
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
				'widget' => '',
				'hide_heading' => '',
			]
		);
		$title        = esc_attr( $instance['title'] );
		$widget       = esc_attr( $instance['widget'] );
		$hide_heading = esc_attr( $instance['hide_heading'] );

		?>
	<p>
		<label>
			<?php _e( 'Title:', 'list-plus' ); ?>
		</label>
		<input class="widefat"  name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" />
	</p>
	<p>
		<label>
			<input <?php checked( $widget, 'yes' ); ?> class="widefat"  name="<?php echo $this->get_field_name( 'widget' ); ?>" type="checkbox" value="yes" />
			<?php _e( 'Display items as widgets.', 'list-plus' ); ?>
		</label>
	</p>
	<p>
		<label>
			<input class="widefat"  <?php checked( $hide_heading, 'yes' ); ?>  name="<?php echo $this->get_field_name( 'hide_heading' ); ?>" type="checkbox" value="yes" />
			<?php _e( 'Hide item heading', 'list-plus' ); ?>
		</label>
	</p>
		<?php
	}
}
