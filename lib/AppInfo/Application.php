<?php

declare(strict_types=1);

namespace OCA\BrTop\AppInfo;

use OCA\BrTop\Middleware\BrAccessMiddleware;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Bootstrap\IBootContext;

class Application extends App implements IBootstrap {
    public const APP_ID = 'brtop';

    public function __construct(array $urlParams = []) {
        parent::__construct(self::APP_ID, $urlParams);
    }

    public function register(IRegistrationContext $context): void {
        $context->registerMiddleware(BrAccessMiddleware::class);
    }

    public function boot(IBootContext $context): void {
    }
}
