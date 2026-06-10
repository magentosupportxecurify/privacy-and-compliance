<?php
declare(strict_types=1);

namespace MiniOrange\PDProtect\Cron;

use MiniOrange\PDProtect\Model\ConsentLogCleaner;

class CleanConsentLog
{
    public function __construct(
        private readonly ConsentLogCleaner $cleaner
    ) {}

    public function execute(): void
    {
        $this->cleaner->execute();
    }
}
