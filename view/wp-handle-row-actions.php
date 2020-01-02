<?php
/**
 * Display row action links
 *
 * Copied from WordPress Core class WP_Posts_List_Table for plugin use.
 * Originaly defined in /wp-admin/includes/class-wp-list-table.php.
 *
 * @since 1.0.0
 *
 * @link https://codex.wordpress.org/Class_Reference/WP_List_Table
 *
 * @ignore
 */

/**
 * Generates a list of actions for a post.
 *
 * @since 1.0.0
 *
 * @param \WP_Post $post The post for generating action links.
 *
 * @ignore
 */
function handle_row_actions( $post ) {

    $post_type_object = get_post_type_object( $post->post_type );
    $can_edit_post    = current_user_can( 'edit_post', $post->ID );
    $actions          = array();
    $title            = _draft_or_post_title();

    if ( $can_edit_post && 'trash' != $post->post_status ) {
        $actions['edit'] = sprintf(
            '<a href="%s" aria-label="%s">%s</a>',
            get_edit_post_link( $post->ID ),
            /* translators: %s: post title */
            esc_attr( sprintf( __( 'Edit &#8220;%s&#8221;' ), $title ) ),
            __( 'Edit' )
        );

        if ( 'wp_block' !== $post->post_type ) {
            $actions['inline hide-if-no-js'] = sprintf(
                '<button type="button" class="button-link editinline" aria-label="%s" aria-expanded="false">%s</button>',
                /* translators: %s: post title */
                esc_attr( sprintf( __( 'Quick edit &#8220;%s&#8221; inline' ), $title ) ),
                __( 'Quick&nbsp;Edit' )
            );
        }
    }

    if ( current_user_can( 'delete_post', $post->ID ) ) {
        if ( 'trash' === $post->post_status ) {
            $actions['untrash'] = sprintf(
                '<a href="%s" aria-label="%s">%s</a>',
                wp_nonce_url( admin_url( sprintf( $post_type_object->_edit_link . '&amp;action=untrash', $post->ID ) ), 'untrash-post_' . $post->ID ),
                /* translators: %s: post title */
                esc_attr( sprintf( __( 'Restore &#8220;%s&#8221; from the Trash' ), $title ) ),
                __( 'Restore' )
            );
        } elseif ( EMPTY_TRASH_DAYS ) {
            $actions['trash'] = sprintf(
                '<a href="%s" class="submitdelete" aria-label="%s">%s</a>',
                get_delete_post_link( $post->ID ),
                /* translators: %s: post title */
                esc_attr( sprintf( __( 'Move &#8220;%s&#8221; to the Trash' ), $title ) ),
                _x( 'Trash', 'verb' )
            );
        }
        if ( 'trash' === $post->post_status || ! EMPTY_TRASH_DAYS ) {
            $actions['delete'] = sprintf(
                '<a href="%s" class="submitdelete" aria-label="%s">%s</a>',
                get_delete_post_link( $post->ID, '', true ),
                /* translators: %s: post title */
                esc_attr( sprintf( __( 'Delete &#8220;%s&#8221; permanently' ), $title ) ),
                __( 'Delete Permanently' )
            );
        }
    }

    if ( is_post_type_viewable( $post_type_object ) ) {
        if ( in_array( $post->post_status, array( 'pending', 'draft', 'future' ) ) ) {
            if ( $can_edit_post ) {
                $preview_link    = get_preview_post_link( $post );
                $actions['view'] = sprintf(
                    '<a href="%s" rel="bookmark" aria-label="%s">%s</a>',
                    esc_url( $preview_link ),
                    /* translators: %s: post title */
                    esc_attr( sprintf( __( 'Preview &#8220;%s&#8221;' ), $title ) ),
                    __( 'Preview' )
                );
            }
        } elseif ( 'trash' != $post->post_status ) {
            $actions['view'] = sprintf(
                '<a href="%s" rel="bookmark" aria-label="%s">%s</a>',
                get_permalink( $post->ID ),
                /* translators: %s: post title */
                esc_attr( sprintf( __( 'View &#8220;%s&#8221;' ), $title ) ),
                __( 'View' )
            );
        }
    }

    if ( 'wp_block' === $post->post_type ) {
        $actions['export'] = sprintf(
            '<button type="button" class="wp-list-reusable-blocks__export button-link" data-id="%s" aria-label="%s">%s</button>',
            $post->ID,
            /* translators: %s: post title */
            esc_attr( sprintf( __( 'Export &#8220;%s&#8221; as JSON' ), $title ) ),
            __( 'Export as JSON' )
        );
    }

    if ( is_post_type_hierarchical( $post->post_type ) ) {

        /**
         * Filters the array of row action links on the Pages list table.
         *
         * The filter is evaluated only for hierarchical post types.
         *
         * @since 2.8.0
         *
         * @param string[] $actions An array of row action links. Defaults are
         *                          'Edit', 'Quick Edit', 'Restore', 'Trash',
         *                          'Delete Permanently', 'Preview', and 'View'.
         * @param WP_Post  $post    The post object.
         */
        $actions = apply_filters( 'page_row_actions', $actions, $post );
    } else {

        /**
         * Filters the array of row action links on the Posts list table.
         *
         * The filter is evaluated only for non-hierarchical post types.
         *
         * @since 2.8.0
         *
         * @param string[] $actions An array of row action links. Defaults are
         *                          'Edit', 'Quick Edit', 'Restore', 'Trash',
         *                          'Delete Permanently', 'Preview', and 'View'.
         * @param WP_Post  $post    The post object.
         */
        $actions = apply_filters( 'post_row_actions', $actions, $post );
    }

    return $actions;
}

/**
 * Displays a <div> of action links for a post.
 *
 * @since 1.0.0
 *
 * @param \WP_Post $post The post for displaying action links.
 *
 * @ignore
 */
function display_row_action_links( $post ) {

  $action_links = handle_row_actions( $post );
  unset( $action_links['inline hide-if-no-js'] ); // Quick Edit link

  $html = '<div class="row-actions">';

  $current = 1;
  $last = count( $action_links );
  foreach ( $action_links as $name => $anchor_tag ) {

    $html .= "<span class='$name'>$anchor_tag";

    if ( $current !== $last ) {
      $html .= ' | </span>';
    } else {
      $html .= '</span>';
    }

    ++$current;
  }

  $html .= '</div>';

  echo $html;

}
