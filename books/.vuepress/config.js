module.exports = {
    title: 'Laravel REST API 開発 入門',
    description: 'Laravel を 利用してREST API を作成するための 入門講座です。',
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
            '/1.タスクリストAPIの作成/',
            '/2.Eloquent による DB 操作/',
            '/3.REST API の設計/',
            '/4.REST API のテスト/',
            '/5.REST API のデバッグ/',
            '/実践演習/',
          {
            title: '補足資料',
            children: [
              '/9.環境構築 Vagrant/',
              '/9.環境構築 Mac/',
            ]
          },
        ],
        repo: 'lec-cafe/books_laravelapi_basic',
        repoLabel: 'Github',
        docsDir: 'books',
        editLinks: true,
        editLinkText: 'ページに不明点や誤字等があれば、Github にて修正を提案してください！'
    }
}
