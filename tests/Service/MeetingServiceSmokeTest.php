<?php

declare(strict_types=1);


use OCA\BrTop\Model\Meeting;
use OCA\BrTop\Repository\DocumentRepository;
use OCA\BrTop\Repository\MeetingRepository;
use OCA\BrTop\Service\AgendaService;
use OCA\BrTop\Service\AgendaTemplateService;
use OCA\BrTop\Service\BrtopSettingsService;
use OCA\BrTop\Service\MeetingScheduleService;
use OCA\BrTop\Service\MeetingService;
use OCA\BrTop\Store\MeetingStore;
use OCA\BrTop\Store\ProtocolBlockStore;
use function OCA\BrTop\Tests\assertSameValue;

$settings = new class extends BrtopSettingsService {
    public function __construct() {
    }

    public function normalizeMeetingType(string $meetingType): string {
        return in_array($meetingType, ['regular_br', 'committee', 'works_committee', 'custom'], true)
            ? $meetingType
            : 'custom';
    }

    public function normalizeCommitteeCode(string $committeeCode): string {
        return strtoupper(trim($committeeCode)) === 'ASA' ? 'ASA' : '';
    }
};

$scheduleService = new class extends MeetingScheduleService {
    public function __construct() {
    }

    public function nextRegularMeetingDefaults(?DateTimeImmutable $referenceDate = null): array {
        return [
            'title' => 'Ordentliche BR-Sitzung',
            'meetingDate' => '2026-07-07',
            'meetingTime' => '10:00',
            'location' => 'BR-Raum',
            'invitationDate' => '2026-07-03',
        ];
    }
};

$templateService = new class extends AgendaTemplateService {
    public function __construct() {
    }

    public function regularBrMeetingItems(): array {
        return [
            ['type' => 'protocol', 'subject' => 'Protokolle'],
            ['type' => 'consultation_report', 'subject' => 'Bericht aus den Sprechstunden'],
        ];
    }
};

$agendaService = new class extends AgendaService {
    public array $templateCalls = [];
    public array $deletedMeetings = [];

    public function __construct() {
    }

    public function addTemplateItems(int $meetingId, array $items): void {
        $this->templateCalls[] = compact('meetingId', 'items');
    }

    public function deleteItemsForMeeting(int $meetingId): void {
        $this->deletedMeetings[] = $meetingId;
    }
};

$meetingStore = new class extends MeetingStore {
    public array $savedMeetings = [];
    private int $nextId = 40;

    public function __construct() {
    }

    public function save(Meeting $meeting): int {
        $meeting->id = $this->nextId++;
        $this->savedMeetings[] = $meeting->toRepositoryData();

        return $meeting->id;
    }
};

$meetingRepository = new class extends MeetingRepository {
    public int $transactions = 0;
    public array $deletedRecipientMeetings = [];
    public array $deletedMeetings = [];

    public function __construct() {
    }

    public function transactional(callable $callback) {
        $this->transactions += 1;

        return $callback();
    }

    public function deleteInvitationRecipients(int $meetingId): void {
        $this->deletedRecipientMeetings[] = $meetingId;
    }

    public function deleteById(int $meetingId): void {
        $this->deletedMeetings[] = $meetingId;
    }
};

$documentRepository = new class extends DocumentRepository {
    public array $deletedMeetings = [];

    public function __construct() {
    }

    public function deleteForMeeting(int $meetingId): void {
        $this->deletedMeetings[] = $meetingId;
    }
};

$protocolBlockStore = new class extends ProtocolBlockStore {
    public array $deletedMeetings = [];

    public function __construct() {
    }

    public function deleteForMeeting(int $meetingId): void {
        $this->deletedMeetings[] = $meetingId;
    }
};

$service = new MeetingService(
    $settings,
    $scheduleService,
    $templateService,
    $agendaService,
    $meetingStore,
    $meetingRepository,
    $documentRepository,
    $protocolBlockStore
);

$freeMeetingId = $service->create('simon', 'Freie Sitzung', '2026-07-10', '09:00', 'BR-Raum', 'unknown', 'ASA');
assertSameValue(40, $freeMeetingId, 'Created meetings should return their saved id.');
assertSameValue('custom', $meetingStore->savedMeetings[0]['meeting_type'], 'Unknown meeting types should normalize to custom.');
assertSameValue('', $meetingStore->savedMeetings[0]['committee_code'], 'Non-committee meetings should not keep committee codes.');

$committeeMeetingId = $service->create('simon', 'ASA', '2026-07-11', '09:00', 'BR-Raum', 'committee', 'asa');
assertSameValue(41, $committeeMeetingId, 'Committee meetings should be persisted.');
assertSameValue('ASA', $meetingStore->savedMeetings[1]['committee_code'], 'Committee meetings should keep normalized committee codes.');

$worksCommitteeMeetingId = $service->create('simon', 'BA', '2026-07-12', '09:00', 'BR-Raum', 'works_committee', '');
assertSameValue(42, $worksCommitteeMeetingId, 'Works committee meetings should be persisted.');
assertSameValue('BA', $meetingStore->savedMeetings[2]['committee_code'], 'Works committee meetings should force BA as committee code.');

$planned = $service->planNextRegular('simon');
assertSameValue(
    [
        'id' => 43,
        'meetingDate' => '2026-07-07',
        'invitationDate' => '2026-07-03',
        'agendaItemsCreated' => 2,
    ],
    $planned,
    'Planning the next regular meeting should expose meeting and invitation dates plus item count.'
);
assertSameValue(1, $meetingRepository->transactions, 'Planning should run inside one transaction.');
assertSameValue('regular_br', $meetingStore->savedMeetings[3]['meeting_type'], 'Regular planning should create a regular BR meeting.');
assertSameValue('planned', $meetingStore->savedMeetings[3]['invitation_status'], 'Regular planning should mark the invitation as planned.');
assertSameValue(43, $agendaService->templateCalls[0]['meetingId'], 'Template TOPs should be created for the saved meeting.');
assertSameValue('Protokolle', $agendaService->templateCalls[0]['items'][0]['subject'], 'Template TOPs should be passed through.');

$service->delete(43);
assertSameValue(2, $meetingRepository->transactions, 'Deleting should run inside one transaction.');
assertSameValue([43], $protocolBlockStore->deletedMeetings, 'Deleting meetings should delete protocol blocks first.');
assertSameValue([43], $documentRepository->deletedMeetings, 'Deleting meetings should delete document metadata.');
assertSameValue([43], $agendaService->deletedMeetings, 'Deleting meetings should delete agenda items.');
assertSameValue([43], $meetingRepository->deletedRecipientMeetings, 'Deleting meetings should delete invitation recipients.');
assertSameValue([43], $meetingRepository->deletedMeetings, 'Deleting meetings should delete the meeting itself.');

echo 'MeetingService smoke tests passed' . PHP_EOL;
