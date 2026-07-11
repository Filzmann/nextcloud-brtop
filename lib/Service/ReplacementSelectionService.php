<?php

declare(strict_types=1);

namespace OCA\BrTop\Service;

class ReplacementSelectionService {
    /**
     * Deterministic list-election policy based on BetrVG section 25(2) while
     * preserving the configured minority minimum from section 15(2).
     * Unresolvable ties and exhausted candidate pools fail closed.
     */
    public function recipients(array $legislature, array $confirmedAbsentMemberIds): array {
        $absent = array_fill_keys(array_map('intval', $confirmedAbsentMemberIds), true);
        $regulars = [];
        $listsById = [];
        $candidatesByList = [];

        foreach (($legislature['lists'] ?? []) as $list) {
            $listId = (int)($list['id'] ?? 0);
            $listsById[$listId] = $list;
            foreach (($list['members'] ?? []) as $member) {
                $member['list_id'] = $listId;
                $member['list_name'] = (string)($list['name'] ?? '');
                $member['list_seats'] = (int)($list['seat_count'] ?? 0);
                if (($member['member_role'] ?? '') === 'regular') {
                    $regulars[] = $member;
                } else {
                    $candidatesByList[$listId][] = $member;
                }
            }
        }
        foreach ($candidatesByList as &$candidates) {
            usort($candidates, static fn(array $a, array $b): int => (int)$a['list_rank'] <=> (int)$b['list_rank']);
        }
        unset($candidates);
        usort($regulars, static fn(array $a, array $b): int => strcasecmp((string)$a['display_name'], (string)$b['display_name']));

        $minorityGender = (string)($legislature['minority_gender'] ?? '');
        $minorityMinimum = (int)($legislature['minority_minimum_seats'] ?? 0);
        $attendingMinority = count(array_filter(
            $regulars,
            static fn(array $member): bool => !isset($absent[(int)$member['id']])
                && ($member['gender'] ?? '') === $minorityGender
        ));
        $vacancies = array_values(array_filter(
            $regulars,
            static fn(array $member): bool => isset($absent[(int)$member['id']])
        ));

        $used = [];
        $fallbackSeats = [];
        $replacements = [];
        foreach ($vacancies as $vacancy) {
            $minorityRequired = $attendingMinority < $minorityMinimum;
            $replacement = $this->candidateFromList(
                (int)$vacancy['list_id'],
                $candidatesByList,
                $absent,
                $used,
                $minorityRequired ? $minorityGender : null
            );
            if ($replacement === null) {
                $fallbackListId = $this->nextFallbackListId($listsById, $fallbackSeats, (int)$vacancy['list_id']);
                $replacement = $this->candidateFromList(
                    $fallbackListId,
                    $candidatesByList,
                    $absent,
                    $used,
                    $minorityRequired ? $minorityGender : null
                );
                if ($replacement !== null) {
                    $fallbackSeats[$fallbackListId] = ($fallbackSeats[$fallbackListId] ?? 0) + 1;
                }
            }
            if ($replacement === null) {
                throw new \RuntimeException('Fuer ' . $vacancy['display_name'] . ' konnte kein zulaessiges Ersatzmitglied bestimmt werden.');
            }

            $used[(int)$replacement['id']] = true;
            if (($replacement['gender'] ?? '') === $minorityGender) {
                $attendingMinority++;
            }
            $replacement['replacement_for_uid'] = (string)$vacancy['user_uid'];
            $replacement['replacement_for_name'] = (string)$vacancy['display_name'];
            $replacement['invitation_type'] = 'replacement';
            $replacements[] = $replacement;
        }

        if ($attendingMinority < $minorityMinimum) {
            throw new \RuntimeException('Die konfigurierte Mindestzahl des Minderheitengeschlechts kann mit den verfuegbaren Ersatzmitgliedern nicht eingehalten werden.');
        }

        $recipients = [];
        foreach ($regulars as $regular) {
            $regular['invitation_type'] = isset($absent[(int)$regular['id']]) ? 'absent' : 'initial';
            $regular['absence_excused'] = isset($absent[(int)$regular['id']]) ? 'bestaetigt' : '';
            $regular['replacement_for_uid'] = '';
            $regular['replacement_for_name'] = '';
            $recipients[] = $regular;
        }
        foreach ($replacements as $replacement) {
            $replacement['absence_excused'] = '';
            $recipients[] = $replacement;
        }

        return $recipients;
    }

    private function candidateFromList(
        int $listId,
        array $candidatesByList,
        array $absent,
        array $used,
        ?string $requiredGender
    ): ?array {
        foreach (($candidatesByList[$listId] ?? []) as $candidate) {
            $id = (int)$candidate['id'];
            if (isset($absent[$id]) || isset($used[$id])) {
                continue;
            }
            if ($requiredGender !== null && ($candidate['gender'] ?? '') !== $requiredGender) {
                continue;
            }
            return $candidate;
        }
        return null;
    }

    private function nextFallbackListId(array $listsById, array $fallbackSeats, int $exhaustedListId): int {
        $quotients = [];
        foreach ($listsById as $listId => $list) {
            if ($listId === $exhaustedListId) {
                continue;
            }
            $divisor = (int)($list['seat_count'] ?? 0) + (int)($fallbackSeats[$listId] ?? 0) + 1;
            $quotients[$listId] = (int)($list['vote_count'] ?? 0) / max(1, $divisor);
        }
        if ($quotients === []) {
            throw new \RuntimeException('Die erschoepfte Vorschlagsliste kann nicht durch eine andere Liste ersetzt werden.');
        }
        arsort($quotients, SORT_NUMERIC);
        $listIds = array_keys($quotients);
        if (count($listIds) > 1 && abs($quotients[$listIds[0]] - $quotients[$listIds[1]]) < 0.0000001) {
            throw new \RuntimeException('Die naechste Vorschlagsliste ist stimmengleich; die erforderliche Losentscheidung muss ausserhalb der App dokumentiert werden.');
        }
        return (int)$listIds[0];
    }
}
