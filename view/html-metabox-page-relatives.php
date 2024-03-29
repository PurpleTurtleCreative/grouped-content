<?php
/**
 * Page Relatives metabox content
 *
 * Displays links to related pages and groups for a post including, when
 * relevant, the current page's parent, siblings, and children.
 *
 * @since 1.0.0
 */

declare(strict_types=1);

namespace ptc_grouped_content;

defined( 'ABSPATH' ) || die();

/* Use passed post if AJAX refresh, else use global $post */
if (
  isset( $post_id )
  && isset( $the_post )
  && isset( $res )
  && isset( $nonce )
) {

  $res['status'] = 'success';

  if (
    NULL === $the_post
    || FALSE === wp_verify_nonce( $nonce, 'ptc_page_relatives' )
  ) {
    $res['status'] = 'fail';
    return;
  }

} else {

  global $post;
  $the_post = $post;

}

/* Metabox Content */
if ( isset( $the_post ) && 'page' == $the_post->post_type ) {

  $is_tree_displayed = output_page_family_subtree( $the_post );

  if ( $is_tree_displayed ) {
    echo '<p class="page-family-tree-help"><i class="fa fa-mouse-pointer"></i><strong>TIP:</strong> Click a folder icon to visit the group. Click a page title to edit that page.</p>';
  }

} else {
  return;
}

/* HELPER FUNCTIONS */

/**
 * Displays related pages and groups in a directory tree format.
 *
 * @since 1.0.0
 *
 * @param \WP_Post $post The post for displaying related content.
 *
 * @return bool TRUE if a tree was able to be output, FALSE if no output was
 * possible.
 */
function output_page_family_subtree( \WP_Post $post ) : bool {

  global $ptc_grouped_content;
  require_once $ptc_grouped_content->plugin_path . 'src/class-ptc-content-group.php';

  echo '<div class="page-family-tree" data-post-id="' . esc_html( $post->ID ) . '">';

  $post->post_parent = (int) $post->post_parent;

  try {
    $parent_content_group = new PTC_Content_Group( $post->post_parent );
  } catch ( \Exception $e ) {
    $parent_content_group = NULL;
  }

  $post->ID = (int) $post->ID;

  try {
    $this_content_group = new PTC_Content_Group( $post->ID );
  } catch ( \Exception $e ) {
    $this_content_group = NULL;
  }

  if ( $parent_content_group === NULL && $this_content_group === NULL ) {
    echo '<p class="page-family-tree_no-family"><em>This page has no relatives.</em></p>';
    echo '</div>';/* close div.page-family-tree wrapper */
    return FALSE;
  } elseif ( $parent_content_group === NULL ) {
    echo '<p class="page-family-tree_no-parent"><em>This page has no parent.</em></p>';
    output_page_family_subtree_children( $this_content_group, $post->ID );
    echo '</div>';/* close div.page-family-tree wrapper */
    return TRUE;
  } elseif ( $this_content_group === NULL ) {
    output_page_family_subtree_children( $parent_content_group, $post->ID );
    echo '</div>';/* close div.page-family-tree wrapper */
    return TRUE;
  }

  /* BEGIN DRAWING */
  try {

    echo  '<p class="post-parent">' .
            group_icon_link( $parent_content_group->id, 'fa fa-folder-open' ) .
            '<a class="post-title" href="' . esc_url( get_edit_post_link( $parent_content_group->post->ID ) ) . '">' .
              esc_html( $parent_content_group->post->post_title ) .
            '</a>' .
          '</p>';

    $sibling_ids = $parent_content_group->get_all_children_ids();
    $sibling_ids_count = count( $sibling_ids );

    if ( $sibling_ids_count < 1 ) {
      throw new \Exception( "Sibling page {$parent_content_group->id} is a parent with no children...?" );
    }

    foreach ( $sibling_ids as $i => $sibling_id ) {

      if ( $this_content_group->id === $sibling_id ) {
        $class_list = 'post-sibling post-current';
        $p = $this_content_group->post;
        if ( $this_content_group->count_children() < 1 ) {
          $if_list_children = FALSE;
        } else {
          $if_list_children = TRUE;
        }
      } else {
        $class_list = 'post-sibling';
        $p = get_post( $sibling_id );
        $if_list_children = FALSE;
      }

      if ( NULL === $p || 'page' !== $p->post_type ) {
        throw new \Exception( "Sibling post $sibling_id is an invalid page post." );
      }

      if ( $if_list_children ) {
        $group_link = group_icon_link( $p->ID, 'fa fa-folder-open' );
      } else {
        $group_link = group_icon_link( $p->ID, 'fa fa-folder' );
      }

      if ( $i < $sibling_ids_count - 1 ) {
        $box_drawing_entity = '&#9507;'; /* vertical and right */
        $is_last_subtree = FALSE;
      } else {
        $box_drawing_entity = '&#9495;'; /* up and right */
        $is_last_subtree = TRUE;
      }

      echo  '<p class="' . esc_attr( $class_list ) . '">' .
              '<span class="box-level-1">' . $box_drawing_entity . '</span>' .
              $group_link .
              '<a class="post-title" href="' . esc_url( get_edit_post_link( $p->ID ) ) . '">' .
                esc_html( $p->post_title ) .
              '</a>' .
            '</p>';

      if ( $if_list_children ) {
        output_page_family_subtree_children(
          $this_content_group,
          $this_content_group->id,
          TRUE,
          $is_last_subtree
        );
      }//end if if_list_children

    }//end foreach sibling_ids

  } catch ( \Exception $e ) {
    error_log( '[' . __FILE__ . '] ' . $e->getMessage() );
    echo '<p class="caught-exception">An error occurred while generating page family tree. Please review the debug log if enabled.</p>';
  }

  echo '</div>';/* close div.page-family-tree wrapper */

  return TRUE;

}//end output_page_family_subtree()

