<?php

declare(strict_types=1);

namespace Tests\Services\TradeDownloads;

use Osmium\Services\TradeDownloads\Models\TradeDownloadsGrouping;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../models/TradeDownloadsGrouping.php';

final class TradeDownloadsGroupingTest extends TestCase
{
    private function product(string $title, ?array $category): array
    {
        return ['id' => 1, 'title' => $title, 'category' => $category];
    }

    private function category(int $id, string $name, array $order): array
    {
        return ['id' => $id, 'name' => $name, 'path' => [$name], 'order' => $order];
    }

    public function test_sections_follow_the_category_sort_order_with_other_last(): void
    {
        $panels = $this->category(1, 'Panels', [2]);
        $batteries = $this->category(2, 'Batteries', [1]);

        $sections = TradeDownloadsGrouping::sections([
            $this->product('Loose Cable', null),
            $this->product('Panel A', $panels),
            $this->product('Battery A', $batteries),
        ]);

        $this->assertSame(['Batteries', 'Panels', 'Other'], \array_column($sections, 'heading'));
    }

    public function test_a_parent_sort_number_comes_before_a_childs(): void
    {
        $sections = TradeDownloadsGrouping::sections([
            $this->product('A', $this->category(5, 'Child of second', [2, 1])),
            $this->product('B', $this->category(6, 'Child of first', [1, 9])),
        ]);

        $this->assertSame(['Child of first', 'Child of second'], \array_column($sections, 'heading'));
    }

    public function test_products_in_a_section_are_sorted_by_title_ignoring_case(): void
    {
        $category = $this->category(1, 'Panels', [1]);

        $sections = TradeDownloadsGrouping::sections([
            $this->product('zeta', $category),
            $this->product('Alpha', $category),
            $this->product('beta', $category),
        ]);

        $this->assertSame(['Alpha', 'beta', 'zeta'], \array_column($sections[0]['products'], 'title'));
    }

    public function test_two_products_in_one_category_share_a_section_and_none_gives_no_sections(): void
    {
        $category = $this->category(1, 'Panels', [1]);

        $this->assertCount(1, TradeDownloadsGrouping::sections([$this->product('A', $category), $this->product('B', $category)]));
        $this->assertSame([], TradeDownloadsGrouping::sections([]));
    }
}
