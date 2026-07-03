<?php

declare(strict_types=1);

require __DIR__ . '/../../lib/Model/AgendaItem.php';
require __DIR__ . '/../../lib/Model/ProtocolBlock.php';
require __DIR__ . '/../../lib/Service/ProtocolBlockService.php';
require __DIR__ . '/../../lib/Service/AgendaService.php';
require __DIR__ . '/../../lib/Store/ProtocolBlockStore.php';

use OCA\BrTop\Model\AgendaItem;
use OCA\BrTop\Model\ProtocolBlock;
use OCA\BrTop\Service\AgendaService;
use OCA\BrTop\Service\ProtocolBlockService;
use OCA\BrTop\Store\ProtocolBlockStore;

$checkSame = static function ($expected, $actual, string $message): void {
    if ($expected !== $actual) {
        fwrite(STDERR, $message . PHP_EOL);
        fwrite(STDERR, 'Expected: ' . var_export($expected, true) . PHP_EOL);
        fwrite(STDERR, 'Actual:   ' . var_export($actual, true) . PHP_EOL);
        exit(1);
    }
};

$checkThrows = static function (callable $callback, string $message): void {
    try {
        $callback();
    } catch (\OutOfBoundsException) {
        return;
    }

    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
};

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
$checkSame('text', $protocolBlockStore->lastBlockType, 'Unknown protocol block types should be normalized to text.');
$checkSame('Notiz', $block->content, 'Added protocol blocks should keep the submitted content.');

$service->updateBlockContent(3, 7, 5, 'Aktualisierte Notiz');

$protocolBlockStore->updateResult = false;
$checkThrows(
    static fn() => $service->updateBlockContent(3, 7, 404, 'fehlt'),
    'Missing protocol blocks should throw a not-found exception.'
);

$agendaService->itemExists = false;
$checkThrows(
    static fn() => $service->addBlock(3, 7, 'text', 'Notiz'),
    'Missing agenda items should throw a not-found exception.'
);

echo 'ProtocolBlockService smoke tests passed' . PHP_EOL;
