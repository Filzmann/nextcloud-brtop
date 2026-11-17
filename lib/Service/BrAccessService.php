<?php

declare(strict_types=1);

namespace OCA\BrTop\Service;

use OCA\BrTop\Exception\AccessDeniedException;
class BrAccessService {
    public function __construct(private BrPermissionService $permissions) {}

    public function isAdmin(string $uid): bool {
        return $this->permissions->has($uid, BrPermissionService::SETTINGS_MANAGE);
    }

    public function canUse(string $uid): bool {
        return $this->permissions->has($uid, BrPermissionService::SESSION_READ);
    }

    public function assertCanUse(string $uid): void {
        if (!$this->canUse($uid)) {
            throw new AccessDeniedException('BRTop ist nur fuer Mitglieder des Betriebsrats freigegeben.');
        }
    }

    public function assertAdmin(string $uid): void {
        $this->permissions->assert($uid, BrPermissionService::SETTINGS_MANAGE);
    }

    public function can(string $uid, string $permission): bool {
        return $this->permissions->has($uid, $permission);
    }

    public function assertPermission(string $uid, string $permission): void {
        $this->permissions->assert($uid, $permission);
    }
}
