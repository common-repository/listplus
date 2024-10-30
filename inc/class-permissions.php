<?php
namespace ListPlus;

class Permissions {

	protected $manage_role = '';

	public function __construct() {
		add_action( 'listplus_install', [ __CLASS__, 'create_roles' ] );
		add_action( 'listplus_uninstall', [ __CLASS__, 'remove_roles' ] );
	}

	public function is_user_admin() {
		return \current_user_can( 'administrator' ) || \current_user_can( 'manage_listing' );
	}

	public function get_nonce() {
		$nonce = isset( $_REQUEST['_nonce'] ) ? sanitize_text_field( $_REQUEST['_nonce'] ) : false;
		return $nonce;
	}

	public function verify_nonce( $action = -1 ) {
		$nonce = $this->get_nonce();
		if ( ! wp_verify_nonce( $nonce, $action ) ) {
			wp_die( 'Access denied.' );
		}
	}

	/**
	 * Remove WooCommerce roles.
	 */
	public static function remove_roles() {
		global $wp_roles;

		if ( ! class_exists( 'WP_Roles' ) ) {
			return;
		}

		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles(); // @codingStandardsIgnoreLine
		}

		$capabilities = self::get_core_capabilities();

		foreach ( $capabilities as $cap_group ) {
			foreach ( $cap_group as $cap ) {
				$wp_roles->remove_cap( 'listing_manager', $cap );
				$wp_roles->remove_cap( 'administrator', $cap );
			}
		}

		remove_role( 'listing_manager' );
	}


	/**
	 * Create roles and capabilities.
	 */
	public static function create_roles() {
		global $wp_roles;

		if ( ! class_exists( 'WP_Roles' ) ) {
			return;
		}

		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles(); // @codingStandardsIgnoreLine
		}

		// Dummy gettext calls to get strings in the catalog.
		/* translators: user role */
		_x( 'Listing manager', 'User role', 'list-plus' );

		// Listing manager role.
		add_role(
			'listing_manager',
			'Listing manager',
			array(
				'read'                   => true,
				'read_private_pages'     => false,
				'read_private_posts'     => false,
				'edit_posts'             => true,
				'edit_pages'             => false,
				'edit_published_posts'   => false,
				'edit_published_pages'   => false,
				'edit_private_pages'     => false,
				'edit_private_posts'     => false,
				'edit_others_posts'      => false,
				'edit_others_pages'      => false,
				'publish_posts'          => false,
				'publish_pages'          => false,
				'delete_posts'           => false,
				'delete_pages'           => false,
				'delete_private_pages'   => false,
				'delete_private_posts'   => false,
				'delete_published_pages' => false,
				'delete_published_posts' => false,
				'delete_others_posts'    => false,
				'delete_others_pages'    => false,
				'manage_categories'      => false,
				'manage_links'           => false,
				'moderate_comments'      => true,
				'upload_files'           => true,
				'export'                 => false,
				'import'                 => false,
				'list_users'             => true,
				'edit_dashboard'         => false,
				'edit_theme_options'     => false,

				'manage_listing_terms'   => true,
				'edit_listing_terms'     => true,
				'delete_listing_terms'   => true,
				'assign_listing_terms'   => true,

			)
		);

		$capabilities = self::get_core_capabilities();

		foreach ( $capabilities as $cap_group ) {
			foreach ( $cap_group as $cap ) {
				$wp_roles->add_cap( 'listing_manager', $cap );
				$wp_roles->add_cap( 'administrator', $cap );
			}
		}
	}


	/**
	 * Get capabilities for WooCommerce - these are assigned to admin/shop manager during installation or reset.
	 *
	 * @return array
	 */
	private static function get_core_capabilities() {
		$capabilities = array();

		$capabilities['core'] = array(
			'manage_listing',
			'view_listing_reports',
		);

		$capability_types = array( 'listing' );

		foreach ( $capability_types as $capability_type ) {

			$capabilities[ $capability_type ] = array(
				// Post type.
				"edit_{$capability_type}",
				"edit_{$capability_type}s",
				"edit_others_{$capability_type}s",
				"edit_private_{$capability_type}s",
				"edit_published_{$capability_type}s",

				"read_{$capability_type}",
				"delete_{$capability_type}",
				"delete_{$capability_type}s",
				"delete_private_{$capability_type}s",
				"delete_published_{$capability_type}s",
				"delete_others_{$capability_type}s",

				"publish_{$capability_type}s",
				"read_private_{$capability_type}s",

				"create_{$capability_type}s",

				// Terms.
				"manage_{$capability_type}_terms",
				"edit_{$capability_type}_terms",
				"delete_{$capability_type}_terms",
				"assign_{$capability_type}_terms",

			);
		}

		return $capabilities;
	}


}
