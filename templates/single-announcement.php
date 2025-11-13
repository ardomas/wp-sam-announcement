<?php
if (!defined('ABSPATH')) exit;

// Fallback: ensure CSS is loaded for single announcement
$__sa_css_url = plugin_dir_url(__FILE__) . 'css/sam-announcement.css';
echo '<link rel="stylesheet" id="sam-announcement-css" href="' . esc_url($__sa_css_url) . '" type="text/css" media="all" />';

if (have_posts()) :
	while (have_posts()) : the_post();
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
		?>

		<article class="sam-announcement">
			<header class="sa-header">
				<h2 class="sa-subtitle"><?php echo $sub_title; ?></h2>
				<h1 class="sa-title"><?php the_title(); ?></h1>

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
						<figure><img src="<?php echo esc_url($image_url); ?>" class="sa-image" alt="<?php echo esc_attr(get_the_title()); ?>"></figure>
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
							if ($name === '') continue;
							?>
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
							if ($oname === '') continue;
							?>
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
							if ($name === '' && $val === '') continue;
							?>
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
	endwhile;
endif;