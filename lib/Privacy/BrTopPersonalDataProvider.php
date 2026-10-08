<?php

declare(strict_types=1);

namespace OCA\BrTop\Privacy;

use InvalidArgumentException;
use OCA\FlzDataProtection\PublicApi\V1\PersonalDataEntry;
use OCA\FlzDataProtection\PublicApi\V1\PersonalDataPage;
use OCA\FlzDataProtection\PublicApi\V1\PersonalDataProvider;
use OCA\FlzDataProtection\PublicApi\V1\PersonalDataRequest;
use OCA\FlzDataProtection\PublicApi\V1\ProviderDescriptor;
use OCA\BrTop\Repository\TemporaryAdminAccessRepository;

final class BrTopPersonalDataProvider implements PersonalDataProvider {
    private const CONTENT_RESTRICTIONS = [
        'Dateiinhalte und konkrete Dateipfade sind vorerst von der BRTop-Auskunft ausgeschlossen.',
        'TOP-, Protokoll-, Beschluss- und Anhanginhalte werden wegen ihres vertraulichen Gremien- und Drittpersonenbezugs nicht ausgegeben.',
        'Nicht strukturiert per Nextcloud-UID zuordenbare Freitexterwähnungen können nicht automatisch ermittelt werden.',
    ];

    public function __construct(private BrTopPrivacyRepository $repository, private TemporaryAdminAccessRepository $adminAccess) {
    }

    public function descriptor(): ProviderDescriptor {
        return new ProviderDescriptor('brtop', 'BR-Sitzungen und Tagesordnung', '1.0', ['nextcloud-user'], ['personal-data'], 500);
    }

    public function collect(PersonalDataRequest $request): PersonalDataPage {
        if ($request->subject()->subjectType() !== 'nextcloud-user') return new PersonalDataPage('not_applicable');
        if ($request->cursor() !== null) throw new InvalidArgumentException('BRTop does not support cursor paging.');
        $rows = $this->repository->forSubject($request->subject()->subjectId(), $request->pageLimit() + 1);
        $adminHistory = $this->adminAccess->historyForUid($request->subject()->subjectId(), $request->pageLimit() + 1);
        $limited = count($rows) + count($adminHistory) > $request->pageLimit();
        $restrictions = self::CONTENT_RESTRICTIONS;
        if ($limited) $restrictions[] = 'Ausgabelimit erreicht; weitere BRTop-Metadaten können vorhanden sein.';
        $items = array_map(fn(array $row): PersonalDataEntry => $this->item($row), $rows);
        foreach ($adminHistory as $grant) $items[] = $this->adminAccessItem($request->subject()->subjectId(), $grant);
        if ($limited) $items = array_slice($items, 0, $request->pageLimit());
        return new PersonalDataPage('partial', $items, $restrictions);
    }

    private function adminAccessItem(string $uid, array $grant): PersonalDataEntry {
        $roles=[];if($grant['targetUid']===$uid)$roles[]='Ziel der Vollzugriffsfreigabe';if($grant['grantedBy']===$uid)$roles[]='Freigebendes Mitglied von Datenschutzbeauftragte';if($grant['revokedBy']===$uid)$roles[]='Widerrufendes Mitglied von Datenschutzbeauftragte';$actualEnd=$grant['revokedAt']??$grant['endsAt'];
        return new PersonalDataEntry('admin-access','Zeitlich begrenzter Admin-Vollzugriff','admin-access:'.$grant['id'],'Admin-Vollzugriff vom '.self::dateTime($grant['startsAt']),'Nachweis einer zeitlich begrenzten administrativen BRTop-Freigabe','App-lokale Freigabesteuerung in BRTop',['Betroffene Person und ausdrücklich berechtigte Datenschutz-Prüfrolle'],'Keine feste Löschfrist festgelegt; die sicherheitsrelevante Freigabehistorie bleibt bis zu einer gesonderten Aufbewahrungsentscheidung erhalten.','Durch BRTop sind keine Drittlandübermittlungen vorgesehen.','Der Server beendet den Vollzugriff spätestens nach 24 Stunden automatisch.','Kennungen anderer beteiligter Personen werden nicht ausgegeben.',['Eigene Rolle im Vorgang'=>implode(', ',$roles),'Beginn'=>self::dateTime($grant['startsAt']),'Geplantes Ende'=>self::dateTime($grant['endsAt']),'Tatsächliches Ende'=>self::dateTime($actualEnd),'Status'=>$grant['revokedAt']===null?'planmäßig beendet oder noch aktiv':'widerrufen']);
    }

    /** @param array<string, mixed> $row */
    private function item(array $row): PersonalDataEntry {
        return match ((string)$row['kind']) {
            'membership' => $this->membership($row),
            'invitation' => $this->invitation($row),
            'meeting' => $this->meeting($row),
            'document' => $this->document($row),
            default => $this->activity($row),
        };
    }

