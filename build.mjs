import { readFileSync, writeFileSync, mkdirSync, readdirSync } from 'fs';
import { join, basename } from 'path';
import matter from 'gray-matter';
import { marked } from 'marked';

const POSTS_DIR    = '_posts';
const TPL_DIR      = '_templates';
const OUT_DIR      = 'blog';
const SITE_URL     = 'https://veroraevents.com';

const articleTpl   = readFileSync(join(TPL_DIR, 'article.html'),    'utf8');
const indexTpl     = readFileSync(join(TPL_DIR, 'blog-index.html'), 'utf8');

mkdirSync(OUT_DIR, { recursive: true });

const files = readdirSync(POSTS_DIR).filter(f => f.endsWith('.md') && f !== '.gitkeep');

const posts = files.map(filename => {
  const raw          = readFileSync(join(POSTS_DIR, filename), 'utf8');
  const { data, content } = matter(raw);
  const slug         = data.slug || basename(filename, '.md').replace(/^\d{4}-\d{2}-\d{2}-/, '');
  return { ...data, slug, body: content, html: marked(content), filename };
}).sort((a, b) => new Date(b.date) - new Date(a.date));

// --- Article pages ---
for (const post of posts) {
  const dir  = join(OUT_DIR, post.slug);
  mkdirSync(dir, { recursive: true });

  const date    = new Date(post.date);
  const dateTH  = date.toLocaleDateString('th-TH', { year: 'numeric', month: 'long', day: 'numeric' });
  const dateISO = date.toISOString();
  const ogImage = post.image
    ? (post.image.startsWith('http') ? post.image : `${SITE_URL}${post.image}`)
    : `${SITE_URL}/assets/social-preview.svg`;
  const heroHtml = post.image
    ? `<div class="article-hero"><img src="${post.image}" alt="${post.title}"></div>`
    : '';

  const page = articleTpl
    .replaceAll('{{TITLE}}',       post.title       || '')
    .replaceAll('{{DESCRIPTION}}', post.description || '')
    .replaceAll('{{DATE}}',        dateTH)
    .replaceAll('{{DATE_ISO}}',    dateISO)
    .replaceAll('{{SLUG}}',        post.slug)
    .replaceAll('{{CONTENT}}',     post.html)
    .replaceAll('{{OG_IMAGE}}',    ogImage)
    .replaceAll('{{HERO_IMAGE}}',  heroHtml)
    .replaceAll('{{SITE_URL}}',    SITE_URL);

  writeFileSync(join(dir, 'index.html'), page, 'utf8');
  console.log(`  ✓ blog/${post.slug}/`);
}

// --- Blog index ---
const cards = posts.length === 0
  ? '<p class="no-posts">ยังไม่มีบทความ</p>'
  : posts.map(post => {
      const dateTH  = new Date(post.date).toLocaleDateString('th-TH', { year: 'numeric', month: 'long', day: 'numeric' });
      const imgHtml = post.image
        ? `<img class="blog-card-img" src="${post.image}" alt="${post.title}" loading="lazy">`
        : `<div class="blog-card-img blog-card-img--placeholder"></div>`;
      return `
    <article class="blog-card glass-panel reveal">
      ${imgHtml}
      <div class="blog-card-body">
        <time class="eyebrow" datetime="${new Date(post.date).toISOString()}">${dateTH}</time>
        <h2><a href="/blog/${post.slug}/">${post.title}</a></h2>
        <p>${post.description || ''}</p>
        <a class="button button-ghost" href="/blog/${post.slug}/">อ่านต่อ →</a>
      </div>
    </article>`;
    }).join('\n');

writeFileSync(join(OUT_DIR, 'index.html'), indexTpl.replaceAll('{{POSTS}}', cards), 'utf8');
console.log(`  ✓ blog/index.html`);
console.log(`\nBuilt ${posts.length} article(s).`);
