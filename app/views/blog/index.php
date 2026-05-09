<?php
/** @var array $posts */
/** @var int $page */
/** @var int $totalPages */
require VIEW_PATH . '/layouts/header.php';
?>

<nav class="breadcrumb-bar" aria-label="Breadcrumb">
  <div class="container">
    <ol>
      <li><a href="/">Home</a></li>
      <li aria-current="page">Blog</li>
    </ol>
  </div>
</nav>

<section class="hero hero-compact">
  <div class="container">
    <h1>Legal Blog</h1>
    <p class="lede">Plain-English guides on U.S. law from experienced attorneys. Stay informed about your rights.</p>
  </div>
</section>

<section class="blog-section">
  <div class="container">
    <?php if (empty($posts)): ?>
      <p>No articles yet — check back soon.</p>
    <?php else: ?>
      <div class="blog-grid">
        <?php foreach ($posts as $post): ?>
          <article class="bcard">
            <?php if ($post['featured_image_url']): ?>
              <a href="/blog/<?= e($post['slug']) ?>/" class="bcard-img">
                <img src="<?= e($post['featured_image_url']) ?>" alt="" loading="lazy" width="400" height="225">
              </a>
            <?php endif; ?>
            <div class="bcard-body">
              <?php if ($post['category_slug']): ?>
                <span class="bchip"><?= e($post['category_name']) ?></span>
              <?php endif; ?>
              <h2><a href="/blog/<?= e($post['slug']) ?>/"><?= e($post['title']) ?></a></h2>
              <p><?= e($post['excerpt']) ?></p>
              <p class="bcard-meta">
                <time datetime="<?= e($post['date_published']) ?>"><?= e(long_date($post['date_published'])) ?></time>
                · <?= (int)$post['reading_minutes'] ?> min read
                · <?= e($post['author_name']) ?>
              </p>
            </div>
          </article>
        <?php endforeach; ?>
      </div>

      <?php if ($totalPages > 1): ?>
        <nav class="pagination" aria-label="Pagination">
          <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>" rel="prev">← Newer</a>
          <?php endif; ?>
          <span>Page <?= (int)$page ?> of <?= (int)$totalPages ?></span>
          <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>" rel="next">Older →</a>
          <?php endif; ?>
        </nav>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>

<?php require VIEW_PATH . '/layouts/footer.php'; ?>
