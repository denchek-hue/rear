// Шапка при скролле
const header = document.getElementById('header');
if (header) {
    window.addEventListener('scroll', () => {
        header.classList.toggle('scrolled', window.scrollY > 50);
    });
}

// Мобильное меню
const nav = document.querySelector('.nav-links');
function toggleMenu() {
    if (nav) nav.classList.toggle('open');
}
document.querySelectorAll('.nav-links a, .nav-links button').forEach(el => {
    el.addEventListener('click', () => nav && nav.classList.remove('open'));
});

// Модальные окна
function openModal(type)  { const m = document.getElementById(`modal-${type}`); if (m) m.classList.add('active'); }
function closeModal(type) { const m = document.getElementById(`modal-${type}`); if (m) m.classList.remove('active'); }
document.querySelectorAll('.modal').forEach(m => {
    m.addEventListener('click', e => { if (e.target === m) m.classList.remove('active'); });
});
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') document.querySelectorAll('.modal').forEach(m => m.classList.remove('active'));
});

// Анимация при скролле
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) entry.target.classList.add('visible');
    });
}, { threshold: 0.1 });
document.querySelectorAll('.fade-up').forEach(el => observer.observe(el));

// Форма обратной связи
const contactForm = document.getElementById('contact-form');
if (contactForm) {
    contactForm.addEventListener('submit', e => {
        e.preventDefault();
        const btn = contactForm.querySelector('button[type="submit"]');
        const orig = btn.textContent;
        btn.textContent = 'Отправлено ✓';
        btn.disabled = true;
        btn.style.background = '#16a34a';
        setTimeout(() => {
            btn.textContent = orig;
            btn.disabled = false;
            btn.style.background = '';
            contactForm.reset();
        }, 3000);
    });
}
