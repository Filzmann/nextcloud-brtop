<?php

declare(strict_types=1);

namespace OCA\BrTop\Service;

use OCA\BrTop\AppInfo\Application;
use OCP\IConfig;

class BrtopSettingsService {
    public function __construct(
        private IConfig $config
    ) {
    }

    public function values(): array {
        return [
            'defaultMeetingTitle' => $this->defaultMeetingTitle(),
            'regularMeetingWeekday' => $this->regularMeetingWeekday(),
            'invitationWeekday' => $this->invitationWeekday(),
            'defaultMeetingTime' => $this->defaultMeetingTime(),
            'defaultLocation' => $this->defaultLocation(),
            'meetingTypes' => $this->meetingTypes(),
            'committeeCodes' => $this->committeeCodes(),
        ];
    }

    public function save(
        string $defaultMeetingTitle,
        int $regularMeetingWeekday,
        int $invitationWeekday,
        string $defaultMeetingTime,
        string $defaultLocation,
        string $regularAgendaTemplateJson
    ): array {
        $this->setStringValue('default_meeting_title', $defaultMeetingTitle, 'Ordentliche BR-Sitzung');
        $this->setWeekdayValue('regular_meeting_weekday', $regularMeetingWeekday);
        $this->setWeekdayValue('invitation_weekday', $invitationWeekday);
        $this->setTimeValue('default_meeting_time', $defaultMeetingTime);
        $this->setStringValue('default_location', $defaultLocation, 'BR-Büro / Videokonferenz');
        $this->setTextValue('regular_agenda_template_json', $regularAgendaTemplateJson);

        return $this->values();
    }

    public function defaultMeetingTitle(): string {
        return $this->stringValue('default_meeting_title', 'Ordentliche BR-Sitzung');
    }

    public function regularMeetingWeekday(): int {
        return $this->weekdayValue('regular_meeting_weekday', 2);
    }

    public function invitationWeekday(): int {
        return $this->weekdayValue('invitation_weekday', 5);
    }

    public function defaultMeetingTime(): string {
        return $this->stringValue('default_meeting_time', '10:00');
    }

    public function defaultLocation(): string {
        return $this->stringValue('default_location', 'BR-Büro / Videokonferenz');
    }

    /** Legacy source used only by the one-time migration into LocalBase. */
    public function legacyMemberGroupName(): string {
        return $this->stringValue('member_group_name', 'Betriebsrat');
    }

    public function regularAgendaTemplateJson(): string {
        return trim($this->config->getAppValue(Application::APP_ID, 'regular_agenda_template_json', ''));
    }

    public function meetingTypes(): array {
        return [
            ['value' => 'regular_br', 'label' => 'Reguläre BR-Sitzung', 'requiresCommittee' => false],
            ['value' => 'monthly_talk', 'label' => 'Monatsgespräch', 'requiresCommittee' => false],
            ['value' => 'works_committee', 'label' => 'Betriebsausschuss', 'requiresCommittee' => false],
            ['value' => 'committee', 'label' => 'Ausschuss / AG', 'requiresCommittee' => true],
            ['value' => 'custom', 'label' => 'Freie Sitzung', 'requiresCommittee' => false],
        ];
    }

    public function committeeCodes(): array {
        $defaults = [
            ['value' => 'ASA', 'label' => 'ASA'],
            ['value' => 'DPA', 'label' => 'DPA'],
            ['value' => 'IKT', 'label' => 'IKT'],
            ['value' => 'BA', 'label' => 'BA'],
            ['value' => 'IBF', 'label' => 'IBF'],
        ];
        $json = trim($this->config->getAppValue(Application::APP_ID, 'document_committee_codes_json', ''));
        if ($json === '') {
            return $defaults;
        }

        try {
            return $this->normalizeCommitteeCodes($json);
        } catch (\InvalidArgumentException) {
            return $defaults;
        }
    }

    public function documentOrganization(): array {
        return [
            'councilName' => $this->stringValue('document_council_name', 'Betriebsrat ambulante dienste e.V.'),
            'councilAddress' => $this->textValue('document_council_address', 'Wilhelm-Kabus-Str. 21-35, 10829 Berlin'),
            'issuePlace' => $this->stringValue('document_issue_place', 'Berlin'),
            'committeeCodes' => $this->committeeCodes(),
            'invitationSalutation' => $this->textValue('document_invitation_salutation', 'Liebe Kolleg*innen,'),
            'invitationIntroduction' => $this->textValue('document_invitation_introduction', 'hiermit lade ich euch zur Sitzung ein.'),
            'invitationClosing' => $this->textValue('document_invitation_closing', 'Viele Grüße'),
            'invitationSender' => $this->textValue('document_invitation_sender', '{{ABSENDER}}'),
        ];
    }

