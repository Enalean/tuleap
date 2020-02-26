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
use Tuleap\xml\InvalidDateException;
use User\XML\Import\IFindUserFromXMLReference;

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
    /**
     * @var \DateTimeImmutable
     */
    private $current_date;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|IFindUserFromXMLReference
     */
    private $user_finder;

    protected function setUp(): void
    {
        $this->version_factory     = Mockery::mock(Docman_VersionFactory::class);
        $this->docman_file_storage = Mockery::mock(Docman_FileStorage::class);
        $this->project             = Mockery::mock(Project::class)->shouldReceive(['getGroupId' => 114])->getMock();
        $this->extraction_path     = '/path/to/extracted/archive';
        $this->current_date        = new \DateTimeImmutable();
        $this->user_finder         = Mockery::mock(IFindUserFromXMLReference::class);

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
            $this->user_finder,
            $this->version_factory,
            $this->docman_file_storage,
            $this->project,
            $this->extraction_path,
            $this->current_date,
            $this->user
        );
    }

    public function testItRaisesExceptionWhenDateIsInvalid(): void
    {
        $node = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <version>
                <filename>Pan-Pan-Artwork1.png</filename>
                <filetype>image/png</filetype>
                <filesize>799789</filesize>
                <date format="ISO8601">invalid</date>
                <content>documents/content-214.bin</content>
            </version>
            EOS
        );

        $this->docman_file_storage
            ->shouldReceive('copy')
            ->with(
                $this->extraction_path . '/documents/content-214.bin',
                'Pan-Pan-Artwork1.png',
                114,
                13,
                1
            )->never();

        $this->expectException(InvalidDateException::class);

        $this->importer->import($node, $this->item, 1);
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

        $this->importer->import($this->node, $this->item, 1);
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
                    'item_id'   => 13,
                    'number'    => 1,
                    'user_id'   => 101,
                    'filename'  => 'Pan-Pan-Artwork1.png',
                    'filesize'  => 799789,
                    'filetype'  => 'image/png',
                    'path'      => $file_path,
                    'date'      => $this->current_date->getTimestamp(),
                    'label'     => '',
                    'changelog' => ''
                ]
            )->once()
            ->andReturnFalse();

        $exception_caught = false;
        try {
            $this->importer->import($this->node, $this->item, 1);
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
                    'item_id'   => 13,
                    'number'    => 1,
                    'user_id'   => 101,
                    'filename'  => 'Pan-Pan-Artwork1.png',
                    'filesize'  => 799789,
                    'filetype'  => 'image/png',
                    'path'      => $file_path,
                    'date'      => $this->current_date->getTimestamp(),
                    'label'     => '',
                    'changelog' => ''
                ]
            )->once()
            ->andReturn(Mockery::mock(Docman_Version::class));

        $this->importer->import($this->node, $this->item, 1);
        $this->assertFileExists($file_path);
    }

    public function testSuccessfulImportWithGivenUser(): void
    {
        $root         = vfsStream::setup();
        $created_file = vfsStream::newFile('file.png')->at($root);
        $file_path    = $created_file->url();
        $this->assertFileExists($file_path);

        $this->docman_file_storage
            ->shouldReceive('copy')
            ->andReturn($file_path);

        $this->version_factory
            ->shouldReceive('create')
            ->with(
                [
                    'item_id'   => 13,
                    'number'    => 1,
                    'user_id'   => 103,
                    'filename'  => 'Pan-Pan-Artwork1.png',
                    'filesize'  => 799789,
                    'filetype'  => 'image/png',
                    'path'      => $file_path,
                    'date'      => $this->current_date->getTimestamp(),
                    'label'     => '',
                    'changelog' => ''
                ]
            )->once()
            ->andReturn(Mockery::mock(Docman_Version::class));

        $this->user_finder
            ->shouldReceive('getUser')
            ->once()
            ->andReturn(Mockery::mock(PFUser::class)->shouldReceive(['getId' => 103])->getMock());

        $node = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <version>
                <filename>Pan-Pan-Artwork1.png</filename>
                <filetype>image/png</filetype>
                <filesize>799789</filesize>
                <author format="ldap">103</author>
                <content>documents/content-214.bin</content>
            </version>
            EOS
        );

        $this->importer->import($node, $this->item, 1);
    }

    public function testSuccessfulImportWithDateInThePast(): void
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
                    'item_id'   => 13,
                    'number'    => 1,
                    'user_id'   => 101,
                    'filename'  => 'Pan-Pan-Artwork1.png',
                    'filesize'  => 799789,
                    'filetype'  => 'image/png',
                    'path'      => $file_path,
                    'date'      => 1234567890,
                    'label'     => '',
                    'changelog' => ''
                ]
            )->once()
            ->andReturn(Mockery::mock(Docman_Version::class));

        $node = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <version>
                <filename>Pan-Pan-Artwork1.png</filename>
                <filetype>image/png</filetype>
                <filesize>799789</filesize>
                <date format="ISO8601">2009-02-14T00:31:30+01:00</date>
                <content>documents/content-214.bin</content>
            </version>
            EOS
        );

        $this->importer->import($node, $this->item, 1);
        $this->assertFileExists($file_path);
    }

    public function testSuccessfulImportWithLabelAndChangelog(): void
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
                    'item_id'   => 13,
                    'number'    => 1,
                    'user_id'   => 101,
                    'filename'  => 'Pan-Pan-Artwork1.png',
                    'filesize'  => 799789,
                    'filetype'  => 'image/png',
                    'path'      => $file_path,
                    'date'      => $this->current_date->getTimestamp(),
                    'label'     => 'The label',
                    'changelog' => 'The changelog'
                ]
            )->once()
            ->andReturn(Mockery::mock(Docman_Version::class));

        $node = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <version>
                <filename>Pan-Pan-Artwork1.png</filename>
                <filetype>image/png</filetype>
                <filesize>799789</filesize>
                <label>The label</label>
                <changelog>The changelog</changelog>
                <content>documents/content-214.bin</content>
            </version>
            EOS
        );

        $this->importer->import($node, $this->item, 1);
        $this->assertFileExists($file_path);
    }
}
