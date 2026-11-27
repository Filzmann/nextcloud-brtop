<?php

declare(strict_types=1);

namespace OCA\BrTop\Service;
use OCP\IL10N;

class BrRosterService {
    public function __construct(private ?IL10N $l10n = null) {}

    /** Lokalisierung fachlicher Texte; im Test ohne IL10N identity. */
    private function t(string $text): string
    {
        return $this->l10n?->t($text) ?? $text;
    }
    public const ROLE_REGULAR = 'regular';
    public const ROLE_REPLACEMENT = 'replacement';

    public const INVITATION_INITIAL = 'initial';
    public const INVITATION_REPLACEMENT = 'replacement';

    private const EXCUSED_REASONS = ['AU', 'U', 'FoBi'];

    public function demoCouncil(): array {
        return [
            'councilSize' => 13,
            'minorityGender' => 'female',
            'absenceCalendarName' => 'BR-Abwesenheiten',
            'lists' => [
                ['name' => $this->t('Liste Zukunft'), 'seats' => 10],
                ['name' => $this->t('Liste Dialog'), 'seats' => 3],
            ],
            'members' => $this->demoMembers(),
        ];
    }

    public function isDemoUserId(string $uid): bool {
        foreach ($this->demoMembers() as $member) {
            if ($member['user_uid'] === $uid) {
                return true;
            }
        }

        return false;
    }

    public function demoInvitationRecipients(int $meetingId, array $calendarAbsences = []): array {
        $members = $this->demoMembers();
        $byUid = [];
        foreach ($members as $member) {
            $byUid[$member['user_uid']] = $member;
        }

        $recipients = [];
        $position = 1;
        foreach ($this->regularMembersSorted($members) as $member) {
            if ($member['role'] !== self::ROLE_REGULAR) {
                continue;
            }

            $absence = $calendarAbsences[$member['user_uid']] ?? null;
            $recipients[] = $this->recipientRow($meetingId, $position, $member, self::INVITATION_INITIAL, null, $absence);
            $position++;
        }

        $usedReplacementUids = [];
        foreach ($this->excusedRegularsForReplacement($members, $calendarAbsences) as $regular) {
            $absence = $calendarAbsences[$regular['user_uid']] ?? null;
            $replacement = $this->replacementForRegular($regular, $members, $usedReplacementUids);
            if ($replacement === null) {
                continue;
            }
            $usedReplacementUids[] = $replacement['user_uid'];

            $recipients[] = $this->recipientRow(
                $meetingId,
                $position,
                $replacement,
                self::INVITATION_REPLACEMENT,
                $regular['user_uid'],
                null
            );
            $position++;
        }

        return $recipients;
    }

    public function isExcusedReason(string $reason): bool {
        return in_array($reason, self::EXCUSED_REASONS, true);
    }

    public function absenceClass(string $reason): string {
        if ($reason === '') {
            return '';
        }

        return $this->isExcusedReason($reason) ? 'entschuldigt' : 'unentschuldigt';
    }

    private function recipientRow(
        int $meetingId,
        int $position,
        array $member,
        string $invitationType,
        ?string $replacementForUid,
        ?array $absence
    ): array {
        $reason = (string)($absence['reason'] ?? '');

        return [
            'meeting_id' => $meetingId,
            'user_uid' => $member['user_uid'],
            'display_name' => $member['display_name'],
            'email' => $member['email'],
            'group_name' => 'Betriebsrat',
            'snapshot_position' => $position,
            'member_role' => $member['role'],
            'invitation_type' => $invitationType,
            'list_name' => $member['list_name'],
            'list_seats' => $member['list_seats'],
            'list_rank' => $member['list_rank'],
            'gender' => $member['gender'],
            'minority_gender' => 'female',
            'absence_reason' => $reason,
            'absence_excused' => $this->absenceClass($reason),
            'replacement_for_uid' => $replacementForUid ?? '',
            'replacement_for_name' => $replacementForUid === null ? '' : $this->replacementForName($replacementForUid),
        ];
    }

