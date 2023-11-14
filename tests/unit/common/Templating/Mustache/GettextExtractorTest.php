<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Templating\Mustache;

final class GettextExtractorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItExtractNothingIfThereIsNoGettextSection(): void
    {
        $gettext_collector = $this->createMock(\Tuleap\Templating\Mustache\GettextCollector::class);
        $entries           = $this->createMock(\Tuleap\Language\Gettext\POTEntryCollection::class);

        $gettext_collector->expects(self::never())->method('collectEntry');

        $extractor = new GettextExtractor(new \Mustache_Parser(), new \Mustache_Tokenizer(), $gettext_collector);
        $extractor->extract('{{# foo }}{{ bar }}{{/ foo }}', $entries);
    }

    public function testItExtractGettextSection(): void
    {
        $gettext_collector = $this->createMock(\Tuleap\Templating\Mustache\GettextCollector::class);
        $entries           = $this->createMock(\Tuleap\Language\Gettext\POTEntryCollection::class);

        $gettext_collector->expects(self::once())->method('collectEntry')->with('gettext', 'whatever | toto', $entries);

        $extractor = new GettextExtractor(new \Mustache_Parser(), new \Mustache_Tokenizer(), $gettext_collector);
        $extractor->extract('{{# gettext }}whatever | toto{{/ gettext }}', $entries);
    }

    public function testItExtractGettextSectionInASection(): void
    {
        $gettext_collector = $this->createMock(\Tuleap\Templating\Mustache\GettextCollector::class);
        $entries           = $this->createMock(\Tuleap\Language\Gettext\POTEntryCollection::class);

        $gettext_collector->expects(self::once())->method('collectEntry')->with('gettext', 'whatever | toto', $entries);

        $extractor = new GettextExtractor(new \Mustache_Parser(), new \Mustache_Tokenizer(), $gettext_collector);
        $extractor->extract('{{# foo }}{{# gettext }}whatever | toto{{/ gettext }}{{/ foo }}', $entries);
    }

    public function testItExtractGettextSectionInAnInvertedSection(): void
    {
        $gettext_collector = $this->createMock(\Tuleap\Templating\Mustache\GettextCollector::class);
        $entries           = $this->createMock(\Tuleap\Language\Gettext\POTEntryCollection::class);

        $gettext_collector->expects(self::once())->method('collectEntry')->with('gettext', 'whatever | toto', $entries);

        $extractor = new GettextExtractor(new \Mustache_Parser(), new \Mustache_Tokenizer(), $gettext_collector);
        $extractor->extract('{{^ foo }}{{# gettext }}whatever | toto{{/ gettext }}{{/ foo }}', $entries);
    }

    public function testItDoesNotExtractGettextSectionInAGettextSection(): void
    {
        $gettext_collector = $this->createMock(\Tuleap\Templating\Mustache\GettextCollector::class);
        $entries           = $this->createMock(\Tuleap\Language\Gettext\POTEntryCollection::class);

        $gettext_collector->expects(self::once())->method('collectEntry')->with('gettext', 'whatever {{# gettext }}toto{{/ gettext }}', $entries);

        $extractor = new GettextExtractor(new \Mustache_Parser(), new \Mustache_Tokenizer(), $gettext_collector);
        $extractor->extract('{{# gettext }}whatever {{# gettext }}toto{{/ gettext }}{{/ gettext }}', $entries);
    }
}
