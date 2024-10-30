<?php

use  ListPlus\Helper ;
use  ListPlus\Post_Types ;
use  ListPlus\CRUD\Listing_Category ;
use  ListPlus\CRUD\Listing_Type ;
$item = ListPlus\get_editing_listing();
$type = \ListPlus\get_type_for_editing_listing();
$listing_type = $type->get_slug();
echo  '<h3 class="lp-before-form-title">' . sprintf( __( 'New %s', 'list-plus' ), ( $type->singular_name ? $type->singular_name : $type->name ) ) . '</h3>' ;
?>
<form class="lp-form lp-main-form" action="" method="post" enctype="multipart/form-data">
	<?php 
$item->get_gallery();
wp_referer_field();
ListPlus()->form->set_data( $item->to_array() );
ListPlus()->form->set_validate_field( true );
ListPlus()->form->hidden( [
    'ID'           => $item->get_id(),
    'action'       => 'listplus_save_listing',
    'listing_type' => $listing_type,
] );
$fields = $type->get_fields();
ListPlus()->form->render( [
    'fields' => $fields,
] );
?>
	<input type="submit" class="button button-primary" value="<?php 
_e( 'Save', 'list-plus' );
?>">
</form>
