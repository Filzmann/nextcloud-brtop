<?php

declare(strict_types=1);

namespace OCA\BrTop\Vendor\Filzmann\Shared\Service;

use OCP\IGroupManager;
use OCP\IL10N;

class GroupProvisioningService {
    public function __construct(private IGroupManager $groupManager,
        private ?IL10N $l10n = null) {
    }

    /** Lokalisierung fachlicher Texte; im Test ohne IL10N identity. */
    private function t(string $text): string
    {
        return $this->l10n?->t($text) ?? $text;
    }

    /**
     * @param array<int, string> $groupNames
     * @return array<int, string>
     */
    public function ensureGroups(array $groupNames): array {
        $created = [];

        foreach ($groupNames as $groupName) {
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

    /**
     * Validates a native Nextcloud group hierarchy without changing it.
     *
     * @param array<int, string> $roleGroupNames
     */
    public function assertRoleMembersBelongTo(string $requiredGroupName, array $roleGroupNames): void {
        $requiredGroup = $this->groupManager->get($requiredGroupName);
        if ($requiredGroup === null) {
            throw new \RuntimeException('Erforderliche Nextcloud-Gruppe ' . $requiredGroupName . ' fehlt.');
        }

        foreach (array_values(array_unique($roleGroupNames)) as $roleGroupName) {
            $roleGroup = $this->groupManager->get($roleGroupName);
            if ($roleGroup === null) {
                throw new \RuntimeException('Erforderliche Nextcloud-Gruppe ' . $roleGroupName . ' fehlt.');
            }
            foreach ($roleGroup->getUsers() as $user) {
                if (!$requiredGroup->inGroup($user)) {
                    throw new \DomainException(
                        $this->t('Alle Mitglieder der Rollengruppe ') . $roleGroupName
                        . $this->t(' müssen zugleich der Gruppe ') . $requiredGroupName . $this->t(' angehören.'),
                    );
                }
            }
        }
    }
}
