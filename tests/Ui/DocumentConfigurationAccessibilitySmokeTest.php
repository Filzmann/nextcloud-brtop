<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$template = (string)file_get_contents($root . '/templates/admin.php');
$script = (string)file_get_contents($root . '/js/admin-document-configuration.js');

foreach ([
    "script('brtop', 'admin-document-configuration')",
    'brtop-document-organization-form',
    'name="committeeCodesJson"',
    'brtop-document-template-list',
    'role="list"',
    'brtop-document-configuration-status',
    'role="status"',
    'aria-live="polite"',
    'brtop-document-template-content-label',
    'brtop-document-template-file-label',
] as $contract) {
    if (!str_contains($template, $contract)) {
        throw new RuntimeException("Dokumentkonfigurations-UI-Vertrag fehlt: {$contract}");
    }
}

foreach ([
    '/api/admin/document-configuration',
    'encodeURIComponent',
    'committeeCodesJson',
    'ODT-Datei',
    'Die Vorlage wurde freigegeben.',
] as $contract) {
    if (!str_contains($script, $contract)) {
        throw new RuntimeException("Dokumentkonfigurations-Binding fehlt: {$contract}");
    }
}

echo "BRTop document configuration accessibility smoke test passed\n";
