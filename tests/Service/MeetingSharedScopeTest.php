<?php

declare(strict_types=1);

namespace OCA\BrTop\Tests {
    use OCA\BrTop\Model\Meeting;
    use OCA\BrTop\Repository\MeetingRepository;
    use OCA\BrTop\Service\AgendaService;
    use OCA\BrTop\Service\AgendaTemplateService;
    use OCA\BrTop\Service\BrtopSettingsService;
    use OCA\BrTop\Service\MeetingScheduleService;
    use OCA\BrTop\Service\MeetingService;
    use OCA\BrTop\Repository\DocumentRepository;
    use OCA\BrTop\Store\MeetingStore;
    use OCA\BrTop\Store\ProtocolBlockStore;

    $foreignMeeting = new Meeting(['id'=>77, 'owner_uid'=>'other-member', 'title'=>'Gremiumssitzung', 'meeting_date'=>'2026-11-03']);
    $meetingStore = new class($foreignMeeting) extends MeetingStore {
        public function __construct(private Meeting $meeting) {}
        public function get(int $meetingId): ?Meeting { return $meetingId === 77 ? $this->meeting : null; }
    };
    $service = new MeetingService(
        (new \ReflectionClass(BrtopSettingsService::class))->newInstanceWithoutConstructor(),
        (new \ReflectionClass(MeetingScheduleService::class))->newInstanceWithoutConstructor(),
        (new \ReflectionClass(AgendaTemplateService::class))->newInstanceWithoutConstructor(),
        (new \ReflectionClass(AgendaService::class))->newInstanceWithoutConstructor(),
        $meetingStore,
        (new \ReflectionClass(MeetingRepository::class))->newInstanceWithoutConstructor(),
        (new \ReflectionClass(DocumentRepository::class))->newInstanceWithoutConstructor(),
        (new \ReflectionClass(ProtocolBlockStore::class))->newInstanceWithoutConstructor(),
    );

    assertSameValue(77, $service->assertMeetingExists(77)->id, 'Ein berechtigtes BR-Mitglied muss eine von einem anderen BR-Mitglied angelegte Sitzung verwenden können.');
    assertThrows(static fn() => $service->assertMeetingExists(999), \RuntimeException::class, 'Manipulierte oder unbekannte Sitzungs-ID muss ohne Datenzugriff abgewiesen werden.');

    echo "BRTop shared meeting scope tests passed\n";
}
