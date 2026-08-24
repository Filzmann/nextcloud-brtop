<?php

declare(strict_types=1);

namespace OCP\EventDispatcher {
    class Event { public function __construct() {} }
    interface IEventListener { public function handle(Event $event): void; }
}

namespace OCA\BrTop\Privacy {
    class BrTopPrivacyRepository {
        /** @var list<array<string, mixed>> */ public array $records = [];
        public function forSubject(string $uid, int $limit): array { return array_slice($this->records, 0, $limit); }
    }
}

namespace {
    use OCA\BrTop\Privacy\BrTopPersonalDataProvider;
    use OCA\BrTop\Privacy\BrTopPrivacyProviderListener;
    use OCA\BrTop\Privacy\BrTopPrivacyRepository;
    use OCA\FilzmannDataProtection\PublicApi\V1\DataSubjectRef;
    use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataRequest;
    use OCA\FilzmannDataProtection\PublicApi\V1\RegisterPersonalDataProvidersEvent;

    $repository = new BrTopPrivacyRepository();
    $repository->records = [
        ['kind'=>'membership','id'=>1,'legislature_id'=>4,'display_name'=>'Test Mitglied','email'=>'mitglied@example.invalid','gender'=>'divers','member_role'=>'regular','list_rank'=>2,'active'=>true,'foreign_uid'=>'other-person'],
        ['kind'=>'invitation','id'=>2,'meeting_date'=>'2026-08-25','meeting_type'=>'regular','invitation_type'=>'initial','absence_reason'=>'verhindert','absence_excused'=>'bestaetigt','replacement_for_uid'=>'other-person','title'=>'Vertraulicher Sitzungstitel'],
        ['kind'=>'meeting','id'=>3,'meeting_date'=>'2026-08-25','meeting_type'=>'regular','status'=>'draft','invitation_status'=>'created','title'=>'Personalfall Beispiel','location'=>'Geheimer Raum'],
        ['kind'=>'document','id'=>4,'document_type'=>'protocol_odt','created_at'=>'2026-08-25','file_path'=>'BR-Sitzungen/Personalfall/Protokoll.odt','content'=>'Vertraulicher Dateiinhalt'],
        ['kind'=>'activity','id'=>5,'activity'=>'absence_review','occurred_at'=>'2026-08-24','affected_uid'=>'other-person','protocol_content'=>'Nicht ausgeben'],
        ['kind'=>'activity','id'=>5,'activity'=>'invitation_snapshot','occurred_at'=>'2026-08-23'],
        ['kind'=>'activity','id'=>5,'activity'=>'legislature_created','occurred_at'=>'2026-08-22'],
    ];
    $provider = new BrTopPersonalDataProvider($repository);
    $descriptor = $provider->descriptor();
    if ($descriptor->appId() !== 'brtop' || !$descriptor->supportsSubjectType('nextcloud-user') || $descriptor->contractVersion() !== '1.0') {
        throw new RuntimeException('BRTop beschreibt den Standalone-V1-Vertrag nicht korrekt.');
    }

    $subject = new DataSubjectRef('nextcloud-user', 'self');
    $page = $provider->collect(new PersonalDataRequest($subject, 'de', 'access-report', 50, []));
    if ($page->status() !== 'partial' || count($page->entries()) !== 7 || $page->restrictions() === []) {
        throw new RuntimeException('BRTop weist die bewusst ausgeschlossenen Inhaltsklassen nicht als Teilantwort aus.');
    }
    $json = json_encode(array_map(static fn($entry): array => [
        'categoryId'=>$entry->categoryId(), 'reference'=>$entry->reference(), 'summary'=>$entry->summary(), 'attributes'=>$entry->attributes(),
        'thirdPartyContentNotice'=>$entry->thirdPartyContentNotice(),
    ], $page->entries()), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    foreach (['Test Mitglied','mitglied@example.invalid','25.08.2026','Reguläre BR-Sitzung','Protokolldokument','Abwesenheitsprüfung'] as $expected) {
        if (!str_contains($json, $expected)) throw new RuntimeException("Erforderliche BRTop-Metadaten fehlen: {$expected}");
    }
    foreach (['other-person','Vertraulicher Sitzungstitel','Personalfall Beispiel','Geheimer Raum','BR-Sitzungen/','Vertraulicher Dateiinhalt','Nicht ausgeben'] as $forbidden) {
        if (str_contains($json, $forbidden)) throw new RuntimeException("BRTop-Auskunft verrät ausgeschlossene Inhalte: {$forbidden}");
    }
    $references = array_map(static fn($entry): string => $entry->reference(), $page->entries());
    if (count($references) !== count(array_unique($references))) throw new RuntimeException('BRTop erzeugt kollidierende fachliche Referenzen für verschiedene Auditquellen.');
    $restrictionText = implode(' ', $page->restrictions());
    foreach (['Dateiinhalte', 'Dateipfade', 'TOP-', 'Protokoll'] as $expected) {
        if (!str_contains($restrictionText, $expected)) throw new RuntimeException("BRTop-Einschränkung benennt den Ausschluss nicht: {$expected}");
    }

    $limited = $provider->collect(new PersonalDataRequest($subject, 'de', 'access-report', 1, []));
    if ($limited->status() !== 'partial' || count($limited->entries()) !== 1 || !str_contains(implode(' ', $limited->restrictions()), 'Ausgabelimit')) {
        throw new RuntimeException('Ein begrenzter BRTop-Bericht benennt das Seitenlimit nicht.');
    }
    $emptyRepository = new BrTopPrivacyRepository();
    $empty = (new BrTopPersonalDataProvider($emptyRepository))->collect(new PersonalDataRequest($subject, 'de', 'access-report', 50, []));
    if ($empty->status() !== 'partial' || $empty->entries() !== []) throw new RuntimeException('Nicht sicher zuordenbare Freitexterwähnungen werden bei leerer UID-Projektion verschwiegen.');
    $unsupported = $provider->collect(new PersonalDataRequest(new DataSubjectRef('external-person', 'self'), 'de', 'access-report', 50, []));
    if ($unsupported->status() !== 'not_applicable' || $unsupported->entries() !== []) throw new RuntimeException('Ein fremder Subject-Typ erhält BRTop-Daten.');
    try {
        $provider->collect((new PersonalDataRequest($subject, 'de', 'access-report', 50, ['brtop'=>'opaque']))->forProvider('brtop', 50));
        throw new RuntimeException('Ein unbekannter Provider-Cursor wurde akzeptiert.');
    } catch (InvalidArgumentException) {}

    $listener = new BrTopPrivacyProviderListener($provider);
    $event = new RegisterPersonalDataProvidersEvent();
    $listener->handle($event);
    if (array_keys($event->providers()) !== ['brtop']) throw new RuntimeException('BRTop registriert seinen Privacy-Provider nicht.');
    $application = (string)file_get_contents(dirname(__DIR__, 2) . '/lib/AppInfo/Application.php');
    if (!str_contains($application, 'registerEventListener(RegisterPersonalDataProvidersEvent::class, BrTopPrivacyProviderListener::class)')) {
        throw new RuntimeException('BRTop registriert den Provider nicht am Standalone-V1-Event.');
    }

    echo "BRTop privacy provider test passed\n";
}
