<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\TemporaryTestDirectory;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class Tracker_Artifact_XMLImport_XMLImportZipArchiveTest extends \PHPUnit\Framework\TestCase
{
    use MockeryPHPUnitIntegration;
    use TemporaryTestDirectory;

    /** @var Tracker */
    private $tracker;
    /**
     * @var int
     */
    private $tracker_id;

    /** @var ZipArchive */
    private $zip;

    /** @var string */
    private $fixtures_dir;

    /** @var string */
    private $tmp_dir;

    /** @var Tracker_Artifact_XMLImport_XMLImportZipArchive */
    private $archive;

    protected function setUp(): void
    {
        $this->tmp_dir      =  $this->getTmpDir();
        $this->fixtures_dir = __DIR__ . '/_fixtures';
        $this->tracker_id   = 1;
        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(1);
        $this->tracker = $tracker;

        $this->zip = new ZipArchive();
        if ($this->zip->open($this->fixtures_dir . '/archive.zip') !== true) {
            $this->fail('unable to open fixture archive.zip');
        }

        $this->archive = new Tracker_Artifact_XMLImport_XMLImportZipArchive(
            $this->tracker,
            $this->zip,
            $this->tmp_dir
        );
    }

    protected function tearDown(): void
    {
        $this->zip->close();
    }

    public function testItGivesTheXMLFile(): void
    {
        $this->assertXmlStringEqualsXmlFile($this->fixtures_dir . '/artifacts.xml', $this->archive->getXML());
    }

    public function testItExtractAttachmentsIntoARandomTemporaryDirectory(): void
    {
        $extraction_path = $this->archive->getExtractionPath();
        $this->assertDirectoryExists($extraction_path);

        $expected_prefix = $this->tmp_dir . '/import_tv5_' . $this->tracker_id . '_';
        $this->assertStringStartsWith($expected_prefix, $extraction_path);

        $this->archive->extractFiles();
        $this->assertFileEquals($this->fixtures_dir . '/data/123/file.txt', $extraction_path . '/data/123/file.txt');
    }

    public function testItEnsuresThatTemporaryDirectoryIsNotReadableByEveryone(): void
    {
        $extraction_path = $this->archive->getExtractionPath();
        $perms = fileperms($extraction_path) & 0777;
        $this->assertEquals(0700, $perms);
    }

    public function testItCleansUp(): void
    {
        $extraction_path = $this->archive->getExtractionPath();
        $this->archive->extractFiles();
        $this->archive->cleanUp();
        $this->assertFileDoesNotExist($extraction_path);
    }
}
