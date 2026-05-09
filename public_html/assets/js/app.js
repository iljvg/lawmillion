/* =============================================================================
   LawMillion.com — Frontend JS
   Mobile nav + AJAX lead/signup form submission
   ============================================================================= */
(function () {
  'use strict';

  // -------- Mobile nav toggle --------
  var toggle = document.querySelector('.mobile-toggle');
  if (toggle) {
    toggle.addEventListener('click', function () {
      var nav = document.querySelector('.nav-main');
      if (!nav) return;
      var open = nav.style.display === 'block';
      nav.style.display = open ? 'none' : 'block';
      toggle.setAttribute('aria-expanded', open ? 'false' : 'true');
    });
  }

  // -------- Helper: AJAX form submission --------
  function bindAjaxForm(formId, statusId, onSuccess) {
    var form = document.getElementById(formId);
    if (!form) return;
    var status = document.getElementById(statusId);

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      if (status) status.innerHTML = '<p>Submitting…</p>';

      var fd = new FormData(form);
      var btn = form.querySelector('button[type="submit"]');
      if (btn) btn.disabled = true;

      fetch(form.action, {
        method: form.method.toUpperCase(),
        body:   fd,
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
      .then(function (r) { return r.json().catch(function(){ return { ok:false, errors:['Unexpected server response.']}; }); })
      .then(function (data) {
        if (btn) btn.disabled = false;
        if (data.ok) {
          form.reset();
          if (status) {
            status.innerHTML = '<div class="alert alert-success">' +
              (data.message || 'Thanks!') + '</div>';
          }
          if (typeof onSuccess === 'function') onSuccess(data);
        } else {
          var html = '<div class="alert alert-error"><ul style="margin:0;padding-left:1.25rem">';
          (data.errors || ['Something went wrong.']).forEach(function (m) {
            html += '<li>' + m + '</li>';
          });
          html += '</ul></div>';
          if (status) status.innerHTML = html;
        }
      })
      .catch(function () {
        if (btn) btn.disabled = false;
        if (status) status.innerHTML = '<div class="alert alert-error">Network error. Please try again.</div>';
      });
    });
  }

  bindAjaxForm('leadForm',   'leadFormStatus');
  bindAjaxForm('signupForm', 'signupStatus', function (data) {
    if (data.redirect) {
      setTimeout(function () { window.location.href = data.redirect; }, 1500);
    }
  });

  // -------- Phone input auto-format ((XXX) XXX-XXXX) --------
  document.querySelectorAll('input[type="tel"]').forEach(function (input) {
    input.addEventListener('input', function () {
      var d = this.value.replace(/\D/g, '').slice(0, 10);
      var f = '';
      if (d.length > 0) f  = '(' + d.slice(0, 3);
      if (d.length >= 4) f += ') ' + d.slice(3, 6);
      if (d.length >= 7) f += '-' + d.slice(6, 10);
      this.value = f;
    });
  });
})();
