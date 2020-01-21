<?php
/**
 * Content Group class
 *
 * The Grouped Content plugin mainly provides functionality to enhance page
 * hierarchies by providing additional relational information for page posts.
 * The PTC_Content_Group class is the core class that provides this information.
 *
 * @since 1.0.0
 */

declare(strict_types=1);

namespace ptc_grouped_content;

defined( 'ABSPATH' ) || die();

if ( ! class_exists( '\ptc_grouped_content\PTC_Content_Group' ) ) {
  /**
   * Maintain content related to a post_parent which represents a group.
   */
  class PTC_Content_Group {

    /**
     * @var int The id of the post_parent that this group represents.
     *
     * @since 1.0.0
     */
    public $id;

    /**
     * @var \WP_Post The post that this group represents.
     *
     * @since 1.0.0
     */
    public $post;

    /**
     * Get all post ids of pages assigned as a post_parent.
     *
     * @since 1.0.0
     *
     * @return int[] The post ids.
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
          ORDER BY parents.menu_order, parents.post_title ASC
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
     * Get all post ids of pages assigned as a post_parent that
     * do not have a post_parent (where post_parent = 0, the default).
     *
     * @since 1.0.0
     *
     * @return int[] The post ids.
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
          ORDER BY parents.menu_order, parents.post_title ASC
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
     * Validates object creation and sets member variables.
     *
     * @since 1.0.0
     *
     * @param int $parent_post_id The post id of an existing page that is
     * assigned as the post_parent on other page posts.
     *
     * @throws \Exception if the provided post id is invalid to represent a page
     * group by the provided id:
     * * not being greater than 1
     * * not belonging to an existing "page" typed post
     * * not having any children pages
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
     * @since 1.0.0
     *
     * @param int $parent_post_id Optional. The post id of an existing page.
     * Default 0 to use the current group object.
     *
     * @return int The count
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
     * @since 1.0.0
     *
     * @return int[] The post ids.
     */
    function get_all_children_ids() : array {

      global $wpdb;
      $children_post_ids = $wpdb->get_results( $wpdb->prepare(
        "
          SELECT DISTINCT posts.ID FROM {$wpdb->posts} posts
          WHERE posts.post_parent = %d
            AND posts.post_type = 'page'
          ORDER BY posts.menu_order, posts.post_title ASC
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
     * Count the direct child pages of the group that are assigned as a
     * post_parent. This is analogous to counting direct subgroups of the group.
     *
     * @since 1.0.0
     *
     * @param int $parent_post_id Optional. The post id of an existing page.
     * Default 0 to use the current group object.
     *
     * @return int The count.
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
          ORDER BY posts.menu_order, posts.post_title ASC
        ",
        $parent_post_id
      ) );

      if ( is_numeric( $child_count ) ) {
        return (int) $child_count;
      }

      return 0;

    }

    /**
     * Retrieve the post ids for the direct child pages of the group that are
     * assigned as a post_parent. This is analogous to retrieving the ids of
     * direct subgroups of the group.
     *
     * @since 1.0.0
     *
     * @return int[] The post ids.
     */
    function get_child_parent_ids() : array {

      global $wpdb;
      $child_parent_post_ids = $wpdb->get_results( $wpdb->prepare(
        "
          SELECT DISTINCT posts.ID FROM {$wpdb->posts} posts
          WHERE posts.post_parent = %d
            AND posts.post_type = 'page'
            AND posts.ID IN( SELECT post_parent FROM {$wpdb->posts} WHERE post_parent != 0 AND post_type = 'page' )
          ORDER BY posts.menu_order, posts.post_title ASC
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
     * Retrieve the post_parent of a post.
     *
     * @since 1.0.0
     *
     * @param int $post_id Optional. The post id to retrieve the post_parent.
     * Default 0 to use the current group.
     *
     * @return int[] The post ids.
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
     * Retrieve all post_parent ids up from the current group.
     *
     * @since 1.0.0
     *
     * @return int[] The post ids from this group's post_parent to the toplevel
     * post_parent.
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

}//end if class_exists
