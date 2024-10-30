<?php

namespace ListPlus;

class Sidebar {
	public function __construct() {
		// Widget.
		add_action( 'widget_display_callback', [ __CLASS__, 'widget_display_callback' ], 1989, 3 );
		add_action( 'in_widget_form', [ __CLASS__, 'in_widget_form' ], 1989, 3 );
		add_filter( 'widget_update_callback', [ __CLASS__, 'widget_update_callback' ], 1989, 4 );
	}


	public static function widget_update_callback( $instance, $new_instance, $old_instance, $widget ) {
		$instance['lw_condition'] = isset( $new_instance['lw_condition'] ) ? sanitize_text_field( $new_instance['lw_condition'] ) : '';
		return $instance;
	}

	public static function in_widget_form( &$widget, &$return, $instance ) {
		$show = isset( $instance['lw_condition'] ) ? $instance['lw_condition'] : '';

		$conditions = [
			'all' => __( 'Show on all pages', 'list-plus' ),
			'listings' => __( 'Show on lising archive', 'list-plus' ),
			'single' => __( 'Show on single listing', 'list-plus' ),
			'show_both' => __( 'Show on single & archive listing', 'list-plus' ),
			'hide_on_listings' => __( 'Hide on listing archive', 'list-plus' ),
			'hide_on_single' => __( 'Hide on single listing', 'list-plus' ),
			'hide_both' => __( 'Hide on single & archive listing', 'list-plus' ),
		];
		?>
		<div class="ls-display-cond" style="border: 1px dashed #a00; padding: 5px 15px; display: block; margin: 0 0px 15px;">
			<p>
				<!-- <label for="<?php echo $widget->get_field_id( 'lw_condition' ); ?>">
					<?php _e( 'Display', 'list-plus' ); ?>
				</label> -->
				<select id="<?php echo $widget->get_field_id( 'lw_condition' ); ?>" name="<?php echo $widget->get_field_name( 'lw_condition' ); ?>">
					<?php foreach ( $conditions as $k => $label ) { ?>
						<option <?php selected( $show, $k ); ?> value="<?php echo esc_attr( $k ); ?>"><?php echo $label; ?></option>
					<?php } ?>
				</select>
			</p>
		</div>
		<?php
	}
	public static function widget_display_callback( $instance, $widget, $args ) {
		if ( isset( $instance['lw_condition'] ) ) {
			switch ( $instance['lw_condition'] ) {
				case 'listings':
					if ( ! \ListPlus()->query->is_listing_archives() ) {
						return false;
					}
					break;
				case 'single':
					if ( ! \is_singular( 'listing' ) ) {
						return false;
					}
					break;
				case 'show_both':
					if ( ! \is_singular( 'listing' ) && ! \ListPlus()->query->is_listing_archives() ) {
						return false;
					}
					break;
				case 'hide_on_listings':
					if ( \ListPlus()->query->is_listing_archives() ) {
						return false;
					}
					break;
				case 'hide_on_single':
					if ( \is_singular( 'listing' ) ) {
						return false;
					}
					break;
				case 'hide_both':
					if ( \is_singular( 'listing' ) || \ListPlus()->query->is_listing_archives() ) {
						return false;
					}
					break;
			}
		}

		return $instance;
	}

}


new Sidebar();
