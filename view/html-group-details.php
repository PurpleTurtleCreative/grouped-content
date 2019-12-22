<?php

declare(strict_types=1);

namespace ptc_grouped_content;

defined( 'ABSPATH' ) || die();

/**
 * View content related to a post_parent which represents a content group.
 *
 * @author Michelle Blanchette <michelle@purpleturtlecreative.com>
 */

global $ptc_grouped_content;
$redirect = TRUE;

if ( isset( $_GET['post_parent'] ) ) {
  require_once $this->plugin_path . 'src/class-ptc-content-group.php';
  try {
    $filtered_get_post_parent = (int) filter_input( INPUT_GET, 'post_parent', FILTER_SANITIZE_NUMBER_INT );
    if ( $filtered_get_post_parent === FALSE || $filtered_get_post_parent === NULL ) {
      throw new \Exception('Failed to filter a sanitized integer value from _GET[post_parent].');
    }
    $content_group = new PTC_Content_Group( $filtered_get_post_parent );
    $redirect = FALSE;
  } catch ( \Exception $e ) {
    $redirect = TRUE;
  }
}

if ( $redirect ) {

  $the_post = get_post( $filtered_get_post_parent );
  $the_parent_id = $the_post->post_parent;

  if ( $the_post !== NULL && $the_parent_id > 0 ) {
    $redirect_url = $ptc_grouped_content->get_groups_list_admin_url( $the_parent_id );
  } else {
    $redirect_url = $ptc_grouped_content->get_groups_list_admin_url();
  }

  header( 'Location: ' . $redirect_url );
  die('We attempted to redirect you, yet here you are! Please <a href="' . esc_url( $redirect_url ) . '">click here to continue</a>.');

}

/* END VALIDATION */

require_once $ptc_grouped_content->plugin_path . 'view/wp-handle-row-actions.php';

?>

<nav id="hierarchy-breadcrumb">
  <?php
  $view_toplevel_url = $ptc_grouped_content->get_groups_list_admin_url();
  echo '<a href="' . esc_url( $view_toplevel_url ) . '" class="crumb crumb-home">' .
          'Home' .
        '</a>';
  echo ' > ';

  $elder_ids = $content_group->get_all_elder_ids();
  for ( $i = count($elder_ids) - 1; $i >= 0; --$i ) {
    $crumb = get_post( $elder_ids[ $i ] );
    $view_crumb_url = $ptc_grouped_content->get_groups_list_admin_url( $crumb->ID );
    echo '<a href="' . esc_url( $view_crumb_url ) . '" class="crumb">' .
            esc_html( $crumb->post_title ) .
          '</a>';
    echo ' > ';
  }//end for

  $the_post_parent = get_post( $content_group->id );
  echo '<span class="crumb crumb-current">' .
          esc_html( $the_post_parent->post_title ) .
        '</span>';
  ?>
</nav>

<div id="group-related-content">

  <aside id="subgroups">
    <h1>Subgroups</h1>
    <nav>
      <?php
      $subgroup_ids = $content_group->get_child_parent_ids();

      if ( empty( $subgroup_ids ) ) {
        echo  '<p class="subgroups-empty">' .
                '<i class="fas fa-folder-open"></i>' .
                'There are no subgroups.' .
              '</p>';
      } else {
        foreach ( $subgroup_ids as $subgroup_id ) {
          $subgroup = get_post( $subgroup_id );
          $view_subgroup_url = $ptc_grouped_content->get_groups_list_admin_url( $subgroup->ID );
          echo  '<p class="subgroup">' .
                  '<a href="' . esc_url( $view_subgroup_url ) . '">' .
                    '<i class="fas fa-folder"></i>' . esc_html( $subgroup->post_title ) .
                  '</a>' .
                  '&ndash;<span class="subgroup-children-count">' .
                  esc_html( ( new PTC_Content_Group( $subgroup_id ) )->count_children() ) .
                  ' Pages</span>' .
                '</p>';
        }//end foreach
      }//end else
      ?>
    </nav>
  </aside>

  <main id="pages">

    <section id="post-parent">
      <h1>Parent</h1>
      <?php
        output_post_row_header( TRUE );
        output_post_row( $content_group->id, TRUE );
      ?>
    </section>

    <section id="children-posts">
      <h1>Children</h1>
        <?php
        output_post_row_header();
        foreach ( $content_group->get_all_children_ids() as $child_id ) {
          output_post_row( $child_id );
        }//end foreach
        ?>
    </section>

  </main>

  <aside id="assigned-menu">
    <h1>Menu</h1>
    <!-- List item objects and their object titles and list item type (Page, Media, Custom URL, etc.) -->
  </aside>

