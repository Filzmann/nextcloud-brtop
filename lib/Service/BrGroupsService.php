<?php

declare(strict_types=1);

namespace OCA\BrTop\Service;

use OCA\BrTop\Vendor\Filzmann\Shared\Service\GroupProvisioningService;

class BrGroupsService {
    private const DEFAULT_GROUPS = [
        'member' => 'Betriebsrat',
        'chair' => 'Betriebsrat-Vorsitzende',
        'deputy' => 'Betriebsrat-Stellvertreter',
    ];

    public function __construct(
        private GroupProvisioningService $groups,
        private BrRoleGateway $roles,
        private BrtopSettingsService $legacySettings,
    ) {
    }

    /** @return array<string,string> */
    public function requiredGroups(): array {
        $snapshot = $this->roles->snapshot();
        if ($snapshot !== null) {
            return [
                'member' => (string)$snapshot->memberGroupId(),
                'chair' => (string)$snapshot->chairGroupId(),
                'deputy' => (string)$snapshot->deputyGroupId(),
            ];
        }

        return ['member' => $this->legacySettings->legacyMemberGroupName()] + self::DEFAULT_GROUPS;
    }

    /** @return list<string> */
    public function ensureRequiredGroups(): array {
        return $this->groups->ensureGroups(array_values($this->requiredGroups()));
    }

    public function memberGroupName(): string {
        return $this->requiredGroups()['member'];
    }

    public function chairGroupName(): string {
        return $this->requiredGroups()['chair'];
    }

    public function deputyGroupName(): string {
        return $this->requiredGroups()['deputy'];
    }
}
