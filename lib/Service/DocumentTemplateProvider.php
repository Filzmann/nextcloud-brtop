<?php

declare(strict_types=1);

namespace OCA\BrTop\Service;

interface DocumentTemplateProvider {
    public function active(string $templateKey): array;
    public function renderActive(string $templateKey, array $values): array;
}
