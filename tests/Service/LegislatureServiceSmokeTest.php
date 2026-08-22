<?php

declare(strict_types=1);


use OCA\BrTop\Repository\LegislatureRepository;
use OCA\BrTop\Service\LegislatureService;
use OCA\BrTop\Service\LegislatureValidationService;
use function OCA\BrTop\Tests\assertContainsString;
use function OCA\BrTop\Tests\assertSameValue;
use function OCA\BrTop\Tests\assertThrows;

function legislature_fixture(): array {
    $lists = [
        ['name' => 'Liste Zukunft', 'seat_count' => 7, 'vote_count' => 700, 'members' => []],
        ['name' => 'Liste Dialog', 'seat_count' => 6, 'vote_count' => 600, 'members' => []],
    ];
    for ($index = 1; $index <= 13; $index++) {
        $listIndex = $index <= 7 ? 0 : 1;
        $rank = $listIndex === 0 ? $index : $index - 7;
        $lists[$listIndex]['members'][] = [
            'user_uid' => 'br-member-' . $index,
            'display_name' => 'BR Mitglied ' . $index,
            'email' => 'member-' . $index . '@example.invalid',
            'gender' => $index <= 7 ? 'female' : 'male',
            'member_role' => 'regular',
            'list_rank' => $rank,
        ];
    }
    $lists[0]['members'][] = [
        'user_uid' => 'br-replacement-1',
        'display_name' => 'Ersatz Mitglied 1',
        'email' => 'replacement-1@example.invalid',
        'gender' => 'female',
        'member_role' => 'replacement',
        'list_rank' => 8,
    ];

    return [
        'name' => 'Legislatur 2026-2030',
        'starts_on' => '2026-05-01',
        'ends_on' => '2030-04-30',
        'election_type' => 'list',
        'council_size' => 13,
        'minority_gender' => 'female',
        'minority_minimum_seats' => 7,
        'absence_calendar_principal' => 'principals/users/admin',
        'absence_calendar_uri' => 'br-abwesenheiten',
        'status' => 'draft',
        'lists' => $lists,
    ];
}

$validation = new LegislatureValidationService();
$fixture = legislature_fixture();
assertSameValue([], $validation->validate($fixture, true), 'A complete two-list legislature should be activatable.');

$tooFewWomen = $fixture;
$tooFewWomen['lists'][0]['members'][0]['gender'] = 'male';
$errors = $validation->validate($tooFewWomen, true);
assertContainsString(
    'Mindestzahl des Minderheitengeschlechts',
    implode(' ', $errors),
    'Activation must reject a roster below seven configured minority seats.'
);

$duplicateUid = $fixture;
$duplicateUid['lists'][1]['members'][0]['user_uid'] = 'br-member-1';
assertContainsString(
    'eindeutige Nextcloud-UID',
    implode(' ', $validation->validate($duplicateUid)),
    'A person must only be entered once per legislature.'
);

$repository = new class($fixture) extends LegislatureRepository {
    public array $stored;
    public bool $activated = false;
    public function __construct(array $fixture) { $this->stored = ['id' => 41] + $fixture; }
    public function latest(): ?array { return $this->stored; }
    public function activeForDate(string $date): ?array { return $this->activated ? $this->stored : null; }
    public function configuration(int $legislatureId): ?array { return $legislatureId === 41 ? $this->stored : null; }
    public function hasOverlappingActive(string $startsOn, string $endsOn, int $excludeId): bool { return false; }
    public function saveDraft(array $configuration, string $uid): int { $this->stored = ['id' => 41] + $configuration; return 41; }
    public function activate(int $legislatureId): void { $this->activated = true; $this->stored['status'] = 'active'; }
};
$service = new LegislatureService($repository, $validation);
$saved = $service->saveDraft($fixture, 'admin');
assertSameValue(41, $saved['id'], 'The service should persist a validated draft through the repository.');
$active = $service->activate(41);
assertSameValue('active', $active['status'], 'Activation should seal the validated legislature.');
assertThrows(
    static fn() => $service->saveDraft($active, 'admin'),
    \RuntimeException::class,
    'An active legislature must not be editable as a draft.'
);

echo 'LegislatureService smoke tests passed' . PHP_EOL;
