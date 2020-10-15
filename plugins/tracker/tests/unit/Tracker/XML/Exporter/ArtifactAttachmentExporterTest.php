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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
final class ArtifactAttachmentExporterTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use \Tuleap\TemporaryTestDirectory;

    /** @var ZipArchive */
    private $archive;

    /** @var string */
    private $archive_path;

    /** @var string */
    private $file01_path;

    /** @var string */
    private $extraction_path;

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
        $tracker    = Mockery::spy(Tracker::class);
        $file_field = \Mockery::spy(\Tracker_FormElement_Field_File::class);
        $file_info  = \Mockery::spy(\Tracker_FileInfo::class)->shouldReceive('getPath')->andReturns($this->file01_path)->getMock();
        $file_info->shouldReceive('getId')->andReturns(1);

        $files      = [$file_info];
        $changeset  = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $file_value = new Tracker_Artifact_ChangesetValue_File(1, $changeset, $file_field, 1, $files);
        $changeset->shouldReceive('getValue')->with($file_field)->andReturns($file_value);

        $artifact = Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class)->shouldReceive('getTracker')->andReturns($tracker)->getMock();
        $artifact->shouldReceive('getLastChangeset')->andReturns($changeset);

        $form_element_factory = Mockery::spy(\Tracker_FormElementFactory::class)->shouldReceive('getUsedFileFields')->with($tracker)->andReturns([$file_field])->getMock();

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
