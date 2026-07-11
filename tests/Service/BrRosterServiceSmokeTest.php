<?php

declare(strict_types=1);

require __DIR__ . '/../helpers.php';
require __DIR__ . '/../../lib/Service/BrRosterService.php';

use OCA\BrTop\Service\BrRosterService;
use function OCA\BrTop\Tests\assertSameValue;

$service = new BrRosterService();
$council = $service->demoCouncil();
$recipients = $service->demoInvitationRecipients(7, [
    'brtop-lz-03' => ['reason' => 'AU', 'source' => 'calendar'],
    'brtop-lz-06' => ['reason' => 'FoBi', 'source' => 'calendar'],
    'brtop-lz-09' => ['reason' => 'keine Angabe', 'source' => 'calendar'],
]);
$regularWomen = count(array_filter(
    $council['members'],
    static fn(array $member): bool => $member['role'] === 'regular' && $member['gender'] === 'female'
));
$regularMen = count(array_filter(
    $council['members'],
    static fn(array $member): bool => $member['role'] === 'regular' && $member['gender'] === 'male'
));
$listOneReplacements = count(array_filter(
    $council['members'],
    static fn(array $member): bool => $member['role'] === 'replacement' && $member['list_name'] === 'Liste Zukunft'
));
$listTwoReplacements = count(array_filter(
    $council['members'],
    static fn(array $member): bool => $member['role'] === 'replacement' && $member['list_name'] === 'Liste Dialog'
));

assertSameValue(13, $council['councilSize'], 'Demo council should model a 13-seat works council.');
assertSameValue('female', $council['minorityGender'], 'Demo council should keep female as minority gender.');
assertSameValue('BR-Abwesenheiten', $council['absenceCalendarName'], 'Demo council should name the absence calendar.');
assertSameValue(true, $regularWomen < $regularMen, 'Demo council should make women the minority gender among regular members.');
assertSameValue('admin', $council['members'][11]['user_uid'], 'Demo council should model admin as a regular member.');
assertSameValue('male', $council['members'][11]['gender'], 'Admin should be male in the demo council.');
assertSameValue('Liste Dialog', $council['members'][11]['list_name'], 'Admin should belong to the smaller list.');
assertSameValue(8, $listOneReplacements, 'The large list should expose eight replacements.');
assertSameValue(8, $listTwoReplacements, 'The small list should expose eight replacements.');
assertSameValue(true, $service->isDemoUserId('brtop-lz-01'), 'Demo roster should identify BRTop demo user ids.');
assertSameValue(true, $service->isDemoUserId('admin'), 'Demo roster should identify admin as a demo council user.');
assertSameValue(false, $service->isDemoUserId('simon'), 'Demo roster should not treat unrelated user ids as demo user ids.');
assertSameValue('Liste Zukunft', $council['lists'][0]['name'], 'Demo council should keep the first list name.');
assertSameValue(10, $council['lists'][0]['seats'], 'Demo council should keep the first list seat count.');
assertSameValue('Liste Dialog', $council['lists'][1]['name'], 'Demo council should keep the second list name.');
assertSameValue(3, $council['lists'][1]['seats'], 'Demo council should keep the second list seat count.');
assertSameValue(15, count($recipients), 'Demo invitation should include 13 regular members plus two excused replacements.');
assertSameValue('admin', $recipients[0]['user_uid'], 'Demo invitation should sort regular members by last name.');
assertSameValue('brtop-lz-06', $recipients[1]['user_uid'], 'Becker should follow Admin in the alphabetical regular list.');
assertSameValue('AU', $recipients[7]['absence_reason'], 'Demo invitation should contain an AU absence from calendar data.');
assertSameValue('entschuldigt', $recipients[7]['absence_excused'], 'AU should be treated as excused.');
assertSameValue('keine Angabe', $recipients[3]['absence_reason'], 'Demo invitation should contain a no-information absence from calendar data.');
assertSameValue('unentschuldigt', $recipients[3]['absence_excused'], 'No information should be treated as unexcused.');
assertSameValue('replacement', $recipients[13]['member_role'], 'The first replacement should be marked as replacement.');
assertSameValue('brtop-lz-03', $recipients[13]['replacement_for_uid'], 'The first replacement should reference the excused regular member.');
assertSameValue('female', $recipients[13]['gender'], 'A female absent regular member should be replaced by a female replacement when available.');

echo 'BrRosterService smoke tests passed' . PHP_EOL;
