<?php

declare(strict_types=1);

namespace OCA\BrTop\Repository;

interface DocumentTemplateRepositoryInterface {
    public function createDraft(string $templateKey, string $format, string $content): array;
    public function approve(string $templateKey, int $version): array;
    public function find(string $templateKey, int $version): ?array;
    public function approvedDescending(string $templateKey): array;
    public function metadata(): array;
}
