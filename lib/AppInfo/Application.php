<?php

declare(strict_types=1);

namespace OCA\BrTop\AppInfo;

use OCA\BrTop\Middleware\BrAccessMiddleware;
use OCA\BrTop\Privacy\BrTopPrivacyProviderListener;
use OCA\BrTop\Permission\BrTopPermissionProviderListener;
use OCA\BrTop\Permission\BrTopPermissionSourceInterface;
use OCA\BrTop\Permission\NextcloudBrTopPermissionSource;
use OCA\FilzmannDataProtection\PublicApi\V1\RegisterPersonalDataProvidersEvent;
use OCA\FilzmannPermissionMatrix\PublicApi\V1\RegisterPermissionProvidersEvent;
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
        $context->registerEventListener(RegisterPersonalDataProvidersEvent::class, BrTopPrivacyProviderListener::class);
        $context->registerEventListener(RegisterPermissionProvidersEvent::class, BrTopPermissionProviderListener::class);
        $context->registerServiceAlias(BrTopPermissionSourceInterface::class, NextcloudBrTopPermissionSource::class);
    }

    public function boot(IBootContext $context): void {
    }
}
