<?php require VIEW_PATH . '/layouts/header.php'; ?>

<nav class="breadcrumb-bar" aria-label="Breadcrumb">
  <div class="container">
    <ol>
      <li><a href="/">Home</a></li>
      <li aria-current="page">Practice Areas</li>
    </ol>
  </div>
</nav>

<section class="hero hero-compact">
  <div class="container">
    <h1>All Practice Areas</h1>
    <p class="lede">Find a verified U.S. attorney in any of our 15 practice areas. Free consultations, all 50 states.</p>
  </div>
</section>

<section class="practice-areas-section">
  <div class="container">
    <div class="practice-grid practice-grid-large">
      <?php foreach ($areas as $area): ?>
        <a href="/practice-areas/<?= e($area['slug']) ?>/" class="practice-card practice-card-large">
          <h2><?= e($area['name']) ?></h2>
          <p><?= e($area['meta_description']) ?></p>
          <span class="link-arrow">Find a <?= e($area['name']) ?> attorney →</span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ItemList JSON-LD for the hub page -->
<script type="application/ld+json">
<?= json_encode([
    '@context' => 'https://schema.org',
    '@type'    => 'CollectionPage',
    'name'     => 'All Practice Areas — LawMillion',
    'url'      => SITE_URL . '/practice-areas/',
    'mainEntity' => [
        '@type' => 'ItemList',
        'itemListElement' => array_map(fn($i, $a) => [
            '@type'    => 'ListItem',
            'position' => $i + 1,
            'url'      => SITE_URL . '/practice-areas/' . $a['slug'] . '/',
            'name'     => $a['name'],
        ], array_keys($areas), $areas),
    ],
], JSON_UNESCAPED_SLASHES) ?>
</script>

<?php require VIEW_PATH . '/layouts/footer.php'; ?>
