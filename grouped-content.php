<?php
/*
Plugin Name:   Grouped Content
Plugin URI:    https://purpleturtlecreative.com/plugins/grouped-content/
Description:   Organize and quickly generate groups of related content in your site admin area. Easily manage and review content hierarchies and subsections on your site such as courses, sales funnels, user engagement flows, product information, and anything else you'd like to organize!
Version:       1.0.0
Requires PHP:  7.0.0
Author:        Purple Turtle Creative
Author URI:    https://purpleturtlecreative.com/
License:       GPLv3
License URI:   https://www.gnu.org/licenses/gpl-3.0.txt

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

if ( ! class_exists( 'PTC_Grouped_Content' ) ) {
  class PTC_Grouped_Content {

    public $plugin_title;
    public $plugin_path;

    function get_groups_list_admin_url( int $post_parent_id = -1 ) : string {

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

    function __construct() {
      $this->plugin_title = plugin_basename( __FILE__ );
      $this->plugin_path = plugin_dir_path( __FILE__ );
    }

    function register() {
      add_action( 'admin_menu', [ $this, 'add_admin_pages' ] );
      add_action( 'admin_enqueue_scripts', [ $this, 'register_scripts' ] );
    }

    function add_admin_pages() {

      add_menu_page( 'Grouped Content &mdash; View Groups', 'Groups', 'edit_posts', 'ptc-grouped-content', function() {
        if ( isset( $_GET['post_parent'] ) ) {
          $html_to_require = $this->plugin_path . 'view/html-group-details.php';
        } else {
          $html_to_require = $this->plugin_path . 'view/html-toplevel-listing.php';
        }
        require_once $html_to_require;
      }, 'dashicons-portfolio', 100 );

      // add_submenu_page( 'ptc-grouped-content', 'Grouped Content &mdash; Generate New Group', 'Generate New Group', 'publish_pages', 'ptc-grouped-content-generator', function() {
      //     require_once $this->plugin_path . 'view/html-admin-generator.php';
      //   }, 10 );

    }

    function register_scripts( $hook_suffix ) {

      wp_register_style(
        'fontawesome-5',
        plugins_url( '/assets/fonts/fontawesome-free-5.12.0-web/css/all.min.css', __FILE__ ),
        [],
        '5.12.0'
      );

      if ( $hook_suffix == 'toplevel_page_ptc-grouped-content' ) {
        wp_enqueue_style(
          'ptc-grouped-content_view-groups-css',
          plugins_url( 'assets/css/view-groups.css', __FILE__ ),
          [ 'fontawesome-5' ],
          '0.0.0'
        );
        // wp_enqueue_script(
        //   'ptc-grouped-content_view-groups-js',
        //   plugins_url( 'assets/js/view-groups.js', __FILE__ ),
        //   [ 'jquery' ],
        //   '0.0.0'
        // );
      }

    }//end register_scripts()

  }//end class

  $ptc_grouped_content = new PTC_Grouped_Content();
  $ptc_grouped_content->register();

}
