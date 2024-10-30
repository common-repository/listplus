<?php

/**
 * Class WPDocs_New_Widget
 */
class ListPlus_Filter_Widget extends \WP_Widget {

	/**
	 * Constructs the new widget.
	 *
	 * @see WP_Widget::__construct()
	 */
	public function __construct() {
		// Instantiate the parent object.
		parent::__construct(
			'listings-filter',
			__( 'Listing Filter', 'list-plus' ),
			[
				'description' => __( 'Display listing filter form.', 'list-plus' ),
				'classname' => 'widget-listings-filter',
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
		$instance = wp_parse_args(
			$instance,
			[
				'title' => '',
				'form_type' => '',
			]
		);

		echo $args['before_widget'];
		$title = isset( $instance['title'] ) && $instance['title'] ? $instance['title'] : '';
		echo '<div class="listplus-area">';
		if ( $title ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
		}

		if ( 'mini' == $instance['form_type'] ) {
			ListPlus()->filter->form_main();
		} else {
			\ListPlus()->filter->form( 'mobile', true );
		}

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
		$instance['form_type'] = sanitize_text_field( $new_instance['form_type'] );
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
				'form_type' => '',
			]
		);
		$title  = esc_attr( $instance['title'] );
		$form_type = esc_attr( $instance['form_type'] );
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>">
				<?php _e( 'Title:', 'list-plus' ); ?>
			</label>
			<input class="widefat" for="<?php echo $this->get_field_id( 'title' ); ?>"  name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'form_type' ); ?>">
				<?php _e( 'Form Type', 'list-plus' ); ?>
			</label>
			<select id="<?php echo $this->get_field_id( 'form_type' ); ?>" name="<?php echo $this->get_field_name( 'form_type' ); ?>">
				<option value="full"><?php _e( 'Full', 'list-plus' ); ?></option>
				<option <?php selected( $form_type, 'mini' ); ?> value="mini"><?php _e( 'Mini', 'list-plus' ); ?></option>
			</select>
		</p>
		<?php
	}
}
