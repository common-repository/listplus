<?php

namespace ListPlus;

class Taxonomies implements \ArrayAccess {
	private $data = [];
	private $prefix = 'ltx_';
	protected $builtins = [ 'listing_type', 'listing_cat', 'listing_region' ];
	public function __construct() {
		$this->get_all_custom();
		add_action( 'init', [ $this, 'registers' ] );

		// manage_{tax}_custom_column
		add_action( 'manage_listing_tax_custom_column', [ $this, 'listing_tax_columns' ], 1989, 3 );
		// manage_edit-{tax}_columns
		add_filter( 'manage_edit-listing_tax_columns', [ $this, 'add_listing_tax_columns' ], 1989 );

		$all_tax = wp_list_pluck( $this->get_all(), 'slug' );
		$all_tax = \array_merge( [ 'listing_cat', 'listing_tax', 'listing_region' ], $all_tax );

		foreach ( $all_tax as $tax ) {
			add_action( 'manage_' . $tax . '_custom_column', [ __CLASS__, 'taxonomy_columns' ], 1989, 3 );
			add_filter( 'manage_edit-' . $tax . '_columns', [ __CLASS__, 'add_taxonomy_columns' ], 1989 );

			add_action( $tax . '_edit_form_fields', [ __CLASS__, 'taxonomy_edit_custom_meta_fields' ], 1989, 2 );
			add_action( $tax . '_add_form_fields', [ __CLASS__, 'taxonomy_add_custom_meta_fields' ], 1989, 2 );
			add_action( 'edited_' . $tax, [ __CLASS__, 'save_taxonomy_custom_meta_fields' ], 1989, 2 );
			add_action( 'create_' . $tax, [ __CLASS__, 'save_taxonomy_custom_meta_fields' ], 1989, 2 );

			add_filter( $tax . '_row_actions', [ $this, 'tax_row_actions' ], 1989, 2 );
		}

		// $actions = apply_filters( "{$taxonomy}_row_actions", $actions, $tag );
		// Filter term link.
		// apply_filters( 'term_link', $termlink, $term, $taxonomy );
		add_filter( 'term_link', [ $this, 'term_link' ], 1989, 3 );
	}

	public function tax_row_actions( $actions, $term ) {
		if ( 1 == $this->is_listing_tax( $term->taxonomy ) ) {
			$link = \ListPlus()->request->get_term_link( $term );
			$actions['view_custom'] = '<a href="' . $link . '">' . __( 'View', 'list-plus' ) . '</a>';
		}
		return $actions;
	}

	public function get_builtins() {
		return $this->builtins;
	}

