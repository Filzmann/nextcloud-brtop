<?php

declare(strict_types=1);

namespace OCP {
    if (!interface_exists(IGroupManager::class)) {
        interface IGroupManager { public function groupExists($gid); public function createGroup($gid); public function get($gid); }
    }
    if (!interface_exists(IConfig::class)) {
        interface IConfig { public function getAppValue($appName, $key, $default = ""); }
    }
}

namespace OCA\BrTop\AppInfo {
    final class Application { public const APP_ID = 'brtop'; }
}

namespace OCA\BrTop\Tests {
    use OCA\BrTop\Service\BrGroupsService;
    use OCA\BrTop\Service\BrRoleGateway;
    use OCA\BrTop\Service\BrtopSettingsService;
    use OCA\BrTop\Vendor\Filzmann\Shared\Service\GroupProvisioningService;
    use OCA\FlzPermissionMatrix\PublicApi\V1\BrRoleSnapshot;
    use OCP\IConfig;
    use OCP\IGroupManager;

    $newGroupManager = static function (array $membersByGroup): IGroupManager {
        return new class($membersByGroup) implements IGroupManager {
            public array $groups = [];
            public function __construct(array $membersByGroup) {
                foreach ($membersByGroup as $groupId => $uids) $this->groups[$groupId] = $this->group($uids);
            }
            public function groupExists($gid): bool { return isset($this->groups[$gid]); }
            public function get($gid): ?object { return $this->groups[$gid] ?? null; }
            public function createGroup($gid): object { return $this->groups[$gid] = $this->group([]); }
            private function group(array $uids): object {
                $users = array_map(static fn(string $uid): object => new class($uid) {
                    public function __construct(public string $uid) {}
                }, $uids);
                return new class($users) {
                    public function __construct(private array $users) {}
                    public function getUsers(): array { return $this->users; }
                    public function inGroup(object $user): bool {
                        foreach ($this->users as $candidate) if ($candidate->uid === $user->uid) return true;
                        return false;
                    }
                };
            }
        };
    };
    $legacySettings = static function (string $memberGroup): BrtopSettingsService {
        $config = new class($memberGroup) implements IConfig {
            public function __construct(private string $memberGroup) {}
            public function getAppValue($appName, $key, $default = ''): string {
                return $key === 'member_group_name' ? $this->memberGroup : (string)$default;
            }
        };
        return new BrtopSettingsService($config);
    };
    $gateway = static function (?BrRoleSnapshot $snapshot): BrRoleGateway {
        return new class($snapshot) extends BrRoleGateway {
            public function __construct(private ?BrRoleSnapshot $snapshotValue) {}
            public function snapshot(): ?BrRoleSnapshot { return $this->snapshotValue; }
        };
    };

    // Kanonischer Vertrag aus flz_permission_matrix: Gruppennamen kommen aus dem V1-Snapshot.
    $groupManager = $newGroupManager(['BR Mitglieder' => ['a'], 'BR Vorsitz' => ['a']]);
    $provisioning = new GroupProvisioningService($groupManager);
    $service = new BrGroupsService(
        $provisioning,
        $gateway(new BrRoleSnapshot(true, 5, 5, 'BR Mitglieder', 'BR Vorsitz', 'BR Vize')),
        $legacySettings('BR Custom'),
    );
    assertSameValue(['BR Mitglieder', 'BR Vorsitz', 'BR Vize'], array_values($service->requiredGroups()), 'Runtime group names must come from the canonical BR role V1 snapshot.');
    assertSameValue(['BR Vize'], $service->ensureRequiredGroups(), 'Only missing role groups must be created.');
    assertSameValue([], $service->ensureRequiredGroups(), 'Ensuring groups must be idempotent.');
    assertSameValue('BR Mitglieder', $service->memberGroupName(), 'Access checks must receive the configured BR member group.');
    assertSameValue('BR Vorsitz', $service->chairGroupName(), 'Access checks must receive the configured BR chair group.');
    assertSameValue('BR Vize', $service->deputyGroupName(), 'Access checks must receive the configured BR deputy group.');

    // Fallback ohne Provider: Legacy-Mitgliedergruppe plus Standard-Rollengruppen.
    $fallbackGroups = $newGroupManager(['BR Custom' => ['chair'], 'Betriebsrat-Vorsitzende' => ['chair']]);
    $fallbackService = new BrGroupsService(new GroupProvisioningService($fallbackGroups), $gateway(null), $legacySettings('BR Custom'));
    assertSameValue(['BR Custom', 'Betriebsrat-Vorsitzende', 'Betriebsrat-Stellvertreter'], array_values($fallbackService->requiredGroups()), 'Without a provider the legacy member group and default role groups must be used.');
    assertSameValue(['Betriebsrat-Stellvertreter'], $fallbackService->ensureRequiredGroups(), 'The fallback should create only missing role groups around the legacy member group.');

    echo 'BrGroupsService smoke tests passed' . PHP_EOL;
}
