<?php
/** @var string $csrf */
/** @var string|null $error */
require VIEW_PATH . '/layouts/header.php';
?>

<section class="auth-page">
  <div class="container narrow">
    <h1>Attorney Login</h1>
    <p>Access your LawMillion attorney dashboard.</p>

    <?php if ($error): ?>
      <div class="alert alert-error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="/for-attorneys/login/" class="auth-form" novalidate>
      <input type="hidden" name="<?= e(CSRF_TOKEN_NAME) ?>" value="<?= e($csrf) ?>">

      <div class="form-row">
        <label for="lg_email">Email</label>
        <input id="lg_email" type="email" name="email" required autocomplete="email" autofocus>
      </div>

      <div class="form-row">
        <label for="lg_password">Password</label>
        <input id="lg_password" type="password" name="password" required autocomplete="current-password" minlength="10">
      </div>

      <button type="submit" class="btn btn-primary btn-block">Log In</button>
    </form>

    <p class="auth-alt">
      New here? <a href="/for-attorneys/create-profile/">Create your attorney profile</a>
    </p>
  </div>
</section>

<?php require VIEW_PATH . '/layouts/footer.php'; ?>
