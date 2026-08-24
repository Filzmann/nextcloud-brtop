<?php

declare(strict_types=1);

namespace {
    if (!interface_exists(\OCP\IGroupManager::class)) {
        eval('namespace OCP; interface IGroupManager { public function groupExists($gid); public function createGroup($gid); public function get($gid); }');
    }
    if (!interface_exists(\OCP\IAppConfig::class)) {
        eval('namespace OCP; interface IAppConfig { public function getValueString(string $appId, string $key, string $default = ""): string; public function setValueString(string $appId, string $key, string $value): void; }');
    }
    if (!interface_exists(\OCP\IConfig::class)) {
        eval('namespace OCP; interface IConfig { public function getAppValue($appName, $key, $default = ""); }');
    }
}

namespace OCA\BrTop\AppInfo {
    final class Application { public const APP_ID = 'brtop'; }
}

namespace OCA\BrTop\Tests {
    use OCA\BrTop\Service\BrGroupsService;
    use OCA\BrTop\Service\BrtopSettingsService;
    use OCA\LocalBase\Organization\BrGroupDefinition;
    use OCA\LocalBase\Organization\BrGroupSettingsService;
    use OCA\LocalBase\Service\GroupProvisioningService;
    use OCP\IAppConfig;
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
    $newAppConfig = static function (string $initial = ''): IAppConfig {
        return new class($initial) implements IAppConfig {
            public array $values = [];
            public array $writes = [];
            public function __construct(string $initial) { if ($initial !== '') $this->values['br_group_definition'] = $initial; }
            public function getValueString(string $appId, string $key, string $default = ''): string { return $this->values[$key] ?? $default; }
            public function setValueString(string $appId, string $key, string $value): void {
                $this->values[$key] = $value;
                $this->writes[] = [$appId, $key, $value];
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

    $groupManager = $newGroupManager(['BR Custom' => ['chair'], 'Betriebsrat-Vorsitzende' => ['chair']]);
    $appConfig = $newAppConfig();
    $provisioning = new GroupProvisioningService($groupManager);
    $contract = new BrGroupSettingsService($appConfig, $provisioning);
    $service = new BrGroupsService($provisioning, $contract, $legacySettings('BR Custom'));
    assertSameValue(['Betriebsrat-Stellvertreter'], $service->ensureRequiredGroups(), 'The migration should create only missing role groups around the legacy member group.');
    assertSameValue('BR Custom', $contract->validatedDefinition()->groupId(BrGroupDefinition::MEMBER), 'The legacy BRTop member group must be imported into the shared contract.');
    assertSameValue([], $service->ensureRequiredGroups(), 'A persisted valid BR group contract must be idempotent.');

    $invalidGroups = $newGroupManager(['BR Custom' => ['member'], 'Betriebsrat-Vorsitzende' => ['outsider'], 'Betriebsrat-Stellvertreter' => []]);
    $invalidConfig = $newAppConfig();
    $invalidProvisioning = new GroupProvisioningService($invalidGroups);
    $invalidService = new BrGroupsService($invalidProvisioning, new BrGroupSettingsService($invalidConfig, $invalidProvisioning), $legacySettings('BR Custom'));
    assertThrows(static fn() => $invalidService->ensureRequiredGroups(), \DomainException::class, 'Chair and deputy group members must also belong to the configured BR member group.');
    assertSameValue([], $invalidConfig->writes, 'A contradictory native group hierarchy must not persist a shared contract.');

    $customDefinition = json_encode(['version' => 1, 'revision' => 4, 'groups' => ['member' => 'BR Mitglieder', 'chair' => 'BR Vorsitz', 'deputy' => 'BR Vize']], JSON_THROW_ON_ERROR);
    $customGroups = $newGroupManager(['BR Mitglieder' => ['a'], 'BR Vorsitz' => ['a'], 'BR Vize' => []]);
    $customConfig = $newAppConfig($customDefinition);
    $customProvisioning = new GroupProvisioningService($customGroups);
    $customService = new BrGroupsService($customProvisioning, new BrGroupSettingsService($customConfig, $customProvisioning), $legacySettings('Ignored legacy group'));
    assertSameValue(['BR Mitglieder', 'BR Vorsitz', 'BR Vize'], $customService->requiredGroups(), 'Runtime group names must come from the persisted LocalBase contract.');
    assertSameValue('BR Mitglieder', $customService->memberGroupName(), 'Access checks must receive the configured BR member group.');
    assertSameValue([], $customService->ensureRequiredGroups(), 'A valid persisted contract must not create or rename native groups.');
    assertSameValue([], $customConfig->writes, 'A valid persisted contract must remain the single source without consumer rewrites.');

    echo 'BrGroupsService smoke tests passed' . PHP_EOL;
}
