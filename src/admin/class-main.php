<?php
/**
 * Core plugin registration.
 *
 * @since 2.0.0
 *
 * @package PTC_Grouped_Content
 */

namespace PTC_Grouped_Content;

defined( 'ABSPATH' ) || die();

/**
 * Provides helper functions and information relevant to this plugin for use
 * in the global space.
 *
 * @since 2.0.0
 */
class Main {

	/**
	 * The post object for this group.
	 *
	 * @since 3.0.0
	 *
	 * @var \WP_Post_Type[] The post that this group represents.
	 */
	public static $grouped_post_types = [];

	/**
	 * Hooks code into WordPress.
	 *
	 * @since 2.0.0
	 *
	 * @ignore
	 */
	public static function register() {

		add_action( 'admin_menu', __CLASS__ . '::add_admin_pages' );
		add_action( 'admin_enqueue_scripts', __CLASS__ . '::register_scripts' );

		add_action( 'add_meta_boxes', __CLASS__ . '::add_meta_boxes' );
		add_action( 'wp_ajax_refresh_page_relatives', __CLASS__ . '::related_content_metabox_html_ajax_refresh' );

		/* Remove nags from plugin pages */
		add_action( 'admin_head-tools_page_ptc-grouped-content_generator', __CLASS__ . '::remove_all_admin_notices', 1 );
		add_action( 'admin_head-toplevel_page_ptc-grouped-content', __CLASS__ . '::remove_all_admin_notices', 1 );
	}

	/**
	 * Get the admin url for the relevant Groups details page.
	 *
	 * @since 2.0.0
	 *
	 * @param int $post_parent_id Optional. The post id to use as the group to
	 * be linked. Default 0 for default Groups home directory url.
	 *
	 * @return string The admin url for the provided group. If the provided id
	 * cannot represent a group, the Groups home directory url is returned.
	 */
	public static function get_groups_list_admin_url( int $post_parent_id = 0 ) : string {

		require_once PLUGIN_PATH . 'src/includes/class-ptc-content-group.php';

		try {
			$content_group = new \ptc_grouped_content\PTC_Content_Group( $post_parent_id );
			// $url = admin_url( 'admin.php?page=ptc-grouped-content&post_parent=' . $post_parent_id );
			$url = admin_url( "edit.php?post_type={$content_group->post->post_type}&page=ptc-grouped-content_post-type-{$content_group->post->post_type}&post_parent={$content_group->post->ID}" );
		} catch ( \Exception $e ) {
			$url = admin_url( "edit.php?post_type={$GLOBALS['typenow']}&page=ptc-grouped-content_post-type-{$GLOBALS['typenow']}" );
		}

		return $url;
	}

	/* Plugin Initialization */

	/**
	 * Add the administrative pages.
	 *
	 * @since 2.0.0
	 *
	 * @ignore
	 */
	public static function add_admin_pages() {

		static::$grouped_post_types = get_post_types(
			[
				'show_ui' => true,
				'show_in_menu' => true,
				'show_in_nav_menus' => true,
				'hierarchical' => true,
			],
			'objects',
			'and'
		);

		foreach ( static::$grouped_post_types as $post_type => $post_type_args ) {

			$singular_name = $post_type;
			if ( ! empty( $post_type_args->labels->singular_name ) ) {
				$singular_name = $post_type_args->labels->singular_name;
			} elseif ( ! empty( $post_type_args->label ) ) {
				$singular_name = $post_type_args->label;
			}

			add_submenu_page(
				"edit.php?post_type={$post_type}",
				"{$singular_name} Groups",
				"{$singular_name} Groups",
				'edit_pages',
				"ptc-grouped-content_post-type-{$post_type}",
				function() {

					if ( current_user_can( 'edit_pages' ) ) {

						if ( isset( $_GET['post_parent'] ) ) {
							$html_to_require = PLUGIN_PATH . 'src/admin/templates/html-group-details.php';
						} else {
							$html_to_require = PLUGIN_PATH . 'src/admin/templates/html-toplevel-listing.php';
						}

						require_once $html_to_require;

					} else {
						echo '<p><strong>You do not have the proper permissions to access this page.</strong></p>';
					}
				}
			);
		}

		add_submenu_page(
			'tools.php',
			'Grouped Content &mdash; Generator',
			'Create Draft Pages',
			'publish_pages',
			'ptc-grouped-content_generator',
			function() {

				if ( current_user_can( 'publish_pages' ) ) {
					require_once PLUGIN_PATH . 'src/admin/templates/html-content-generator.php';
				} else {
					echo '<p><strong>You do not have the proper permissions to access this page.</strong></p>';
				}
			}
		);
	}//end add_admin_pages()

