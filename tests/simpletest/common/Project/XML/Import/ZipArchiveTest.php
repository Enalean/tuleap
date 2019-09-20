<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

namespace Tuleap\Project\XML\Import;

class ZipArchiveTest extends \TuleapTestCase
{

    /** @var Project */
    private $project;

    /** @var int */
    private $project_id;

    /** @var \ZipArchive */
    private $zip;

    /** @var string */
    private $fixtures_dir;

    /** @var string */
    private $tmp_dir;

    /** @var ProjectXMLImporter_XMLImportZipArchive */
    private $archive;

    public function setUp()
    {
        parent::setUp();
        $this->tmp_dir      = '/var/tmp';
        $this->fixtures_dir = dirname(__FILE__) .'/_fixtures';

        $this->project_id   = getmypid();

        $this->archive = new ZipArchive(
            $this->fixtures_dir .'/archive.zip',
            $this->tmp_dir
        );
    }

    public function tearDown()
    {
        $this->archive->cleanUp();
        parent::tearDown();
    }

    public function itGivesTheXMLFile()
    {
        $expected = file_get_contents($this->fixtures_dir .'/project.xml');
        $this->assertEqual($expected, $this->archive->getProjectXML());
    }

    public function itExtractAttachmentsIntoARandomTemporaryDirectory()
    {
        $extraction_path = $this->archive->getExtractionPath();
        $this->assertTrue(is_dir($extraction_path));

        $expected_prefix = $this->tmp_dir .'/import_project_';
        $this->assertPattern('%'. $expected_prefix .'\w+%', $extraction_path);

        $this->archive->extractFiles();

        $expected  = file_get_contents($this->fixtures_dir .'/data/Artifact69');
        $extracted = file_get_contents($extraction_path .'/data/Artifact69');

        $this->assertEqual($extracted, $expected);
    }

    public function itEnsuresThatTemporaryDirectoryIsNotReadableByEveryone()
    {
        $extraction_path = $this->archive->getExtractionPath();
        $perms = fileperms($extraction_path) & 0777;
        $this->assertEqual(0700, $perms);
    }

    public function itCleansUp()
    {
        $extraction_path = $this->archive->getExtractionPath();
        $this->archive->extractFiles();
        $this->archive->cleanUp();
        $this->assertFalse(file_exists($extraction_path));
    }
}
