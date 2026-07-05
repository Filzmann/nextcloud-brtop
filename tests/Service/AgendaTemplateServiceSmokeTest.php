<?php

declare(strict_types=1);

require __DIR__ . '/../helpers.php';
require __DIR__ . '/../../lib/Service/BrtopSettingsService.php';
require __DIR__ . '/../../lib/Service/AgendaAttachmentService.php';
require __DIR__ . '/../../lib/Service/AgendaTemplateService.php';

use OCA\BrTop\Service\AgendaTemplateService;
use OCA\BrTop\Service\BrtopSettingsService;
use function OCA\BrTop\Tests\assertContainsString;
use function OCA\BrTop\Tests\assertSameValue;
use function OCA\BrTop\Tests\assertThrows;

$emptySettings = new class extends BrtopSettingsService {
    public function __construct() {
    }

    public function regularAgendaTemplateJson(): string {
        return '';
    }
};

$service = new AgendaTemplateService($emptySettings);
$defaultItems = $service->regularBrMeetingItems();

assertSameValue(9, count($defaultItems), 'Default regular BR meetings should include the expected TOP skeleton.');
assertSameValue('Protokolle', $defaultItems[0]['subject'], 'Default template should start with protocols.');
assertSameValue('personnel', $defaultItems[2]['key'], 'Default template should include a personnel section key.');
assertSameValue('personnel', $defaultItems[3]['parent'], 'Personnel 99 TOP should be below the personnel section.');
assertSameValue('report', $defaultItems[8]['agendaItemKind'], 'Consultation report should be a report item.');

$customJson = json_encode([
    [
        'key' => 'root',
        'type' => 'organisation',
        'subject' => 'Root TOP',
        'level' => 1,
        'agendaItemKind' => 'section',
    ],
    [
        'type' => 'personnel_99',
        'subject' => 'Einstellung',
        'parent' => 'root',
        'level' => 2,
        'requiresResolution' => true,
        'resolutionCount' => 3,
        'resolutionText' => 'Beschlussfrage',
        'invitationNote' => " Bitte lesen \n\n vorab ",
        'attachmentPaths' => " /BR/Fall.pdf \n/BR/Fall.pdf ",
    ],
], JSON_THROW_ON_ERROR);

$customSettings = new class($customJson) extends BrtopSettingsService {
    public function __construct(private string $json) {
    }

    public function regularAgendaTemplateJson(): string {
        return $this->json;
    }
};

$customService = new AgendaTemplateService($customSettings);
$customItems = $customService->regularBrMeetingItems();

assertSameValue('root', $customItems[1]['parent'], 'Custom template parents should be preserved.');
assertSameValue('resolution', $customItems[1]['agendaItemKind'], 'Resolution templates should force resolution kind.');
assertSameValue(true, $customItems[1]['requiresResolution'], 'Resolution templates should require resolutions.');
assertSameValue(3, $customItems[1]['resolutionCount'], 'Resolution count should survive template normalization.');
assertSameValue("Bitte lesen\nvorab", $customItems[1]['invitationNote'], 'Invitation notes should survive template normalization.');
assertSameValue('/BR/Fall.pdf', $customItems[1]['attachmentPaths'], 'Attachment paths should be normalized and deduplicated.');

$normalizedJson = $customService->normalizeJsonForStorage($customJson);
assertContainsString('"resolutionCount": 3', $normalizedJson, 'Stored template JSON should keep resolution count.');
assertContainsString('"invitationNote": "Bitte lesen\nvorab"', $normalizedJson, 'Stored template JSON should keep invitation notes.');
assertContainsString('"attachmentPaths": "/BR/Fall.pdf"', $normalizedJson, 'Stored template JSON should keep attachment paths.');

assertThrows(
    static fn() => $service->normalizeJsonForStorage('[{"subject":"Kind","parent":"missing","level":2}]'),
    \InvalidArgumentException::class,
    'Templates with unknown parents should be rejected.'
);

echo 'AgendaTemplateService smoke tests passed' . PHP_EOL;