	public function term_link( $termlink, $term, $taxonomy ) {
		if ( 1 == $this->is_listing_tax( $term->taxonomy ) ) {
			return \ListPlus()->request->get_term_link( $term );
		}
		return $termlink;
	}
	public static function taxonomy_add_custom_meta_fields() {
		global $taxonomy;
		\ListPlus()->form->reset();

		if ( 'listing_tax' == $taxonomy ) {

			?>
			<div class="form-field">
				<label for="term_meta_singular"><?php _e( 'Singular', 'list-plus' ); ?></label>
				<input name="term_meta_singular" id="term_meta_singular" type="text" value="" size="40">
			</div>
			<div class="form-field">
				<label for="term_meta_plural"><?php _e( 'Plural', 'list-plus' ); ?></label>
				<input name="term_meta_plural" id="term_meta_plural" type="text" value="" size="40">
			</div>
			<div class="form-field">
				<label for="term_meta_frontend_name"><?php _e( 'Frontend Name', 'list-plus' ); ?></label>
				<input name="term_meta_frontend_name" id="term_meta_frontend_name" type="text" value="" size="40">
			</div>
			<div class="form-field">
				<label for="term_meta_filter_label"><?php _e( 'Filter Label', 'list-plus' ); ?></label>
				<input name="term_meta_filter_label" id="term_meta_filter_label" type="text" value="" size="40">
			</div>
			<div class="form-field">
				<label for="term_meta_hierarchical"><input name="term_meta_hierarchical" id="term_meta_hierarchical" type="checkbox" value="yes"> <?php _e( 'Hierarchical', 'list-plus' ); ?></label>
			</div>
			<div class="form-field">
				<label>
					<input name="term_meta_exclude_filter" id="term_meta_exclude_filter" type="checkbox" value="yes" >
					<?php _e( 'Check this to not show on filter.', 'list-plus' ); ?>
				</label>
			</div>
			<div class="form-field">
				<label><input name="term_meta_allow_new" id="term_meta_allow_new" type="checkbox" value="yes" > <?php _e( 'Allow user add terms.', 'list-plus' ); ?></label>
			</div>
			<div class="form-field">
				<label><input name="term_meta_custom_value" id="term_meta_custom_value" type="checkbox" value="yes" > <?php _e( 'Additional info.', 'list-plus' ); ?></label>
			</div>

		<?php } ?>
		<div class="form-field">
			<label for="term_meta_icon"><?php _e( 'Icon', 'list-plus' ); ?></label>
			<?php
				\ListPlus()->form->the_field(
					[
						'type' => 'icon',
						'input_only' => true,
						'name' => 'term_meta_icon',
						'atts' => [
							'placeholder' => __( 'Select an icon', 'list-plus' ),
						],
					]
				);
			?>
		</div>
		<div class="form-field">
			<label for="term_meta_icon"><?php _e( 'Image', 'list-plus' ); ?></label>

			<?php
			\ListPlus()->form->the_field(
				[
					'type' => 'wp_upload',
					'input_only' => true,
					'name' => 'term_meta_image',
				]
			);
			?>
		</div>
		<?php
	}

