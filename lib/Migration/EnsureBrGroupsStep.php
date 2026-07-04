<?php

declare(strict_types=1);

namespace OCA\BrTop\Migration;

use OCA\BrTop\Service\BrGroupsService;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class EnsureBrGroupsStep implements IRepairStep {
    public function __construct(
        private BrGroupsService $groups
    ) {
    }

    public function getName(): string {
        return 'BR-Gruppen anlegen';
    }

    public function run(IOutput $output): void {
        $createdGroups = $this->groups->ensureRequiredGroups();

        if ($createdGroups === []) {
            $output->info('BR-Gruppen sind bereits vorhanden.');
            return;
        }

        foreach ($createdGroups as $groupName) {
            $output->info('BR-Gruppe angelegt: ' . $groupName);
        }
    }
}
