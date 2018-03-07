<?php
/**
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
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

use Tuleap\Language\Gettext\POTFileDumper;

class DomainExtractorTest extends \TuleapTestCase
{
    public function itRecursivelyFindsAllStringsForDefaultDomain()
    {
        $destination_template = $this->getTmpDir() . '/template.pot';
        $sources              = __DIR__ . '/_fixtures';

        $extractor = new DomainExtractor(
            new POTFileDumper(),
            new GettextExtractor(
                new \Mustache_Parser(),
                new \Mustache_Tokenizer(),
                new GettextCollector(new GettextSectionContentTransformer())
            )
        );
        $extractor->extract(GettextCollector::DEFAULT_DOMAIN, $sources, $destination_template);

        $content = file_get_contents($destination_template);
        $this->assertPattern('/foo in tuleap-core/', $content);
        $this->assertPattern('/bar in tuleap-core/', $content);
        $this->assertNoPattern('/foo in mydomain/', $content);
        $this->assertNoPattern('/bar in mydomain/', $content);
        $this->assertNoPattern('/foo in .php/', $content);
    }

    public function itRecursivelyFindsAllStringsForMyDomain()
    {
        $destination_template = $this->getTmpDir() . '/template.pot';
        $sources              = __DIR__ . '/_fixtures';

        $extractor = new DomainExtractor(
            new POTFileDumper(),
            new GettextExtractor(
                new \Mustache_Parser(),
                new \Mustache_Tokenizer(),
                new GettextCollector(new GettextSectionContentTransformer())
            )
        );
        $extractor->extract('mydomain', $sources, $destination_template);

        $content = file_get_contents($destination_template);
        $this->assertNoPattern('/foo in tuleap-core/', $content);
        $this->assertNoPattern('/bar in tuleap-core/', $content);
        $this->assertPattern('/foo in mydomain/', $content);
        $this->assertPattern('/bar in mydomain/', $content);
        $this->assertNoPattern('/foo in .php/', $content);
    }

    public function itFindsNothingIfSourcesFolderDoesNotExist()
    {
        $destination_template = $this->getTmpDir() . '/template.pot';
        $sources              = '/donotexist';

        $extractor = new DomainExtractor(
            new POTFileDumper(),
            new GettextExtractor(
                new \Mustache_Parser(),
                new \Mustache_Tokenizer(),
                new GettextCollector(new GettextSectionContentTransformer())
            )
        );
        $extractor->extract('mydomain', $sources, $destination_template);

        $content = file_get_contents($destination_template);
        $this->assertEqual('', $content);
    }

    public function itFindsNothingForUnknownDomain()
    {
        $destination_template = $this->getTmpDir() . '/template.pot';
        $sources              = __DIR__ . '/_fixtures';

        $extractor = new DomainExtractor(
            new POTFileDumper(),
            new GettextExtractor(
                new \Mustache_Parser(),
                new \Mustache_Tokenizer(),
                new GettextCollector(new GettextSectionContentTransformer())
            )
        );
        $extractor->extract('unknown', $sources, $destination_template);

        $content = file_get_contents($destination_template);
        $this->assertEqual('', $content);
    }
}
