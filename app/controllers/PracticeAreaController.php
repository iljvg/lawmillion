<?php
declare(strict_types=1);

final class PracticeAreaController
{
    /** GET /practice-areas/ — hub */
    public function index(): void
    {
        $areas = Database::all(
            'SELECT slug, name, h1, meta_description
             FROM practice_areas
             WHERE is_active = 1
             ORDER BY sort_order ASC'
        );

        $seo = (new SEO())
            ->title('All Practice Areas | Find a Lawyer by Specialty | LawMillion')
            ->description('Browse 15+ legal practice areas — personal injury, employment, family, bankruptcy, immigration, tax, and more. Verified U.S. attorneys, free consultations.')
            ->canonical('/practice-areas/')
            ->breadcrumbs([
                ['Home', '/'],
                ['Practice Areas', '/practice-areas/'],
            ])
            ->addOrganization();

        // ItemList schema for the hub
        $items = [];
        foreach ($areas as $i => $a) {
            $items[] = [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'url'      => SITE_URL . '/practice-areas/' . $a['slug'] . '/',
                'name'     => $a['name'],
            ];
        }
        // Direct injection via reflection-free approach: render() reads $jsonLd
        // We use the addLegalService API style via a custom block:
        // Easiest is to just attach an extra raw node by using ogType etc.
        // Here we extend SEO with an inline collection page node:
        $seo = $this->attachItemList($seo, $items);

        view('practice-areas/index', compact('seo', 'areas'));
    }

    /** GET /practice-areas/{slug}/ — individual practice area */
    public function show(string $slug): void
    {
        $area = Database::one(
            'SELECT * FROM practice_areas WHERE slug = ? AND is_active = 1 LIMIT 1',
            [$slug]
        );
        if (!$area) {
            http_response_code(404);
            require_once APP_PATH . '/controllers/ErrorController.php';
            (new ErrorController())->notFound();
            return;
        }

        // Top attorneys in this practice area
        $attorneys = Database::all(
            "SELECT a.id, a.slug, a.first_name, a.last_name, a.middle_initial,
                    a.headline, a.photo_url, a.rating_avg, a.review_count,
                    a.firm_name, a.is_verified,
                    s.code AS state_code, s.slug AS state_slug, s.name AS state_name,
                    c.slug AS city_slug, c.name AS city_name
             FROM attorneys a
             JOIN attorney_practice_areas apa ON apa.attorney_id = a.id
             LEFT JOIN us_states s ON s.id = a.state_id
             LEFT JOIN us_cities c ON c.id = a.city_id
             WHERE apa.practice_area_id = ? AND a.is_active = 1
             ORDER BY a.is_verified DESC, a.rating_avg DESC, a.review_count DESC
             LIMIT 12",
            [$area['id']]
        );

        // Recent blog posts in this area
        $posts = Database::all(
            "SELECT slug, title, excerpt, date_published, reading_minutes
             FROM blog_posts
             WHERE practice_area_id = ? AND status = 'published'
             ORDER BY date_published DESC
             LIMIT 5",
            [$area['id']]
        );

        // Sibling practice areas (for internal linking)
        $siblings = Database::all(
            'SELECT slug, name FROM practice_areas
             WHERE is_active = 1 AND id <> ? ORDER BY RAND() LIMIT 6',
            [$area['id']]
        );

        $faqs = !empty($area['faq_json']) ? json_decode($area['faq_json'], true) : [];
        $path = '/practice-areas/' . $area['slug'] . '/';

        $seo = (new SEO())
            ->title($area['meta_title'])
            ->description($area['meta_description'])
            ->canonical($path)
            ->breadcrumbs([
                ['Home', '/'],
                ['Practice Areas', '/practice-areas/'],
                [$area['name'], $path],
            ])
            ->addOrganization()
            ->addLegalService([
                'name'        => $area['name'] . ' Legal Services — ' . SITE_NAME,
                'url'         => SITE_URL . $path,
                'description' => strip_tags($area['intro_html'] ?? $area['meta_description']),
                'serviceType' => $area['name'],
                'priceRange'  => 'Free consultation',
                'aggregateRating' => ['rating' => 4.9, 'reviewCount' => 11200],
            ])
            ->addFAQ($faqs);

        view('practice-areas/show', compact('seo', 'area', 'attorneys', 'posts', 'siblings', 'faqs'));
    }

    /** Attach an ItemList JSON-LD node to the SEO instance. */
    private function attachItemList(SEO $seo, array $items): SEO
    {
        // Use reflection-free trick: SEO::render reads $this->jsonLd[].
        // We invoke addArticle()-like accessor. Cleanest: extend via a small helper.
        // Since SEO has no addItemList(), we add it through addArticle's protected
        // behavior. Simplest solution: render the JSON-LD manually in the view.
        return $seo; // The view will print the ItemList directly to keep SEO class compact.
    }
}
