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
    $cpt = new Sam_Announcement_CPT();
    $cpt->register();
    $cpt->init(); // enable template loading via CPT class
    (new Sam_Announcement_Meta())->init();
    // Display class no longer manages single templates; keep for future display utilities if needed
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
 * Front-end assets for single announcement
 */
function sam_announcement_enqueue_assets() {
    if (is_singular('announcement')) {
        $css_path = plugin_dir_path(__FILE__) . 'templates/css/sam-announcement.css';
        $css_url  = plugins_url('templates/css/sam-announcement.css', __FILE__);
        $version  = file_exists($css_path) ? filemtime($css_path) : null;
        wp_enqueue_style('sam-announcement', $css_url, [], $version);
    }
}
add_action('wp_enqueue_scripts', 'sam_announcement_enqueue_assets');

/**
 * Front-end assets for announcement widget
 */
function sam_announcement_widget_enqueue_assets() {
    $css_path = plugin_dir_path(__FILE__) . 'templates/css/announcement-widget.css';
    $css_url  = plugins_url('templates/css/announcement-widget.css', __FILE__);
    $version  = file_exists($css_path) ? filemtime($css_path) : null;
    wp_enqueue_style('sam-announcement-widget', $css_url, [], $version);
}
add_action('wp_enqueue_scripts', 'sam_announcement_widget_enqueue_assets');

/**
 * Shortcode: [active_announcements]
 */
