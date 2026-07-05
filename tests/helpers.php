<?php

declare(strict_types=1);

namespace OCA\BrTop\Tests;

require_once __DIR__ . '/../../localbase/tests/Support/assertions.php';

use function OCA\LocalBase\Tests\Support\assertContainsString as supportAssertContainsString;
use function OCA\LocalBase\Tests\Support\assertSameValue as supportAssertSameValue;
use function OCA\LocalBase\Tests\Support\assertThrows as supportAssertThrows;

function assertSameValue(mixed $expected, mixed $actual, string $message): void {
    supportAssertSameValue($expected, $actual, $message);
}

function assertContainsString(string $needle, string $haystack, string $message): void {
    supportAssertContainsString($needle, $haystack, $message);
}

function assertThrows(callable $callback, string $exceptionClass, string $message): \Throwable {
    return supportAssertThrows($callback, $exceptionClass, $message);
}
