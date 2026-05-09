<!DOCTYPE html>
<html lang="en-US" itemscope itemtype="https://schema.org/WebPage">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#080F1E">

<?= $seo->render() ?>

<link rel="icon" type="image/png" sizes="32x32" href="/assets/images/favicon-32.png">
<link rel="apple-touch-icon" sizes="180x180" href="/assets/images/icon-180x180.png">
<link rel="manifest" href="/manifest.json">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>

<a class="skip-link" href="#main-content">Skip to main content</a>

<header class="site-header" id="siteHeader" role="banner">
  <div class="container header-row">
    <a href="/" class="brand" aria-label="LawMillion home">
      <img src="/assets/images/logo.svg" alt="LawMillion" width="160" height="36">
    </a>

    <nav class="nav-main" role="navigation" aria-label="Main navigation">
      <ul>
        <li><a href="/practice-areas/">Practice Areas</a></li>
        <li><a href="/find-a-lawyer/">Find a Lawyer</a></li>
        <li><a href="/blog/">Legal Blog</a></li>
        <li><a href="/for-attorneys/">For Attorneys</a></li>
      </ul>
    </nav>

    <div class="header-cta">
      <a href="tel:<?= e(SITE_PHONE) ?>" class="phone-link"><?= e(us_phone(SITE_PHONE)) ?></a>
      <a href="/find-a-lawyer/" class="btn btn-primary">Free Consultation</a>
    </div>

    <button class="mobile-toggle" aria-label="Open menu" aria-expanded="false" aria-controls="mobileNav">
      <span></span><span></span><span></span>
    </button>
  </div>
</header>

<main id="main-content">
