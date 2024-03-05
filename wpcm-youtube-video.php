<?php
/**
 * Plugin Name: WPCM Youtube Video
 * Description: Um plugin para exibir vídeos do Youtube em um layout personalizado sem mostrar vídeos sugeridos ao final.
 * Version: 1.5
 * Author: Daniel Oliveira da Paixão
 * License: GPL2
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

function wpcm_youtube_video_activate() {
    add_option('wpcm_youtube_videos', []);
}
register_activation_hook(__FILE__, 'wpcm_youtube_video_activate');

function wpcm_youtube_video_deactivate() {
    delete_option('wpcm_youtube_videos');
}
register_deactivation_hook(__FILE__, 'wpcm_youtube_video_deactivate');
function wpcm_youtube_video_enqueue_scripts() {
    $css = "
        #video-section {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .video-list {
            list-style-type: none;
            padding: 0;
            flex-basis: 30%;
            max-width: 30%;
        }
        .video-list-item {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            cursor: pointer;
        }
        .video-list-item:last-child {
            border-bottom: none;
        }
        .video-feature {
            flex-grow: 1;
            max-width: 70%;
        }
        .video-feature iframe {
            width: 100%;
            height: 400px;
        }
        @media (max-width: 768px) {
            .video-list, .video-feature {
                flex-basis: 100%;
                max-width: 100%;
            }
        }
    ";
    
    $js = "
        document.addEventListener('DOMContentLoaded', function() {
            var items = document.querySelectorAll('.video-list-item');
            var featuredVideo = document.getElementById('featured-video');
            items.forEach(function(item) {
                item.addEventListener('click', function() {
                    var videoUrl = this.getAttribute('data-video-url');
                    featuredVideo.src = videoUrl + '?autoplay=1&rel=0&showinfo=0';
                });
            });
        });
    ";

    echo '<style>' . $css . '</style>';
    echo '<script type="text/javascript">' . $js . '</script>';
}
add_action('wp_head', 'wpcm_youtube_video_enqueue_scripts');
function wpcm_youtube_video_shortcode() {
    $videos = get_option('wpcm_youtube_videos', []);
    ob_start();
    ?>
    <div id="video-section">
        <ul class="video-list">
            <?php foreach ($videos as $video): ?>
                <li class="video-list-item" data-video-url="<?php echo esc_url($video['url']); ?>">
                    <span class="video-list-item-date"><?php echo esc_html($video['date']); ?></span>
                    <div class="video-list-item-title"><?php echo esc_html($video['title']); ?></div>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="video-feature">
            <iframe id="featured-video" src="" frameborder="0" allowfullscreen></iframe>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('wpcm_youtube_video', 'wpcm_youtube_video_shortcode');
function wpcm_youtube_video_admin_menu() {
    add_menu_page(
        'WPCM Youtube Video Settings',
        'Youtube Video',
        'manage_options',
        'wpcm-youtube-video',
        'wpcm_youtube_video_settings_page'
    );
}
add_action('admin_menu', 'wpcm_youtube_video_admin_menu');

function wpcm_youtube_video_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_POST['wpcm_youtube_video_nonce'], $_POST['wpcm_youtube_video_urls']) && wp_verify_nonce($_POST['wpcm_youtube_video_nonce'], 'wpcm_youtube_video_update')) {
        $video_urls = explode("\n", sanitize_textarea_field($_POST['wpcm_youtube_video_urls']));
        $videos = array_map('wpcm_youtube_video_sanitize_video', $video_urls);
        update_option('wpcm_youtube_videos', $videos);
        echo '<div class="notice notice-success is-dismissible"><p>Video URLs updated successfully.</p></div>';
    }

    $saved_videos = get_option('wpcm_youtube_videos', []);
    ?>
    <div class="wrap">
        <h1><?= esc_html(get_admin_page_title()); ?></h1>
        <form method="post" action="">
            <?php wp_nonce_field('wpcm_youtube_video_update', 'wpcm_youtube_video_nonce'); ?>
            <textarea name="wpcm_youtube_video_urls" rows="10" class="large-text"><?php echo esc_textarea(implode("\n", array_column($saved_videos, 'url'))); ?></textarea>
            <p><input type="submit" value="Save Changes" class="button button-primary" /></p>
        </form>
    </div>
    <?php
}

function wpcm_youtube_video_sanitize_video($url) {
    $url = trim($url);
    return filter_var($url, FILTER_VALIDATE_URL) ? ['url' => $url, 'title' => '', 'date' => ''] : '';
}
