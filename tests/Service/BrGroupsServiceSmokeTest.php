<?php

declare(strict_types=1);

namespace {
    if (!interface_exists(\OCP\IGroupManager::class)) {
        eval('namespace OCP; interface IGroupManager { public function groupExists($gid); public function createGroup($gid); }');
    }

    require __DIR__ . '/../../lib/Service/BrGroupsService.php';

    use OCA\BrTop\Service\BrGroupsService;
    use OCP\IGroupManager;

    $checkSame = static function ($expected, $actual, string $message): void {
        if ($expected !== $actual) {
            fwrite(STDERR, $message . PHP_EOL);
            fwrite(STDERR, 'Expected: ' . var_export($expected, true) . PHP_EOL);
            fwrite(STDERR, 'Actual:   ' . var_export($actual, true) . PHP_EOL);
            exit(1);
        }
    };

    $groupManager = new class(['Betriebsrat']) implements IGroupManager {
        public array $groups = [];

        public function __construct(array $groups) {
            foreach ($groups as $group) {
                $this->groups[$group] = true;
            }
        }

        public function groupExists($gid): bool {
            return isset($this->groups[$gid]);
        }

        public function createGroup($gid): object {
            $this->groups[$gid] = true;

            return new \stdClass();
        }
    };

    $service = new BrGroupsService($groupManager);

    $checkSame(
        ['Betriebsrat-Vorsitzende', 'Betriebsrat-Stellvertreter'],
        $service->ensureRequiredGroups(),
        'Only missing BR groups should be created.'
    );
    $checkSame(
        [],
        $service->ensureRequiredGroups(),
        'BR group creation should be idempotent.'
    );

    echo 'BrGroupsService smoke tests passed' . PHP_EOL;
}