/**
 * Displays children pages and groups in a directory tree format.
 *
 * @since 1.0.0
 *
 * @param \ptc_grouped_content\PTC_Content_Group $root_group The group for
 * displaying.
 *
 * @param int $current_post_id Optional. Used for classifying the current post.
 * Default 0 for no current post.
 *
 * @param bool $is_subtree Optional. Set to TRUE if the provided root group
 * should be output as the parent. Default FALSE.
 *
 * @param bool $is_last_subtree Optional. Determines the box-level-1 glyph.
 * Vertical line if TRUE, nothing if FALSE. Default FALSE.
 *
 * @throws \Exception if a truly exceptional situation has occurred such as:
 * * the $root_group has no children
 * * a child is retrieved that is not an actual page-type post
 */
function output_page_family_subtree_children( PTC_Content_Group $root_group, int $current_post_id = 0, bool $is_subtree = FALSE, bool $is_last_subtree = FALSE ) {

  if ( ! $is_subtree ) {
    if ( $root_group->id === $current_post_id ) {
      $class_list = 'post-parent post-current';
    } else {
      $class_list = 'post-parent';
    }
    echo  '<p class="' . esc_attr( $class_list ) . '">' .
            group_icon_link( $root_group->id, 'fa fa-folder-open' ) .
            '<a class="post-title" href="' . esc_url( get_edit_post_link( $root_group->post->ID ) ) . '">' .
              esc_html( $root_group->post->post_title ) .
            '</a>' .
          '</p>';
    $box_drawing_parent_level = '';
  } else {
    $box_drawing_parent_level = $is_last_subtree ?
                                '<span class="box-level-1"></span>' :
                                '<span class="box-level-1">&#9475;</span>';
  }

  $children_ids = $root_group->get_all_children_ids();
  $children_ids_count = count( $children_ids );

  if ( $children_ids_count < 1 ) {
    throw new \Exception( "Page {$root_group->id} is a parent with no children...?" );
  }

  foreach ( $children_ids as $i => $child_id ) {

    $p = get_post( $child_id );
    if ( NULL === $p || 'page' !== $p->post_type ) {
      throw new \Exception( "Child post $child_id is an invalid page post." );
    }

    if ( $current_post_id === $child_id ) {
      $class_list = 'child-post post-current';
    } else {
      $class_list = 'child-post';
    }

    if ( $i < $children_ids_count - 1 ) {
      $box_drawing_entity = '&#9507;'; /* vertical and right */
    } else {
      $box_drawing_entity = '&#9495;'; /* up and right */
    }

    echo  '<p class="' . esc_attr( $class_list ) . '">' .
            $box_drawing_parent_level .
            '<span class="box-level-2">' . $box_drawing_entity . '</span>' .
            group_icon_link( $p->ID, 'fa fa-folder' ) .
            '<a class="post-title" href="' . esc_url( get_edit_post_link( $p->ID ) ) . '">' .
              esc_html( $p->post_title ) .
            '</a>' .
          '</p>';

  }//end foreach $children_ids

}//end output_page_family_subtree_children()

/**
 * Returns HTML of a Font Awesome icon linking to a group details page.
 *
 * @since 1.0.0
 *
 * @see https://fontawesome.com/icons?d=gallery&s=solid&m=free for free icons
 * included in this plugin.
 *
 * @param int $post_id The post id for creating the group link.
 *
 * @param string $fontawesome_classlist Optional. The Font Awesome class list
 * for the icon. Default 'fa fa-folder'.
 *
 * @param string $fallback_html Optional. HTML to return if the provided
 * $post_id cannot represent a group. Default ''.
 *
 * @return string The anchor tag.
 */
function group_icon_link( int $post_id, string $fontawesome_classlist = 'fa fa-folder', string $fallback_html = '' ) : string {
  try {
    $subgroup = new PTC_Content_Group( $post_id );
    global $ptc_grouped_content;
    $view_subgroup_url = $ptc_grouped_content->get_groups_list_admin_url( $subgroup->id );
    return  '<a class="group-link" href="' . esc_url( $view_subgroup_url ) . '">' .
              '<i class="' . esc_attr( $fontawesome_classlist ) . '"></i>' .
            '</a>';
  } catch ( \Exception $e ) {
    return $fallback_html;
  }
}//end group_icon_link()
