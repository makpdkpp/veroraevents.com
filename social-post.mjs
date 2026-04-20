/**
 * social-post.mjs
 * Called by GitHub Actions when a new _posts/*.md is detected.
 * Usage: node social-post.mjs _posts/2026-04-20-my-article.md
 */

import matter from 'gray-matter';
import { readFileSync } from 'fs';
import { basename } from 'path';

const postFile = process.argv[2];
if (!postFile) { console.log('No post file — skipping.'); process.exit(0); }

const { data } = matter(readFileSync(postFile, 'utf8'));
const slug = data.slug || basename(postFile, '.md').replace(/^\d{4}-\d{2}-\d{2}-/, '');
const SITE_URL   = process.env.SITE_URL || 'https://veroraevents.com';
const articleUrl = `${SITE_URL}/blog/${slug}/`;

const caption = [
  data.title,
  '',
  data.description || '',
  '',
  `อ่านต่อได้ที่ → ${articleUrl}`,
].join('\n');

const FB_PAGE_ID           = process.env.FB_PAGE_ID;
const FB_PAGE_ACCESS_TOKEN = process.env.FB_PAGE_ACCESS_TOKEN;
const IG_USER_ID           = process.env.IG_USER_ID;
const postFB               = data.social?.facebook !== false;
const postIG               = data.social?.instagram !== false;

// ── Facebook ──────────────────────────────────────────────────────────────
async function postFacebook() {
  if (!postFB || !FB_PAGE_ID || !FB_PAGE_ACCESS_TOKEN) {
    console.log('Facebook: skipped'); return;
  }
  const res  = await fetch(`https://graph.facebook.com/v19.0/${FB_PAGE_ID}/feed`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ message: caption, link: articleUrl, access_token: FB_PAGE_ACCESS_TOKEN }),
  });
  const json = await res.json();
  if (json.id) {
    console.log(`Facebook: posted ✓ (id: ${json.id})`);
  } else {
    console.error('Facebook error:', JSON.stringify(json, null, 2));
    process.exitCode = 1;
  }
}

// ── Instagram ─────────────────────────────────────────────────────────────
async function postInstagram() {
  if (!postIG || !IG_USER_ID || !FB_PAGE_ACCESS_TOKEN) {
    console.log('Instagram: skipped (no credentials)'); return;
  }
  if (!data.image) {
    console.log('Instagram: skipped (no image in frontmatter)'); return;
  }
  const imageUrl = data.image.startsWith('http') ? data.image : `${SITE_URL}${data.image}`;

  // Step 1 — create media container
  const containerRes = await fetch(`https://graph.facebook.com/v19.0/${IG_USER_ID}/media`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ image_url: imageUrl, caption, access_token: FB_PAGE_ACCESS_TOKEN }),
  });
  const container = await containerRes.json();
  if (!container.id) {
    console.error('Instagram container error:', JSON.stringify(container, null, 2));
    process.exitCode = 1; return;
  }

  // Step 2 — publish
  const publishRes = await fetch(`https://graph.facebook.com/v19.0/${IG_USER_ID}/media_publish`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ creation_id: container.id, access_token: FB_PAGE_ACCESS_TOKEN }),
  });
  const result = await publishRes.json();
  if (result.id) {
    console.log(`Instagram: posted ✓ (id: ${result.id})`);
  } else {
    console.error('Instagram error:', JSON.stringify(result, null, 2));
    process.exitCode = 1;
  }
}

await postFacebook();
await postInstagram();
