<?php

declare(strict_types=1);

namespace OCA\BrTop\Service;

use OCA\BrTop\AppInfo\Application;
use OCA\LocalBase\Service\AppLogger;
use Throwable;

class BrtopLogger {
    public function __construct(
        private AppLogger $logger
    ) {
    }

    public function error(string $action, Throwable $exception, array $context = []): void {
        $this->logger->error(Application::APP_ID, 'BRTop', $action, $exception, $context);
    }
}
