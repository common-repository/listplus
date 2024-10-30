<?php

$template = \ListPlus\get_theme_slug();

switch ( $template ) {
	case 'twentyten':
		echo '<div id="container" class="ls-container"><div id="content">';
		break;
	default:
		echo '<div id="primary" class="ls-container"><div id="content">';
		break;
}
