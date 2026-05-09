<?php
declare(strict_types=1);

final class AttorneyPortalController
{
    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_name(SESSION_NAME);
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'secure'   => !APP_DEBUG,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    /** GET /for-attorneys/ — marketing landing (indexable) */
    public function landing(): void
    {
        $seo = (new SEO())
            ->title('List Your Law Firm | Get More Clients | LawMillion')
            ->description('Grow your U.S. law practice. Verified attorney profiles, qualified leads, and pay-per-lead pricing. Join 50,000+ attorneys on LawMillion.')
            ->canonical('/for-attorneys/')
            ->breadcrumbs([
                ['Home', '/'],
                ['For Attorneys', '/for-attorneys/'],
            ])
            ->addOrganization();

        view('portal/landing', compact('seo'));
    }

    /** GET /for-attorneys/login/ — noindex */
    public function loginForm(): void
    {
        $seo = (new SEO())
            ->title('Attorney Login | LawMillion')
            ->description('Login to your LawMillion attorney portal.')
            ->canonical('/for-attorneys/login/')
            ->noindex();

        $error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_error']);
        $csrf = csrf_token();
        view('portal/login', compact('seo', 'csrf', 'error'));
    }

    /** POST /for-attorneys/login/ */
    public function loginSubmit(): void
    {
        if (!csrf_check($_POST[CSRF_TOKEN_NAME] ?? null)) {
            $_SESSION['flash_error'] = 'Security token expired. Please try again.';
            redirect('/for-attorneys/login/');
        }

        $email = trim($_POST['email']    ?? '');
        $pass  = (string)($_POST['password'] ?? '');

        $user = Database::one(
            "SELECT * FROM users WHERE email = ? AND role = 'attorney' AND is_active = 1 LIMIT 1",
            [$email]
        );

        if (!$user || !password_verify($pass, $user['password_hash'])) {
            $_SESSION['flash_error'] = 'Invalid email or password.';
            redirect('/for-attorneys/login/');
        }

        // Rotate session ID (prevent fixation)
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['role']    = $user['role'];

        Database::exec('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$user['id']]);

        redirect('/for-attorneys/dashboard/');
    }

    /** POST /for-attorneys/logout/ */
    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        redirect('/');
    }

    /** GET /for-attorneys/create-profile/ — signup conversion page (indexable) */
    public function signupForm(): void
    {
        $seo = (new SEO())
            ->title('Create Attorney Profile | List Your Firm | LawMillion')
            ->description('Create your free attorney profile on LawMillion. Reach clients searching for legal help in your state and practice area.')
            ->canonical('/for-attorneys/create-profile/')
            ->breadcrumbs([
                ['Home', '/'],
                ['For Attorneys', '/for-attorneys/'],
                ['Create Profile', '/for-attorneys/create-profile/'],
            ])
            ->addOrganization();

        $states         = Database::all('SELECT code, slug, name FROM us_states ORDER BY name');
        $practiceAreas  = Database::all('SELECT slug, name FROM practice_areas WHERE is_active = 1 ORDER BY sort_order');
        $csrf           = csrf_token();

        view('portal/create-profile', compact('seo', 'states', 'practiceAreas', 'csrf'));
    }

