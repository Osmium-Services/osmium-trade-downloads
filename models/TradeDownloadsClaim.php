<?php

declare(strict_types=1);

namespace Osmium\Services\TradeDownloads\Models;

/**
 * Answers core's `page.claim` hook for the Trade Downloads page. The theme supplies the view; the claim hands over the
 * data. A claim cannot redirect, so a visitor who is not logged in is shown the view with loggedIn false and the
 * login address to link to.
 *
 * View: trade/views/downloads.phtml (relative to the site's module path), data $tradeDownloads:
 *   loggedIn   bool; false means show a "please log in" message and link to loginUrl
 *   loginUrl   the Trade login page
 *   customer   company_name and contact_name of the logged-in customer (empty when not logged in)
 *   sections   list of {heading, path[], products[]}; a product has id, title, brandName, name, documents[] (type,
 *              label, url) and images[] (type, label, url)
 *   zipAction  the address that downloads a product's images as a ZIP; add ?id=<product id>
 */
final class TradeDownloadsClaim
{
    /** @param array{pageId: int, slug: string, osmium: object, data?: array} $payload */
    public static function page(array $payload): ?array
    {
        $isOurs = $payload['slug'] === TradeDownloadsPages::URL;
        if (!$isOurs) return null;

        $osmium = $payload['osmium'];
        $slash = ($osmium->config->site->urlTrailingSlash ?? false) ? '/' : '';
        $customer = TradeDownloadsSource::customer($osmium);
        $loggedIn = $customer !== null;

        $sections = $loggedIn ? TradeDownloadsGrouping::sections(TradeDownloadsSource::products($osmium)) : [];

        return ['view' => 'trade/views/downloads.phtml', 'data' => ['tradeDownloads' => [
            'loggedIn' => $loggedIn,
            'loginUrl' => '/trade/login' . $slash,
            'customer' => ['company_name' => (string) ($customer['company_name'] ?? ''), 'contact_name' => (string) ($customer['contact_name'] ?? '')],
            'sections' => $sections,
            'zipAction' => '/api/trade-download-images' . $slash,
        ]]];
    }
}
