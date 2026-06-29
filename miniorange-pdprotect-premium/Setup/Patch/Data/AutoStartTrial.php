<?php

declare(strict_types=1);

namespace MiniOrange\PDProtectPremium\Setup\Patch\Data;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use MiniOrange\PDProtect\Helper\Encryption;
use MiniOrange\PDProtectPremium\Helper\Constants;

/**
 * Auto-starts the trial on first setup:upgrade after PDProtectPremium is installed,
 * for stores that have no trial start date and no verified license.
 */
class AutoStartTrial implements DataPatchInterface
{
    public function __construct(
        private readonly WriterInterface $configWriter,
        private readonly ScopeConfigInterface $scopeConfig
    ) {}

    public function apply(): self
    {
        $trialStart     = $this->scopeConfig->getValue(Constants::TRIAL_START_DATE);
        $licenseKey     = $this->scopeConfig->getValue(Constants::OAUTH_LK);

        // Only auto-start if no trial has been set yet and no license is active
        if (empty($trialStart) && empty($licenseKey)) {
            $this->configWriter->save(
                Constants::TRIAL_START_DATE,
                Encryption::encrypt(date('Y-m-d H:i:s'), Constants::DEFAULT_TOKEN_VALUE),
                'default',
                0
            );
        }

        return $this;
    }

    public static function getDependencies(): array
    {
        return [InitPremiumConfig::class];
    }

    public function getAliases(): array
    {
        return [];
    }
}
