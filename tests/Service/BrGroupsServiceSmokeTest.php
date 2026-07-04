<?php

declare(strict_types=1);

namespace {
    if (!interface_exists(\OCP\IGroupManager::class)) {
        eval('namespace OCP; interface IGroupManager { public function groupExists($gid); public function createGroup($gid); }');
    }

    require __DIR__ . '/../helpers.php';
    require __DIR__ . '/../../../localbase/lib/Service/GroupProvisioningService.php';
    require __DIR__ . '/../../lib/Service/BrGroupsService.php';

    use OCA\BrTop\Service\BrGroupsService;
    use OCA\LocalBase\Service\GroupProvisioningService;
    use OCP\IGroupManager;
    use function OCA\BrTop\Tests\assertSameValue;

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

    $service = new BrGroupsService(new GroupProvisioningService($groupManager));

    assertSameValue(
        ['Betriebsrat-Vorsitzende', 'Betriebsrat-Stellvertreter'],
        $service->ensureRequiredGroups(),
        'Only missing BR groups should be created.'
    );
    assertSameValue(
        [],
        $service->ensureRequiredGroups(),
        'BR group creation should be idempotent.'
    );

    echo 'BrGroupsService smoke tests passed' . PHP_EOL;
}
