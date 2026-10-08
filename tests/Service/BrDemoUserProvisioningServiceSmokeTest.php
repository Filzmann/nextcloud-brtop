<?php

declare(strict_types=1);

namespace OCP {
    if (!interface_exists(IUserManager::class)) {
        interface IUserManager { public function get($uid); public function createUser($uid, $password); }
    }
    if (!interface_exists(IGroupManager::class)) {
        interface IGroupManager { public function get($gid); public function createGroup($gid); }
    }
}

namespace OCA\BrTop\Tests {
    use OCA\BrTop\Service\BrDemoUserProvisioningService;
    use OCA\BrTop\Service\BrGroupsService;
    use OCA\BrTop\Service\BrRosterService;
    use OCP\IGroupManager;
    use OCP\IUserManager;

    $userManager = new class implements IUserManager {
        public array $created = [];
        public function get($uid): ?object { return null; }
        public function createUser($uid, $password): object {
            $this->created[] = [$uid, $password];
            return new class {
                public function setDisplayName(string $name): void {}
                public function setEMailAddress(string $email): void {}
            };
        }
    };
    $group = new class {
        public array $added = [];
        public function inGroup(object $user): bool { return false; }
        public function addUser(object $user): void { $this->added[] = $user; }
    };
    $groupManager = new class($group) implements IGroupManager {
        public array $requested = [];
        public function __construct(private object $group) {}
        public function get($gid): object { $this->requested[] = $gid; return $this->group; }
        public function createGroup($gid): object { throw new \RuntimeException('The configured group already exists.'); }
    };
    $roster = new class extends BrRosterService {
        public function demoCouncil(): array {
            return ['members' => [[
                'user_uid' => 'brtop-demo-01',
                'display_name' => 'Demo Person',
                'email' => 'brtop-demo-01@example.org',
                'role' => self::ROLE_REGULAR,
            ]]];
        }
    };
    $groupNames = new class extends BrGroupsService {
        public function __construct() {}
        public function memberGroupName(): string { return 'BR Custom'; }
    };

    $service = new BrDemoUserProvisioningService($userManager, $groupManager, $roster, $groupNames);
    $result = $service->ensureDemoUsers();

    assertSameValue([['brtop-demo-01', 'brtop-demo-01']], $userManager->created, 'Every locally created BRTop demo user must use username=password.');
    assertSameValue(['BR Custom'], $groupManager->requested, 'Demo users must be assigned through the canonical configured BR member group.');
    assertSameValue(['brtop-demo-01'], $result['created'], 'Created demo user IDs must still be reported.');

    echo 'BrDemoUserProvisioningService smoke tests passed' . PHP_EOL;
}
