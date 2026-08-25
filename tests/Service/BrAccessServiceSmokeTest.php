<?php

declare(strict_types=1);

namespace {
    if (!interface_exists(\OCP\IGroupManager::class)) {
        eval('namespace OCP; interface IGroupManager { public function isAdmin($userId); public function isInGroup($userId, $group); }');
    }
}

namespace OCA\BrTop\Tests {

    use OCA\BrTop\Exception\AccessDeniedException;
    use OCA\BrTop\Service\BrAccessService;
    use OCA\BrTop\Service\BrGroupsService;
    use OCA\BrTop\Service\TemporaryAdminAccessChecker;
    use OCP\IGroupManager;

    $groups = new class implements IGroupManager {
        public function isAdmin($userId): bool { return $userId === 'admin'; }
        public function isInGroup($userId, $group): bool { return $userId === 'br-member' && $group === 'BR Custom'; }
    };
    $groupNames = new class extends BrGroupsService {
        public function __construct() {}
        public function memberGroupName(): string { return 'BR Custom'; }
    };
    $adminAccess = new class implements TemporaryAdminAccessChecker { public bool $active=false; public function hasActiveGrant(string $uid):bool{return $this->active;} };
    $service = new BrAccessService($groups, $groupNames, $adminAccess);

    assertSameValue(false, $service->canUse('admin'), 'Native Nextcloud admins must not receive BRTop access without an app-local grant.');
    $adminAccess->active=true;
    assertSameValue(true, $service->canUse('admin'), 'An active app-local grant should enable BRTop admin access.');
    assertSameValue(true, $service->canUse('br-member'), 'Members of the configured BR group should be allowed to use BRTop.');
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
