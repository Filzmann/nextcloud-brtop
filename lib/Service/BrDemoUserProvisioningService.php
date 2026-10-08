<?php

declare(strict_types=1);

namespace OCA\BrTop\Service;

use OCP\IGroupManager;
use OCP\IUserManager;

class BrDemoUserProvisioningService {
    public function __construct(
        private IUserManager $userManager,
        private IGroupManager $groupManager,
        private BrRosterService $rosterService,
        private BrGroupsService $groups,
    ) {
    }

    public function ensureDemoUsers(): array {
        $memberGroup = $this->groups->memberGroupName();
        $group = $this->groupManager->get($memberGroup)
            ?? $this->groupManager->createGroup($memberGroup);
        if ($group === null) {
            throw new \RuntimeException('Nextcloud-Gruppe ' . $memberGroup . ' konnte nicht angelegt werden.');
        }

        $created = [];
        $addedToGroup = [];

        foreach ($this->rosterService->demoCouncil()['members'] as $member) {
            $uid = (string)$member['user_uid'];
            $user = $this->userManager->get($uid);
            if ($user === null) {
                $user = $this->userManager->createUser($uid, $uid);
                if ($user === false || $user === null) {
                    throw new \RuntimeException('Demo-User ' . $uid . ' konnte nicht angelegt werden.');
                }
                $created[] = $uid;
            }

            if (method_exists($user, 'setDisplayName')) {
                $user->setDisplayName((string)$member['display_name']);
            }
            if (method_exists($user, 'setEMailAddress')) {
                $user->setEMailAddress((string)$member['email']);
            }

            if (($member['role'] ?? '') === BrRosterService::ROLE_REGULAR && !$group->inGroup($user)) {
                $group->addUser($user);
                $addedToGroup[] = $uid;
            }
        }

        return [
            'created' => $created,
            'addedToGroup' => $addedToGroup,
        ];
    }
}
