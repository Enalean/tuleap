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

namespace Tests\Tuleap\Docman\Version;

use Docman_Empty;
use Docman_File;
use Docman_Folder;
use Docman_Item;
use Docman_VersionFactory;
use Docman_LinkVersionFactory;
use LogicException;
use Override;
use PHPUnit\Framework\TestCase;
use Tuleap\Docman\Builders\DocmanFileTestBuilder;
use Tuleap\Docman\Builders\DocmanLinkTestBuilder;
use Tuleap\Docman\Builders\DocmanLinkVersionBuilder;
use Tuleap\Docman\Builders\FileVersionBuilder;
use Tuleap\Docman\Version\VersionRetrieverFromApprovalTableVisitor;
use Tuleap\Test\Builders\DocmanEmbeddedFileTestBuilder;
use WikiPageVersionFactory;
use WikiVersionDao;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class VersionRetrieverVisitorTest extends TestCase
{
    private Docman_VersionFactory&\PHPUnit\Framework\MockObject\MockObject $version_factory;
    private Docman_LinkVersionFactory&\PHPUnit\Framework\MockObject\MockObject $link_version_factory;
    private WikiVersionDao&\PHPUnit\Framework\MockObject\MockObject $wiki_version_dao;
    private WikiPageVersionFactory&\PHPUnit\Framework\MockObject\MockObject $wiki_version_factory;

    private VersionRetrieverFromApprovalTableVisitor $visitor;

    #[Override]
    protected function setUp(): void
    {
        $this->version_factory      = $this->createMock(Docman_VersionFactory::class);
        $this->link_version_factory = $this->createMock(Docman_LinkVersionFactory::class);
        $this->wiki_version_dao     = $this->createMock(WikiVersionDao::class);
        $this->wiki_version_factory = $this->createMock(WikiPageVersionFactory::class);

        $this->visitor = new VersionRetrieverFromApprovalTableVisitor(
            $this->version_factory,
            $this->link_version_factory,
            $this->wiki_version_dao,
            $this->wiki_version_factory
        );
    }

    public function testItReturnsTheFileVersion(): void
    {
        $file    = DocmanFileTestBuilder::aFile()->build();
        $version = FileVersionBuilder::aFileVersion()->build();

        $this->version_factory
            ->expects($this->once())
            ->method('getSpecificVersion')
            ->willReturn($version);

        $result = $this->visitor->visitFile($file, ['approval_table_version_number' => $version->getId()]);

        self::assertSame($version, $result);
    }

    public function testVisitFileThrowsWhenParameterIsNotProvided(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Version number not provided for file');

        $file = $this->createMock(Docman_File::class);
        $this->visitor->visitFile($file);
    }

    public function testVisitFileThrowsWhenVersionIsNotFoundInDB(): void
    {
        $file = $this->createMock(Docman_File::class);

        $this->version_factory
            ->expects($this->once())
            ->method('getSpecificVersion')
            ->willReturn(null);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('File does not have a version');

        $this->visitor->visitFile($file, ['approval_table_version_number' => 22]);
    }

    public function testVisitEmbeddedFileReturnsVersion(): void
    {
        $file    = DocmanEmbeddedFileTestBuilder::anEmbeddedFile()->build();
        $version = FileVersionBuilder::aFileVersion()->build();

        $this->version_factory
            ->expects($this->once())
            ->method('getSpecificVersion')
            ->willReturn($version);

        $result = $this->visitor->visitEmbeddedFile($file, ['approval_table_version_number' => $version->getId()]);

        self::assertSame($version, $result);
    }

    public function testVisitLinkReturnsVersion(): void
    {
        $link    = DocmanLinkTestBuilder::aLink()->build();
        $version = DocmanLinkVersionBuilder::aLinkVersion()->build();

        $this->link_version_factory
            ->expects($this->once())
            ->method('getSpecificVersion')
            ->willReturn($version);

        $result = $this->visitor->visitLink($link, ['approval_table_version_number' => $version->getId()]);

        self::assertSame($version, $result);
    }

    public function testVisitFolderThrows(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Folder are not versioned');

        $folder = $this->createMock(Docman_Folder::class);
        $this->visitor->visitFolder($folder);
    }

    public function testVisitEmptyThrows(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Empty are not versioned');

        $empty = $this->createMock(Docman_Empty::class);
        $this->visitor->visitEmpty($empty);
    }

    public function testVisitItemThrows(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Item without type are not versioned');

        $item = $this->createMock(Docman_Item::class);
        $this->visitor->visitItem($item);
    }
}
