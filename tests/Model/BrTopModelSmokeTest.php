<?php

declare(strict_types=1);


use OCA\BrTop\Model\AgendaItem;
use OCA\BrTop\Model\GeneratedDocument;
use OCA\BrTop\Model\Meeting;
use OCA\BrTop\Model\ProtocolBlock;
use function OCA\BrTop\Tests\assertSameValue;

$protocolBlock = new ProtocolBlock([
    'id' => 12,
    'meeting_id' => 3,
    'top_id' => 7,
    'block_position' => 1,
    'block_type' => 'text',
    'content' => 'Protokollinhalt',
]);

$top = new AgendaItem([
    'id' => 7,
    'meeting_id' => 3,
    'position' => 2,
    'type' => 'personnel_99',
    'subject' => 'Einstellung Test',
    'person_name' => 'Test Person',
    'legal_basis' => '§ 99 BetrVG',
    'requires_resolution' => 1,
    'resolution_count' => 2,
    'agenda_number' => '2.1',
    'protocol_blocks' => [$protocolBlock],
]);

assertSameValue('2.1', $top->number(), 'AgendaItem should prefer explicit agenda numbers.');
assertSameValue('resolution', $top->kind(), 'Resolution TOPs should expose their kind.');
assertSameValue(true, $top->isResolutionItem(), 'Resolution TOPs should be resolution-relevant.');
assertSameValue(2, $top->resolutionCount(), 'Resolution count should be preserved.');
assertSameValue(true, $protocolBlock->isTextBlock(), 'ProtocolBlock should expose text block checks.');
assertSameValue(
    'Wer verweigert die Zustimmung zu Einstellung Test und widerspricht ihr damit?',
    $top->defaultResolutionText(),
    'Personnel §99 TOPs should build the expected default resolution question.'
);

$document = new GeneratedDocument([
    'id' => 1,
    'meeting_id' => 3,
    'document_type' => 'invitation_markdown',
    'title' => '',
    'file_path' => 'BR-Sitzungen/2026-07-07/01_Ladung.md',
]);

$meeting = new Meeting([
    'id' => 3,
    'legislature_id' => 2,
    'owner_uid' => 'simon',
    'title' => '',
    'meeting_date' => '2026-07-07',
    'meeting_time' => '10:00',
    'meeting_type' => 'regular_br',
    'tops' => [$top],
    'documents' => [$document],
    'invitation_recipients' => [
        ['user_uid' => 'simon', 'display_name' => 'Simon'],
    ],
]);

$mappedMeeting = Meeting::get($meeting->toArray());
$mappedMeetings = Meeting::get_all([$meeting->toArray()]);

$payload = $meeting->toArray();

assertSameValue(true, $mappedMeeting instanceof Meeting, 'Meeting::get should hydrate API data.');
assertSameValue(1, count($mappedMeetings), 'Meeting::get_all should hydrate API lists.');
assertSameValue(3, $mappedMeeting->toArray()['id'], 'Model toArray should keep the API payload shape.');
assertSameValue(2, $mappedMeeting->toArray()['legislature_id'], 'Meeting should preserve its bound legislature.');
assertSameValue('ohne Titel', $meeting->displayTitle(), 'Meeting displayTitle should have a fallback.');
assertSameValue(true, $meeting->isRegularBrMeeting(), 'Meeting should expose regular BR sessions.');
assertSameValue('Ladung', $document->displayTitle(), 'GeneratedDocument should expose display titles.');
assertSameValue(7, $payload['tops'][0]['id'], 'Meeting API payload should include TOP objects as arrays.');
assertSameValue('invitation_markdown', $payload['documents'][0]['document_type'], 'Meeting API payload should include document objects as arrays.');
assertSameValue('simon', $payload['invitation_recipients'][0]['user_uid'], 'Meeting API payload should include invitation recipients.');

echo 'BRTop model smoke tests passed' . PHP_EOL;
