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

  /**
   * @var int The id of the post_parent that this group represents
   *
   * @since 1.0.0 Introduced
   */
  public $id;

  /**
   * @var \WP_Post The post that this group represents
   *
   * @since 1.0.0 Introduced
   */
  public $post;

  /**
   * Get all distinct post ids of pages assigned as a post_parent.
   *
   * @return int[] The post ids
   *
   * @since 1.0.0 Introduced
   */
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

  /**
   * Get all distinct post ids of pages assigned as a post_parent that do not have a post_parent.
   *
   * @return int[] The post ids
   *
   * @since 1.0.0 Introduced
   */
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

  /**
   * Construct a new instance.
   *
   * @param int $parent_post_id The post id of an existing page that is assigned as the post_parent
   * on other page posts.
   *
   * @throws \Exception if the provided post id is invalid to represent a page group.
   *
   * @since 1.0.0 Introduced
   */
  function __construct( int $parent_post_id ) {

    $this->id = $parent_post_id;
    if ( $this->id < 1 ) {
      throw new \Exception("Cannot make page group from post id {$this->id} because the post id is invalid.");
    }

    $this->post = get_post( $this->id );
    if ( NULL === $this->post || 'page' !== $this->post->post_type ) {
      throw new \Exception("Cannot make page group from post id {$this->id} because it is not a page post.");
    }

    if ( $this->count_children() === 0 ) {
      throw new \Exception("Cannot make page group from post id {$this->id} because it is not assigned as a parent of any page post.");
    }

  }

  /**
   * Count the direct child pages of the group.
   *
   * @param int $parent_post_id The post id of an existing page. Used to count how many page posts
   * have this id assigned as their post_parent. Default: 0 to use the current group object
   *
   * @return int The count
   *
   * @since 1.0.0 Introduced
   */
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

  /**
   * Retrieve the post ids of the direct child pages of the group.
   *
   * @return int[] The post ids
   *
   * @since 1.0.0 Introduced
   */
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

  /**
   * Count the direct child pages of the group that are assigned as a post_parent. This is the same
   * as counting the direct subgroups of the provided group.
   *
   * @param int $parent_post_id The post id of an existing page. Used to find child page posts that
   * have this id assigned as their post_parent. Default: 0 to use the current group object
   *
   * @return int The count
   *
   * @since 1.0.0 Introduced
   */
  function count_children_parents( int $parent_post_id = 0 ) : int {

    if ( $parent_post_id < 1 ) {
      $parent_post_id = $this->id;
    }

    global $wpdb;
    $child_count = $wpdb->get_var( $wpdb->prepare(
      "
        SELECT COUNT( DISTINCT posts.ID ) FROM {$wpdb->posts} posts
        WHERE posts.post_parent = %d
          AND posts.post_type = 'page'
          AND posts.ID IN( SELECT post_parent FROM {$wpdb->posts} WHERE post_parent != 0 AND post_type = 'page' )
        ORDER BY posts.post_title ASC
      ",
      $parent_post_id
    ) );

    if ( is_numeric( $child_count ) ) {
      return (int) $child_count;
    }

    return 0;

  }

  /**
   * Retrieve the post ids for the direct child pages of the group that are assigned as a post_parent.
   * This is the same as retrieving the direct subgroups of the group.
   *
   * @return int[] The post ids
   *
   * @since 1.0.0 Introduced
   */
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

  /**
   * Retrieve the post id for the group's post_parent. This is the same as retrieving the group id
   * of the parent group.
   *
   * @return int[] The post ids
   *
   * @since 1.0.0 Introduced
   */
  function get_parent_id( int $post_id = 0 ) : int {

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

  /**
   * Retrieve all post_parent ids up from the current group. This is usually used to generate
   * breadcrumb navigation.
   *
   * @return int[] The post ids from this group's post_parent to the toplevel post_parent
   *
   * @since 1.0.0 Introduced
   */
  function get_all_elder_ids() : array {

    $elder_ids = [];
    $elder_id = $this->get_parent_id();

    while ( $elder_id > 0 ) {
      $elder_ids[] = $elder_id;
      $elder_id = $this->get_parent_id( $elder_id );
    }

    if ( is_array( $elder_ids ) ) {
      return $elder_ids;
    }

    return [];

  }

}//end class
