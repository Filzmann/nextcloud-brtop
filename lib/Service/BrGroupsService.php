<?php

declare(strict_types=1);

namespace OCA\BrTop\Service;

use OCP\IGroupManager;

class BrGroupsService {
    public const MEMBER_GROUP = 'Betriebsrat';
    public const CHAIR_GROUP = 'Betriebsrat-Vorsitzende';
    public const DEPUTY_GROUP = 'Betriebsrat-Stellvertreter';

    private const REQUIRED_GROUPS = [
        self::MEMBER_GROUP,
        self::CHAIR_GROUP,
        self::DEPUTY_GROUP,
    ];

    public function __construct(
        private IGroupManager $groupManager
    ) {
    }

    public function requiredGroups(): array {
        return self::REQUIRED_GROUPS;
    }

    public function ensureRequiredGroups(): array {
        $created = [];

        foreach (self::REQUIRED_GROUPS as $groupName) {
            if ($this->groupManager->groupExists($groupName)) {
                continue;
            }

            $group = $this->groupManager->createGroup($groupName);
            if ($group === null && !$this->groupManager->groupExists($groupName)) {
                throw new \RuntimeException('Nextcloud-Gruppe ' . $groupName . ' konnte nicht angelegt werden.');
            }

            if ($group !== null) {
                $created[] = $groupName;
            }
        }

        return $created;
    }
}
