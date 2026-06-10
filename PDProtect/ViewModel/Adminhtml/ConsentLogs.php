<?php
declare(strict_types=1);

namespace MiniOrange\PDProtect\ViewModel\Adminhtml;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class ConsentLogs implements ArgumentInterface
{
    private const TABLE = 'miniorange_pdprotect_consent_log';
    private const DEFAULT_LIMIT = 20;
    private const ALLOWED_LIMITS = [10, 20, 50, 100];
    private const ALLOWED_STATUS_FILTERS = ['all', 'accepted', 'withdrawn', 'guest'];

    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly UrlInterface $urlBuilder,
        private readonly RequestInterface $request
    ) {}

    public function getLimit(): int
    {
        $limit = (int) $this->request->getParam('limit', self::DEFAULT_LIMIT);
        return in_array($limit, self::ALLOWED_LIMITS, true) ? $limit : self::DEFAULT_LIMIT;
    }

    public function getCurrentPage(): int
    {
        return max(1, (int) $this->request->getParam('p', 1));
    }

    public function getStatusFilter(): string
    {
        $status = (string) $this->request->getParam('status', 'all');
        return in_array($status, self::ALLOWED_STATUS_FILTERS, true) ? $status : 'all';
    }

    public function getSearchQuery(): string
    {
        return trim((string) $this->request->getParam('search', ''));
    }

    public function getFilterUrl(string $status): string
    {
        $params = ['p' => 1, 'limit' => $this->getLimit(), 'status' => $status];
        $search = $this->getSearchQuery();
        if ($search !== '') {
            $params['search'] = $search;
        }
        return $this->urlBuilder->getUrl('mopdp/consentlogs/index', $params);
    }

    public function getSearchBaseUrl(): string
    {
        return $this->urlBuilder->getUrl('mopdp/consentlogs/index', [
            'p'      => 1,
            'limit'  => $this->getLimit(),
            'status' => $this->getStatusFilter(),
        ]);
    }

    private function buildWhereClause(): string
    {
        $conditions = [];

        $statusCondition = match ($this->getStatusFilter()) {
            'accepted'  => "consent_status = 'accepted'",
            'withdrawn' => "consent_status != 'accepted'",
            'guest'     => 'customer_id IS NULL',
            default     => '',
        };
        if ($statusCondition !== '') {
            $conditions[] = $statusCondition;
        }

        $search = $this->getSearchQuery();
        if ($search !== '') {
            $connection = $this->resource->getConnection();
            $like       = $connection->quote('%' . $search . '%');
            $conditions[] = "(visitor_email LIKE {$like} OR visitor_ip LIKE {$like})";
        }

        return $conditions ? implode(' AND ', $conditions) : '';
    }

    public function getTotalCount(): int
    {
        $connection = $this->resource->getConnection();
        $table      = $this->resource->getTableName(self::TABLE);
        $where      = $this->buildWhereClause();
        return (int) $connection->fetchOne(
            'SELECT COUNT(*) FROM ' . $table . ($where ? " WHERE {$where}" : '')
        );
    }

    /**
     * Returns global counts across ALL pages for the stats bar.
     * ['total' => N, 'accepted' => N, 'other' => N, 'guests' => N]
     */
    public function getStatusCounts(): array
    {
        $connection = $this->resource->getConnection();
        $table      = $this->resource->getTableName(self::TABLE);
        $total      = (int) $connection->fetchOne("SELECT COUNT(*) FROM {$table}");
        $accepted   = (int) $connection->fetchOne(
            "SELECT COUNT(*) FROM {$table} WHERE consent_status = 'accepted'"
        );
        $guests     = (int) $connection->fetchOne(
            "SELECT COUNT(*) FROM {$table} WHERE customer_id IS NULL"
        );
        return [
            'total'    => $total,
            'accepted' => $accepted,
            'other'    => $total - $accepted,
            'guests'   => $guests,
        ];
    }

    public function getTotalPages(): int
    {
        return (int) ceil($this->getTotalCount() / $this->getLimit());
    }

    public function getLogs(): array
    {
        $connection = $this->resource->getConnection();
        $table      = $this->resource->getTableName(self::TABLE);
        $limit      = $this->getLimit();
        $offset     = ($this->getCurrentPage() - 1) * $limit;
        $where      = $this->buildWhereClause();
        return $connection->fetchAll(
            'SELECT * FROM ' . $table
            . ($where ? " WHERE {$where}" : '')
            . ' ORDER BY created_at DESC LIMIT ' . $limit . ' OFFSET ' . $offset
        );
    }

    public function getPageUrl(int $page): string
    {
        $params = ['p' => $page, 'limit' => $this->getLimit(), 'status' => $this->getStatusFilter()];
        $search = $this->getSearchQuery();
        if ($search !== '') {
            $params['search'] = $search;
        }
        return $this->urlBuilder->getUrl('mopdp/consentlogs/index', $params);
    }

    public function getLimitUrl(int $limit): string
    {
        return $this->urlBuilder->getUrl('mopdp/consentlogs/index', [
            'p'     => 1,
            'limit' => $limit,
        ]);
    }

    public function getAllowedLimits(): array
    {
        return self::ALLOWED_LIMITS;
    }
}
