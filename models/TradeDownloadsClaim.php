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
 *   product    set when the page is asked for one product (?product=<id>, which core stashes and redirects): that product's id, title, brandName, name,
 *              imageUrl, documents[] and images[], or null when the shop has no such product; absent otherwise
 *   downloadsUrl the page itself, for a link back from a product
 *   zipAction  the address a small form posts to (method post, hidden field id holding the product id) to download that
 *              product's images as a ZIP; core accepts only same-origin POSTs on api routes
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

        $extra = [];
        $productId = (int) $osmium->getStashedParam('product', 0); // Core redirects away a query string and keeps it in the session
        $osmium->clearStashedQuerystring();
        if ($loggedIn && $productId > 0) {
            $extra['product'] = TradeDownloadsProduct::find(TradeDownloadsSource::products($osmium), TradeDownloadsSource::allProducts($osmium), $productId);
        }

        return ['view' => 'trade/views/downloads.phtml', 'data' => ['tradeDownloads' => $extra + [
            'downloadsUrl' => '/trade/downloads' . $slash,
            'loggedIn' => $loggedIn,
            'loginUrl' => '/trade/login' . $slash,
            'customer' => ['company_name' => (string) ($customer['company_name'] ?? ''), 'contact_name' => (string) ($customer['contact_name'] ?? '')],
            'sections' => $sections,
            'zipAction' => '/api/trade-download-images' . $slash,
        ]]];
    }

    /**
     * Answers `trade.documents.url`: the address to put a product id after to open that product's documents, for
     * another service (the trade price list) to link to without knowing this service's page.
     *
     * @param array{osmium: object} $payload
     */
    public static function documentsUrl(array $payload): string
    {
        $slash = ($payload['osmium']->config->site->urlTrailingSlash ?? false) ? '/' : '';

        return '/' . TradeDownloadsPages::URL . $slash . '?product=';
    }
}
