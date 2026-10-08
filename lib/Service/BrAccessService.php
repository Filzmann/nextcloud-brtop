<?php

declare(strict_types=1);

namespace OCA\BrTop\Service;

use OCA\BrTop\Exception\AccessDeniedException;
use OCP\IGroupManager;

class BrAccessService {
    public function __construct(
        private IGroupManager $groupManager,
        private BrGroupsService $groups,
        private ?TemporaryAdminAccessChecker $temporaryAdminAccess = null,
    ) {
    }

    public function isAdmin(string $uid): bool {
        return $uid !== '' && $this->groupManager->isAdmin($uid) && ($this->temporaryAdminAccess?->hasActiveGrant($uid) ?? false);
    }

    public function canUse(string $uid): bool {
        return $this->isAdmin($uid)
            || ($uid !== '' && $this->groupManager->isInGroup($uid, $this->groups->memberGroupName()));
    }

    public function assertCanUse(string $uid): void {
        if (!$this->canUse($uid)) {
            throw new AccessDeniedException('BRTop ist nur fuer Mitglieder des Betriebsrats freigegeben.');
        }
    }

    public function assertAdmin(string $uid): void {
        if (!$this->isAdmin($uid)) {
            throw new AccessDeniedException('Diese BRTop-Funktion benötigt eine aktive app-lokale Adminfreigabe.');
        }
    }
}
