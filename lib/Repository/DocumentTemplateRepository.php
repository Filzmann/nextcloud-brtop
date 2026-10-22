<?php

declare(strict_types=1);

namespace OCA\BrTop\Repository;

use DateTimeImmutable;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

final class DocumentTemplateRepository implements DocumentTemplateRepositoryInterface {
    public function __construct(private IDBConnection $db) {
    }

    public function createDraft(string $templateKey, string $format, string $content): array {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->db->beginTransaction();
            try {
                $version = $this->nextVersion($templateKey);
                $qb = $this->db->getQueryBuilder();
                $qb->insert('brt_doc_templates')->values([
                    'template_key' => $qb->createNamedParameter($templateKey),
                    'version_no' => $qb->createNamedParameter($version, IQueryBuilder::PARAM_INT),
                    'format' => $qb->createNamedParameter($format),
                    'content' => $qb->createNamedParameter(base64_encode($content)),
                    'status' => $qb->createNamedParameter('draft'),
                    'created_at' => $qb->createNamedParameter(new DateTimeImmutable(), IQueryBuilder::PARAM_DATETIME_IMMUTABLE),
                    'approved_at' => $qb->createNamedParameter(null, IQueryBuilder::PARAM_NULL),
                ]);
                $qb->executeStatement();
                $this->db->commit();
                return $this->find($templateKey, $version) ?? throw new \RuntimeException('Gespeicherte Vorlagenversion wurde nicht gefunden.');
            } catch (\Throwable $e) {
                $this->db->rollBack();
                if ($attempt === 2) {
                    throw $e;
                }
            }
        }

        throw new \RuntimeException('Vorlagenversion konnte nicht angelegt werden.');
    }

    public function approve(string $templateKey, int $version): array {
        $existing = $version < 1 ? null : $this->find($templateKey, $version);
        if ($existing === null) {
            throw new \InvalidArgumentException('Vorlagenversion nicht gefunden.');
        }
        if (($existing['status'] ?? '') === 'approved') {
            return $existing;
        }
        if (($existing['status'] ?? '') !== 'draft') {
            throw new \InvalidArgumentException('Nur Entwürfe können freigegeben werden.');
        }
        $qb = $this->db->getQueryBuilder();
        $qb->update('brt_doc_templates')
            ->set('status', $qb->createNamedParameter('approved'))
            ->set('approved_at', $qb->createNamedParameter(new DateTimeImmutable(), IQueryBuilder::PARAM_DATETIME_IMMUTABLE))
            ->where($qb->expr()->eq('template_key', $qb->createNamedParameter($templateKey)))
            ->andWhere($qb->expr()->eq('version_no', $qb->createNamedParameter($version, IQueryBuilder::PARAM_INT)));
        $qb->executeStatement();

        return $this->find($templateKey, $version) ?? throw new \RuntimeException('Freigegebene Vorlagenversion wurde nicht gefunden.');
    }

    public function find(string $templateKey, int $version): ?array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')->from('brt_doc_templates')
            ->where($qb->expr()->eq('template_key', $qb->createNamedParameter($templateKey)))
            ->andWhere($qb->expr()->eq('version_no', $qb->createNamedParameter($version, IQueryBuilder::PARAM_INT)));
        $row = $qb->executeQuery()->fetch();
        return is_array($row) ? $this->decodeContent($row) : null;
    }

    public function approvedDescending(string $templateKey): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')->from('brt_doc_templates')
            ->where($qb->expr()->eq('template_key', $qb->createNamedParameter($templateKey)))
            ->andWhere($qb->expr()->eq('status', $qb->createNamedParameter('approved')))
            ->orderBy('version_no', 'DESC');
        return array_map(
            fn(array $row): array => $this->decodeContent($row, true),
            $qb->executeQuery()->fetchAll()
        );
    }

    public function metadata(): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('template_key', 'version_no', 'format', 'status', 'created_at', 'approved_at')
            ->from('brt_doc_templates')
            ->orderBy('template_key', 'ASC')
            ->addOrderBy('version_no', 'DESC');
        return $qb->executeQuery()->fetchAll();
    }

    private function nextVersion(string $templateKey): int {
        $qb = $this->db->getQueryBuilder();
        $qb->selectAlias($qb->func()->max('version_no'), 'max_version')
            ->from('brt_doc_templates')
            ->where($qb->expr()->eq('template_key', $qb->createNamedParameter($templateKey)));
        $value = $qb->executeQuery()->fetchOne();
        return max(1, (int)$value + 1);
    }

    private function decodeContent(array $row, bool $tolerateCorruption = false): array {
        $decoded = base64_decode((string)($row['content'] ?? ''), true);
        if (!is_string($decoded)) {
            if ($tolerateCorruption) {
                $row['content'] = '';
                $row['_content_decode_error'] = true;
                return $row;
            }
            throw new \RuntimeException('Gespeicherter Vorlageninhalt ist beschädigt.');
        }
        $row['content'] = $decoded;
        return $row;
    }
}
