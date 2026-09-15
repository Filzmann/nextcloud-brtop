<?php

declare(strict_types=1);

namespace OCP {
    if (!interface_exists(IDBConnection::class)) {
        interface IDBConnection {}
    }
}

namespace OCA\BrTop\Tests {

    use OCA\BrTop\Service\BrGroupsService;
    use OCA\BrTop\Service\InvitationSnapshotService;
    use OCA\BrTop\Service\MeetingAbsenceService;
    use OCA\BrTop\Service\ReplacementSelectionService;
    use OCP\IDBConnection;

    $existingRecipients = [[
        'meeting_id' => 17,
        'user_uid' => 'snapshot-user',
        'display_name' => 'Snapshot Person',
        'member_role' => 'regular',
        'invitation_type' => 'initial',
    ]];

    $service = new class($existingRecipients) extends InvitationSnapshotService {
        public function __construct(private array $existingRecipients) {}
        public function recipientsForMeeting(int $meetingId): array { return $this->existingRecipients; }
    };

    assertSameValue(
        $existingRecipients,
        $service->getOrCreateForMeeting(17, '2026-07-21'),
        'An existing invitation snapshot must be returned unchanged without re-evaluating the legislature or absences.'
    );

    $db = new class implements IDBConnection {};
    $absenceService = new class extends MeetingAbsenceService { public function __construct() {} };
    $replacementService = new class extends ReplacementSelectionService { public function __construct() {} };
    $groupNames = new class extends BrGroupsService {
        public function __construct() {}
        public function memberGroupName(): string { return 'BR Custom'; }
    };
    $newSnapshotService = new InvitationSnapshotService($db, $absenceService, $replacementService, $groupNames);
    $snapshotRow = new \ReflectionMethod(InvitationSnapshotService::class, 'snapshotRow');
    $row = $snapshotRow->invoke($newSnapshotService, [
        'user_uid' => 'member',
        'display_name' => 'Mitglied',
        'member_role' => 'regular',
        'invitation_type' => 'initial',
        'list_name' => 'Liste',
        'list_seats' => 1,
        'list_rank' => 1,
        'gender' => 'diverse',
    ], 18, 2, 'female', 1, 1);
    assertSameValue('BR Custom', $row['group_name'], 'New invitation snapshots must record the canonical configured BR member group.');

    echo 'InvitationSnapshotService smoke tests passed' . PHP_EOL;
}
