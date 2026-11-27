<?php

declare(strict_types=1);

use OCA\BrTop\Repository\DocumentTemplateRepositoryInterface;
use OCA\BrTop\Service\DocumentTemplateEngine;
use OCA\BrTop\Service\DocumentTemplateService;
use OCA\BrTop\Service\OdtTemplateValidator;
use function OCA\BrTop\Tests\assertContainsString;
use function OCA\BrTop\Tests\assertSameValue;

$repository = new class implements DocumentTemplateRepositoryInterface {
    public array $rows = [];

    public function createDraft(string $templateKey, string $format, string $content): array {
        $version = 1;
        foreach ($this->rows as $row) {
            if ($row['template_key'] === $templateKey) {
                $version = max($version, (int)$row['version_no'] + 1);
            }
        }
        $row = [
            'template_key' => $templateKey,
            'version_no' => $version,
            'format' => $format,
            'content' => $content,
            'status' => 'draft',
            'created_at' => '2026-10-22 12:00:00',
            'approved_at' => null,
        ];
        $this->rows[] = $row;
        return $row;
    }

    public function approve(string $templateKey, int $version): array {
        foreach ($this->rows as &$row) {
            if ($row['template_key'] === $templateKey && (int)$row['version_no'] === $version) {
                $row['status'] = 'approved';
                $row['approved_at'] = '2026-10-22 12:01:00';
                return $row;
            }
        }
        throw new InvalidArgumentException('Vorlagenversion nicht gefunden.');
    }

    public function find(string $templateKey, int $version): ?array {
        foreach ($this->rows as $row) {
            if ($row['template_key'] === $templateKey && (int)$row['version_no'] === $version) {
                return $row;
            }
        }
        return null;
    }

    public function approvedDescending(string $templateKey): array {
        $rows = array_values(array_filter(
            $this->rows,
            static fn(array $row): bool => $row['template_key'] === $templateKey && $row['status'] === 'approved'
        ));
        usort($rows, static fn(array $a, array $b): int => (int)$b['version_no'] <=> (int)$a['version_no']);
        return $rows;
    }

    public function metadata(): array {
        return array_map(static function (array $row): array {
            unset($row['content']);
            return $row;
        }, $this->rows);
    }
};

$service = new DocumentTemplateService(
    $repository,
    new DocumentTemplateEngine(),
    new OdtTemplateValidator(),
    dirname(__DIR__, 2) . '/templates/odt/protokoll-template.odt'
);

$builtIn = $service->active('protocol_markdown');
assertSameValue(0, $builtIn['version'], 'The compatible built-in template should be active before any approval.');
assertSameValue('built_in', $builtIn['status'], 'The built-in fallback should be explicit.');

$draft = $service->saveDraft('protocol_markdown', "# Eigener Kopf\n\n{{PROTOCOL_BODY}}\n");
assertSameValue(1, $draft['version'], 'The first persisted draft should receive version one.');
assertSameValue(0, $service->active('protocol_markdown')['version'], 'A draft must never affect generated documents.');

$approved = $service->approve('protocol_markdown', 1);
assertSameValue('approved', $approved['status'], 'Approval should publish the selected immutable version.');
assertSameValue(1, $service->active('protocol_markdown')['version'], 'The latest valid approved version should become active.');
$repository->rows[0]['created_at'] = new DateTimeImmutable('2026-10-22T12:00:00+00:00');
assertSameValue('2026-10-22T12:00:00+00:00', $service->metadata()[0]['createdAt'], 'Template metadata timestamps should be JSON-safe and stable.');

$repository->rows[] = [
    'template_key' => 'protocol_markdown',
    'version_no' => 2,
    'format' => 'markdown',
    'content' => '{{UNKNOWN}}',
    'status' => 'approved',
    'created_at' => '2026-10-22 12:02:00',
    'approved_at' => '2026-10-22 12:03:00',
];
$fallback = $service->active('protocol_markdown');
assertSameValue(1, $fallback['version'], 'A corrupt newest approval should fall back to the last valid approved version.');
$repository->rows[] = [
    'template_key' => 'protocol_markdown',
    'version_no' => 3,
    'format' => 'markdown',
    'content' => '{{PROTOCOL_BODY}}',
    '_content_decode_error' => true,
    'status' => 'approved',
    'created_at' => '2026-10-22 12:04:00',
    'approved_at' => '2026-10-22 12:05:00',
];
$decodeFallback = $service->active('protocol_markdown');
assertSameValue(1, $decodeFallback['version'], 'A Base64-corrupt newest approval must fall back instead of accepting its raw storage value.');
assertContainsString('Eigener Kopf', $service->renderVersion('protocol_markdown', 1, ['PROTOCOL_BODY' => 'Inhalt'])['content'], 'Historical versions should remain reproducible.');
$preview = $service->preview('invitation_email', "{{SALUTATION}}\n{{MEETING_DETAILS}}\n{{AGENDA}}\n");
assertSameValue('preview', $preview['status'], 'Preview should be explicitly non-persistent.');
assertContainsString('27.10.2026', $preview['content'], 'Preview should render neutral sample meeting data.');
assertSameValue(3, count($repository->rows), 'Preview must not persist or approve an additional version.');

try {
    $service->approve('protocol_markdown', 99);
    throw new RuntimeException('Unknown versions must not be approved.');
} catch (InvalidArgumentException $e) {
    assertContainsString('nicht gefunden', $e->getMessage(), 'Unknown approval targets should be rejected precisely.');
}

echo 'DocumentTemplateService tests passed' . PHP_EOL;
