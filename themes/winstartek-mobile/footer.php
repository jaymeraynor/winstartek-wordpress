</main><!-- #site-main -->

<nav class="bottom-nav" aria-label="<?php esc_attr_e('Bottom navigation', 'winstartek-mobile'); ?>">
  <a href="<?php echo esc_url(home_url('/')); ?>">
    <span class="nav-icon">🏠</span>
    <span><?php esc_html_e('Home', 'winstartek-mobile'); ?></span>
  </a>
  <a href="<?php echo esc_url(get_search_link()); ?>">
    <span class="nav-icon">🔍</span>
    <span><?php esc_html_e('Search', 'winstartek-mobile'); ?></span>
  </a>
  <a href="<?php echo esc_url(get_permalink(get_option('page_for_posts'))); ?>">
    <span class="nav-icon">📰</span>
    <span><?php esc_html_e('Blog', 'winstartek-mobile'); ?></span>
  </a>
  <?php if (is_user_logged_in()): ?>
  <a href="<?php echo esc_url(admin_url()); ?>">
    <span class="nav-icon">⚙️</span>
    <span><?php esc_html_e('Admin', 'winstartek-mobile'); ?></span>
  </a>
  <?php else: ?>
  <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>">
    <span class="nav-icon">👤</span>
    <span><?php esc_html_e('Login', 'winstartek-mobile'); ?></span>
  </a>
  <?php endif; ?>
</nav>

<footer class="site-footer">
  <p>&copy; <?php echo esc_html(date('Y')); ?> <?php bloginfo('name'); ?>. All rights reserved.</p>
  <?php wp_footer(); ?>
</footer>
</body>
</html>
