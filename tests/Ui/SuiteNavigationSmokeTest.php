<?php

declare(strict_types=1);

$template = file_get_contents(__DIR__ . '/../../templates/index.php');
$info = file_get_contents(__DIR__ . '/../../appinfo/info.xml');
$css = file_get_contents(__DIR__ . '/../../css/style.css');
if ($template === false || $info === false || $css === false) throw new RuntimeException('BRTop-Vertragsdatei konnte nicht gelesen werden.');
if (!str_contains($info, '<app>orgsuite</app>') || !str_contains($info, '<app>localbase</app>') || str_contains($info, '<navigations>')) throw new RuntimeException('OrgSuite-/LocalBase-Appvertrag fehlt.');
if (!str_contains($info, '<version>0.1.33</version>')) throw new RuntimeException('Die BR-Gruppenmigration benötigt eine neue App-Version.');
foreach (["script('orgsuite', 'suite-navigation')", "style('orgsuite', 'suite-navigation')", 'data-orgsuite data-suite="br" data-current-app="brtop"'] as $contract) {
    if (!str_contains($template, $contract)) throw new RuntimeException("Suite-Navigationsvertrag fehlt: {$contract}");
}
if (str_contains($css, '#content')) throw new RuntimeException('BRTop überschreibt weiterhin den globalen Nextcloud-Inhaltscontainer.');
if (preg_match('/#brtop-app\s*\{[^}]*width:\s*100%[^}]*max-width:\s*none[^}]*height:\s*100%[^}]*min-height:\s*0[^}]*overflow-y:\s*auto[^}]*background:\s*var\(--color-main-background\)/s', $css) !== 1) {
    throw new RuntimeException('BRTop-App-Root erfüllt den Vollbreiten- und Scrollvertrag nicht.');
}
echo "BRTop suite navigation smoke test passed\n";
