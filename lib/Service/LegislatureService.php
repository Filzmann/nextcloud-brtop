<?php

declare(strict_types=1);

namespace OCA\BrTop\Service;

use OCA\BrTop\Model\LegislatureConfiguration;
use OCA\BrTop\Repository\LegislatureRepository;

class LegislatureService {
    public function __construct(
        private LegislatureRepository $repository,
        private LegislatureValidationService $validation
    ) {
    }

    public function latest(): ?array {
        return $this->repository->latest();
    }

    public function configuration(int $legislatureId): array {
        return $this->repository->configuration($legislatureId)
            ?? throw new \RuntimeException('Legislatur nicht gefunden.');
    }

    public function activeForDate(string $date): array {
        $configuration = $this->repository->activeForDate($date);
        if ($configuration === null) {
            throw new \RuntimeException('Fuer das Sitzungsdatum ist keine aktive Legislatur konfiguriert.');
        }
        return $configuration;
    }

    public function saveDraft(array $payload, string $uid): array {
        $configuration = LegislatureConfiguration::get($payload)->toArray();
        if (($configuration['status'] ?? 'draft') !== 'draft') {
            throw new \RuntimeException('Eine aktivierte Legislatur ist versiegelt und kann nicht als Entwurf gespeichert werden.');
        }
        $errors = $this->validation->validate($configuration);
        if ($errors !== []) {
            throw new \InvalidArgumentException(implode(' ', $errors));
        }

        $id = $this->repository->saveDraft($configuration, $uid);
        return $this->repository->configuration($id) ?? throw new \RuntimeException('Legislatur konnte nicht geladen werden.');
    }

    public function activate(int $legislatureId): array {
        $configuration = $this->repository->configuration($legislatureId);
        if ($configuration === null) {
            throw new \RuntimeException('Legislatur nicht gefunden.');
        }
        if (($configuration['status'] ?? '') !== 'draft') {
            throw new \RuntimeException('Nur ein Entwurf kann aktiviert und versiegelt werden.');
        }
        $errors = $this->validation->validate($configuration, true);
        if ($errors !== []) {
            throw new \InvalidArgumentException(implode(' ', $errors));
        }
        if ($this->repository->hasOverlappingActive(
            (string)$configuration['starts_on'],
            (string)$configuration['ends_on'],
            $legislatureId
        )) {
            throw new \RuntimeException('Der Zeitraum ueberschneidet sich mit einer aktiven Legislatur.');
        }

        $this->repository->activate($legislatureId);
        return $this->repository->configuration($legislatureId) ?? throw new \RuntimeException('Legislatur konnte nicht geladen werden.');
    }
}
