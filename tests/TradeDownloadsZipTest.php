<?php

declare(strict_types=1);

namespace Tests\Services\TradeDownloads;

use Osmium\Services\TradeDownloads\Models\TradeDownloadsZip;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../models/TradeDownloadsZip.php';

final class TradeDownloadsZipTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = \sys_get_temp_dir() . '/tdz_test_' . \uniqid();
        \mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        foreach (\glob($this->dir . '/*') ?: [] as $file) {
            \unlink($file);
        }
        \rmdir($this->dir);
    }

    public function test_it_packs_each_file_under_the_name_given(): void
    {
        \file_put_contents($this->dir . '/a@2x.jpg', 'one');
        \file_put_contents($this->dir . '/b.jpg', 'two');

        $path = TradeDownloadsZip::build([
            ['zipFile' => $this->dir . '/a@2x.jpg', 'zipName' => 'a.jpg'],
            ['zipFile' => $this->dir . '/b.jpg', 'zipName' => 'b-extra1.jpg'],
        ]);

        $zip = new \ZipArchive();
        $zip->open($path);
        $this->assertSame(['a.jpg', 'b-extra1.jpg'], [$zip->getNameIndex(0), $zip->getNameIndex(1)]);
        $this->assertSame('one', $zip->getFromName('a.jpg'));
        $zip->close();
        \unlink($path);
    }

    public function test_a_missing_file_is_skipped_and_all_missing_gives_null(): void
    {
        \file_put_contents($this->dir . '/a.jpg', 'one');

        $path = TradeDownloadsZip::build([['zipFile' => $this->dir . '/gone.jpg', 'zipName' => 'gone.jpg'], ['zipFile' => $this->dir . '/a.jpg', 'zipName' => 'a.jpg']]);
        $zip = new \ZipArchive();
        $zip->open($path);
        $this->assertSame(1, $zip->numFiles);
        $zip->close();
        \unlink($path);

        $this->assertNull(TradeDownloadsZip::build([['zipFile' => $this->dir . '/gone.jpg', 'zipName' => 'gone.jpg']]));
        $this->assertNull(TradeDownloadsZip::build([]));
    }

    public function test_the_download_name_is_a_safe_slug_of_the_title(): void
    {
        $this->assertSame('seed-acme-panel-200w-images.zip', TradeDownloadsZip::filename('Seed Acme Panel 200W'));
        $this->assertSame('a-b-images.zip', TradeDownloadsZip::filename('  A / "B" ../'));
        $this->assertSame('product-images.zip', TradeDownloadsZip::filename('???'));
    }
}
