// @ts-check
import {themes as prismThemes} from 'prism-react-renderer';

/** @type {import('@docusaurus/types').Config} */
const config = {
  title: 'Devups',
  tagline: 'Framework PHP pour back-offices et API',
  favicon: 'img/favicon.ico',

  future: {},

  url: 'https://docs.devups.dev',
  baseUrl: '/',

  organizationName: 'yuricoco',
  projectName: 'devups-package',

  onBrokenLinks: 'throw',
  markdown: {
    hooks: {
      onBrokenMarkdownLinks: 'warn',
    },
  },

  i18n: {
    defaultLocale: 'fr',
    locales: ['fr'],
  },

  presets: [
    [
      'classic',
      /** @type {import('@docusaurus/preset-classic').Options} */
      ({
        docs: {
          routeBasePath: '/',
          sidebarPath: './sidebars.js',
          editUrl: 'https://github.com/yuricoco/devups-package/tree/master/docs-site/',
        },
        blog: false,
        theme: {
          customCss: './src/css/custom.css',
        },
      }),
    ],
  ],

  plugins: ['./plugins/llms-txt'],

  themeConfig:
    /** @type {import('@docusaurus/preset-classic').ThemeConfig} */
    ({
      image: 'img/docusaurus-social-card.jpg',
      colorMode: {
        respectPrefersColorScheme: true,
      },
      navbar: {
        title: 'Devups',
        logo: {
          alt: 'Devups',
          src: 'img/logo.svg',
        },
        items: [
          {type: 'docSidebar', sidebarId: 'demarrageSidebar', position: 'left', label: 'Démarrage'},
          {type: 'docSidebar', sidebarId: 'guidesSidebar', position: 'left', label: 'Guides'},
          {type: 'docSidebar', sidebarId: 'referenceSidebar', position: 'left', label: 'Référence'},
          {type: 'docSidebar', sidebarId: 'migrationSidebar', position: 'left', label: 'Migration'},
          {
            href: 'https://github.com/yuricoco/devups-package',
            label: 'GitHub',
            position: 'right',
          },
        ],
      },
      footer: {
        style: 'dark',
        links: [
          {
            title: 'Documentation',
            items: [
              {label: 'Démarrage', to: '/'},
              {label: 'Référence', to: '/reference'},
              {label: 'Migration', to: '/migration/8.0'},
            ],
          },
          {
            title: 'Pour les IA',
            items: [
              {label: 'llms.txt', href: 'https://docs.devups.dev/llms.txt'},
              {label: 'llms-full.txt', href: 'https://docs.devups.dev/llms-full.txt'},
            ],
          },
          {
            title: 'Projet',
            items: [
              {label: 'GitHub', href: 'https://github.com/yuricoco/devups-package'},
            ],
          },
        ],
        copyright: `Copyright © ${new Date().getFullYear()} Spacekola — Devups.`,
      },
      prism: {
        theme: prismThemes.github,
        darkTheme: prismThemes.dracula,
        additionalLanguages: ['php', 'bash', 'json'],
      },
    }),
};

export default config;