    /** POST /for-attorneys/create-profile/ */
    public function signupSubmit(): void
    {
        if (!csrf_check($_POST[CSRF_TOKEN_NAME] ?? null)) {
            http_response_code(419);
            echo 'Security token invalid.';
            return;
        }

        $email   = trim($_POST['email']    ?? '');
        $pass    = (string)($_POST['password'] ?? '');
        $first   = trim($_POST['first_name'] ?? '');
        $last    = trim($_POST['last_name']  ?? '');
        $barNum  = trim($_POST['bar_number']  ?? '');
        $stateCd = trim($_POST['bar_state']   ?? '');

        $errors = [];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))    $errors[] = 'Valid email required.';
        if (strlen($pass) < 10)                             $errors[] = 'Password must be at least 10 characters.';
        if ($first === '' || $last === '')                  $errors[] = 'First and last name required.';
        if ($barNum === '')                                 $errors[] = 'Bar number required.';
        if ($stateCd === '')                                $errors[] = 'Bar state required.';
        if (Database::scalar('SELECT 1 FROM users WHERE email = ?', [$email])) $errors[] = 'Email already registered.';

        if ($errors) {
            http_response_code(422);
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'errors' => $errors]);
            return;
        }

        $hash = password_hash($pass, PASSWORD_ALGO, ['cost' => PASSWORD_COST]);
        Database::exec(
            "INSERT INTO users (email, password_hash, role) VALUES (?, ?, 'attorney')",
            [$email, $hash]
        );
        $userId = Database::lastInsertId();

        $stateId = Database::scalar('SELECT id FROM us_states WHERE code = ? OR slug = ? LIMIT 1', [$stateCd, $stateCd]);
        $slug    = slugify($first . ' ' . $last);

        // Ensure unique slug
        $i = 1;
        $base = $slug;
        while (Database::scalar('SELECT 1 FROM attorneys WHERE slug = ?', [$slug])) {
            $slug = $base . '-' . (++$i);
        }

        Database::exec(
            "INSERT INTO attorneys (user_id, slug, first_name, last_name, bar_number, bar_state_id, is_active, is_verified)
             VALUES (?,?,?,?,?,?,1,0)",
            [$userId, $slug, $first, $last, $barNum, $stateId]
        );

        header('Content-Type: application/json');
        echo json_encode([
            'ok'       => true,
            'message'  => 'Profile created. Verification can take up to 48 hours.',
            'redirect' => '/for-attorneys/login/',
        ]);
    }

    /** GET /for-attorneys/dashboard/ — auth-gated, noindex */
    public function dashboard(): void
    {
        if (empty($_SESSION['user_id'])) {
            redirect('/for-attorneys/login/');
        }

        $attorney = $this->loadAttorneyForUser((int)$_SESSION['user_id']);
        $this->resolveExpiredLeads();
        $state    = $this->buildDashboardState($attorney);

        $seo = (new SEO())
            ->title('Dashboard | LawMillion')
            ->description('Attorney dashboard.')
            ->canonical('/for-attorneys/dashboard/')
            ->noindex();

        $csrf = csrf_token();
        view('portal/dashboard', compact('seo', 'attorney', 'state', 'csrf'));
    }

    /**
     * POST /for-attorneys/dashboard/place-bid
     * Body: lead_id (external_id), amount, csrf
     * Returns: full updated dashboard state as JSON.
     */
    public function placeBid(): void
    {
        header('Content-Type: application/json');

        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Not signed in.']);
            return;
        }
        if (!csrf_check($_POST[CSRF_TOKEN_NAME] ?? null)) {
            http_response_code(419);
            echo json_encode(['ok' => false, 'error' => 'Security token expired. Please refresh.']);
            return;
        }

        $attorney = $this->loadAttorneyForUser((int)$_SESSION['user_id']);
        if (!$attorney) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Attorney profile required to bid.']);
            return;
        }

        $extId  = trim((string)($_POST['lead_id'] ?? ''));
        $amount = (int)($_POST['amount'] ?? 0);

        if ($extId === '' || $amount < 5) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Minimum bid is $5.']);
            return;
        }

        // Resolve any expired auctions first so we don't bid on a closed lead
        $this->resolveExpiredLeads();

        $lead = Database::one(
            'SELECT * FROM marketplace_leads WHERE external_id = ? LIMIT 1',
            [$extId]
        );
        if (!$lead) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Lead not found.']);
            return;
        }
        if ($lead['status'] !== 'open') {
            http_response_code(409);
            echo json_encode(['ok' => false, 'error' => 'This lead is already closed.']);
            return;
        }

        $topBid = (float)Database::scalar(
            'SELECT COALESCE(MAX(amount), 0) FROM marketplace_bids WHERE lead_id = ?',
            [(int)$lead['id']]
        );
        $minBid = max(5, (int)$topBid + 1);
        if ($amount < $minBid) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => "Minimum bid is \${$minBid}."]);
            return;
        }

        // Insert bid + reset timer to 30 minutes
        Database::pdo()->beginTransaction();
        try {
            Database::exec(
                'INSERT INTO marketplace_bids (lead_id, attorney_id, amount) VALUES (?, ?, ?)',
                [(int)$lead['id'], (int)$attorney['id'], $amount]
            );
            Database::exec(
                'UPDATE marketplace_leads SET expires_at = DATE_ADD(NOW(), INTERVAL 30 MINUTE) WHERE id = ?',
                [(int)$lead['id']]
            );
            Database::pdo()->commit();
        } catch (Throwable $e) {
            Database::pdo()->rollBack();
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'Could not record your bid. Try again.']);
            return;
        }

        $state = $this->buildDashboardState($this->loadAttorneyForUser((int)$_SESSION['user_id']));
        echo json_encode([
            'ok'      => true,
            'message' => "Bid of \${$amount} placed.",
            'state'   => $state,
        ]);
    }

    /**
     * GET /for-attorneys/dashboard/state
     * Polling endpoint — returns current dashboard state as JSON.
     */
    public function dashboardState(): void
    {
        header('Content-Type: application/json');

        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Not signed in.']);
            return;
        }
        $attorney = $this->loadAttorneyForUser((int)$_SESSION['user_id']);
        $this->resolveExpiredLeads();
        echo json_encode(['ok' => true, 'state' => $this->buildDashboardState($attorney)]);
    }

    // -------------------------------------------------------------------------
    // PRIVATE HELPERS
    // -------------------------------------------------------------------------

    /** Load the attorney record + city/state names for a given user_id. */
    private function loadAttorneyForUser(int $userId): ?array
    {
        return Database::one(
            'SELECT a.*, s.name AS state_name, c.name AS city_name
             FROM attorneys a
             LEFT JOIN us_states s ON s.id = a.state_id
             LEFT JOIN us_cities c ON c.id = a.city_id
             WHERE a.user_id = ? LIMIT 1',
            [$userId]
        );
    }

    /**
     * Build the JSON state shape consumed by the dashboard view.
     * Returns ['me' => {...}, 'leads' => [...]] matching the front-end's
     * existing ME / LEADS data contract.
     */
    private function buildDashboardState(?array $attorney): array
    {
        $myId = $attorney ? (int)$attorney['id'] : 0;

        // ME
        $me = [
            'attorneyId' => $myId,
            'name'       => $attorney
                ? trim($attorney['first_name'] . ' ' . $attorney['last_name'])
                : 'Attorney',
            'firstName'  => $attorney ? $attorney['first_name'] : 'Attorney',
            'initials'   => $attorney
                ? initials($attorney['first_name'], $attorney['last_name'])
                : '??',
            'balance'    => $attorney ? (float)$attorney['wallet_balance'] : 0.0,
            'plan'       => $attorney ? ucfirst($attorney['plan']) : 'Basic',
        ];

        // LEADS — pull all non-deleted leads + practice area name
        $rows = Database::all(
            "SELECT ml.*, pa.name AS area_name
             FROM marketplace_leads ml
             JOIN practice_areas pa ON pa.id = ml.practice_area_id
             ORDER BY
               CASE ml.status WHEN 'open' THEN 0 WHEN 'won' THEN 1 ELSE 2 END,
               ml.expires_at ASC"
        );

        // Pre-fetch all bids for these leads in one query (avoid N+1)
        $leadIds = array_column($rows, 'id');
        $bidsByLead = [];
        if ($leadIds) {
            $place = implode(',', array_fill(0, count($leadIds), '?'));
            $bidRows = Database::all(
                "SELECT b.id, b.lead_id, b.attorney_id, b.amount, b.created_at,
                        a.first_name, a.last_name
                 FROM marketplace_bids b
                 JOIN attorneys a ON a.id = b.attorney_id
                 WHERE b.lead_id IN ($place)
                 ORDER BY b.amount DESC, b.created_at ASC",
                $leadIds
            );
            foreach ($bidRows as $b) {
                $bidsByLead[(int)$b['lead_id']][] = $b;
            }
        }

        // Resolve "won by" attorney names in one query
        $wonByIds = array_filter(array_unique(array_column($rows, 'won_by_attorney_id')));
        $wonByNames = [];
        if ($wonByIds) {
            $place = implode(',', array_fill(0, count($wonByIds), '?'));
            foreach (Database::all(
                "SELECT id, first_name, last_name FROM attorneys WHERE id IN ($place)",
                array_values($wonByIds)
            ) as $a) {
                $wonByNames[(int)$a['id']] = trim($a['first_name'] . ' ' . $a['last_name']);
            }
        }

        $leads = [];
        foreach ($rows as $r) {
            $leadId  = (int)$r['id'];
            $iWon    = ($myId && (int)$r['won_by_attorney_id'] === $myId);
            $expires = strtotime($r['expires_at']);
            $secs    = max(0, $expires - time());

            $bids = [];
            foreach ($bidsByLead[$leadId] ?? [] as $b) {
                $isMine = ($myId && (int)$b['attorney_id'] === $myId);
                $bids[] = [
                    'id' => 'b' . $b['id'],
                    'n'  => trim($b['first_name'] . ' ' . $b['last_name']),
                    'i'  => initials($b['first_name'], $b['last_name']),
                    'a'  => (int)$b['amount'],
                    't'  => time_ago($b['created_at']),
                    'm'  => $isMine,
                ];
            }

            $leads[] = [
                'id'      => $r['external_id'],
                'title'   => $r['title'],
                'loc'     => trim(($r['city'] ?? '') . ', ' . ($r['state_code'] ?? ''), ' ,'),
                'area'    => $r['area_name'],
                'sub'     => $r['sub_category'] ?? '',
                'date'    => (new DateTimeImmutable($r['created_at']))->format('M j'),
                'urgency' => $r['urgency'],
                'budget'  => $r['budget_label'] ?? '',
                'value'   => $r['estimated_value'] ?? '',
                'desc'    => $r['description'] ?? '',
                'facts'   => $r['facts_json'] ? (json_decode($r['facts_json'], true) ?: []) : [],
                'bids'    => $bids,
                'secs'    => $secs,
                'status'  => $r['status'],
                'wonBy'   => $r['won_by_attorney_id']
                    ? ($wonByNames[(int)$r['won_by_attorney_id']] ?? null)
                    : null,
                // Contact details only revealed if I won this lead — never sent
                // to the browser otherwise (security).
                'cN'      => $iWon ? ($r['client_name']  ?? '') : '',
                'cP'      => $iWon ? ($r['client_phone'] ?? '') : '',
                'cE'      => $iWon ? ($r['client_email'] ?? '') : '',
            ];
        }

        return ['me' => $me, 'leads' => $leads];
    }

    /**
     * Resolve any open auctions whose timer has expired. Top bidder wins
     * (status=won, contact unlocked, wallet debited). No bids → status=closed.
     * Idempotent — safe to call on every request.
     */
    private function resolveExpiredLeads(): void
    {
        $expired = Database::all(
            "SELECT id FROM marketplace_leads
             WHERE status = 'open' AND expires_at <= NOW()"
        );
        foreach ($expired as $row) {
            $this->resolveLead((int)$row['id']);
        }
    }

    private function resolveLead(int $leadId): void
    {
        Database::pdo()->beginTransaction();
        try {
            $top = Database::one(
                'SELECT attorney_id, amount FROM marketplace_bids
                 WHERE lead_id = ? ORDER BY amount DESC, created_at ASC LIMIT 1',
                [$leadId]
            );
            if ($top) {
                $affected = Database::exec(
                    "UPDATE marketplace_leads
                     SET status='won', won_by_attorney_id=?
                     WHERE id=? AND status='open'",
                    [(int)$top['attorney_id'], $leadId]
                );
                if ($affected) {
                    // Charge the winner's wallet (clamp at zero)
                    Database::exec(
                        'UPDATE attorneys
                         SET wallet_balance = GREATEST(wallet_balance - ?, 0)
                         WHERE id = ?',
                        [(float)$top['amount'], (int)$top['attorney_id']]
                    );
                }
            } else {
                Database::exec(
                    "UPDATE marketplace_leads SET status='closed'
                     WHERE id=? AND status='open'",
                    [$leadId]
                );
            }
            Database::pdo()->commit();
        } catch (Throwable $e) {
            Database::pdo()->rollBack();
            error_log('[LawMillion] resolveLead failed: ' . $e->getMessage());
        }
    }
}
