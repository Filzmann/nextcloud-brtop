<?php

declare(strict_types=1);


use OCA\BrTop\Model\AgendaItem;
use OCA\BrTop\Model\ProtocolBlock;
use OCA\BrTop\Service\AgendaService;
use OCA\BrTop\Service\ProtocolBlockService;
use OCA\BrTop\Store\ProtocolBlockStore;
use function OCA\BrTop\Tests\assertSameValue;
use function OCA\BrTop\Tests\assertThrows;

$agendaService = new class extends AgendaService {
    public bool $itemExists = true;

    public function __construct() {
    }

    public function itemForMeeting(int $meetingId, int $topId): ?AgendaItem {
        if (!$this->itemExists) {
            return null;
        }

        return new AgendaItem([
            'id' => $topId,
            'meeting_id' => $meetingId,
            'subject' => 'Test TOP',
        ]);
    }
};

$protocolBlockStore = new class extends ProtocolBlockStore {
    public string $lastBlockType = '';
    public bool $updateResult = true;

    public function __construct() {
    }

    public function addBlock(int $meetingId, int $topId, string $blockType, string $content): ProtocolBlock {
        $this->lastBlockType = $blockType;

        return new ProtocolBlock([
            'id' => 5,
            'meeting_id' => $meetingId,
            'top_id' => $topId,
            'block_position' => 1,
            'block_type' => $blockType,
            'content' => $content,
        ]);
    }

    public function updateContent(int $meetingId, int $topId, int $blockId, string $content): bool {
        return $this->updateResult;
    }
};

$service = new ProtocolBlockService($agendaService, $protocolBlockStore);

$block = $service->addBlock(3, 7, 'unknown', 'Notiz');
assertSameValue('text', $protocolBlockStore->lastBlockType, 'Unknown protocol block types should be normalized to text.');
assertSameValue('Notiz', $block->content, 'Added protocol blocks should keep the submitted content.');

$service->updateBlockContent(3, 7, 5, 'Aktualisierte Notiz');

$protocolBlockStore->updateResult = false;
assertThrows(
    static fn() => $service->updateBlockContent(3, 7, 404, 'fehlt'),
    \OutOfBoundsException::class,
    'Missing protocol blocks should throw a not-found exception.'
);

$agendaService->itemExists = false;
assertThrows(
    static fn() => $service->addBlock(3, 7, 'text', 'Notiz'),
    \OutOfBoundsException::class,
    'Missing agenda items should throw a not-found exception.'
);

echo 'ProtocolBlockService smoke tests passed' . PHP_EOL;
