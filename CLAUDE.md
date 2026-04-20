# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Verora Balloon Shop** — a static marketing/booking site for a balloon decoration and event services business in Thailand. No build step, no framework, no package manager.

## Development

**Serve locally** via Laragon (already configured at `http://veroraevents.com.test`) or any static file server.

There are no build, test, or lint commands — this is a zero-build-step static site.

## Architecture

Single-page site with three files doing all the work:

- **`index.html`** — all content, sections, and markup. Sections: Hero → Gallery → Services → Tools (pricing/timeline) → Contact
- **`styles.css`** — CSS custom properties define the design system (colors, spacing, typography). Uses CSS Grid/Flexbox for layout. Glass-morphism panels and gradient backgrounds are the dominant visual patterns.
- **`script.js`** — mobile nav toggle, inquiry form handler, client-side validation

### Design System (in `styles.css` `:root`)
- **Brand colors**: Pink `#f7d7de`, Gold `#c79c63`
- **Fonts**: Manrope (body), Cormorant Garamond (headings) — loaded from Google Fonts in `<head>`
- Primary language is Thai; English is secondary

### Assets
All brand assets live in `assets/`. Product/portfolio images are served from Unsplash CDN — no local image assets.

### SEO / PWA
`robots.txt`, `sitemap.xml`, `site.webmanifest`, and JSON-LD schema markup in `index.html` are intentionally maintained for SEO and installability.

## Design Guidance

`.github/prompts/ui-ux-pro-max/PROMPT.md` contains the full UI/UX design system specification used for this project — consult it before making visual changes.
