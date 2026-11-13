/**
 * OBU Student Marketplace - Client-side Form Validation
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Password Confirmation Check
    const regForm = document.getElementById('registrationForm');
    if (regForm) {
        const pwdInput = document.getElementById('reg_password');
        const confirmInput = document.getElementById('reg_confirm_password');
        const meterBar = document.getElementById('passwordMeterBar');

        if (pwdInput && meterBar) {
            pwdInput.addEventListener('input', function () {
                const val = this.value;
                let score = 0;
                if (val.length >= 8) score += 30;
                if (/[A-Z]/.test(val)) score += 20;
                if (/[a-z]/.test(val)) score += 20;
                if (/[0-9]/.test(val)) score += 20;
                if (/[^A-Za-z0-9]/.test(val)) score += 10;

                meterBar.style.width = Math.min(score, 100) + '%';
                if (score < 50) {
                    meterBar.style.backgroundColor = '#EF4444';
                } else if (score < 80) {
                    meterBar.style.backgroundColor = '#F59E0B';
                } else {
                    meterBar.style.backgroundColor = '#10B981';
                }
            });
        }

        regForm.addEventListener('submit', function (e) {
            let isValid = true;
            
            // Password match
            if (pwdInput && confirmInput && pwdInput.value !== confirmInput.value) {
                e.preventDefault();
                confirmInput.classList.add('is-invalid');
                let feedback = confirmInput.nextElementSibling;
                if (!feedback || !feedback.classList.contains('invalid-feedback')) {
                    feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback';
                    confirmInput.parentNode.appendChild(feedback);
                }
                feedback.innerText = 'Passwords do not match.';
                feedback.style.display = 'block';
                isValid = false;
            }

            // Student ID format validation
            const studentIdInput = document.getElementById('reg_student_id');
            if (studentIdInput) {
                const sid = studentIdInput.value.trim();
                if (sid.length < 3) {
                    e.preventDefault();
                    studentIdInput.classList.add('is-invalid');
                    isValid = false;
                }
            }

            return isValid;
        });
    }

    // 2. Generic Bootstrap form validation styling
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
});