	/**
	 * Add metaboxes.
	 *
	 * @since 2.0.0
	 *
	 * @ignore
	 */
	public static function add_meta_boxes() {
		foreach ( static::$grouped_post_types as $post_type => $post_type_args ) {

			$singular_name = $post_type;
			if ( ! empty( $post_type_args->labels->singular_name ) ) {
				$singular_name = $post_type_args->labels->singular_name;
			} elseif ( ! empty( $post_type_args->label ) ) {
				$singular_name = $post_type_args->label;
			}

			add_meta_box(
				'ptc-grouped-content',
				"{$singular_name} Relatives",
				__CLASS__ . '::related_content_metabox_html',
				$post_type,
				'side'
			);
		}
	}

	/**
	 * Gets content for the Page Relatives metabox.
	 *
	 * @since 2.0.0
	 *
	 * @ignore
	 */
	public static function related_content_metabox_html() {
		include_once PLUGIN_PATH . 'src/admin/templates/html-metabox-page-relatives.php';
	}

	/**
	 * Handles AJAX refreshing the Page Relatives metabox in Gutenberg.
	 *
	 * @since 1.2.0
	 *
	 * @ignore
	 */
	public static function related_content_metabox_html_ajax_refresh() {
		require_once PLUGIN_PATH . 'src/admin/ajax-refresh-metabox-page-relatives.php';
	}

	/**
	 * Removes all admin notice actions.
	 *
	 * @since 1.2.2
	 *
	 * @ignore
	 */
	public static function remove_all_admin_notices() {
		remove_all_actions( 'admin_notices' );
		remove_all_actions( 'all_admin_notices' );
	}

	/**
	 * Registers and enqueues plugin CSS and JS.
	 *
	 * @since 2.0.0
	 *
	 * @param string $hook_suffix The current admin page.
	 *
	 * @ignore
	 */
	public static function register_scripts( $hook_suffix ) {

		wp_register_style(
			'fontawesome',
			'https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css',
			[],
			'4.7.0'
		);

		if ( false !== strpos( $hook_suffix, 'ptc-grouped-content_post-type-' ) ) {
			wp_enqueue_style(
				'ptc-grouped-content_view-groups-css',
				PLUGIN_URL . 'assets/css/view-groups.css',
				[ 'fontawesome' ],
				'1.0.0'
			);
		}

		$grouped_post_type_slugs = array_keys( static::$grouped_post_types );

		switch ( $hook_suffix ) {
			case 'post.php':
				if ( in_array( get_post_type(), $grouped_post_type_slugs, true ) ) {

					wp_enqueue_style(
						'ptc-grouped-content_metabox-page-relatives-css',
						PLUGIN_URL . 'assets/css/metabox_page-relatives.css',
						[ 'fontawesome' ],
						'0.0.0'
					);

					wp_enqueue_script(
						'ptc-grouped-content_metabox-page-relatives-js',
						PLUGIN_URL . 'assets/js/metabox-page-relatives.js',
						[ 'jquery' ],
						'0.0.1'
					);

					$the_post = get_post();
					$the_parent_post_id = -1;
					$the_parent_post_title = '';
					if ( is_object( $the_post ) && is_a( $the_post, '\WP_Post' ) ) {
						if ( isset( $the_post->post_parent ) && $the_post->post_parent > 0 ) {
							$the_parent_post_id = $the_post->post_parent;
							$the_parent_post_title = get_the_title( $the_parent_post_id );
						}
					}

					wp_localize_script(
						'ptc-grouped-content_metabox-page-relatives-js',
						'ptc_page_relatives',
						[
							'nonce' => wp_create_nonce( 'ptc_page_relatives' ),
							'post_parent' => $the_parent_post_id,
							'post_parent_title' => $the_parent_post_title,
						]
					);

				}
				break;
			case 'tools_page_ptc-grouped-content_generator':
				wp_enqueue_style(
					'ptc-grouped-content_content-generator-css',
					PLUGIN_URL . 'assets/css/content-generator.css',
					[ 'fontawesome' ],
					'0.0.0'
				);
				wp_enqueue_script(
					'ptc-grouped-content_content-generator-js',
					PLUGIN_URL . 'assets/js/content-generator.js',
					[ 'jquery' ],
					'0.0.0'
				);
				break;
		}
	}//end register_scripts()
}//end class
