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

use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueFileTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\FileFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactAttachmentExporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use \Tuleap\TemporaryTestDirectory;

    private \Tuleap\Project\XML\Export\ZipArchive $archive;

    private string $archive_path;

    private string $file01_path;

    private string $extraction_path;

    protected function setUp(): void
    {
        $this->archive_path    = $this->getTmpDir() . '/test.zip';
        $this->file01_path     = __DIR__ . '/_fixtures/file01.txt';
        $this->extraction_path = $this->getTmpDir() . '/extraction';
        mkdir($this->extraction_path);

        $this->initArchive();
    }

    public function testItAddsFileIntoArchive(): void
    {
        $tracker    = TrackerTestBuilder::aTracker()->build();
        $file_field = FileFieldBuilder::aFileField(1001)->build();
        $file_info  = $this->createMock(\Tracker_FileInfo::class);
        $file_info->method('getPath')->willReturn($this->file01_path);
        $file_info->method('getId')->willReturn(1);

        $files     = [$file_info];
        $changeset = ChangesetTestBuilder::aChangeset(101)->build();

        $file_value = ChangesetValueFileTestBuilder::aValue(1, $changeset, $file_field)->withFiles($files)->build();
        $changeset->setFieldValue($file_field, $file_value);


        $artifact = ArtifactTestBuilder::anArtifact(101)->inTracker($tracker)->withChangesets($changeset)->build();

        $form_element_factory = $this->createMock(\Tracker_FormElementFactory::class);
        $form_element_factory->method('getUsedFileFields')->with($tracker)->willReturn([$file_field]);

        $exporter = new Tracker_XML_Exporter_ArtifactAttachmentExporter($form_element_factory);

        $exporter->exportAttachmentsInArchive($artifact, $this->archive);

        $this->extractArchive();

        $this->assertStringEqualsFile($this->extraction_path . '/data/Artifact1', 'file01');
    }

    private function initArchive(): void
    {
        $this->archive = new Tuleap\Project\XML\Export\ZipArchive($this->archive_path);
    }

    private function extractArchive(): void
    {
        $this->archive->close();

        $zip = new ZipArchive();
        $zip->open($this->archive_path);
        $zip->extractTo($this->extraction_path);
        $zip->close();
    }
}
