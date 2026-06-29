<?php
declare(strict_types=1);

namespace MiniOrange\PDProtect\Model;

/**
 * Free-tier stub — no data manipulation logic.
 *
 * All actual anonymization logic lives in
 * MiniOrange\PDProtectPremium\Model\PersonalDataAnonymizer,
 * which is injected via DI preference when the Premium extension is installed.
 */
class PersonalDataAnonymizer
{
    public function isOrderAnonymized(int $orderId): bool
    {
        return false;
    }

    /** @param int[] $orderIds */
    public function anonymizeOrdersByIds(array $orderIds): int
    {
        return 0;
    }

    /** @param int[] $quoteIds */
    public function anonymizeQuotesByIds(array $quoteIds): int
    {
        return 0;
    }

    public function anonymizeCustomer(int $customerId): void
    {
        // Premium feature — no-op in free extension.
    }

    public function anonymizeCustomerOrders(int $customerId): void
    {
        // Premium feature — no-op in free extension.
    }

    public function isCustomerAnonymized(int $customerId): bool
    {
        return false;
    }
}
