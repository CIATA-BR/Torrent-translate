<?php

namespace Tests\Unit;

use App\Services\GettextCatalogService;
use PHPUnit\Framework\TestCase;

class GettextCatalogServiceTest extends TestCase
{
    public function test_compact_gettext_header_is_ignored_and_entries_are_parsed(): void
    {
        $pot = <<<'POT'
msgid ""
msgstr ""
"Project-Id-Version: SerrebiTorrent\nMIME-Version: 1.0\nContent-Type: text/plain; charset=UTF-8\nContent-Transfer-Encoding: 8bit\n"

msgid "Start"
msgstr ""

msgid "Select {name}"
msgstr ""
POT;

        $entries = (new GettextCatalogService())->parsePot($pot);

        $this->assertSame(['Start', 'Select {name}'], $entries);
    }
}
