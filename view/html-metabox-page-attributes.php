<?php

declare(strict_types=1);

namespace ptc_grouped_content;

defined( 'ABSPATH' ) || die();

/**
 * List groups and sibling links based on current page post's post_parent.
 *
 * @author Michelle Blanchette <michelle@purpleturtlecreative.com>
 */

global $post;
// TODO: if not defined due to AJAX refresh for Gutenberg, check POSTed id and get_post

if ( isset( $post ) && 'page' == $post->post_type ) {

  output_page_family_subtree( $post );

} else {
  return;
}

/* HELPERS */

function output_page_family_subtree( \WP_Post $post ) : void {

  global $ptc_grouped_content;
  require_once $ptc_grouped_content->plugin_path . 'src/class-ptc-content-group.php';

  echo '<div class="page-family-tree" data-post-id="' . esc_html( $post->ID ) . '">';

  try {
    $parent_content_group = new PTC_Content_Group( $post->post_parent );
  } catch ( \Exception $e ) {
    $parent_content_group = NULL;
  }

  try {
    $this_content_group = new PTC_Content_Group( $post->ID );
  } catch ( \Exception $e ) {
    $this_content_group = NULL;
  }

  if ( $parent_content_group === NULL && $this_content_group === NULL ) {
    echo '<p class="page-family-tree_no-family"><em>This page has no relatives.</em></p>';
  } elseif ( $parent_content_group === NULL ) {
    echo '<p class="page-family-tree_no-parent"><em>This page has no parent.</em></p>';
    output_page_family_subtree_children( $this_content_group );
    return;
  } elseif ( $this_content_group === NULL ) {
    output_page_family_subtree_children( $parent_content_group );
    return;
  }

  /* BEGIN DRAWING */
  try {

    echo  '<p class="post-parent">' .
            group_icon_link( $parent_content_group->id, 'fas fa-folder-open' ) .
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

      if ( $sibling_id === $this_content_group->id ) {
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
        $group_link = group_icon_link( $p->ID, 'fas fa-folder-open' );
      } else {
        $group_link = group_icon_link( $p->ID, 'fas fa-folder' );
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
        output_page_family_subtree_children( $this_content_group, TRUE, $is_last_subtree );
      }//end if if_list_children

    }//end foreach sibling_ids

  } catch ( \Exception $e ) {
    error_log( '[' . __FILE__ . '] ' . $e->getMessage() );
    echo '<p class="caught-exception">An error occurred while generating page family tree. Please review the debug log if enabled.</p>';
  }

  echo '</div>';/* close div.page-family-tree wrapper */

}//end output_page_family_subtree()

function output_page_family_subtree_children( PTC_Content_Group $root_group, bool $is_subtree = FALSE, bool $is_last_subtree = FALSE ) : void {

  if ( ! $is_subtree ) {
    echo  '<p class="post-parent post-current">' .
            group_icon_link( $root_group->id, 'fas fa-folder-open' ) .
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

    if ( $i < $children_ids_count - 1 ) {
      $box_drawing_entity = '&#9507;'; /* vertical and right */
    } else {
      $box_drawing_entity = '&#9495;'; /* up and right */
    }

    echo  '<p class="child-post">' .
            $box_drawing_parent_level .
            '<span class="box-level-2">' . $box_drawing_entity . '</span>' .
            group_icon_link( $p->ID, 'fas fa-folder' ) .
            '<a class="post-title" href="' . esc_url( get_edit_post_link( $p->ID ) ) . '">' .
              esc_html( $p->post_title ) .
            '</a>' .
          '</p>';

  }//end foreach $children_ids

}//end output_page_family_subtree_children()

function group_icon_link( int $post_id, string $fontawesome_classlist = 'fas fa-folder', string $fallback_html = '' ) : string {
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
