<?php

declare(strict_types=1);

$template = file_get_contents(__DIR__ . '/../../templates/index.php');
$info = file_get_contents(__DIR__ . '/../../appinfo/info.xml');
if ($template === false || $info === false) throw new RuntimeException('BRTop-Vertragsdatei konnte nicht gelesen werden.');
if (!str_contains($info, '<app>orgsuite</app>') || str_contains($info, '<navigations>')) throw new RuntimeException('OrgSuite-Appvertrag fehlt.');
foreach (["script('orgsuite', 'suite-navigation')", "style('orgsuite', 'suite-navigation')", 'data-orgsuite data-suite="br" data-current-app="brtop"'] as $contract) {
    if (!str_contains($template, $contract)) throw new RuntimeException("Suite-Navigationsvertrag fehlt: {$contract}");
}
echo "BRTop suite navigation smoke test passed\n";