    private function regularMembersSorted(array $members): array {
        $regular = array_values(array_filter(
            $members,
            static fn(array $member): bool => $member['role'] === self::ROLE_REGULAR
        ));

        usort($regular, static function (array $a, array $b): int {
            return strcasecmp($a['last_name'], $b['last_name'])
                ?: strcasecmp($a['display_name'], $b['display_name']);
        });

        return $regular;
    }

    private function excusedRegularsForReplacement(array $members, array $calendarAbsences): array {
        $regulars = array_values(array_filter(
            $this->regularMembersSorted($members),
            fn(array $member): bool => isset($calendarAbsences[$member['user_uid']])
                && $this->isExcusedReason((string)($calendarAbsences[$member['user_uid']]['reason'] ?? ''))
        ));

        usort($regulars, static function (array $a, array $b): int {
            $aMinority = $a['gender'] === 'female' ? 0 : 1;
            $bMinority = $b['gender'] === 'female' ? 0 : 1;

            return $aMinority <=> $bMinority
                ?: strcasecmp($a['last_name'], $b['last_name']);
        });

        return $regulars;
    }

    private function replacementForRegular(array $regular, array $members, array $usedReplacementUids): ?array {
        $candidates = array_values(array_filter(
            $members,
            static fn(array $member): bool => $member['role'] === self::ROLE_REPLACEMENT
                && $member['list_name'] === $regular['list_name']
                && !in_array($member['user_uid'], $usedReplacementUids, true)
        ));

        usort($candidates, static function (array $a, array $b): int {
            return (int)$a['list_rank'] <=> (int)$b['list_rank'];
        });

        if ($regular['gender'] === 'female') {
            foreach ($candidates as $candidate) {
                if ($candidate['gender'] === 'female') {
                    return $candidate;
                }
            }
        }

        return $candidates[0] ?? null;
    }

    private function replacementForName(string $uid): string {
        foreach ($this->demoMembers() as $member) {
            if ($member['user_uid'] === $uid) {
                return $member['display_name'];
            }
        }

        return '';
    }

