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
 */

declare(strict_types=1);

namespace Tuleap\Docman\XML\Import;

use Docman_FileStorage;
use Docman_Item;
use Docman_Version;
use Docman_VersionFactory;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use SimpleXMLElement;

class VersionImporterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Docman_VersionFactory|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $version_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Docman_FileStorage
     */
    private $docman_file_storage;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Project
     */
    private $project;
    /**
     * @var string
     */
    private $extraction_path;
    /**
     * @var VersionImporter
     */
    private $importer;
    /**
     * @var SimpleXMLElement
     */
    private $node;
    /**
     * @var Docman_Item|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $item;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $user;

    protected function setUp(): void
    {
        $this->version_factory     = Mockery::mock(Docman_VersionFactory::class);
        $this->docman_file_storage = Mockery::mock(Docman_FileStorage::class);
        $this->project             = Mockery::mock(Project::class)->shouldReceive(['getGroupId' => 114])->getMock();
        $this->extraction_path     = '/path/to/extracted/archive';

        $this->node = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <version>
                <filename>Pan-Pan-Artwork1.png</filename>
                <filetype>image/png</filetype>
                <filesize>799789</filesize>
                <content>documents/content-214.bin</content>
            </version>
            EOS
        );

        $this->item = Mockery::mock(Docman_Item::class)->shouldReceive(['getId' => 13])->getMock();
        $this->user = Mockery::mock(PFUser::class)->shouldReceive(['getId' => 101])->getMock();

        $this->importer = new VersionImporter(
            $this->version_factory,
            $this->docman_file_storage,
            $this->project,
            $this->extraction_path
        );
    }

    public function testItRaisesExceptionWhenFileCannotBeCopiedOnFilesystem(): void
    {
        $this->docman_file_storage
            ->shouldReceive('copy')
            ->with(
                $this->extraction_path . '/documents/content-214.bin',
                'Pan-Pan-Artwork1.png',
                114,
                13,
                1
            )->once()
            ->andReturnFalse();

        $this->expectException(UnableToCreateFileOnFilesystemException::class);

        $this->importer->import($this->node, $this->item, $this->user, 1);
    }

    public function testItRaisesExceptionWhenVersionCannotBeCreatedInDb(): void
    {
        $root         = vfsStream::setup();
        $created_file = vfsStream::newFile('file.png')->at($root);
        $file_path    = $created_file->url();
        $this->assertFileExists($file_path);

        $this->docman_file_storage
            ->shouldReceive('copy')
            ->with(
                $this->extraction_path . '/documents/content-214.bin',
                'Pan-Pan-Artwork1.png',
                114,
                13,
                1
            )->once()
            ->andReturn($file_path);

        $this->version_factory
            ->shouldReceive('create')
            ->with(
                [
                    'item_id'  => 13,
                    'number'   => 1,
                    'user_id'  => 101,
                    'filename' => 'Pan-Pan-Artwork1.png',
                    'filesize' => 799789,
                    'filetype' => 'image/png',
                    'path'     => $file_path,
                    'date'     => (new \DateTimeImmutable)->getTimestamp(),
                ]
            )->once()
            ->andReturnFalse();

        $exception_caught = false;
        try {
            $this->importer->import($this->node, $this->item, $this->user, 1);
        } catch (UnableToCreateVersionInDbException $exception) {
            $exception_caught = true;
            $this->assertFileNotExists($file_path);
        }
        $this->assertTrue($exception_caught);
    }

    public function testSuccessfulImport(): void
    {
        $root         = vfsStream::setup();
        $created_file = vfsStream::newFile('file.png')->at($root);
        $file_path    = $created_file->url();
        $this->assertFileExists($file_path);

        $this->docman_file_storage
            ->shouldReceive('copy')
            ->with(
                $this->extraction_path . '/documents/content-214.bin',
                'Pan-Pan-Artwork1.png',
                114,
                13,
                1
            )->once()
            ->andReturn($file_path);

        $this->version_factory
            ->shouldReceive('create')
            ->with(
                [
                    'item_id'  => 13,
                    'number'   => 1,
                    'user_id'  => 101,
                    'filename' => 'Pan-Pan-Artwork1.png',
                    'filesize' => 799789,
                    'filetype' => 'image/png',
                    'path'     => $file_path,
                    'date'     => (new \DateTimeImmutable)->getTimestamp(),
                ]
            )->once()
            ->andReturn(Mockery::mock(Docman_Version::class));

        $this->importer->import($this->node, $this->item, $this->user, 1);
        $this->assertFileExists($file_path);
    }
}
