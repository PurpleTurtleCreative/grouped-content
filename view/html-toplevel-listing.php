<?php

declare(strict_types=1);

namespace ptc_grouped_content;

defined( 'ABSPATH' ) || die();

/**
 * View top-level groups.
 *
 * @author Michelle Blanchette <michelle@purpleturtlecreative.com>
 */

global $ptc_grouped_content;
require_once $ptc_grouped_content->plugin_path . 'src/class-ptc-content-group.php';

$toplevel_parent_ids = PTC_Content_Group::get_all_toplevel_parent_ids();
$count = count( $toplevel_parent_ids );

?>

<h1>Main Groups</h1>
<p><?php echo esc_html( $count ); ?> Total</p>

<main id="ptc-content-groups">

<?php
foreach ( $toplevel_parent_ids as $i => $parent_id ) {

  $the_post = get_post( $parent_id );
  $the_group = new PTC_Content_Group( $parent_id );
  $view_group_url = $ptc_grouped_content->get_groups_list_admin_url( $parent_id );
  ?>

  <section class="ptc-content-group" data-post-id="<?php echo esc_attr( $parent_id ); ?>">

      <div>
        <p><a class="title" href="<?php echo esc_url( $view_group_url ); ?>"><?php echo esc_html( $the_post->post_title ); ?></a></p>
        <p><?php echo esc_html( $the_group->count_children() ); ?> Children</p>
      </div>

  </section>

  <?php
}//end foreach $toplevel_parent_ids
?>

</main>
