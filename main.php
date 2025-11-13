<?php
/**
 * Plugin Name: Sam Announcement
 * Description: A lightweight WordPress plugin for managing time-based announcements (date+time), speakers, organizers, and locations.
 * Version: 1.0.3
 * Author: Sam
 * Text Domain: wp-sam-announcement
 * Author URI: https://ardomas.com/
 */

if (!defined('ABSPATH')) exit;

/**
 * Autoload plugin classes
 */
require_once plugin_dir_path(__FILE__) . 'includes/class-sam-announcement-cpt.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-sam-announcement-meta.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-sam-announcement-display.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-sam-announcement-logger.php';

/**
 * Initialize logger early
 */
Sam_Announcement_Logger::init();

/**
 * Initialize the plugin
 */
function sam_announcement_init() {
    (new Sam_Announcement_CPT())->register();
    (new Sam_Announcement_Meta())->init();
    (new Sam_Announcement_Display()); // langsung hook display logic
}
add_action('plugins_loaded', 'sam_announcement_init');

/**
 * Plugin activation
 */
function sam_announcement_activate() {
    (new Sam_Announcement_CPT())->register();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'sam_announcement_activate');

/**
 * Plugin deactivation
 */
function sam_announcement_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'sam_announcement_deactivate');

/**
 * Shortcode: [active_announcements]
 */
function active_announcements_widget() {
    $today = date('Y-m-d');

    /*
    $args = array(
        'post_type'      => 'announcement',
        'meta_query'     => array(
            array(
                'key'     => '_sa_end_date',
                'value'   => $today,
                'compare' => '>=',
                'type'    => 'DATE'
            )
        ),
        'posts_per_page' => 5,
        'orderby'        => 'start_date',
        'order'          => 'ASC',
    );
    */

    $args = array(
        'post_type'      => 'announcement',
        'posts_per_page' => 5,
        'meta_key'       => '_sa_start_date', // untuk urutan berdasarkan tanggal mulai
        'orderby'        => 'meta_value',
        'meta_type'      => 'DATE',
        'order'          => 'ASC',
        'meta_query'     => array(
            array(
                'key'     => '_sa_end_date',
                'value'   => $today,
                'compare' => '>=',
                'type'    => 'DATE',
            ),
        ),
    );

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        ob_start();

        while ($query->have_posts()) {
            $query->the_post();

            $image_id   = get_post_meta(get_the_ID(), '_sa_image', true);
            $image      = wp_get_attachment_url($image_id);
            $date1      = get_post_meta(get_the_ID(), '_sa_start_date', true);
            $date2      = get_post_meta(get_the_ID(), '_sa_end_date', true);

            $date_label = 'Hari: ' . get_localized_day_name($date1) . ', ' . get_localized_date($date1, 'id');
            if ($date1 !== $date2) {
                $date_label .= ' - ' . get_localized_day_name($date2) . ', ' . get_localized_date($date2, 'id');
            }

            $time_label = 'Jam: ' . get_post_meta(get_the_ID(), '_sa_start_time', true)
                        . ' - ' . get_post_meta(get_the_ID(), '_sa_end_time', true);

            $speakers   = get_post_meta(get_the_ID(), '_sa_speakers', true);
            $speaker_names = [];
            if (is_array($speakers)) {
                foreach ($speakers as $speaker) {
                    $speaker_names[] = '<li>' . esc_html($speaker['name']) . '</li>';
                }
            }
            $speakers_str = '<ul style="text-align: left;">' . implode('', $speaker_names) . '</ul>';

            $location = get_post_meta(get_the_ID(), '_sa_location', true);

            include plugin_dir_path(__FILE__) . 'templates/announcement-widget.html';
        }

        wp_reset_postdata();
        return ob_get_clean();
    }

    return '<p>No active announcements. Date: ' . esc_html($today) . '</p>';
}
add_shortcode('active_announcements', 'active_announcements_widget');

/**
 * Helper functions (locale-aware)
 */
function get_localized_date($date, $locale = 'id') {
    return function_exists('sam_format_date')
        ? sam_format_date($date, $locale)
        : $date;
}

function get_localized_currency($value, $locale = 'id', $decimals = 0) {
    return function_exists('sam_format_currency')
        ? sam_format_currency($value, $locale, $decimals)
        : $value;
}

function get_localized_day_name($date, $locale = 'id') {
    return function_exists('sam_get_day_name')
        ? sam_get_day_name($date, $locale)
        : '';
}
