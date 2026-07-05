<?php

declare(strict_types=1);

namespace OCA\BrTop\Service;

use OCA\BrTop\Model\Meeting;
use OCA\BrTop\Repository\DocumentRepository;
use OCA\BrTop\Repository\MeetingRepository;
use OCA\BrTop\Store\MeetingStore;
use OCA\BrTop\Store\ProtocolBlockStore;

class MeetingService {
    public function __construct(
        private BrtopSettingsService $settings,
        private MeetingScheduleService $meetingScheduleService,
        private AgendaTemplateService $agendaTemplateService,
        private AgendaService $agendaService,
        private MeetingStore $meetingStore,
        private MeetingRepository $meetingRepository,
        private DocumentRepository $documentRepository,
        private ProtocolBlockStore $protocolBlockStore
    ) {
    }

    public function assertOwnedMeeting(int $meetingId, string $uid): Meeting {
        $meeting = $this->meetingStore->getOwned($meetingId, $uid);
        if ($meeting === null) {
            throw new \RuntimeException('Sitzung nicht gefunden oder keine Berechtigung.');
        }

        return $meeting;
    }

    public function create(
        string $uid,
        string $title,
        string $meetingDate,
        string $meetingTime,
        string $location,
        string $meetingType,
        string $committeeCode
    ): int {
        $meetingType = $this->settings->normalizeMeetingType($meetingType);
        $committeeCode = $this->normalizeCommitteeCodeForMeeting($meetingType, $committeeCode);

        $meeting = $this->newMeeting(
            $uid,
            $title,
            $meetingDate,
            $meetingTime,
            $location,
            $meetingType,
            $committeeCode
        );

        return $meeting->save();
    }

    public function planNextRegular(string $uid): array {
        $defaults = $this->meetingScheduleService->nextRegularMeetingDefaults();
        $items = $this->agendaTemplateService->regularBrMeetingItems();

        $id = $this->meetingRepository->transactional(function () use ($uid, $defaults, $items): int {
            $meeting = $this->newMeeting(
                $uid,
                $defaults['title'],
                $defaults['meetingDate'],
                $defaults['meetingTime'],
                $defaults['location'],
                'regular_br',
                '',
                $defaults['invitationDate'],
                'planned'
            );
            $meetingId = $meeting->save();

            $this->agendaService->addTemplateItems($meetingId, $items);

            return $meetingId;
        });

        return [
            'id' => $id,
            'meetingDate' => $defaults['meetingDate'],
            'invitationDate' => $defaults['invitationDate'],
            'agendaItemsCreated' => count($items),
        ];
    }

    public function delete(int $meetingId): void {
        $this->meetingRepository->transactional(function () use ($meetingId): void {
            $this->protocolBlockStore->deleteForMeeting($meetingId);
            $this->documentRepository->deleteForMeeting($meetingId);
            $this->agendaService->deleteItemsForMeeting($meetingId);
            $this->meetingRepository->deleteInvitationRecipients($meetingId);
            $this->meetingRepository->deleteById($meetingId);
        });
    }

    private function normalizeCommitteeCodeForMeeting(string $meetingType, string $committeeCode): string {
        if ($meetingType === 'works_committee') {
            return 'BA';
        }

        if ($meetingType !== 'committee') {
            return '';
        }

        return $this->settings->normalizeCommitteeCode($committeeCode);
    }

    private function newMeeting(
        string $uid,
        string $title,
        string $meetingDate,
        string $meetingTime,
        string $location,
        string $meetingType,
        string $committeeCode,
        ?string $invitationDate = null,
        string $invitationStatus = 'not_created',
        string $status = 'draft'
    ): Meeting {
        return new Meeting([
            'owner_uid' => $uid,
            'title' => $title,
            'meeting_date' => $meetingDate,
            'meeting_time' => $meetingTime,
            'location' => $location,
            'meeting_type' => $meetingType,
            'committee_code' => $committeeCode,
            'invitation_date' => $invitationDate,
            'invitation_status' => $invitationStatus,
            'status' => $status,
        ], $this->meetingStore);
    }
}
