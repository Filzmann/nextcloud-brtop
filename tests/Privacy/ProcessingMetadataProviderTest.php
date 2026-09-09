<?php

declare(strict_types=1);

namespace OCP\AppFramework {
    class App { public function __construct(string $appName, array $urlParams = []) {} }
}

namespace OCP\AppFramework\Bootstrap {
    interface IBootContext {}
    interface IBootstrap {}
    interface IRegistrationContext {}
}

namespace OCP\EventDispatcher {
    class Event { public function __construct() {} }
    interface IEventListener { public function handle(Event $event): void; }
}

namespace {
    use OCA\BrTop\Privacy\BrTopProcessingMetadataProvider;
    use OCA\BrTop\Privacy\BrTopProcessingMetadataProviderListener;
    use OCA\FilzmannDataProtection\PublicApi\V1\RegisterProcessingMetadataProvidersEvent;
    use OCP\EventDispatcher\Event;

    $provider = new BrTopProcessingMetadataProvider();
    $catalog = $provider->catalog();
    $descriptor = $provider->descriptor();
    if ($descriptor->appId() !== 'brtop' || $descriptor->displayName() !== 'BR TOP- und Sitzungsverwaltung' || $descriptor->contractVersion() !== '1.0') {
        throw new RuntimeException('Der Processing-Metadata-Provider beschreibt BR TOP nicht korrekt.');
    }
    if ($catalog->appId() !== 'brtop') {
        throw new RuntimeException('Processing-Metadata-Provider und Katalog verwenden nicht die kanonische App-ID.');
    }
    if ($catalog->processingIds() !== [
        'council_legislature_and_roster_management',
        'meeting_agenda_and_protocol_management',
        'invitation_snapshot_and_absence_management',
        'document_generation_and_file_storage',
        'temporary_admin_full_access',
    ]) {
        throw new RuntimeException('Der app-lokale Processing-Katalog ist unvollständig.');
    }
    if (array_key_exists('personal_runtime_data', $catalog->toArray())) {
        throw new RuntimeException('Der Processing-Katalog enthält personenbezogene Laufzeitdaten.');
    }

    $registration = new RegisterProcessingMetadataProvidersEvent();
    $listener = new BrTopProcessingMetadataProviderListener($provider);
    $listener->handle(new Event());
    if ($registration->providers() !== []) {
        throw new RuntimeException('Ein fremdes Event registriert den Processing-Metadata-Provider.');
    }
    $listener->handle($registration);
    if (($registration->providers()['brtop'] ?? null) !== $provider) {
        throw new RuntimeException('Der Processing-Metadata-Provider wird nicht lazy registriert.');
    }

    $application = (string)file_get_contents(dirname(__DIR__, 2) . '/lib/AppInfo/Application.php');
    if (!str_contains($application, 'registerEventListener(RegisterProcessingMetadataProvidersEvent::class, BrTopProcessingMetadataProviderListener::class)')) {
        throw new RuntimeException('Der Bootstrap registriert den Processing-Metadata-Provider nicht am öffentlichen V1-Event.');
    }

    echo "BR TOP processing metadata provider test passed\n";
}
