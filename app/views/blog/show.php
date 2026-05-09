<?php
/** @var array $post */
/** @var array $related */
/** @var string $path */
require VIEW_PATH . '/layouts/header.php';
?>

<nav class="breadcrumb-bar" aria-label="Breadcrumb">
  <div class="container">
    <ol>
      <li><a href="/">Home</a></li>
      <li><a href="/blog/">Blog</a></li>
      <li aria-current="page"><?= e($post['title']) ?></li>
    </ol>
  </div>
</nav>

<article class="article">
  <header class="article-header">
    <div class="container narrow">
      <?php if ($post['category_slug']): ?>
        <span class="bchip"><?= e($post['category_name']) ?></span>
      <?php endif; ?>
      <h1><?= e($post['title']) ?></h1>
      <p class="article-meta">
        By <strong><?= e($post['author_name']) ?></strong> ·
        <time datetime="<?= e($post['date_published']) ?>"><?= e(long_date($post['date_published'])) ?></time>
        <?php if ($post['date_modified'] && $post['date_modified'] !== $post['date_published']): ?>
          · Updated <time datetime="<?= e($post['date_modified']) ?>"><?= e(long_date($post['date_modified'])) ?></time>
        <?php endif; ?>
        · <?= (int)$post['reading_minutes'] ?> min read
      </p>
    </div>
  </header>

  <?php if ($post['featured_image_url']): ?>
    <div class="article-hero-image">
      <img src="<?= e($post['featured_image_url']) ?>" alt="" width="1200" height="600">
    </div>
  <?php endif; ?>

  <div class="container narrow article-body">
    <?= $post['body_html'] ?>
  </div>
</article>

<?php if (!empty($related)): ?>
<section class="related-articles">
  <div class="container">
    <h2>Related Articles</h2>
    <div class="blog-grid">
      <?php foreach ($related as $r): ?>
        <article class="bcard">
          <div class="bcard-body">
            <h3><a href="/blog/<?= e($r['slug']) ?>/"><?= e($r['title']) ?></a></h3>
            <p><?= e($r['excerpt']) ?></p>
            <p class="bcard-meta">
              <time datetime="<?= e($r['date_published']) ?>"><?= e(long_date($r['date_published'])) ?></time>
              · <?= (int)$r['reading_minutes'] ?> min read
            </p>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($post['practice_slug'])): ?>
<section class="get-matched">
  <div class="container narrow">
    <h2>Need help with a <?= e(strtolower($post['practice_name'])) ?> case?</h2>
    <p>Get matched with a verified attorney in your state. Free consultation.</p>
    <?php view('partials/lead-form', ['practiceSlug' => $post['practice_slug']]); ?>
  </div>
</section>
<?php endif; ?>

<?php require VIEW_PATH . '/layouts/footer.php'; ?>
