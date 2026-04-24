<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header">
  <div class="header-inner">
    <div class="site-logo">
      <?php if (has_custom_logo()): ?>
        <?php the_custom_logo(); ?>
      <?php else: ?>
        <a href="<?php echo esc_url(home_url('/')); ?>"><?php bloginfo('name'); ?></a>
      <?php endif; ?>
    </div>

    <button class="menu-toggle" aria-controls="site-nav" aria-expanded="false" aria-label="<?php esc_attr_e('Toggle menu', 'winstartek-mobile'); ?>">
      <span class="hamburger">
        <span></span><span></span><span></span>
      </span>
    </button>
  </div>
</header>

<nav id="site-nav" class="site-nav" aria-label="<?php esc_attr_e('Primary navigation', 'winstartek-mobile'); ?>">
  <?php wp_nav_menu(['theme_location' => 'primary', 'menu_class' => 'nav-menu', 'container' => false]); ?>
</nav>

<main id="site-main" class="site-main">
