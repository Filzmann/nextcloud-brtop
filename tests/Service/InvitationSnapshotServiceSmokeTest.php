<?php

declare(strict_types=1);

namespace {
    if (!interface_exists(\OCP\IDBConnection::class)) {
        eval('namespace OCP; interface IDBConnection {}');
    }
}

namespace OCA\BrTop\Tests {
    require __DIR__ . '/../helpers.php';
    require_once __DIR__ . '/../../lib/Service/InvitationSnapshotService.php';

    use OCA\BrTop\Service\InvitationSnapshotService;

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

    echo 'InvitationSnapshotService smoke tests passed' . PHP_EOL;
}
