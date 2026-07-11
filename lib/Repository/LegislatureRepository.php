<?php

declare(strict_types=1);

namespace OCA\BrTop\Repository;

use DateTimeImmutable;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class LegislatureRepository {
    public function __construct(private IDBConnection $db) {
    }

    public function configuration(int $legislatureId): ?array {
        $legislature = $this->legislatureRow($legislatureId);
        if ($legislature === null) {
            return null;
        }

        $lists = $this->listRows($legislatureId);
        $membersByList = [];
        foreach ($this->memberRows($legislatureId) as $member) {
            $membersByList[(int)$member['list_id']][] = $member;
        }
        foreach ($lists as &$list) {
            $list['members'] = $membersByList[(int)$list['id']] ?? [];
        }
        unset($list);
        $legislature['lists'] = $lists;

        return $legislature;
    }

    public function latest(): ?array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('id')
            ->from('brtop_legislatures')
            ->orderBy('id', 'DESC')
            ->setMaxResults(1);
        $id = $qb->executeQuery()->fetchOne();

        return $id === false ? null : $this->configuration((int)$id);
    }

    public function activeForDate(string $date): ?array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('id')
            ->from('brtop_legislatures')
            ->where($qb->expr()->eq('status', $qb->createNamedParameter('active')))
            ->andWhere($qb->expr()->lte('starts_on', $qb->createNamedParameter($date)))
            ->andWhere($qb->expr()->gte('ends_on', $qb->createNamedParameter($date)))
            ->orderBy('id', 'DESC')
            ->setMaxResults(1);
        $id = $qb->executeQuery()->fetchOne();

        return $id === false ? null : $this->configuration((int)$id);
    }

    public function hasOverlappingActive(string $startsOn, string $endsOn, int $excludeId): bool {
        $qb = $this->db->getQueryBuilder();
        $qb->select('id')
            ->from('brtop_legislatures')
            ->where($qb->expr()->eq('status', $qb->createNamedParameter('active')))
            ->andWhere($qb->expr()->neq('id', $qb->createNamedParameter($excludeId, IQueryBuilder::PARAM_INT)))
            ->andWhere($qb->expr()->lte('starts_on', $qb->createNamedParameter($endsOn)))
            ->andWhere($qb->expr()->gte('ends_on', $qb->createNamedParameter($startsOn)))
            ->setMaxResults(1);

        return $qb->executeQuery()->fetchOne() !== false;
    }

    public function saveDraft(array $configuration, string $uid): int {
        return $this->transactional(function () use ($configuration, $uid): int {
            $id = (int)($configuration['id'] ?? 0);
            if ($id > 0) {
                $current = $this->legislatureRow($id);
                if ($current === null || ($current['status'] ?? '') !== 'draft') {
                    throw new \RuntimeException('Nur eine vorhandene Entwurfslegislatur darf geaendert werden.');
                }
                $this->updateDraftRow($id, $configuration);
                $this->deleteChildren($id);
            } else {
                $id = $this->insertDraftRow($configuration, $uid);
            }

            $this->insertChildren($id, $configuration['lists'] ?? []);
            return $id;
        });
    }

    public function activate(int $legislatureId): void {
        $qb = $this->db->getQueryBuilder();
        $qb->update('brtop_legislatures')
            ->set('status', $qb->createNamedParameter('active'))
            ->set('activated_at', $qb->createNamedParameter(new DateTimeImmutable(), IQueryBuilder::PARAM_DATE))
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($legislatureId, IQueryBuilder::PARAM_INT)))
            ->andWhere($qb->expr()->eq('status', $qb->createNamedParameter('draft')));
        if ($qb->executeStatement() !== 1) {
            throw new \RuntimeException('Die Legislatur konnte nicht aktiviert werden.');
        }
    }

    private function legislatureRow(int $id): ?array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from('brtop_legislatures')
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));
        $row = $qb->executeQuery()->fetch();
        return $row === false ? null : $row;
    }

    private function listRows(int $legislatureId): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from('brtop_electoral_lists')
            ->where($qb->expr()->eq('legislature_id', $qb->createNamedParameter($legislatureId, IQueryBuilder::PARAM_INT)))
            ->orderBy('sort_order', 'ASC');
        return $qb->executeQuery()->fetchAll();
    }

    private function memberRows(int $legislatureId): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from('brtop_roster_members')
            ->where($qb->expr()->eq('legislature_id', $qb->createNamedParameter($legislatureId, IQueryBuilder::PARAM_INT)))
            ->andWhere($qb->expr()->eq('active', $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL)))
            ->orderBy('list_rank', 'ASC');
        return $qb->executeQuery()->fetchAll();
    }

    private function insertDraftRow(array $data, string $uid): int {
        $qb = $this->db->getQueryBuilder();
        $qb->insert('brtop_legislatures')->values($this->legislatureValues($qb, $data) + [
            'status' => $qb->createNamedParameter('draft'),
            'created_by_uid' => $qb->createNamedParameter($uid),
            'created_at' => $qb->createNamedParameter(new DateTimeImmutable(), IQueryBuilder::PARAM_DATE),
        ]);
        $qb->executeStatement();
        return (int)$this->db->lastInsertId('brtop_legislatures');
    }

    private function updateDraftRow(int $id, array $data): void {
        $qb = $this->db->getQueryBuilder();
        $qb->update('brtop_legislatures');
        foreach ($this->legislatureValues($qb, $data) as $column => $value) {
            $qb->set($column, $value);
        }
        $qb->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));
        $qb->executeStatement();
    }

    private function legislatureValues(IQueryBuilder $qb, array $data): array {
        return [
            'name' => $qb->createNamedParameter((string)$data['name']),
            'starts_on' => $qb->createNamedParameter((string)$data['starts_on']),
            'ends_on' => $qb->createNamedParameter((string)$data['ends_on']),
            'election_type' => $qb->createNamedParameter('list'),
            'council_size' => $qb->createNamedParameter((int)$data['council_size'], IQueryBuilder::PARAM_INT),
            'minority_gender' => $qb->createNamedParameter((string)$data['minority_gender']),
            'minority_minimum_seats' => $qb->createNamedParameter((int)$data['minority_minimum_seats'], IQueryBuilder::PARAM_INT),
            'absence_calendar_principal' => $qb->createNamedParameter((string)($data['absence_calendar_principal'] ?? '')),
            'absence_calendar_uri' => $qb->createNamedParameter((string)($data['absence_calendar_uri'] ?? '')),
        ];
    }

    private function deleteChildren(int $legislatureId): void {
        $qb = $this->db->getQueryBuilder();
        $qb->delete('brtop_roster_members')
            ->where($qb->expr()->eq('legislature_id', $qb->createNamedParameter($legislatureId, IQueryBuilder::PARAM_INT)))
            ->executeStatement();
        $qb = $this->db->getQueryBuilder();
        $qb->delete('brtop_electoral_lists')
            ->where($qb->expr()->eq('legislature_id', $qb->createNamedParameter($legislatureId, IQueryBuilder::PARAM_INT)))
            ->executeStatement();
    }

    private function insertChildren(int $legislatureId, array $lists): void {
        foreach (array_values($lists) as $index => $list) {
            $qb = $this->db->getQueryBuilder();
            $qb->insert('brtop_electoral_lists')->values([
                'legislature_id' => $qb->createNamedParameter($legislatureId, IQueryBuilder::PARAM_INT),
                'name' => $qb->createNamedParameter((string)$list['name']),
                'seat_count' => $qb->createNamedParameter((int)$list['seat_count'], IQueryBuilder::PARAM_INT),
                'vote_count' => $qb->createNamedParameter((int)$list['vote_count'], IQueryBuilder::PARAM_INT),
                'sort_order' => $qb->createNamedParameter($index + 1, IQueryBuilder::PARAM_INT),
            ])->executeStatement();
            $listId = (int)$this->db->lastInsertId('brtop_electoral_lists');

            foreach (($list['members'] ?? []) as $member) {
                $qb = $this->db->getQueryBuilder();
                $qb->insert('brtop_roster_members')->values([
                    'legislature_id' => $qb->createNamedParameter($legislatureId, IQueryBuilder::PARAM_INT),
                    'list_id' => $qb->createNamedParameter($listId, IQueryBuilder::PARAM_INT),
                    'user_uid' => $qb->createNamedParameter((string)$member['user_uid']),
                    'display_name' => $qb->createNamedParameter((string)$member['display_name']),
                    'email' => $qb->createNamedParameter((string)($member['email'] ?? '')),
                    'gender' => $qb->createNamedParameter((string)$member['gender']),
                    'member_role' => $qb->createNamedParameter((string)$member['member_role']),
                    'list_rank' => $qb->createNamedParameter((int)$member['list_rank'], IQueryBuilder::PARAM_INT),
                    'active' => $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL),
                ])->executeStatement();
            }
        }
    }

    private function transactional(callable $callback) {
        $this->db->beginTransaction();
        try {
            $result = $callback();
            $this->db->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
