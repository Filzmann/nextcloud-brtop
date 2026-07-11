<?php

declare(strict_types=1);

require __DIR__ . '/../helpers.php';
require __DIR__ . '/../../../localbase/lib/Model/ModelApiTrait.php';
require __DIR__ . '/../../lib/Model/AgendaItem.php';
require __DIR__ . '/../../lib/Model/Meeting.php';
require __DIR__ . '/../../lib/Model/ProtocolBlock.php';
require __DIR__ . '/../../lib/Service/AgendaService.php';
require __DIR__ . '/../../lib/Service/AgendaAttachmentService.php';
require __DIR__ . '/../../lib/Service/DocumentDateFormatter.php';
require __DIR__ . '/../../lib/Service/InvitationContentService.php';
require __DIR__ . '/../../lib/Service/DocumentContentService.php';

use OCA\BrTop\Model\AgendaItem;
use OCA\BrTop\Model\Meeting;
use OCA\BrTop\Model\ProtocolBlock;
use OCA\BrTop\Service\AgendaAttachmentService;
use OCA\BrTop\Service\AgendaService;
use OCA\BrTop\Service\DocumentContentService;
use OCA\BrTop\Service\DocumentDateFormatter;
use OCA\BrTop\Service\InvitationContentService;
use function OCA\BrTop\Tests\assertContainsString;

$agendaService = (new ReflectionClass(AgendaService::class))->newInstanceWithoutConstructor();
$attachmentService = new AgendaAttachmentService();
$dateFormatter = new DocumentDateFormatter();
$invitationContentService = new InvitationContentService($agendaService, $attachmentService, $dateFormatter);
$contentService = new DocumentContentService($agendaService, $invitationContentService, $dateFormatter);

$meeting = new Meeting([
    'id' => 3,
    'title' => 'Ordentliche BR-Sitzung',
    'meeting_date' => '2026-07-07',
    'meeting_time' => '10:00',
    'location' => 'BR-Büro',
]);

$top = new AgendaItem([
    'id' => 7,
    'meeting_id' => 3,
    'position' => 1,
    'type' => 'personnel_99',
    'subject' => 'Einstellung Test',
    'person_name' => 'Test Person',
    'legal_basis' => '§ 99 BetrVG',
    'requires_resolution' => 1,
    'resolution_count' => 2,
    'resolution_text' => "Der Betriebsrat stimmt der Einstellung zu.\nDer Betriebsrat verweigert die Zustimmung.",
    'agenda_number' => '2.1.1',
    'protocol_blocks' => [
        new ProtocolBlock(['content' => 'Der Betriebsrat berät den Vorgang.']),
    ],
]);

$email = $contentService->invitationEmail($meeting, [$top], [
    [
        'snapshot_position' => 1,
        'user_uid' => 'simon',
        'display_name' => 'Simon',
        'email' => 'simon@example.invalid',
        'member_role' => 'regular',
        'invitation_type' => 'absent',
        'list_name' => 'Liste Zukunft',
        'list_seats' => 10,
        'list_rank' => 1,
        'gender' => 'male',
        'minority_gender' => 'female',
        'minority_minimum_seats' => 1,
    ],
    [
        'snapshot_position' => 2,
        'user_uid' => 'nora',
        'display_name' => 'Nora',
        'email' => 'nora@example.invalid',
        'member_role' => 'replacement',
        'invitation_type' => 'replacement',
        'list_name' => 'Liste Zukunft',
        'list_seats' => 10,
        'list_rank' => 11,
        'gender' => 'female',
        'minority_gender' => 'female',
        'minority_minimum_seats' => 1,
        'replacement_for_name' => 'Clara Neumann',
    ],
]);
$protocol = $contentService->protocolTemplate($meeting, [$top]);
$resolution = $contentService->resolutionDocument($meeting, $top, 2);

assertContainsString('Sitzung: Ordentliche BR-Sitzung', $email, 'Invitation email should accept Meeting models.');
assertContainsString('Geladene BR-Mitglieder: 0', $email, 'Invitation email should not count confirmed absent regular members as invited.');
assertContainsString('Geladene Nachrücker*innen: 1', $email, 'Invitation email should count replacements separately.');
assertContainsString('2.1.1. Einstellung Test', $email, 'Invitation email should accept AgendaItem models.');
assertContainsString('2 Beschlüsse vorgesehen', $email, 'Invitation email should show multiple resolutions.');
assertContainsString('## 2.1.1. Einstellung Test', $protocol, 'Protocol template should accept AgendaItem models.');
assertContainsString('Der Betriebsrat berät den Vorgang.', $protocol, 'Protocol template should use protocol blocks from AgendaItem models.');
assertContainsString('1. Der Betriebsrat stimmt der Einstellung zu.', $protocol, 'Protocol template should list the first custom resolution question.');
assertContainsString('2. Der Betriebsrat verweigert die Zustimmung.', $protocol, 'Protocol template should list the second custom resolution question.');
assertContainsString('**Beschluss:** 2 von 2', $resolution, 'Resolution document should accept Meeting and AgendaItem models.');
assertContainsString('Der Betriebsrat verweigert die Zustimmung.', $resolution, 'Resolution document should use the requested resolution question.');

$missingQuestionsTop = new AgendaItem([
    'id' => 8,
    'meeting_id' => 3,
    'position' => 2,
    'type' => 'personnel_102',
    'subject' => 'Anhörung Test',
    'person_name' => 'Test Person',
    'legal_basis' => '§ 102 BetrVG',
    'requires_resolution' => 1,
    'resolution_count' => 3,
    'resolution_text' => 'Der Betriebsrat widerspricht der Kündigung.',
    'agenda_number' => '2.1.2',
]);
$missingQuestionsProtocol = $contentService->protocolTemplate($meeting, [$missingQuestionsTop]);
$missingQuestionsResolution = $contentService->resolutionDocument($meeting, $missingQuestionsTop, 3);

assertContainsString('1. Der Betriebsrat widerspricht der Kündigung.', $missingQuestionsProtocol, 'Protocol template should use provided resolution questions first.');
assertContainsString('2. Beschlussfrage 2 ergänzen.', $missingQuestionsProtocol, 'Protocol template should add placeholders for missing questions.');
assertContainsString('3. Beschlussfrage 3 ergänzen.', $missingQuestionsProtocol, 'Protocol template should add all missing placeholders.');
assertContainsString('**Beschluss:** 3 von 3', $missingQuestionsResolution, 'Resolution document should label the selected resolution index.');
assertContainsString('Beschlussfrage 3 ergänzen.', $missingQuestionsResolution, 'Resolution document should use placeholder questions when needed.');

echo 'DocumentContentService smoke tests passed' . PHP_EOL;
