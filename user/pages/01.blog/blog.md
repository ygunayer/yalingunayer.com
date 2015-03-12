---
title: Home
blog_url: blog
body_classes: fullwidth

sitemap:
    changefreq: monthly
    priority: 1.03

content:
    items: @self.children
    order:
        by: date
        dir: desc
    limit: 5
    pagination: true

feed:
    description: Yalin's Blog
    limit: 10

pagination: true
---