	public static function taxonomy_edit_custom_meta_fields( $term ) {
		\ListPlus()->form->reset();
		$icon           = get_term_meta( $term->term_id, '_icon', true );
		$image          = get_term_meta( $term->term_id, '_image', true );
		$singular       = get_term_meta( $term->term_id, '_singular', true );
		$plural         = get_term_meta( $term->term_id, '_plural', true );
		$frontend_name  = get_term_meta( $term->term_id, '_frontend_name', true );
		$filter_label   = get_term_meta( $term->term_id, '_filter_label', true );
		$hierarchical   = get_term_meta( $term->term_id, '_hierarchical', true );
		$exclude_filter = get_term_meta( $term->term_id, '_exclude_filter', true );
		$allow_new      = get_term_meta( $term->term_id, '_allow_new', true );
		$custom_value      = get_term_meta( $term->term_id, '_custom_value', true );

		if ( 'listing_tax' == $term->taxonomy ) {
			?>
			<tr class="form-field">
				<th scope="row" valign="top">
					<label for="term_meta_singular"><?php _e( 'Singular', 'list-plus' ); ?></label>
				</th>
				<td>
				<input name="term_meta_singular" id="term_meta_singular" type="text" value="<?php echo esc_attr( $singular ); ?>" size="40">
				</td>
			</tr>

			<tr class="form-field">
				<th scope="row" valign="top">
					<label for="term_meta_plural"><?php _e( 'Plural', 'list-plus' ); ?></label>
				</th>
				<td>
					<input name="term_meta_plural"  value="<?php echo esc_attr( $plural ); ?>" id="term_meta_plural" type="text" value="" size="40">
				</td>
			</tr>
			<tr class="form-field">
				<th scope="row" valign="top">
					<label for="term_meta_frontend_name"><?php _e( 'Frontend Display Name', 'list-plus' ); ?></label>
				</th>
				<td>
					<input name="term_meta_frontend_name" value="<?php echo esc_attr( $frontend_name ); ?>" id="term_meta_frontend_name" type="text" value="" size="40">
				</td>
			</tr>
			<tr class="form-field">
				<th scope="row" valign="top">
					<label for="term_meta_filter_label"><?php _e( 'Filter Label', 'list-plus' ); ?></label>
				</th>
				<td>
					<input name="term_meta_filter_label" value="<?php echo esc_attr( $filter_label ); ?>" id="term_meta_filter_label" type="text" value="" size="40">
				</td>
			</tr>
			<tr class="form-field">
				<th scope="row" valign="top">
					<label for="term_meta_hierarchical"><?php _e( 'Hierarchical', 'list-plus' ); ?></label>
				</th>
				<td>
					<input name="term_meta_hierarchical" id="term_meta_hierarchical" <?php checked( $hierarchical, 'yes' ); ?> type="checkbox" value="yes" >
				</td>
			</tr>
			<tr class="form-field">
				<th scope="row" valign="top">
					<label for="term_meta_exclude_filter"><?php _e( 'Exclude filter', 'list-plus' ); ?></label>
				</th>
				<td>
					<label>
						<input name="term_meta_exclude_filter" id="term_meta_exclude_filter" <?php checked( $exclude_filter, 'yes' ); ?> type="checkbox" value="yes" >
						<?php _e( 'Check this to not show on filter.', 'list-plus' ); ?>
					</label>
				</td>
			</tr>
			<tr class="form-field">
				<th scope="row" valign="top">
					<label for="term_meta_allow_new"><?php _e( 'Allow add new', 'list-plus' ); ?></label>
				</th>
				<td>
					<label><input name="term_meta_allow_new" id="term_meta_allow_new" <?php checked( $allow_new, 'yes' ); ?> type="checkbox" value="yes" > <?php _e( 'Allow user add terms', 'list-plus' ); ?></label>
				</td>
			</tr>
			<tr class="form-field">
				<th scope="row" valign="top">
					<label for="term_meta_custom_value"><?php _e( 'Additional info', 'list-plus' ); ?></label>
				</th>
				<td>
					<label><input name="term_meta_custom_value" id="term_meta_custom_value" <?php checked( $custom_value, 'yes' ); ?> type="checkbox" value="yes" > <?php _e( 'Check this to allow add custom additional info', 'list-plus' ); ?></label>
				</td>
			</tr>

		<?php } ?>

		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="term_meta_icon"><?php _e( 'Icon', 'list-plus' ); ?></label>
			</th>
			<td>
			<?php
				\ListPlus()->form->the_field(
					[
						'type' => 'icon',
						'input_only' => true,
						'name' => 'term_meta_icon',
						'value' => $icon,
						'atts' => [
							'placeholder' => __( 'Select an icon', 'list-plus' ),
						],
					]
				);
			?>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="term_meta_image"><?php _e( 'Image', 'list-plus' ); ?></label>
			</th>
			<td>
			<?php
			\ListPlus()->form->the_field(
				[
					'type' => 'wp_upload',
					'input_only' => true,
					'value' => $image,
					'name' => 'term_meta_image',
				]
			);
			?>
			</td>
		</tr>
		<?php
	}

	public static function save_taxonomy_custom_meta_fields( $term_id ) {
		$term = get_term( $term_id );

		$icon = isset( $_POST['term_meta_icon'] ) ? sanitize_text_field( wp_unslash( $_POST['term_meta_icon'] ) ) : '';
		$image = isset( $_POST['term_meta_image'] ) ? sanitize_text_field( wp_unslash( $_POST['term_meta_image'] ) ) : '';

		update_term_meta( $term_id, '_icon', $icon );
		update_term_meta( $term_id, '_image', $image );

		if ( 'listing_tax' == $term->taxonomy ) {

			$singular       = isset( $_POST['term_meta_singular'] ) ? sanitize_text_field( wp_unslash( $_POST['term_meta_singular'] ) ) : '';
			$plural         = isset( $_POST['term_meta_plural'] ) ? sanitize_text_field( wp_unslash( $_POST['term_meta_plural'] ) ) : '';
			$frontend_name  = isset( $_POST['term_meta_frontend_name'] ) ? sanitize_text_field( wp_unslash( $_POST['term_meta_frontend_name'] ) ) : '';
			$filter_label   = isset( $_POST['term_meta_filter_label'] ) ? sanitize_text_field( wp_unslash( $_POST['term_meta_filter_label'] ) ) : '';
			$hierarchical   = isset( $_POST['term_meta_hierarchical'] ) ? sanitize_text_field( wp_unslash( $_POST['term_meta_hierarchical'] ) ) : '';
			$exclude_filter = isset( $_POST['term_meta_exclude_filter'] ) ? sanitize_text_field( wp_unslash( $_POST['term_meta_exclude_filter'] ) ) : '';
			$allow_new      = isset( $_POST['term_meta_allow_new'] ) ? sanitize_text_field( wp_unslash( $_POST['term_meta_allow_new'] ) ) : '';
			$custom_value      = isset( $_POST['term_meta_custom_value'] ) ? sanitize_text_field( wp_unslash( $_POST['term_meta_custom_value'] ) ) : '';

			update_term_meta( $term_id, '_singular', $singular );
			update_term_meta( $term_id, '_plural', $plural );
			update_term_meta( $term_id, '_frontend_name', $frontend_name );
			update_term_meta( $term_id, '_filter_label', $filter_label );
			update_term_meta( $term_id, '_hierarchical', $hierarchical );
			update_term_meta( $term_id, '_exclude_filter', $exclude_filter );
			update_term_meta( $term_id, '_allow_new', $allow_new );
			update_term_meta( $term_id, '_custom_value', $custom_value );
		}

	}

