<?php

declare(strict_types=1);

namespace OCA\BrTop\Service;

use OCA\BrTop\Repository\DocumentTemplateRepositoryInterface;

final class DocumentTemplateService implements DocumentTemplateProvider {
    private const TEXT_DEFAULTS = [
        'invitation_email' => "{{SALUTATION}}\n\n{{INVITATION_INTRO}}\n\n{{MEETING_DETAILS}}\n\n{{RECIPIENT_SUMMARY}}\n\nTagesordnung:\n\n{{AGENDA}}\n\n{{CLOSING}}\n{{SENDER}}\n",
        'invitation_markdown' => "# {{SUBJECT}}\n\n```text\n{{EMAIL_BODY}}\n```\n",
        'protocol_markdown' => "{{PROTOCOL_BODY}}\n",
        'resolution_markdown' => "{{RESOLUTION_BODY}}\n",
    ];

    public function __construct(
        private DocumentTemplateRepositoryInterface $repository,
        private DocumentTemplateEngine $engine,
        private OdtTemplateValidator $odtValidator,
        private string $builtInOdtPath = '',
        private ?BrtopLogger $logger = null
    ) {
        if ($this->builtInOdtPath === '') {
            $this->builtInOdtPath = __DIR__ . '/../../templates/odt/protokoll-template.odt';
        }
    }

    public function active(string $templateKey): array {
        $this->assertKnownKey($templateKey);
        foreach ($this->repository->approvedDescending($templateKey) as $row) {
            try {
                $this->validate($templateKey, (string)$row['content']);
                return $this->publicVersion($row);
            } catch (\Throwable $e) {
                $this->logger?->error('document_template_fallback', $e, [
                    'template_key' => $templateKey,
                    'template_version' => (int)($row['version_no'] ?? 0),
                ]);
            }
        }
        return $this->builtIn($templateKey);
    }

    public function saveDraft(string $templateKey, string $content): array {
        $this->assertKnownKey($templateKey);
        $content = $this->normalize($templateKey, $content);
        return $this->publicVersion($this->repository->createDraft($templateKey, $this->format($templateKey), $content));
    }

    public function approve(string $templateKey, int $version): array {
        $row = $this->repository->find($templateKey, $version);
        if ($row === null) {
            throw new \InvalidArgumentException('Vorlagenversion nicht gefunden.');
        }
        $this->validate($templateKey, (string)$row['content']);
        return $this->publicVersion($this->repository->approve($templateKey, $version));
    }

    public function renderActive(string $templateKey, array $values): array {
        return $this->render($this->active($templateKey), $values);
    }

    public function renderVersion(string $templateKey, int $version, array $values): array {
        if ($version === 0) {
            return $this->render($this->builtIn($templateKey), $values);
        }
        $row = $this->repository->find($templateKey, $version);
        if ($row === null) {
            throw new \InvalidArgumentException('Vorlagenversion nicht gefunden.');
        }
        return $this->render($this->publicVersion($row), $values);
    }

    public function preview(string $templateKey, string $content): array {
        $this->assertKnownKey($templateKey);
        $content = $this->normalize($templateKey, $content);
        $preview = [
            'templateKey' => $templateKey,
            'version' => null,
            'format' => $this->format($templateKey),
            'content' => $content,
            'status' => 'preview',
            'createdAt' => null,
            'approvedAt' => null,
        ];
        if ($preview['format'] === 'odt') {
            return $preview;
        }

        return $this->render($preview, [
            'SALUTATION' => 'Guten Tag,',
            'INVITATION_INTRO' => 'hiermit laden wir zur Sitzung ein.',
            'MEETING_DETAILS' => "Sitzung: Beispielsitzung\nDatum: 27.10.2026\nUhrzeit: 10:00\nOrt: Sitzungsraum",
            'RECIPIENT_SUMMARY' => 'Geladene BR-Mitglieder: 7',
            'AGENDA' => "1. Bericht\n2. Beratung",
            'CLOSING' => 'Viele Grüße',
            'SENDER' => 'Der Betriebsrat',
            'SUBJECT' => 'Ladung zur Sitzung am 27.10.2026',
            'EMAIL_BODY' => 'Beispiel für die Einladungs-E-Mail.',
            'COUNCIL_NAME' => 'Betriebsrat Beispielorganisation',
            'COUNCIL_ADDRESS' => 'Beispielweg 1, 12345 Beispielstadt',
            'ISSUE_PLACE' => 'Beispielstadt',
            'PROTOCOL_BODY' => "# Protokollvorlage\n\nFester fachlicher Protokollblock.",
            'RESOLUTION_BODY' => "# Beschlussdokument\n\nFester fachlicher Beschlussblock.",
        ]);
    }

