<?php
/**
 * Content Generator form submission processing
 *
 * Processes the submission of the Content Generator form and displays notices.
 *
 * @since 1.1.0
 *
 * @package PTC_Grouped_Content
 */

namespace PTC_Grouped_Content;

defined( 'ABSPATH' ) || die();

require_once PLUGIN_PATH . 'src/includes/class-ptc-content-generator.php';

if (
	isset( $_POST['generate_content'] )
	&& current_user_can( 'publish_pages' )
	&& isset( $_POST['generate_content_nonce'] )
	&& false !== wp_verify_nonce( $_POST['generate_content_nonce'], 'generate_content' )
) {

	display_notice( 'info', 'Generating content...' );

	try {

		if (
			isset( $_POST['parent_page_id'] )
			&& is_numeric( $_POST['parent_page_id'] )
		) {

			$parent_page_id = (int) filter_var( wp_unslash( $_POST['parent_page_id'] ), FILTER_SANITIZE_NUMBER_INT );
			$created_parent_page = false;

			if (
				isset( $_POST['children_page_titles'] )
				&& ! empty( $_POST['children_page_titles'] )
			) {

				$children_page_titles = filter_var(
					wp_unslash( $_POST['children_page_titles'] ),
					FILTER_SANITIZE_STRING
				);

				$children_page_titles = preg_split( '/\r\n|[\r\n]/', $children_page_titles );
				display_notice( 'info', 'Counted ' . count( $children_page_titles ) . ' child page titles.' );

				if ( $parent_page_id < 0 ) {
					/* Create parent page */
					if (
						isset( $_POST['new_parent_page_title'] )
						&& ! empty( $_POST['new_parent_page_title'] )
					) {

						$new_parent_page_title = filter_var(
							wp_unslash( $_POST['new_parent_page_title'] ),
							FILTER_SANITIZE_STRING
						);

						$new_parent_page_id_arr = PTC_Content_Generator::create_pages_from_titles( [ $new_parent_page_title ] );

						if (
							isset( $new_parent_page_id_arr[0] )
							&& ! empty( $new_parent_page_id_arr[0] )
							&& is_int( $new_parent_page_id_arr[0] )
							&& $new_parent_page_id_arr[0] > 0
						) {

							$parent_page_id = $new_parent_page_id_arr[0];
							$parent_page_post = get_post( $parent_page_id );

							if ( null !== $parent_page_post && 'page' === $parent_page_post->post_type ) {
								$created_parent_page = true;
								display_notice( 'success', 'Created parent page: <a href="' . esc_url( get_edit_post_link( $parent_page_post->ID ) ) . '">' . esc_html( $parent_page_post->post_title ) . '</a>' );
							} else {
								throw new \Exception( 'Something went wrong when creating the parent page.' );
							}
						} else {
							throw new \Exception( 'Failed to create parent page.' );
						}
					} else {
						throw new \Exception( 'A page title is required to create a new parent page.' );
					}
				} elseif ( $parent_page_id > 0 ) {
					/* Use existing page as parent */
					$parent_page_post = get_post( $parent_page_id );

					if ( null !== $parent_page_post && 'page' === $parent_page_post->post_type ) {
						display_notice( 'info', 'Using parent page: <a href="' . esc_url( get_edit_post_link( $parent_page_post->ID ) ) . '">' . esc_html( $parent_page_post->post_title ) . '</a>' );
					} else {
						throw new \Exception( "Failed to use parent page {$parent_page_id}." );
					}
				}//end if-else parent_page_id

				if (
					isset( $_POST['sequential_content'] )
					&& 'yes' === $_POST['sequential_content']
				) {
					$is_sequential = true;
				} else {
					$is_sequential = false;
				}

				/* Create child pages */
				$child_page_ids = PTC_Content_Generator::create_pages_from_titles( $children_page_titles, $parent_page_id, $is_sequential );

				if ( empty( $child_page_ids ) ) {
					throw new \Exception( 'No child pages could be created.' );
				} else {
					$child_page_count = count( $child_page_ids );
					$page_or_pages = ( 1 === $child_page_count ) ? 'page' : 'pages';
					$child_or_not = ( 0 === $parent_page_id ) ? 'top-level ' : 'child ';
					display_notice( 'success', "Created {$child_page_count} {$child_or_not}{$page_or_pages}." );
				}

				/* Finished generating group */
				if ( $parent_page_id > 0 ) {
					$view_group_url = Main::get_groups_list_admin_url( $parent_page_post->ID );
					display_notice( 'success', 'Added pages to group: <a href="' . esc_url( $view_group_url ) . '">View Group</a>' );
				}

				if (
					isset( $_POST['create_menu'] )
					&& 'yes' === $_POST['create_menu']
				) {
					/* Create menu */
					$menu_id = PTC_Content_Generator::create_menu_of_pages( $child_page_ids );

					if (
						is_int( $menu_id )
						&& $menu_id > 0
					) {

						$new_menu = wp_get_nav_menu_object( $menu_id );

						if ( false === $new_menu || 'WP_Term' !== get_class( $new_menu ) ) {
							throw new \Exception( 'Something went wrong when creating the menu.' );
						}

						display_notice( 'success', 'Created menu: <a href="' . esc_url( admin_url( 'nav-menus.php?action=edit&menu=' . $new_menu->term_id ) ) . '">' . esc_html( $new_menu->name ) . '</a>' );

						// @TODO: Assign created menu to the parent page, when applicable.
					} else {
						throw new \Exception( 'Failed to create menu.' );
					}
				}//end if create_menu

				/*
				Draft all pages - Pages must first be published so that permalinks are
				properly generated when creating child pages. Once all pages are
				created with the correct relationships, they can then all be drafted.
				*/

				if ( true === $created_parent_page && $parent_page_id > 0 ) {
					wp_update_post(
						[
							'ID' => $parent_page_id,
							'post_status' => 'draft',
						]
					);
				}

				if ( ! empty( $child_page_ids ) && is_array( $child_page_ids ) ) {
					foreach ( $child_page_ids as $page_id ) {
						wp_update_post(
							[
								'ID' => $page_id,
								'post_status' => 'draft',
							]
						);
					}
				}
			}//end if children_page_titles
		}//end if parent_page_id
	} catch ( \Exception $e ) {
		display_notice( 'error', $e->getMessage() );
	}
}//end if generate_content submitted

/**
 * Displays a notice using WordPress's admin notice css classes.
 *
 * @since 1.1.0
 *
 * @param string $notice_type Supported types are 'error', 'warning', 'success',
 * or 'info'. 'default' is recommended to use for a neutral status.
 * @param string $notice_html Escaped HTML for the notice's content.
 */
function display_notice( string $notice_type, string $notice_html ) {
	printf(
		'<div class="notice notice-%s">%s</div>',
		esc_attr( $notice_type ),
		wp_kses_post( $notice_html )
	);
}
