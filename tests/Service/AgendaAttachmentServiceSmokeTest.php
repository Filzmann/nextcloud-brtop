<?php

declare(strict_types=1);


use OCA\BrTop\Service\AgendaAttachmentService;
use function OCA\BrTop\Tests\assertSameValue;
use function OCA\BrTop\Tests\assertThrows;

$service = new AgendaAttachmentService();

assertSameValue(
    "Kurzinfo\nzweite Zeile",
    $service->normalizeInvitationNote("  Kurzinfo  \n\n zweite Zeile "),
    'Invitation notes should be trimmed and empty lines should be removed.'
);

assertSameValue(
    "/BR/Einladung.pdf\nTeam/Fall 1.pdf",
    $service->normalizeAttachmentPaths(" /BR/Einladung.pdf \nTeam/Fall 1.pdf\n/BR/Einladung.pdf"),
    'Attachment paths should be trimmed, deduplicated, and keep order.'
);

assertSameValue(
    ['/BR/Einladung.pdf', 'Team/Fall 1.pdf'],
    $service->attachmentPathLines("/BR/Einladung.pdf\nTeam/Fall 1.pdf"),
    'Attachment path lines should parse normalized paths.'
);

assertThrows(
    static fn() => $service->normalizeAttachmentPaths('/BR/../secret.pdf'),
    \InvalidArgumentException::class,
    'Attachment paths with .. segments should be rejected.'
);

echo 'AgendaAttachmentService smoke tests passed' . PHP_EOL;
