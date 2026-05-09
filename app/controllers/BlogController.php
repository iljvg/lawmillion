<?php
declare(strict_types=1);

final class BlogController
{
    /** GET /blog/ */
    public function index(): void
    {
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 12;
        $offset  = ($page - 1) * $perPage;

        $posts = Database::all(
            "SELECT p.slug, p.title, p.excerpt, p.featured_image_url,
                    p.date_published, p.reading_minutes, p.author_name,
                    c.name AS category_name, c.slug AS category_slug
             FROM blog_posts p
             LEFT JOIN blog_categories c ON c.id = p.category_id
             WHERE p.status = 'published'
             ORDER BY p.date_published DESC
             LIMIT {$perPage} OFFSET {$offset}"
        );

        $totalPosts = (int) Database::scalar(
            "SELECT COUNT(*) FROM blog_posts WHERE status = 'published'"
        );
        $totalPages = (int) ceil($totalPosts / $perPage);

        $seo = (new SEO())
            ->title('Legal Blog | Law Guides & Know Your Rights | LawMillion')
            ->description('Stay current on U.S. law. Plain-English guides on personal injury, employment rights, tax law, immigration, family law and more — written by experienced attorneys.')
            ->canonical('/blog/')
            ->breadcrumbs([
                ['Home', '/'],
                ['Blog', '/blog/'],
            ])
            ->addOrganization();

        view('blog/index', compact('seo', 'posts', 'page', 'totalPages'));
    }

    /** GET /blog/{slug}/ */
    public function show(string $slug): void
    {
        $post = Database::one(
            "SELECT p.*, c.name AS category_name, c.slug AS category_slug,
                    pa.name AS practice_name, pa.slug AS practice_slug
             FROM blog_posts p
             LEFT JOIN blog_categories c ON c.id = p.category_id
             LEFT JOIN practice_areas pa ON pa.id = p.practice_area_id
             WHERE p.slug = ? AND p.status = 'published' LIMIT 1",
            [$slug]
        );

        if (!$post) {
            http_response_code(404);
            require_once APP_PATH . '/controllers/ErrorController.php';
            (new ErrorController())->notFound();
            return;
        }

        // Increment view counter (fire-and-forget; tolerate failures)
        try {
            Database::exec('UPDATE blog_posts SET view_count = view_count + 1 WHERE id = ?', [$post['id']]);
        } catch (Throwable $e) {}

        // Related posts
        $related = Database::all(
            "SELECT slug, title, excerpt, date_published, reading_minutes
             FROM blog_posts
             WHERE status = 'published' AND id <> ?
               AND (practice_area_id = ? OR category_id = ?)
             ORDER BY date_published DESC LIMIT 3",
            [$post['id'], $post['practice_area_id'], $post['category_id']]
        );

        $path = '/blog/' . $post['slug'] . '/';

        $seo = (new SEO())
            ->title($post['meta_title'])
            ->description($post['meta_description'])
            ->canonical($path)
            ->ogType('article')
            ->ogImage($post['featured_image_url'] ?? '')
            ->breadcrumbs([
                ['Home', '/'],
                ['Blog', '/blog/'],
                [$post['title'], $path],
            ])
            ->addOrganization()
            ->addArticle([
                'headline'      => $post['title'],
                'url'           => SITE_URL . $path,
                'image'         => $post['featured_image_url'] ?? null,
                'datePublished' => (new DateTimeImmutable($post['date_published']))->format(DateTimeInterface::ATOM),
                'dateModified'  => (new DateTimeImmutable($post['date_modified']))->format(DateTimeInterface::ATOM),
                'author'        => $post['author_name'],
                'description'   => $post['excerpt'] ?? $post['meta_description'],
            ]);

        view('blog/show', compact('seo', 'post', 'related', 'path'));
    }
}
