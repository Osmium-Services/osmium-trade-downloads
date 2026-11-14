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

    /**
     * Sections as the trade price list has them: each product under every category it is listed under, through the
     * price list's own layout (its own section, inside its parent, or hidden), ordered by brand, the product's sort
     * number in that category, then name. Only products that have files appear.
     *
     * @param array<int, array<string, mixed>> $withFiles as shop.product.files gives them
     * @param array<int, array<string, mixed>> $all as shop.products.list gives them (categories, categorySorts, brandId)
     * @param array<int, array{id: int, name: string, path: string[], order: int[]}|false> $layout category id => section or false
     * @return array<int, array{heading: string, path: string[], products: array<int, array<string, mixed>>}>
     */
    public static function layoutSections(array $withFiles, array $all, array $layout): array
    {
        $files = [];
        foreach ($withFiles as $product) {
            $files[(int) $product['id']] = $product;
        }

        $byKey = [];
        foreach ($all as $listed) {
            $id = (int) $listed['id'];
            if (!isset($files[$id])) continue;

            $product = $files[$id] + ['brandId' => (int) ($listed['brandId'] ?? 0)];
            $categories = ($listed['categories'] ?? []) !== [] ? $listed['categories'] : [null];
            foreach ($categories as $category) {
                $section = $category === null ? null : ($layout[(int) $category['id']] ?? $category);
                if ($section === false) continue;

                $key = $section === null ? 'other' : 'c' . (int) $section['id'];
                $byKey[$key] ??= [
                    'heading' => $section === null ? self::OTHER : (string) $section['name'],
                    'path' => $section === null ? [] : \array_values($section['path']),
                    'order' => $section === null ? [] : \array_values($section['order']),
                    'products' => [],
                ];
                $sort = $category === null ? 9999 : (int) ($listed['categorySorts'][(int) $category['id']] ?? 9999);
                $alreadyThere = \in_array($id, \array_column($byKey[$key]['products'], 'id'), true);
                if (!$alreadyThere) $byKey[$key]['products'][] = $product + ['sortInSection' => $sort];
            }
        }

        $sections = \array_values($byKey);
        \usort($sections, static function (array $a, array $b): int {
            if (($a['order'] === []) !== ($b['order'] === [])) return $a['order'] === [] ? 1 : -1;

            return self::compareOrder($a['order'], $b['order']) ?: \strcasecmp($a['heading'], $b['heading']);
        });

        $result = [];
        foreach ($sections as $section) {
            \usort($section['products'], static fn(array $a, array $b): int => [$a['brandId'], $a['sortInSection']] <=> [$b['brandId'], $b['sortInSection']] ?: \strcasecmp((string) $a['name'], (string) $b['name']));
            $products = \array_map(static function (array $product): array {
                unset($product['sortInSection']);

                return $product;
            }, $section['products']);
            $result[] = ['heading' => $section['heading'], 'path' => $section['path'], 'products' => $products];
        }

        return $result;
    }

    /** Sort numbers from the top of the tree, one level at a time (a parent before its children) */
    private static function compareOrder(array $a, array $b): int
    {
        foreach ($a as $level => $number) {
            if (!isset($b[$level])) return 1;
            if ($number !== $b[$level]) return $number <=> $b[$level];
        }

        return \count($a) <=> \count($b);
    }
}
