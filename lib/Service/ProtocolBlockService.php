<?php

declare(strict_types=1);

namespace OCA\BrTop\Service;

use OCA\BrTop\Model\ProtocolBlock;
use OCA\BrTop\Store\ProtocolBlockStore;

class ProtocolBlockService {
    public function __construct(
        private AgendaService $agendaService,
        private ProtocolBlockStore $protocolBlockStore
    ) {
    }

    public function addBlock(
        int $meetingId,
        int $topId,
        string $blockType = 'text',
        string $content = ''
    ): ProtocolBlock {
        $this->assertAgendaItemExists($meetingId, $topId);

        return $this->protocolBlockStore->addBlock(
            $meetingId,
            $topId,
            $this->normalizeBlockType($blockType),
            $content
        );
    }

    public function updateBlockContent(
        int $meetingId,
        int $topId,
        int $blockId,
        string $content = ''
    ): void {
        $this->assertAgendaItemExists($meetingId, $topId);

        $updated = $this->protocolBlockStore->updateContent($meetingId, $topId, $blockId, $content);
        if (!$updated) {
            throw new \OutOfBoundsException('Protokollblock nicht gefunden.');
        }
    }

    private function assertAgendaItemExists(int $meetingId, int $topId): void {
        if ($this->agendaService->itemForMeeting($meetingId, $topId) === null) {
            throw new \OutOfBoundsException('TOP nicht gefunden.');
        }
    }

    private function normalizeBlockType(string $blockType): string {
        if ($blockType !== 'text') {
            return 'text';
        }

        return $blockType;
    }
}
