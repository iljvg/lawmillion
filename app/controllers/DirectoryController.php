<?php
declare(strict_types=1);

final class DirectoryController
{
    /** GET /find-a-lawyer/ — searchable directory landing */
    public function index(): void
    {
        $q             = trim($_GET['q']     ?? '');
        $practiceSlug  = trim($_GET['area']  ?? '');
        $stateSlug     = trim($_GET['state'] ?? '');
        $citySlug      = trim($_GET['city']  ?? '');
        $page          = max(1, (int)($_GET['page'] ?? 1));
        $perPage       = 20;
        $offset        = ($page - 1) * $perPage;

        $where  = ['a.is_active = 1'];
        $params = [];

        if ($practiceSlug !== '') {
            $where[] = 'apa.practice_area_id = (SELECT id FROM practice_areas WHERE slug = ?)';
            $params[] = $practiceSlug;
        }
        if ($stateSlug !== '') {
            $where[] = 's.slug = ?';
            $params[] = $stateSlug;
        }
        if ($citySlug !== '') {
            $where[] = 'c.slug = ?';
            $params[] = $citySlug;
        }
        if ($q !== '') {
            $where[]  = '(CONCAT(a.first_name, " ", a.last_name) LIKE ? OR a.firm_name LIKE ? OR a.headline LIKE ?)';
            $like     = '%' . $q . '%';
            $params[] = $like; $params[] = $like; $params[] = $like;
        }

        $sql = "SELECT DISTINCT a.id, a.slug, a.first_name, a.last_name, a.middle_initial,
                       a.headline, a.photo_url, a.rating_avg, a.review_count, a.firm_name, a.is_verified,
                       s.slug AS state_slug, s.code AS state_code, s.name AS state_name,
                       c.slug AS city_slug, c.name AS city_name
                FROM attorneys a
                LEFT JOIN attorney_practice_areas apa ON apa.attorney_id = a.id
                LEFT JOIN us_states s ON s.id = a.state_id
                LEFT JOIN us_cities c ON c.id = a.city_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY a.is_verified DESC, a.rating_avg DESC, a.review_count DESC
                LIMIT {$perPage} OFFSET {$offset}";

        $attorneys = Database::all($sql, $params);

        $practiceAreas = Database::all(
            'SELECT slug, name FROM practice_areas WHERE is_active = 1 ORDER BY sort_order'
        );
        $states = Database::all('SELECT code, slug, name FROM us_states ORDER BY name');

        $seo = (new SEO())
            ->title('Find a Lawyer Near Me | Free Attorney Search | LawMillion')
            ->description('Search 50,000+ verified U.S. attorneys by practice area, state, and city. Free consultations. Personal injury, employment, family, immigration & more.')
            ->canonical('/find-a-lawyer/')
            ->breadcrumbs([
                ['Home', '/'],
                ['Find a Lawyer', '/find-a-lawyer/'],
            ])
            ->addOrganization()
            ->addWebSite();

        view('find-a-lawyer', compact('seo', 'attorneys', 'practiceAreas', 'states', 'q', 'practiceSlug', 'stateSlug', 'citySlug', 'page'));
    }

    /** POST /leads/submit — handles "Get Free Consultation" forms */
    public function submitLead(): void
    {
        if (!csrf_check($_POST[CSRF_TOKEN_NAME] ?? null)) {
            http_response_code(419);
            echo 'Security token invalid. Please refresh and try again.';
            return;
        }

        $name    = trim($_POST['full_name'] ?? '');
        $email   = trim($_POST['email']     ?? '');
        $phone   = trim($_POST['phone']     ?? '');
        $summary = trim($_POST['case_summary'] ?? '');
        $area    = trim($_POST['practice_area'] ?? '');
        $stateCd = trim($_POST['state']     ?? '');
        $zip     = trim($_POST['zip']       ?? '');
        $source  = $_SERVER['HTTP_REFERER']  ?? null;

        $errors = [];
        if ($name === '' || mb_strlen($name) > 120) $errors[] = 'Full name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';
        if ($summary === '' || mb_strlen($summary) < 20) $errors[] = 'Please provide at least 20 characters describing your case.';
        if ($zip !== '' && !preg_match('/^\d{5}(-\d{4})?$/', $zip)) $errors[] = 'ZIP must be a valid US ZIP (12345 or 12345-6789).';

        if ($errors) {
            http_response_code(422);
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'errors' => $errors]);
            return;
        }

        $stateId = null;
        if ($stateCd !== '') {
            $stateId = Database::scalar('SELECT id FROM us_states WHERE code = ? OR slug = ? LIMIT 1', [$stateCd, $stateCd]);
        }
        $paId = null;
        if ($area !== '') {
            $paId = Database::scalar('SELECT id FROM practice_areas WHERE slug = ? LIMIT 1', [$area]);
        }

        Database::exec(
            'INSERT INTO leads
             (practice_area_id, state_id, zip_code, full_name, email, phone, case_summary, source_page, ip_address, user_agent)
             VALUES (?,?,?,?,?,?,?,?,?,?)',
            [
                $paId, $stateId, $zip ?: null, $name, $email, $phone ?: null, $summary,
                $source, $_SERVER['REMOTE_ADDR'] ?? null, substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
            ]
        );

        header('Content-Type: application/json');
        echo json_encode([
            'ok'      => true,
            'message' => 'Thanks! A verified attorney will contact you within 24 hours.',
        ]);
    }
}
