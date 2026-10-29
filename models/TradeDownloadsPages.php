<?php

declare(strict_types=1);

namespace Osmium\Services\TradeDownloads\Models;

use Osmium\Core\Library\AdminPaths;
use Osmium\Modules\Admin\Models\Page;
use Osmium\Modules\Admin\Services\AdminPages;

/**
 * Keeps the Trade Downloads page in step with the service, as Trade Accounts does for its pages: created when the
 * service is installed, put in the bin (never removed) when it is disabled or removed, restored when enabled again.
 * It is an ordinary Pages row; TradeDownloadsClaim answers for it.
 */
class TradeDownloadsPages
{
    public const URL = 'trade/downloads';

    public function __construct(private Page $page, private AdminPages $pages)
    {
    }

    /** pages.protected_urls: pages no page tool may delete or move */
    public static function protectedUrls(array $payload): array
    {
        return [self::URL];
    }

    /** service.installed and service.enabled */
    public static function onEnabled(array $payload): void
    {
        self::forDatabase($payload['db'])->sync(true);
    }

    /** service.disabled and service.removing */
    public static function onDisabled(array $payload): void
    {
        self::forDatabase($payload['db'])->sync(false);
    }

    public function sync(bool $on): void
    {
        $row = null;
        foreach ($this->page->getAll() as $existing) {
            if ($existing['url'] === self::URL) $row = $existing;
        }

        $isMissing = $row === null;
        $isInBin = !$isMissing && $row['deleted_at'] !== null;

        if ($on && $isMissing) $this->create();
        if ($on && $isInBin) $this->page->restore((int) $row['id']);
        if (!$on && !$isMissing && !$isInBin) $this->page->softDelete((int) $row['id'], \date('Y-m-d H:i:s'));

        $this->pages->regenerateRoutesFile();
    }

    private static function forDatabase(\Osmium\Core\Library\OsmiumPDO $db): self
    {
        require_once AdminPaths::path('services/AdminPages.php');
        $page = new Page($db, 'pages');

        return new self($page, new AdminPages($page));
    }

    private function create(): void
    {
        $this->page->insert(
            id: $this->page->getNextId(),
            parentPageId: null,
            url: self::URL,
            title: 'Trade Downloads',
            description: null,
            robots: 'noindex,nofollow',
            now: \date('Y-m-d H:i:s'),
        );
    }
}
