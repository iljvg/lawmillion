<?php
declare(strict_types=1);

final class ErrorController
{
    public function notFound(): void
    {
        http_response_code(404);
        $seo = (new SEO())
            ->title('Page Not Found | LawMillion')
            ->description('The page you requested could not be found.')
            ->canonical('/404')
            ->noindex();
        view('errors/404', compact('seo'));
    }
}
