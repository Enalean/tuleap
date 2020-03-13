<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tracker\Artifact\XMLImport;

use Logger;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use PFUser;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use Tracker_Artifact;
use Tracker_Artifact_XMLImport_CollectionOfFilesToImportInArtifact;
use Tracker_Artifact_XMLImport_Exception_NoValidAttachementsException;
use Tracker_Artifact_XMLImport_XMLImportFieldStrategyAttachment;
use Tracker_FormElement_Field;

class XMLImportFieldStrategyAttachmentTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Logger|Mockery\MockInterface
     */
    private $logger;
    /**
     * @var Mockery\MockInterface|Tracker_Artifact_XMLImport_CollectionOfFilesToImportInArtifact
     */
    private $files_importer;
    /**
     * @var Tracker_Artifact_XMLImport_XMLImportFieldStrategyAttachment
     */
    private $strategy;
    /**
     * @var Mockery\MockInterface|Tracker_FormElement_Field
     */
    private $field;
    /**
     * @var Mockery\MockInterface|PFUser
     */
    private $submitted_by;
    /**
     * @var Mockery\MockInterface|Tracker_Artifact
     */
    private $artifact;
    /**
     * @var string
     */
    private $extraction_path;

    protected function setUp(): void
    {
        $this->extraction_path = vfsStream::setup()->url() . '/tmp';
        mkdir($this->extraction_path);

        $this->logger         = Mockery::mock(\Psr\Log\LoggerInterface::class);
        $this->files_importer = Mockery::mock(Tracker_Artifact_XMLImport_CollectionOfFilesToImportInArtifact::class);

        $this->strategy = new Tracker_Artifact_XMLImport_XMLImportFieldStrategyAttachment(
            $this->extraction_path,
            $this->files_importer,
            $this->logger
        );

        $this->field = Mockery::mock(Tracker_FormElement_Field::class);
        $this->field->shouldReceive('getLabel')->andReturn('Attachments');
        $this->submitted_by = Mockery::mock(PFUser::class);
        $this->artifact     = Mockery::mock(Tracker_Artifact::class);
    }

    public function testItReturnsListOfFilesInfos(): void
    {
        touch($this->extraction_path . '/Readme.mkd');
        touch($this->extraction_path . '/Lenna.png');

        $field_change = new SimpleXMLElement(
            '<?xml version="1.0"?>
            <field_change field_name="file" type="file">
                <value ref="F123"/>
                <value ref="F456"/>
            </field_change>'
        );

        $this->files_importer
            ->shouldReceive('getFileXML')
            ->with("F123")
            ->andReturn(
                new SimpleXMLElement(
                    '<?xml version="1.0"?>
                    <file id="F123">
                        <filename>Readme.mkd</filename>
                        <path>Readme.mkd</path>
                        <filesize>1024</filesize>
                        <filetype>text/plain</filetype>
                        <description></description>
                    </file>'
                )
            );
        $this->files_importer
            ->shouldReceive('getFileXML')
            ->with("F456")
            ->andReturn(
                new SimpleXMLElement(
                    '<?xml version="1.0"?>
                    <file id="F456">
                        <filename>Lenna.png</filename>
                        <path>Lenna.png</path>
                        <filesize>2048</filesize>
                        <filetype>image/png</filetype>
                        <description></description>
                    </file>'
                )
            );
        $this->files_importer
            ->shouldReceive('fileIsAlreadyImported')
            ->with('F123')
            ->andReturn(false);
        $this->files_importer
            ->shouldReceive('fileIsAlreadyImported')
            ->with('F456')
            ->andReturn(false);

        $this->files_importer
            ->shouldReceive('markAsImported')
            ->with('F123')
            ->once();
        $this->files_importer
            ->shouldReceive('markAsImported')
            ->with('F456')
            ->once();

        $this->assertEquals(
            [
                [
                    'is_migrated'  => true,
                    'submitted_by' => $this->submitted_by,
                    'name'         => 'Readme.mkd',
                    'type'         => 'text/plain',
                    'description'  => '',
                    'size'         => 1024,
                    'tmp_name'     => $this->extraction_path . '/Readme.mkd',
                    'error'        => 0
                ],
                [
                    'is_migrated'  => true,
                    'submitted_by' => $this->submitted_by,
                    'name'         => 'Lenna.png',
                    'type'         => 'image/png',
                    'description'  => '',
                    'size'         => 2048,
                    'tmp_name'     => $this->extraction_path . '/Lenna.png',
                    'error'        => 0
                ]
            ],
            $this->strategy->getFieldData($this->field, $field_change, $this->submitted_by, $this->artifact)
        );
    }

    public function testItReturnsEmtpyArrayIfFieldChangeDoesNotHaveRefAttribute(): void
    {
        $this->logger
            ->shouldReceive('warning')
            ->with('Skipped attachment field Attachments: field value is empty.')
            ->once();

        $field_change = new SimpleXMLElement(
            '<?xml version="1.0"?>
            <field_change field_name="file" type="file">
                <value/>
            </field_change>'
        );

        $this->assertEquals(
            [],
            $this->strategy->getFieldData($this->field, $field_change, $this->submitted_by, $this->artifact)
        );
    }

    public function testItRaisesAWarningIfFileCannotBeFound(): void
    {
        touch($this->extraction_path . '/Lenna.png');

        $this->logger
            ->shouldReceive('warning')
            ->with('Skipped attachment field Attachments: File not found: ' . $this->extraction_path . '/Readme.mkd')
            ->once();

        $field_change = new SimpleXMLElement(
            '<?xml version="1.0"?>
            <field_change field_name="file" type="file">
                <value ref="F123"/>
                <value ref="F456"/>
            </field_change>'
        );

        $this->files_importer
            ->shouldReceive('getFileXML')
            ->with("F123")
            ->andReturn(
                new SimpleXMLElement(
                    '<?xml version="1.0"?>
                    <file id="F123">
                        <filename>Readme.mkd</filename>
                        <path>Readme.mkd</path>
                        <filesize>1024</filesize>
                        <filetype>text/plain</filetype>
                        <description></description>
                    </file>'
                )
            );
        $this->files_importer
            ->shouldReceive('getFileXML')
            ->with("F456")
            ->andReturn(
                new SimpleXMLElement(
                    '<?xml version="1.0"?>
                    <file id="F456">
                        <filename>Lenna.png</filename>
                        <path>Lenna.png</path>
                        <filesize>2048</filesize>
                        <filetype>image/png</filetype>
                        <description></description>
                    </file>'
                )
            );
        $this->files_importer
            ->shouldReceive('fileIsAlreadyImported')
            ->with('F123')
            ->andReturn(false);
        $this->files_importer
            ->shouldReceive('fileIsAlreadyImported')
            ->with('F456')
            ->andReturn(false);

        $this->files_importer
            ->shouldReceive('markAsImported')
            ->with('F456')
            ->once();

        $this->assertEquals(
            [
                [
                    'is_migrated'  => true,
                    'submitted_by' => $this->submitted_by,
                    'name'         => 'Lenna.png',
                    'type'         => 'image/png',
                    'description'  => '',
                    'size'         => 2048,
                    'tmp_name'     => $this->extraction_path . '/Lenna.png',
                    'error'        => 0
                ]
            ],
            $this->strategy->getFieldData($this->field, $field_change, $this->submitted_by, $this->artifact)
        );
    }

    public function testItRaisesExceptionIfNoFileCannotBeFound(): void
    {
        $this->logger
            ->shouldReceive('warning')
            ->with('Skipped attachment field Attachments: File not found: ' . $this->extraction_path . '/Readme.mkd')
            ->once();
        $this->logger
            ->shouldReceive('warning')
            ->with('Skipped attachment field Attachments: File not found: ' . $this->extraction_path . '/Lenna.png')
            ->once();

        $field_change = new SimpleXMLElement(
            '<?xml version="1.0"?>
            <field_change field_name="file" type="file">
                <value ref="F123"/>
                <value ref="F456"/>
            </field_change>'
        );

        $this->files_importer
            ->shouldReceive('getFileXML')
            ->with("F123")
            ->andReturn(
                new SimpleXMLElement(
                    '<?xml version="1.0"?>
                    <file id="F123">
                        <filename>Readme.mkd</filename>
                        <path>Readme.mkd</path>
                        <filesize>1024</filesize>
                        <filetype>text/plain</filetype>
                        <description></description>
                    </file>'
                )
            );
        $this->files_importer
            ->shouldReceive('getFileXML')
            ->with("F456")
            ->andReturn(
                new SimpleXMLElement(
                    '<?xml version="1.0"?>
                    <file id="F456">
                        <filename>Lenna.png</filename>
                        <path>Lenna.png</path>
                        <filesize>2048</filesize>
                        <filetype>image/png</filetype>
                        <description></description>
                    </file>'
                )
            );
        $this->files_importer
            ->shouldReceive('fileIsAlreadyImported')
            ->with('F123')
            ->andReturn(false);
        $this->files_importer
            ->shouldReceive('fileIsAlreadyImported')
            ->with('F456')
            ->andReturn(false);

        $this->expectException(Tracker_Artifact_XMLImport_Exception_NoValidAttachementsException::class);
        $this->strategy->getFieldData($this->field, $field_change, $this->submitted_by, $this->artifact);
    }
}
