const navToggle = document.querySelector('.nav-toggle');
const navMenu = document.querySelector('.nav-menu');
const inquiryForm = document.querySelector('#inquiry-form');
const formStatus = document.querySelector('#form-status');

if (navToggle && navMenu) {
    navToggle.addEventListener('click', () => {
        const isOpen = navMenu.classList.toggle('is-open');
        navToggle.setAttribute('aria-expanded', String(isOpen));
    });

    navMenu.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            navMenu.classList.remove('is-open');
            navToggle.setAttribute('aria-expanded', 'false');
        });
    });
}

if (inquiryForm && formStatus) {
    inquiryForm.addEventListener('submit', (event) => {
        event.preventDefault();

        const formData = new FormData(inquiryForm);
        const name = (formData.get('name') || 'คุณลูกค้า').toString().trim();

        formStatus.textContent = `ขอบคุณ ${name} เราได้รับรายละเอียดงานแล้ว และจะติดต่อกลับภายใน 24 ชั่วโมง`;
        inquiryForm.reset();
    });
}