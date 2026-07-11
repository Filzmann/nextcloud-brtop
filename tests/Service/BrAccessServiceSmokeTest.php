<?php

declare(strict_types=1);

namespace {
    if (!interface_exists(\OCP\IGroupManager::class)) {
        eval('namespace OCP; interface IGroupManager { public function isAdmin($userId); public function isInGroup($userId, $group); }');
    }
}

namespace OCA\BrTop\Tests {
    require __DIR__ . '/../helpers.php';
    require_once __DIR__ . '/../../lib/Exception/AccessDeniedException.php';
    require_once __DIR__ . '/../../lib/Service/BrGroupsService.php';
    require_once __DIR__ . '/../../lib/Service/BrAccessService.php';

    use OCA\BrTop\Exception\AccessDeniedException;
    use OCA\BrTop\Service\BrAccessService;
    use OCP\IGroupManager;

    $groups = new class implements IGroupManager {
        public function isAdmin($userId): bool { return $userId === 'admin'; }
        public function isInGroup($userId, $group): bool { return $userId === 'br-member' && $group === 'Betriebsrat'; }
    };
    $service = new BrAccessService($groups);

    assertSameValue(true, $service->canUse('admin'), 'Nextcloud admins should be allowed to use BRTop.');
    assertSameValue(true, $service->canUse('br-member'), 'Members of the Betriebsrat group should be allowed to use BRTop.');
    assertSameValue(false, $service->canUse('other-user'), 'Unrelated authenticated users must be denied.');
    assertThrows(
        static fn() => $service->assertCanUse('other-user'),
        AccessDeniedException::class,
        'The service must deny unrelated users server-side.'
    );
    assertThrows(
        static fn() => $service->assertAdmin('br-member'),
        AccessDeniedException::class,
        'Legislature management must remain restricted to Nextcloud admins.'
    );

    echo 'BrAccessService smoke tests passed' . PHP_EOL;
}
