<?php
add_filter('the_content', 'sam_announcement_filter_content');

function sam_announcement_filter_content($content) {
    // Tampilkan hanya untuk single announcement
    if (is_singular('announcement')) {
        // Pastikan class Display dimuat
        if (!class_exists('Sam_Announcement_Display')) {
            require_once plugin_dir_path(__FILE__) . 'classes/class-sam-announcement-display.php';
        }

        // Gunakan class Display untuk merender HTML
        $display = new Sam_Announcement_Display();
        return $display->render_single_announcement();
    }

    // Jika bukan post type announcement → tampilkan konten normal
    return $content;
}
