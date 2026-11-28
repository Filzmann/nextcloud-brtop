<?php

declare(strict_types=1);

namespace OCP {
    if (!interface_exists(IAppManager::class)) {
        interface IAppManager { public function getEnabledApps(): array; }
    }
}

namespace OCP\App {
    if (!interface_exists(IAppManager::class)) {
        interface IAppManager { public function getEnabledApps(): array; }
    }
}

namespace Psr\Log {
    if (!interface_exists(LoggerInterface::class)) {
        interface LoggerInterface { public function warning($message, array $context = []): void; }
    }
}

namespace {
    require_once dirname(__DIR__) . '/bootstrap.php';

    use function OCA\BrTop\Tests\assertSameValue;
    use OCA\BrTop\Repository\MeetingRepository;
    use OCA\BrTop\Service\AgendaService;
    use OCA\BrTop\Service\SprechstundenberichtGateway;
    use OCA\BrTop\Service\SprechstundenberichtService;
    use OCP\App\IAppManager;
    use Psr\Log\LoggerInterface;

    $gatewayWith = static function (?array $snapshot): SprechstundenberichtGateway {
        return new class($snapshot) extends SprechstundenberichtGateway {
            public function __construct(private ?array $snapshot) {}
            public function snapshotSince(string $requesterUid, string $sinceDate): ?array { return $this->snapshot; }
        };
    };
    $agenda = new class extends AgendaService {
        public array $items = [];
        public function __construct() {}
        public function addItem(int $meetingId, string $type, string $subject, string $personName, string $legalBasis, string $resolutionText, bool $requiresResolution, string $agendaItemKind, int $parentId, string $protocolContent, string $invitationNote = '', string $attachmentPaths = '', int $resolutionCount = 0): int {
            $id = count($this->items) + 100;
            $this->items[] = compact('meetingId', 'type', 'subject', 'personName', 'agendaItemKind', 'parentId', 'protocolContent');
            return $id;
        }
    };
    $meetingsWith = static function (array $byId, array $recent): MeetingRepository {
        return new class($byId, $recent) extends MeetingRepository {
            public function __construct(private array $byId, private array $recent) {}
            public function findById(int $meetingId): ?array { return $this->byId[$meetingId] ?? null; }
            public function findRecent(?int $limit = null): array { return $this->recent; }
        };
    };

    $snapshot = [
        'since_date' => '2026-11-20',
        'sprechstunden' => [
            ['date' => '2026-11-28', 'member_uid' => 'alice', 'eintraege' => 3, 'wichtige' => 1, 'faelle' => 1],
            ['date' => '2026-12-01', 'member_uid' => 'bob', 'eintraege' => 2, 'wichtige' => 0, 'faelle' => 0],
        ],
    ];
    $service = new SprechstundenberichtService(
        $gatewayWith($snapshot),
        $agenda,
        $meetingsWith([43 => ['id' => 43, 'meeting_date' => '2026-12-08']], [
            ['id' => 43, 'meeting_date' => '2026-12-08'],
            ['id' => 40, 'meeting_date' => '2026-11-20'],
            ['id' => 39, 'meeting_date' => '2026-11-13'],
        ]),
    );

    // Positiv: parent section + 2 sub-TOPs, Stichtag = letzte Sitzung vor der neuen
    $count = $service->appendToMeeting(43, 'simon');
    assertSameValue(2, $count, 'Zwei Sub-TOPs erwartet.');
    assertSameValue(3, count($agenda->items), 'Parent + 2 Sub-TOPs erwartet.');
    assertSameValue('section', $agenda->items[0]['type'], 'Erstes Item muss die Section sein.');
    assertSameValue('Bericht aus den Sprechstunden', $agenda->items[0]['subject'], 'Section-Betreff fehlt.');
    assertSameValue(100, $agenda->items[1]['parentId'], 'Sub-TOP muss unter der Section hängen.');
    assertSameValue('report', $agenda->items[1]['type'], 'Sub-TOP muss report sein.');
    if (!str_contains($agenda->items[1]['subject'], '2026-11-28') || !str_contains($agenda->items[1]['subject'], 'alice')) {
        throw new RuntimeException('Sub-TOP-Betreff muss Datum und Mitglied enthalten: ' . $agenda->items[1]['subject']);
    }
    if (!str_contains($agenda->items[1]['protocolContent'], '3') || !str_contains($agenda->items[1]['protocolContent'], '1')) {
        throw new RuntimeException('Protokoll-Text muss Anzahlen enthalten: ' . $agenda->items[1]['protocolContent']);
    }
    assertSameValue('bob', $agenda->items[2]['personName'], 'personName muss das BR-Mitglied sein.');

