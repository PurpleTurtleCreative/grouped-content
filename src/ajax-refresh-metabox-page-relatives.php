<?php
/**
 * Reload the Page Relatives metabox content
 *
 * Refresh the metabox content on Gutenberg editor AJAX request.
 *
 * @since 1.2.0
 */

declare(strict_types=1);

namespace ptc_grouped_content;

defined( 'ABSPATH' ) || die();

global $ptc_grouped_content;

$res['status'] = 'error';
$res['data'] = 'Missing expected data.';

if (
  isset( $_POST['post_id'] )
  && isset( $_POST['edited_parent'] )
  && isset( $_POST['nonce'] )
) {

  $nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ) );
  if ( FALSE === wp_verify_nonce( $nonce, 'ptc_page_relatives' ) ) {
    throw new \Exception( 'Security failure.' );
  }

  try {

    $post_id = (int) filter_var( wp_unslash( $_POST['post_id'] ), FILTER_SANITIZE_NUMBER_INT );
    $the_post = get_post( $post_id );

    if ( NULL === $the_post ) {
      throw new \Exception( "Post with id $post_id does not exist." );
    }

    $edited_parent = (int) filter_var( wp_unslash( $_POST['edited_parent'] ), FILTER_SANITIZE_NUMBER_INT );

    if ( ! isset( $the_post->post_parent ) || $the_post->post_parent !== $edited_parent ) {
      throw new \Exception( "The current post parent [{$the_post->post_parent}] does not match the passed edited parent [{$edited_parent}]." );
    }

    ob_start();
    require $ptc_grouped_content->plugin_path . 'view/html-metabox-page-relatives.php';
    $contents = ob_get_contents();
    ob_end_clean();

    if (
      ! empty( $contents )
      && $res['status'] === 'success'
    ) {
      $res['data'] = $contents;
    } else {
      throw new \Exception( 'There was an issue retrieving the updated content.' );
    }

  } catch ( \Exception $e ) {
    $res['status'] = 'fail';
    $res['data'] = $e->getMessage();
  }

}

echo json_encode( $res );
wp_die();
