<?php

declare(strict_types=1);

namespace OCA\BrTop\Service;

/**
 * Validates and renders the text-only document-template boundary.
 *
 * Canonical domain blocks stay in PHP services. Administrators may only
 * arrange the explicitly declared placeholders around those blocks.
 */
final class DocumentTemplateEngine {
    private const DEFINITIONS = [
        'invitation_email' => [
            'allowed' => ['SALUTATION', 'INVITATION_INTRO', 'MEETING_DETAILS', 'RECIPIENT_SUMMARY', 'AGENDA', 'CLOSING', 'SENDER'],
            'required' => ['MEETING_DETAILS', 'AGENDA'],
            'raw' => ['MEETING_DETAILS', 'RECIPIENT_SUMMARY', 'AGENDA'],
            'format' => 'text',
        ],
        'invitation_markdown' => [
            'allowed' => ['SUBJECT', 'EMAIL_BODY'],
            'required' => ['SUBJECT', 'EMAIL_BODY'],
            'raw' => ['EMAIL_BODY'],
            'format' => 'markdown',
        ],
        'protocol_markdown' => [
            'allowed' => ['COUNCIL_NAME', 'COUNCIL_ADDRESS', 'ISSUE_PLACE', 'PROTOCOL_BODY'],
            'required' => ['PROTOCOL_BODY'],
            'raw' => ['PROTOCOL_BODY'],
            'format' => 'markdown',
        ],
        'resolution_markdown' => [
            'allowed' => ['COUNCIL_NAME', 'COUNCIL_ADDRESS', 'ISSUE_PLACE', 'RESOLUTION_BODY'],
            'required' => ['RESOLUTION_BODY'],
            'raw' => ['RESOLUTION_BODY'],
            'format' => 'markdown',
        ],
    ];

    public function validateAndNormalize(string $templateKey, string $content): string {
        $definition = $this->definition($templateKey);
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        if (str_contains($content, "\0")) {
            throw new \InvalidArgumentException('Vorlagen dürfen keine Nullbytes enthalten.');
        }
        if (strlen($content) > 200000) {
            throw new \InvalidArgumentException('Die Vorlage überschreitet die maximale Größe von 200.000 Bytes.');
        }
        if (trim($content) === '') {
            throw new \InvalidArgumentException('Die Vorlage darf nicht leer sein.');
        }
        if (preg_match('/<\s*\/?\s*[a-z][^>]*>|\bon[a-z]+\s*=|javascript\s*:/i', $content) === 1) {
            throw new \InvalidArgumentException('Die Vorlage enthält ausführbaren HTML- oder Skriptinhalt.');
        }

        preg_match_all('/\{\{\s*([^{}]+?)\s*\}\}/', $content, $matches);
        $placeholders = array_values(array_unique(array_map(
            static fn(string $value): string => strtoupper(trim($value)),
            $matches[1] ?? []
        )));
        foreach ($placeholders as $placeholder) {
            if (!preg_match('/^[A-Z][A-Z0-9_]*$/', $placeholder)
                || !in_array($placeholder, $definition['allowed'], true)) {
                throw new \InvalidArgumentException('Nicht erlaubter Vorlagenplatzhalter: ' . $placeholder);
            }
        }
        foreach ($definition['required'] as $required) {
            if (!in_array($required, $placeholders, true)) {
                throw new \InvalidArgumentException('Pflichtplatzhalter fehlt: ' . $required);
            }
        }

        $content = preg_replace_callback(
            '/\{\{\s*([^{}]+?)\s*\}\}/',
            static fn(array $match): string => '{{' . strtoupper(trim((string)$match[1])) . '}}',
            $content
        ) ?? $content;

        return rtrim($content) . "\n";
    }

    public function render(string $templateKey, string $content, array $values): string {
        $definition = $this->definition($templateKey);
        $content = $this->validateAndNormalize($templateKey, $content);
        $replacements = [];
        foreach ($definition['allowed'] as $placeholder) {
            $value = $this->plainText((string)($values[$placeholder] ?? ''));
            if ($definition['format'] === 'markdown' && !in_array($placeholder, $definition['raw'], true)) {
                $value = $this->escapeMarkdown($value);
            }
            $replacements['{{' . $placeholder . '}}'] = $value;
        }

        return strtr($content, $replacements);
    }

    public function keys(): array {
        return array_keys(self::DEFINITIONS);
    }

    public function definitionFor(string $templateKey): array {
        return $this->definition($templateKey);
    }

    private function definition(string $templateKey): array {
        if (!isset(self::DEFINITIONS[$templateKey])) {
            throw new \InvalidArgumentException('Unbekannter Vorlagentyp: ' . $templateKey);
        }

        return self::DEFINITIONS[$templateKey];
    }

    private function plainText(string $value): string {
        $value = str_replace(["\r\n", "\r", "\0"], ["\n", "\n", ''], $value);
        return preg_replace('/[\x01-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';
    }

    private function escapeMarkdown(string $value): string {
        return preg_replace('/([\\\\`*_{}\[\]()#+\-.!|>])/', '\\\\$1', $value) ?? '';
    }
}
