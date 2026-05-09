<?php
declare(strict_types=1);

final class HomeController
{
    public function index(): void
    {
        $practiceAreas = Database::all(
            'SELECT slug, name FROM practice_areas WHERE is_active = 1 ORDER BY sort_order ASC'
        );
        $featuredPosts = Database::all(
            "SELECT slug, title, excerpt, date_published, reading_minutes
             FROM blog_posts
             WHERE status = 'published'
             ORDER BY date_published DESC
             LIMIT 3"
        );

        $seo = (new SEO())
            ->title('Find a Lawyer Near You | Free Legal Help | LawMillion')
            ->description('Connect with verified U.S. attorneys in 15+ practice areas. Personal injury, employment, family law, immigration, bankruptcy & more. Free consultations nationwide.')
            ->canonical('/')
            ->ogType('website')
            ->breadcrumbs([['Home', '/']])
            ->addOrganization()
            ->addWebSite();

        view('home', compact('seo', 'practiceAreas', 'featuredPosts'));
    }
}
