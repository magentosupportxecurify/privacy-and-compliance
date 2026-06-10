<?php
declare(strict_types=1);

namespace MiniOrange\PDProtect\Setup\Patch\Data;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class InitFreeConfig implements DataPatchInterface
{
    public function __construct(
        private readonly WriterInterface $configWriter
    ) {}

    public function apply(): self
    {
        $this->configWriter->save('pdprotect/free/delete_approval_count', '0', 'default', 0);
        $this->configWriter->save('pdprotect/general/allowed_countries_mode', 'all', 'default', 0);
        $this->configWriter->save('pdprotect/general/log_auto_clean', '1', 'default', 0);
        $this->configWriter->save('pdprotect/general/log_auto_clean_unit', 'days', 'default', 0);
        $this->configWriter->save('pdprotect/general/log_auto_clean_period', '1', 'default', 0);
        // Tracking: seed path so it exists from install (mirrors TwoFA TIMESTAMP constant)
        $this->configWriter->save('pdprotect/tracking/timestamp', '', 'default', 0);
        return $this;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
