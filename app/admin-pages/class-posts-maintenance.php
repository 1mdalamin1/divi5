<?php
/**
 * Posts Maintenance test block.
 *
 * @link          https://wpmudev.com/
 * @since         1.0.0
 *
 * @author        WPMUDEV (https://wpmudev.com)
 * @package       WPMUDEV\PluginTest
 *
 * @copyright (c) 2025, Incsub (http://incsub.com)
 */

namespace WPMUDEV\PluginTest\App\Admin_Pages;

// Abort if called directly.
defined( 'WPINC' ) || die;

use WPMUDEV\PluginTest\Base;

class Posts_Maintenance extends Base {
	
	private $page_title;
	private $page_slug = 'wpmudev_plugintest_posts_maintenance';
	private $creds = array();
	private $option_name = 'wpmudev_plugin_tests_auth';
	private $page_scripts = array();
	private $assets_version = '';
	private $unique_id = '';
	private $scan_transient_key = 'wpmudev_posts_scan_progress';


	public function init() {
		$this->page_title     = __( 'Posts Maintenance', 'wpmudev-plugin-test' );
		$this->creds          = get_option( $this->option_name, array() );
		// $this->assets_version = ! empty( $this->script_data( 'version' ) ) ? $this->script_data( 'version' ) : WPMUDEV_PLUGINTEST_VERSION;
		$this->unique_id      = "wpmudev_plugintest_posts_maintenance_main_wrap-{$this->assets_version}";

		add_action( 'admin_menu', array( $this, 'register_admin_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		// Add body class to admin pages.
		add_filter( 'admin_body_class', array( $this, 'admin_body_classes' ) );
		add_action( 'wp_ajax_wpmudev_scan_posts', array( $this, 'ajax_scan_posts' ) );
		add_action( 'wp_ajax_wpmudev_scan_progress', array( $this, 'ajax_scan_progress' ) );
		add_action( 'wpmudev_daily_posts_scan', array( $this, 'background_scan' ) );
		if ( ! wp_next_scheduled( 'wpmudev_daily_posts_scan' ) ) {
			wp_schedule_event( time(), 'daily', 'wpmudev_daily_posts_scan' );
		}
	}

	public function register_admin_page() {
		
		// $page = add_menu_page(
		// 	'Posts Maintenance',
		// 	$this->page_title,
		// 	'manage_options',
		// 	$this->page_slug,
		// 	array( $this, 'callback' ),
		// 	'dashicons-ellipsis',
		// 	7
		// );
		
		// add_action( 'load-' . $page, array( $this, 'prepare_assets' ) );
		
		//add submenu 2
		add_submenu_page(
			'wpmudev_plugintest_drive',// $this->page_slug, // wpmudev_plugintest_drive
			__('Posts Maintenance ','wpmudev-plugin-test'),
			__('Posts Maintenance ','wpmudev-plugin-test'),
			'manage_options',
			'wpmudev_plugintest_posts_maintenance',
			array( $this, 'callback' ) // array( $this, 'wpmudev_plugintest_posts_maintenance_setting_fun' )
		);
	}

	public function callback() {
		$this->view();
	}

	protected function view() {
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		?>
		<div id="<?php echo esc_attr( $this->unique_id ); ?>" class="sui-wrap">
			<h2><?php esc_html_e( 'Posts Maintenance', 'wpmudev-plugin-test' ); ?></h2>
			<form id="wpmudev-scan-posts-form">
				<p><?php esc_html_e( 'Select post types to scan:', 'wpmudev-plugin-test' ); ?></p>
				<label>
					<input type="checkbox" name="post_types[]" value="post" checked>
					<?php esc_html_e( 'Posts', 'wpmudev-plugin-test' ); ?>
				</label><br>
				<label>
					<input type="checkbox" name="post_types[]" value="page">
					<?php esc_html_e( 'Pages', 'wpmudev-plugin-test' ); ?>
				</label><br>

				<?php // foreach ( $post_types as $pt ) : ?>
					<!-- <label>
						<input type="checkbox" name="post_types[]" value="<?php // echo esc_attr( $pt->name ); ?>" checked>
						<?php // echo esc_html( $pt->labels->singular_name ); ?>
					</label><br> -->
				<?php // endforeach; ?>
				<br>
				<button type="button" class="button button-primary" id="wpmudev-scan-posts-btn"><?php esc_html_e( 'Scan Posts', 'wpmudev-plugin-test' ); ?></button>
			</form>
			<div id="wpmudev-scan-progress" style="margin-top:20px;"></div>
		</div>
		<script>
		(function($){
		console.log('test ajax url --> ', ajaxurl);

			var interval;
			$('#wpmudev-scan-posts-btn').on('click', function(e){
				e.preventDefault();
				var post_types = [];
				$('input[name="post_types[]"]:checked').each(function(){ post_types.push($(this).val()); });
				$('#wpmudev-scan-progress').html('<?php esc_html_e( 'Starting scan...', 'wpmudev-plugin-test' ); ?>');
				$.post(ajaxurl, {
					action: 'wpmudev_scan_posts',
					post_types: post_types,
					_wpnonce: '<?php echo wp_create_nonce( "wpmudev_scan_posts" ); ?>'
				}, function(resp){
					if(resp.success){
						interval = setInterval(function(){
							$.post(ajaxurl, { action: 'wpmudev_scan_progress' }, function(data){
								if(data && data.progress){
									$('#wpmudev-scan-progress').html(data.progress);
									if(data.done){
										clearInterval(interval);
										$('#wpmudev-scan-progress').append('<br><?php esc_html_e( 'Scan complete!', 'wpmudev-plugin-test' ); ?>');
									}
								}
							});
						}, 2000);
					}else{
						$('#wpmudev-scan-progress').html(resp.data || 'Error');
					}
				});
			});
		})(jQuery);
		</script>
		<?php
	}

	public function ajax_scan_posts() {
		check_ajax_referer( 'wpmudev_scan_posts' );
		if ( empty( $_POST['post_types'] ) || ! is_array( $_POST['post_types'] ) ) {
			wp_send_json_error( __( 'No post types selected.', 'wpmudev-plugin-test' ) );
		}
		$post_types = array_map( 'sanitize_text_field', $_POST['post_types'] );
		$args = array(
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'fields'         => 'ids',
			'posts_per_page' => -1,
			'nopaging'       => true,
		);
		$posts = get_posts( $args );
		set_transient( $this->scan_transient_key, array(
			'total' => count( $posts ),
			'processed' => 0,
			'post_ids' => $posts,
			'done' => false,
		), HOUR_IN_SECONDS );
		wp_schedule_single_event( time(), 'wpmudev_daily_posts_scan' );
		wp_send_json_success();
	}

	public function ajax_scan_progress() {
		$progress = get_transient( $this->scan_transient_key );
		if ( ! $progress ) {
			wp_send_json( array( 'progress' => __( 'No scan running.', 'wpmudev-plugin-test' ), 'done' => true ) );
		}
		$msg = sprintf(
			__( 'Processed %1$d of %2$d posts.', 'wpmudev-plugin-test' ),
			(int) $progress['processed'],
			(int) $progress['total']
		);
		wp_send_json( array(
			'progress' => $msg,
			'done' => ! empty( $progress['done'] ),
		) );
	}

	public function background_scan() {
		$progress = get_transient( $this->scan_transient_key );
		if ( ! $progress || empty( $progress['post_ids'] ) ) {
			return;
		}
		$batch_size = 20;
		$post_ids = $progress['post_ids'];
		$to_process = array_splice( $post_ids, 0, $batch_size );
		foreach ( $to_process as $pid ) {
			update_post_meta( $pid, 'wpmudev_test_last_scan', time() );
		}
		$progress['processed'] += count( $to_process );
		$progress['post_ids'] = $post_ids;
		if ( empty( $post_ids ) ) {
			$progress['done'] = true;
		}
		set_transient( $this->scan_transient_key, $progress, HOUR_IN_SECONDS );
		if ( ! empty( $post_ids ) ) {
			wp_schedule_single_event( time() + 5, 'wpmudev_daily_posts_scan' );
		}
	}

	public function enqueue_assets() {
		if ( ! empty( $this->page_scripts ) ) {
			foreach ( $this->page_scripts as $handle => $page_script ) {
				wp_register_script(
					$handle,
					$page_script['src'],
					$page_script['deps'],
					$page_script['ver'],
					$page_script['strategy']
				);

				if ( ! empty( $page_script['localize'] ) ) {
					wp_localize_script( $handle, 'wpmudevDriveTest', $page_script['localize'] );
				}

				wp_enqueue_script( $handle );

				if ( ! empty( $page_script['style_src'] ) ) {
					wp_enqueue_style( $handle, $page_script['style_src'], array(), $this->assets_version );
				}
			}
		}
	}
	/**
	 * Adds the SUI class on markup body.
	 *
	 * @param string $classes
	 *
	 * @return string
	 */
	public function admin_body_classes( $classes = '' ) {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return $classes;
		}

		$current_screen = get_current_screen();

		if ( empty( $current_screen->id ) || ! strpos( $current_screen->id, $this->page_slug ) ) {
			return $classes;
		}

		$classes .= ' sui-' . str_replace( '.', '-', WPMUDEV_PLUGINTEST_SUI_VERSION ) . ' ';

		return $classes;
	}
}