</div>
<?php

/* HELPER FUNCTIONS */

function output_post_row_header( bool $is_current_group = FALSE ) : void {
  ?>
  <div class="post-row-header">

    <div class="post-title">Page</div>

    <div class="post-subgroup"><?php echo $is_current_group ? '' : 'Subgroup'; ?></div>

    <div class="post-author">Author</div>

    <div class="post-date">Date</div>

  </div>
  <?php
}

function output_post_row( int $post_id, bool $is_current_group = FALSE ) : void {
  $the_post = get_post( $post_id );
  if ( NULL === $the_post ) {
    return;
  }
  ?>
  <div class="post-row">

    <div class="post-title">
      <p class="post-heading">
        <a href="<?php echo esc_url( get_edit_post_link( $the_post->ID ) ); ?>"><?php echo esc_html( $the_post->post_title ); ?></a>
        <?php
        if ( $the_post->post_status !== 'publish' ) {
          echo  '<span class="post-status post-status_' . esc_attr( $the_post->post_status ) . '">' .
                  esc_html( $the_post->post_status ) .
                '</span>';
        }
        ?>
      </p>
      <?php display_row_action_links( $the_post ); ?>
    </div>

    <div class="post-subgroup">
      <?php
      if ( ! $is_current_group ) {
        try {
          $subgroup = new PTC_Content_Group( $the_post->ID );
          global $ptc_grouped_content;
          $view_subgroup_url = $ptc_grouped_content->get_groups_list_admin_url( $subgroup->id );
          echo  '<a href="' . esc_url( $view_subgroup_url ) . '">' .
                  '<i class="fas fa-folder"></i>' .
                '</a>';
        } catch ( \Exception $e ) {
          echo '<span class="default">&mdash;</span>';
        }
      }
      ?>
    </div>

    <div class="post-author">
      <?php
      $the_author = get_user_by( 'ID', $the_post->post_author );
      if ( FALSE !== $the_author ) {
        echo get_avatar( $the_author->ID, 25, 'mystery' );
        echo  '<a class="author-name" href="' . esc_url( admin_url( 'edit.php?post-type=page&author=' . $the_author->ID ) ) . '">' .
                esc_html( $the_author->display_name ) .
              '</a>';
      } else {
        echo '<span class="default">&mdash;</span>';
      }
      ?>
    </div>

    <div class="post-date">
      <?php
      $the_date = get_the_date( '', $the_post );
      if ( FALSE !== $the_date ) {
        echo '<p class="the-date">';
        output_post_date( $the_post );
        echo '</p>';
      } else {
        echo '<span class="default">&mdash;</span>';
      }
      ?>
    </div>

  </div>
  <?php
}//end output_post_row()

function output_post_date( \WP_Post $the_post ) : void {

  if ( '0000-00-00 00:00:00' === $the_post->post_date ) {
    $t_time    = __( 'Unpublished' );
    $h_time    = $t_time;
  } else {
    $t_time    = get_the_time( __( 'Y/m/d g:i:s a' ), $the_post );
    $h_time = get_the_time( __( 'Y/m/d' ), $the_post );
  }

  if ( 'publish' === $the_post->post_status ) {
    $status = __( 'Published' );
  } else {
    $status = __( 'Last Modified' );
  }

  echo esc_html( $status ) . '<br />';
  echo '<span title="' . esc_attr( $t_time ) . '">' . esc_html( $h_time ) . '</span>';

}
