<h1 class="wp-heading-inline">
	<?php _e( 'Tools', 'list-plus' ); ?>
</h1>

<hr class="wp-header-end">

<form action="" class="l-export-form lp-form mt-top-2 lp-max">
	<div class="form-box">
		<h3><?php _e( 'Export', 'list-plus' ); ?></h3>
		<div class="inner">
			<div class="ff">
				<?php
				$rows = \ListPlus\CRUD\Listing_Type::get_all_active();
				?>
				<select id='listing_type_id' name="type_id">
					<option value=""><?php esc_html_e( 'Select Listing Type', 'list-plus' ); ?></option>
					<?php
					foreach ( $rows as $row ) {
						echo '<option value="' . esc_attr( $row->term_id ) . '">' . esc_html( $row->name ) . '</option>';
					}
					?>
				</select>
			</div>
			
			<div class="ff">
				<button type="button" class="action-export-listings  button-primary button" data-doing-txt="<?php esc_attr_e( 'Exporting...', 'list-plus' ); ?>"><?php _e( 'Export', 'list-plus' ); ?></button>
			</div>
			<div class="l-ie-progress ff">
				
			</div>
		</div>
	</div>
</form>

<?php
$file = get_transient( 'listplus_importing_file' );
delete_transient( 'listplus_importing_file' );
?>
<form method="post" enctype="multipart/form-data" action="<?php echo add_query_arg( [ 'action' => 'listplus_import_file' ], admin_url( 'admin-ajax.php' ) ); ?>" class="l-export-form lp-form mt-top-2 lp-max">
	<div class="form-box">
		<h3><?php _e( 'Import', 'list-plus' ); ?></h3>
		<div class="inner">
			<?php if ( $file && 'error' == $file ) { ?>
			<div class="lp-warning">
				<?php _e( 'An error occur while upload file, please try again.', 'list-plus' ); ?>
			</div>
			<?php } ?>
			<div class="ff">
				<input type="file" required accept=".xlsx,.xls" name="import_file"/>
				<p class="description"><?php _e( 'Select an excel file to import.', 'list-plus' ); ?></p>
			</div>
			<div class="ff">
				<button type="submit" data-start="<?php echo (int) $file; ?>" class="action-import-listings button-secondary button" data-doing-txt="<?php esc_attr_e( 'Importing...', 'list-plus' ); ?>"><?php _e( 'Import', 'list-plus' ); ?></button>
			</div>
			<div class="l-ie-progress ff"></div>
		</div>
	</div>
	<?php wp_referer_field(); ?>
</form>