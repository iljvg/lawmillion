<?php
/** @var array $area */
/** @var array $attorneys */
/** @var array $posts */
/** @var array $siblings */
/** @var array $faqs */
require VIEW_PATH . '/layouts/header.php';
?>

<nav class="breadcrumb-bar" aria-label="Breadcrumb">
  <div class="container">
    <ol>
      <li><a href="/">Home</a></li>
      <li><a href="/practice-areas/">Practice Areas</a></li>
      <li aria-current="page"><?= e($area['name']) ?></li>
    </ol>
  </div>
</nav>

<section class="practice-hero" aria-labelledby="paHero">
  <div class="container">
    <h1 id="paHero"><?= e($area['h1']) ?></h1>
    <?php if (!empty($area['intro_html'])): ?>
      <div class="lede"><?= $area['intro_html'] // already trusted HTML in DB ?></div>
    <?php endif; ?>

    <div class="practice-cta">
      <a href="#getMatched" class="btn btn-primary btn-large">Get Free Consultation</a>
      <a href="tel:<?= e(SITE_PHONE) ?>" class="btn btn-outline">Call <?= e(us_phone(SITE_PHONE)) ?></a>
    </div>
  </div>
</section>

<?php if (!empty($area['body_html'])): ?>
<section class="practice-body">
  <div class="container narrow">
    <?= $area['body_html'] ?>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($attorneys)): ?>
<section class="attorneys-section" aria-labelledby="attHeading">
  <div class="container">
    <h2 id="attHeading">Top <?= e($area['name']) ?> <?= e(ucfirst($area['noun'])) ?>s</h2>
    <div class="attorneys-grid">
      <?php foreach ($attorneys as $a):
        $name = trim($a['first_name'] . ' '
              . ($a['middle_initial'] ? $a['middle_initial'] . ' ' : '')
              . $a['last_name']);
        $href = sprintf('/attorneys/%s/%s/%s/',
                  e($a['state_slug']), e($a['city_slug']), e($a['slug']));
      ?>
      <article class="atty-card">
        <div class="atty-top">
          <?php if ($a['photo_url']): ?>
            <img src="<?= e($a['photo_url']) ?>" alt="" class="atty-avatar" width="96" height="96">
          <?php else: ?>
            <div class="atty-avatar atty-avatar-placeholder" aria-hidden="true"><?= e(mb_substr($a['first_name'],0,1) . mb_substr($a['last_name'],0,1)) ?></div>
          <?php endif; ?>
          <?php if ($a['is_verified']): ?>
            <span class="atty-vbadge" title="Verified attorney">✓ Verified</span>
          <?php endif; ?>
        </div>
        <div class="atty-body">
          <h3><a href="<?= $href ?>"><?= e($name) ?></a></h3>
          <?php if ($a['headline']): ?>
            <p class="atty-title"><?= e($a['headline']) ?></p>
          <?php endif; ?>
          <p class="atty-loc"><?= e($a['city_name']) ?>, <?= e($a['state_code']) ?></p>
          <?php if ($a['review_count'] > 0): ?>
            <p class="atty-stats">
              <strong><?= number_format((float)$a['rating_avg'], 1) ?></strong> ★
              <span>(<?= number_format((int)$a['review_count']) ?> reviews)</span>
            </p>
          <?php endif; ?>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <p class="text-center">
      <a href="/find-a-lawyer/?area=<?= e($area['slug']) ?>" class="btn btn-secondary">
        See more <?= e($area['name']) ?> <?= e($area['noun']) ?>s
      </a>
    </p>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($faqs)): ?>
<section class="faq-section" aria-labelledby="faqHeading">
  <div class="container narrow">
    <h2 id="faqHeading">Frequently Asked Questions About <?= e($area['name']) ?></h2>
    <?php foreach ($faqs as $faq): ?>
      <details class="faq-item">
        <summary><?= e($faq['q']) ?></summary>
        <div class="faq-answer"><p><?= e($faq['a']) ?></p></div>
      </details>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($posts)): ?>
<section class="related-articles" aria-labelledby="relArticles">
  <div class="container">
    <h2 id="relArticles"><?= e($area['name']) ?> Articles &amp; Guides</h2>
    <div class="blog-grid">
      <?php foreach ($posts as $post): ?>
        <article class="bcard">
          <div class="bcard-body">
            <h3><a href="/blog/<?= e($post['slug']) ?>/"><?= e($post['title']) ?></a></h3>
            <p><?= e($post['excerpt']) ?></p>
            <p class="bcard-meta">
              <time datetime="<?= e($post['date_published']) ?>"><?= e(long_date($post['date_published'])) ?></time>
              · <?= (int)$post['reading_minutes'] ?> min read
            </p>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="get-matched" id="getMatched" aria-labelledby="matchHeading">
  <div class="container narrow">
    <h2 id="matchHeading">Get Matched with a <?= e($area['name']) ?> <?= e(ucfirst($area['noun'])) ?></h2>
    <p>Tell us about your case. A verified <?= e(strtolower($area['name'])) ?> <?= e($area['noun']) ?> will contact you within 24 hours — free, no obligation.</p>
    <?php view('partials/lead-form', ['practiceSlug' => $area['slug']]); ?>
  </div>
</section>

<?php if (!empty($siblings)): ?>
<section class="related-areas">
  <div class="container">
    <h2>Other Practice Areas</h2>
    <ul class="sibling-list">
      <?php foreach ($siblings as $s): ?>
        <li><a href="/practice-areas/<?= e($s['slug']) ?>/"><?= e($s['name']) ?></a></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
<?php endif; ?>

<?php require VIEW_PATH . '/layouts/footer.php'; ?>
