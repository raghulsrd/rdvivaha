
(function () {
  'use strict';

  var API_URL = 'https://www.samplefakeentries.org/skynetbee/api/register-customer.php';

  var form = document.getElementById('registerForm');
  if (!form) return;

  var alertBox = document.getElementById('formAlert');
  var submitBtn = document.getElementById('submitBtn');
  var btnLabel = submitBtn.querySelector('.btn-label');

  function clearErrors() {
    alertBox.hidden = true;
    alertBox.textContent = '';
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
    alertBox.scrollIntoView({
      behavior: 'smooth',
      block: 'center'
    });
  }

  function showFieldError(field, message) {
    var errorBox = form.querySelector('[data-error-for="' + field + '"]');
    var input = form.querySelector('[name="' + field + '"]');

    if (errorBox) {
      errorBox.textContent = message;
      errorBox.classList.add('show');
    }

    if (input) {
      input.classList.add('is-invalid');
      input.focus();
    }
  }

  function setLoading(loading) {
    submitBtn.disabled = loading;
    btnLabel.textContent = loading ? 'Submitting…' : 'Register';
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();

    clearErrors();

    var customerName = form.customerName.value.trim();
    var email = form.email.value.trim();
    var phone1 = form.phone1.value.replace(/\D/g, '');
    var phone2 = form.phone2.value.replace(/\D/g, '');
    var address = form.address.value.trim();

    if (customerName === '') {
      showFieldError('customerName', 'Full name is required');
      return;
    }

    if (email === '') {
      showFieldError('email', 'Email is required');
      return;
    }

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      showFieldError('email', 'Enter a valid email address');
      return;
    }

    if (!/^[6-9]\d{9}$/.test(phone1)) {
      showFieldError('phone1', 'Enter a valid 10 digit phone number');
      return;
    }

    if (phone2 !== '' && !/^[6-9]\d{9}$/.test(phone2)) {
      showFieldError('phone2', 'Enter a valid alternate phone number');
      return;
    }

    if (phone2 !== '' && phone1 === phone2) {
      showFieldError('phone2', 'Both phone numbers cannot be same');
      return;
    }

    if (address === '') {
      showFieldError('address', 'Address is required');
      return;
    }

    var payload = {
      customerName: customerName,
      email: email,
      phone1: phone1,
      phone2: phone2,
      address: address
    };

    setLoading(true);

    fetch(API_URL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(payload)
    })
    .then(function (response) {
      return response.text().then(function (text) {
        var data;

        try {
          data = JSON.parse(text);
        } catch (e) {
          throw new Error('Invalid response from server: ' + text);
        }

        return {
          httpStatus: response.status,
          data: data
        };
      });
    })
    .then(function (result) {
      setLoading(false);

      var data = result.data;

      if (data.status === 1 || data.success === true) {
        showAlert(
          data.message || 'Registration successful',
          'success'
        );

        form.reset();

        setTimeout(function () {
          window.location.href = 'index.html';
        }, 2500);

        return;
      }

      if (data.field) {
        showFieldError(
          data.field,
          data.message || 'Please check this field'
        );
        return;
      }

      if (data.errors) {
        Object.keys(data.errors).forEach(function (field) {
          showFieldError(field, data.errors[field]);
        });

        return;
      }

      showAlert(
        data.message || 'Registration failed',
        'error'
      );
    })
    .catch(function (error) {
      setLoading(false);

      console.error('Registration API Error:', error);

      showAlert(
        'Could not reach the server. Please try again.',
        'error'
      );
    });
  });
})();
```
