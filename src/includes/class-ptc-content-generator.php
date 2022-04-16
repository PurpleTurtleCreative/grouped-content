<?php
/**
 * Content Generator class
 *
 * Used for rapidly creating content.
 *
 * @since 1.1.0
 *
 * @package PTC_Grouped_Content
 */

namespace PTC_Grouped_Content;

defined( 'ABSPATH' ) || die();

/**
 * Creates new content.
 */
class PTC_Content_Generator {

	/**
	 * Creates empty page posts from the list of provided titles.
	 *
	 * @since 1.1.0
	 * @since 1.2.0 Added $is_sequential argument.
	 *
	 * @param string[] $page_titles The title strings to create each page.
	 * @param int      $parent_page_id Optional. The id of the page post to assign
	 * as the parent post of each created page. Default 0 for no parent.
	 * @param bool     $is_sequential Optional. If sequential menu_order values
	 * should be assigned to created pages. Default false for all pages to have
	 * default menu_order of 0.
	 *
	 * @return int[] The ids of the created page posts.
	 */
	public static function create_pages_from_titles( array $page_titles, int $parent_page_id = 0, bool $is_sequential = false ) : array {

		if ( 0 !== $parent_page_id ) {

			$parent_post = get_post( $parent_page_id );

			if ( null === $parent_post ) {
				error_log( "Failed to create pages for invalid parent_page_id = $parent_page_id" );
				return [];
			} elseif ( 'page' !== $parent_post->post_type ) {
				error_log( "Failed to create pages for {$parent_post->post_type} parent post $parent_page_id. It should be of type 'page'." );
				return [];
			}
		}

		$generated_page_ids = [];

		foreach ( $page_titles as $i => $title ) {

			$sanitized_title = filter_var(
				$title,
				FILTER_SANITIZE_STRING,
				FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH
			);

			$sanitized_title = trim( $sanitized_title );

			if ( empty( $sanitized_title ) ) {
				error_log( 'Skipping page creation for empty title: $title' );
				continue;
			}

			$menu_order = 0;
			if ( $is_sequential ) {
				$menu_order = $i;
			}

			$new_post_id = wp_insert_post(
				[
					'post_title'   => $sanitized_title,
					'post_content' => '',
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_parent'  => $parent_page_id,
					'menu_order'   => $menu_order,
				]
			);

			if ( is_int( $new_post_id ) && $new_post_id > 0 ) {
				$generated_page_ids[] = (int) $new_post_id;
			} else {
				error_log( "Could not create page titled: $sanitized_title" );
			}
		}

		return $generated_page_ids;
	}//end create_pages_from_titles()

	/**
	 * Create a new WordPress menu with the provided pages.
	 *
	 * @since 1.1.0
	 *
	 * @param int[]  $page_ids The ids of the page's to link in the menu.
	 * @param string $menu_title Optional. The desired name for the new menu.
	 * Default '' to attempt using the first page's parent page title.
	 *
	 * @return int The id of the created menu. Returns 0 if no menu was created.
	 */
	public static function create_menu_of_pages( array $page_ids, string $menu_title = '' ) : int {

		/* Sanitize menu_title */
		if ( '' === $menu_title && is_numeric( $page_ids[0] ) ) {

			$first_post = get_post( $page_ids[0] );

			if ( null === $first_post || 'page' !== $first_post->post_type ) {
				error_log( "The first post is not a valid page post, id = {$page_ids[0]}" );
				return 0;
			}

			$parent_post = get_post( $first_post->post_parent );

			if ( null === $parent_post || 'page' !== $parent_post->post_type ) {
				$menu_title = 'Generated Menu ' . date( 'Y-m-d' );
				/**
				 * Filters the fallback menu title when creating a menu.
				 *
				 * @since 1.1.0
				 *
				 * @param string $menu_title The fallback menu title.
				 * @param int[] $page_ids The provided page ids for the menu.
				 */
				$menu_title = apply_filters( 'ptc_generated_menu_fallback_title', $menu_title, $page_ids );
			} else {
				$menu_title = $parent_post->post_title;
			}
		} else {

			$menu_title = filter_var(
				$menu_title,
				FILTER_SANITIZE_STRING,
				FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH
			);

			/**
			 * Filters the menu title when creating a menu.
			 *
			 * @since 1.1.0
			 *
			 * @param string $menu_title The desired menu title.
			 * @param int[] $page_ids The provided page ids for the menu.
			 */
			$menu_title = apply_filters( 'ptc_generated_menu_title', $menu_title, $page_ids );
		}

		/* Ensure menu title is unique */
		$menu_term_object = wp_get_nav_menu_object( $menu_title );
		$count_suffix = 1;
		while ( false !== $menu_term_object ) {
			++$count_suffix;
			$mod_menu_title = "$menu_title - $count_suffix";
			$menu_term_object = wp_get_nav_menu_object( $mod_menu_title );
		}

		if ( isset( $mod_menu_title ) ) {
			$menu_title = $mod_menu_title;
		}

		/* Create the menu */
		$new_menu_id = wp_create_nav_menu( $menu_title );
		if (
			is_wp_error( $new_menu_id )
			|| ! is_numeric( $new_menu_id )
			|| 0 === (int) $new_menu_id
		) {
			error_log( "Failed to create new menu titled: {$menu_title}" );
			return 0;
		} else {
			$new_menu_id = (int) $new_menu_id;
		}

		$new_menu = wp_get_nav_menu_object( $new_menu_id );
		if ( false === $new_menu || 'WP_Term' !== get_class( $new_menu ) ) {
			error_log( "Failed to get new menu with id: {$new_menu_id}" );
			return $new_menu_id;
		}

		/* Add pages to the new menu */
		foreach ( $page_ids as $i => $page_id ) {

			$the_post = get_post( $page_id );

			if ( null === $the_post || 'page' !== $the_post->post_type ) {
				error_log( "Not adding invalid page post {$page_id} to created menu {$new_menu_id}" );
				continue;
			}

			wp_update_nav_menu_item(
				$new_menu_id,
				0, /* The ID of the menu item. If "0", creates a new menu item. */
				[
					'menu-item-object' => 'page',
					'menu-item-object-id' => $the_post->ID,
					'menu-item-type' => 'post_type',
					'menu-item-status' => 'publish',
					'menu-item-position' => $i,
				]
			);
		}//end foreach $page_ids

		/**
		 * Fires after generating a new menu of pages.
		 *
		 * @since 1.1.0
		 *
		 * @param int $new_menu_id The generated menu's id.
		 * @param int[] $page_ids The passed page ids that were to be added to the
		 * menu. Use wp_get_nav_menu_items( $new_menu_id ) to get the actual menu
		 * items.
		 */
		do_action( 'ptc_after_generating_menu_of_pages', $new_menu_id, $page_ids );

		/* To add meta to the menu, use: update_term_meta( $new_menu_id, 'key', 'value' ); */

		return (int) $new_menu_id;
	}//end create_menu_of_pages()
}//end class
