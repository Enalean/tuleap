<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\Templating\Mustache;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Tuleap\Language\Gettext\POTFileDumper;

final class DomainExtractorTest extends TestCase
{
    /**
     * @var string
     */
    private $tmp_dir;

    protected function setUp(): void
    {
        $this->tmp_dir = vfsStream::setup()->url();
    }

    public function testItRecursivelyFindsAllStringsForDefaultDomain(): void
    {
        $destination_template = $this->tmp_dir . '/template.pot';
        $sources              = __DIR__ . '/_fixtures';

        $extractor = new DomainExtractor(
            new POTFileDumper(),
            new GettextExtractor(
                new \Mustache_Parser(),
                new \Mustache_Tokenizer(),
                new GettextCollector(new GettextSectionContentTransformer())
            )
        );
        $extractor->extract(GettextCollector::DEFAULT_DOMAIN, [$sources], $destination_template);

        $content = file_get_contents($destination_template);
        $this->assertStringContainsString('foo in tuleap-core', $content);
        $this->assertStringContainsString('bar in tuleap-core', $content);
        $this->assertStringNotContainsString('foo in mydomain', $content);
        $this->assertStringNotContainsString('bar in mydomain', $content);
        $this->assertStringNotContainsString('foo in .php', $content);
    }

    public function testItRecursivelyFindsAllStringsForMyDomain(): void
    {
        $destination_template = $this->tmp_dir . '/template.pot';
        $sources              = __DIR__ . '/_fixtures';

        $extractor = new DomainExtractor(
            new POTFileDumper(),
            new GettextExtractor(
                new \Mustache_Parser(),
                new \Mustache_Tokenizer(),
                new GettextCollector(new GettextSectionContentTransformer())
            )
        );
        $extractor->extract('mydomain', [$sources], $destination_template);

        $content = file_get_contents($destination_template);
        $this->assertStringNotContainsString('foo in tuleap-core', $content);
        $this->assertStringNotContainsString('bar in tuleap-core', $content);
        $this->assertStringContainsString('foo in mydomain', $content);
        $this->assertStringContainsString('bar in mydomain', $content);
        $this->assertStringNotContainsString('foo in .php', $content);
    }

    public function testItFindsNothingIfSourcesFolderDoesNotExist(): void
    {
        $destination_template = $this->tmp_dir . '/template.pot';
        $sources              = '/donotexist';

        $extractor = new DomainExtractor(
            new POTFileDumper(),
            new GettextExtractor(
                new \Mustache_Parser(),
                new \Mustache_Tokenizer(),
                new GettextCollector(new GettextSectionContentTransformer())
            )
        );
        $extractor->extract('mydomain', [$sources], $destination_template);

        $content = file_get_contents($destination_template);
        $this->assertSame('', $content);
    }

    public function testItFindsNothingForUnknownDomain(): void
    {
        $destination_template = $this->tmp_dir . '/template.pot';
        $sources              = __DIR__ . '/_fixtures';

        $extractor = new DomainExtractor(
            new POTFileDumper(),
            new GettextExtractor(
                new \Mustache_Parser(),
                new \Mustache_Tokenizer(),
                new GettextCollector(new GettextSectionContentTransformer())
            )
        );
        $extractor->extract('unknown', [$sources], $destination_template);

        $content = file_get_contents($destination_template);
        $this->assertSame('', $content);
    }

    public function testItCollectStringsInTwoDirectories(): void
    {
        $destination_template = $this->tmp_dir . '/template.pot';

        $extractor = new DomainExtractor(
            new POTFileDumper(),
            new GettextExtractor(
                new \Mustache_Parser(),
                new \Mustache_Tokenizer(),
                new GettextCollector(new GettextSectionContentTransformer())
            )
        );
        $extractor->extract(
            GettextCollector::DEFAULT_DOMAIN,
            [
                __DIR__ . '/_fixtures/foo/bar',
                __DIR__ . '/_fixtures/baz',
            ],
            $destination_template
        );

        $content = file_get_contents($destination_template);
        $this->assertStringNotContainsString('foo in tuleap-core', $content);
        $this->assertStringContainsString('bar in tuleap-core', $content);
        $this->assertStringContainsString('baz in tuleap-core', $content);
    }
}
