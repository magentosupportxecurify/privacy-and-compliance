<?php

declare(strict_types=1);

namespace MiniOrange\PDProtectPremium\Setup\Patch\Data;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class InitPremiumConfig implements DataPatchInterface
{
    public function __construct(
        private readonly WriterInterface $configWriter
    ) {}

    public function apply(): self
    {
        // Data-deletion defaults
        $this->configWriter->save('mopdp/data_deletion/abandoned_enabled', '0', 'default', 0);
        $this->configWriter->save('mopdp/data_deletion/abandoned_value', '2', 'default', 0);
        $this->configWriter->save('mopdp/data_deletion/abandoned_unit', 'years', 'default', 0);
        $this->configWriter->save('mopdp/data_deletion/recent_docs_enabled', '0', 'default', 0);
        $this->configWriter->save('mopdp/data_deletion/recent_docs_value', '1', 'default', 0);
        $this->configWriter->save('mopdp/data_deletion/recent_docs_unit', 'years', 'default', 0);
        $this->configWriter->save('mopdp/data_deletion/order_status_enabled', '0', 'default', 0);
        $this->configWriter->save('mopdp/data_deletion/order_statuses', '', 'default', 0);
        $this->configWriter->save('mopdp/data_deletion/order_action', 'anonymize', 'default', 0);

        // Premium tracking defaults — mirrors TwoFA TRIAL_PLAN_CONSTANT / trial_expired guard
        // NOTE: pdprotect/tracking/timestamp is seeded by the free module (InitFreeConfig) — do NOT reset it here
        $this->configWriter->save('pdprotect/tracking/trial_plan_constant', '', 'default', 0);
        $this->configWriter->save('pdprotect/tracking/trial_expired_sent', '', 'default', 0);
        $this->configWriter->save('pdprotect/tracking/trial_extended_sent', '', 'default', 0);
        $this->configWriter->save('pdprotect/tracking/plan_activation_sent', '', 'default', 0);

        return $this;
    }

    public static function getDependencies(): array
    {
        return [\MiniOrange\PDProtect\Setup\Patch\Data\InitFreeConfig::class];
    }

    public function getAliases(): array
    {
        return [];
    }
}
