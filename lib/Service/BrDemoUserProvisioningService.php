<?php

declare(strict_types=1);

namespace OCA\BrTop\Service;

use OCP\IGroupManager;
use OCP\IUserManager;

class BrDemoUserProvisioningService {
    public function __construct(
        private IUserManager $userManager,
        private IGroupManager $groupManager,
        private BrRosterService $rosterService
    ) {
    }

    public function ensureDemoUsers(): array {
        $group = $this->groupManager->get(BrGroupsService::MEMBER_GROUP)
            ?? $this->groupManager->createGroup(BrGroupsService::MEMBER_GROUP);
        if ($group === null) {
            throw new \RuntimeException('Nextcloud-Gruppe ' . BrGroupsService::MEMBER_GROUP . ' konnte nicht angelegt werden.');
        }

        $created = [];
        $addedToGroup = [];

        foreach ($this->rosterService->demoCouncil()['members'] as $member) {
            $uid = (string)$member['user_uid'];
            $user = $this->userManager->get($uid);
            if ($user === null) {
                $user = $this->userManager->createUser($uid, $this->randomPassword());
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

    private function randomPassword(): string {
        return bin2hex(random_bytes(18));
    }
}
