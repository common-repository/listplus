<?php
// ListPlus()->filter->form();
$slider_svg = ListPlus()->icons->the_icon_svg( 'equalizer2' );
?>
<div class="l-filter-wrapper">
	<span href="#" class="l-trigger-filter l-btn-primary f-icon"><?php echo $slider_svg; ?> <span><?php _e( 'Filter', 'list-plus' ); ?></span></span>
	<?php
	ListPlus()->filter->form_main();
	ListPlus()->filter->form_more();
	?>
</div>


