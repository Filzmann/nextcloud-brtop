<?php

declare(strict_types=1);


use OCA\BrTop\Repository\LegislatureRepository;
use OCA\BrTop\Repository\MeetingAbsenceRepository;
use OCA\BrTop\Repository\MeetingRepository;
use OCA\BrTop\Service\BrCalendarAbsenceService;
use OCA\BrTop\Service\LegislatureService;
use OCA\BrTop\Service\LegislatureValidationService;
use OCA\BrTop\Service\MeetingAbsenceService;
use function OCA\BrTop\Tests\assertSameValue;
use function OCA\BrTop\Tests\assertThrows;

$legislature = [
    'id' => 5,
    'status' => 'active',
    'lists' => [[
        'name' => 'Liste Zukunft',
        'members' => [
            ['id' => 11, 'user_uid' => 'br-member-1', 'display_name' => 'BR Mitglied 1', 'member_role' => 'regular'],
            ['id' => 12, 'user_uid' => 'br-member-2', 'display_name' => 'BR Mitglied 2', 'member_role' => 'regular'],
            ['id' => 13, 'user_uid' => 'br-replacement-1', 'display_name' => 'Ersatz 1', 'member_role' => 'replacement'],
        ],
    ]],
];
$legislatureRepository = new class($legislature) extends LegislatureRepository {
    public function __construct(private array $value) {}
    public function configuration(int $legislatureId): ?array { return $this->value; }
    public function activeForDate(string $date): ?array { return $this->value; }
};
$legislatureService = new LegislatureService($legislatureRepository, new LegislatureValidationService());

$meetingRepository = new class extends MeetingRepository {
    public bool $locked = false;
    public ?int $boundLegislature = null;
    public function __construct() {}
    public function findById(int $meetingId): ?array {
        return ['id' => $meetingId, 'meeting_date' => '2026-07-21', 'legislature_id' => $this->boundLegislature];
    }
    public function bindLegislature(int $meetingId, int $legislatureId): void { $this->boundLegislature = $legislatureId; }
    public function hasInvitationSnapshot(int $meetingId): bool { return $this->locked; }
};
$absenceRepository = new class extends MeetingAbsenceRepository {
    public array $rows = [];
    public bool $reviewed = false;
    public function __construct() {}
    public function forMeeting(int $meetingId): array { return $this->rows; }
    public function addSuggestions(int $meetingId, array $memberIds): void {
        foreach ($memberIds as $memberId) {
            $exists = array_filter($this->rows, static fn(array $row): bool => (int)$row['member_id'] === (int)$memberId);
            if ($exists === []) {
                $this->rows[] = ['meeting_id' => $meetingId, 'member_id' => $memberId, 'status' => 'suggested'];
            }
        }
    }
    public function replaceConfirmed(int $meetingId, array $memberIds, string $uid): void {
        $this->rows = array_map(
            static fn(int $memberId): array => ['meeting_id' => $meetingId, 'member_id' => $memberId, 'status' => 'confirmed'],
            $memberIds
        );
        $this->reviewed = true;
    }
    public function isReviewed(int $meetingId): bool { return $this->reviewed; }
};
$calendar = new class extends BrCalendarAbsenceService {
    public function __construct() {}
    public function suggestedMemberUids(string $date, array $legislature): array { return ['br-member-1']; }
};

$service = new MeetingAbsenceService($meetingRepository, $absenceRepository, $legislatureService, $calendar);
$prepared = $service->prepare(77);
assertSameValue(5, $meetingRepository->boundLegislature, 'Preparing absences should bind the meeting to its active legislature.');
assertSameValue('suggested', $prepared['members'][0]['absence_status'], 'Calendar data should only preselect a regular member.');
assertSameValue('', $prepared['members'][1]['absence_status'], 'Unmentioned members should stay unselected.');
assertSameValue('replacement', $prepared['members'][2]['member_role'], 'Replacement members must be available for manual unavailability confirmation.');

$service->saveConfirmed(77, [12], 'admin');
assertSameValue([12], $service->confirmedMemberIds(77), 'Only the explicit admin confirmation should drive replacement selection.');

$meetingRepository->locked = true;
assertThrows(
    static fn() => $service->saveConfirmed(77, [11], 'admin'),
    \RuntimeException::class,
    'Confirmed absences must be immutable after the invitation snapshot exists.'
);

echo 'MeetingAbsenceService smoke tests passed' . PHP_EOL;
