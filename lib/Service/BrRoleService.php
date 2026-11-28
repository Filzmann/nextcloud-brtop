<?php

declare(strict_types=1);

namespace OCA\BrTop\Service;

use OCA\FlzPermissionMatrix\PublicApi\V1\BrRoleSnapshot;
use OCP\IGroupManager;
use Throwable;

/** Fail-closed consumer of the public flz_permission_matrix BR role V1 contract. */
final class BrRoleService {
    public function __construct(
        private BrRoleGateway $roles,
        private IGroupManager $groups,
    ) {}

    public function isMember(string $uid): bool {
        return $this->isInRole($uid, 'member');
    }

    public function isChair(string $uid): bool {
        return $this->isInRole($uid, 'chair') && $this->isMember($uid);
    }

    public function isDeputy(string $uid): bool {
        return $this->isInRole($uid, 'deputy') && $this->isMember($uid);
    }

    /** @return list<array{uid:string,displayName:string}> */
    public function members(): array {
        $snapshot = $this->validSnapshot();
        if ($snapshot === null) {
            return [];
        }
        try {
            $group = $this->groups->get((string)$snapshot->memberGroupId());
            if ($group === null || !method_exists($group, 'getUsers')) {
                return [];
            }
            $members = [];
            foreach ($group->getUsers() as $user) {
                if (!is_object($user) || !method_exists($user, 'getUID')) {
                    return [];
                }
                $uid = trim((string)$user->getUID());
                if (!$this->validUid($uid)) {
                    return [];
                }
                $displayName = method_exists($user, 'getDisplayName') ? trim((string)$user->getDisplayName()) : '';
                $members[] = ['uid' => $uid, 'displayName' => $displayName !== '' ? $displayName : $uid];
            }
            usort($members, static fn(array $left, array $right): int => strnatcasecmp($left['displayName'], $right['displayName']));
            return $members;
        } catch (Throwable) {
            return [];
        }
    }

    public function memberGroupId(): ?string {
        return $this->validSnapshot()?->memberGroupId();
    }

    public function chairGroupId(): ?string {
        return $this->validSnapshot()?->chairGroupId();
    }

    public function deputyGroupId(): ?string {
        return $this->validSnapshot()?->deputyGroupId();
    }

    private function isInRole(string $uid, string $role): bool {
        $uid = trim($uid);
        if (!$this->validUid($uid)) {
            return false;
        }
        $snapshot = $this->validSnapshot();
        if ($snapshot === null) {
            return false;
        }
        $groupId = match ($role) {
            'member' => $snapshot->memberGroupId(),
            'chair' => $snapshot->chairGroupId(),
            'deputy' => $snapshot->deputyGroupId(),
            default => null,
        };
        if (!is_string($groupId) || trim($groupId) === '') {
            return false;
        }
        try {
            return $this->groups->isInGroup($uid, $groupId);
        } catch (Throwable) {
            return false;
        }
    }

    private function validSnapshot(): ?BrRoleSnapshot {
        try {
            $snapshot = $this->roles->snapshot();
            if ($snapshot->contractVersion() !== BrRoleSnapshot::CONTRACT_VERSION
                || !$snapshot->isValid()
                || $snapshot->definitionVersion() < 1
                || $snapshot->definitionRevision() < 1
                || !$this->validGroupId($snapshot->memberGroupId())
                || !$this->validGroupId($snapshot->chairGroupId())
                || !$this->validGroupId($snapshot->deputyGroupId())) {
                return null;
            }
            return $snapshot;
        } catch (Throwable) {
            return null;
        }
    }

    private function validGroupId(?string $groupId): bool {
        return is_string($groupId) && trim($groupId) !== '' && strlen($groupId) <= 64 && preg_match('/[\x00-\x1F\x7F]/u', $groupId) !== 1;
    }

    private function validUid(string $uid): bool {
        return $uid !== '' && strlen($uid) <= 64 && preg_match('/[\x00-\x1F\x7F]/u', $uid) !== 1;
    }
}
