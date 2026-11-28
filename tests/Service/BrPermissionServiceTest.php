<?php

declare(strict_types=1);

namespace OCP {
    if (!interface_exists(IGroupManager::class)) {
        interface IGroupManager { public function isAdmin($uid); public function isInGroup($uid, $gid); public function get($gid); public function createGroup($gid); }
    }
    if (!interface_exists(IUserManager::class)) {
        interface IUserManager { public function get($uid); }
    }
    if (!interface_exists(IConfig::class)) {
        interface IConfig { public function getAppValue($app, $key, $default = ''); public function setAppValue($app, $key, $value); }
    }
}

namespace OCA\BrTop\AppInfo {
    if (!class_exists(Application::class, false)) {
        final class Application { public const APP_ID = 'brtop'; }
    }
}

namespace OCA\BrTop\Tests {
    use OCA\BrTop\Exception\AccessDeniedException;
    use OCA\BrTop\Service\BrDpoAssignmentService;
    use OCA\BrTop\Service\BrPermissionService;
    use OCA\BrTop\Service\BrRoleService;
    use OCA\BrTop\Service\TemporaryAdminAccessChecker;
    use OCA\BrTop\Service\BrRoleGateway;
    use OCA\FlzPermissionMatrix\PublicApi\V1\BrRoleSnapshot;
    use OCP\IConfig;
    use OCP\IGroupManager;

    $users = [
        'chair' => ['display' => 'Vorsitz', 'groups' => ['br-members', 'br-chair']],
        'member' => ['display' => 'Mitglied', 'groups' => ['br-members']],
        'dpo' => ['display' => 'BR Datenschutz', 'groups' => ['br-members']],
        'outsider' => ['display' => 'Außenstehend', 'groups' => []],
    ];
    $groups = new class($users) implements IGroupManager {
        public function __construct(public array $users) {}
        public function isAdmin($uid): bool { return $uid === 'admin'; }
        public function isInGroup($uid, $gid): bool { return in_array($gid, $this->users[$uid]['groups'] ?? [], true); }
        public function createGroup($gid): ?object { return $this->get($gid); }
        public function get($gid): ?object {
            $manager = $this;
            $members = [];
            foreach ($this->users as $uid => $data) {
                if (in_array($gid, $data['groups'], true)) {
                    $members[] = new class($uid, $data['display']) {
                        public function __construct(private string $uid, private string $display) {}
                        public function getUID(): string { return $this->uid; }
                        public function getDisplayName(): string { return $this->display; }
                    };
                }
            }
            return new class($members, $manager, $gid) {
                public function __construct(private array $members, private IGroupManager $manager, private string $gid) {}
                public function getUsers(): array { return $this->members; }
                public function addUser($user): void { $uid = $user->getUID(); if (!in_array($this->gid, $this->manager->users[$uid]['groups'] ?? [], true)) { $this->manager->users[$uid]['groups'][] = $this->gid; } }
                public function removeUser($user): void { $uid = $user->getUID(); $this->manager->users[$uid]['groups'] = array_values(array_diff($this->manager->users[$uid]['groups'] ?? [], [$this->gid])); }
            };
        }
    };
    $userManager = new class($users) implements \OCP\IUserManager {
        public function __construct(private array $users) {}
        public function get($uid): ?object {
            if (!isset($this->users[$uid])) return null;
            return new class($uid, $this->users[$uid]['display']) {
                public function __construct(private string $uid, private string $display) {}
                public function getUID(): string { return $this->uid; }
                public function getDisplayName(): string { return $this->display; }
            };
        }
    };
    $snapshotProvider = new class extends BrRoleGateway {
        public ?BrRoleSnapshot $value;
        public function __construct() { $this->value = new BrRoleSnapshot(true, 1, 3, 'br-members', 'br-chair', 'br-deputy'); }
        public function snapshot(): ?BrRoleSnapshot { return $this->value; }
    };
    $roles = new BrRoleService($snapshotProvider, $groups);
    $config = new class implements IConfig {
        public array $values = [];
        public int $writes = 0;
        public function getAppValue($app, $key, $default = ''): string { return $this->values[$key] ?? (string)$default; }
        public function setAppValue($app, $key, $value): void { $this->values[$key] = (string)$value; $this->writes++; }
    };
    $assignments = new BrDpoAssignmentService($config, $roles, $groups, $userManager);
    $adminAccess = new class implements TemporaryAdminAccessChecker {
        public function hasActiveGrant(string $uid): bool { return $uid === 'admin'; }
    };
    $permissions = new BrPermissionService($roles, $assignments, $adminAccess);

