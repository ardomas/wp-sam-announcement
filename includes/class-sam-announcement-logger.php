<?php

if (!defined('ABSPATH')) {
    exit;
}

class Sam_Announcement_Logger {
    private static $log_dir;
    private static $log_file;
    private static $max_size = 1048576; // 1MB

    public static function init() {
        self::$log_dir = plugin_dir_path(__FILE__) . '../log/';
        self::$log_file = self::$log_dir . 'debug.log';

        // Pastikan folder log ada
        if (!file_exists(self::$log_dir)) {
            wp_mkdir_p(self::$log_dir);
        }

        // Buat file kosong jika belum ada
        if (!file_exists(self::$log_file)) {
            file_put_contents(self::$log_file, '');
        }
    }

    public static function write($message) {
        // 1️⃣ Cek apakah WordPress debug aktif
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        if (!defined('WP_DEBUG_LOG') || !WP_DEBUG_LOG) {
            return;
        }

        // 2️⃣ Pastikan inisialisasi
        if (!self::$log_file) {
            self::init();
        }

        // 3️⃣ Rotasi jika file sudah besar
        self::rotate_if_needed();

        // 4️⃣ Tulis log
        $timestamp = date('Y-m-d H:i:s');
        $formatted = "[$timestamp] $message" . PHP_EOL;
        file_put_contents(self::$log_file, $formatted, FILE_APPEND);
    }

    private static function rotate_if_needed() {
        if (file_exists(self::$log_file) && filesize(self::$log_file) > self::$max_size) {
            $time = date('Ymd-His');
            $rotated = self::$log_dir . "debug-$time.log";
            rename(self::$log_file, $rotated);
            file_put_contents(self::$log_file, ""); // buat file baru kosong
        }
    }
}
