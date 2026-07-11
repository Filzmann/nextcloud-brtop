<?php

declare(strict_types=1);

namespace OCA\BrTop\Service;

use OCA\BrTop\Exception\AccessDeniedException;
use OCP\IGroupManager;

class BrAccessService {
    public function __construct(private IGroupManager $groupManager) {
    }

    public function isAdmin(string $uid): bool {
        return $uid !== '' && $this->groupManager->isAdmin($uid);
    }

    public function canUse(string $uid): bool {
        return $this->isAdmin($uid)
            || ($uid !== '' && $this->groupManager->isInGroup($uid, BrGroupsService::MEMBER_GROUP));
    }

    public function assertCanUse(string $uid): void {
        if (!$this->canUse($uid)) {
            throw new AccessDeniedException('BRTop ist nur fuer Mitglieder des Betriebsrats freigegeben.');
        }
    }

    public function assertAdmin(string $uid): void {
        if (!$this->isAdmin($uid)) {
            throw new AccessDeniedException('Diese BRTop-Funktion ist nur fuer Nextcloud-Admins freigegeben.');
        }
    }
}
