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

<nav id="hiearchy-breadcrumb">
  <?php
  $elder_ids = $content_group->get_all_elder_ids();

  $view_toplevel_url = $ptc_grouped_content->get_groups_list_admin_url();
  echo '<a href="' . esc_url( $view_toplevel_url ) . '" class="crumb crumb-home">' .
          'Home' .
        '</a>';
  if ( ! empty( $elder_ids ) ) {
    echo ' > ';
  }

  for ( $i = count($elder_ids) - 1; $i >= 0; --$i ) {
    $crumb = get_post( $elder_ids[ $i ] );
    $view_crumb_url = $ptc_grouped_content->get_groups_list_admin_url( $crumb->ID );
    echo '<a href="' . esc_url( $view_crumb_url ) . '" class="crumb">' .
            esc_html( $crumb->post_title ) .
          '</a>';
    if ( $i > 0 ) {
      echo ' > ';
    }
  }//end for
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
                    esc_html( $subgroup->post_title ) .
                  '</a>' .
                  '<span class="subgroup-children-count">' .
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
        output_post_row( $content_group->id );
      ?>
    </section>

    <section id="children-posts">
      <h1>Children</h1>
        <?php
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

function output_post_row( int $post_id ) {
  $the_post = get_post( $post_id );
  $the_parent_id = $the_post->post_parent;
  ?>
  <div class="post-row">

    <div class="post-title">
        <p>
          <a href="<?php echo esc_url( get_edit_post_link( $the_post->ID ) ); ?>">
            <?php echo esc_html( $the_post->post_title ); ?>
          </a>
        </p>
        <p>
          <?php echo esc_html( $the_post->post_type ); ?>
          <span class="post-status post-status_<?php echo esc_attr( $the_post->post_status ); ?>">
            <?php echo esc_html( $the_post->post_status ); ?>
          </span>
        </p>
        <?php display_row_action_links( $the_post ); ?>
    </div>

    <div class="subgroup-status">
      <?php
      try {
        $subgroup = new PTC_Content_Group( $the_post->ID );
        global $ptc_grouped_content;
        $view_subgroup_url = $ptc_grouped_content->get_groups_list_admin_url( $subgroup->id );
        echo  '<a href="' . esc_url( $view_subgroup_url ) . '">' .
                '<i class="fas fa-folder"></i>' .
              '</a>';
      } catch ( \Exception $e ) {
        echo '<span>&mdash;</span>';
      }
      ?>
    </div>

  </div>
  <?php
}//end output_post_row()
