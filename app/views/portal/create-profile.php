<?php
/** @var array $states */
/** @var array $practiceAreas */
/** @var string $csrf */
require VIEW_PATH . '/layouts/header.php';
?>

<nav class="breadcrumb-bar" aria-label="Breadcrumb">
  <div class="container">
    <ol>
      <li><a href="/">Home</a></li>
      <li><a href="/for-attorneys/">For Attorneys</a></li>
      <li aria-current="page">Create Profile</li>
    </ol>
  </div>
</nav>

<section class="auth-page">
  <div class="container narrow">
    <h1>Create Your Attorney Profile</h1>
    <p>Free to join. We'll verify your bar status before activating your profile (typically within 48 hours).</p>

    <form id="signupForm" method="post" action="/for-attorneys/create-profile/" class="auth-form" novalidate>
      <input type="hidden" name="<?= e(CSRF_TOKEN_NAME) ?>" value="<?= e($csrf) ?>">

      <div class="form-row form-row-2col">
        <div>
          <label for="su_first">First name *</label>
          <input id="su_first" type="text" name="first_name" required maxlength="80">
        </div>
        <div>
          <label for="su_last">Last name *</label>
          <input id="su_last" type="text" name="last_name" required maxlength="80">
        </div>
      </div>

      <div class="form-row">
        <label for="su_email">Work email *</label>
        <input id="su_email" type="email" name="email" required autocomplete="email">
      </div>

      <div class="form-row">
        <label for="su_password">Password (10+ characters) *</label>
        <input id="su_password" type="password" name="password" required minlength="10" autocomplete="new-password">
      </div>

      <div class="form-row form-row-2col">
        <div>
          <label for="su_bar">Bar number *</label>
          <input id="su_bar" type="text" name="bar_number" required maxlength="50">
        </div>
        <div>
          <label for="su_state">Bar state *</label>
          <select id="su_state" name="bar_state" required>
            <option value="">— Select state —</option>
            <?php foreach ($states as $s): ?>
              <option value="<?= e($s['code']) ?>"><?= e($s['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-block">Create Profile</button>
      <p class="form-disclaimer">
        By creating an account, you agree to our <a href="/terms/">Terms of Service</a> and <a href="/privacy/">Privacy Policy</a>.
      </p>
      <div id="signupStatus" role="status" aria-live="polite"></div>
    </form>

    <p class="auth-alt">
      Already have an account? <a href="/for-attorneys/login/">Log in</a>
    </p>
  </div>
</section>

<?php require VIEW_PATH . '/layouts/footer.php'; ?>
