<?php

declare(strict_types=1);

namespace OCA\BrTop\Service;

use OCA\BrSprechstundenbuch\PublicApi\V1\SprechstundenberichtService;
use OCP\App\IAppManager;
use OCP\Server;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Fail-closed Consumer des öffentlichen V1-Vertrags von brsprechstundenbuch
 * (SprechstundenberichtService). Fehlende/deaktivierte Provider-Apps oder
 * kompatibilitätsverletzende Stände liefern null — ein gültiger
 * Standalone-Zustand ohne Bericht.
 */
class SprechstundenberichtGateway
{
    private const PROVIDER_APP_ID = 'brsprechstundenbuch';

    public function __construct(
        private IAppManager $apps,
        private LoggerInterface $logger,
    )
    {
    }

    /**
     * @return array{since_date: string, sprechstunden: list<array{date: string, member_uid: string, eintraege: int, wichtige: int, faelle: int}>}|null
     */
    public function snapshotSince(string $requesterUid, string $sinceDate): ?array
    {
        try
        {
            if (!in_array(self::PROVIDER_APP_ID, $this->apps->getEnabledApps(), true))
            {
                return null;
            }
            if (!class_exists(SprechstundenberichtService::class))
            {
                return null;
            }
            $service = Server::get(SprechstundenberichtService::class);
            if (!$service instanceof SprechstundenberichtService)
            {
                return null;
            }
            return $service->snapshotSince($requesterUid, $sinceDate)->toArray();
        }
        catch (Throwable $error)
        {
            $this->logger->warning('Sprechstundenbericht nicht verfügbar', [
                'app' => 'brtop',
                'provider' => self::PROVIDER_APP_ID,
                'exceptionClass' => $error::class,
            ]);
            return null;
        }
    }
}
