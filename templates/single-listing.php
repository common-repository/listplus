<?php
$layout_class = ListPlus()->template->get_layout_class();
echo '<div class="' . $layout_class . '">';

do_action( 'listplus_listing_content' );

echo '</div>';
