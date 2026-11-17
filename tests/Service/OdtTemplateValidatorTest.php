<?php

declare(strict_types=1);

namespace {
    if (!class_exists(ZipArchive::class)) {
        final class ZipArchive {
            public static array $entries = [];
            public static int $statCalls = 0;
            public static int $readsBeforeAllStats = 0;
            public int $numFiles = 0;

            public function open(string $path): bool {
                $this->numFiles = count(self::$entries);
                self::$statCalls = 0;
                self::$readsBeforeAllStats = 0;
                return true;
            }

            public function close(): bool { return true; }
            public function getNameIndex(int $index): string|false { return self::$entries[$index]['name'] ?? false; }
            public function statIndex(int $index): array|false {
                self::$statCalls++;
                return isset(self::$entries[$index]) ? ['size' => self::$entries[$index]['size'] ?? strlen(self::$entries[$index]['content'])] : false;
            }
            public function getFromIndex(int $index): string|false {
                if (self::$statCalls < $this->numFiles) { self::$readsBeforeAllStats++; }
                return self::$entries[$index]['content'] ?? false;
            }
            public function getFromName(string $name): string|false {
                foreach (self::$entries as $index => $entry) {
                    if ($entry['name'] === $name) { return $this->getFromIndex($index); }
                }
                return false;
            }
        }
    }
}

namespace OCA\BrTop\Tests {
    use OCA\BrTop\Service\OdtTemplateValidator;

    if ((new \ReflectionClass(\ZipArchive::class))->isInternal()) {
        echo "ODT template validator fake-ZIP contracts skipped; native ZIP runtime available\n";
        return;
    }

    $validContent = '<o:document-content xmlns:o="urn:oasis:names:tc:opendocument:xmlns:office:1.0"><o:body><o:text>{{SITZUNG_DATUM}}</o:text></o:body></o:document-content>';
    $validStyles = '<o:document-styles xmlns:o="urn:oasis:names:tc:opendocument:xmlns:office:1.0"/>';
    $entries = static fn(?string $content = null, ?string $styles = null): array => [
        ['name' => 'mimetype', 'content' => 'application/vnd.oasis.opendocument.text'],
        ['name' => 'content.xml', 'content' => $content ?? $validContent],
        ['name' => 'styles.xml', 'content' => $styles ?? $validStyles],
        ['name' => 'META-INF/manifest.xml', 'content' => '<m:manifest xmlns:m="urn:oasis:names:tc:opendocument:xmlns:manifest:1.0"/>'],
    ];
    $validator = new OdtTemplateValidator();

    \ZipArchive::$entries = $entries();
    $validator->validate('synthetic-odt');

    $expectInvalid = static function (array $archiveEntries, string $message) use ($validator): void {
        \ZipArchive::$entries = $archiveEntries;
        try {
            $validator->validate('synthetic-odt');
            throw new \RuntimeException($message);
        } catch (\InvalidArgumentException) {
        }
    };

    $expectInvalid($entries(
        '<office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"><office:body><office:text></office:body></office:document-content>'
    ), 'Malformed XML must be rejected.');

    $expectInvalid($entries(
        '<o:document-content xmlns:o="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:d="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0" xmlns:link="http://www.w3.org/1999/xlink"><o:body><o:text><d:image link:href="https://example.invalid/pixel"/></o:text></o:body></o:document-content>'
    ), 'External references must be rejected independently of their namespace prefix.');

    $expectInvalid($entries(
        null,
        '<o:document-styles xmlns:o="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:d="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0" xmlns:link="http://www.w3.org/1999/xlink"><d:image link:href="https&#58;//example.invalid/pixel"/></o:document-styles>'
    ), 'Encoded external references in copied XML entries must be rejected.');

    $malformedManifest = $entries();
    $malformedManifest[3]['content'] = '<m:manifest xmlns:m="urn:oasis:names:tc:opendocument:xmlns:manifest:1.0">';
    $expectInvalid($malformedManifest, 'Every copied XML entry must be well formed.');

    $expectInvalid($entries(
        '<!DOCTYPE o:document-content [<!ENTITY remote "https://example.invalid/pixel">]><o:document-content xmlns:o="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:d="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0" xmlns:link="http://www.w3.org/1999/xlink"><o:body><o:text><d:image link:href="&remote;"/></o:text></o:body></o:document-content>'
    ), 'DTD/entity indirection must be rejected.');

    $expectInvalid($entries(
        '<o:document-content xmlns:o="urn:oasis:names:tc:opendocument:xmlns:office:1.0"><o:scripts/><o:body><o:text/></o:body></o:document-content>'
    ), 'Active content must be rejected independently of its namespace prefix.');

    $bomb = $entries();
    $bomb[] = ['name' => 'Pictures/oversized.bin', 'content' => 'x', 'size' => 50_000_001];
    $expectInvalid($bomb, 'Oversized expanded archives must be rejected.');
    if (\ZipArchive::$readsBeforeAllStats !== 0) {
        throw new \RuntimeException('Archive entries must not be decompressed before every size statistic has passed validation.');
    }

    echo "ODT template validator tests passed\n";
}
