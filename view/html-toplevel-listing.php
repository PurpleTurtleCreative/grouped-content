<?php
/**
 * Groups home admin page view
 *
 * Lists group links and information for all toplevel groups. This is used as
 * the "home" Groups page when no valid group id is provided.
 *
 * @since 1.0.0
 */

declare(strict_types=1);

namespace ptc_grouped_content;

defined( 'ABSPATH' ) || die();

global $ptc_grouped_content;
require_once $ptc_grouped_content->plugin_path . 'src/class-ptc-content-group.php';

$toplevel_parent_ids = PTC_Content_Group::get_all_toplevel_parent_ids();
$count = count( $toplevel_parent_ids );

?>

<h1 id="toplevel-parents">Main Groups <span id="count-total-toplevel-groups"><?php echo esc_html( $count ); ?> Total</span></h1>

<main id="ptc-toplevel-content-groups">

<?php
foreach ( $toplevel_parent_ids as $i => $parent_id ) {

  try {
    $the_group = new PTC_Content_Group( $parent_id );
  } catch ( \Exception $e ) {
    continue;
  }

  $the_post = get_post( $parent_id );
  $view_group_url = $ptc_grouped_content->get_groups_list_admin_url( $parent_id );

  $child_page_count = $the_group->count_children();
  $page_or_pages = $child_page_count === 1 ? 'Page' : 'Pages';

  $child_subgroups_count = $the_group->count_children_parents();
  $subgroup_or_subgroups = $child_subgroups_count === 1 ? 'Subgroup' : 'Subgroups';
  ?>

  <section class="ptc-content-group" data-post-id="<?php echo esc_attr( $parent_id ); ?>">
      <div>
        <a class="title" href="<?php echo esc_url( $view_group_url ); ?>"><i class="fas fa-folder"></i><?php echo esc_html( $the_post->post_title ); ?></a>
        &ndash;
        <span class="child-counts">
          <?php echo esc_html( "{$child_page_count} {$page_or_pages}, {$child_subgroups_count} {$subgroup_or_subgroups}" ); ?>
        </span>
      </div>
  </section>

  <?php
}//end foreach $toplevel_parent_ids
?>

</main>
