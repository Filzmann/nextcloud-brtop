<?php

declare(strict_types=1);

namespace OCA\BrTop\Service;

use DateTimeImmutable;
use OCP\Calendar\ICalendar;
use OCP\Calendar\IManager;

class BrCalendarAbsenceService {
    public function __construct(private IManager $calendarManager) {
    }

    public function suggestedMemberUids(string $date, array $legislature): array {
        $principal = trim((string)($legislature['absence_calendar_principal'] ?? ''));
        $calendarUri = trim((string)($legislature['absence_calendar_uri'] ?? ''));
        if ($principal === '' || $calendarUri === '') {
            return [];
        }

        $knownUids = [];
        foreach (($legislature['lists'] ?? []) as $list) {
            foreach (($list['members'] ?? []) as $member) {
                if (!empty($member['user_uid'])) {
                    $knownUids[(string)$member['user_uid']] = true;
                }
            }
        }

        $calendar = $this->calendar($principal, $calendarUri);
        if ($calendar === null) {
            throw new \RuntimeException('Der konfigurierte Abwesenheitskalender wurde nicht gefunden.');
        }

        $start = new DateTimeImmutable($date . ' 00:00:00');
        $end = $start->modify('+1 day');
        $uids = [];
        foreach ($calendar->search('', [], [
            'timerange' => ['start' => $start, 'end' => $end],
            'types' => ['VEVENT'],
        ]) as $event) {
            foreach (($event['objects'] ?? []) as $object) {
                $text = $this->propertyValue($object['SUMMARY'] ?? null)
                    . "\n"
                    . $this->propertyValue($object['DESCRIPTION'] ?? null);
                $uid = $this->explicitUid($text, $knownUids);
                if ($uid !== null) {
                    $uids[$uid] = true;
                }
            }
        }

        return array_keys($uids);
    }

    private function calendar(string $principal, string $uri): ?ICalendar {
        foreach ($this->calendarManager->getCalendarsForPrincipal($principal, [$uri]) as $calendar) {
            if ($calendar->getUri() === $uri) {
                return $calendar;
            }
        }
        return null;
    }

    private function propertyValue(mixed $property): string {
        if (!is_array($property)) {
            return '';
        }
        if (isset($property[0]) && is_string($property[0])) {
            return $property[0];
        }
        return '';
    }

    private function explicitUid(string $text, array $knownUids): ?string {
        if (preg_match('/\[([a-zA-Z0-9@._-]+)\]/', $text, $matches) && isset($knownUids[$matches[1]])) {
            return $matches[1];
        }
        if (preg_match('/(?:BRTop-UID|UID)\s*:\s*([a-zA-Z0-9@._-]+)/i', $text, $matches)
            && isset($knownUids[$matches[1]])) {
            return $matches[1];
        }
        return null;
    }
}
