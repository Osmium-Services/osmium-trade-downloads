<?php

declare(strict_types=1);

namespace Tests\Services\TradeDownloads;

use Osmium\Services\TradeDownloads\Models\TradeDownloadsProduct;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../models/TradeDownloadsProduct.php';

final class TradeDownloadsProductTest extends TestCase
{
    public function test_a_product_with_files_comes_with_them_and_its_main_image(): void
    {
        $files = [['id' => 14, 'title' => 'SolarGo2 40W', 'brandName' => 'SolarGo2', 'name' => '40W', 'documents' => [['type' => 'datasheet']], 'images' => [['type' => 'extra', 'url' => '/e.jpg'], ['type' => 'main', 'url' => '/m.jpg']]]];

        $product = TradeDownloadsProduct::find($files, [], 14);

        $this->assertSame('/m.jpg', $product['imageUrl']);
        $this->assertCount(1, $product['documents']);
        $this->assertCount(2, $product['images']);
    }

    public function test_a_product_without_files_is_found_in_the_full_list_with_nothing_to_download(): void
    {
        $all = [['id' => 9, 'title' => 'Cable', 'brandName' => 'SolarGo2', 'name' => 'Cable', 'image' => '/c.jpg']];

        $product = TradeDownloadsProduct::find([], $all, 9);

        $this->assertSame([], $product['documents']);
        $this->assertSame([], $product['images']);
        $this->assertSame('/c.jpg', $product['imageUrl']);
    }

    public function test_an_unknown_product_is_null(): void
    {
        $this->assertNull(TradeDownloadsProduct::find([], [], 5));
    }
}
