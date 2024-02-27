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
use SimpleXMLElement;
use Tracker_Artifact_XMLImport_CollectionOfFilesToImportInArtifact;
use Tracker_Artifact_XMLImport_Exception_NoValidAttachementsException;
use Tracker_Artifact_XMLImport_XMLImportFieldStrategyAttachment;
use Tracker_FormElement_InvalidFieldException;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Action\FieldMapping;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationContext;
use Tuleap\Tracker\Artifact\XMLImport\MoveImportConfig;
use Tuleap\Tracker\Artifact\XMLImport\TrackerXmlImportConfig;
use Tuleap\Tracker\Test\Builders\Fields\FileFieldBuilder;

final class XMLImportFieldStrategyAttachmentTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

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
    private \Tracker_FormElement_Field_File $field;
    /**
     * @var Mockery\MockInterface|PFUser
     */
    private $submitted_by;
    /**
     * @var Mockery\MockInterface|Artifact
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
        \ForgeConfig::set("sys_data_dir", $this->extraction_path);

        $this->logger         = Mockery::mock(\Psr\Log\LoggerInterface::class);
        $this->files_importer = Mockery::mock(Tracker_Artifact_XMLImport_CollectionOfFilesToImportInArtifact::class);

        $this->strategy = new Tracker_Artifact_XMLImport_XMLImportFieldStrategyAttachment(
            $this->extraction_path,
            $this->files_importer,
            $this->logger
        );

        $this->field        = FileFieldBuilder::aFileField(1)->withName('Attachments')->build();
        $this->submitted_by = UserTestBuilder::anActiveUser()->build();
        $this->artifact     = Mockery::mock(Artifact::class);
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
                    <file id="fileinfo_123">
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
                    <file id="fileinfo_456">
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
                    'error'        => 0,
                    'previous_fileinfo_id' => 123,
                ],
                [
                    'is_migrated'  => true,
                    'submitted_by' => $this->submitted_by,
                    'name'         => 'Lenna.png',
                    'type'         => 'image/png',
                    'description'  => '',
                    'size'         => 2048,
                    'tmp_name'     => $this->extraction_path . '/Lenna.png',
                    'error'        => 0,
                    'previous_fileinfo_id' => 456,
                ],
            ],
            $this->strategy->getFieldData($this->field, $field_change, $this->submitted_by, $this->artifact, PostCreationContext::withNoConfig(false))
        );
    }

    public function testItReturnsEmptyArrayIfFieldChangeDoesNotHaveRefAttribute(): void
    {
        $this->logger
            ->shouldReceive('info')
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
            $this->strategy->getFieldData($this->field, $field_change, $this->submitted_by, $this->artifact, PostCreationContext::withNoConfig(false))
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
                    <file id="fileinfo_123">
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
                    <file id="fileinfo_456">
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
                    'is_migrated' => true,
                    'submitted_by' => $this->submitted_by,
                    'name' => 'Lenna.png',
                    'type' => 'image/png',
                    'description' => '',
                    'size' => 2048,
                    'tmp_name' => $this->extraction_path . '/Lenna.png',
                    'error' => 0,
                    'previous_fileinfo_id' => 456,
                ],
            ],
            $this->strategy->getFieldData(
                $this->field,
                $field_change,
                $this->submitted_by,
                $this->artifact,
                PostCreationContext::withNoConfig(false)
            )
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
                    <file id="fileinfo_123">
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
                    <file id="fileinfo_456">
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
        $this->strategy->getFieldData($this->field, $field_change, $this->submitted_by, $this->artifact, PostCreationContext::withNoConfig(false));
    }

    public function testItReturnsListOfFileInfoInMoveContext(): void
    {
        mkdir($this->extraction_path . '/tracker');
        mkdir($this->extraction_path . '/tracker/2');
        touch($this->extraction_path . '/tracker/2/123');
        touch($this->extraction_path . '/tracker/2/456');

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
                    <file id="fileinfo_123">
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
                    <file id="fileinfo_456">
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
                    'is_moved' => true,
                    'submitted_by' => $this->submitted_by,
                    'name' => 'Readme.mkd',
                    'type' => 'text/plain',
                    'description' => '',
                    'size' => 1024,
                    'tmp_name' => 'vfs://root/tmp/tracker/2/123',
                    'error' => 0,
                    'previous_fileinfo_id' => 123,
                ],
                [
                    'is_moved' => true,
                    'submitted_by' => $this->submitted_by,
                    'name' => 'Lenna.png',
                    'type' => 'image/png',
                    'description' => '',
                    'size' => 2048,
                    'tmp_name' => 'vfs://root/tmp/tracker/2/456',
                    'error' => 0,
                    'previous_fileinfo_id' => 456,
                ],
            ],
            $this->strategy->getFieldData(
                $this->field,
                $field_change,
                $this->submitted_by,
                $this->artifact,
                PostCreationContext::withConfig(
                    new TrackerXmlImportConfig(
                        $this->submitted_by,
                        new \DateTimeImmutable(),
                        MoveImportConfig::buildForMoveArtifact(true, [FieldMapping::fromFields(FileFieldBuilder::aFileField(2)->withName('Attachments')->build(), $this->field)])
                    ),
                    false
                )
            )
        );
    }

    public function testItThrowExceptionWhenSourceFieldIsNotFound(): void
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
                    <file id="fileinfo_123">
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
                    <file id="fileinfo_456">
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

        $this->expectException(Tracker_FormElement_InvalidFieldException::class);
        $this->strategy->getFieldData(
            $this->field,
            $field_change,
            $this->submitted_by,
            $this->artifact,
            PostCreationContext::withConfig(new TrackerXmlImportConfig($this->submitted_by, new \DateTimeImmutable(), MoveImportConfig::buildForMoveArtifact(true, [])), false)
        );
    }
}
