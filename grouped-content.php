<?php
/**
 * Grouped Content
 *
 * @author            Michelle Blanchette
 * @copyright         2019 Michelle Blanchette
 * @license           GPL-3.0-or-later
 *
 * Plugin Name:       Grouped Content
 * Plugin URI:        https://purpleturtlecreative.com/grouped-content/
 * Description:       Enhances the use of page hierarchies by providing easy access to the parent page, sibling pages, and child pages in your admin area.
 * Version:           1.1.0
 * Requires PHP:      7.0
 * Author:            Purple Turtle Creative
 * Author URI:        https://purpleturtlecreative.com/
 * License:           GPL v3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.txt
 */

/*
Grouped Content is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, version 3 of the License.

Grouped Content is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Grouped Content. If not, see https://www.gnu.org/licenses/gpl-3.0.txt.
*/

defined( 'ABSPATH' ) || die();

if ( ! class_exists( '\PTC_Grouped_Content' ) ) {
  /**
   * Provides helper functions and information relevant to this plugin for use
   * in the global space.
   *
   * @since 1.0.0
   */
  class PTC_Grouped_Content {

    /**
     * This plugin's basename.
     *
     * @since 1.0.0
     *
     * @ignore
     */
    public $plugin_title;

    /**
     * The full file path to this plugin's directory ending with a slash.
     *
     * @since 1.0.0
     *
     * @ignore
     */
    public $plugin_path;

    /**
     * Get the admin url for the relevant Groups details page.
     *
     * @since 1.0.0
     *
     * @param int $post_parent_id Optional. The post id to use as the group to
     * be linked. Default 0 for default Groups home directory url.
     *
     * @return string The admin url for the provided group. If the provided id
     * cannot represent a group, the Groups home directory url is returned.
     */
    function get_groups_list_admin_url( int $post_parent_id = 0 ) : string {

      require_once $this->plugin_path . 'src/class-ptc-content-group.php';

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
     * Sets plugin member variables.
     *
     * @since 1.0.0
     *
     * @ignore
     */
    function __construct() {
      $this->plugin_title = plugin_basename( __FILE__ );
      $this->plugin_path = plugin_dir_path( __FILE__ );
    }

    /**
     * Hook code into WordPress.
     *
     * @since 1.0.0
     *
     * @ignore
     */
    function register() {
      add_action( 'admin_menu', [ $this, 'add_admin_pages' ] );
      add_action( 'admin_enqueue_scripts', [ $this, 'register_scripts' ] );
      add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
    }

    /**
     * Add the administrative pages.
     *
     * @since 1.0.0
     * @since 1.1.0 Added content generator submenu page
     *
     * @ignore
     */
    function add_admin_pages() {

      add_menu_page(
        'Grouped Content &mdash; View Groups',
        'Groups',
        'edit_pages',
        'ptc-grouped-content',
        function() {

          if ( current_user_can( 'edit_pages' ) ) {

            if ( isset( $_GET['post_parent'] ) ) {
              $html_to_require = $this->plugin_path . 'view/html-group-details.php';
            } else {
              $html_to_require = $this->plugin_path . 'view/html-toplevel-listing.php';
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
            require_once $this->plugin_path . 'view/html-content-generator.php';
          } else {
            echo '<p><strong>You do not have the proper permissions to access this page.</strong></p>';
          }

        }
      );

    }//end add_admin_pages()

    /**
     * Add metaboxes.
     *
     * @since 1.0.0
     *
     * @ignore
     */
    function add_meta_boxes() {
      add_meta_box(
        'ptc-grouped-content',
        'Page Relatives',
        [ $this, 'related_content_metabox_html' ],
        'page',
        'side'
      );
    }

    /**
     * Content for the Page Relatives metabox.
     *
     * @since 1.0.0
     *
     * @ignore
     */
    function related_content_metabox_html() {
      include_once $this->plugin_path . 'view/html-metabox-page-attributes.php';
    }

    /**
     * Register and enqueue plugin CSS and JS.
     *
     * @since 1.0.0
     * @since 1.1.0 Added content generator scripts
     *
     * @ignore
     */
    function register_scripts( $hook_suffix ) {

      wp_register_style(
        'fontawesome-5',
        plugins_url( '/assets/fonts/fontawesome-free-5.12.0-web/css/all.min.css', __FILE__ ),
        [],
        '5.12.0'
      );

      switch ( $hook_suffix ) {
        case 'toplevel_page_ptc-grouped-content':
          wp_enqueue_style(
            'ptc-grouped-content_view-groups-css',
            plugins_url( 'assets/css/view-groups.css', __FILE__ ),
            [ 'fontawesome-5' ],
            '0.0.0'
          );
          break;
        case 'post.php':
          wp_enqueue_style(
            'ptc-grouped-content_metabox-page-relatives-css',
            plugins_url( 'assets/css/metabox_page-relatives.css', __FILE__ ),
            [ 'fontawesome-5' ],
            '0.0.0'
          );
          break;
        case 'groups_page_ptc-grouped-content_generator':
          wp_enqueue_style(
            'ptc-grouped-content_content-generator-css',
            plugins_url( 'assets/css/content-generator.css', __FILE__ ),
            [ 'fontawesome-5' ],
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

  $ptc_grouped_content = new PTC_Grouped_Content();
  $ptc_grouped_content->register();

}//end if class_exists
