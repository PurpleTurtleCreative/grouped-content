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
		add_action( 'admin_head-groups_page_ptc-grouped-content_generator', __CLASS__ . '::remove_all_admin_notices', 1 );
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

		require_once PLUGIN_PATH . 'src/class-ptc-content-group.php';

		try {
			$content_group = new \ptc_grouped_content\PTC_Content_Group( $post_parent_id );
			$url = admin_url( 'admin.php?page=ptc-grouped-content&post_parent=' . $post_parent_id );
		} catch ( \Exception $e ) {
			$url = admin_url( 'admin.php?page=ptc-grouped-content' );
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

		add_menu_page(
			'Grouped Content &mdash; View Groups',
			'Groups',
			'edit_pages',
			'ptc-grouped-content',
			function() {

				if ( current_user_can( 'edit_pages' ) ) {

					if ( isset( $_GET['post_parent'] ) ) {
						$html_to_require = PLUGIN_PATH . 'view/html-group-details.php';
					} else {
						$html_to_require = PLUGIN_PATH . 'view/html-toplevel-listing.php';
					}

					require_once $html_to_require;

				} else {
					echo '<p><strong>You do not have the proper permissions to access this page.</strong></p>';
				}
			},
			'dashicons-portfolio',
			21 /* Pages menu item is priority 20, see https://developer.wordpress.org/reference/functions/add_menu_page/#default-bottom-of-menu-structure */
		);

		add_submenu_page(
			'ptc-grouped-content',
			'Grouped Content &mdash; Generator',
			'Add New',
			'publish_pages',
			'ptc-grouped-content_generator',
			function() {

				if ( current_user_can( 'publish_pages' ) ) {
					require_once PLUGIN_PATH . 'view/html-content-generator.php';
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
		add_meta_box(
			'ptc-grouped-content',
			'Page Relatives',
			[ $this, 'related_content_metabox_html' ],
			'page',
			'side'
		);
	}

	/**
	 * Gets content for the Page Relatives metabox.
	 *
	 * @since 2.0.0
	 *
	 * @ignore
	 */
	public static function related_content_metabox_html() {
		include_once PLUGIN_PATH . 'view/html-metabox-page-relatives.php';
	}

	/**
	 * Handles AJAX refreshing the Page Relatives metabox in Gutenberg.
	 *
	 * @since 1.2.0
	 *
	 * @ignore
	 */
	public static function related_content_metabox_html_ajax_refresh() {
		require_once PLUGIN_PATH . 'src/ajax-refresh-metabox-page-relatives.php';
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

		switch ( $hook_suffix ) {
			case 'toplevel_page_ptc-grouped-content':
				wp_enqueue_style(
					'ptc-grouped-content_view-groups-css',
					plugins_url( 'assets/css/view-groups.css', __FILE__ ),
					[ 'fontawesome' ],
					'1.0.0'
				);
				break;
			case 'post.php':
				if ( get_post_type() === 'page' ) {

					wp_enqueue_style(
						'ptc-grouped-content_metabox-page-relatives-css',
						plugins_url( 'assets/css/metabox_page-relatives.css', __FILE__ ),
						[ 'fontawesome' ],
						'0.0.0'
					);

					wp_enqueue_script(
						'ptc-grouped-content_metabox-page-relatives-js',
						plugins_url( 'assets/js/metabox-page-relatives.js', __FILE__ ),
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
			case 'groups_page_ptc-grouped-content_generator':
				wp_enqueue_style(
					'ptc-grouped-content_content-generator-css',
					plugins_url( 'assets/css/content-generator.css', __FILE__ ),
					[ 'fontawesome' ],
					'0.0.0'
				);
				wp_enqueue_script(
					'ptc-grouped-content_content-generator-js',
					plugins_url( 'assets/js/content-generator.js', __FILE__ ),
					[ 'jquery' ],
					'0.0.0'
				);
				break;
		}
	}//end register_scripts()
}//end class
