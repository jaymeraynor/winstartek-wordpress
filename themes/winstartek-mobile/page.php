<?php get_header(); ?>

<?php while (have_posts()): the_post(); ?>
  <article class="post-card">
    <div class="post-body">
      <header class="entry-header">
        <h1 class="entry-title"><?php the_title(); ?></h1>
      </header>
      <div class="entry-content">
        <?php the_content(); ?>
      </div>
    </div>
  </article>
<?php endwhile; ?>

<?php get_footer(); ?>
