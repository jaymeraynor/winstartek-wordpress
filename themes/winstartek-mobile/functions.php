<?php
defined('ABSPATH') || exit;

function winstartek_setup(): void {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
    add_theme_support('responsive-embeds');
    add_theme_support('wp-block-styles');
    add_theme_support('custom-logo', [
        'height'      => 56,
        'flex-height' => true,
        'flex-width'  => true,
    ]);

    register_nav_menus([
        'primary' => __('Primary Menu', 'winstartek-mobile'),
        'bottom'  => __('Bottom Navigation', 'winstartek-mobile'),
    ]);
}
add_action('after_setup_theme', 'winstartek_setup');

function winstartek_enqueue(): void {
    $ver = wp_get_theme()->get('Version');
    wp_enqueue_style('winstartek-mobile', get_theme_file_uri('assets/css/mobile.css'), [], $ver);
    wp_enqueue_script('winstartek-mobile', get_theme_file_uri('assets/js/mobile.js'), [], $ver, true);
}
add_action('wp_enqueue_scripts', 'winstartek_enqueue');

// Mobile viewport meta (ensures proper scaling on all phones)
function winstartek_viewport_meta(): void {
    echo '<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">' . "\n";
    echo '<meta name="mobile-web-app-capable" content="yes">' . "\n";
    echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
    echo '<meta name="apple-mobile-web-app-status-bar-style" content="default">' . "\n";
    echo '<meta name="theme-color" content="#1a73e8">' . "\n";
}
add_action('wp_head', 'winstartek_viewport_meta', 1);

// Excerpt length optimized for mobile cards
add_filter('excerpt_length', fn() => 20);
add_filter('excerpt_more', fn() => '…');

// Remove unnecessary query strings from static resources
add_filter('style_loader_src',  'winstartek_remove_ver', 9999);
add_filter('script_loader_src', 'winstartek_remove_ver', 9999);
function winstartek_remove_ver(string $src): string {
    return strpos($src, 'ver=') ? remove_query_arg('ver', $src) : $src;
}

// Widget area
function winstartek_widgets(): void {
    register_sidebar([
        'name'          => __('Sidebar', 'winstartek-mobile'),
        'id'            => 'sidebar-1',
        'before_widget' => '<section class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ]);
}
add_action('widgets_init', 'winstartek_widgets');