	public static function add_taxonomy_columns( $columns ) {
		$new_columns = [];
		if ( isset( $columns['cb'] ) ) {
			$new_columns['cb'] = $columns['cb'];
		}

		$new_columns['thumb'] = '<span class="dashicons dashicons-format-image"></span>';
		foreach ( $columns as $k => $title ) {
			if ( ! isset( $new_columns[ $k ] ) ) {
				$new_columns[ $k ] = $columns[ $k ];
			}
		}

		return $new_columns;
	}

	public static function taxonomy_columns( $deprecated, $column_name, $term_id ) {

		switch ( $column_name ) {
			case 'thumb':
				$icon  = get_term_meta( $term_id, '_icon', true );
				$image  = get_term_meta( $term_id, '_image', true );
				if ( $icon ) {
					echo '<span class="ls-svg-icon">' . \ListPlus()->icons->the_icon_svg( $icon ) . '</span>';
				} elseif ( $image && $src = wp_get_attachment_thumb_url( $image ) ) {
					echo '<img src="' . esc_url( $src ) . '" alt="">';
				} else {
					echo '<span class="ls-placeholder-img"><span class="dashicons dashicons-format-image"></span></span>';
				}
				break;
			// case 'terms':
			// echo 'dsadas444';
			// break;
		}

	}

	public function add_listing_tax_columns( $columns ) {
		$columns['tcount'] = __( 'Terms count', 'list-plus' );
		$columns['terms'] = __( 'Action', 'list-plus' );
		unset( $columns['posts'] );
		unset( $columns['description'] );
		return $columns;
	}


	public function listing_tax_columns( $deprecated, $column_name, $term_id ) {
		$term = get_term( $term_id );
		$tax = 'ltx_' . $term->slug;
		switch ( $column_name ) {
			case 'tcount':
				$count = \wp_count_terms( $tax );
				if ( \is_wp_error( $count ) ) {
					echo '0';
				} else {
					echo $count;
				}
				break;
			case 'terms':
				?>
				<a href="<?php echo esc_url( \add_query_arg( [ 'taxonomy' => 'ltx_' . $term->slug ], \admin_url( 'edit-tags.php?post_type=listing' ) ) ); ?>"><?php _e( 'View terms' ); ?></a>
				<?php
				break;
		}

	}

