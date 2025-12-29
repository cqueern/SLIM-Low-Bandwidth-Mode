<?php
// SLIM single-request template: no wp_head/wp_footer, no external assets.
?><!doctype html><html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="slim-profile" content="SLIM v1.0">
  <?php
    $canon = is_singular() ? get_permalink(get_queried_object_id()) : home_url(add_query_arg(null, null));
    echo '<link rel="canonical" href="' . esc_url($canon) . '">';
    if (get_option('slim_noindex', true)) {
      echo '<meta name="robots" content="noindex,follow">';
    }
  ?>
  <title><?php echo esc_html(slimpress_context_title()); ?></title>
  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Ctext y='14' font-size='14'%3ES%3C/text%3E%3C/svg%3E">
</head>
<body>
<main>
<?php
echo '<nav>' . slimpress_home_link_html() . '</nav>';

if (is_home()) {
  // Home: show Posts and Pages as separate sections, and show the SLIM notice only once.
  echo '<h1>' . esc_html(slimpress_context_title()) . '</h1>';

  // Posts section
  $posts_q = new WP_Query([
    'post_type'           => 'post',
    'post_status'         => 'publish',
    'posts_per_page'      => 50,
    'orderby'             => 'date',
    'order'               => 'DESC',
    'ignore_sticky_posts' => true,
    'no_found_rows'       => true,
  ]);

  echo '<h2>' . esc_html__('Posts', 'slimpress') . '</h2>';
  if ($posts_q->have_posts()) {
  while ($posts_q->have_posts()) { $posts_q->the_post();
    $title = get_the_title() ?: '(untitled)';
    $perma = get_permalink();
     $date = get_the_date('F j, Y'); echo '<a href="' . esc_url($perma) . '">' . esc_html($title) . '</a>' . ($date ? ' <small>(' . esc_html($date) . ')</small>' : '') . '<br><br>';
  }
} else {
  echo '<p>' . esc_html__('No posts found.', 'slimpress') . '</p>';
}
wp_reset_postdata();

  // Pages section
  $pages_q = new WP_Query([
    'post_type'      => 'page',
    'post_status'    => 'publish',
    'posts_per_page' => 50,
    'orderby'        => 'date',
    'order'          => 'DESC',
    'no_found_rows'  => true,
  ]);

  echo '<h2>' . esc_html__('Pages', 'slimpress') . '</h2>';
  if ($pages_q->have_posts()) {
  while ($pages_q->have_posts()) { $pages_q->the_post();
    $title = get_the_title() ?: '(untitled)';
    $perma = get_permalink();
     $date = get_the_date('F j, Y'); echo '<a href="' . esc_url($perma) . '">' . esc_html($title) . '</a>' . ($date ? ' <small>(' . esc_html($date) . ')</small>' : '') . '<br><br>';
  }
} else {
  echo '<p>' . esc_html__('No pages found.', 'slimpress') . '</p>';
}
wp_reset_postdata();

  // SLIM notice appears once on homepage (as requested)

  // Admin-only dashboard link
  if (is_user_logged_in() && current_user_can('manage_options')) {
    echo '<p><a href="' . esc_url(admin_url()) . '">' . esc_html__('Admin Dashboard', 'slimpress') . '</a></p>';
  }

} elseif (have_posts()) {

  if (is_singular()) {
    while (have_posts()) { the_post();
      echo '<article>';
      echo '<h1>' . esc_html(get_the_title() ?: '(untitled)') . '</h1>';
      echo slimpress_published_date_html(get_the_ID());
      echo apply_filters('the_content', get_the_content('', false));
      echo '</article>';
    }

  } elseif (is_archive() || is_search()) {

    echo '<h1>' . esc_html(slimpress_context_title()) . '</h1>';
    echo '<ul>';
    while (have_posts()) { the_post();
      $title = get_the_title() ?: '(untitled)';
      $perma = get_permalink();
      $excerpt = slimpress_plain_excerpt(get_the_ID(), 220);
      $date = get_the_date('F j, Y');
      $type = get_post_type();
      echo '<li>';
      echo '<a href="' . esc_url($perma) . '">' . esc_html($title) . '</a>';
      if ($date) echo ' <small>(' . esc_html($date) . ')</small>';
      if ($type && $type === 'page') echo ' <small>' . esc_html__('(Page)', 'slimpress') . '</small>';
      if (!empty($excerpt)) echo '<div>' . esc_html($excerpt) . '</div>';
      echo '</li>';
    }
    echo '</ul>';

    $links = paginate_links([ 'type' => 'plain', 'prev_text' => '« Prev', 'next_text' => 'Next »' ]);
    if ($links) {
      $links = wp_strip_all_tags($links, true);
      echo '<nav>' . esc_html($links) . '</nav>';
    }

  } else {

    while (have_posts()) { the_post();
      echo '<article>';
      echo '<h1>' . esc_html(get_the_title()) . '</h1>';
      echo slimpress_published_date_html(get_the_ID());
      echo '<div>' . esc_html(slimpress_plain_excerpt(get_the_ID(), 300)) . '</div>';
      echo '</article>';
    }

  }

} else {
  echo '<h1>' . esc_html(slimpress_context_title()) . '</h1>';
  if (is_search()) echo '<p>' . esc_html__('No results found.', 'slimpress') . '</p>';
  else echo '<p>' . esc_html__('Nothing here.', 'slimpress') . '</p>';
}
?>
<?php echo '<footer>' . slimpress_notice_html() . '<nav>' . slimpress_home_link_html() . '</nav></footer>'; ?>
</main>
</body></html>