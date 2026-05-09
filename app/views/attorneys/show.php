<?php
/** @var array $row */
/** @var array $practiceAreas */
/** @var array $reviews */
/** @var string $fullName */
/** @var string $path */
require VIEW_PATH . '/layouts/header.php';
?>

<nav class="breadcrumb-bar" aria-label="Breadcrumb">
  <div class="container">
    <ol>
      <li><a href="/">Home</a></li>
      <li><a href="/find-a-lawyer/">Find a Lawyer</a></li>
      <li><?= e($row['state_name']) ?></li>
      <li><?= e($row['city_name']) ?></li>
      <li aria-current="page"><?= e($fullName) ?></li>
    </ol>
  </div>
</nav>

<section class="profile-hero">
  <div class="container profile-hero-grid">
    <div class="profile-photo">
      <?php if ($row['photo_url']): ?>
        <img src="<?= e($row['photo_url']) ?>" alt="<?= e($fullName) ?>" width="200" height="200">
      <?php else: ?>
        <div class="atty-avatar atty-avatar-placeholder atty-avatar-large" aria-hidden="true">
          <?= e(mb_substr($row['first_name'],0,1) . mb_substr($row['last_name'],0,1)) ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="profile-meta">
      <h1><?= e($fullName) ?></h1>
      <?php if ($row['headline']): ?>
        <p class="profile-headline"><?= e($row['headline']) ?></p>
      <?php endif; ?>
      <p class="profile-firm"><?= e($row['firm_name']) ?></p>
      <p class="profile-loc">
        <?= e($row['street_address']) ?><br>
        <?= e($row['city_name']) ?>, <?= e($row['state_code']) ?> <?= e($row['zip_code']) ?>
      </p>

      <div class="profile-badges">
        <?php if ($row['is_verified']): ?>
          <span class="badge badge-verified">✓ Verified</span>
        <?php endif; ?>
        <?php if ($row['free_consultation']): ?>
          <span class="badge">Free Consultation</span>
        <?php endif; ?>
        <?php if ($row['contingency_fee']): ?>
          <span class="badge">Contingency Fee Available</span>
        <?php endif; ?>
      </div>

      <?php if ($row['review_count'] > 0): ?>
        <div class="profile-rating">
          <strong><?= number_format((float)$row['rating_avg'], 1) ?></strong> ★
          <span>(<?= number_format((int)$row['review_count']) ?> reviews)</span>
        </div>
      <?php endif; ?>

      <div class="profile-cta">
        <?php if ($row['phone']): ?>
          <a href="tel:<?= e($row['phone']) ?>" class="btn btn-primary btn-large">Call <?= e(us_phone($row['phone'])) ?></a>
        <?php endif; ?>
        <a href="#contactForm" class="btn btn-secondary btn-large">Send Message</a>
      </div>
    </div>
  </div>
</section>

<section class="profile-body">
  <div class="container profile-body-grid">
    <div class="profile-main">
      <?php if ($row['bio_html']): ?>
        <h2>About <?= e($row['first_name']) ?></h2>
        <?= $row['bio_html'] ?>
      <?php endif; ?>

      <?php if (!empty($practiceAreas)): ?>
        <h2>Practice Areas</h2>
        <ul class="practice-tags">
          <?php foreach ($practiceAreas as $pa): ?>
            <li>
              <a href="/practice-areas/<?= e($pa['slug']) ?>/" class="tag <?= $pa['is_primary'] ? 'tag-primary' : '' ?>">
                <?= e($pa['name']) ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <h2>Credentials</h2>
      <dl class="credentials">
        <?php if ($row['years_practicing']): ?>
          <dt>Years practicing</dt>
          <dd><?= (int)$row['years_practicing'] ?> years</dd>
        <?php endif; ?>
        <?php if ($row['law_school']): ?>
          <dt>Law school</dt>
          <dd><?= e($row['law_school']) ?></dd>
        <?php endif; ?>
        <?php if ($row['bar_number']): ?>
          <dt>Bar number</dt>
          <dd><?= e($row['bar_number']) ?> (<?= e($row['state_name']) ?>)</dd>
        <?php endif; ?>
      </dl>

      <?php if (!empty($reviews)): ?>
        <h2>Client Reviews</h2>
        <div class="reviews-list">
          <?php foreach ($reviews as $r): ?>
            <article class="review-card">
              <header>
                <strong><?= e($r['reviewer_name']) ?></strong>
                <span class="review-stars" aria-label="<?= (int)$r['rating'] ?> out of 5"><?= str_repeat('★', (int)$r['rating']) . str_repeat('☆', 5 - (int)$r['rating']) ?></span>
                <time datetime="<?= e($r['created_at']) ?>"><?= e(long_date($r['created_at'])) ?></time>
              </header>
              <?php if ($r['title']): ?>
                <h4><?= e($r['title']) ?></h4>
              <?php endif; ?>
              <p><?= e($r['body']) ?></p>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <aside class="profile-sidebar" id="contactForm">
      <h3>Contact <?= e($row['first_name']) ?></h3>
      <p>Send a free, confidential message. Most attorneys respond within 24 hours.</p>
      <?php
        $primary = $practiceAreas[0]['slug'] ?? '';
        view('partials/lead-form', ['practiceSlug' => $primary]);
      ?>
    </aside>
  </div>
</section>

<?php require VIEW_PATH . '/layouts/footer.php'; ?>
