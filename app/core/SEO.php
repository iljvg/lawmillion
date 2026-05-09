<?php
declare(strict_types=1);

/**
 * SEO + AEO (AI Engine Optimization) helper.
 *
 * Builds:
 *   - Meta tags (title, description, robots, canonical, OG, Twitter Card)
 *   - Schema.org JSON-LD (Organization, WebSite, BreadcrumbList, LegalService,
 *     Attorney, Article, FAQPage, AggregateRating)
 *   - hreflang (en-US only — US market)
 *
 * Usage in a controller:
 *   $seo = (new SEO())
 *       ->title('Personal Injury Lawyers Near You')
 *       ->description('...')
 *       ->canonical('/practice-areas/personal-injury/')
 *       ->breadcrumbs([['Home','/'], ['Practice Areas','/practice-areas/'], ['Personal Injury','/practice-areas/personal-injury/']])
 *       ->addLegalService([...])
 *       ->addFAQ([['q'=>'?','a'=>'!'], ...]);
 */
final class SEO
{
    private string $title       = '';
    private string $description = '';
    private string $canonical   = '';
    private string $robots      = 'index,follow,max-snippet:-1,max-image-preview:large,max-video-preview:-1';
    private string $ogType      = 'website';
    private string $ogImage     = '';
    private array  $jsonLd      = [];
    private array  $breadcrumbs = [];

    /** -------- Setters -------- */

    public function title(string $t): self
    {
        $this->title = $t;
        return $this;
    }

    public function description(string $d): self
    {
        $this->description = $d;
        return $this;
    }

    public function canonical(string $path): self
    {
        // Always absolute, https, no trailing query string
        $this->canonical = SITE_URL . $path;
        return $this;
    }

    public function robots(string $r): self
    {
        $this->robots = $r;
        return $this;
    }

    public function noindex(): self
    {
        $this->robots = 'noindex,follow';
        return $this;
    }

    public function ogType(string $t): self
    {
        $this->ogType = $t;
        return $this;
    }

    public function ogImage(string $url): self
    {
        $this->ogImage = $url;
        return $this;
    }

    public function breadcrumbs(array $items): self
    {
        $this->breadcrumbs = $items;

        $list = [];
        foreach ($items as $i => [$name, $path]) {
            $list[] = [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'name'     => $name,
                'item'     => SITE_URL . $path,
            ];
        }
        $this->jsonLd[] = [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $list,
        ];
        return $this;
    }

    /** -------- Schema.org generators -------- */

    public function addOrganization(): self
    {
        $this->jsonLd[] = [
            '@context'  => 'https://schema.org',
            '@type'     => 'Organization',
            '@id'       => SITE_URL . '#organization',
            'name'      => SITE_NAME,
            'url'       => SITE_URL,
            'logo'      => SITE_URL . '/assets/images/logo.png',
            'telephone' => SITE_PHONE,
            'email'     => SITE_EMAIL,
            'areaServed'=> ['@type' => 'Country', 'name' => 'United States'],
            'sameAs'    => [
                'https://www.facebook.com/lawmillion',
                'https://twitter.com/lawmillion',
                'https://www.linkedin.com/company/lawmillion',
            ],
        ];
        return $this;
    }

