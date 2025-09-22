<?php
/**
 * WP-CLI command for Posts Maintenance scan.
 *
 * Usage:
 *   wp posts-maintenance scan
 *   wp posts-maintenance scan --post_types=post,page
 *
 * @when after_wp_load
 */

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Maintains posts by updating scan meta.
	 */
	class Posts_Maintenance_CLI_Command extends WP_CLI_Command {

		/**
		 * Scan posts and update 'wpmudev_test_last_scan' meta.
		 *
		 * ## OPTIONS
		 *
		 * [--post_types=<types>]
		 * : Comma-separated list of post types to scan. Default: post,page
		 *
		 * ## EXAMPLES
		 *
		 *     wp posts-maintenance scan
		 *     wp posts-maintenance scan --post_types=post,page,custom_type
		 *
		 * @when after_wp_load
		 */
		public function scan( $args, $assoc_args ) {
			$post_types = array( 'post', 'page' );
			if ( ! empty( $assoc_args['post_types'] ) ) {
				$post_types = array_map( 'trim', explode( ',', $assoc_args['post_types'] ) );
			}
			$args = array(
				'post_type'      => $post_types,
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'nopaging'       => true,
			);
			$posts = get_posts( $args );
			$total = count( $posts );
			if ( ! $total ) {
				WP_CLI::success( 'No posts found for selected post types.' );
				return;
			}
			WP_CLI::log( sprintf( 'Scanning %d posts (%s)...', $total, implode( ', ', $post_types ) ) );
			$progress = \WP_CLI\Utils\make_progress_bar( 'Scanning', $total );
			foreach ( $posts as $pid ) {
				update_post_meta( $pid, 'wpmudev_test_last_scan', time() );
				$progress->tick();
			}
			$progress->finish();
			WP_CLI::success( sprintf( 'Scan complete. %d posts processed.', $total ) );
		}
	}
	WP_CLI::add_command( 'posts-maintenance', 'Posts_Maintenance_CLI_Command' );
}
