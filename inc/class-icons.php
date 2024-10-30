<?php

namespace ListPlus;

class Icons {

	public $icons = null;

	public function __construct() {

	}

	public function the_icon_svg( $icon_id, $wrapper = false ) {
		$this->get_icons();
		if ( isset( $this->icons[ $icon_id ] ) ) {
			if ( $wrapper ) {
				$class = ( true === $wrapper ) ? 'l-icon f-icon' : $wrapper;
				return '<span class="' . esc_attr( $class ) . '">' . $this->icons[ $icon_id ]['svg'] . '</span>';
			}
			return $this->icons[ $icon_id ]['svg'];
		}
		return '';
	}

	public function get_icons() {
		if ( $this->icons ) {
			return $this->icons;
		}

		$icons = [];
		$icon_path = LISTPLUS_PATH . '/assets/icons/icons-svg.json';
		if ( \file_exists( $icon_path ) && \is_readable( $icon_path ) ) {
			$content = \file_get_contents( $icon_path );
			$icons = \json_decode( $content, true );
		}

		$this->icons = \apply_filters( 'listplus_get_icons', $icons );
		return $this->icons;

		// $icon_path = LISTPLUS_PATH . '/inc/fontawesome5.php';
		// $this->icons = include $icon_path;
		// $this->icons = \apply_filters( 'listplus_get_icons', $this->icons );
		// return $this->icons;
	}


}