    foreach (BrPermissionService::DEFAULT_MEMBER_PERMISSIONS as $permission) {
        assertSameValue(true, $permissions->has('member', $permission), "BR-Mitglied benötigt Standardrecht {$permission}.");
        assertSameValue(false, $permissions->has('outsider', $permission), "Außenstehende dürfen {$permission} nicht erhalten.");
        assertSameValue(true, $permissions->has('admin', $permission), "Aktiv freigegebene Administration benötigt {$permission}.");
    }
    assertSameValue(true, $permissions->has('chair', BrPermissionService::ADMIN_ACCESS_MANAGE), 'Vorsitz muss Adminfreigaben verwalten dürfen.');
    assertSameValue(false, $permissions->has('member', BrPermissionService::ADMIN_ACCESS_MANAGE), 'Gewöhnliche BR-Mitglieder dürfen Adminfreigaben nicht verwalten.');
    assertSameValue(true, $permissions->has('chair', BrPermissionService::BR_DPO_ASSIGN), 'Nur Vorsitz darf die BR-Datenschutzberechtigung zuweisen.');
    assertSameValue(false, $permissions->has('dpo', BrPermissionService::BR_DPO_ASSIGN), 'Zugewiesene BR-Datenschutzrolle darf sich nicht selbst weiterdelegieren.');

    $assignments->replace('chair', ['dpo']);
    assertSameValue(true, $groups->isInGroup('dpo', 'br_dpo'), 'Die BR-Datenschutzberechtigung muss in der nativen Gruppe br_dpo liegen (SSOT), nicht in einer App-UID-Liste.');
    assertSameValue(false, isset($config->values['br_dpo_permission_uids']), 'Die alte UID-Liste darf nicht mehr geschrieben werden.');
    assertSameValue(true, $permissions->has('dpo', BrPermissionService::ADMIN_ACCESS_MANAGE), 'Explizit berechtigtes BR-Mitglied muss Adminfreigaben verwalten dürfen.');
    $before = $config->writes;
    assertThrows(static fn() => $assignments->replace('member', ['member']), AccessDeniedException::class, 'Nicht-Vorsitz darf keine BR-Datenschutzberechtigung vergeben.');
    assertSameValue($before, $config->writes, 'Abgewiesene Vergabe darf AppConfig nicht verändern.');
    assertThrows(static fn() => $assignments->replace('chair', ['outsider']), \InvalidArgumentException::class, 'Nur aktuelle kanonische BR-Mitglieder dürfen ausgewählt werden.');
    assertSameValue($before, $config->writes, 'Manipulierte Ziel-UID darf AppConfig nicht verändern.');

    $groups->users['dpo']['groups'] = [];
    assertSameValue(false, $permissions->has('dpo', BrPermissionService::ADMIN_ACCESS_MANAGE), 'Verlust der BR-Mitgliedschaft muss die Zuweisung sofort deaktivieren.');

    $snapshotProvider->value = new BrRoleSnapshot(false, 1, 0, null, null, null);
    assertSameValue(false, $permissions->has('member', BrPermissionService::SESSION_READ), 'Ungültiger BR-Rollenvertrag muss geschlossen verweigern.');
    assertThrows(static fn() => $assignments->replace('chair', ['member']), AccessDeniedException::class, 'Ungültiger Vertrag darf keine Zuweisung erlauben.');

    echo "BRTop permission service tests passed\n";
}
