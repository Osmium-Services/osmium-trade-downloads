<?php

declare(strict_types=1);

namespace Osmium\Services\TradeDownloads\Models;

use Osmium\Core\Library\ServiceHooks;
use Osmium\Core\Library\ServiceLoader;
use Osmium\Core\Models\ServiceModel;

/**
 * Where Trade Downloads gets its facts: who is logged in (Trade Accounts' trade.customer.current) and the products
 * with files (the shop's shop.product.files). Both come through hooks, so neither service's PHP is called.
 */
final class TradeDownloadsSource
{
    /** @return array{id: int, company_name: string, contact_name: string, status: string}|null null when nobody is logged in */
    public static function customer(object $osmium): ?array
    {
        $customer = self::hooks($osmium)->handle(event: 'trade.customer.current', payload: ['osmium' => $osmium]);

        return \is_array($customer) ? $customer : null;
    }

    /** @return array<int, array<string, mixed>> products that have documents or images, as the shop describes them */
    public static function products(object $osmium): array
    {
        $payload = ['dataSource' => $osmium->dataSource, 'osmium' => $osmium];
        $products = self::hooks($osmium)->handle(event: 'shop.product.files', payload: $payload);

        return \is_array($products) ? $products : [];
    }

    /** @return array<int, array<string, mixed>> every live product, as shop.products.list gives them */
    public static function allProducts(object $osmium): array
    {
        $payload = ['dataSource' => $osmium->dataSource, 'osmium' => $osmium];
        $products = self::hooks($osmium)->handle(event: 'shop.products.list', payload: $payload);

        return \is_array($products) ? $products : [];
    }

    /** @return array<int, array{id: int, name: string, path: string[], order: int[]}|false> category id => the section the trade price list shows it in, or false when hidden; empty when Trade Ordering is not installed */
    public static function categoryLayout(object $osmium): array
    {
        $layout = self::hooks($osmium)->handle(event: 'trade.category.layout', payload: ['osmium' => $osmium]);

        return \is_array($layout) ? $layout : [];
    }

    private static function hooks(object $osmium): ServiceHooks
    {
        return new ServiceHooks(new ServiceLoader(new ServiceModel($osmium->dataSource)));
    }
}