    // Stichtag-Prüfung: Gateway bekommt das Datum der letzten Sitzung VOR der neuen
    $spyGateway = new class($snapshot) extends SprechstundenberichtGateway {
        public string $seenSince = '';
        public function __construct(private array $snapshot) {}
        public function snapshotSince(string $requesterUid, string $sinceDate): ?array { $this->seenSince = $sinceDate; return $this->snapshot; }
    };
    $service2 = new SprechstundenberichtService($spyGateway, $agenda, $meetingsWith([43 => ['id' => 43, 'meeting_date' => '2026-12-08']], [
        ['id' => 43, 'meeting_date' => '2026-12-08'],
        ['id' => 40, 'meeting_date' => '2026-11-20'],
    ]));
    $service2->appendToMeeting(43, 'simon');
    assertSameValue('2026-11-20', $spyGateway->seenSince, 'Stichtag muss das Datum der vorherigen Sitzung sein.');

    // Leerer Snapshot: keine Items
    $agenda2 = new class extends AgendaService {
        public array $items = [];
        public function __construct() {}
        public function addItem(int $meetingId, string $type, string $subject, string $personName, string $legalBasis, string $resolutionText, bool $requiresResolution, string $agendaItemKind, int $parentId, string $protocolContent, string $invitationNote = '', string $attachmentPaths = '', int $resolutionCount = 0): int {
            $this->items[] = $subject;
            return count($this->items) + 100;
        }
    };
    $service3 = new SprechstundenberichtService($gatewayWith(['since_date' => '2026-11-20', 'sprechstunden' => []]), $agenda2, $meetingsWith([43 => ['id' => 43, 'meeting_date' => '2026-12-08']], []));
    assertSameValue(0, $service3->appendToMeeting(43, 'simon'), 'Leerer Snapshot darf nichts anlegen.');
    assertSameValue([], $agenda2->items, 'Leerer Snapshot darf keine Items erzeugen.');

    // Provider fehlt (null): Standalone-Zustand, keine Items
    $service4 = new SprechstundenberichtService($gatewayWith(null), $agenda2, $meetingsWith([43 => ['id' => 43, 'meeting_date' => '2026-12-08']], []));
    assertSameValue(0, $service4->appendToMeeting(43, 'simon'), 'Fehlender Provider darf nichts anlegen.');

    // Unbekannte Sitzung: keine Items, kein Fehler
    assertSameValue(0, $service4->appendToMeeting(999, 'simon'), 'Unbekannte Sitzung muss 0 liefern.');

    // Erste Sitzung überhaupt: Stichtag 1970-01-01
    $spy2 = new class($snapshot) extends SprechstundenberichtGateway {
        public string $seenSince = '';
        public function __construct(private array $snapshot) {}
        public function snapshotSince(string $requesterUid, string $sinceDate): ?array { $this->seenSince = $sinceDate; return $this->snapshot; }
    };
    $service5 = new SprechstundenberichtService($spy2, $agenda2, $meetingsWith([50 => ['id' => 50, 'meeting_date' => '2026-12-08']], [['id' => 50, 'meeting_date' => '2026-12-08']]));
    $service5->appendToMeeting(50, 'simon');
    assertSameValue('1970-01-01', $spy2->seenSince, 'Ohne Vorgängersitzung muss der Stichtag 1970-01-01 sein.');

    // Gateway fail-closed: App deaktiviert -> null; App aktiv, aber Provider wirft -> null + Log
    $logger = new class implements LoggerInterface {
        public array $warnings = [];
        public function warning($message, array $context = []): void { $this->warnings[] = $message; }
    };
    $appsOff = new class implements IAppManager {
        public function getEnabledApps(): array { return ['brtop']; }
    };
    $gwOff = new class($appsOff, $logger) extends SprechstundenberichtGateway {
        public function __construct(IAppManager $apps, LoggerInterface $logger) { parent::__construct($apps, $logger); }
    };
    assertSameValue(null, $gwOff->snapshotSince('simon', '2026-11-20'), 'Deaktivierte Provider-App muss null liefern.');

    echo "SprechstundenberichtServiceTest: OK\n";
}
