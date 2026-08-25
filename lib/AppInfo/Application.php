<?php

declare(strict_types=1);

namespace OCA\BrTop\AppInfo;

use OCA\BrTop\Middleware\BrAccessMiddleware;
use OCA\BrTop\Privacy\BrTopPrivacyProviderListener;
use OCA\BrTop\Permission\BrTopPermissionProviderListener;
use OCA\BrTop\Permission\BrTopPermissionSourceInterface;
use OCA\BrTop\Permission\NextcloudBrTopPermissionSource;
use OCA\BrTop\Repository\TemporaryAdminAccessRepository;
use OCA\BrTop\Repository\TemporaryAdminAccessRepositoryInterface;
use OCA\BrTop\Service\TemporaryAdminAccessChecker;
use OCA\BrTop\Service\TemporaryAdminAccessService;
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
        $context->registerServiceAlias(TemporaryAdminAccessChecker::class, TemporaryAdminAccessService::class);
        $context->registerServiceAlias(TemporaryAdminAccessRepositoryInterface::class, TemporaryAdminAccessRepository::class);
    }

    public function boot(IBootContext $context): void {
    }
}
