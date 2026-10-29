<?php

use Osmium\Services\TradeDownloads\Models\TradeDownloadsSource;
use Osmium\Services\TradeDownloads\Models\TradeDownloadsZip;

/**
 * POST /api/trade-download-images with the product id in field id: a ZIP of that product's images, for a logged-in trade
 * customer only. Core accepts only same-origin POSTs on api routes, so the page offers it as a small form. The images are
 * the shop's own public files; this packs them in one download.
 */
$fail = static function (int $code, string $message): never {
    \http_response_code($code);
    \header('Content-Type: text/plain; charset=utf-8');
    echo $message;
    exit;
};

$customer = TradeDownloadsSource::customer($this->osmium);
$notLoggedIn = $customer === null;
if ($notLoggedIn) $fail(403, 'Please log in to download images.');

$id = (int) ($_POST['id'] ?? 0);
$product = null;
foreach (TradeDownloadsSource::products($this->osmium) as $candidate) {
    if ((int) $candidate['id'] === $id) $product = $candidate;
}

$missing = $product === null || $product['images'] === [];
if ($missing) $fail(404, 'No images found for that product.');

$zipPath = TradeDownloadsZip::build($product['images']);
$empty = $zipPath === null;
if ($empty) $fail(404, 'No images found for that product.');

$filename = TradeDownloadsZip::filename((string) $product['title']);
\header('Content-Type: application/zip');
\header('Content-Disposition: attachment; filename="' . $filename . '"');
\header('Content-Length: ' . \filesize($zipPath));
\header('Cache-Control: private, max-age=0, must-revalidate');
\readfile($zipPath);
\unlink($zipPath);
exit;
