<?php
/** @var array $attorneys */
/** @var array $practiceAreas */
/** @var array $states */
/** @var string $q */
/** @var string $practiceSlug */
/** @var string $stateSlug */
/** @var string $citySlug */
/** @var int $page */
require VIEW_PATH . '/layouts/header.php';
?>

<nav class="breadcrumb-bar" aria-label="Breadcrumb">
  <div class="container">
    <ol>
      <li><a href="/">Home</a></li>
      <li aria-current="page">Find a Lawyer</li>
    </ol>
  </div>
</nav>

<section class="hero hero-compact">
  <div class="container">
    <h1>Find a Lawyer</h1>
    <p class="lede">Search 50,000+ verified U.S. attorneys by practice area, state, and city.</p>
  </div>
</section>

<section class="directory">
  <div class="container directory-grid">

    <aside class="directory-filters">
      <form method="get" action="/find-a-lawyer/" class="filter-form">
        <div class="form-row">
          <label for="f_q">Search</label>
          <input id="f_q" type="search" name="q" value="<?= e($q) ?>" placeholder="Name, firm, or keyword">
        </div>

        <div class="form-row">
          <label for="f_area">Practice area</label>
          <select id="f_area" name="area">
            <option value="">All practice areas</option>
            <?php foreach ($practiceAreas as $pa): ?>
              <option value="<?= e($pa['slug']) ?>" <?= $pa['slug'] === $practiceSlug ? 'selected' : '' ?>>
                <?= e($pa['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-row">
          <label for="f_state">State</label>
          <select id="f_state" name="state">
            <option value="">All states</option>
            <?php foreach ($states as $s): ?>
              <option value="<?= e($s['slug']) ?>" <?= $s['slug'] === $stateSlug ? 'selected' : '' ?>>
                <?= e($s['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-row">
          <label for="f_city">City</label>
          <input id="f_city" type="text" name="city" value="<?= e($citySlug) ?>" placeholder="e.g. dallas">
        </div>

        <button type="submit" class="btn btn-primary btn-block">Apply Filters</button>
        <a href="/find-a-lawyer/" class="reset-link">Reset filters</a>
      </form>
    </aside>

    <div class="directory-results">
      <p class="results-count">
        <?= count($attorneys) === 0 ? 'No attorneys match your filters.' : 'Showing ' . count($attorneys) . ' attorneys' ?>
      </p>

      <?php if (empty($attorneys)): ?>
        <div class="empty-state">
          <p>Try broadening your search or <a href="#getMatched">tell us about your case</a> and we'll match you with the right attorney.</p>
        </div>
      <?php else: ?>
        <div class="attorneys-list">
          <?php foreach ($attorneys as $a):
            $name = trim($a['first_name'] . ' '
                  . ($a['middle_initial'] ? $a['middle_initial'] . ' ' : '')
                  . $a['last_name']);
            $href = sprintf('/attorneys/%s/%s/%s/',
                      e($a['state_slug']), e($a['city_slug']), e($a['slug']));
          ?>
          <article class="atty-card atty-card-wide">
            <?php if ($a['photo_url']): ?>
              <img src="<?= e($a['photo_url']) ?>" alt="" class="atty-avatar" width="96" height="96">
            <?php else: ?>
              <div class="atty-avatar atty-avatar-placeholder" aria-hidden="true">
                <?= e(mb_substr($a['first_name'],0,1) . mb_substr($a['last_name'],0,1)) ?>
              </div>
            <?php endif; ?>
            <div class="atty-body">
              <h3><a href="<?= $href ?>"><?= e($name) ?></a>
                <?php if ($a['is_verified']): ?>
                  <span class="atty-vbadge">✓ Verified</span>
                <?php endif; ?>
              </h3>
              <?php if ($a['headline']): ?>
                <p class="atty-title"><?= e($a['headline']) ?></p>
              <?php endif; ?>
              <p class="atty-loc"><?= e($a['city_name']) ?>, <?= e($a['state_code']) ?>
                <?php if ($a['firm_name']): ?> · <?= e($a['firm_name']) ?><?php endif; ?>
              </p>
              <?php if ($a['review_count'] > 0): ?>
                <p class="atty-stats">
                  <strong><?= number_format((float)$a['rating_avg'], 1) ?></strong> ★
                  <span>(<?= number_format((int)$a['review_count']) ?> reviews)</span>
                </p>
              <?php endif; ?>
            </div>
            <div class="atty-action">
              <a href="<?= $href ?>" class="btn btn-secondary">View Profile</a>
            </div>
          </article>
          <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <nav class="pagination" aria-label="Pagination">
          <?php if ($page > 1): ?>
            <a href="?<?= http_build_query(array_filter(['q'=>$q,'area'=>$practiceSlug,'state'=>$stateSlug,'city'=>$citySlug,'page'=>$page-1])) ?>" rel="prev">← Previous</a>
          <?php endif; ?>
          <span>Page <?= (int)$page ?></span>
          <?php if (count($attorneys) === 20): ?>
            <a href="?<?= http_build_query(array_filter(['q'=>$q,'area'=>$practiceSlug,'state'=>$stateSlug,'city'=>$citySlug,'page'=>$page+1])) ?>" rel="next">Next →</a>
          <?php endif; ?>
        </nav>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="get-matched" id="getMatched">
  <div class="container narrow">
    <h2>Don't see the right attorney? Tell us about your case.</h2>
    <p>A verified attorney will reach out within 24 hours. Free, no obligation.</p>
    <?php view('partials/lead-form', ['practiceSlug' => $practiceSlug]); ?>
  </div>
</section>

<?php require VIEW_PATH . '/layouts/footer.php'; ?>