function active_announcements_widget() {
    $today = date('Y-m-d');

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
/**
 * Shortcode: [sam_announcement_single]
 * Renders single announcement content compatible with block themes.
 */
function sam_announcement_single_shortcode() {
	if (!is_singular('announcement')) return '';
	$post_id = get_the_ID();

	$sub_title   = esc_html(get_post_meta($post_id, '_sa_sub_title', true));
	$location    = esc_html(get_post_meta($post_id, '_sa_location', true));
	$price_label = esc_html(get_post_meta($post_id, '_sa_price_label', true));

	$start_date = esc_html(get_post_meta($post_id, '_sa_start_date', true));
	$end_date   = esc_html(get_post_meta($post_id, '_sa_end_date', true));
	$end_range  = ($start_date === $end_date || empty($end_date)) ? '' : ' - ' . $end_date;

	$start_time = esc_html(get_post_meta($post_id, '_sa_start_time', true));
	$end_time   = esc_html(get_post_meta($post_id, '_sa_end_time', true));

	$img_id     = get_post_meta($post_id, '_sa_image', true);
	$image_url  = $img_id ? wp_get_attachment_url($img_id) : '';

	$speakers   = maybe_unserialize(get_post_meta($post_id, '_sa_speakers', true));
	$organizers = maybe_unserialize(get_post_meta($post_id, '_sa_organizers', true));
	$prices     = maybe_unserialize(get_post_meta($post_id, '_sa_prices', true));

	$meta_content = get_post_meta($post_id, '_sa_content', true);
	$content_html = $meta_content && is_string($meta_content)
		? apply_filters('the_content', $meta_content)
		: apply_filters('the_content', get_post_field('post_content', $post_id));

	ob_start();
	?>
	<article class="sam-announcement">
		<header class="sa-header">
			<h2 class="sa-subtitle"><?php echo $sub_title; ?></h2>
			<h1 class="sa-title"><?php echo esc_html(get_the_title($post_id)); ?></h1>
			<div class="sa-date-time-meta">
				<div class="sa-date"><?php echo $start_date . $end_range; ?></div>
				<div class="sa-date-time-spacer">,</div>
				<div class="sa-time"><?php echo esc_html__('Jam:', 'wp-sam-announcement') . ' ' . $start_time . ' - ' . $end_time; ?></div>
			</div>
			<?php if (!empty($location)) : ?>
				<div class="sa-location"><?php echo $location; ?></div>
			<?php endif; ?>
		</header>
		<?php if (!empty($image_url)) : ?>
			<div class="sa-row">
				<div class="sa-image">
					<figure><img src="<?php echo esc_url($image_url); ?>" class="sa-image" alt="<?php echo esc_attr(get_the_title($post_id)); ?>"></figure>
				</div>
				<div class="sa-content">
					<section class="sa-content"><?php echo $content_html; ?></section>
				</div>
			</div>
		<?php else : ?>
			<section class="sa-content"><?php echo $content_html; ?></section>
		<?php endif; ?>
		<?php if (!empty($speakers) && is_array($speakers)) : ?>
			<section class="announcement-speakers">
				<h2><?php echo esc_html__('Pembicara', 'wp-sam-announcement'); ?></h2>
				<ul>
					<?php foreach ($speakers as $sp) :
						$name = esc_html($sp['name'] ?? '');
						$prof = esc_html($sp['profession'] ?? '');
						$org  = esc_html($sp['organization'] ?? '');
						if ($name === '') continue; ?>
						<li>
							<strong><?php echo $name; ?></strong>
							<?php if ($prof) : ?><br><?php echo $prof; ?><?php endif; ?>
							<?php if ($org) : ?><br><?php echo $org; ?><?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>
		<?php if (!empty($organizers) && is_array($organizers)) : ?>
			<section class="announcement-organizers">
				<h2><?php echo esc_html__('Penyelenggara', 'wp-sam-announcement'); ?></h2>
				<ul>
					<?php foreach ($organizers as $org) :
						$oname = esc_html($org['name'] ?? '');
						$odesc = esc_html($org['description'] ?? '');
						if ($oname === '') continue; ?>
						<li>
							<strong><?php echo $oname; ?></strong>
							<?php if ($odesc) : ?><br><?php echo $odesc; ?><?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>
		<?php if (!empty($prices) && is_array($prices)) : ?>
			<section class="announcement-prices">
				<h2><?php echo $price_label ? $price_label : esc_html__('Harga', 'wp-sam-announcement'); ?></h2>
				<table class="price-table" style="width:100%;">
					<?php foreach ($prices as $p) :
						$name = esc_html($p['name'] ?? '');
						$val  = esc_html($p['value'] ?? '');
						if ($name === '' && $val === '') continue; ?>
						<tr>
							<td style="width:50%;"><?php echo $name; ?></td>
							<td style="width:50%; text-align:right;"><?php echo $val; ?></td>
						</tr>
					<?php endforeach; ?>
				</table>
			</section>
		<?php endif; ?>
	</article>
	<?php
	return ob_get_clean();
}
add_shortcode('sam_announcement_single', 'sam_announcement_single_shortcode');

/**
 * Shortcode: [sam_home_articles posts="3" blog_id=""]
 * - If blog_id provided (multisite), switches to that blog to fetch posts.
 */
function sam_home_articles_shortcode($atts = []) {
	$atts = shortcode_atts([
		'posts'   => 3,
		'blog_id' => '',
	], $atts, 'sam_home_articles');

	$restore = false;
	if (!empty($atts['blog_id']) && function_exists('switch_to_blog')) {
		switch_to_blog((int)$atts['blog_id']);
		$restore = true;
	}

	$q = new WP_Query([
		'post_type'           => 'post',
		'posts_per_page'      => (int)$atts['posts'],
		'ignore_sticky_posts' => true,
		'post_status'         => 'publish',
	]);

	ob_start();
	if ($q->have_posts()) {
		echo '<div class="sam-home-articles">';
		while ($q->have_posts()) {
			$q->the_post();
			$permalink = get_permalink();
			$title     = get_the_title();
			$excerpt   = get_the_excerpt();
			$thumb     = get_the_post_thumbnail(null, 'medium', ['style' => 'width:100%;height:auto;']);
			echo '<article class="sam-article-card" style="margin-bottom:1.2rem;">';
			if ($thumb) {
				echo '<a href="' . esc_url($permalink) . '">' . $thumb . '</a>';
			}
			echo '<h4 style="margin:.4rem 0;"><a href="' . esc_url($permalink) . '">' . esc_html($title) . '</a></h4>';
			if (!empty($excerpt)) {
				echo '<p style="margin:0;">' . esc_html(wp_trim_words($excerpt, 24)) . '</p>';
			}
			echo '</article>';
		}
		echo '</div>';
		wp_reset_postdata();
	} else {
		echo '<p>No articles found.</p>';
	}

	if ($restore) {
		restore_current_blog();
	}
	return ob_get_clean();
}
add_shortcode('sam_home_articles', 'sam_home_articles_shortcode');

/**
 * Shortcode: [sam_about_us page_slug="about"]
 * Pulls content from a page (default slug: about).
 */
function sam_about_us_shortcode($atts = []) {
	$atts = shortcode_atts([
		'page_slug' => 'about',
	], $atts, 'sam_about_us');

	$page = get_page_by_path(sanitize_title($atts['page_slug']), OBJECT, 'page');
	if (!$page) {
		return '<p>About page not found.</p>';
	}
	$title   = get_the_title($page);
	$content = apply_filters('the_excerpt', $page->post_excerpt);
	if (empty($content)) {
		$content = apply_filters('the_content', $page->post_content);
		$content = wp_kses_post(wp_trim_words(wp_strip_all_tags($content), 40));
	}
	$permalink = get_permalink($page);

	ob_start();
	echo '<div class="sam-about-us">';
	echo '<p>' . $content . '</p>';
	echo '<p><a href="' . esc_url($permalink) . '">Read more</a></p>';
	echo '</div>';
	return ob_get_clean();
}
add_shortcode('sam_about_us', 'sam_about_us_shortcode');

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
