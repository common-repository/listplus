<?php

namespace ListPlus;

class Error extends \WP_Error {
	public function reset() {
		$this->errors = [];
	}

	public function to_html() {
		
		if ( ! $this->has_errors() ) {
			return false;
		}

		$html = '<div class="lp-errors">';
		foreach ( (array) $this->get_error_messages() as $code => $message ) {
			$html .= '<div class="error-msg">' . $message . '</div>';
		}
		$html .= '</div>';
		return $html;
	}
}