	private function get_all_custom() {
		$query_args = [
			'taxonomy' => 'listing_tax',
			'orderby' => 'name',
			'hide_empty' => false,
			'order' => 'asc',
		];

		$query = new \WP_Term_Query( $query_args );
		foreach ( (array) $query->get_terms() as $t ) {
			$slug = $this->prefix . $t->slug;

			$singular       = get_term_meta( $t->term_id, '_singular', true );
			$plural         = get_term_meta( $t->term_id, '_plural', true );
			$frontend_name  = get_term_meta( $t->term_id, '_frontend_name', true );
			$hierarchical   = get_term_meta( $t->term_id, '_hierarchical', true );
			$filter_label   = get_term_meta( $t->term_id, '_filter_label', true );
			$allow_new      = get_term_meta( $t->term_id, '_allow_new', true );
			$custom_value   = get_term_meta( $t->term_id, '_custom_value', true );
			$exclude_filter = get_term_meta( $t->term_id, '_exclude_filter', true );

			if ( ! $singular ) {
				$singular = $t->name;
			}

			if ( ! $plural ) {
				$plural = $t->name;
			}

			if ( ! $frontend_name ) {
				$frontend_name = $t->name;
			}
			if ( ! $filter_label ) {
				$filter_label = $t->name;
			}

			$this->data[ $slug ] = [
				'slug'           => $slug,
				'term_id'        => $t->term_id,
				'orginal_slug'   => $t->slug,
				'name'           => $t->name,
				'singular'       => $t->name,
				'plural'         => $plural,
				'frontend_name'  => $frontend_name,
				'filter_label'   => $filter_label,
				'exclude_filter' => $exclude_filter,
				'hierarchical'   => $hierarchical,
				'allow_new'      => $allow_new ? true : false,
				'custom_value'   => $custom_value ? true : false,
				'_builtin'       => false,
			];
		}
	}


	public function is_custom( $taxonomy ) {
		if ( isset( $this->data[ $taxonomy ] ) ) {
			return 2;
		}

		return false;
	}

	public function get_all() {
		return $this->data;
	}

	public function get_custom( $tax ) {
		if ( \in_array( $tax, $this->builtins, true ) ) {
			return \apply_filters(
				'get_listing_tax_args',
				[
					'hierarchical'     => true,
					'allow_new'     => false,
					'_builtin'     => true,
				],
				$tax,
				true
			);
		}
		return isset( $this->data[ $tax ] ) ? \apply_filters( 'get_listing_tax_args', $this->data[ $tax ], $tax, false ) : false;
	}
	public function to_array() {
		return $this->data;
	}

	public function is_listing_tax( $taxonomy ) {
		if ( \in_array( $taxonomy, $this->builtins, true ) ) {
			return 1;
		}

		return $this->is_custom( $taxonomy );
	}


