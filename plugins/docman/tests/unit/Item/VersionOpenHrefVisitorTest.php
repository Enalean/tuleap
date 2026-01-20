<?php
/*
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Docman\Item;

use LogicException;
use Tuleap\Docman\Builders\DocmanFileTestBuilder;
use Tuleap\Docman\Builders\DocmanFolderTestBuilder;
use Tuleap\Docman\Builders\DocmanLinkTestBuilder;
use Tuleap\Docman\Builders\DocmanLinkVersionBuilder;
use Tuleap\Docman\Builders\DocmanWikiTestBuilder;
use Tuleap\Test\Builders\DocmanEmbeddedFileTestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class VersionOpenHrefVisitorTest extends TestCase
{
    public function testFolderAreNotVersioned(): void
    {
        $folder  = DocmanFolderTestBuilder::aFolder()->build();
        $visitor = new VersionOpenHrefVisitor();

        $this->assertSame('', $visitor->visitFolder($folder));
    }

    public function testWikiAreNotVersioned(): void
    {
        $wiki    = DocmanWikiTestBuilder::aWiki()->build();
        $visitor = new VersionOpenHrefVisitor();

        $this->assertSame('', $visitor->visitWiki($wiki));
    }

    public function testItGeneratesCorrectHrefForLink(): void
    {
        $link = DocmanLinkTestBuilder::aLink()
            ->withId(678)
            ->withGroupId(456)
            ->build();

        $visitor = new VersionOpenHrefVisitor();
        $params  = ['version' => DocmanLinkVersionBuilder::aLinkVersion()->withNumber(123)->build()];

        $expected_url = '/plugins/docman/?' . http_build_query([
            'group_id' => 456,
            'action' => 'show',
            'id' => 678,
            'version_number' => 123,
        ]);

        $this->assertSame($expected_url, $visitor->visitLink($link, $params));
    }

    public function testItRThrowsForFileWithoutVersion(): void
    {
        $file    = DocmanFileTestBuilder::aFile()->withId(100)->build();
        $visitor = new VersionOpenHrefVisitor();

        $this->expectException(LogicException::class);
        $visitor->visitFile($file, []);
    }

    public function testItGeneratesCorrectHrefForFile(): void
    {
        $file    = DocmanFileTestBuilder::aFile()->withId(200)->build();
        $version = new \Docman_Version(['number' => 3]);
        $visitor = new VersionOpenHrefVisitor();

        $expected_url = '/plugins/docman/download/200/3';

        $this->assertSame($expected_url, $visitor->visitFile($file, ['version' => $version]));
    }

    public function testItThrowsAnExceptionForEmbeddedFileWithoutVersionOrProject(): void
    {
        $embedded_file = DocmanEmbeddedFileTestBuilder::anEmbeddedFile()->withId(300)->build();
        $visitor       = new VersionOpenHrefVisitor();

        $this->expectException(LogicException::class);
        $visitor->visitEmbeddedFile($embedded_file, []);
    }

    public function testItGeneratesCorrectHrefForEmbeddedFile(): void
    {
        $project       = ProjectTestBuilder::aProject()->withUnixName('unix_project')->build();
        $embedded_file = DocmanEmbeddedFileTestBuilder::anEmbeddedFile()
            ->withId(400)
            ->withParentId(500)
            ->build();

        $version = new \Docman_Version(['id' => 600]);
        $visitor = new VersionOpenHrefVisitor();

        $expected_url = '/plugins/document/unix_project/folder/500/400/600';

        $this->assertSame($expected_url, $visitor->visitEmbeddedFile($embedded_file, [
            'version' => $version,
            'project' => $project,
        ]));
    }

    public function testEmptyAreNotVersioned(): void
    {
        $visitor = new VersionOpenHrefVisitor();

        $this->assertSame('', $visitor->visitEmpty($this->createMock(\Docman_Empty::class)));
    }

    public function testOtherItemsAreNotVersioned(): void
    {
        $visitor = new VersionOpenHrefVisitor();

        $this->assertSame('', $visitor->visitItem($this->createMock(\Docman_Item::class)));
    }
}
