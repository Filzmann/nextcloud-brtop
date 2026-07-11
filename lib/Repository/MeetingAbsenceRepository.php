<?php

declare(strict_types=1);

namespace OCA\BrTop\Repository;

use DateTimeImmutable;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class MeetingAbsenceRepository {
    public function __construct(private IDBConnection $db) {
    }

    public function forMeeting(int $meetingId): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from('brtop_meeting_absences')
            ->where($qb->expr()->eq('meeting_id', $qb->createNamedParameter($meetingId, IQueryBuilder::PARAM_INT)));
        return $qb->executeQuery()->fetchAll();
    }

    public function addSuggestions(int $meetingId, array $memberIds): void {
        $existing = [];
        foreach ($this->forMeeting($meetingId) as $row) {
            $existing[(int)$row['member_id']] = true;
        }
        foreach ($memberIds as $memberId) {
            $memberId = (int)$memberId;
            if ($memberId <= 0 || isset($existing[$memberId])) {
                continue;
            }
            $this->insert($meetingId, $memberId, 'suggested', 'calendar', null);
        }
    }

    public function replaceConfirmed(int $meetingId, array $memberIds, string $uid): void {
        $this->db->beginTransaction();
        try {
            $qb = $this->db->getQueryBuilder();
            $qb->delete('brtop_meeting_absences')
                ->where($qb->expr()->eq('meeting_id', $qb->createNamedParameter($meetingId, IQueryBuilder::PARAM_INT)))
                ->executeStatement();
            foreach ($memberIds as $memberId) {
                $this->insert($meetingId, (int)$memberId, 'confirmed', 'admin', $uid);
            }
            $qb = $this->db->getQueryBuilder();
            $qb->delete('brtop_absence_reviews')
                ->where($qb->expr()->eq('meeting_id', $qb->createNamedParameter($meetingId, IQueryBuilder::PARAM_INT)))
                ->executeStatement();
            $qb = $this->db->getQueryBuilder();
            $qb->insert('brtop_absence_reviews')->values([
                'meeting_id' => $qb->createNamedParameter($meetingId, IQueryBuilder::PARAM_INT),
                'confirmed_by_uid' => $qb->createNamedParameter($uid),
                'confirmed_at' => $qb->createNamedParameter(new DateTimeImmutable(), IQueryBuilder::PARAM_DATE),
            ])->executeStatement();
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function isReviewed(int $meetingId): bool {
        $qb = $this->db->getQueryBuilder();
        $qb->select('id')
            ->from('brtop_absence_reviews')
            ->where($qb->expr()->eq('meeting_id', $qb->createNamedParameter($meetingId, IQueryBuilder::PARAM_INT)))
            ->setMaxResults(1);
        return $qb->executeQuery()->fetchOne() !== false;
    }

    private function insert(int $meetingId, int $memberId, string $status, string $source, ?string $uid): void {
        $qb = $this->db->getQueryBuilder();
        $qb->insert('brtop_meeting_absences')->values([
            'meeting_id' => $qb->createNamedParameter($meetingId, IQueryBuilder::PARAM_INT),
            'member_id' => $qb->createNamedParameter($memberId, IQueryBuilder::PARAM_INT),
            'status' => $qb->createNamedParameter($status),
            'source' => $qb->createNamedParameter($source),
            'confirmed_by_uid' => $qb->createNamedParameter($uid),
            'updated_at' => $qb->createNamedParameter(new DateTimeImmutable(), IQueryBuilder::PARAM_DATE),
        ])->executeStatement();
    }
}