    public function metadata(): array {
        $metadata = array_map([$this, 'publicVersion'], $this->repository->metadata());
        foreach ($this->keys() as $key) {
            $metadata[] = [
                'templateKey' => $key,
                'version' => 0,
                'format' => $this->format($key),
                'status' => 'built_in',
                'createdAt' => null,
                'approvedAt' => null,
            ];
        }
        return $metadata;
    }

    public function keys(): array {
        return [...$this->engine->keys(), 'protocol_odt'];
    }

    public function templateContent(string $templateKey, int $version): array {
        $item = $version === 0
            ? $this->builtIn($templateKey)
            : $this->publicVersion($this->repository->find($templateKey, $version) ?? throw new \InvalidArgumentException('Vorlagenversion nicht gefunden.'));
        if ($item['format'] === 'odt') {
            $item['content'] = base64_encode($item['content']);
            $item['encoding'] = 'base64';
        }
        return $item;
    }

    private function render(array $version, array $values): array {
        if ($version['format'] === 'odt') {
            throw new \InvalidArgumentException('ODT-Vorlagen werden durch den ODT-Renderer verarbeitet.');
        }
        $version['content'] = $this->engine->render($version['templateKey'], $version['content'], $values);
        return $version;
    }

    private function builtIn(string $templateKey): array {
        $this->assertKnownKey($templateKey);
        if ($templateKey === 'protocol_odt') {
            $content = file_get_contents($this->builtInOdtPath);
            if (!is_string($content)) {
                throw new \RuntimeException('Die eingebaute ODT-Vorlage wurde nicht gefunden.');
            }
        } else {
            $content = self::TEXT_DEFAULTS[$templateKey];
        }
        $this->validate($templateKey, $content);
        return [
            'templateKey' => $templateKey,
            'version' => 0,
            'format' => $this->format($templateKey),
            'content' => $content,
            'status' => 'built_in',
            'createdAt' => null,
            'approvedAt' => null,
        ];
    }

    private function normalize(string $templateKey, string $content): string {
        if ($templateKey === 'protocol_odt') {
            $this->odtValidator->validate($content);
            return $content;
        }
        return $this->engine->validateAndNormalize($templateKey, $content);
    }

    private function validate(string $templateKey, string $content): void {
        $this->normalize($templateKey, $content);
    }

    private function format(string $templateKey): string {
        if ($templateKey === 'protocol_odt') {
            return 'odt';
        }
        return (string)$this->engine->definitionFor($templateKey)['format'];
    }

    private function assertKnownKey(string $templateKey): void {
        if (!in_array($templateKey, $this->keys(), true)) {
            throw new \InvalidArgumentException('Unbekannter Vorlagentyp: ' . $templateKey);
        }
    }

    private function publicVersion(array $row): array {
        if (($row['_content_decode_error'] ?? false) === true) {
            throw new \RuntimeException('Gespeicherter Vorlageninhalt ist beschädigt.');
        }
        return [
            'templateKey' => (string)$row['template_key'],
            'version' => (int)$row['version_no'],
            'format' => (string)$row['format'],
            'content' => (string)($row['content'] ?? ''),
            'status' => (string)$row['status'],
            'createdAt' => $this->dateValue($row['created_at'] ?? null),
            'approvedAt' => $this->dateValue($row['approved_at'] ?? null),
        ];
    }

    private function dateValue(mixed $value): ?string {
        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }
        if ($value === null || $value === '') {
            return null;
        }
        return (string)$value;
    }
}
