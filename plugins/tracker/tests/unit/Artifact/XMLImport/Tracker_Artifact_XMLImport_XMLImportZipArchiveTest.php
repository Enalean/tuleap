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

namespace Tuleap\Tracker\Artifact\XMLImport;

use Tracker_Artifact_XMLImport_XMLImportZipArchive;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use ZipArchive;

// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
final class Tracker_Artifact_XMLImport_XMLImportZipArchiveTest extends TestCase
{
    use TemporaryTestDirectory;

    private int $tracker_id;
    private ZipArchive $zip;
    private string $fixtures_dir;
    private string $tmp_dir;
    private Tracker_Artifact_XMLImport_XMLImportZipArchive $archive;

    protected function setUp(): void
    {
        $this->tmp_dir      = $this->getTmpDir();
        $this->fixtures_dir = __DIR__ . '/_fixtures';
        $this->tracker_id   = 1;
        $this->zip          = new ZipArchive();
        if ($this->zip->open($this->fixtures_dir . '/archive.zip') !== true) {
            $this->fail('unable to open fixture archive.zip');
        }

        $this->archive = new Tracker_Artifact_XMLImport_XMLImportZipArchive(
            TrackerTestBuilder::aTracker()->withId($this->tracker_id)->build(),
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
        self::assertXmlStringEqualsXmlFile($this->fixtures_dir . '/artifacts.xml', $this->archive->getXML());
    }

    public function testItExtractAttachmentsIntoARandomTemporaryDirectory(): void
    {
        $extraction_path = $this->archive->getExtractionPath();
        self::assertDirectoryExists($extraction_path);

        $expected_prefix = $this->tmp_dir . '/import_tv5_' . $this->tracker_id . '_';
        self::assertStringStartsWith($expected_prefix, $extraction_path);

        $this->archive->extractFiles();
        self::assertFileEquals($this->fixtures_dir . '/data/123/file.txt', $extraction_path . '/data/123/file.txt');
    }

    public function testItEnsuresThatTemporaryDirectoryIsNotReadableByEveryone(): void
    {
        $extraction_path = $this->archive->getExtractionPath();
        $perms           = fileperms($extraction_path) & 0777;
        self::assertEquals(0700, $perms);
    }

    public function testItCleansUp(): void
    {
        $extraction_path = $this->archive->getExtractionPath();
        $this->archive->extractFiles();
        $this->archive->cleanUp();
        self::assertFileDoesNotExist($extraction_path);
    }
}
