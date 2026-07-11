<?php

declare(strict_types=1);

namespace OCA\BrTop\Service;

use OCA\BrTop\Model\AgendaItem;
use OCA\BrTop\Store\DocumentStore;
use OCA\BrTop\Store\MeetingStore;
use OCA\BrTop\Store\ProtocolBlockStore;

class MeetingStateService {
    public function __construct(
        private MeetingStore $meetingStore,
        private DocumentStore $documentStore,
        private ProtocolBlockStore $protocolBlockStore,
        private InvitationSnapshotService $invitationSnapshotService
    ) {
    }

    public function meetingsForOwner(string $uid): array {
        $meetings = $this->meetingStore->recentForOwner($uid);
        $payload = [];

        foreach ($meetings as $meeting) {
            $meetingId = (int)$meeting->id;
            $meeting->setAgendaItems(
                $this->attachProtocolBlocks($meetingId, $meeting->agendaItems())
            );
            $meeting->setDocuments($this->documentStore->forMeeting($meetingId));
            $meeting->setInvitationRecipients($this->invitationSnapshotService->recipientsForMeeting($meetingId));
            $payload[] = $meeting->toArray();
        }

        return $payload;
    }

    private function attachProtocolBlocks(int $meetingId, array $tops): array {
        $blocksByTop = $this->protocolBlockStore->groupedForMeeting($meetingId);

        foreach ($tops as $top) {
            if ($top instanceof AgendaItem && $top->id !== null) {
                $top->protocolBlocks = $blocksByTop[$top->id] ?? [];
            }
        }

        return $tops;
    }
}