    private function base(string $categoryId, string $label, array $row, string $summary, string $purpose, array $attributes, ?string $notice = null): PersonalDataEntry {
        $referenceKind = $categoryId === 'administrative_activity' ? (string)($row['activity'] ?? $categoryId) : $categoryId;
        return new PersonalDataEntry(
            categoryId: $categoryId, categoryLabel: $label, reference: $referenceKind . ':' . (int)$row['id'], summary: $summary,
            purpose: $purpose, source: 'BRTop-Fachdaten mit explizitem Bezug zur angefragten Nextcloud-UID',
            recipientCategories: ['Berechtigte Mitglieder und Funktionsträger*innen des Betriebsrats'],
            retention: 'Keine feste Löschfrist festgelegt; fachliche Nachweis- und Aufbewahrungsanforderungen sind noch zu entscheiden.',
            thirdCountryTransfer: 'BRTop selbst sieht keine Drittlandübermittlung vor.',
            automatedDecision: 'Es findet keine automatisierte Entscheidung mit rechtlicher oder vergleichbar erheblicher Wirkung statt.',
            thirdPartyContentNotice: $notice, attributes: $attributes,
        );
    }

    private function membership(array $row): PersonalDataEntry {
        return $this->base('council_membership', 'Betriebsratszuordnung', $row, 'Betriebsratszuordnung in Legislatur ' . (int)$row['legislature_id'], 'Zusammensetzung und rechtssichere Planung des Betriebsrats', [
            'Anzeigename'=>(string)$row['display_name'], 'E-Mail'=>(string)$row['email'], 'Geschlecht'=>(string)$row['gender'],
            'Rolle'=>(string)$row['member_role'], 'Listenrang'=>(int)$row['list_rank'], 'Aktiv'=>(bool)$row['active'],
        ]);
    }

    private function invitation(array $row): PersonalDataEntry {
        $date = self::date($row['meeting_date'] ?? '');
        return $this->base('invitation_recipient', 'Ladungsnachweis', $row, self::meetingType((string)$row['meeting_type']) . ' am ' . $date, 'Rechtssicherer Ladungs- und Teilnahmenachweis', [
            'Sitzungsdatum'=>$date, 'Sitzungsart'=>self::meetingType((string)$row['meeting_type']), 'Ladungsart'=>(string)$row['invitation_type'],
            'Abwesenheitsgrund'=>(string)($row['absence_reason'] ?? ''), 'Abwesenheit bestätigt'=>(string)($row['absence_excused'] ?? ''),
        ], 'Ersatz- und weitere Ladungsbezüge können andere Personen betreffen; deren Identitäten werden nicht ausgegeben.');
    }

    private function meeting(array $row): PersonalDataEntry {
        $date = self::date($row['meeting_date'] ?? '');
        return $this->base('meeting_responsibility', 'Sitzungsverantwortung', $row, self::meetingType((string)$row['meeting_type']) . ' am ' . $date, 'Planung und Dokumentation einer Betriebsratssitzung', [
            'Sitzungsdatum'=>$date, 'Sitzungsart'=>self::meetingType((string)$row['meeting_type']), 'Status'=>(string)$row['status'], 'Ladungsstatus'=>(string)$row['invitation_status'],
        ], 'Sitzungstitel, Ort und Gremieninhalte werden nicht ausgegeben.');
    }

    private function document(array $row): PersonalDataEntry {
        return $this->base('generated_document', 'Erzeugtes Dokument', $row, self::documentType((string)$row['document_type']), 'Erzeugung von Ladungs-, Protokoll- und Beschlussunterlagen im persönlichen Nextcloud-Dateibereich', [
            'Dokumentart'=>self::documentType((string)$row['document_type']), 'Erzeugt am'=>self::date($row['created_at'] ?? ''),
        ], 'Dateiname, Dateipfad und Dateiinhalt werden nicht ausgegeben. Eine spätere sichere Inhaltslösung bleibt vorbehalten.');
    }

    private function activity(array $row): PersonalDataEntry {
        $label = match ((string)($row['activity'] ?? '')) { 'absence_review'=>'Abwesenheitsprüfung', 'invitation_snapshot'=>'Ladungssnapshot erstellt', default=>'Legislatur angelegt' };
        return $this->base('administrative_activity', 'Administrativer Nachweis', $row, $label, 'Nachvollziehbarkeit verantwortlicher BRTop-Verfahrensschritte', ['Vorgang'=>$label, 'Zeitpunkt'=>self::date($row['occurred_at'] ?? '')], 'Betroffene Sitzungen, Mitglieder und Inhalte werden nicht ausgegeben.');
    }

    private static function meetingType(string $type): string { return match ($type) { 'regular'=>'Reguläre BR-Sitzung', 'monthly'=>'Monatsgespräch', 'executive'=>'Betriebsausschuss', default=>'Weitere BR-Sitzung' }; }
    private static function documentType(string $type): string { return str_starts_with($type, 'protocol_') ? 'Protokolldokument' : (str_starts_with($type, 'resolution_') ? 'Beschlussdokument' : 'Ladungsdokument'); }
    private static function date(mixed $value): string {
        if ($value instanceof \DateTimeInterface) return $value->format('d.m.Y');
        $timestamp = strtotime((string)$value);
        return $timestamp === false ? (string)$value : date('d.m.Y', $timestamp);
    }
    private static function dateTime(mixed $value): string { if($value instanceof \DateTimeInterface)return $value->format('d.m.Y, H:i').' Uhr';return (new \DateTimeImmutable((string)$value))->format('d.m.Y, H:i').' Uhr'; }
}
