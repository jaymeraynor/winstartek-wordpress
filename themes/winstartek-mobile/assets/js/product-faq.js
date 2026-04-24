(function () {
  'use strict';

  // FAQ accordion
  document.querySelectorAll('.faq-question').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var answerId = this.getAttribute('aria-controls');
      var answer   = document.getElementById(answerId);
      var isOpen   = this.getAttribute('aria-expanded') === 'true';

      // Close all others
      document.querySelectorAll('.faq-question').forEach(function (other) {
        var otherId = other.getAttribute('aria-controls');
        var otherAnswer = document.getElementById(otherId);
        other.setAttribute('aria-expanded', 'false');
        if (otherAnswer) otherAnswer.classList.remove('is-open');
      });

      // Toggle current
      if (!isOpen) {
        this.setAttribute('aria-expanded', 'true');
        answer.classList.add('is-open');
      }
    });
  });
})();
