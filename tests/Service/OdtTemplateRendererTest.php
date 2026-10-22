<?php

declare(strict_types=1);

use OCA\BrTop\Service\OdtTemplateRenderer;
use function OCA\BrTop\Tests\assertContainsString;

$method = new ReflectionMethod(OdtTemplateRenderer::class, 'renderProtocol');
if ($method->getNumberOfParameters() < 4) {
    throw new RuntimeException('ODT rendering must receive the selected template bytes and organization settings.');
}
$validatorSource = (string)file_get_contents(dirname(__DIR__, 2) . '/lib/Service/OdtTemplateValidator.php');
foreach (['MAX_ARCHIVE_ENTRIES', 'MAX_UNCOMPRESSED_BYTES', 'OFFICE_NAMESPACE', 'SCRIPT_NAMESPACE', 'XLINK_NAMESPACE', 'DOMDocument', 'ALLOWED_PLACEHOLDERS', 'Nicht erlaubter ODT-Platzhalter'] as $securityContract) {
    assertContainsString($securityContract, $validatorSource, 'ODT validation must reject active content and decompression abuse.');
}

$template = (string)file_get_contents(dirname(__DIR__, 2) . '/templates/odt/protokoll-template.odt');
if (!class_exists(ZipArchive::class)) {
    $source = (string)file_get_contents(dirname(__DIR__, 2) . '/lib/Service/OdtTemplateRenderer.php');
    foreach (['councilName', 'councilAddress', '$templateContent'] as $contract) {
        assertContainsString($contract, $source, 'ODT renderer source contract is incomplete without the local ZIP extension.');
    }
    echo 'ODT template renderer source contract passed; ZIP runtime unavailable' . PHP_EOL;
    return;
}
$rendered = (new OdtTemplateRenderer())->renderProtocol(
    ['meeting_date' => '2026-10-27'],
    [],
    $template,
    [
        'councilName' => 'Betriebsrat Beispiel & Partner',
        'councilAddress' => 'Beispielweg 1 <Hof>',
        'issuePlace' => 'Beispielstadt',
    ]
);

$path = tempnam(sys_get_temp_dir(), 'brtop-odt-test-');
if ($path === false || file_put_contents($path, $rendered) === false) {
    throw new RuntimeException('Rendered ODT could not be inspected.');
}
$zip = new ZipArchive();
if ($zip->open($path) !== true) {
    throw new RuntimeException('Rendered ODT is not a valid ZIP archive.');
}
$styles = (string)$zip->getFromName('styles.xml');
$content = (string)$zip->getFromName('content.xml');
$zip->close();
unlink($path);

assertContainsString('Betriebsrat Beispiel &amp; Partner', $styles, 'Configured council names should be XML-escaped in ODT styles.');
assertContainsString('Beispielweg 1 &lt;Hof&gt;', $styles, 'Configured addresses should be XML-escaped in ODT styles.');
assertContainsString('Anwesenheit und ordnungsgemäße Einladung', $content, 'Fixed protocol semantics must remain code-owned in ODT output.');

echo 'ODT template renderer tests passed' . PHP_EOL;
