<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$adminTemplate = (string) file_get_contents($root . '/templates/admin.php');
$appTemplate = (string) file_get_contents($root . '/templates/index.php');
$accessScript = is_file($root . '/js/admin-access.js') ? (string) file_get_contents($root . '/js/admin-access.js') : '';
$bundleOverlay = is_file($root . '/js/shared/overlay.js') ? (string) file_get_contents($root . '/js/shared/overlay.js') : '';
$pageController = (string) file_get_contents($root . '/lib/Controller/PageController.php');
$middleware = (string) file_get_contents($root . '/lib/Middleware/BrAccessMiddleware.php');
$routes = (string) file_get_contents($root . '/appinfo/routes.php');

// Die Freigabesteuerung bleibt eine rollenabhängige Fachapp-Steuerung und liegt nicht im technischen Adminbereich.
foreach (['brtop-full-access-form', 'brtop-full-access-enabled', 'brtop-full-access-history', 'Maximal 24 Stunden'] as $contract) {
    if (!str_contains($appTemplate, $contract)) {
        throw new RuntimeException("Rollenabhängige Fachapp-Steuerung fehlt: {$contract}");
    }
    if (str_contains($adminTemplate, $contract)) {
        throw new RuntimeException("Steuerung liegt noch im technischen Adminbereich: {$contract}");
    }
}

// Overlay-Vertrag: Das Warn-Icon ist ein Button, der ein modales Overlay mit Warnung und Steuerung öffnet.
$overlayId = 'brtop-admin-access-overlay';
foreach ([
    'id="' . $overlayId . '"',
    'role="dialog"',
    'aria-modal="true"',
    'brtop-admin-access-overlay-close',
    'id="brtop-admin-access-warning"',
    'aria-haspopup="dialog"',
    'aria-controls="' . $overlayId . '"',
    'aria-expanded',
] as $contract) {
    if (!str_contains($appTemplate, $contract)) {
        throw new RuntimeException("Overlay-Vertrag fehlt in der Vorlage: {$contract}");
    }
}
if (str_contains($appTemplate, '<details class="brtop-admin-access-warning"')) {
    throw new RuntimeException('Warn-Icon ist noch als aufklappbares details-Element umgesetzt.');
}
if (str_contains($appTemplate, 'target="_blank"')) {
    throw new RuntimeException('Veralteter Verweis "Freigabesteuerung in neuem Tab öffnen" ist noch vorhanden.');
}
$overlayPos = strpos($appTemplate, 'id="' . $overlayId . '"');
$sectionPos = strpos($appTemplate, 'id="brtop-full-access"');
$warningPos = strpos($appTemplate, 'Kein zeitlich begrenzter fachlicher Vollzugriff aktiv.');
if ($overlayPos === false || $sectionPos === false || $warningPos === false || !($overlayPos < $sectionPos) || !($overlayPos < $warningPos)) {
    throw new RuntimeException('Warnung und Freigabesteuerung liegen nicht innerhalb des Overlays.');
}

// UI/API-Vertrag: bestehende Admin-Access-API; Bootstrap konfiguriert die gemeinsame Overlay-Komponente.
foreach (['/api/admin/full-access', 'durationMinutes', 'targetUid', $overlayId, 'brtop-admin-access-warning', 'brtop-admin-access-overlay-close', 'brtop-full-access-form', 'brtop-full-access-history', 'brtop-full-access-status'] as $contract) {
    if (!str_contains($accessScript . $routes, $contract)) {
        throw new RuntimeException("Vollzugriffs-UI/API-Vertrag fehlt: {$contract}");
    }
}
foreach (['AdminAccessOverlay', 'Overlay', 'Escape', 'aria-expanded', 'flz-shared-overlay', 'flz-shared-warn-trigger', 'Widerrufen', 'hidden'] as $contract) {
    if (!str_contains($bundleOverlay, $contract)) {
        throw new RuntimeException("Gemeinsame Overlay-Komponente fehlt im Bundle: {$contract}");
    }
}
if (!str_contains($appTemplate, "script('brtop', 'shared/overlay')")) {
    throw new RuntimeException('Das Template lädt die gemeinsame Overlay-Komponente nicht app-lokal.');
}
if (!str_contains($appTemplate, 'class="flz-shared-overlay"') || !str_contains($appTemplate, 'class="flz-shared-warn-trigger"')) {
    throw new RuntimeException('Overlay und Warn-Icon verwenden nicht die gemeinsamen Klassen.');
}

// Rollenprojektion und serverseitige Zugriffsgrenzen bleiben unverändert.
foreach (['canManageAdminAccess', 'showMissingAdminGrant', 'showAdminAccessLink', 'hasBrTopAccess'] as $contract) {
    if (!str_contains($appTemplate . $pageController, $contract)) {
        throw new RuntimeException("Rollenprojektion fehlt: {$contract}");
    }
}
if (!str_contains($middleware, '$controller instanceof PageController')) {
    throw new RuntimeException('Page-Zugriff wird nicht kontrolliert an die rollenbewusste PageController-Grenze delegiert.');
}
if (!str_contains($routes, "'verb' => 'DELETE'")) {
    throw new RuntimeException('Widerrufroute fehlt.');
}

echo "BRTop admin full access overlay contract tests passed\n";
