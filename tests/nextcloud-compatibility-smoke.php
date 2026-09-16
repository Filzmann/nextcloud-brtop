<?php

declare(strict_types=1);

use OCA\BrTop\Service\TemporaryAdminAccessService;

return [
    'providerRegistrations' => [
        'filzmann_data_protection' => [
            OCA\FilzmannDataProtection\PublicApi\V1\RegisterPersonalDataProvidersEvent::class,
            OCA\FilzmannDataProtection\PublicApi\V1\RegisterProcessingMetadataProvidersEvent::class,
        ],
        'filzmann_permission_matrix' => [
            OCA\FilzmannPermissionMatrix\PublicApi\V1\RegisterPermissionProvidersEvent::class,
        ],
    ],
    'uiPath' => '/index.php/apps/brtop/',
    'preGrantUiStatuses' => [200],
    'postGrantUiStatuses' => [200],
    'grantService' => TemporaryAdminAccessService::class,
    'permissionProbe' => static fn(string $uid): bool => OCP\Server::get(TemporaryAdminAccessService::class)
        ->hasActiveGrant($uid),
    'apiSmokes' => [
        ['/index.php/apps/brtop/api/state', [200]],
    ],
];
