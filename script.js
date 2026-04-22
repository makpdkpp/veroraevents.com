fetch('/_data/content.json', { cache: 'no-store' })
    .then((res) => (res.ok ? res.json() : null))
    .then((content) => {
        if (!content || typeof content !== 'object') return;
        document.querySelectorAll('[data-content]').forEach((el) => {
            const key = el.getAttribute('data-content');
            if (key && typeof content[key] === 'string' && content[key].trim() !== '') {
                el.textContent = content[key];
            }
        });
        document.querySelectorAll('[data-content-src]').forEach((el) => {
            const key = el.getAttribute('data-content-src');
            if (key && typeof content[key] === 'string' && content[key].trim() !== '') {
                el.setAttribute('src', content[key]);
            }
        });
        document.querySelectorAll('[data-content-href]').forEach((el) => {
            const key = el.getAttribute('data-content-href');
            if (key && typeof content[key] === 'string' && content[key].trim() !== '') {
                el.setAttribute('href', content[key]);
            }
        });
    })
    .catch(() => {});

const navToggle = document.querySelector('.nav-toggle');
const navMenu = document.querySelector('.nav-menu');
const inquiryForm = document.querySelector('#inquiry-form');
const formStatus = document.querySelector('#form-status');
const galleryGrid = document.querySelector('#gallery-grid');

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

if (galleryGrid) {
    fetch('/_data/gallery.json', { cache: 'no-store' })
        .then((res) => (res.ok ? res.json() : []))
        .then((items) => {
            if (!Array.isArray(items)) return;
            const sorted = [...items].sort((a, b) => {
                const ao = Number(a?.order ?? 0);
                const bo = Number(b?.order ?? 0);
                if (ao === bo) return String(a?.id ?? '').localeCompare(String(b?.id ?? ''));
                return ao - bo;
            });

            galleryGrid.innerHTML = '';
            for (const it of sorted) {
                const card = document.createElement('article');
                card.className = `gallery-card${it?.tall ? ' tall' : ''} reveal`;

                const img = document.createElement('img');
                img.className = 'gallery-image';
                img.src = String(it?.image ?? '');
                img.alt = String(it?.alt ?? '');
                img.loading = 'lazy';

                const copy = document.createElement('div');
                copy.className = 'gallery-copy glass-panel';

                const tag = document.createElement('span');
                tag.textContent = String(it?.tag ?? '');

                const title = document.createElement('h3');
                title.textContent = String(it?.title ?? '');

                copy.append(tag, title);
                card.append(img, copy);
                galleryGrid.append(card);
            }
        })
        .catch(() => {});
}