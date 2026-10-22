<?php

declare(strict_types=1);

use OCA\BrTop\Service\DocumentTemplateEngine;
use function OCA\BrTop\Tests\assertContainsString;
use function OCA\BrTop\Tests\assertSameValue;

$engine = new DocumentTemplateEngine();

$template = "{{SALUTATION}}\n\n{{INVITATION_INTRO}}\n\n{{MEETING_DETAILS}}\n\n{{AGENDA}}\n\n{{CLOSING}}\n{{SENDER}}\n";
$normalized = $engine->validateAndNormalize('invitation_email', $template);
assertSameValue($template, $normalized, 'Valid invitation templates should preserve their normalized structure.');

$rendered = $engine->render('invitation_email', $template, [
    'SALUTATION' => 'Liebe Kolleg*innen,',
    'INVITATION_INTRO' => 'hiermit lade ich euch ein.',
    'MEETING_DETAILS' => "Sitzung: Test\nDatum: 21.10.2026",
    'AGENDA' => "1. Bericht\n2. Beratung",
    'CLOSING' => 'Viele Grüße',
    'SENDER' => 'BR-Vorsitz',
]);
assertContainsString('Sitzung: Test', $rendered, 'Rendered invitation templates should contain canonical meeting details.');
assertContainsString('1. Bericht', $rendered, 'Rendered invitation templates should contain the canonical agenda block.');

try {
    $engine->validateAndNormalize('invitation_email', '{{SALUTATION}}');
    throw new RuntimeException('A template without canonical required blocks must be rejected.');
} catch (InvalidArgumentException $e) {
    assertContainsString('MEETING_DETAILS', $e->getMessage(), 'Missing required placeholders should be diagnosed precisely.');
}

try {
    $engine->validateAndNormalize('protocol_markdown', "{{PROTOCOL_BODY}}\n{{PHP_CODE}}");
    throw new RuntimeException('An unknown executable-looking placeholder must be rejected.');
} catch (InvalidArgumentException $e) {
    assertContainsString('PHP_CODE', $e->getMessage(), 'Unknown placeholders should be named in validation failures.');
}

try {
    $engine->validateAndNormalize('protocol_markdown', "<script>alert('x')</script>\n{{PROTOCOL_BODY}}");
    throw new RuntimeException('Executable HTML in a text template must be rejected.');
} catch (InvalidArgumentException $e) {
    assertContainsString('ausführbaren HTML', $e->getMessage(), 'Executable markup should be rejected explicitly.');
}

try {
    $engine->validateAndNormalize('protocol_markdown', "<div>nicht kontrolliertes HTML</div>\n{{PROTOCOL_BODY}}");
    throw new RuntimeException('Raw HTML must not bypass the non-executable template contract.');
} catch (InvalidArgumentException $e) {
    assertContainsString('ausführbaren HTML', $e->getMessage(), 'All raw HTML should be rejected at the template boundary.');
}

$escaped = $engine->render('invitation_markdown', "# {{SUBJECT}}\n\n```text\n{{EMAIL_BODY}}\n```\n", [
    'SUBJECT' => 'Sitzung [intern] *wichtig*',
    'EMAIL_BODY' => "Zeile 1\nZeile 2",
]);
assertContainsString('Sitzung \\[intern\\] \\*wichtig\\*', $escaped, 'Scalar Markdown placeholders must be escaped.');
assertContainsString("Zeile 1\nZeile 2", $escaped, 'Canonical body placeholders must remain structured text.');

echo 'DocumentTemplateEngine tests passed' . PHP_EOL;
