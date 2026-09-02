(function () {
  'use strict';

  var API_URL = 'api/register.php';   // same domain-na relative path போதும்

  var form = document.getElementById('registerForm');
  if (!form) return;

  var alertBox  = document.getElementById('formAlert');
  var submitBtn = document.getElementById('submitBtn');
  var btnLabel  = submitBtn.querySelector('.btn-label');

  function clearErrors() {
    alertBox.hidden = true;
    alertBox.className = 'register__alert';
    form.querySelectorAll('.register__error').forEach(function (el) {
      el.textContent = '';
      el.classList.remove('show');
    });
    form.querySelectorAll('.form-control').forEach(function (el) {
      el.classList.remove('is-invalid');
    });
  }

  function showAlert(message, type) {
    alertBox.textContent = message;
    alertBox.className = 'register__alert is-' + type;
    alertBox.hidden = false;
    alertBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  function showFieldErrors(errors) {
    Object.keys(errors || {}).forEach(function (field) {
      var msg   = form.querySelector('[data-error-for="' + field + '"]');
      var input = form.querySelector('[name="' + field + '"]');
      if (msg)   { msg.textContent = errors[field]; msg.classList.add('show'); }
      if (input) { input.classList.add('is-invalid'); }
    });
  }

  function setLoading(on) {
    submitBtn.disabled = on;
    btnLabel.textContent = on ? 'Submitting…' : 'Register';
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    clearErrors();
    setLoading(true);

    var payload = {
      customerName: form.customerName.value.trim(),
      email:        form.email.value.trim(),
      phone1:       form.phone1.value.trim(),
      phone2:       form.phone2.value.trim(),
      address:      form.address.value.trim()
    };

    fetch(API_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })
      .then(function (res) {
        return res.json().then(function (data) {
          return { status: res.status, data: data };
        });
      })
      .then(function (result) {
        setLoading(false);
        if (result.data.success) {
          showAlert(result.data.message, 'success');
          form.reset();
          setTimeout(function () { window.location.href = 'index.html'; }, 2500);
        } else {
          showAlert(result.data.message || 'Registration failed.', 'error');
          showFieldErrors(result.data.errors);
        }
      })
      .catch(function (err) {
        setLoading(false);
        console.error(err);
        showAlert('Could not reach the server. Please try again.', 'error');
      });
  });
})();
