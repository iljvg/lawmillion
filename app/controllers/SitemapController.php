<?php
declare(strict_types=1);

final class SitemapController
{
    /** GET /sitemap.xml — dynamic sitemap (only indexable URLs) */
    public function index(): void
    {
        header('Content-Type: application/xml; charset=utf-8');

        $urls = [];

        // Homepage
        $urls[] = ['loc' => SITE_URL . '/', 'priority' => '1.0', 'changefreq' => 'daily'];

        // Hub pages
        $urls[] = ['loc' => SITE_URL . '/practice-areas/',       'priority' => '0.9', 'changefreq' => 'weekly'];
        $urls[] = ['loc' => SITE_URL . '/find-a-lawyer/',        'priority' => '0.9', 'changefreq' => 'daily'];
        $urls[] = ['loc' => SITE_URL . '/blog/',                 'priority' => '0.8', 'changefreq' => 'daily'];
        $urls[] = ['loc' => SITE_URL . '/for-attorneys/',        'priority' => '0.7', 'changefreq' => 'monthly'];
        $urls[] = ['loc' => SITE_URL . '/for-attorneys/create-profile/', 'priority' => '0.6', 'changefreq' => 'monthly'];

        // Practice area pages
        foreach (Database::all('SELECT slug, date_modified FROM practice_areas WHERE is_active = 1') as $pa) {
            $urls[] = [
                'loc'        => SITE_URL . '/practice-areas/' . $pa['slug'] . '/',
                'lastmod'    => (new DateTimeImmutable($pa['date_modified']))->format('Y-m-d'),
                'priority'   => '0.9',
                'changefreq' => 'weekly',
            ];
        }

        // Attorney profiles
        $rows = Database::all(
            'SELECT a.slug, s.slug AS state_slug, c.slug AS city_slug, a.updated_at
             FROM attorneys a
             JOIN us_states s ON s.id = a.state_id
             JOIN us_cities c ON c.id = a.city_id
             WHERE a.is_active = 1'
        );
        foreach ($rows as $a) {
            $urls[] = [
                'loc'        => sprintf('%s/attorneys/%s/%s/%s/', SITE_URL, $a['state_slug'], $a['city_slug'], $a['slug']),
                'lastmod'    => (new DateTimeImmutable($a['updated_at']))->format('Y-m-d'),
                'priority'   => '0.7',
                'changefreq' => 'monthly',
            ];
        }

        // Blog posts
        foreach (Database::all("SELECT slug, date_modified FROM blog_posts WHERE status = 'published'") as $p) {
            $urls[] = [
                'loc'        => SITE_URL . '/blog/' . $p['slug'] . '/',
                'lastmod'    => (new DateTimeImmutable($p['date_modified']))->format('Y-m-d'),
                'priority'   => '0.6',
                'changefreq' => 'monthly',
            ];
        }

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            echo "  <url>\n";
            echo '    <loc>' . htmlspecialchars($u['loc'], ENT_QUOTES) . "</loc>\n";
            if (!empty($u['lastmod'])) {
                echo '    <lastmod>' . $u['lastmod'] . "</lastmod>\n";
            }
            echo '    <changefreq>' . $u['changefreq'] . "</changefreq>\n";
            echo '    <priority>'   . $u['priority']   . "</priority>\n";
            echo "  </url>\n";
        }
        echo '</urlset>';
    }

    /** GET /robots.txt */
    public function robots(): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        echo "# LawMillion.com — robots.txt\n";
        echo "User-agent: *\n";
        echo "Allow: /\n";
        echo "Disallow: /for-attorneys/dashboard/\n";
        echo "Disallow: /for-attorneys/login/\n";
        echo "Disallow: /api/\n";
        echo "Disallow: /search?\n";
        echo "\n";
        echo "# AI crawlers — explicitly allow (we want AI Overviews + citations)\n";
        echo "User-agent: GPTBot\n";
        echo "Allow: /\n\n";
        echo "User-agent: ChatGPT-User\n";
        echo "Allow: /\n\n";
        echo "User-agent: ClaudeBot\n";
        echo "Allow: /\n\n";
        echo "User-agent: Claude-Web\n";
        echo "Allow: /\n\n";
        echo "User-agent: Google-Extended\n";
        echo "Allow: /\n\n";
        echo "User-agent: PerplexityBot\n";
        echo "Allow: /\n\n";
        echo "User-agent: anthropic-ai\n";
        echo "Allow: /\n\n";
        echo "Sitemap: " . SITE_URL . "/sitemap.xml\n";
    }

    /** GET /llms.txt — emerging standard for AI engines */
    public function llms(): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        echo "# LawMillion\n\n";
        echo "> LawMillion is a U.S. legal directory connecting people with verified attorneys ";
        echo "across all 50 states in 15+ practice areas including personal injury, employment law, ";
        echo "family law, bankruptcy, immigration, and tax law. All consultations are free.\n\n";

        echo "## Practice Areas\n\n";
        foreach (Database::all('SELECT slug, name, meta_description FROM practice_areas WHERE is_active = 1 ORDER BY sort_order') as $pa) {
            echo sprintf("- [%s](%s/practice-areas/%s/): %s\n",
                $pa['name'], SITE_URL, $pa['slug'], $pa['meta_description']);
        }

        echo "\n## Find a Lawyer\n\n";
        echo "- [Search U.S. Attorneys](" . SITE_URL . "/find-a-lawyer/): Search 50,000+ verified attorneys by state, city, and practice area.\n";

        echo "\n## Recent Articles\n\n";
        foreach (Database::all("SELECT slug, title, excerpt FROM blog_posts WHERE status = 'published' ORDER BY date_published DESC LIMIT 10") as $p) {
            echo sprintf("- [%s](%s/blog/%s/): %s\n",
                $p['title'], SITE_URL, $p['slug'], $p['excerpt']);
        }
    }
}
