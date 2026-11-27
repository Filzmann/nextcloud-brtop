<?php

declare(strict_types=1);

use OCA\BrTop\Service\TemporaryAdminAccessService;
use OCA\BrTop\Repository\MeetingRepository;
use OCA\BrTop\Service\FileExportService;
use OCA\LocalBase\PublicApi\V1\BrMembershipSnapshotService;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\IGroupManager;
use OCP\IUserManager;

$objectStorageExportPath = 'BR-Sitzungen/2035-01-02_-_FR-08/compatibility-export.txt';
$postgresqlMeetingTitle = 'FR-04 PostgreSQL Upgrade-Bestand';
$postgresqlRollbackTitle = 'FR-04 PostgreSQL Rollback darf nicht bestehen';

return [
    'providerRegistrations' => [
        'flz_data_protection' => [
            OCA\FlzDataProtection\PublicApi\V1\RegisterPersonalDataProvidersEvent::class,
            OCA\FlzDataProtection\PublicApi\V1\RegisterProcessingMetadataProvidersEvent::class,
        ],
        'flz_permission_matrix' => [
            OCA\FlzPermissionMatrix\PublicApi\V1\RegisterPermissionProvidersEvent::class,
        ],
    ],
    'uiPath' => '/index.php/apps/brtop/',
    'preGrantUiStatuses' => [200],
    'postGrantUiStatuses' => [200],
    // canManage() requires an active LocalBase BR membership snapshot (seeded
    // by the compatibility stage) plus membership in the member and br_dpo
    // groups; grantManagerGroups adds br_dpo, the membership is ensured here so
    // that pre-grant probes stay false for membership-based apps like brstunden.
    'grantService' => static function (string $uid): void {
        $snapshot = OCP\Server::get(BrMembershipSnapshotService::class)->snapshot();
        if (!$snapshot->isValid()) {
            throw new RuntimeException('Der LocalBase-BR-Mitgliedervertrag ist nicht gueltig.');
        }
        $groups = OCP\Server::get(IGroupManager::class);
        $user = OCP\Server::get(IUserManager::class)->get($uid);
        $member = $groups->get((string)$snapshot->memberGroupId());
        if ($user === null || $member === null) {
            throw new RuntimeException('Compatibility membership prerequisites are missing.');
        }
        if (!$groups->isInGroup($uid, (string)$snapshot->memberGroupId())) {
            $member->addUser($user);
        }
        OCP\Server::get(TemporaryAdminAccessService::class)->activate($uid, 60);
    },
    // BrDpoAssignmentService::dpoGroupId() default.
    'grantManagerGroups' => ['br_dpo'],
    'permissionProbe' => static fn(string $uid): bool => OCP\Server::get(TemporaryAdminAccessService::class)
        ->hasActiveGrant($uid),
    'apiSmokes' => [
        ['/index.php/apps/brtop/api/state', [200]],
    ],
    'postgresqlUpgradeSeed' => static function (string $uid) use ($postgresqlMeetingTitle, $postgresqlRollbackTitle): void {
        $repository = OCP\Server::get(MeetingRepository::class);
        $meetingId = $repository->insert(
            $uid,
            $postgresqlMeetingTitle,
            '2036-01-08',
            '10:30',
            'Synthetischer Raum',
            'custom',
            '',
            null,
            'not_created',
        );
        $meeting = $repository->findByIdAndOwner($meetingId, $uid);
        if ($meeting === null || (string)$meeting['title'] !== $postgresqlMeetingTitle || $meeting['invitation_date'] !== null) {
            throw new RuntimeException('Der synthetische BRTop-PostgreSQL-Bestand wurde nicht korrekt angelegt.');
        }

        try {
            $repository->transactional(static function () use ($repository, $uid, $postgresqlRollbackTitle): void {
                $repository->insert(
                    $uid,
                    $postgresqlRollbackTitle,
                    '2036-01-09',
                    '11:00',
                    'Synthetischer Raum',
                    'custom',
                    '',
                    null,
                    'not_created',
                );
                throw new RuntimeException('fr04-intended-rollback');
            });
            throw new RuntimeException('Der BRTop-Rollbackfall hat unerwartet committed.');
        } catch (RuntimeException $error) {
            if ($error->getMessage() !== 'fr04-intended-rollback') {
                throw $error;
            }
        }
        foreach ($repository->findRecentByOwner($uid) as $row) {
            if ((string)$row['title'] === $postgresqlRollbackTitle) {
                throw new RuntimeException('Der BRTop-Rollback hinterließ einen Datensatz.');
            }
        }
    },
    'postgresqlUpgradeVerify' => static function (string $uid) use ($postgresqlMeetingTitle, $postgresqlRollbackTitle): void {
        $repository = OCP\Server::get(MeetingRepository::class);
        $matches = array_values(array_filter(
            $repository->findRecentByOwner($uid),
            static fn(array $row): bool => (string)$row['title'] === $postgresqlMeetingTitle,
        ));
        if (count($matches) !== 1 || $matches[0]['invitation_date'] !== null) {
            throw new RuntimeException('Der BRTop-Bestand wurde beim PostgreSQL-Upgrade nicht unverändert erhalten.');
        }
        foreach ($repository->findRecentByOwner($uid) as $row) {
            if ((string)$row['title'] === $postgresqlRollbackTitle) {
                throw new RuntimeException('Der zurückgerollte BRTop-Datensatz erschien nach dem Upgrade.');
            }
        }
        $repository->deleteById((int)$matches[0]['id']);
    },
    'objectStorageWebSetup' => static function (string $uid) use ($objectStorageExportPath): void {
        OCP\Server::get(FileExportService::class)->putUserFile(
            $uid,
            $objectStorageExportPath,
            'brtop-object-storage-web-export',
        );
    },
    'objectStorageJobVerify' => static function (string $uid) use ($objectStorageExportPath): void {
        $userFolder = OCP\Server::get(IRootFolder::class)->getUserFolder($uid);
        $node = $userFolder->get($objectStorageExportPath);
        if (!$node instanceof File || $node->getContent() !== 'brtop-object-storage-web-export') {
            throw new RuntimeException('Der BRTop-Dateiexport ist im Jobprozess nicht unverändert lesbar.');
        }
        OCP\Server::get(FileExportService::class)->putUserFile(
            $uid,
            $objectStorageExportPath,
            'brtop-object-storage-job-export',
        );
        $updated = $userFolder->get($objectStorageExportPath);
        if (!$updated instanceof File || $updated->getContent() !== 'brtop-object-storage-job-export') {
            throw new RuntimeException('Der BRTop-Dateiexport lässt sich im Jobprozess nicht überschreiben.');
        }
        $updated->delete();
    },
];
