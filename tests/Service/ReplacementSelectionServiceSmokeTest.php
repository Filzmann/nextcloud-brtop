<?php

declare(strict_types=1);

require __DIR__ . '/../helpers.php';
require __DIR__ . '/../../lib/Service/ReplacementSelectionService.php';

use OCA\BrTop\Service\ReplacementSelectionService;
use function OCA\BrTop\Tests\assertSameValue;
use function OCA\BrTop\Tests\assertThrows;

function replacement_legislature(): array {
    $lists = [
        ['id' => 1, 'name' => 'Liste Zukunft', 'seat_count' => 7, 'vote_count' => 700, 'members' => []],
        ['id' => 2, 'name' => 'Liste Dialog', 'seat_count' => 6, 'vote_count' => 600, 'members' => []],
    ];
    for ($index = 1; $index <= 13; $index++) {
        $listIndex = $index <= 7 ? 0 : 1;
        $rank = $listIndex === 0 ? $index : $index - 7;
        $lists[$listIndex]['members'][] = [
            'id' => $index,
            'user_uid' => 'regular-' . $index,
            'display_name' => 'Feste Person ' . str_pad((string)$index, 2, '0', STR_PAD_LEFT),
            'email' => 'regular-' . $index . '@example.invalid',
            'gender' => $index <= 7 ? 'female' : 'male',
            'member_role' => 'regular',
            'list_rank' => $rank,
        ];
    }
    $lists[0]['members'][] = [
        'id' => 101, 'user_uid' => 'replacement-male', 'display_name' => 'Ersatz Mann',
        'email' => '', 'gender' => 'male', 'member_role' => 'replacement', 'list_rank' => 8,
    ];
    $lists[0]['members'][] = [
        'id' => 102, 'user_uid' => 'replacement-female', 'display_name' => 'Ersatz Frau',
        'email' => '', 'gender' => 'female', 'member_role' => 'replacement', 'list_rank' => 9,
    ];
    $lists[1]['members'][] = [
        'id' => 201, 'user_uid' => 'replacement-list-2', 'display_name' => 'Ersatz Liste Zwei',
        'email' => '', 'gender' => 'female', 'member_role' => 'replacement', 'list_rank' => 7,
    ];

    return [
        'id' => 5,
        'minority_gender' => 'female',
        'minority_minimum_seats' => 7,
        'lists' => $lists,
    ];
}

$service = new ReplacementSelectionService();
$legislature = replacement_legislature();

$femaleAbsent = $service->recipients($legislature, [1]);
$replacement = array_values(array_filter(
    $femaleAbsent,
    static fn(array $recipient): bool => ($recipient['invitation_type'] ?? '') === 'replacement'
))[0];
assertSameValue('replacement-female', $replacement['user_uid'], 'A female replacement must preserve the configured seven-seat minority minimum.');
assertSameValue('regular-1', $replacement['replacement_for_uid'], 'The snapshot must record the replaced regular member.');

$maleAbsent = $service->recipients($legislature, [8]);
$maleReplacement = array_values(array_filter(
    $maleAbsent,
    static fn(array $recipient): bool => ($recipient['invitation_type'] ?? '') === 'replacement'
))[0];
assertSameValue('replacement-list-2', $maleReplacement['user_uid'], 'A majority-gender vacancy should use the first available candidate from the same list.');

assertThrows(
    static fn() => $service->recipients($legislature, [1, 102, 201]),
    \RuntimeException::class,
    'The policy must fail closed when the minority minimum cannot be restored.'
);

echo 'ReplacementSelectionService smoke tests passed' . PHP_EOL;
