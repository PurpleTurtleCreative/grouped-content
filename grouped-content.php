<?php
/*
Plugin Name:   Grouped Content
Description:   Enhances the functionality of page hierarchies by providing easy access to the parent page, sibling pages, and child pages in your admin area. Easily manage and review content hierarchies and subsections on your site such as courses, sales funnels, user engagement flows, knowledgebases, and anything else you'd like to organize through hierarchies!
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

if ( ! class_exists( '\PTC_Grouped_Content' ) ) {
  /**
   * Provides helper functions and information relevant to this plugin for use in the global space.
   *
   * @author Michelle Blanchette <michelle@purpleturtlecreative.com>
   */
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
      add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
    }

    function add_admin_pages() {

      add_menu_page( 'Grouped Content &mdash; View Groups', 'Groups', 'edit_pages', 'ptc-grouped-content', function() {
        if ( isset( $_GET['post_parent'] ) ) {
          $html_to_require = $this->plugin_path . 'view/html-group-details.php';
        } else {
          $html_to_require = $this->plugin_path . 'view/html-toplevel-listing.php';
        }
        require_once $html_to_require;
      }, 'dashicons-portfolio', 21 ); /* Pages menu item is priority 20, see https://developer.wordpress.org/reference/functions/add_menu_page/#default-bottom-of-menu-structure */

    }//end add_admin_pages()

    function add_meta_boxes() {
      add_meta_box(
        'ptc-grouped-content',
        'Page Relatives',
        [ $this, 'related_content_metabox_html' ],
        'page',
        'side'
      );
    }

    function related_content_metabox_html() {
      include_once $this->plugin_path . 'view/html-metabox-page-attributes.php';
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
      } elseif ( $hook_suffix == 'post.php' ) {
        wp_enqueue_style(
          'ptc-grouped-content_metabox-page-relatives-css',
          plugins_url( 'assets/css/metabox_page-relatives.css', __FILE__ ),
          [ 'fontawesome-5' ],
          '0.0.0'
        );
      }

    }//end register_scripts()

  }//end class

  $ptc_grouped_content = new PTC_Grouped_Content();
  $ptc_grouped_content->register();

}//end if class_exists
