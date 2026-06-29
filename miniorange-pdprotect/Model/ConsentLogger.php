<?php
declare(strict_types=1);

namespace MiniOrange\PDProtect\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;

class ConsentLogger
{
    private const TABLE = 'miniorange_pdprotect_consent_log';

    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly RemoteAddress $remoteAddress
    ) {}

    public function log(string $status, ?int $customerId = null, string $countryCode = '', ?string $email = null): void
    {
        $connection = $this->resource->getConnection();
        $connection->insert(
            $this->resource->getTableName(self::TABLE),
            [
                'customer_id'    => $customerId,
                'visitor_ip'     => (string) $this->remoteAddress->getRemoteAddress(),
                'country_code'   => $countryCode ?: null,
                'visitor_email'  => $email,
                'consent_status' => $status,
                'created_at'     => (new \DateTime())->format('Y-m-d H:i:s'),
            ]
        );
    }

    public function hasActiveConsent(int $customerId): bool
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from($this->resource->getTableName(self::TABLE), ['log_id'])
            ->where('customer_id = ?', $customerId)
            ->where('consent_status = ?', 'accepted')
            ->limit(1);
        return (bool) $connection->fetchOne($select);
    }
}
