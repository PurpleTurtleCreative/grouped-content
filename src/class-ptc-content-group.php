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
    $parent_post_ids = $wpdb->get_results(
      "
        SELECT DISTINCT parents.ID FROM {$wpdb->posts} posts
        JOIN {$wpdb->posts} parents
          ON parents.ID = posts.post_parent
        WHERE posts.post_parent != 0
          AND posts.post_type = 'page'
        ORDER BY parents.post_title ASC
      ", ARRAY_N );

    if ( is_array( $parent_post_ids ) && ! empty( $parent_post_ids[0] ) ) {

      foreach ( $parent_post_ids as $i => $cols ) {
        $parent_post_ids[ $i ] = (int) $cols[0];
      }

      return $parent_post_ids;

    }

    return [];

  }

  static function get_all_toplevel_parent_ids() : array {

    global $wpdb;
    $parent_post_ids = $wpdb->get_results(
       "
        SELECT DISTINCT parents.ID FROM {$wpdb->posts} posts
        JOIN {$wpdb->posts} parents
          ON parents.ID = posts.post_parent
        WHERE posts.post_parent != 0
          AND posts.post_type = 'page'
          AND parents.post_parent = 0
        ORDER BY parents.post_title ASC
      ", ARRAY_N );

    if ( is_array( $parent_post_ids ) && ! empty( $parent_post_ids[0] ) ) {

      foreach ( $parent_post_ids as $i => $cols ) {
        $parent_post_ids[ $i ] = (int) $cols[0];
      }

      return $parent_post_ids;

    }

    return [];

  }

  function __construct( int $post_parent_id ) {

    $this->id = $post_parent_id;

    if ( $this->count_children() === 0 ) {
      throw new \Exception("Cannot make page group from post id {$this->id} because it is not assigned as a parent of any page post.");
    }

  }

  function count_children( int $parent_post_id = 0 ) : int {

    if ( $parent_post_id < 1 ) {
      $parent_post_id = $this->id;
    }

    global $wpdb;
    $child_count = $wpdb->get_var( $wpdb->prepare(
      "
        SELECT COUNT( DISTINCT posts.ID ) FROM {$wpdb->posts} posts
        WHERE posts.post_parent = %d
          AND posts.post_type = 'page'
      ",
      $parent_post_id
    ) );

    if ( is_numeric( $child_count ) ) {
      return (int) $child_count;
    }

    return 0;

  }

  // TODO: Ahhhh, this is not gonna be as easy as I immediately thought because of recursion...
  //       Gonna need to use maybe get_child_parent_ids and count_children..? Eh...
  // function count_descendents() : int {

  //   $descendents_count = 0;
  //   $parent_id = $this->id;

  //   while ( $parent_id > 0 ) {
  //     $elder_ids[] = $elder_id;
  //     $parent_id = $this->get_elder_id( $elder_id );
  //   }

  //   if ( is_array( $elder_ids ) ) {
  //     return $elder_ids;
  //   }

  //   return [];

  // }

  function get_all_children_ids() : array {

    global $wpdb;
    $children_post_ids = $wpdb->get_results( $wpdb->prepare(
      "
        SELECT DISTINCT posts.ID FROM {$wpdb->posts} posts
        WHERE posts.post_parent = %d
          AND posts.post_type = 'page'
        ORDER BY posts.post_title ASC
      ",
      $this->id
    ), ARRAY_N );

    if ( is_array( $children_post_ids ) && ! empty( $children_post_ids[0] ) ) {

      foreach ( $children_post_ids as $i => $cols ) {
        $children_post_ids[ $i ] = (int) $cols[0];
      }

      return $children_post_ids;

    }

    return [];

  }

  function get_child_parent_ids() : array {

    global $wpdb;
    $child_parent_post_ids = $wpdb->get_results( $wpdb->prepare(
      "
        SELECT DISTINCT posts.ID FROM {$wpdb->posts} posts
        WHERE posts.post_parent = %d
          AND posts.post_type = 'page'
          AND posts.ID IN( SELECT post_parent FROM {$wpdb->posts} WHERE post_parent != 0 AND post_type = 'page' )
        ORDER BY posts.post_title ASC
      ",
      $this->id
    ), ARRAY_N );

    if ( is_array( $child_parent_post_ids ) && ! empty( $child_parent_post_ids[0] ) ) {

      foreach ( $child_parent_post_ids as $i => $cols ) {
        $child_parent_post_ids[ $i ] = (int) $cols[0];
      }

      return $child_parent_post_ids;

    }

    return [];

  }

  function get_elder_id( int $post_id = 0 ) : int {

    if ( $post_id < 1 ) {
      $post_id = $this->id;
    }

    global $wpdb;
    $elder_id = $wpdb->get_var( $wpdb->prepare(
      "
        SELECT parent.ID FROM {$wpdb->posts} post
        JOIN {$wpdb->posts} parent
          ON parent.ID = post.post_parent
        WHERE post.ID = %d
          AND parent.post_type = 'page'
      ",
      $post_id
    ) );

    if ( is_numeric( $elder_id ) && $elder_id > 0 ) {
      return (int) $elder_id;
    }

    return 0;

  }

  function get_all_elder_ids() : array {

    $elder_ids = [];
    $elder_id = $this->get_elder_id();

    while ( $elder_id > 0 ) {
      $elder_ids[] = $elder_id;
      $elder_id = $this->get_elder_id( $elder_id );
    }

    if ( is_array( $elder_ids ) ) {
      return $elder_ids;
    }

    return [];

  }

  function assign_child_post( int $post_id ) {}

  function is_child( int $post_id ) {}

  function add_post_to_group( int $post_id, string $subsection_title = '' ) {}

  function get_assigned_menu_id() {}

}//end class
