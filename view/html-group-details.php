<?php
/**
 * Groups detailed admin page view
 *
 * Provides group details and navigation. View the details of or navigate to
 * child pages and subgroups of the current group.
 *
 * @since 1.0.0
 */

declare(strict_types=1);

namespace ptc_grouped_content;

defined( 'ABSPATH' ) || die();

global $ptc_grouped_content;
$redirect = TRUE;

if ( isset( $_GET['post_parent'] ) ) {
  require_once $this->plugin_path . 'src/class-ptc-content-group.php';
  try {
    $filtered_get_post_parent = (int) filter_input( INPUT_GET, 'post_parent', FILTER_SANITIZE_NUMBER_INT );
    if ( $filtered_get_post_parent === FALSE || $filtered_get_post_parent === NULL ) {
      throw new \Exception('Failed to filter a sanitized integer value from $_GET[post_parent].');
    }
    $content_group = new PTC_Content_Group( $filtered_get_post_parent );
    $redirect = FALSE;
  } catch ( \Exception $e ) {
    $redirect = TRUE;
  }
}

if ( $redirect ) {

  $the_post = get_post( $filtered_get_post_parent );

  if ( $the_post !== NULL && $the_post->post_parent > 0 ) {
    /* Redirect to parent group if id of child page was given */
    $redirect_url = $ptc_grouped_content->get_groups_list_admin_url( $the_post->post_parent );
  } else {
    $redirect_url = $ptc_grouped_content->get_groups_list_admin_url();
  }

  header( 'Location: ' . $redirect_url );
  die( 'We attempted to redirect you, yet here you are! Please <a href="' . esc_url( $redirect_url ) . '">click here to continue</a>.' );

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

  $the_post_parent = $content_group->post;
  echo '<span class="crumb crumb-current">' .
          esc_html( $the_post_parent->post_title ) .
        '</span>';
  ?>
</nav>

<div id="group-related-content">

  <aside id="left-sidebar">

    <?php
    /**
     * Fires before the subgroups section in the lefthand sidebar.
     *
     * @since 1.0.0
     *
     * @param \ptc_grouped_content\PTC_Content_Group $content_group The content
     * group currently being viewed.
     */
    do_action( 'ptc_view_grouped_content_before_subgroups', $content_group );
    ?>

    <section id="subgroups">
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
            try {
              $child_page_count = ( new PTC_Content_Group( $subgroup_id ) )->count_children();
              $page_or_pages = $child_page_count === 1 ? ' Page' : ' Pages';
            } catch ( \Exception $e ) {
              continue;
            }
            echo  '<p class="subgroup">' .
                    '<a href="' . esc_url( $view_subgroup_url ) . '">' .
                      '<i class="fas fa-folder"></i>' . esc_html( $subgroup->post_title ) .
                    '</a>' .
                    '&ndash;<span class="subgroup-children-count">' .
                    esc_html( $child_page_count ) . esc_html( $page_or_pages ) .
                    '</span>' .
                  '</p>';
          }//end foreach
        }//end else
        ?>
      </nav>
    </section>

    <?php
    /**
     * Fires after the subgroups section in the lefthand sidebar.
     *
     * @since 1.0.0
     *
     * @param \ptc_grouped_content\PTC_Content_Group $content_group The content
     * group currently being viewed.
     */
    do_action( 'ptc_view_grouped_content_after_subgroups', $content_group );
    ?>

  </aside>

  <main id="pages">

    <?php
    /**
     * Fires before the parent page section in the main column.
     *
     * @since 1.0.0
     *
     * @param \ptc_grouped_content\PTC_Content_Group $content_group The content
     * group currently being viewed.
     */
    do_action( 'ptc_view_grouped_content_before_parent', $content_group );
    ?>

    <section id="post-parent">
      <h1>Parent</h1>
      <?php
        output_post_row_header( TRUE );
        output_post_row( $content_group->post, TRUE );
      ?>
    </section>

    <?php
    /**
     * Fires after the parent page section in the main column.
     *
     * @since 1.0.0
     *
     * @param \ptc_grouped_content\PTC_Content_Group $content_group The content
     * group currently being viewed.
     */
    do_action( 'ptc_view_grouped_content_after_parent', $content_group );
    ?>

    <section id="children-posts">
      <h1>Children</h1>
        <?php
        output_post_row_header();
        foreach ( $content_group->get_all_children_ids() as $child_id ) {
          $child_post = get_post( $child_id );
          output_post_row( $child_post );
        }//end foreach
        ?>
    </section>

    <?php
    /**
     * Fires after the children pages section in the main column.
     *
     * @since 1.0.0
     *
     * @param \ptc_grouped_content\PTC_Content_Group $content_group The content
     * group currently being viewed.
     */
    do_action( 'ptc_view_grouped_content_after_children', $content_group );
    ?>

  </main>

  <aside id="right-sidebar">
    <?php
    /**
     * Fires in the righthand sidebar.
     *
     * @since 1.0.0
     *
     * @param \ptc_grouped_content\PTC_Content_Group $content_group The content
     * group currently being viewed.
     */
    do_action( 'ptc_view_grouped_content_right_sidebar', $content_group );
    ?>
  </aside>

</div>
<?php

/* HELPER FUNCTIONS */

/**
 * Displays the header row for a post row.
 *
 * @since 1.0.0
 *
 * @see \ptc_grouped_content\output_post_row() for outputting a corresponding
 * post row.
 *
 * @param bool $is_current_group Optional. Set to TRUE to not display the
 * "Subgroups" column header. Intended to match the argument with the same name
 * passed to \ptc_grouped_content\output_post_row(). Default FALSE.
 */
function output_post_row_header( bool $is_current_group = FALSE ) : void {
  ?>
  <div class="post-row-header">

    <div class="post-title">Page</div>

    <div class="post-subgroup"><?php echo $is_current_group ? '' : 'Subgroup'; ?></div>

    <div class="post-author">Author</div>

    <div class="post-date">Date</div>

  </div>
  <?php
}//end output_post_row_header()

/**
 * Displays information about a post.
 *
 * @since 1.0.0
 *
 * @see \ptc_grouped_content\output_post_row_header() for outputting a
 * corresponding header row.
 *
 * @param \WP_Post $the_post The post to display information about.
 *
 * @param bool $is_current_group Optional. Set to TRUE to not display the
 * "Subgroups" column content. Intended to match the argument with the same name
 * passed to \ptc_grouped_content\output_post_row_header(). Default FALSE.
 */
function output_post_row( \WP_Post $the_post, bool $is_current_group = FALSE ) : void {
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

/**
 * Displays date information for a post.
 *
 * @since 1.0.0
 *
 * @param \WP_Post $the_post The post for retrieving a date.
 */
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

}//end output_post_date()
