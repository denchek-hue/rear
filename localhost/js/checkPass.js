const pass     = document.getElementById('pass');
const copyPass = document.getElementById('copyPass');
const msgPass  = document.getElementById('massagePass');

if (copyPass && pass) {
    copyPass.addEventListener('input', () => {
        if (pass.value !== copyPass.value) {
            if (msgPass) msgPass.textContent = 'Пароли не совпадают';
            copyPass.style.borderColor = '#dc2626';
        } else {
            if (msgPass) msgPass.textContent = '';
            copyPass.style.borderColor = '#16a34a';
        }
    });
}
