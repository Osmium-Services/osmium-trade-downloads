<?php

declare(strict_types=1);

namespace Osmium\Services\TradeDownloads\Models;

/**
 * One product's page: its documents and images. A product with neither is not in the shop's list of products with files,
 * so it is taken from the shop's full product list and shown with nothing to download.
 */
final class TradeDownloadsProduct
{
    /**
     * @param array<int, array<string, mixed>> $withFiles as shop.product.files gives them
     * @param array<int, array<string, mixed>> $all as shop.products.list gives them
     * @return array{id: int, title: string, brandName: string, name: string, imageUrl: ?string, documents: array<int, array<string, mixed>>, images: array<int, array<string, mixed>>}|null null when the shop has no such product
     */
    public static function find(array $withFiles, array $all, int $id): ?array
    {
        foreach ($withFiles as $product) {
            if ((int) $product['id'] !== $id) continue;

            $main = null;
            foreach ($product['images'] as $image) {
                if ($image['type'] === 'main') $main = $image['url'];
            }

            return [
                'id' => $id,
                'title' => (string) $product['title'],
                'brandName' => (string) $product['brandName'],
                'name' => (string) $product['name'],
                'imageUrl' => $main ?? ($product['images'][0]['url'] ?? null),
                'documents' => $product['documents'],
                'images' => $product['images'],
            ];
        }

        foreach ($all as $product) {
            if ((int) $product['id'] !== $id) continue;

            return [
                'id' => $id,
                'title' => (string) $product['title'],
                'brandName' => (string) $product['brandName'],
                'name' => (string) $product['name'],
                'imageUrl' => $product['image'] ?? null,
                'documents' => [],
                'images' => [],
            ];
        }

        return null;
    }
}
