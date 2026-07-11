<?php

declare(strict_types=1);

namespace OCA\BrTop\Service;

use OCA\BrTop\Repository\MeetingAbsenceRepository;
use OCA\BrTop\Repository\MeetingRepository;

class MeetingAbsenceService {
    public function __construct(
        private MeetingRepository $meetingRepository,
        private MeetingAbsenceRepository $absenceRepository,
        private LegislatureService $legislatureService,
        private BrCalendarAbsenceService $calendarService
    ) {
    }

    public function prepare(int $meetingId): array {
        [$meeting, $legislature] = $this->meetingAndLegislature($meetingId);
        $memberIdsByUid = [];
        $rosterMembers = [];
        foreach (($legislature['lists'] ?? []) as $list) {
            foreach (($list['members'] ?? []) as $member) {
                $member['list_name'] = (string)$list['name'];
                $rosterMembers[] = $member;
                $memberIdsByUid[(string)$member['user_uid']] = (int)$member['id'];
            }
        }

        $suggestedIds = [];
        foreach ($this->calendarService->suggestedMemberUids((string)$meeting['meeting_date'], $legislature) as $uid) {
            if (isset($memberIdsByUid[$uid])) {
                $suggestedIds[] = $memberIdsByUid[$uid];
            }
        }
        $this->absenceRepository->addSuggestions($meetingId, $suggestedIds);

        $statuses = [];
        foreach ($this->absenceRepository->forMeeting($meetingId) as $row) {
            $statuses[(int)$row['member_id']] = (string)$row['status'];
        }
        foreach ($rosterMembers as &$member) {
            $member['absence_status'] = $statuses[(int)$member['id']] ?? '';
        }
        unset($member);

        return [
            'meeting_id' => $meetingId,
            'meeting_date' => (string)$meeting['meeting_date'],
            'legislature_id' => (int)$legislature['id'],
            'members' => $rosterMembers,
            'snapshot_locked' => $this->meetingRepository->hasInvitationSnapshot($meetingId),
            'reviewed' => $this->absenceRepository->isReviewed($meetingId),
        ];
    }

    public function saveConfirmed(int $meetingId, array $memberIds, string $uid): array {
        if ($this->meetingRepository->hasInvitationSnapshot($meetingId)) {
            throw new \RuntimeException('Die Verhinderungen sind durch den Einladungssnapshot bereits versiegelt.');
        }
        $prepared = $this->prepare($meetingId);
        $allowed = [];
        foreach ($prepared['members'] as $member) {
            $allowed[(int)$member['id']] = true;
        }
        $confirmed = [];
        foreach (array_values(array_unique(array_map('intval', $memberIds))) as $memberId) {
            if ($memberId <= 0 || !isset($allowed[$memberId])) {
                throw new \InvalidArgumentException('Mindestens eine bestaetigte Person gehoert nicht zur aktiven Legislatur.');
            }
            $confirmed[] = $memberId;
        }
        $this->absenceRepository->replaceConfirmed($meetingId, $confirmed, $uid);
        return $this->prepare($meetingId);
    }

    public function confirmedMemberIds(int $meetingId): array {
        return array_map(
            static fn(array $row): int => (int)$row['member_id'],
            array_values(array_filter(
                $this->absenceRepository->forMeeting($meetingId),
                static fn(array $row): bool => ($row['status'] ?? '') === 'confirmed'
            ))
        );
    }

    public function assertReviewed(int $meetingId): void {
        if (!$this->absenceRepository->isReviewed($meetingId)) {
            throw new \RuntimeException('Die Verhinderungen muessen vor der ersten Einladung durch einen Admin bestaetigt werden.');
        }
    }

    public function legislatureForMeeting(int $meetingId): array {
        [, $legislature] = $this->meetingAndLegislature($meetingId);
        return $legislature;
    }

    private function meetingAndLegislature(int $meetingId): array {
        $meeting = $this->meetingRepository->findById($meetingId);
        if ($meeting === null) {
            throw new \RuntimeException('Sitzung nicht gefunden.');
        }
        $legislatureId = (int)($meeting['legislature_id'] ?? 0);
        if ($legislatureId > 0) {
            $legislature = $this->legislatureService->configuration($legislatureId);
        } else {
            $legislature = $this->legislatureService->activeForDate((string)$meeting['meeting_date']);
            $this->meetingRepository->bindLegislature($meetingId, (int)$legislature['id']);
        }
        return [$meeting, $legislature];
    }
}
