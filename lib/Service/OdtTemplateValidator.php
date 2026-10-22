<?php

declare(strict_types=1);

namespace OCA\BrTop\Service;

use RuntimeException;
use ZipArchive;

final class OdtTemplateValidator {
    private const MAX_ARCHIVE_ENTRIES = 500;
    private const MAX_UNCOMPRESSED_BYTES = 50_000_000;
    private const OFFICE_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
    private const SCRIPT_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:script:1.0';
    private const XLINK_NAMESPACE = 'http://www.w3.org/1999/xlink';

    private const ALLOWED_PLACEHOLDERS = [
        'ANWESEND',
        'ENTSCHULDIGT',
        'MODERATION',
        'PROTOKOLL',
        'SITZUNG_DATUM',
        'UNENTSCHULDIGT',
        'GREMIENNAME',
        'GREMIUM_ADRESSE',
        'AUSSTELLUNGSORT',
    ];

    public function validate(string $content): void {
        if ($content === '' || strlen($content) > 10_000_000) {
            throw new \InvalidArgumentException('Die ODT-Vorlage ist leer oder größer als 10 MB.');
        }
        $path = tempnam(sys_get_temp_dir(), 'brtop-odt-check-');
        if ($path === false || file_put_contents($path, $content) === false) {
            throw new RuntimeException('Die ODT-Vorlage konnte nicht temporär geprüft werden.');
        }

        $zip = new ZipArchive();
        $opened = false;
        try {
            if ($zip->open($path) !== true) {
                throw new \InvalidArgumentException('Die ODT-Vorlage ist kein gültiges ZIP/ODT-Archiv.');
            }
            $opened = true;
            if ($zip->numFiles > self::MAX_ARCHIVE_ENTRIES) {
                throw new \InvalidArgumentException('Die ODT-Vorlage enthält zu viele Archiveinträge.');
            }

            $uncompressedBytes = 0;
            $entries = [];
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $name = $zip->getNameIndex($index);
                $stat = $zip->statIndex($index);
                if (!is_string($name) || !is_array($stat)) {
                    throw new \InvalidArgumentException('Ein Archiveintrag der ODT-Vorlage konnte nicht geprüft werden.');
                }
                $size = $stat['size'] ?? null;
                if (!is_int($size) || $size < 0 || $size > self::MAX_UNCOMPRESSED_BYTES - $uncompressedBytes) {
                    throw new \InvalidArgumentException('Die entpackte ODT-Vorlage überschreitet 50 MB.');
                }
                $uncompressedBytes += $size;
                if (str_starts_with($name, '/') || str_contains($name, '../') || preg_match('/^(?:Basic|Scripts)\//i', $name) === 1) {
                    throw new \InvalidArgumentException('Die ODT-Vorlage enthält einen nicht erlaubten Archivpfad: ' . $name);
                }
                $entries[] = ['index' => $index, 'name' => $name];
            }

            $hasMimeType = false;
            $hasContentXml = false;
            $hasStylesXml = false;
            foreach ($entries as $entry) {
                $name = $entry['name'];
                $entryContent = $zip->getFromIndex($entry['index']);
                if (!is_string($entryContent)) {
                    throw new \InvalidArgumentException('Ein Archiveintrag der ODT-Vorlage konnte nicht gelesen werden: ' . $name);
                }
                if ($name === 'mimetype') {
                    $hasMimeType = $entryContent === 'application/vnd.oasis.opendocument.text';
                }
                if (str_ends_with(strtolower($name), '.xml')) {
                    $this->validateXml($name, $entryContent, $name === 'content.xml');
                    if ($name === 'content.xml') {
                        $hasContentXml = true;
                    }
                    if ($name === 'styles.xml') {
                        $hasStylesXml = true;
                    }
                    preg_match_all('/\{\{\s*([^{}]+?)\s*\}\}/', $entryContent, $matches);
                    foreach ($matches[1] ?? [] as $placeholder) {
                        $placeholder = strtoupper(trim((string)$placeholder));
                        if (!in_array($placeholder, self::ALLOWED_PLACEHOLDERS, true)) {
                            throw new \InvalidArgumentException('Nicht erlaubter ODT-Platzhalter: ' . $placeholder);
                        }
                    }
                }
            }
            if (!$hasMimeType) {
                throw new \InvalidArgumentException('Die ODT-Vorlage hat keinen gültigen ODT-MIME-Typ.');
            }
            if (!$hasContentXml) {
                throw new \InvalidArgumentException('Die ODT-Vorlage enthält keine content.xml.');
            }
            if (!$hasStylesXml) {
                throw new \InvalidArgumentException('Die ODT-Vorlage enthält keine styles.xml.');
            }
        } finally {
            if ($opened) {
                $zip->close();
            }
            unlink($path);
        }
    }

    private function validateXml(string $name, string $xml, bool $requireOfficeText): void {
        $previous = libxml_use_internal_errors(true);
        try {
            $document = new \DOMDocument();
            if (!$document->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT) || $document->doctype !== null) {
                throw new \InvalidArgumentException('Die ODT-Vorlage enthält ungültiges XML: ' . $name);
            }

            $hasOfficeText = false;
            foreach ($document->getElementsByTagName('*') as $element) {
                if ($element->namespaceURI === self::OFFICE_NAMESPACE && $element->localName === 'text') {
                    $hasOfficeText = true;
                }
                if (
                    ($element->namespaceURI === self::OFFICE_NAMESPACE && $element->localName === 'scripts')
                    || ($element->namespaceURI === self::SCRIPT_NAMESPACE && $element->localName === 'event-listener')
                ) {
                    throw new \InvalidArgumentException('Die ODT-Vorlage enthält aktive Inhalte oder externe Verweise.');
                }
                foreach ($element->attributes ?? [] as $attribute) {
                    if (
                        $attribute->namespaceURI === self::XLINK_NAMESPACE
                        && $attribute->localName === 'href'
                        && $this->isExternalReference((string)$attribute->value)
                    ) {
                        throw new \InvalidArgumentException('Die ODT-Vorlage enthält aktive Inhalte oder externe Verweise.');
                    }
                }
            }

            if ($requireOfficeText && !$hasOfficeText) {
                throw new \InvalidArgumentException('Die ODT-Vorlage enthält keinen ersetzbaren office:text-Bereich.');
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private function isExternalReference(string $value): bool {
        $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $normalized = preg_replace('/[\x00-\x20\x7f]+/', '', $decoded) ?? $decoded;
        return preg_match('/^[a-z][a-z0-9+.-]*:/i', $normalized) === 1
            || str_starts_with($normalized, '//')
            || str_starts_with($normalized, '/')
            || preg_match('~(?:^|/)\.\.(?:/|$)~', $normalized) === 1;
    }
}
