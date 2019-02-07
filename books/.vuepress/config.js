module.exports = {
    title: '実践 Laravel REST API 開発',
    description: 'HTML/CSS を使った Web 制作技術について、現場で使えるテクニックを紹介します。',
    head: [
        ['script', { src: "https://static.codepen.io/assets/embed/ei.js"}]
    ],
    locales: {
        '/': {
            lang: 'ja',
        },
    },
    markdown: {
        anchor: {
            level: [1,2,3],
            slugify: (s) => encodeURIComponent(String(s).trim().toLowerCase().replace(/\s+/g, '-')),
            permalink: true,
            permalinkBefore: true,
            permalinkSymbol: '#'
        },
        config: md => {
            md.use(require('markdown-it-playground'))
        },
        linkify: true
    },
    themeConfig: {
        nav: [
            { text: 'Lec Café', link: 'https://leccafe.connpass.com/' },
        ],
        sidebar: [
            '/1.LaravelによるREST API 開発/',
            '/2.RESTAPIにおける認証/',
            '/9.1Telescope を使ったデバッガの導入/',
            '/9.2 Factory を使ったテストデータの生成/',
            // '/2.レスポンシブデザイン',
            // '/3.クラス名の管理',
            // '/4.Meta要素とSEO',
        ],
        repo: 'lec-cafe/book_laravel_api',
        repoLabel: 'Github',
        docsDir: 'books',
        editLinks: true,
        editLinkText: 'ページに不明点や誤字等があれば、Github にて修正を提案してください！'
    }
}
