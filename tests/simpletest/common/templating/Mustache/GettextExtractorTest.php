<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

class GettextExtractorTest extends \TuleapTestCase
{
    public function itExtractNothingIfThereIsNoGettextSection()
    {
        $gettext_collector = mock('Tuleap\Templating\Mustache\GettextCollector');
        $entries           = mock('Tuleap\Language\Gettext\POTEntryCollection');

        expect($gettext_collector)->collectEntry()->never();

        $extractor = new GettextExtractor(new \Mustache_Parser(), new \Mustache_Tokenizer(), $gettext_collector);
        $extractor->extract('{{# foo }}{{ bar }}{{/ foo }}', $entries);
    }

    public function itExtractGettextSection()
    {
        $gettext_collector = mock('Tuleap\Templating\Mustache\GettextCollector');
        $entries           = mock('Tuleap\Language\Gettext\POTEntryCollection');

        expect($gettext_collector)->collectEntry('gettext', 'whatever | toto', $entries)->once();

        $extractor = new GettextExtractor(new \Mustache_Parser(), new \Mustache_Tokenizer(), $gettext_collector);
        $extractor->extract('{{# gettext }}whatever | toto{{/ gettext }}', $entries);
    }

    public function itExtractGettextSectionInASection()
    {
        $gettext_collector = mock('Tuleap\Templating\Mustache\GettextCollector');
        $entries           = mock('Tuleap\Language\Gettext\POTEntryCollection');

        expect($gettext_collector)->collectEntry('gettext', 'whatever | toto', $entries)->once();

        $extractor = new GettextExtractor(new \Mustache_Parser(), new \Mustache_Tokenizer(), $gettext_collector);
        $extractor->extract('{{# foo }}{{# gettext }}whatever | toto{{/ gettext }}{{/ foo }}', $entries);
    }

    public function itExtractGettextSectionInAnInvertedSection()
    {
        $gettext_collector = mock('Tuleap\Templating\Mustache\GettextCollector');
        $entries           = mock('Tuleap\Language\Gettext\POTEntryCollection');

        expect($gettext_collector)->collectEntry('gettext', 'whatever | toto', $entries)->once();

        $extractor = new GettextExtractor(new \Mustache_Parser(), new \Mustache_Tokenizer(), $gettext_collector);
        $extractor->extract('{{^ foo }}{{# gettext }}whatever | toto{{/ gettext }}{{/ foo }}', $entries);
    }

    public function itDoesNotExtractGettextSectionInAGettextSection()
    {
        $gettext_collector = mock('Tuleap\Templating\Mustache\GettextCollector');
        $entries           = mock('Tuleap\Language\Gettext\POTEntryCollection');

        expect($gettext_collector)->collectEntry()->count(1);
        expect($gettext_collector)->collectEntry('gettext', 'whatever {{# gettext }}toto{{/ gettext }}', $entries)->once();

        $extractor = new GettextExtractor(new \Mustache_Parser(), new \Mustache_Tokenizer(), $gettext_collector);
        $extractor->extract('{{# gettext }}whatever {{# gettext }}toto{{/ gettext }}{{/ gettext }}', $entries);
    }
}
