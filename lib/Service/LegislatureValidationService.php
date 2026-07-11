<?php

declare(strict_types=1);

namespace OCA\BrTop\Service;

class LegislatureValidationService {
    private const GENDERS = ['female', 'male', 'diverse'];
    private const ROLES = ['regular', 'replacement'];

    public function validate(array $configuration, bool $forActivation = false): array {
        $errors = [];
        $name = trim((string)($configuration['name'] ?? ''));
        $startsOn = (string)($configuration['starts_on'] ?? '');
        $endsOn = (string)($configuration['ends_on'] ?? '');
        $councilSize = (int)($configuration['council_size'] ?? 0);
        $minorityGender = (string)($configuration['minority_gender'] ?? '');
        $minoritySeats = (int)($configuration['minority_minimum_seats'] ?? 0);
        $lists = $configuration['lists'] ?? [];

        if ($name === '') {
            $errors[] = 'Die Legislatur braucht eine Bezeichnung.';
        }
        if (!$this->validDate($startsOn) || !$this->validDate($endsOn) || $startsOn > $endsOn) {
            $errors[] = 'Beginn und Ende der Legislatur muessen gueltige, aufsteigende Datumswerte sein.';
        }
        if (($configuration['election_type'] ?? 'list') !== 'list') {
            $errors[] = 'Aktuell wird ausschliesslich die bestaetigte Listenwahl unterstuetzt.';
        }
        if ($councilSize < 3) {
            $errors[] = 'Die Zahl der festen BR-Mitglieder muss mindestens 3 betragen.';
        }
        if (!in_array($minorityGender, self::GENDERS, true)) {
            $errors[] = 'Das Minderheitengeschlecht ist ungueltig.';
        }
        if ($minoritySeats < 0 || $minoritySeats > $councilSize) {
            $errors[] = 'Die Mindestzahl der Minderheitensitze ist ungueltig.';
        }
        if (!is_array($lists) || count($lists) < 1) {
            $errors[] = 'Mindestens eine Vorschlagsliste ist erforderlich.';
            return $errors;
        }

        $listNames = [];
        $allUids = [];
        $seatTotal = 0;
        $regularTotal = 0;
        $minorityRegularTotal = 0;
        $voteTotal = 0;

        foreach ($lists as $listIndex => $list) {
            $label = 'Liste ' . ($listIndex + 1);
            $listName = trim((string)($list['name'] ?? ''));
            $seatCount = (int)($list['seat_count'] ?? 0);
            $voteCount = (int)($list['vote_count'] ?? 0);
            $members = is_array($list['members'] ?? null) ? $list['members'] : [];

            $listKey = strtolower($listName);
            if ($listName === '' || isset($listNames[$listKey])) {
                $errors[] = $label . ' braucht einen eindeutigen Namen.';
            }
            $listNames[$listKey] = true;
            if ($seatCount < 0) {
                $errors[] = $label . ' hat eine ungueltige Sitzzahl.';
            }
            if ($voteCount < 0) {
                $errors[] = $label . ' hat eine ungueltige Stimmenzahl.';
            }
            $seatTotal += $seatCount;
            $voteTotal += $voteCount;

            $ranks = [];
            $regularOnList = 0;
            foreach ($members as $memberIndex => $member) {
                $memberLabel = $label . ', Mitglied ' . ($memberIndex + 1);
                $uid = trim((string)($member['user_uid'] ?? ''));
                $displayName = trim((string)($member['display_name'] ?? ''));
                $gender = (string)($member['gender'] ?? '');
                $role = (string)($member['member_role'] ?? '');
                $rank = (int)($member['list_rank'] ?? 0);

                if ($uid === '' || isset($allUids[$uid])) {
                    $errors[] = $memberLabel . ' braucht eine eindeutige Nextcloud-UID.';
                }
                $allUids[$uid] = true;
                if ($displayName === '') {
                    $errors[] = $memberLabel . ' braucht einen Anzeigenamen.';
                }
                if (!in_array($gender, self::GENDERS, true)) {
                    $errors[] = $memberLabel . ' hat eine ungueltige Geschlechtsangabe.';
                }
                if (!in_array($role, self::ROLES, true)) {
                    $errors[] = $memberLabel . ' hat eine ungueltige Rolle.';
                }
                if ($rank <= 0 || isset($ranks[$rank])) {
                    $errors[] = $memberLabel . ' braucht einen positiven, innerhalb der Liste eindeutigen Rang.';
                }
                $ranks[$rank] = true;

                if ($role === 'regular') {
                    $regularOnList++;
                    $regularTotal++;
                    if ($gender === $minorityGender) {
                        $minorityRegularTotal++;
                    }
                }
            }

            if ($regularOnList !== $seatCount) {
                $errors[] = $label . ' muss genau so viele feste Mitglieder wie Sitze enthalten.';
            }
        }

        if ($seatTotal !== $councilSize || $regularTotal !== $councilSize) {
            $errors[] = 'Listenplaetze und feste Mitglieder muessen zusammen der BR-Groesse entsprechen.';
        }
        if ($minorityRegularTotal < $minoritySeats) {
            $errors[] = 'Die festen Mitglieder unterschreiten die eingetragene Mindestzahl des Minderheitengeschlechts.';
        }
        if ($forActivation && $voteTotal <= 0) {
            $errors[] = 'Vor der Aktivierung muessen die gueltigen Stimmen der Listen eingetragen sein.';
        }

        return array_values(array_unique($errors));
    }

    private function validDate(string $value): bool {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }
}
