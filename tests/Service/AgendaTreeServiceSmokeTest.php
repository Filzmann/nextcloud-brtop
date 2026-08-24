<?php

declare(strict_types=1);


use OCA\BrTop\Service\AgendaTreeService;
use function OCA\BrTop\Tests\assertSameValue;

$service = new AgendaTreeService();

$tops = [
    ['id' => 1, 'parent_id' => null, 'position' => 1, 'level' => 1],
    ['id' => 2, 'parent_id' => 1, 'position' => 2, 'level' => 2],
    ['id' => 3, 'parent_id' => 2, 'position' => 3, 'level' => 3],
    ['id' => 4, 'parent_id' => null, 'position' => 4, 'level' => 1],
];

$numbered = $service->numberedItems($tops);
assertSameValue(['1', '1.1', '1.1.1', '2'], array_column($numbered, 'agenda_number'), 'Agenda numbering should follow the hierarchy.');

assertSameValue([1, 2, 3], $service->subtreeIds($tops, 1), 'Subtree IDs should include descendants in order.');
assertSameValue(3, $service->maxSubtreeLevel($tops, 1), 'Subtree max level should detect depth 3.');
assertSameValue(1, $service->previousSibling($tops, $tops[3])['id'] ?? null, 'Previous root sibling should be found by position.');

$overDeep = [
    ['id' => 1, 'parent_id' => null, 'position' => 1, 'level' => 1],
    ['id' => 2, 'parent_id' => 1, 'position' => 2, 'level' => 2],
    ['id' => 3, 'parent_id' => 2, 'position' => 3, 'level' => 3],
    ['id' => 4, 'parent_id' => 3, 'position' => 4, 'level' => 4],
];

$ordered = $service->orderedTreeItems($overDeep);
assertSameValue([1, 2, 3, 4], array_column($ordered, 'id'), 'Ordered items should keep traversal order.');
assertSameValue([1, 2, 3, 1], array_column($ordered, 'level'), 'Items deeper than level 3 should be lifted to root level.');
assertSameValue(null, $ordered[3]['parent_id'], 'Lifted items should lose their parent relation.');

$selfParented = [
    ['id' => 1, 'parent_id' => 1, 'position' => 1, 'level' => 2],
    ['id' => 2, 'parent_id' => 1, 'position' => 2, 'level' => 3],
];
$numberedSelfParented = $service->numberedItems($selfParented);
assertSameValue(['1', '1.1'], array_column($numberedSelfParented, 'agenda_number'), 'Self-parented roots should still be numbered with their descendants.');

$cyclic = [
    ['id' => 1, 'parent_id' => 2, 'position' => 1, 'level' => 1],
    ['id' => 2, 'parent_id' => 1, 'position' => 2, 'level' => 2],
    ['id' => 3, 'parent_id' => 1, 'position' => 3, 'level' => 2],
];
assertSameValue([1, 2, 3], $service->subtreeIds($cyclic, 1), 'Subtree IDs should ignore already visited items in cyclic hierarchies.');
assertSameValue(2, $service->maxSubtreeLevel($cyclic, 1), 'Subtree max level should stay finite in cyclic hierarchies.');

echo 'AgendaTreeService smoke tests passed' . PHP_EOL;
