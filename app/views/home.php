<?php require VIEW_PATH . '/layouts/header.php'; ?>

<section class="hero">
  <div class="container">
    <h1>Find a Trusted Lawyer Near You — Free Consultation</h1>
    <p class="lede">Connect with verified U.S. attorneys in <?= count($practiceAreas) ?>+ practice areas. Personal injury, employment, family law, immigration & more — across all 50 states.</p>

    <form class="hero-search" action="/find-a-lawyer/" method="get" role="search">
      <label class="visually-hidden" for="hero_q">Search lawyers</label>
      <input id="hero_q" type="search" name="q" placeholder="Practice area, attorney name, or city" autocomplete="off">
      <button type="submit" class="btn btn-primary">Search</button>
    </form>

    <ul class="hero-stats" aria-label="Trust signals">
      <li><strong>50,000+</strong> verified attorneys</li>
      <li><strong>All 50</strong> U.S. states</li>
      <li><strong>4.9/5</strong> average rating</li>
      <li><strong>Free</strong> consultation</li>
    </ul>
  </div>
</section>

<section class="practice-areas-section" aria-labelledby="paHeading">
  <div class="container">
    <h2 id="paHeading">Browse by Practice Area</h2>
    <div class="practice-grid">
      <?php foreach ($practiceAreas as $pa): ?>
        <a href="/practice-areas/<?= e($pa['slug']) ?>/" class="practice-card">
          <h3><?= e($pa['name']) ?></h3>
          <span class="arrow">→</span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="featured-blog" aria-labelledby="blogHeading">
  <div class="container">
    <h2 id="blogHeading">Latest Legal Guides</h2>
    <div class="blog-grid">
      <?php foreach ($featuredPosts as $post): ?>
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
    <p class="text-center"><a href="/blog/" class="btn btn-secondary">View all guides</a></p>
  </div>
</section>

<section class="bottom-cta">
  <div class="container">
    <h2>Ready to talk to a lawyer?</h2>
    <p>Free consultation. No obligation. Get matched with a verified attorney in your area in minutes.</p>
    <a href="/find-a-lawyer/" class="btn btn-primary btn-large">Get Free Consultation</a>
  </div>
</section>

<?php require VIEW_PATH . '/layouts/footer.php'; ?>
