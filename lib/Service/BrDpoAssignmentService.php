<?php

declare(strict_types=1);

namespace OCA\BrTop\Service;

use InvalidArgumentException;
use JsonException;
use OCA\BrTop\AppInfo\Application;
use OCA\BrTop\Exception\AccessDeniedException;
use OCP\IConfig;

/** App-local user assignment for the dedicated BR data-protection capability. */
final class BrDpoAssignmentService {
    private const CONFIG_KEY = 'br_dpo_permission_uids';
    private const MAX_ASSIGNMENTS = 100;

    public function __construct(private IConfig $config, private BrRoleService $roles) {}

    public function isAssigned(string $uid): bool {
        $uid = trim($uid);
        return $uid !== '' && $this->roles->isMember($uid) && in_array($uid, $this->storedUids(), true);
    }

    /** @return list<string> */
    public function assignedUids(): array {
        return array_values(array_filter($this->storedUids(), fn(string $uid): bool => $this->roles->isMember($uid)));
    }

    /** @return array{members:list<array{uid:string,displayName:string,assigned:bool}>} */
    public function state(string $actorUid): array {
        $this->assertChair($actorUid);
        $assigned = array_fill_keys($this->assignedUids(), true);
        return ['members' => array_map(static fn(array $member): array => $member + ['assigned' => isset($assigned[$member['uid']])], $this->roles->members())];
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
        $this->config->setAppValue(Application::APP_ID, self::CONFIG_KEY, json_encode($uids, JSON_THROW_ON_ERROR));
        return $uids;
    }

    public function isStoredForSubject(string $uid): bool {
        return in_array(trim($uid), $this->storedUids(), true);
    }

    /** @return list<string> */
    private function storedUids(): array {
        $raw = trim((string)$this->config->getAppValue(Application::APP_ID, self::CONFIG_KEY, '[]'));
        try {
            $decoded = json_decode($raw, true, 16, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }
        if (!is_array($decoded) || count($decoded) > self::MAX_ASSIGNMENTS) {
            return [];
        }
        $uids = [];
        foreach ($decoded as $uid) {
            if (!is_string($uid)) {
                return [];
            }
            $uid = trim($uid);
            if ($uid === '' || strlen($uid) > 64 || preg_match('/[\x00-\x1F\x7F]/u', $uid) === 1) {
                return [];
            }
            $uids[$uid] = true;
        }
        return array_keys($uids);
    }

    private function assertChair(string $uid): void {
        if (!$this->roles->isChair($uid)) {
            throw new AccessDeniedException('Nur der aktuelle BR-Vorsitz darf die BR-Datenschutzberechtigung verwalten.');
        }
    }
}
