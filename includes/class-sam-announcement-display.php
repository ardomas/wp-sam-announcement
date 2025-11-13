<?php
if (!defined('ABSPATH')) exit;

class Sam_Announcement_Display {

    public function __construct() {
        add_filter('single_template', [$this, 'load_this_template']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
    }

    public function enqueue_styles() {
        // if (is_singular('announcement')) {
            $css_path = plugin_dir_path(__FILE__) . '../templates/css/sam-announcement.css';
            $css_url  = plugin_dir_url(__FILE__) . '../templates/css/sam-announcement.css';

            wp_enqueue_style(
                'sam-single-announcement',
                $css_url,
                [],
                filemtime($css_path)
            );
        // }
    }

    public function load_template(){
        $template = '';
        if (is_singular('announcement')) {
            $template = plugin_dir_path(__FILE__) . '../templates/single-announcement.html';
        }
        return $template;
    }
    public function load_this_template($template) {
        if (is_singular('announcement')) {
            $template = plugin_dir_path(__FILE__) . '../templates/single-announcement.php';
        }
        return $template;
    }

    public function load_template_single_announcement() {
        $template_html = plugin_dir_path(__FILE__) . '../templates/single-announcement.html';
        if (!file_exists($template_html)) return '<p>Template not found.</p>';
        return file_get_contents($template_html);
    }

    public function render_single_announcement() {
        $template_html = plugin_dir_path(__FILE__) . '../templates/single-announcement.html';
        if (!file_exists($template_html)) return '<p>Template not found.</p>';

        // include $template_html;
        $template = file_get_contents($template_html);
        $data     = $this->get_announcement_data();

        // Handle {{#if var}} ... {{else}} ... {{/if}}
        $template = preg_replace_callback('/{{#if (\w+)}}(.*?)({{else}}(.*?))?{{\/if}}/s', function ($m) use ($data) {
            $var = $m[1];
            $true_block  = $m[2];
            $false_block = isset($m[4]) ? $m[4] : '';
            return !empty($data[$var]) ? $true_block : $false_block;
        }, $template);

        // Replace {{var}}
        foreach ($data as $key => $value) {
            if (is_array($value)) $value = implode(', ', $value);
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }

        return $template;
    }

    public function get_announcement_data() {
        $post_id = get_the_ID();
        $data = [];

        $data['title']       = get_the_title($post_id);
        $data['sub_title']   = esc_html(get_post_meta($post_id, '_sa_sub_title', true));
        $data['content']     = apply_filters('the_content', get_the_content(null, false, $post_id));
        $data['location']    = esc_html(get_post_meta($post_id, '_sa_location', true));
        $data['price_label'] = esc_html(get_post_meta($post_id, '_sa_price_label', true));

        $start_date = esc_html(get_post_meta($post_id, '_sa_start_date', true));
        $end_date   = esc_html(get_post_meta($post_id, '_sa_end_date', true));
        $data['start_date']  = $start_date;
        $data['end_date']    = ($start_date == $end_date) ? '' : ' - ' . $end_date;

        $data['start_time'] = esc_html(get_post_meta($post_id, '_sa_start_time', true));
        $data['end_time']   = esc_html(get_post_meta($post_id, '_sa_end_time', true));

        $img_id = get_post_meta($post_id, '_sa_image', true);
        $data['image'] = wp_get_attachment_url($img_id);

        $speakers   = maybe_unserialize(get_post_meta($post_id, '_sa_speakers', true));
        $organizers = maybe_unserialize(get_post_meta($post_id, '_sa_organizers', true));
        $prices     = maybe_unserialize(get_post_meta($post_id, '_sa_prices', true));

        $data['speakers']   = $this->_build_list($speakers, ['name', 'profession', 'organization']);
        $data['organizers'] = $this->_build_list($organizers, ['name', 'description']);
        $data['prices']     = $this->_build_price_table($prices);

        return $data;
    }

    private function _build_list($items, $fields) {
        if (empty($items) || !is_array($items)) return '';
        $html = '<ul>';
        foreach ($items as $item) {
            $parts = [];
            foreach ($fields as $f) {
                if (!empty($item[$f])) {
                    $parts[] = ($f === 'name') ? '<strong>' . esc_html($item[$f]) . '</strong>' : esc_html($item[$f]);
                }
            }
            $html .= '<li>' . implode('<br>', $parts) . '</li>';
        }
        return $html . '</ul>';
    }

    private function _build_price_table($prices) {
        if (empty($prices) || !is_array($prices)) return '';
        $html = '<table class="price-table" style="width:100%;">';
        foreach ($prices as $p) {
            $name = esc_html($p['name'] ?? '');
            $val  = esc_html($p['value'] ?? '');
            $html .= "<tr><td style='width:50%;'>{$name}</td><td style='width:50%; text-align:right;'>{$val}</td></tr>";
        }
        return $html . '</table>';
    }
}
