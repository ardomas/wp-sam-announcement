<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Sam_Announcement_CPT {

    public $post_type = 'announcement';

    public function __construct() {
        // $this->init();
    }

    public function init() {
        add_filter('single_template', [$this, 'load_single_template']);
    }

    public function load_single_template($single) {
        // If a block theme is active, let the block template system handle it (header/footer etc.)
        if (function_exists('wp_is_block_theme') && wp_is_block_theme()) {
            return $single;
        }
        global $post;
        if ($post && $post->post_type === 'announcement') {
            $theme_template = locate_template('single-announcement.php');
            if ($theme_template) {
                return $theme_template;
            }
            $plugin_template = plugin_dir_path(__FILE__) . '../templates/single-announcement.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        return $single;
    }

    public function register() {
        add_action( 'init', [ $this, 'register_post_type' ] );
    }

    public function register_post_type() {
        $labels = [
            'name'               => __( 'Announcements' ),
            'singular_name'      => __( 'Announcement' ),
            'menu_name'          => __( 'Announcements' ),
            'name_admin_bar'     => __( 'Announcement' ),
            'add_new'            => __( 'Add New' ),
            'add_new_item'       => __( 'Add New Announcement' ),
            'new_item'           => __( 'New Announcement' ),
            'edit_item'          => __( 'Edit Announcement' ),
            'view_item'          => __( 'View Announcement' ),
            'all_items'          => __( 'All Announcements' ),
            'search_items'       => __( 'Search Announcements' ),
            'not_found'          => __( 'No announcements found.' ),
        ];

        $args = [
            'labels'             => $labels,
            'public'             => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'menu_position'      => 5,
            'menu_icon'          => 'dashicons-megaphone',
            'supports'           => ['title', 'editor', 'thumbnail', 'excerpt'],
            'has_archive'        => true,
            'rewrite'            => ['slug' => 'announcement'],
            'show_in_rest'       => true,
        ];

        register_post_type( $this->post_type, $args );
    }
}
