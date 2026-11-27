<?php

declare(strict_types=1);

namespace OCA\BrTop\Service;

class BrAdminAccessManagerPolicy {
    public function __construct(private BrRoleService $roles, private BrDpoAssignmentService $assignments) {}

    public function canManage(string $uid): bool {
        return $this->roles->isChair($uid) || $this->assignments->isAssigned($uid);
    }
}
