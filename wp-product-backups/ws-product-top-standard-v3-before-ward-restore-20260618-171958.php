<?php
/**
 * Plugin Name: Winstar Product Top Standard V3
 * Description: Normalizes custom product page top band to the Panel Light page standard.
 */
if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_footer', static function () {
    if (!is_singular()) {
        return;
    }

    $targets = [
        20255 => '.elementor-20255',
        20159 => '.elementor-20159',
        26533 => '.elementor-26533',
        26691 => '.elementor-17560.elementor-location-single.post-26691',
    ];

    $post_id = get_queried_object_id();
    if (!isset($targets[$post_id])) {
        return;
    }

    $body_post = 'body.postid-' . (int) $post_id;
    $body_page = 'body.page-id-' . (int) $post_id;
    $elementor = $targets[$post_id];

    $content = $body_post . ' #content.site-content, ' . $body_page . ' #content.site-content';
    $header = $body_post . ' #masthead, ' . $body_page . ' #masthead, ' . $body_post . ' .elementor-location-header, ' . $body_page . ' .elementor-location-header';
    $pseudo = $body_post . ' .site-content::before, ' . $body_page . ' .site-content::before, ' . $body_post . ' .site-content::after, ' . $body_page . ' .site-content::after';
    $band = $body_post . ' ' . $elementor . '::before, ' . $body_page . ' ' . $elementor . '::before';
    $content_band = '';
    if ((int) $post_id === 26533) {
        $content_band = $body_post . ' .ws-ward-shell::before, ' . $body_page . ' .ws-ward-shell::before';
    }
    ?>
    <style id="ws-product-top-standard-v3-css">
    <?php echo esc_html($content); ?> {
        padding-top: 0 !important;
        background: #f2f2f2 !important;
        background-image: none !important;
    }
    <?php echo esc_html($header); ?> {
        background: transparent !important;
        background-image: none !important;
    }
    <?php echo esc_html($pseudo); ?> {
        display: none !important;
        content: none !important;
    }
    <?php echo esc_html($band); ?> {
        content: "" !important;
        display: block !important;
        width: 100vw !important;
        min-height: 150px !important;
        margin-top: -76px !important;
        margin-bottom: 54px !important;
        margin-left: calc(50% - 50vw) !important;
        background:
            linear-gradient(90deg, rgba(30,32,34,.82), rgba(76,78,80,.68)),
            repeating-linear-gradient(135deg, rgba(255,255,255,.16) 0 1px, transparent 1px 32px),
            repeating-linear-gradient(45deg, rgba(255,255,255,.08) 0 1px, transparent 1px 52px),
            #5f6264 !important;
        background-size: auto, 32px 32px, 52px 52px, auto !important;
    }
    <?php if ($content_band !== '') : ?>
    <?php echo esc_html($content_band); ?> {
        content: "" !important;
        display: block !important;
        width: 100vw !important;
        min-height: 150px !important;
        margin-top: -76px !important;
        margin-bottom: 54px !important;
        margin-left: calc(50% - 50vw) !important;
        background:
            linear-gradient(90deg, rgba(30,32,34,.82), rgba(76,78,80,.68)),
            repeating-linear-gradient(135deg, rgba(255,255,255,.16) 0 1px, transparent 1px 32px),
            repeating-linear-gradient(45deg, rgba(255,255,255,.08) 0 1px, transparent 1px 52px),
            #5f6264 !important;
        background-size: auto, 32px 32px, 52px 52px, auto !important;
    }
    <?php endif; ?>
    @media (max-width: 921px) {
        <?php echo esc_html($band); ?> {
            min-height: 128px !important;
            margin-top: -74px !important;
            margin-bottom: 46px !important;
        }
        <?php if ($content_band !== '') : ?>
        <?php echo esc_html($content_band); ?> {
            min-height: 128px !important;
            margin-top: -74px !important;
            margin-bottom: 46px !important;
        }
        <?php endif; ?>
    }
    </style>
    <?php
}, 1001);

