<?php get_header(); ?>

<?php while (have_posts()): the_post(); ?>
  <article class="post-card">
    <?php if (has_post_thumbnail()): ?>
      <div class="post-thumbnail">
        <?php the_post_thumbnail('large'); ?>
      </div>
    <?php endif; ?>

    <div class="post-body">
      <header class="entry-header">
        <h1 class="entry-title"><?php the_title(); ?></h1>
        <div class="entry-meta">
          <?php echo esc_html(get_the_date()); ?> &middot; <?php the_author(); ?>
        </div>
      </header>

      <div class="entry-content">
        <?php the_content(); ?>
      </div>
    </div>
  </article>
<?php endwhile; ?>

<?php get_footer(); ?>
