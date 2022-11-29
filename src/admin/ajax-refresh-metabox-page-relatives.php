<?php
/**
 * Reload the Page Relatives metabox content
 *
 * Refresh the metabox content on Gutenberg editor AJAX request.
 *
 * @since 1.2.0
 *
 * @package PTC_Grouped_Content
 */

namespace PTC_Grouped_Content;

defined( 'ABSPATH' ) || die();

$res['status'] = 'error';
$res['data'] = 'Missing expected data.';

if (
	isset( $_POST['post_id'] )
	&& isset( $_POST['edited_parent'] )
	&& isset( $_POST['nonce'] )
) {

	$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ) );
	if ( false === wp_verify_nonce( $nonce, 'ptc_page_relatives' ) ) {
		throw new \Exception( 'Security failure.' );
	}

	try {

		$the_post_id = (int) filter_var( wp_unslash( $_POST['post_id'] ), FILTER_SANITIZE_NUMBER_INT );
		$the_post = get_post( $the_post_id );

		if ( null === $the_post ) {
			throw new \Exception( "Post with id $the_post_id does not exist." );
		}

		$edited_parent = (int) filter_var( wp_unslash( $_POST['edited_parent'] ), FILTER_SANITIZE_NUMBER_INT );

		if ( ! isset( $the_post->post_parent ) || $the_post->post_parent !== $edited_parent ) {
			throw new \Exception( "The current post parent [{$the_post->post_parent}] does not match the passed edited parent [{$edited_parent}]." );
		}

		ob_start();
		require PLUGIN_PATH . 'src/admin/templates/html-metabox-page-relatives.php';
		$contents = ob_get_clean();

		if (
			! empty( $contents )
			&& 'success' === $res['status']
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
