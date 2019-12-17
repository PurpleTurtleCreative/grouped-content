<?php

declare(strict_types=1);

namespace ptc_grouped_content;

defined( 'ABSPATH' ) || die();

/**
 * Maintain content related to a post_parent which represents a group.
 *
 * @author Michelle Blanchette <michelle@purpleturtlecreative.com>
 */
class PTC_Content_Group {

  public $id;

  static function get_all_post_parent_ids() : array {

    global $wpdb;
    $parent_post_ids = $wpdb->get_results( $wpdb->prepare(
      "
        SELECT DISTINCT parents.ID FROM {$wpdb->posts} posts
        JOIN {$wpdb->posts} parents
          ON parents.ID = posts.post_parent
        WHERE posts.post_parent != %d
          AND posts.post_type = %s
        ORDER BY parents.post_title ASC
      ",
      0,
      'page'
    ), ARRAY_N );

    if ( is_array( $parent_post_ids ) ) {

      foreach ( $parent_post_ids[0] as $i => $id ) {
        $parent_post_ids[0][ $i ] = (int) $id;
      }

      return $parent_post_ids[0];

    } else {
      return [];
    }

  }

  static function get_all_toplevel_parent_ids() : array {

    global $wpdb;
    $parent_post_ids = $wpdb->get_results( $wpdb->prepare(
      "
        SELECT DISTINCT parents.ID FROM {$wpdb->posts} posts
        JOIN {$wpdb->posts} parents
          ON parents.ID = posts.post_parent
        WHERE posts.post_parent != %d
          AND posts.post_type = %s
          AND parents.post_parent = %d
        ORDER BY parents.post_title ASC
      ",
      0,
      'page',
      0
    ), ARRAY_N );

    if ( is_array( $parent_post_ids ) ) {

      foreach ( $parent_post_ids[0] as $i => $id ) {
        $parent_post_ids[0][ $i ] = (int) $id;
      }

      return $parent_post_ids[0];

    } else {
      return [];
    }

  }

  function __construct( int $post_parent_id ) {

    $this->id = $post_parent_id;

    if ( $this->count_children() === 0 ) {
      throw new \Exception("Cannot make page group from post id {$this->id} because it is not assigned as a parent of any page post.");
    }

  }

  function count_children() : int {

    global $wpdb;
    $child_count = $wpdb->get_var( $wpdb->prepare(
      "
        SELECT COUNT( DISTINCT posts.ID ) FROM {$wpdb->posts} posts
        WHERE posts.post_parent = %d
          AND posts.post_type = %s
        ORDER BY posts.post_title ASC
      ",
      $this->id,
      'page'
    ) );

    if ( is_numeric( $child_count ) ) {
      return (int) $child_count;
    } else {
      return 0;
    }

  }

  function get_all_children_ids() : array {

    global $wpdb;
    $children_post_ids = $wpdb->get_results( $wpdb->prepare(
      "
        SELECT DISTINCT posts.ID FROM {$wpdb->posts} posts
        WHERE posts.post_parent = %d
          AND posts.post_type = %s
        ORDER BY posts.post_title ASC
      ",
      $this->id,
      'page'
    ), ARRAY_N );

    if ( is_array( $children_post_ids ) ) {

      foreach ( $children_post_ids[0] as $i => $id ) {
        $children_post_ids[0][ $i ] = (int) $id;
      }

      return $children_post_ids[0];

    } else {
      return [];
    }

  }

  function assign_child_post( int $post_id ) {}

  function is_child( int $post_id ) {}

  function add_post_to_group( int $post_id, string $subsection_title = '' ) {}

  function get_assigned_menu_id() {}

}//end class
