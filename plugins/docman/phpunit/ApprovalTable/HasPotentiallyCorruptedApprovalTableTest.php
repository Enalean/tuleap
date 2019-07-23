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
 */

declare(strict_types=1);

namespace Tuleap\Docman\ApprovalTable;

use Docman_ApprovalTable;
use Docman_ApprovalTableFileDao;
use Docman_EmbeddedFile;
use Docman_File;
use Docman_Folder;
use Docman_Link;
use Docman_LinkVersion;
use Docman_LinkVersionFactory;
use Docman_Version;
use Docman_Wiki;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use TestHelper;

final class HasPotentiallyCorruptedApprovalTableTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testFolderApprovalTableIsNeverBeCorrupted() : void
    {
        $visitor = new HasPotentiallyCorruptedApprovalTable(
            Mockery::mock(Docman_ApprovalTableFileDao::class),
            Mockery::mock(Docman_LinkVersionFactory::class)
        );

        $folder = new Docman_Folder();
        $this->assertFalse($folder->accept($visitor));
    }

    public function testWikiApprovalTableIsNeverBeCorrupted() : void
    {
        $visitor = new HasPotentiallyCorruptedApprovalTable(
            Mockery::mock(Docman_ApprovalTableFileDao::class),
            Mockery::mock(Docman_LinkVersionFactory::class)
        );

        $wiki = new Docman_Wiki();
        $this->assertFalse($wiki->accept($visitor));
    }

    /**
     * @dataProvider dataProviderFileClasses
     */
    public function testFileApprovalTableIsCorruptedIfTheTableExistAndIndicatesItMightBeCorrupted(string $class_file) : void
    {
        $visitor = new HasPotentiallyCorruptedApprovalTable(
            Mockery::mock(Docman_ApprovalTableFileDao::class),
            Mockery::mock(Docman_LinkVersionFactory::class)
        );

        $approval_table = Mockery::mock(Docman_ApprovalTable::class);
        $approval_table->shouldReceive('isPotentiallyCorrupted')->andReturn(true);

        $file_document = new $class_file();
        $this->assertTrue($file_document->accept($visitor, ['approval_table' => $approval_table]));
    }

    /**
     * @dataProvider dataProviderFileClasses
     */
    public function testFileApprovalTableCannotBeCorruptedWhenTheTableDoesNotExist(string $class_file) : void
    {
        $visitor = new HasPotentiallyCorruptedApprovalTable(
            Mockery::mock(Docman_ApprovalTableFileDao::class),
            Mockery::mock(Docman_LinkVersionFactory::class)
        );

        $file_document = new $class_file();
        $this->assertFalse($file_document->accept($visitor, ['approval_table' => null]));
    }

    /**
     * @dataProvider dataProviderFileClasses
     */
    public function testFileApprovalTableIsNotCorruptedWhenTheTableIsNotMarkedAsCorrupted(string $class_file) : void
    {
        $visitor = new HasPotentiallyCorruptedApprovalTable(
            Mockery::mock(Docman_ApprovalTableFileDao::class),
            Mockery::mock(Docman_LinkVersionFactory::class)
        );

        $approval_table = Mockery::mock(Docman_ApprovalTable::class);
        $approval_table->shouldReceive('isPotentiallyCorrupted')->andReturn(false);

        $file_document = new $class_file();
        $this->assertFalse($file_document->accept($visitor, ['approval_table' => $approval_table]));
    }

    public function dataProviderFileClasses() : array
    {
        return [
            [Docman_File::class],
            [Docman_EmbeddedFile::class]
        ];
    }

    public function testLinkApprovalTableCannotBeCorruptedIfWeFindARecentTableForTheLink() : void
    {
        $visitor = new HasPotentiallyCorruptedApprovalTable(
            Mockery::mock(Docman_ApprovalTableFileDao::class),
            Mockery::mock(Docman_LinkVersionFactory::class)
        );

        $link_document = new Docman_Link();
        $this->assertFalse($link_document->accept(
            $visitor,
            ['approval_table' => Mockery::mock(Docman_ApprovalTable::class)]
        ));
    }

    public function testLinkApprovalTableMightBeCorruptedIfWeFindACorruptedFileApprovalTableForTheSameVersion() : void
    {
        $approval_file_dao    = Mockery::mock(Docman_ApprovalTableFileDao::class);
        $link_version_factory = Mockery::mock(Docman_LinkVersionFactory::class);
        $visitor              = new HasPotentiallyCorruptedApprovalTable($approval_file_dao, $link_version_factory);

        $version = Mockery::mock(Docman_Version::class);
        $version->shouldReceive('getId')->andReturn(741);
        $link_version_factory->shouldReceive('getSpecificVersion')->andReturn($version);

        $approval_file_dao->shouldReceive('getTableById')
            ->andReturn(TestHelper::arrayToDar(['might_be_corrupted' => 1]));

        $link_document = new Docman_Link();
        $this->assertTrue($link_document->accept(
            $visitor,
            ['approval_table' => null, 'version_number' => 4]
        ));
    }

    public function testLinkApprovalTableIsNotCorruptedIfWeFindANonCorruptedFileApprovalTableForTheSameVersion() : void
    {
        $approval_file_dao    = Mockery::mock(Docman_ApprovalTableFileDao::class);
        $link_version_factory = Mockery::mock(Docman_LinkVersionFactory::class);
        $visitor              = new HasPotentiallyCorruptedApprovalTable($approval_file_dao, $link_version_factory);

        $link_document = new Docman_Link();

        $version = Mockery::mock(Docman_LinkVersion::class);
        $version->shouldReceive('getId')->andReturn(741);
        $link_document->setCurrentVersion($version);

        $approval_file_dao->shouldReceive('getTableById')
            ->andReturn(TestHelper::arrayToDar(['might_be_corrupted' => 0]));

        $link_document = new Docman_Link();
        $this->assertFalse($link_document->accept(
            $visitor,
            ['approval_table' => null, 'version_number' => null]
        ));
    }
}
