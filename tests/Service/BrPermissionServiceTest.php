<?php

declare(strict_types=1);

namespace OCP {
    if (!interface_exists(IGroupManager::class)) {
        interface IGroupManager { public function isAdmin($uid); public function isInGroup($uid, $gid); public function get($gid); }
    }
    if (!interface_exists(IConfig::class)) {
        interface IConfig { public function getAppValue($app, $key, $default = ''); public function setAppValue($app, $key, $value); }
    }
}

namespace OCA\LocalBase\PublicApi\V1 {
    if (!class_exists(BrMembershipSnapshot::class, false)) {
        final class BrMembershipSnapshot {
            public const CONTRACT_VERSION = '1.0';
            public function __construct(
                private bool $valid,
                private string $version = '1.0',
                private ?string $member = 'br-members',
                private ?string $chair = 'br-chair',
                private ?string $deputy = 'br-deputy',
            ) {}
            public function isValid(): bool { return $this->valid; }
            public function contractVersion(): string { return $this->version; }
            public function definitionVersion(): int { return 1; }
            public function definitionRevision(): int { return 3; }
            public function memberGroupId(): ?string { return $this->member; }
            public function chairGroupId(): ?string { return $this->chair; }
            public function deputyGroupId(): ?string { return $this->deputy; }
        }
    }
    if (!class_exists(BrMembershipSnapshotService::class, false)) {
        class BrMembershipSnapshotService {
            public function __construct(public BrMembershipSnapshot $value) {}
            public function snapshot(): BrMembershipSnapshot { return $this->value; }
        }
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
    use OCA\LocalBase\PublicApi\V1\BrMembershipSnapshot;
    use OCA\LocalBase\PublicApi\V1\BrMembershipSnapshotService;
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
        public function get($gid): ?object {
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
            return new class($members) {
                public function __construct(private array $members) {}
                public function getUsers(): array { return $this->members; }
            };
        }
    };
    $snapshotProvider = new BrMembershipSnapshotService(new BrMembershipSnapshot(true));
    $roles = new BrRoleService($snapshotProvider, $groups);
    $config = new class implements IConfig {
        public array $values = [];
        public int $writes = 0;
        public function getAppValue($app, $key, $default = ''): string { return $this->values[$key] ?? (string)$default; }
        public function setAppValue($app, $key, $value): void { $this->values[$key] = (string)$value; $this->writes++; }
    };
    $assignments = new BrDpoAssignmentService($config, $roles);
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
    assertSameValue(true, $permissions->has('dpo', BrPermissionService::ADMIN_ACCESS_MANAGE), 'Explizit berechtigtes BR-Mitglied muss Adminfreigaben verwalten dürfen.');
    $before = $config->writes;
    assertThrows(static fn() => $assignments->replace('member', ['member']), AccessDeniedException::class, 'Nicht-Vorsitz darf keine BR-Datenschutzberechtigung vergeben.');
    assertSameValue($before, $config->writes, 'Abgewiesene Vergabe darf AppConfig nicht verändern.');
    assertThrows(static fn() => $assignments->replace('chair', ['outsider']), \InvalidArgumentException::class, 'Nur aktuelle kanonische BR-Mitglieder dürfen ausgewählt werden.');
    assertSameValue($before, $config->writes, 'Manipulierte Ziel-UID darf AppConfig nicht verändern.');

    $groups->users['dpo']['groups'] = [];
    assertSameValue(false, $permissions->has('dpo', BrPermissionService::ADMIN_ACCESS_MANAGE), 'Verlust der BR-Mitgliedschaft muss die Zuweisung sofort deaktivieren.');

    $snapshotProvider->value = new BrMembershipSnapshot(false);
    assertSameValue(false, $permissions->has('member', BrPermissionService::SESSION_READ), 'Ungültiger LocalBase-Vertrag muss geschlossen verweigern.');
    assertThrows(static fn() => $assignments->replace('chair', ['member']), AccessDeniedException::class, 'Ungültiger Vertrag darf keine Zuweisung erlauben.');

    echo "BRTop permission service tests passed\n";
}
