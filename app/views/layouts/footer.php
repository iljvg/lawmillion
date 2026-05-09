</main>

<footer class="site-footer" role="contentinfo">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-col">
        <h3>LawMillion</h3>
        <p>Connecting Americans with verified attorneys nationwide. Free consultations in 50 states.</p>
        <p class="footer-contact">
          <a href="tel:<?= e(SITE_PHONE) ?>"><?= e(us_phone(SITE_PHONE)) ?></a><br>
          <a href="mailto:<?= e(SITE_EMAIL) ?>"><?= e(SITE_EMAIL) ?></a>
        </p>
      </div>

      <div class="footer-col">
        <h4>Top Practice Areas</h4>
        <ul>
          <li><a href="/practice-areas/personal-injury/">Personal Injury</a></li>
          <li><a href="/practice-areas/employment-law/">Employment Law</a></li>
          <li><a href="/practice-areas/family-law/">Family Law</a></li>
          <li><a href="/practice-areas/bankruptcy/">Bankruptcy</a></li>
          <li><a href="/practice-areas/immigration/">Immigration</a></li>
          <li><a href="/practice-areas/">All Practice Areas →</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h4>For Attorneys</h4>
        <ul>
          <li><a href="/for-attorneys/">List Your Firm</a></li>
          <li><a href="/for-attorneys/create-profile/">Create Profile</a></li>
          <li><a href="/for-attorneys/login/">Attorney Login</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h4>About</h4>
        <ul>
          <li><a href="/about/">About LawMillion</a></li>
          <li><a href="/blog/">Legal Blog</a></li>
          <li><a href="/contact/">Contact</a></li>
          <li><a href="/privacy/">Privacy Policy</a></li>
          <li><a href="/terms/">Terms of Service</a></li>
        </ul>
      </div>
    </div>

    <div class="footer-bottom">
      <p>&copy; <?= date('Y') ?> LawMillion. All rights reserved. LawMillion is not a law firm and does not provide legal advice. The information on this site is for general informational purposes only.</p>
    </div>
  </div>
</footer>

<script src="/assets/js/app.js" defer></script>
</body>
</html>