    public function addWebSite(): self
    {
        $this->jsonLd[] = [
            '@context'        => 'https://schema.org',
            '@type'           => 'WebSite',
            '@id'             => SITE_URL . '#website',
            'url'             => SITE_URL,
            'name'            => SITE_NAME,
            'inLanguage'      => SITE_LANGUAGE,
            'potentialAction' => [
                '@type'       => 'SearchAction',
                'target'      => SITE_URL . '/find-a-lawyer/?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ];
        return $this;
    }

    public function addLegalService(array $data): self
    {
        // Required keys: name, url, description, serviceType
        // Optional: aggregateRating ['rating'=>4.9,'reviewCount'=>11200], offers (array of strings)
        $node = [
            '@context'    => 'https://schema.org',
            '@type'       => ['LegalService', 'LocalBusiness'],
            '@id'         => $data['url'] . '#service',
            'name'        => $data['name'],
            'url'         => $data['url'],
            'description' => $data['description'],
            'serviceType' => $data['serviceType'],
            'telephone'   => SITE_PHONE,
            'priceRange'  => $data['priceRange'] ?? 'Free consultation',
            'areaServed'  => ['@type' => 'Country', 'name' => 'United States'],
        ];
        if (!empty($data['aggregateRating'])) {
            $node['aggregateRating'] = [
                '@type'       => 'AggregateRating',
                'ratingValue' => (string) $data['aggregateRating']['rating'],
                'reviewCount' => (string) $data['aggregateRating']['reviewCount'],
                'bestRating'  => '5',
                'worstRating' => '1',
            ];
        }
        if (!empty($data['offers']) && is_array($data['offers'])) {
            $node['hasOfferCatalog'] = [
                '@type'           => 'OfferCatalog',
                'name'            => $data['name'] . ' Services',
                'itemListElement' => array_map(fn($o) => [
                    '@type'      => 'Offer',
                    'itemOffered' => ['@type' => 'Service', 'name' => $o],
                ], $data['offers']),
            ];
        }
        $this->jsonLd[] = $node;
        return $this;
    }

    public function addAttorney(array $a): self
    {
        // Required: name, url, jobTitle, image, address[streetAddress,city,state,zip], telephone
        $node = [
            '@context'  => 'https://schema.org',
            '@type'     => ['Attorney', 'LegalService'],
            '@id'       => $a['url'] . '#attorney',
            'name'      => $a['name'],
            'url'       => $a['url'],
            'jobTitle'  => $a['jobTitle'] ?? 'Attorney',
            'image'     => $a['image'] ?? null,
            'telephone' => $a['telephone'] ?? null,
            'email'     => $a['email'] ?? null,
            'priceRange'=> $a['priceRange'] ?? 'Free consultation',
        ];
        if (!empty($a['address'])) {
            $node['address'] = [
                '@type'           => 'PostalAddress',
                'streetAddress'   => $a['address']['streetAddress'] ?? null,
                'addressLocality' => $a['address']['city'] ?? null,
                'addressRegion'   => $a['address']['state'] ?? null,
                'postalCode'      => $a['address']['zip'] ?? null,
                'addressCountry'  => 'US',
            ];
        }
        if (!empty($a['rating'])) {
            $node['aggregateRating'] = [
                '@type'       => 'AggregateRating',
                'ratingValue' => (string) $a['rating']['value'],
                'reviewCount' => (string) $a['rating']['count'],
                'bestRating'  => '5',
                'worstRating' => '1',
            ];
        }
        if (!empty($a['knowsAbout'])) {
            $node['knowsAbout'] = $a['knowsAbout']; // array of practice area names
        }
        // Filter null fields for cleaner JSON-LD
        $this->jsonLd[] = array_filter($node, fn($v) => $v !== null);
        return $this;
    }

    public function addArticle(array $a): self
    {
        // Required: headline, url, datePublished, dateModified, author, image
        $this->jsonLd[] = [
            '@context'         => 'https://schema.org',
            '@type'            => ['Article', 'LegalArticle'],
            'headline'         => $a['headline'],
            'image'            => $a['image'] ?? null,
            'datePublished'    => $a['datePublished'],
            'dateModified'     => $a['dateModified'] ?? $a['datePublished'],
            'author'           => [
                '@type' => 'Person',
                'name'  => $a['author'] ?? 'LawMillion Editorial Team',
                'url'   => $a['authorUrl'] ?? SITE_URL . '/about/',
            ],
            'publisher'        => [
                '@type' => 'Organization',
                'name'  => SITE_NAME,
                'logo'  => ['@type' => 'ImageObject', 'url' => SITE_URL . '/assets/images/logo.png'],
            ],
            'mainEntityOfPage' => $a['url'],
            'description'      => $a['description'] ?? null,
            'inLanguage'       => SITE_LANGUAGE,
        ];
        return $this;
    }

    public function addFAQ(array $faqs): self
    {
        if (empty($faqs)) {
            return $this;
        }
        $entities = array_map(fn($f) => [
            '@type'          => 'Question',
            'name'           => $f['q'],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']],
        ], $faqs);

        $this->jsonLd[] = [
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'mainEntity' => $entities,
        ];
        return $this;
    }

    /** -------- Render (call inside <head>) -------- */

    public function render(): string
    {
        $title = e($this->title);
        $desc  = e($this->description);
        $canon = e($this->canonical);
        $robots= e($this->robots);
        $ogImg = e($this->ogImage ?: SITE_URL . '/assets/images/og-default.jpg');
        $ogTyp = e($this->ogType);
        $lang  = e(SITE_LANGUAGE);

        $html  = "<title>{$title}</title>\n";
        $html .= "<meta name=\"description\" content=\"{$desc}\">\n";
        $html .= "<meta name=\"robots\" content=\"{$robots}\">\n";
        $html .= "<meta name=\"author\" content=\"" . e(SITE_NAME) . " Editorial Team\">\n";
        $html .= "<meta name=\"geo.region\" content=\"US\">\n";
        $html .= "<meta http-equiv=\"content-language\" content=\"en-US\">\n";

        if ($canon) {
            $html .= "<link rel=\"canonical\" href=\"{$canon}\">\n";
            $html .= "<link rel=\"alternate\" hreflang=\"en-US\" href=\"{$canon}\">\n";
            $html .= "<link rel=\"alternate\" hreflang=\"x-default\" href=\"{$canon}\">\n";
        }

        // Open Graph
        $html .= "<meta property=\"og:type\" content=\"{$ogTyp}\">\n";
        $html .= "<meta property=\"og:url\" content=\"{$canon}\">\n";
        $html .= "<meta property=\"og:title\" content=\"{$title}\">\n";
        $html .= "<meta property=\"og:description\" content=\"{$desc}\">\n";
        $html .= "<meta property=\"og:image\" content=\"{$ogImg}\">\n";
        $html .= "<meta property=\"og:site_name\" content=\"" . e(SITE_NAME) . "\">\n";
        $html .= "<meta property=\"og:locale\" content=\"en_US\">\n";

        // Twitter
        $html .= "<meta name=\"twitter:card\" content=\"summary_large_image\">\n";
        $html .= "<meta name=\"twitter:title\" content=\"{$title}\">\n";
        $html .= "<meta name=\"twitter:description\" content=\"{$desc}\">\n";
        $html .= "<meta name=\"twitter:image\" content=\"{$ogImg}\">\n";

        // JSON-LD
        foreach ($this->jsonLd as $node) {
            $json = json_encode($node, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $html .= "<script type=\"application/ld+json\">{$json}</script>\n";
        }
        return $html;
    }
}
