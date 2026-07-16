import { defineConfig } from 'astro/config';
import sitemap from '@astrojs/sitemap';

// ВАЖНО: заменить на реальный домен перед публикацией (нужно для sitemap, canonical, OG).
const SITE = 'https://buhgalter-orenburg.ru';

export default defineConfig({
  site: SITE,
  integrations: [sitemap()],
  build: { inlineStylesheets: 'auto' },
  compressHTML: true,
});
