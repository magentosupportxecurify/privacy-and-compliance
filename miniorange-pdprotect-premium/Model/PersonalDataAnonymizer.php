<?php
declare(strict_types=1);

namespace MiniOrange\PDProtectPremium\Model;

use Magento\Framework\App\ResourceConnection;
use MiniOrange\PDProtect\Helper\Data;
use MiniOrange\PDProtect\Model\PersonalDataAnonymizer as FreePersonalDataAnonymizer;

class PersonalDataAnonymizer extends FreePersonalDataAnonymizer
{
    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly Data $dataHelper
    ) {
    }

    public function isOrderAnonymized(int $orderId): bool
    {
        $connection = $this->resource->getConnection();
        $email = $connection->fetchOne(
            'SELECT customer_email FROM ' . $this->resource->getTableName('sales_order')
            . ' WHERE entity_id = ?',
            [$orderId]
        );

        return is_string($email) && str_ends_with($email, '@deleted.invalid');
    }

    /**
     * @param int[] $orderIds
     */
    public function anonymizeOrdersByIds(array $orderIds): int
    {
        $orderIds = array_values(array_filter(array_map('intval', $orderIds)));
        if ($orderIds === []) {
            return 0;
        }

        $connection = $this->resource->getConnection();
        $processed  = 0;

        foreach ($orderIds as $orderId) {
            if ($this->isOrderAnonymized($orderId)) {
                continue;
            }

            $anonymousEmail = 'deleted_' . $orderId . '@deleted.invalid';

            $connection->update(
                $this->resource->getTableName('sales_order'),
                [
                    'customer_firstname'  => 'Deleted',
                    'customer_lastname'   => 'User',
                    'customer_email'      => $anonymousEmail,
                    'customer_middlename' => null,
                    'customer_prefix'     => null,
                    'customer_suffix'     => null,
                    'customer_dob'        => null,
                ],
                ['entity_id = ?' => $orderId]
            );

            $connection->update(
                $this->resource->getTableName('sales_order_address'),
                [
                    'firstname' => 'Deleted',
                    'lastname'  => 'User',
                    'email'     => $anonymousEmail,
                    'street'    => 'REDACTED',
                    'city'      => 'REDACTED',
                    'postcode'  => 'REDACTED',
                    'telephone' => null,
                    'company'   => null,
                ],
                ['parent_id = ?' => $orderId]
            );

            $connection->update(
                $this->resource->getTableName('sales_order_grid'),
                [
                    'customer_name'  => 'Deleted User',
                    'customer_email' => $anonymousEmail,
                    'billing_name'   => 'Deleted User',
                    'shipping_name'  => 'Deleted User',
                ],
                ['entity_id = ?' => $orderId]
            );

            $processed++;
        }

        return $processed;
    }

    /**
     * @param int[] $quoteIds
     */
    public function anonymizeQuotesByIds(array $quoteIds): int
    {
        $quoteIds = array_values(array_filter(array_map('intval', $quoteIds)));
        if ($quoteIds === []) {
            return 0;
        }

        $connection = $this->resource->getConnection();
        $processed  = 0;

        foreach ($quoteIds as $quoteId) {
            $anonymousEmail = 'deleted_quote_' . $quoteId . '@deleted.invalid';

            $connection->update(
                $this->resource->getTableName('quote'),
                [
                    'customer_email'      => $anonymousEmail,
                    'customer_firstname'  => 'Deleted',
                    'customer_lastname'   => 'User',
                    'customer_middlename' => null,
                    'customer_prefix'     => null,
                    'customer_suffix'     => null,
                    'remote_ip'           => null,
                ],
                ['entity_id = ?' => $quoteId]
            );

            $connection->update(
                $this->resource->getTableName('quote_address'),
                [
                    'email'     => $anonymousEmail,
                    'firstname' => 'Deleted',
                    'lastname'  => 'User',
                    'street'    => 'REDACTED',
                    'city'      => 'REDACTED',
                    'postcode'  => 'REDACTED',
                    'telephone' => null,
                    'company'   => null,
                ],
                ['quote_id = ?' => $quoteId]
            );

            $processed++;
        }

        return $processed;
    }

    /**
     * Anonymize customer PII while keeping the account.
     *
     * @throws \Exception
     */
    public function anonymizeCustomer(int $customerId): void
    {
        try {
            $connection     = $this->resource->getConnection();
            $anonymousEmail = 'anonymized_' . $customerId . '@deleted.invalid';

            $connection->update(
                $this->resource->getTableName('customer_entity'),
                [
                    'email'     => $anonymousEmail,
                    'firstname' => 'Anonymized',
                    'lastname'  => 'User',
                    'middlename' => null,
                    'prefix'    => null,
                    'suffix'    => null,
                    'dob'       => null,
                ],
                ['entity_id = ?' => $customerId]
            );

            $connection->update(
                $this->resource->getTableName('customer_address_entity'),
                [
                    'firstname' => 'Anonymized',
                    'lastname'  => 'User',
                    'middlename' => null,
                    'company'   => null,
                    'street'    => 'REDACTED',
                    'city'      => 'REDACTED',
                    'postcode'  => 'REDACTED',
                    'telephone' => null,
                ],
                ['parent_id = ?' => $customerId]
            );

            $orderIds = $connection->fetchCol(
                'SELECT entity_id FROM ' . $this->resource->getTableName('sales_order')
                . ' WHERE customer_id = ?',
                [$customerId]
            );
            $this->anonymizeOrdersByIds($orderIds);

            $connection->delete(
                $this->resource->getTableName('miniorange_pdprotect_consent_log'),
                ['customer_id = ?' => $customerId]
            );

            // Keep any pending deletion request in sync: the snapshot of name/email
            // stored at request time is now stale. Update it so the admin grid
            // reflects that this account has already been anonymized.
            $connection->update(
                $this->resource->getTableName('miniorange_pdprotect_deletion_request'),
                [
                    'customer_email' => $anonymousEmail,
                    'customer_name'  => 'Anonymized User',
                ],
                [
                    'customer_id = ?' => $customerId,
                    'status = ?'      => 'pending',
                ]
            );
        } catch (\Exception $e) {
            $this->dataHelper->log_debug(
                'PersonalDataAnonymizer: failed to anonymize customer '
                . $customerId . ': ' . $e->getMessage()
            );
            throw $e;
        }
    }

    /**
     * Anonymize all orders for a customer (used before account deletion).
     */
    public function anonymizeCustomerOrders(int $customerId): void
    {
        $connection = $this->resource->getConnection();
        $orderIds   = $connection->fetchCol(
            'SELECT entity_id FROM ' . $this->resource->getTableName('sales_order')
            . ' WHERE customer_id = ?',
            [$customerId]
        );
        $this->anonymizeOrdersByIds($orderIds);

        $anonymousEmail = 'deleted_' . $customerId . '@deleted.invalid';
        $connection->update(
            $this->resource->getTableName('sales_order_grid'),
            [
                'customer_name'  => 'Deleted User',
                'customer_email' => $anonymousEmail,
                'billing_name'   => 'Deleted User',
                'shipping_name'  => 'Deleted User',
            ],
            ['customer_id = ?' => $customerId]
        );
    }

    public function isCustomerAnonymized(int $customerId): bool
    {
        $connection = $this->resource->getConnection();
        $email = $connection->fetchOne(
            'SELECT email FROM ' . $this->resource->getTableName('customer_entity') . ' WHERE entity_id = ?',
            [$customerId]
        );

        return is_string($email) && (
            str_ends_with($email, '@deleted.invalid')
            || str_starts_with($email, 'anonymized_')
        );
    }
}
