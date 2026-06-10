<?php
declare(strict_types=1);

namespace MiniOrange\PDProtect\ViewModel\Adminhtml;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use MiniOrange\PDProtect\Helper\Data as PDProtectHelper;

class DeletionRequests implements ArgumentInterface
{
    private const DEFAULT_LIMIT = 20;
    private const ALLOWED_LIMITS = [10, 20, 50, 100];
    private const ALLOWED_STATUS_FILTERS = ['all', 'pending', 'approved', 'rejected'];

    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly UrlInterface $urlBuilder,
        private readonly RequestInterface $request,
        private readonly PDProtectHelper $helper
    ) {}

    public function hasReachedDeleteLimit(): bool
    {
        return $this->helper->hasReachedDeleteLimit();
    }

    public function getDeleteApprovalCount(): int
    {
        return $this->helper->getDeleteApprovalCount();
    }

    public function getDeleteApprovalLifetimeLimit(): int
    {
        return $this->helper->getDeleteApprovalLifetimeLimit();
    }

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
        return $this->urlBuilder->getUrl('mopdp/datadeletion/index', $params);
    }

    public function getSearchBaseUrl(): string
    {
        return $this->urlBuilder->getUrl('mopdp/datadeletion/index', [
            'p'      => 1,
            'limit'  => $this->getLimit(),
            'status' => $this->getStatusFilter(),
        ]);
    }

    private function buildWhereClause(): string
    {
        $conditions = [];

        $filter = $this->getStatusFilter();
        if ($filter !== 'all') {
            $conditions[] = "status = '{$filter}'";
        }

        $search = $this->getSearchQuery();
        if ($search !== '') {
            $connection = $this->resource->getConnection();
            $like       = $connection->quote('%' . $search . '%');
            $conditions[] = "(customer_name LIKE {$like} OR customer_email LIKE {$like})";
        }

        return $conditions ? implode(' AND ', $conditions) : '';
    }

    public function getTotalCount(): int
    {
        $connection = $this->resource->getConnection();
        $table      = $this->resource->getTableName('miniorange_pdprotect_deletion_request');
        $where      = $this->buildWhereClause();
        return (int) $connection->fetchOne(
            "SELECT COUNT(*) FROM {$table}" . ($where ? " WHERE {$where}" : '')
        );
    }

    public function getStatusCounts(): array
    {
        $connection = $this->resource->getConnection();
        $table      = $this->resource->getTableName('miniorange_pdprotect_deletion_request');
        $rows       = $connection->fetchAll(
            "SELECT status, COUNT(*) AS cnt FROM {$table} GROUP BY status"
        );
        $counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['cnt'];
        }
        return $counts;
    }

    public function getTotalPages(): int
    {
        return (int) ceil($this->getTotalCount() / $this->getLimit());
    }

    public function getRequests(): array
    {
        $connection = $this->resource->getConnection();
        $table      = $this->resource->getTableName('miniorange_pdprotect_deletion_request');
        $limit      = $this->getLimit();
        $offset     = ($this->getCurrentPage() - 1) * $limit;
        $where      = $this->buildWhereClause();
        return $connection->fetchAll(
            "SELECT * FROM {$table}"
            . ($where ? " WHERE {$where}" : '')
            . " ORDER BY requested_at DESC LIMIT {$limit} OFFSET {$offset}"
        );
    }

    public function getUpdateStatusUrl(): string
    {
        return $this->urlBuilder->getUrl('mopdp/datadeletion/updatestatus');
    }

    public function getPageUrl(int $page): string
    {
        $params = ['p' => $page, 'limit' => $this->getLimit(), 'status' => $this->getStatusFilter()];
        $search = $this->getSearchQuery();
        if ($search !== '') {
            $params['search'] = $search;
        }
        return $this->urlBuilder->getUrl('mopdp/datadeletion/index', $params);
    }

    public function getLimitUrl(int $limit): string
    {
        return $this->urlBuilder->getUrl('mopdp/datadeletion/index', [
            'p'     => 1,
            'limit' => $limit,
        ]);
    }

    public function getAllowedLimits(): array
    {
        return self::ALLOWED_LIMITS;
    }
}