    private function demoMembers(): array {
        return [
            $this->member('brtop-lz-01', $this->t('Anna Berger'), 'Berger', 'female', $this->t('Liste Zukunft'), 10, 1, self::ROLE_REGULAR),
            $this->member('brtop-lz-02', $this->t('Ben Schneider'), 'Schneider', 'male', $this->t('Liste Zukunft'), 10, 2, self::ROLE_REGULAR),
            $this->member('brtop-lz-03', $this->t('Clara Neumann'), 'Neumann', 'female', $this->t('Liste Zukunft'), 10, 3, self::ROLE_REGULAR),
            $this->member('brtop-lz-04', $this->t('David Krüger'), $this->t('Krüger'), 'male', $this->t('Liste Zukunft'), 10, 4, self::ROLE_REGULAR),
            $this->member('brtop-lz-05', $this->t('Elif Yilmaz'), 'Yilmaz', 'female', $this->t('Liste Zukunft'), 10, 5, self::ROLE_REGULAR),
            $this->member('brtop-lz-06', $this->t('Florian Becker'), 'Becker', 'male', $this->t('Liste Zukunft'), 10, 6, self::ROLE_REGULAR),
            $this->member('brtop-lz-07', $this->t('Gina Hoffmann'), 'Hoffmann', 'female', $this->t('Liste Zukunft'), 10, 7, self::ROLE_REGULAR),
            $this->member('brtop-lz-08', $this->t('Hannes Wolf'), 'Wolf', 'male', $this->t('Liste Zukunft'), 10, 8, self::ROLE_REGULAR),
            $this->member('brtop-lz-09', $this->t('Ivan Braun'), 'Braun', 'male', $this->t('Liste Zukunft'), 10, 9, self::ROLE_REGULAR),
            $this->member('brtop-lz-10', $this->t('Jonas Richter'), 'Richter', 'male', $this->t('Liste Zukunft'), 10, 10, self::ROLE_REGULAR),
            $this->member('brtop-ld-01', $this->t('Katrin Sommer'), 'Sommer', 'female', $this->t('Liste Dialog'), 3, 1, self::ROLE_REGULAR),
            $this->member('admin', $this->t('Admin'), $this->t('Admin'), 'male', $this->t('Liste Dialog'), 3, 2, self::ROLE_REGULAR),
            $this->member('brtop-ld-03', $this->t('Markus Lehmann'), 'Lehmann', 'male', $this->t('Liste Dialog'), 3, 3, self::ROLE_REGULAR),
            $this->member('brtop-lz-e01', $this->t('Nora Seidel'), 'Seidel', 'female', $this->t('Liste Zukunft'), 10, 11, self::ROLE_REPLACEMENT),
            $this->member('brtop-lz-e02', $this->t('Oliver Hartmann'), 'Hartmann', 'male', $this->t('Liste Zukunft'), 10, 12, self::ROLE_REPLACEMENT),
            $this->member('brtop-lz-e03', $this->t('Paula König'), $this->t('König'), 'female', $this->t('Liste Zukunft'), 10, 13, self::ROLE_REPLACEMENT),
            $this->member('brtop-lz-e04', $this->t('Robert Lang'), 'Lang', 'male', $this->t('Liste Zukunft'), 10, 14, self::ROLE_REPLACEMENT),
            $this->member('brtop-lz-e05', $this->t('Sina Mertens'), 'Mertens', 'female', $this->t('Liste Zukunft'), 10, 15, self::ROLE_REPLACEMENT),
            $this->member('brtop-lz-e06', $this->t('Tobias Ernst'), 'Ernst', 'male', $this->t('Liste Zukunft'), 10, 16, self::ROLE_REPLACEMENT),
            $this->member('brtop-lz-e07', $this->t('Ulrike Vogler'), 'Vogler', 'female', $this->t('Liste Zukunft'), 10, 17, self::ROLE_REPLACEMENT),
            $this->member('brtop-lz-e08', $this->t('Viktor Schramm'), 'Schramm', 'male', $this->t('Liste Zukunft'), 10, 18, self::ROLE_REPLACEMENT),
            $this->member('brtop-ld-e01', $this->t('Quirin Brandt'), 'Brandt', 'male', $this->t('Liste Dialog'), 3, 4, self::ROLE_REPLACEMENT),
            $this->member('brtop-ld-e02', $this->t('Rita Vogt'), 'Vogt', 'female', $this->t('Liste Dialog'), 3, 5, self::ROLE_REPLACEMENT),
            $this->member('brtop-ld-e03', $this->t('Selma Aydin'), 'Aydin', 'female', $this->t('Liste Dialog'), 3, 6, self::ROLE_REPLACEMENT),
            $this->member('brtop-ld-e04', $this->t('Thomas Krämer'), $this->t('Krämer'), 'male', $this->t('Liste Dialog'), 3, 7, self::ROLE_REPLACEMENT),
            $this->member('brtop-ld-e05', $this->t('Ute Lenz'), 'Lenz', 'female', $this->t('Liste Dialog'), 3, 8, self::ROLE_REPLACEMENT),
            $this->member('brtop-ld-e06', $this->t('Volker Meier'), 'Meier', 'male', $this->t('Liste Dialog'), 3, 9, self::ROLE_REPLACEMENT),
            $this->member('brtop-ld-e07', $this->t('Wiebke Peters'), 'Peters', 'female', $this->t('Liste Dialog'), 3, 10, self::ROLE_REPLACEMENT),
            $this->member('brtop-ld-e08', $this->t('Xaver Scholz'), 'Scholz', 'male', $this->t('Liste Dialog'), 3, 11, self::ROLE_REPLACEMENT),
        ];
    }

    private function member(
        string $uid,
        string $displayName,
        string $lastName,
        string $gender,
        string $listName,
        int $listSeats,
        int $listRank,
        string $role
    ): array {
        return [
            'user_uid' => $uid,
            'display_name' => $displayName,
            'last_name' => $lastName,
            'email' => $uid . '@example.invalid',
            'gender' => $gender,
            'list_name' => $listName,
            'list_seats' => $listSeats,
            'list_rank' => $listRank,
            'role' => $role,
        ];
    }
}