	public function registers() {

		// Register Custom Taxonomy.
		$args_listing_cat = array(
			'labels'            => array(
				'name'              => _x( 'Categories', 'taxonomy general name', 'list-plus' ),
				'singular_name'     => _x( 'Category', 'taxonomy singular name', 'list-plus' ),
				'search_items'      => __( 'Search Categories', 'list-plus' ),
				'all_items'         => __( 'All Categories', 'list-plus' ),
				'parent_item'       => __( 'Parent Category', 'list-plus' ),
				'parent_item_colon' => __( 'Parent Category:', 'list-plus' ),
				'edit_item'         => __( 'Edit Category', 'list-plus' ),
				'update_item'       => __( 'Update Category', 'list-plus' ),
				'add_new_item'      => __( 'Add New Category', 'list-plus' ),
				'new_item_name'     => __( 'New Category Name', 'list-plus' ),
				'menu_name'         => __( 'Categories', 'list-plus' ),
			),
			'hierarchical'      => true,
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
			'rewrite' => [
				'slug' => 'cat',
				'with_front' => false,
				'hierarchical' => true,
			],
			'update_count_callback' => [ __CLASS__, 'update_term_count' ],
		);

		$args_listing_region = array(
			'labels'            => array(
				'name'              => _x( 'Regions', 'taxonomy general name', 'list-plus' ),
				'singular_name'     => _x( 'Region', 'taxonomy singular name', 'list-plus' ),
				'search_items'      => __( 'Search Region', 'list-plus' ),
				'all_items'         => __( 'All Regions', 'list-plus' ),
				'parent_item'       => __( 'Parent Region', 'list-plus' ),
				'parent_item_colon' => __( 'Parent Region:', 'list-plus' ),
				'edit_item'         => __( 'Edit Region', 'list-plus' ),
				'update_item'       => __( 'Update Region', 'list-plus' ),
				'add_new_item'      => __( 'Add New Region', 'list-plus' ),
				'new_item_name'     => __( 'New Region Name', 'list-plus' ),
				'menu_name'         => __( 'Regions', 'list-plus' ),
			),
			'hierarchical'      => true,
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
			'rewrite' => [
				'slug' => 'region',
				'with_front' => false,
				'hierarchical' => true,
			],
			'update_count_callback' => [ __CLASS__, 'update_term_count' ],
		);

		$args_listing_tax = array(
			'labels'            => array(
				'name'              => _x( 'Taxonomies', 'taxonomy general name', 'list-plus' ),
				'singular_name'     => _x( 'Taxonomy', 'taxonomy singular name', 'list-plus' ),
				'search_items'      => __( 'Search Taxonomy', 'list-plus' ),
				'all_items'         => __( 'All Taxonomies', 'list-plus' ),
				'parent_item'       => __( 'Parent Taxonomy', 'list-plus' ),
				'parent_item_colon' => __( 'Parent Taxonomy:', 'list-plus' ),
				'edit_item'         => __( 'Edit Taxonomy', 'list-plus' ),
				'update_item'       => __( 'Update Taxonomy', 'list-plus' ),
				'add_new_item'      => __( 'Add New Taxonomy', 'list-plus' ),
				'new_item_name'     => __( 'New Taxonomy Name', 'list-plus' ),
				'menu_name'         => __( 'Taxonomies', 'list-plus' ),
			),
			'hierarchical'      => false,
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => false,
			'show_in_nav_menus' => false,
			'show_tagcloud'     => false,
			'update_count_callback' => [ __CLASS__, 'update_term_count' ],
		);

		$args_listing_type = array(
			'labels'                => array(
				'name'              => _x( 'Types', 'taxonomy general name', 'list-plus' ),
				'singular_name'     => _x( 'Type', 'taxonomy singular name', 'list-plus' ),
				'search_items'      => __( 'Search Type', 'list-plus' ),
				'all_items'         => __( 'All Types', 'list-plus' ),
				'parent_item'       => __( 'Parent Type', 'list-plus' ),
				'parent_item_colon' => __( 'Parent Type:', 'list-plus' ),
				'edit_item'         => __( 'Edit Type', 'list-plus' ),
				'update_item'       => __( 'Update Type', 'list-plus' ),
				'add_new_item'      => __( 'Add New Type', 'list-plus' ),
				'new_item_name'     => __( 'New Type Name', 'list-plus' ),
				'menu_name'         => __( 'Taxonomies', 'list-plus' ),
			),
			'rewrite' => [
				'slug' => 'listing_type',
				'with_front' => false,
				'hierarchical' => true,
			],
			'hierarchical'          => false,
			'public'                => true,
			'show_ui'               => false,
			'show_admin_column'     => true,
			'show_in_nav_menus'     => true,
			'show_tagcloud'         => true,
			'query_var'             => 'listing_type',
			'update_count_callback' => [ __CLASS__, 'update_term_count' ],
		);

		$caps = array(
			'manage_terms'  => 'manage_listing_terms',
			'edit_terms'    => 'edit_listing_terms',
			'delete_terms'  => 'delete_listing_terms',
			'assign_terms'  => 'assign_listing_terms',
		);

		$args_listing_type['capabilities'] = $caps;
		$args_listing_cat['capabilities'] = $caps;
		$args_listing_region['capabilities'] = $caps;
		$args_listing_tax['capabilities'] = $caps;

		register_taxonomy( 'listing_type', array( 'listing' ), $args_listing_type );
		register_taxonomy( 'listing_cat', array( 'listing' ), $args_listing_cat );
		register_taxonomy( 'listing_region', array( 'listing' ), $args_listing_region );
		register_taxonomy( 'listing_tax', array( 'listing' ), $args_listing_tax );

		// Dynamic custom taxonomies.
		foreach ( $this->data as $slug => $settings ) {
			$args = array(
				'labels'            => array(
					'name'              => $settings['plural'],
					'singular_name'     => $settings['singular'],
					'search_items'      => \sprintf( __( 'Search %s', 'list-plus' ), $settings['singular'] ),
					'all_items'         => \sprintf( __( 'All %s', 'list-plus' ), $settings['plural'] ),
					'parent_item'       => \sprintf( __( 'Parrent %s', 'list-plus' ), $settings['singular'] ),
					'parent_item_colon' => \sprintf( __( 'Parrent %s', 'list-plus' ), $settings['singular'] ),
					'edit_item'         => \sprintf( __( 'Edit %s', 'list-plus' ), $settings['singular'] ),
					'update_item'       => \sprintf( __( 'Update %s', 'list-plus' ), $settings['singular'] ),
					'add_new_item'      => \sprintf( __( 'Add New %s', 'list-plus' ), $settings['singular'] ),
					'new_item_name'     => \sprintf( __( 'New %s Name', 'list-plus' ), $settings['singular'] ),
					'menu_name'         => \sprintf( __( 'Listing %s', 'list-plus' ), $settings['plural'] ),
				),
				'hierarchical'      => $settings['hierarchical'] ? true : false,
				'public'            => false,
				'show_ui'           => true,
				'show_admin_column' => false,
				'show_in_nav_menus' => false,
				'show_tagcloud'     => false,
				'rewrite' => [
					'slug' => $slug,
					'with_front' => false,
					'hierarchical' => true,
				],
				'update_count_callback' => [ __CLASS__, 'update_term_count' ],
			);

			$args['capabilities'] = $caps;

			register_taxonomy( $slug, array( 'listing' ), $args );
		}

	}

