<?php get_header(); ?>

<?php get_search_form(); ?>

<?php if (have_posts()): ?>
  <?php while (have_posts()): the_post(); ?>
    <article class="post-card">
      <?php if (has_post_thumbnail()): ?>
        <div class="post-thumbnail">
          <?php the_post_thumbnail('large'); ?>
        </div>
      <?php endif; ?>

      <div class="post-body">
        <h2 class="post-title">
          <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
        </h2>
        <div class="post-meta">
          <?php echo esc_html(get_the_date()); ?> &middot; <?php the_author(); ?>
        </div>
        <div class="post-excerpt">
          <?php the_excerpt(); ?>
        </div>
        <a class="read-more" href="<?php the_permalink(); ?>">
          <?php esc_html_e('Read more', 'winstartek-mobile'); ?> &rarr;
        </a>
      </div>
    </article>
  <?php endwhile; ?>

  <div class="pagination">
    <?php the_posts_pagination(['mid_size' => 2]); ?>
  </div>

<?php else: ?>
  <p><?php esc_html_e('No posts found.', 'winstartek-mobile'); ?></p>
<?php endif; ?>

<?php get_footer(); ?>
