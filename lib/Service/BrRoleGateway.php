<?php

declare(strict_types=1);

namespace OCA\BrTop\Service;

use OCA\FlzPermissionMatrix\PublicApi\V1\BrRoleSnapshot;
use OCA\FlzPermissionMatrix\PublicApi\V1\BrRoleSnapshotService;
use OCP\App\IAppManager;
use OCP\Server;
use Psr\Log\LoggerInterface;
use Throwable;

/** Fail-closed consumer of the public flz_permission_matrix BR role V1 contract. */
class BrRoleGateway
{
    private const PROVIDER_APP_ID = 'flz_permission_matrix';
    private const SUPPORTED_CONTRACT_VERSION = '1.0';

    public function __construct(
        private IAppManager $apps,
        private LoggerInterface $logger,
    )
    {
    }

    public function snapshot(): ?BrRoleSnapshot
    {
        try
        {
            if (!in_array(self::PROVIDER_APP_ID, $this->apps->getEnabledApps(), true))
            {
                return null;
            }
            if (!class_exists(BrRoleSnapshotService::class))
            {
                return null;
            }
            $provider = Server::get(BrRoleSnapshotService::class);
            if (!$provider instanceof BrRoleSnapshotService)
            {
                return null;
            }
            $snapshot = $provider->snapshot();
            if ($snapshot->contractVersion() !== self::SUPPORTED_CONTRACT_VERSION
                || !$snapshot->isValid()
                || $snapshot->definitionVersion() < 1
                || $snapshot->definitionRevision() < 1
                || !$this->validGroupId($snapshot->memberGroupId())
                || !$this->validGroupId($snapshot->chairGroupId())
                || !$this->validGroupId($snapshot->deputyGroupId()))
            {
                return null;
            }
            return $snapshot;
        }
        catch (Throwable $error)
        {
            $this->logger->warning('BR role snapshot unavailable', [
                'app' => 'brtop',
                'provider' => self::PROVIDER_APP_ID,
                'exceptionClass' => $error::class,
            ]);
            return null;
        }
    }

    private function validGroupId(mixed $groupId): bool
    {
        if (!is_string($groupId)) return false;
        $groupId = trim($groupId);
        $characters = preg_match_all('/./us', $groupId, $matches);
        return $groupId !== '' && $characters !== false && $characters <= 64 && preg_match('/[\x00-\x1F\x7F]/u', $groupId) !== 1;
    }
}
