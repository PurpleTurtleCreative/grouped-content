<?php
/**
 * Groups home admin page view
 *
 * Lists group links and information for all toplevel groups. This is used as
 * the "home" Groups page when no valid group id is provided.
 *
 * @since 1.0.0
 *
 * @package PTC_Grouped_Content
 */

namespace PTC_Grouped_Content;

defined( 'ABSPATH' ) || die();

require_once PLUGIN_PATH . 'src/includes/class-ptc-content-group.php';

$toplevel_parent_ids = PTC_Content_Group::get_all_toplevel_parent_ids( $GLOBALS['typenow'] );
$count = count( $toplevel_parent_ids );
?>

<main>

	<section id="toplevel-groups">
		<h1>Main Groups <span id="count-total-toplevel-groups"><?php echo esc_html( $count ); ?> Total</span></h1>

		<div id="ptc-content-group-header" class="post-row-header">
			<div class="col-group-title">Group</div>
			<div class="col-subgroups">Subgroups</div>
			<div class="col-unpublished-pages">Unpublished Pages</div>
			<div class="col-total-pages">Total Pages</div>
		</div>

		<?php
		foreach ( $toplevel_parent_ids as $i => $parent_id ) {

			try {
				$the_group = new PTC_Content_Group( $parent_id );
			} catch ( \Exception $e ) {
				continue;
			}

			$the_post = get_post( $parent_id );
			$view_group_url = Main::get_groups_list_admin_url( $parent_id );

			$child_page_count = $the_group->count_children();
			$child_page_or_pages = ( 1 === $child_page_count ) ? 'Child Page' : 'Child Pages';

			$child_subgroups = $the_group->get_child_parent_ids();
			$child_subgroups_count = $the_group->count_children_parents();
			$subgroup_or_subgroups = ( 1 === $child_subgroups_count ) ? 'Subgroup' : 'Subgroups';

			$descendant_ids = $the_group->get_all_descendant_ids();
			$descendant_ids_count = count( $descendant_ids ) + 1;/* Include parent page in count */
			?>

			<div class="ptc-content-group post-row" data-post-id="<?php echo esc_attr( $parent_id ); ?>">

					<div class="col-group-title">
						<a class="title" href="<?php echo esc_url( $view_group_url ); ?>"><i class="fa fa-folder"></i><?php echo esc_html( $the_post->post_title ); ?></a>
						<?php
						if ( 'publish' !== $the_post->post_status ) {
							echo '<span class="post-status post-status_' . esc_attr( $the_post->post_status ) . '">' .
											esc_html( $the_post->post_status ) .
										'</span>';
						}
						?>
					</div>

					<div class="col-subgroups">
						<div class="container">
							<?php
							if ( ! empty( $child_subgroups ) ) {
								foreach ( $child_subgroups as $subgroup_id ) {
									try {
										$subgroup = new PTC_Content_Group( $subgroup_id );
										$view_subgroup_url = Main::get_groups_list_admin_url( $subgroup->post->ID );
										echo '<a href="' . esc_url( $view_subgroup_url ) . '">' .
														'<i class="fa fa-folder"></i>' .
														esc_html( $subgroup->post->post_title ) .
													'</a>';
									} catch ( \Exception $e ) {
										echo '<p class="default subgroups-none"><i class="fa fa-folder-open"></i>There are no subgroups.</p>';
									}
								}
							} else {
								echo '<p class="default subgroups-none"><i class="fa fa-folder-open"></i>There are no subgroups.</p>';
							}
							?>
						</div>
					</div>

					<div class="col-unpublished-pages">
						<div class="container">
							<?php
							$total_pages_by_status = PTC_Content_Group::organize_posts_by( $descendant_ids, 'post_status' );
							$post_status_keys = array_keys( $total_pages_by_status );
							$found_unpublished = false;

							foreach ( $post_status_keys as $post_status ) {
								if ( 'publish' !== $post_status ) {
									echo '<p class="count-post-status post-status_' . esc_attr( $post_status ) . '">' .
													esc_html( count( $total_pages_by_status[ $post_status ] ) ) .
													'<span class="post-status post-status_' . esc_attr( $post_status ) . '">' .
														esc_html( $post_status ) .
													'</span>' .
												'</p>';
									/* TODO: List page edit links when plus-sign clicked via AJAX */
									$found_unpublished = true;
								}
							}//end foreach $post_status_keys

							if ( ! $found_unpublished ) {
								echo '<p class="default count-post-status-none"><i class="fa fa-check"></i>All published!</p>';
							}
							?>
						</div>
					</div>

					<div class="col-total-pages">
						<div class="container">
							<p class="count-total-descendants">
								<i class="fa fa-files-o"></i><?php echo esc_html( "$descendant_ids_count Total Pages" ); ?>
							</p>
							<p class="count-total-child-pages">
								<i class="fa fa-file-o"></i><?php echo esc_html( "$child_page_count $child_page_or_pages" ); ?>
							</p>
						</div>
					</div>

			</div>
			<?php
		}//end foreach $toplevel_parent_ids
		?>
		</section>
</main>
