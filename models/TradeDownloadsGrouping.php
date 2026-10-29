<?php

declare(strict_types=1);

namespace Osmium\Services\TradeDownloads\Models;

/**
 * Groups the shop's products with files into sections by category, the way SolarGo2's document list did (by the
 * category name, with "Other" last), but from the shop's own category tree instead of hard-coded names.
 */
final class TradeDownloadsGrouping
{
    public const OTHER = 'Other';

    /**
     * Sections ordered by the category's sort numbers from the top of the tree; products with no category in "Other",
     * last. Within a section, products by title.
     *
     * @param array<int, array<string, mixed>> $products as shop.product.files gives them
     * @return array<int, array{heading: string, path: string[], products: array<int, array<string, mixed>>}>
     */
    public static function sections(array $products): array
    {
        $byKey = [];
        foreach ($products as $product) {
            $category = $product['category'] ?? null;
            $heading = $category === null ? self::OTHER : (string) $category['name'];
            $key = $category === null ? 'other' : 'c' . (int) $category['id'];

            $byKey[$key] ??= [
                'heading' => $heading,
                'path' => $category === null ? [] : \array_values($category['path']),
                'order' => $category === null ? [] : \array_values($category['order']),
                'products' => [],
            ];
            $byKey[$key]['products'][] = $product;
        }

        $sections = \array_values($byKey);
        \usort($sections, self::compareSections(...));

        $result = [];
        foreach ($sections as $section) {
            \usort($section['products'], static fn(array $a, array $b): int => \strcasecmp((string) $a['title'], (string) $b['title']));
            $result[] = ['heading' => $section['heading'], 'path' => $section['path'], 'products' => $section['products']];
        }

        return $result;
    }

    /** @param array{heading: string, order: int[]} $a */
    private static function compareSections(array $a, array $b): int
    {
        $aOther = $a['order'] === [];
        $bOther = $b['order'] === [];
        if ($aOther !== $bOther) return $aOther ? 1 : -1;

        $byOrder = $a['order'] <=> $b['order'];
        if ($byOrder !== 0) return $byOrder;

        return \strcasecmp($a['heading'], $b['heading']);
    }
}
