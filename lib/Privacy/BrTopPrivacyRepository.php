<?php

declare(strict_types=1);

namespace OCA\BrTop\Privacy;

use OCP\IDBConnection;

final class BrTopPrivacyRepository {
    public function __construct(private IDBConnection $db) {
    }

    /** @return list<array<string, mixed>> */
    public function forSubject(string $uid, int $limit): array {
        $records = [];
        $this->append($records, $this->memberships($uid, $limit), 'membership', $limit);
        $this->append($records, $this->invitations($uid, $limit - count($records)), 'invitation', $limit);
        $this->append($records, $this->meetings($uid, $limit - count($records)), 'meeting', $limit);
        $this->append($records, $this->documents($uid, $limit - count($records)), 'document', $limit);
        $this->append($records, $this->activities('brtop_legislatures', 'created_by_uid', 'created_at', 'legislature_created', $uid, $limit - count($records)), 'activity', $limit);
        $this->append($records, $this->activities('brtop_invitation_snapshots', 'created_by_uid', 'created_at', 'invitation_snapshot', $uid, $limit - count($records)), 'activity', $limit);
        $this->append($records, $this->activities('brtop_absence_reviews', 'confirmed_by_uid', 'confirmed_at', 'absence_review', $uid, $limit - count($records)), 'activity', $limit);
        return $records;
    }

    /** @param list<array<string, mixed>> $target @param list<array<string, mixed>> $rows */
    private function append(array &$target, array $rows, string $kind, int $limit): void {
        foreach ($rows as $row) {
            if (count($target) >= $limit) return;
            $target[] = ['kind'=>$kind] + $row;
        }
    }

    private function memberships(string $uid, int $limit): array {
        if ($limit < 1) return [];
        $qb = $this->db->getQueryBuilder();
        return $qb->select('id','legislature_id','display_name','email','gender','member_role','list_rank','active')
            ->from('brtop_roster_members')->where($qb->expr()->eq('user_uid', $qb->createNamedParameter($uid)))
            ->orderBy('id', 'ASC')->setMaxResults($limit)->executeQuery()->fetchAll();
    }

    private function invitations(string $uid, int $limit): array {
        if ($limit < 1) return [];
        $qb = $this->db->getQueryBuilder();
        return $qb->select('r.id','m.meeting_date','m.meeting_type','r.invitation_type','r.absence_reason','r.absence_excused','r.created_at')
            ->from('brtop_invitation_recipients', 'r')
            ->innerJoin('r', 'brtop_meetings', 'm', $qb->expr()->eq('m.id', 'r.meeting_id'))
            ->where($qb->expr()->eq('r.user_uid', $qb->createNamedParameter($uid)))
            ->orderBy('r.id', 'ASC')->setMaxResults($limit)->executeQuery()->fetchAll();
    }

    private function meetings(string $uid, int $limit): array {
        if ($limit < 1) return [];
        $qb = $this->db->getQueryBuilder();
        return $qb->select('id','meeting_date','meeting_type','status','invitation_status','created_at')
            ->from('brtop_meetings')->where($qb->expr()->eq('owner_uid', $qb->createNamedParameter($uid)))
            ->orderBy('id', 'ASC')->setMaxResults($limit)->executeQuery()->fetchAll();
    }

    private function documents(string $uid, int $limit): array {
        if ($limit < 1) return [];
        $qb = $this->db->getQueryBuilder();
        return $qb->select('d.id','d.document_type','d.created_at')
            ->from('brtop_documents', 'd')
            ->innerJoin('d', 'brtop_meetings', 'm', $qb->expr()->eq('m.id', 'd.meeting_id'))
            ->where($qb->expr()->eq('m.owner_uid', $qb->createNamedParameter($uid)))
            ->orderBy('d.id', 'ASC')->setMaxResults($limit)->executeQuery()->fetchAll();
    }

    private function activities(string $table, string $uidColumn, string $dateColumn, string $activity, string $uid, int $limit): array {
        if ($limit < 1) return [];
        $qb = $this->db->getQueryBuilder();
        $rows = $qb->select('id', $dateColumn)->from($table)
            ->where($qb->expr()->eq($uidColumn, $qb->createNamedParameter($uid)))
            ->orderBy('id', 'ASC')->setMaxResults($limit)->executeQuery()->fetchAll();
        return array_map(static fn(array $row): array => ['id'=>$row['id'], 'activity'=>$activity, 'occurred_at'=>$row[$dateColumn]], $rows);
    }
}
