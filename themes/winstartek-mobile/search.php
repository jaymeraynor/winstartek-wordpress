<?php get_header(); ?>

<h2 style="margin-bottom:16px;">
  <?php printf(esc_html__('Search results for: %s', 'winstartek-mobile'), '<strong>' . get_search_query() . '</strong>'); ?>
</h2>

<?php get_search_form(); ?>

<?php if (have_posts()): ?>
  <?php while (have_posts()): the_post(); ?>
    <article class="post-card">
      <div class="post-body">
        <h3 class="post-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
        <div class="post-meta"><?php echo esc_html(get_the_date()); ?></div>
        <div class="post-excerpt"><?php the_excerpt(); ?></div>
      </div>
    </article>
  <?php endwhile; ?>
  <div class="pagination"><?php the_posts_pagination(['mid_size' => 2]); ?></div>
<?php else: ?>
  <p><?php esc_html_e('No results found. Try a different search term.', 'winstartek-mobile'); ?></p>
<?php endif; ?>

<?php get_footer(); ?>
