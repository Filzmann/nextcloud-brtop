<?php

declare(strict_types=1);

namespace OCA\BrTop\Privacy;

use DomainException;
use InvalidArgumentException;
use JsonException;
use OCA\BrTop\AppInfo\Application;
use OCA\FlzDataProtection\PublicApi\V1\ProcessingMetadataCatalog;
use OCA\FlzDataProtection\PublicApi\V1\ProcessingMetadataProvider;
use OCA\FlzDataProtection\PublicApi\V1\ProcessingMetadataProviderDescriptor;

final class BrTopProcessingMetadataProvider implements ProcessingMetadataProvider {
    public function descriptor(): ProcessingMetadataProviderDescriptor {
        return new ProcessingMetadataProviderDescriptor(Application::APP_ID, 'BR TOP- und Sitzungsverwaltung', '1.0');
    }

    public function catalog(): ProcessingMetadataCatalog {
        $path = dirname(__DIR__, 2) . '/resources/privacy-processing.json';
        if (!is_file($path)) {
            throw new DomainException('Processing metadata catalog unavailable.');
        }

        try {
            $content = file_get_contents($path);
            if ($content === false) {
                throw new DomainException('Processing metadata catalog unavailable.');
            }
            $payload = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($payload)) {
                throw new DomainException('Processing metadata catalog is invalid.');
            }
            return ProcessingMetadataCatalog::fromArray($payload);
        } catch (JsonException|InvalidArgumentException) {
            throw new DomainException('Processing metadata catalog is invalid.');
        }
    }
}
