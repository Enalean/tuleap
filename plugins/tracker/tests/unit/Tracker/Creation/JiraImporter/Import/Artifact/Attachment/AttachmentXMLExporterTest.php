<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use XML_SimpleXMLCDATAFactory;

class AttachmentXMLExporterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItExportsAttachmentFilesInXML(): void
    {
        $downloader = Mockery::mock(AttachmentDownloader::class);

        $exporter = new AttachmentXMLExporter(
            $downloader,
            new XML_SimpleXMLCDATAFactory()
        );

        $attachment_collection = new AttachmentCollection(
            [
                new Attachment(
                    10007,
                    "file01.png",
                    "image/png",
                    "URL1",
                    30
                ),
                new Attachment(
                    10008,
                    "file02.gif",
                    "image/gif",
                    "URL2",
                    1234
                )
            ]
        );

        $artifact_node = new SimpleXMLElement('<artifacts/>');

        $downloader->shouldReceive('downloadAttachment')->andReturn(
            'file0123',
            'file5678'
        );

        $exporter->exportCollectionOfAttachmentInXML(
            $attachment_collection,
            $artifact_node
        );

        $this->assertCount(2, $artifact_node->children());
        $exported_file_01 = $artifact_node->file[0];
        $this->assertSame("fileinfo_10007", (string) $exported_file_01['id']);
        $this->assertSame("file01.png", (string) $exported_file_01->filename);
        $this->assertSame("file0123", (string) $exported_file_01->path);

        $exported_file_02 = $artifact_node->file[1];
        $this->assertSame("fileinfo_10008", (string) $exported_file_02['id']);
        $this->assertSame("file02.gif", (string) $exported_file_02->filename);
        $this->assertSame("file5678", (string) $exported_file_02->path);
    }
}
