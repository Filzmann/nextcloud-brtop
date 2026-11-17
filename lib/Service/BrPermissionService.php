<?php

declare(strict_types=1);

namespace OCA\BrTop\Service;

use OCA\BrTop\Exception\AccessDeniedException;

class BrPermissionService {
    public const SESSION_READ = 'brtop.session.read';
    public const SESSION_MANAGE = 'brtop.session.manage';
    public const DOCUMENT_GENERATE = 'brtop.document.generate';
    public const SETTINGS_MANAGE = 'brtop.settings.manage';
    public const LEGISLATURE_MANAGE = 'brtop.legislature.manage';
    public const ABSENCE_CONFIRM = 'brtop.absence.confirm';
    public const ADMIN_ACCESS_MANAGE = 'brtop.admin_access.manage';
    public const BR_DPO_ASSIGN = 'brtop.br_dpo.assign';

    public const DEFAULT_MEMBER_PERMISSIONS = [
        self::SESSION_READ,
        self::SESSION_MANAGE,
        self::DOCUMENT_GENERATE,
        self::SETTINGS_MANAGE,
        self::LEGISLATURE_MANAGE,
        self::ABSENCE_CONFIRM,
    ];

    public function __construct(
        private BrRoleService $roles,
        private BrDpoAssignmentService $dpoAssignments,
        private TemporaryAdminAccessChecker $temporaryAdminAccess,
    ) {}

    public function has(string $uid, string $permission): bool {
        $uid = trim($uid);
        if ($uid === '') {
            return false;
        }
        if ($permission === self::ADMIN_ACCESS_MANAGE) {
            return $this->roles->isChair($uid) || $this->dpoAssignments->isAssigned($uid);
        }
        if ($permission === self::BR_DPO_ASSIGN) {
            return $this->roles->isChair($uid);
        }
        if (!in_array($permission, self::DEFAULT_MEMBER_PERMISSIONS, true)) {
            return false;
        }
        return $this->roles->isMember($uid) || $this->temporaryAdminAccess->hasActiveGrant($uid);
    }

    public function assert(string $uid, string $permission): void {
        if (!$this->has($uid, $permission)) {
            throw new AccessDeniedException('Für diese BRTop-Funktion fehlt die erforderliche Berechtigung.');
        }
    }
}
