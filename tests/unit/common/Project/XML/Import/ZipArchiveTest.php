<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

namespace Tuleap\Project\XML\Import;

use Tuleap\TemporaryTestDirectory;

final class ZipArchiveTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use TemporaryTestDirectory;

    private const FIXTURES_DIR = __DIR__ . '/_fixtures';

    private string $tmp_dir;

    private ZipArchive $archive;

    protected function setUp(): void
    {
        $this->tmp_dir = $this->getTmpDir();

        $this->archive = new ZipArchive(
            self::FIXTURES_DIR . '/archive.zip',
            $this->tmp_dir
        );
    }

    protected function tearDown(): void
    {
        $this->archive->cleanUp();
        parent::tearDown();
    }

    public function testItGivesTheXMLFile(): void
    {
        $expected = file_get_contents(self::FIXTURES_DIR . '/project.xml');
        self::assertEquals($expected, $this->archive->getProjectXML());
    }

    public function testItExtractAttachmentsIntoARandomTemporaryDirectory(): void
    {
        $extraction_path = $this->archive->getExtractionPath();
        self::assertDirectoryExists($extraction_path);

        $expected_prefix = $this->tmp_dir . '/import_project_';
        self::assertMatchesRegularExpression('%' . $expected_prefix . '\w+%', $extraction_path);

        $this->archive->extractFiles();

        $expected  = file_get_contents(self::FIXTURES_DIR . '/data/Artifact69');
        $extracted = file_get_contents($extraction_path . '/data/Artifact69');

        self::assertEquals($expected, $extracted);
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
