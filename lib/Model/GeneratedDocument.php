<?php

declare(strict_types=1);

namespace OCA\BrTop\Model;

use OCA\LocalBase\Model\ModelApiTrait;
use OCP\IL10N;

class GeneratedDocument {
    use ModelApiTrait;

    public ?int $id;
    public int $meetingId;
    public string $documentType;
    public string $title;
    public string $filePath;
    public ?string $templateKey;
    public ?int $templateVersion;
    public string $createdAt;

    public function __construct(array $data = [], private ?IL10N $l10n = null) {
        $this->id = isset($data['id']) ? (int)$data['id'] : null;
        $this->meetingId = (int)($data['meeting_id'] ?? $data['meetingId'] ?? 0);
        $this->documentType = (string)($data['document_type'] ?? $data['documentType'] ?? '');
        $this->title = (string)($data['title'] ?? '');
        $this->filePath = (string)($data['file_path'] ?? $data['filePath'] ?? '');
        $templateKey = $data['template_key'] ?? $data['templateKey'] ?? null;
        $this->templateKey = $templateKey === null || $templateKey === '' ? null : (string)$templateKey;
        $templateVersion = $data['template_version'] ?? $data['templateVersion'] ?? null;
        $this->templateVersion = $templateVersion === null || $templateVersion === '' ? null : (int)$templateVersion;
        $this->createdAt = (string)($data['created_at'] ?? $data['createdAt'] ?? '');
    }

    /** Lokalisierung fachlicher Texte; im Test ohne IL10N identity. */
    private function t(string $text): string
    {
        return $this->l10n?->t($text) ?? $text;
    }

    public function displayTitle(): string {
        return trim($this->title) !== '' ? $this->title : $this->typeLabel();
    }

    public function typeLabel(): string {
        return match ($this->documentType) {
            'invitation_email' => $this->t('Einladung E-Mail'),
            'invitation_markdown' => 'Ladung',
            'invitation_recipients' => 'Ladungsliste',
            'protocol_markdown' => 'Protokollvorlage',
            'protocol_odt' => $this->t('Protokollvorlage ODT'),
            'resolution_markdown' => 'Beschlussdokument',
            default => $this->t('Dokument'),
        };
    }

    public function toRepositoryData(): array {
        return [
            'id' => $this->id,
            'meeting_id' => $this->meetingId,
            'document_type' => $this->documentType,
            'title' => $this->title,
            'file_path' => $this->filePath,
            'template_key' => $this->templateKey,
            'template_version' => $this->templateVersion,
            'created_at' => $this->createdAt,
        ];
    }

    public function toArray(): array {
        return $this->toRepositoryData();
    }
}
