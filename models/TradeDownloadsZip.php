<?php

declare(strict_types=1);

namespace Osmium\Services\TradeDownloads\Models;

/**
 * Builds the ZIP of a product's images. Each image names the file to pack (the 2x version when there is one) and the
 * name to give it inside, as shop.product.files describes it.
 */
final class TradeDownloadsZip
{
    /**
     * @param array<int, array{zipFile?: string, zipName?: string}> $images
     * @return string|null the temporary ZIP file (the caller deletes it), or null when no image file could be packed
     */
    public static function build(array $images): ?string
    {
        $path = \tempnam(\sys_get_temp_dir(), 'tdz');
        $failed = $path === false;
        if ($failed) return null;

        $zip = new \ZipArchive();
        $opened = $zip->open($path, \ZipArchive::OVERWRITE);
        if ($opened !== true) {
            \unlink($path);

            return null;
        }

        $added = 0;
        foreach ($images as $image) {
            $file = (string) ($image['zipFile'] ?? '');
            $name = (string) ($image['zipName'] ?? '');
            $usable = $file !== '' && $name !== '' && \is_file($file);
            if ($usable && $zip->addFile($file, $name)) $added++;
        }

        $zip->close();
        if ($added === 0) {
            if (\is_file($path)) \unlink($path); // closing a ZIP with nothing in it removes the file itself

            return null;
        }

        return $path;
    }

    /** A safe download name from a product title: lower case, letters, digits and hyphens only */
    public static function filename(string $title): string
    {
        $slug = \trim(\preg_replace('/[^a-z0-9]+/', '-', \mb_strtolower($title)) ?? '', '-');

        return ($slug === '' ? 'product' : $slug) . '-images.zip';
    }
}
