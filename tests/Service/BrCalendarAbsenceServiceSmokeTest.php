<?php

declare(strict_types=1);

namespace OCP\Calendar {
    if (!interface_exists(ICalendar::class)) {
        interface ICalendar { public function getUri(): string; public function search(string $pattern, array $searchProperties = [], array $options = [], ?int $limit = null, ?int $offset = null): array; }
    }
    if (!interface_exists(IManager::class)) {
        interface IManager { public function getCalendarsForPrincipal(string $principalUri, array $calendarUris = []): array; }
    }
}

namespace OCA\BrTop\Tests {

    use OCA\BrTop\Service\BrCalendarAbsenceService;
    use OCP\Calendar\ICalendar;
    use OCP\Calendar\IManager;

    $calendar = new class implements ICalendar {
        public function getUri(): string { return 'br-abwesenheiten'; }
        public function search(string $pattern, array $searchProperties = [], array $options = [], ?int $limit = null, ?int $offset = null): array {
            return [[
                'objects' => [
                    ['SUMMARY' => ['Verhindert [br-member-1]', []]],
                    ['SUMMARY' => ['BRTop-UID: br-member-2', []]],
                    ['SUMMARY' => ['Nur ein Anzeigename BR Mitglied 3', []]],
                    ['SUMMARY' => ['Ersatz verhindert [br-replacement-1]', []]],
                    ['SUMMARY' => ['Unbekannt [external-user]', []]],
                ],
            ]];
        }
    };
    $manager = new class($calendar) implements IManager {
        public function __construct(private ICalendar $calendar) {}
        public function getCalendarsForPrincipal(string $principalUri, array $calendarUris = []): array {
            assertSameValue('principals/users/admin', $principalUri, 'Calendar lookup must stay scoped to the configured principal.');
            assertSameValue(['br-abwesenheiten'], $calendarUris, 'Calendar lookup must stay scoped to the configured URI.');
            return [$this->calendar];
        }
    };
    $service = new BrCalendarAbsenceService($manager);
    $legislature = [
        'absence_calendar_principal' => 'principals/users/admin',
        'absence_calendar_uri' => 'br-abwesenheiten',
        'lists' => [[
            'members' => [
                ['user_uid' => 'br-member-1', 'member_role' => 'regular'],
                ['user_uid' => 'br-member-2', 'member_role' => 'regular'],
                ['user_uid' => 'br-member-3', 'member_role' => 'regular'],
                ['user_uid' => 'br-replacement-1', 'member_role' => 'replacement'],
            ],
        ]],
    ];

    assertSameValue(
        ['br-member-1', 'br-member-2', 'br-replacement-1'],
        $service->suggestedMemberUids('2026-07-21', $legislature),
        'Only explicit, known roster UIDs should become calendar suggestions.'
    );

    echo 'BrCalendarAbsenceService smoke tests passed' . PHP_EOL;
}
