<?php
/**
 * Unit tests for Posts Maintenance functionality
 *
 * @package WPMUDEV_PluginTest
 */

use WPMUDEV\PluginTest\App\Admin_Pages\Posts_Maintenance;

/**
 * Class Test_Posts_Maintenance
 */
class Test_Posts_Maintenance extends WP_UnitTestCase {

    private $posts_maintenance;
    private $scan_transient_key = 'wpmudev_posts_scan_progress';

    public function setUp(): void {
        parent::setUp();
        
        if (!class_exists('WPMUDEV\PluginTest\App\Admin_Pages\Posts_Maintenance')) {
            require_once plugin_dir_path(dirname(__FILE__)) . '../app/admin-pages/class-posts-maintenance.php';
        }
        
        $this->posts_maintenance = new Posts_Maintenance();
        
        delete_transient($this->scan_transient_key);
        
        wp_clear_scheduled_hook('wpmudev_daily_posts_scan');
    }

    public function tearDown(): void {
        delete_transient($this->scan_transient_key);
        wp_clear_scheduled_hook('wpmudev_daily_posts_scan');
        
        $posts = get_posts(array(
            'post_type' => array('post', 'page', 'attachment'),
            'numberposts' => -1,
            'post_status' => 'any'
        ));
        
        foreach ($posts as $post) {
            wp_delete_post($post->ID, true);
            delete_post_meta($post->ID, 'wpmudev_test_last_scan');
        }
        
        parent::tearDown();
    }

    public function test_class_initialization() {
        $this->assertInstanceOf(
            'WPMUDEV\PluginTest\App\Admin_Pages\Posts_Maintenance',
            $this->posts_maintenance,
            'Class should be properly instantiated'
        );
    }

    /**
     * Test admin page registration
     */
    public function test_admin_page_registration() {
        global $submenu;
        
        $submenu = array();
        
        $this->posts_maintenance->register_admin_page();
        
        $this->assertArrayHasKey('wpmudev_plugintest_drive', $submenu);
        
        $found = false;
        foreach ($submenu['wpmudev_plugintest_drive'] as $item) {
            if (in_array('wpmudev_plugintest_posts_maintenance', $item)) {
                $found = true;
                break;
            }
        }
        
        $this->assertTrue($found, 'Posts Maintenance submenu should be registered');
    }

}


