<?php
declare(strict_types=1);

final class AttorneyController
{
    /** GET /attorneys/{state_slug}/{city_slug}/{attorney_slug}/ */
    public function show(string $stateSlug, string $citySlug, string $attorneySlug): void
    {
        $row = Database::one(
            "SELECT a.*,
                    s.code AS state_code, s.slug AS state_slug, s.name AS state_name,
                    c.slug AS city_slug, c.name AS city_name
             FROM attorneys a
             LEFT JOIN us_states s ON s.id = a.state_id
             LEFT JOIN us_cities c ON c.id = a.city_id
             WHERE a.slug = ? AND a.is_active = 1
               AND s.slug = ? AND c.slug = ?
             LIMIT 1",
            [$attorneySlug, $stateSlug, $citySlug]
        );

        if (!$row) {
            http_response_code(404);
            require_once APP_PATH . '/controllers/ErrorController.php';
            (new ErrorController())->notFound();
            return;
        }

        // Practice areas
        $practiceAreas = Database::all(
            'SELECT pa.slug, pa.name, apa.is_primary
             FROM attorney_practice_areas apa
             JOIN practice_areas pa ON pa.id = apa.practice_area_id
             WHERE apa.attorney_id = ?
             ORDER BY apa.is_primary DESC, pa.sort_order ASC',
            [$row['id']]
        );

        // Reviews
        $reviews = Database::all(
            'SELECT reviewer_name, rating, title, body, created_at
             FROM attorney_reviews
             WHERE attorney_id = ? AND is_published = 1
             ORDER BY created_at DESC LIMIT 20',
            [$row['id']]
        );

        $fullName = trim($row['first_name'] . ' '
                       . ($row['middle_initial'] ? $row['middle_initial'] . ' ' : '')
                       . $row['last_name']);

        $path = sprintf('/attorneys/%s/%s/%s/',
            $row['state_slug'], $row['city_slug'], $row['slug']);

        $seo = (new SEO())
            ->title($row['meta_title']  ?: $fullName . ' | ' . ($row['headline'] ?? 'Attorney') . ' ' . $row['city_name'] . ' ' . $row['state_code'])
            ->description($row['meta_description'] ?: substr(strip_tags($row['bio_html'] ?? ''), 0, 240))
            ->canonical($path)
            ->ogType('profile')
            ->breadcrumbs([
                ['Home', '/'],
                ['Find a Lawyer', '/find-a-lawyer/'],
                [$row['state_name'], '/attorneys/' . $row['state_slug'] . '/'],
                [$row['city_name'],  '/attorneys/' . $row['state_slug'] . '/' . $row['city_slug'] . '/'],
                [$fullName,          $path],
            ])
            ->addOrganization()
            ->addAttorney([
                'name'      => $fullName,
                'url'       => SITE_URL . $path,
                'jobTitle'  => $row['headline'] ?: 'Attorney',
                'image'     => $row['photo_url'],
                'telephone' => $row['phone'],
                'email'     => $row['email_public'],
                'address'   => [
                    'streetAddress' => $row['street_address'],
                    'city'          => $row['city_name'],
                    'state'         => $row['state_code'],
                    'zip'           => $row['zip_code'],
                ],
                'rating'    => $row['review_count'] > 0 ? [
                    'value' => $row['rating_avg'],
                    'count' => $row['review_count'],
                ] : null,
                'knowsAbout' => array_map(fn($p) => $p['name'], $practiceAreas),
            ]);

        view('attorneys/show', compact('seo', 'row', 'practiceAreas', 'reviews', 'fullName', 'path'));
    }
}