    public function saveDocumentOrganization(
        string $councilName,
        string $councilAddress,
        string $issuePlace,
        string $committeeCodesJson,
        string $invitationSalutation,
        string $invitationIntroduction,
        string $invitationClosing,
        string $invitationSender
    ): array {
        $values = [
            'document_council_name' => $this->requiredText($councilName, 'Gremienname', 255),
            'document_council_address' => $this->requiredText($councilAddress, 'Anschrift', 2000),
            'document_issue_place' => $this->requiredText($issuePlace, 'Ausstellungsort', 255),
            'document_invitation_salutation' => $this->requiredText($invitationSalutation, 'Anrede', 500),
            'document_invitation_introduction' => $this->requiredText($invitationIntroduction, 'Einladungstext', 4000),
            'document_invitation_closing' => $this->requiredText($invitationClosing, 'Grußformel', 500),
            'document_invitation_sender' => $this->requiredText($invitationSender, 'Absender', 500),
        ];
        $committeeCodes = $this->normalizeCommitteeCodes($committeeCodesJson);
        $encodedCodes = json_encode($committeeCodes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        foreach ($values as $key => $value) {
            $this->config->setAppValue(Application::APP_ID, $key, $value);
        }
        $this->config->setAppValue(Application::APP_ID, 'document_committee_codes_json', $encodedCodes);

        return $this->documentOrganization();
    }

    public function normalizeMeetingType(string $meetingType): string {
        $meetingType = trim($meetingType);
        foreach ($this->meetingTypes() as $type) {
            if ($type['value'] === $meetingType) {
                return $meetingType;
            }
        }

        return 'custom';
    }

    public function normalizeCommitteeCode(string $committeeCode): string {
        $committeeCode = strtoupper(trim($committeeCode));
        foreach ($this->committeeCodes() as $committee) {
            if ($committee['value'] === $committeeCode) {
                return $committeeCode;
            }
        }

        return '';
    }

    private function stringValue(string $key, string $default): string {
        $value = trim($this->config->getAppValue(Application::APP_ID, $key, $default));

        return $value !== '' ? $value : $default;
    }

    private function textValue(string $key, string $default): string {
        $value = trim(str_replace(["\r\n", "\r"], "\n", $this->config->getAppValue(Application::APP_ID, $key, $default)));
        return $value !== '' ? $value : $default;
    }

    private function requiredText(string $value, string $label, int $maxLength): string {
        $value = trim(str_replace(["\r\n", "\r", "\0"], ["\n", "\n", ''], $value));
        if ($value === '') {
            throw new \InvalidArgumentException($label . ' darf nicht leer sein.');
        }
        if (strlen($value) > $maxLength) {
            throw new \InvalidArgumentException($label . ' ist zu lang.');
        }
        if (preg_match('/[\x01-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $value) === 1) {
            throw new \InvalidArgumentException($label . ' enthält ungültige Steuerzeichen.');
        }
        return $value;
    }

    private function normalizeCommitteeCodes(string $json): array {
        try {
            $items = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \InvalidArgumentException('Der Ausschusskatalog ist kein gültiges JSON-Array.', 0, $e);
        }
        if (!is_array($items) || $items === [] || count($items) > 30) {
            throw new \InvalidArgumentException('Der Ausschusskatalog muss zwischen 1 und 30 Einträge enthalten.');
        }

        $normalized = [];
        $seen = [];
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                throw new \InvalidArgumentException('Ausschusscode ' . ($index + 1) . ' ist kein Objekt.');
            }
            $value = strtoupper(trim((string)($item['value'] ?? '')));
            $label = trim((string)($item['label'] ?? ''));
            if (preg_match('/^[A-Z][A-Z0-9_-]{1,15}$/', $value) !== 1) {
                throw new \InvalidArgumentException('Ausschusscode ' . ($index + 1) . ' ist ungültig.');
            }
            if ($label === '' || strlen($label) > 80) {
                throw new \InvalidArgumentException('Bezeichnung für Ausschusscode ' . $value . ' ist ungültig.');
            }
            if (isset($seen[$value])) {
                throw new \InvalidArgumentException('Ausschusscode ' . $value . ' ist doppelt vorhanden.');
            }
            $seen[$value] = true;
            $normalized[] = ['value' => $value, 'label' => $label];
        }

        return $normalized;
    }

    private function weekdayValue(string $key, int $default): int {
        $value = (int)$this->config->getAppValue(Application::APP_ID, $key, (string)$default);

        if ($value < 1 || $value > 7) {
            return $default;
        }

        return $value;
    }

    private function setStringValue(string $key, string $value, string $default): void {
        $value = trim($value);
        if ($value === '') {
            $value = $default;
        }

        $this->config->setAppValue(Application::APP_ID, $key, substr($value, 0, 255));
    }

    private function setTextValue(string $key, string $value): void {
        $this->config->setAppValue(Application::APP_ID, $key, trim($value));
    }

    private function setWeekdayValue(string $key, int $value): void {
        if ($value < 1 || $value > 7) {
            throw new \InvalidArgumentException('Ungültiger Wochentag.');
        }

        $this->config->setAppValue(Application::APP_ID, $key, (string)$value);
    }

    private function setTimeValue(string $key, string $value): void {
        $value = trim($value);
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value)) {
            throw new \InvalidArgumentException('Ungültige Uhrzeit.');
        }

        $this->config->setAppValue(Application::APP_ID, $key, $value);
    }
}
