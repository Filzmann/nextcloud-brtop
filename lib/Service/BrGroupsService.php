<?php

declare(strict_types=1);

namespace OCA\BrTop\Service;

use OCA\LocalBase\Service\GroupProvisioningService;

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
        private GroupProvisioningService $groups
    ) {
    }

    public function requiredGroups(): array {
        return self::REQUIRED_GROUPS;
    }

    public function ensureRequiredGroups(): array {
        return $this->groups->ensureGroups(self::REQUIRED_GROUPS);
    }
}
