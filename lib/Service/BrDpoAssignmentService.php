<?php

declare(strict_types=1);

namespace OCA\BrTop\Service;

use InvalidArgumentException;
use OCA\BrTop\AppInfo\Application;
use OCA\BrTop\Exception\AccessDeniedException;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUserManager;

/**
 * BR-Datenschutzberechtigung als native Nextcloud-Gruppe (Single Source of Truth für
 * „wer dazugehört“: die Gruppenmitgliedschaft liegt in Core, nicht in einer app-eigenen UID-Liste).
 * Die App speichert nur die Gruppen-ID (AppConfig br_dpo_group, Default br_dpo).
 */
final class BrDpoAssignmentService {
    private const GROUP_KEY = 'br_dpo_group';
    private const DEFAULT_GROUP = 'br_dpo';
    private const MAX_ASSIGNMENTS = 100;

    public function __construct(
        private IConfig $config,
        private BrRoleService $roles,
        private IGroupManager $groups,
        private IUserManager $users,
    ) {}

    public function isAssigned(string $uid): bool {
        $uid = trim($uid);
        return $uid !== '' && $this->roles->isMember($uid) && $this->groups->isInGroup($uid, $this->dpoGroupId());
    }

    /** @return list<string> */
    public function assignedUids(): array {
        $group = $this->groups->get($this->dpoGroupId());
        if ($group === null || !method_exists($group, 'getUsers')) {
            return [];
        }
        $uids = [];
        foreach ($group->getUsers() as $user) {
            if (!is_object($user) || !method_exists($user, 'getUID')) {
                continue;
            }
            $uid = trim((string)$user->getUID());
            if ($uid !== '' && $this->roles->isMember($uid)) {
                $uids[$uid] = true;
            }
        }
        $uids = array_keys($uids);
        sort($uids, SORT_STRING);
        return $uids;
    }

    /** @return array{members:list<array{uid:string,displayName:string,assigned:bool}>} */
    public function state(string $actorUid): array {
        $this->assertChair($actorUid);
        $gid = $this->dpoGroupId();
        return ['members' => array_map(
            static fn(array $member): array => $member + ['assigned' => $member['uid'] !== '' && $this->groups->isInGroup($member['uid'], $gid)],
            $this->roles->members(),
        )];
    }

    /** @param list<mixed> $uids @return list<string> */
    public function replace(string $actorUid, array $uids): array {
        $this->assertChair($actorUid);
        if (count($uids) > self::MAX_ASSIGNMENTS) {
            throw new InvalidArgumentException('Zu viele BR-Datenschutzberechtigungen ausgewählt.');
        }
        $normalized = [];
        foreach ($uids as $uid) {
            if (!is_string($uid)) {
                throw new InvalidArgumentException('Ungültige Benutzerkennung.');
            }
            $uid = trim($uid);
            if ($uid === '' || strlen($uid) > 64 || preg_match('/[\x00-\x1F\x7F]/u', $uid) === 1 || !$this->roles->isMember($uid)) {
                throw new InvalidArgumentException('Ausgewählte Person ist kein aktuelles Betriebsratsmitglied.');
            }
            $normalized[$uid] = true;
        }
        $uids = array_keys($normalized);
        sort($uids, SORT_STRING);

        // Mitgliedschaft in der nativen Gruppe setzen/entziehen; erst nach erfolgreicher Validierung.
        $group = $this->groups->get($this->dpoGroupId());
        if ($group === null) {
            $group = $this->groups->createGroup($this->dpoGroupId());
        }
        if ($group === null || !method_exists($group, 'addUser') || !method_exists($group, 'removeUser')) {
            throw new \RuntimeException('Die BR-Datenschutzgruppe kann nicht verwaltet werden.');
        }
        foreach ($this->roles->members() as $member) {
            $user = $this->users->get($member['uid']);
            if ($user === null) {
                continue;
            }
            if (isset($normalized[$member['uid']])) {
                $group->addUser($user);
            } else {
                $group->removeUser($user);
            }
        }
        return $uids;
    }

    public function isAssignedToSubject(string $uid): bool {
        $uid = trim($uid);
        return $uid !== '' && $this->groups->isInGroup($uid, $this->dpoGroupId());
    }

    private function dpoGroupId(): string {
        $gid = trim($this->config->getAppValue(Application::APP_ID, self::GROUP_KEY, self::DEFAULT_GROUP));
        return $gid !== '' ? $gid : self::DEFAULT_GROUP;
    }

    private function assertChair(string $uid): void {
        if (!$this->roles->isChair($uid)) {
            throw new AccessDeniedException('Nur der aktuelle BR-Vorsitz darf die BR-Datenschutzberechtigung verwalten.');
        }
    }
}
