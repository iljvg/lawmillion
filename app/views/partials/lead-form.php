<?php
/**
 * Lead capture form (used on practice-area pages, find-a-lawyer, etc.)
 * Expects: $practiceSlug (optional)
 */
$practiceSlug ??= '';
?>
<form id="leadForm" class="lead-form" action="/leads/submit" method="post" novalidate>
  <input type="hidden" name="<?= e(CSRF_TOKEN_NAME) ?>" value="<?= e(csrf_token()) ?>">
  <input type="hidden" name="practice_area" value="<?= e($practiceSlug) ?>">

  <div class="form-row">
    <label for="lf_name">Full name *</label>
    <input id="lf_name" type="text" name="full_name" required autocomplete="name" maxlength="120">
  </div>

  <div class="form-row form-row-2col">
    <div>
      <label for="lf_email">Email *</label>
      <input id="lf_email" type="email" name="email" required autocomplete="email">
    </div>
    <div>
      <label for="lf_phone">Phone</label>
      <input id="lf_phone" type="tel" name="phone" autocomplete="tel" placeholder="(555) 555-5555">
    </div>
  </div>

  <div class="form-row form-row-2col">
    <div>
      <label for="lf_state">State</label>
      <select id="lf_state" name="state">
        <option value="">— Select —</option>
        <?php foreach (Database::all('SELECT code, name FROM us_states ORDER BY name') as $s): ?>
          <option value="<?= e($s['code']) ?>"><?= e($s['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label for="lf_zip">ZIP code</label>
      <input id="lf_zip" type="text" name="zip" pattern="\d{5}(-\d{4})?" inputmode="numeric" maxlength="10" placeholder="12345">
    </div>
  </div>

  <div class="form-row">
    <label for="lf_summary">Briefly describe your case *</label>
    <textarea id="lf_summary" name="case_summary" rows="4" required minlength="20" maxlength="2000"></textarea>
  </div>

  <button type="submit" class="btn btn-primary btn-block">Get Free Consultation</button>
  <p class="form-disclaimer">
    By submitting, you agree to our <a href="/privacy/">privacy policy</a>. Submitting this form does not create an attorney-client relationship.
  </p>
  <div id="leadFormStatus" role="status" aria-live="polite"></div>
</form>
