<?php

declare(strict_types=1);

namespace OCA\BrTop\Privacy;

use OCA\FlzDataProtection\PublicApi\V1\RegisterProcessingMetadataProvidersEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

final class BrTopProcessingMetadataProviderListener implements IEventListener {
    public function __construct(private BrTopProcessingMetadataProvider $provider) {}

    public function handle(Event $event): void {
        if ($event instanceof RegisterProcessingMetadataProvidersEvent) {
            $event->register($this->provider);
        }
    }
}
