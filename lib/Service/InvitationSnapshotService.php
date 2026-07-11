<?php

declare(strict_types=1);

namespace OCA\BrTop\Service;

use DateTimeImmutable;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class InvitationSnapshotService {
    public function __construct(
        private IDBConnection $db,
        private MeetingAbsenceService $absenceService,
        private ReplacementSelectionService $replacementSelectionService
    ) {
    }

    public function getOrCreateForMeeting(int $meetingId, string $meetingDate = '', string $actorUid = ''): array {
        $recipients = $this->recipientsForMeeting($meetingId);
        if ($recipients !== []) {
            return $recipients;
        }
        if ($actorUid === '') {
            throw new \RuntimeException('Der Einladungssnapshot braucht eine verantwortliche Benutzer-ID.');
        }

        $this->absenceService->assertReviewed($meetingId);
        $legislature = $this->absenceService->legislatureForMeeting($meetingId);
        $recipients = $this->replacementSelectionService->recipients(
            $legislature,
            $this->absenceService->confirmedMemberIds($meetingId)
        );

        $this->db->beginTransaction();
        try {
            foreach (array_values($recipients) as $index => &$recipient) {
                $recipient = $this->snapshotRow(
                    $recipient,
                    $meetingId,
                    (int)$legislature['id'],
                    (string)$legislature['minority_gender'],
                    (int)$legislature['minority_minimum_seats'],
                    $index + 1
                );
                $this->insertRecipient($recipient);
            }
            unset($recipient);
            $qb = $this->db->getQueryBuilder();
            $qb->insert('brtop_invitation_snapshots')->values([
                'meeting_id' => $qb->createNamedParameter($meetingId, IQueryBuilder::PARAM_INT),
                'legislature_id' => $qb->createNamedParameter((int)$legislature['id'], IQueryBuilder::PARAM_INT),
                'created_by_uid' => $qb->createNamedParameter($actorUid),
                'created_at' => $qb->createNamedParameter(new DateTimeImmutable(), IQueryBuilder::PARAM_DATE),
            ])->executeStatement();
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        return $recipients;
    }

    public function recipientsForMeeting(int $meetingId): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from('brtop_invitation_recipients')
            ->where($qb->expr()->eq('meeting_id', $qb->createNamedParameter($meetingId, IQueryBuilder::PARAM_INT)))
            ->orderBy('snapshot_position', 'ASC');
        return $qb->executeQuery()->fetchAll();
    }

    private function snapshotRow(
        array $member,
        int $meetingId,
        int $legislatureId,
        string $minorityGender,
        int $minorityMinimumSeats,
        int $position
    ): array {
        return [
            'meeting_id' => $meetingId,
            'legislature_id' => $legislatureId,
            'user_uid' => (string)$member['user_uid'],
            'display_name' => (string)$member['display_name'],
            'email' => (string)($member['email'] ?? ''),
            'group_name' => BrGroupsService::MEMBER_GROUP,
            'snapshot_position' => $position,
            'member_role' => (string)$member['member_role'],
            'invitation_type' => (string)$member['invitation_type'],
            'list_name' => (string)$member['list_name'],
            'list_seats' => (int)$member['list_seats'],
            'list_rank' => (int)$member['list_rank'],
            'gender' => (string)$member['gender'],
            'minority_gender' => $minorityGender,
            'minority_minimum_seats' => $minorityMinimumSeats,
            'absence_reason' => '',
            'absence_excused' => (string)($member['absence_excused'] ?? ''),
            'replacement_for_uid' => (string)($member['replacement_for_uid'] ?? ''),
            'replacement_for_name' => (string)($member['replacement_for_name'] ?? ''),
        ];
    }

    private function insertRecipient(array $recipient): void {
        $qb = $this->db->getQueryBuilder();
        $qb->insert('brtop_invitation_recipients')->values([
            'meeting_id' => $qb->createNamedParameter((int)$recipient['meeting_id'], IQueryBuilder::PARAM_INT),
            'legislature_id' => $qb->createNamedParameter((int)$recipient['legislature_id'], IQueryBuilder::PARAM_INT),
            'user_uid' => $qb->createNamedParameter((string)$recipient['user_uid']),
            'display_name' => $qb->createNamedParameter((string)$recipient['display_name']),
            'email' => $qb->createNamedParameter((string)$recipient['email']),
            'group_name' => $qb->createNamedParameter((string)$recipient['group_name']),
            'snapshot_position' => $qb->createNamedParameter((int)$recipient['snapshot_position'], IQueryBuilder::PARAM_INT),
            'member_role' => $qb->createNamedParameter((string)$recipient['member_role']),
            'invitation_type' => $qb->createNamedParameter((string)$recipient['invitation_type']),
            'list_name' => $qb->createNamedParameter((string)$recipient['list_name']),
            'list_seats' => $qb->createNamedParameter((int)$recipient['list_seats'], IQueryBuilder::PARAM_INT),
            'list_rank' => $qb->createNamedParameter((int)$recipient['list_rank'], IQueryBuilder::PARAM_INT),
            'gender' => $qb->createNamedParameter((string)$recipient['gender']),
            'minority_gender' => $qb->createNamedParameter((string)$recipient['minority_gender']),
            'minority_minimum_seats' => $qb->createNamedParameter((int)$recipient['minority_minimum_seats'], IQueryBuilder::PARAM_INT),
            'absence_reason' => $qb->createNamedParameter((string)$recipient['absence_reason']),
            'absence_excused' => $qb->createNamedParameter((string)$recipient['absence_excused']),
            'replacement_for_uid' => $qb->createNamedParameter((string)$recipient['replacement_for_uid']),
            'replacement_for_name' => $qb->createNamedParameter((string)$recipient['replacement_for_name']),
            'created_at' => $qb->createNamedParameter(new DateTimeImmutable(), IQueryBuilder::PARAM_DATE),
        ])->executeStatement();
    }
}
