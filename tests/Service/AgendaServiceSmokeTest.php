<?php

declare(strict_types=1);

require __DIR__ . '/../../lib/Model/ModelApiTrait.php';
require __DIR__ . '/../../lib/Model/ProtocolBlock.php';
require __DIR__ . '/../../lib/Model/AgendaItem.php';
require __DIR__ . '/../../lib/Repository/AgendaItemRepository.php';
require __DIR__ . '/../../lib/Store/AgendaItemStore.php';
require __DIR__ . '/../../lib/Service/AgendaTreeService.php';
require __DIR__ . '/../../lib/Service/AgendaAttachmentService.php';
require __DIR__ . '/../../lib/Service/AgendaTemplateService.php';
require __DIR__ . '/../../lib/Service/AgendaService.php';

use OCA\BrTop\Model\AgendaItem;
use OCA\BrTop\Repository\AgendaItemRepository;
use OCA\BrTop\Service\AgendaAttachmentService;
use OCA\BrTop\Service\AgendaService;
use OCA\BrTop\Service\AgendaTemplateService;
use OCA\BrTop\Service\AgendaTreeService;
use OCA\BrTop\Store\AgendaItemStore;

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
    } catch (\InvalidArgumentException) {
        return;
    }

    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
};

$repository = new class extends AgendaItemRepository {
    public array $items = [
        ['id' => 1, 'meeting_id' => 3, 'position' => 1, 'parent_id' => null, 'level' => 1, 'type' => 'section', 'subject' => 'Root'],
        ['id' => 2, 'meeting_id' => 3, 'position' => 2, 'parent_id' => 1, 'level' => 2, 'type' => 'discussion', 'subject' => 'Child'],
        ['id' => 3, 'meeting_id' => 3, 'position' => 3, 'parent_id' => 2, 'level' => 3, 'type' => 'discussion', 'subject' => 'Grandchild'],
    ];
    public array $hierarchyUpdates = [];

    public function __construct() {
    }

    public function findForMeeting(int $meetingId): array {
        return $this->items;
    }

    public function updateHierarchyState(
        int $meetingId,
        int $topId,
        ?int $parentId,
        int $level,
        int $position
    ): void {
        $this->hierarchyUpdates[] = [
            'meeting_id' => $meetingId,
            'id' => $topId,
            'parent_id' => $parentId,
            'level' => $level,
            'position' => $position,
        ];
    }
};

$store = new class($repository) extends AgendaItemStore {
    public ?AgendaItem $savedItem = null;

    public function __construct(private AgendaItemRepository $repository) {
    }

    public function save(AgendaItem $item): int {
        $item->id = 10;
        $this->savedItem = new AgendaItem($item->toRepositoryData());
        $this->repository->items[] = $item->toRepositoryData();

        return $item->id;
    }
};

$templateService = new class extends AgendaTemplateService {
    public function __construct() {
    }

    public function normalizeKind(string $kind, string $type, bool $requiresResolution): string {
        if ($requiresResolution) {
            return 'resolution';
        }

        return $kind !== '' ? $kind : 'discussion';
    }
};

$service = new AgendaService(
    $repository,
    $store,
    $templateService,
    new AgendaTreeService(),
    new AgendaAttachmentService()
);

$id = $service->addItem(
    3,
    'other',
    'Neuer Unterpunkt',
    '',
    '',
    '',
    false,
    'discussion',
    1,
    '',
    '',
    '',
    0
);

$checkSame(10, $id, 'Added agenda items should return their id.');
$checkSame(1, $store->savedItem->parentId, 'New agenda items should keep a valid parent id.');
$checkSame(2, $store->savedItem->level, 'New agenda items below a root parent should be level 2.');
$checkSame('', $store->savedItem->attachmentPaths, 'Attachment paths should still be normalized.');

$checkThrows(
    static fn() => $service->addItem(3, 'other', 'Fehlt', '', '', '', false, 'discussion', 404, '', '', '', 0),
    'Missing parent agenda items should be rejected.'
);

$checkThrows(
    static fn() => $service->addItem(3, 'other', 'Zu tief', '', '', '', false, 'discussion', 3, '', '', '', 0),
    'Parent agenda items on level 3 should be rejected.'
);

echo 'AgendaService smoke tests passed' . PHP_EOL;