	/**
	 * Will update term count based on object types of the current taxonomy.
	 *
	 * @see _update_post_term_count
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param array  $terms    List of Term taxonomy IDs.
	 * @param object $taxonomy Current taxonomy object of terms.
	 */
	public static function update_term_count( $terms, $taxonomy ) {
		global $wpdb;

		$object_types = (array) $taxonomy->object_type;

		foreach ( $object_types as &$object_type ) {
			list( $object_type ) = explode( ':', $object_type );
		}

		$object_types = array_unique( $object_types );

		$check_attachments = array_search( 'attachment', $object_types );
		if ( false !== $check_attachments ) {
			unset( $object_types[ $check_attachments ] );
			$check_attachments = true;
		}

		if ( $object_types ) {
			$object_types = esc_sql( array_filter( $object_types, 'post_type_exists' ) );
		}

		$not_count_status = "( 'draft', 'trash', 'pending', 'reject' )";

		foreach ( (array) $terms as $term ) {
			$count = 0;

			// Attachments can be 'inherit' status, we need to base count off the parent's status if so.
			if ( $check_attachments ) {
				$count += (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_relationships, $wpdb->posts p1 WHERE p1.ID = $wpdb->term_relationships.object_id AND ( post_status NOT IN {$not_count_status} OR ( post_status = 'inherit' AND post_parent > 0 AND ( SELECT post_status FROM $wpdb->posts WHERE ID = p1.post_parent ) = 'publish' ) ) AND post_type = 'attachment' AND term_taxonomy_id = %d", $term ) );
			}

			if ( $object_types ) {
				// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.QuotedDynamicPlaceholderGeneration
				$count += (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_relationships, $wpdb->posts WHERE $wpdb->posts.ID = $wpdb->term_relationships.object_id AND post_status NOT IN {$not_count_status} AND post_type IN ('" . implode( "', '", $object_types ) . "') AND term_taxonomy_id = %d", $term ) );
			}

			/** This action is documented in wp-includes/taxonomy.php */
			do_action( 'edit_term_taxonomy', $term, $taxonomy->name );
			$wpdb->update( $wpdb->term_taxonomy, compact( 'count' ), array( 'term_taxonomy_id' => $term ) );

			/** This action is documented in wp-includes/taxonomy.php */
			do_action( 'edited_term_taxonomy', $term, $taxonomy->name );
		}
	}


	public function offsetSet( $offset, $value ) {
		if ( is_null( $offset ) ) {
			$this->data[] = $value;
		} else {
			$this->data[ $offset ] = $value;
		}
	}

	public function offsetExists( $offset ) {
		return isset( $this->data[ $offset ] );
	}

	public function offsetUnset( $offset ) {
		unset( $this->data[ $offset ] );
	}

	public function offsetGet( $offset ) {
		return isset( $this->data[ $offset ] ) ? $this->data[ $offset ] : null;
	}


}
