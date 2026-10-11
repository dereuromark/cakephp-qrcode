import { defineConfig } from 'vitepress'

export default defineConfig({
  title: 'cakephp-qrcode',
  description: 'QR images, payload formatters and optional image endpoints for CakePHP.',
  base: '/cakephp-qrcode/',
  cleanUrls: true,
  lastUpdated: true,
  head: [['link', { rel: 'icon', type: 'image/svg+xml', href: '/cakephp-qrcode/favicon.svg' }]],
  themeConfig: {
    logo: '/logo.svg',
    nav: [
      { text: 'Installation', link: '/guide/installation' },
      { text: 'Getting started', link: '/guide/getting-started' },
      { text: 'Helper', link: '/reference/helper' },
      { text: 'Library docs', link: 'https://php-qrcode.readthedocs.io/en/v6.0.x/' },
    ],
    sidebar: [{ text: 'Guide', items: [
      { text: 'Installation', link: '/guide/installation' },
      { text: 'Getting started', link: '/guide/getting-started' },
      { text: 'Image endpoints', link: '/guide/endpoints' },
      { text: 'Admin generator', link: '/guide/admin' },
    ] }, { text: 'Reference', items: [
      { text: 'Helper options', link: '/reference/helper' },
      { text: 'Payload formatters', link: '/reference/formatters' },
      { text: 'HTTP caching', link: '/reference/http' },
      { text: 'Upgrading and contributing', link: '/reference/upgrading' },
    ] }],
    search: { provider: 'local' },
    socialLinks: [{ icon: 'github', link: 'https://github.com/dereuromark/cakephp-qrcode' }],
    editLink: { pattern: 'https://github.com/dereuromark/cakephp-qrcode/edit/master/docs/:path' },
    footer: { message: 'Released under the MIT License.' },
  },
})
