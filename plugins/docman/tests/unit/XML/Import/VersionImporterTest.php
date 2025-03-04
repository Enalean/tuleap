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

use DateTimeImmutable;
use Docman_FileStorage;
use Docman_Item;
use Docman_Version;
use Docman_VersionFactory;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\MockObject\MockObject;
use SimpleXMLElement;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\User\XML\Import\IFindUserFromXMLReferenceStub;
use Tuleap\xml\InvalidDateException;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class VersionImporterTest extends TestCase
{
    private Docman_VersionFactory&MockObject $version_factory;
    private Docman_FileStorage&MockObject $docman_file_storage;
    private string $extraction_path;
    private VersionImporter $importer;
    private SimpleXMLElement $node;
    private Docman_Item $item;
    private DateTimeImmutable $current_date;

    protected function setUp(): void
    {
        $this->version_factory     = $this->createMock(Docman_VersionFactory::class);
        $this->docman_file_storage = $this->createMock(Docman_FileStorage::class);
        $project                   = ProjectTestBuilder::aProject()->withId(114)->build();
        $this->extraction_path     = '/path/to/extracted/archive';
        $this->current_date        = new DateTimeImmutable();
        $user                      = UserTestBuilder::buildWithId(101);
        $user_finder               = IFindUserFromXMLReferenceStub::buildWithUser($user);

        $this->node = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <version>
                <filename>Pan-Pan-Artwork1.png</filename>
                <filetype>image/png</filetype>
                <content>documents/content-214.bin</content>
            </version>
            EOS
        );

        $this->item = new Docman_Item(['item_id' => 13]);

        $this->importer = new VersionImporter(
            $user_finder,
            $this->version_factory,
            $this->docman_file_storage,
            $project,
            $this->extraction_path,
            $this->current_date,
            $user
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
                <date format="ISO8601">invalid</date>
                <content>documents/content-214.bin</content>
            </version>
            EOS
        );

        $this->docman_file_storage->expects(self::never())->method('copy')
            ->with($this->extraction_path . '/documents/content-214.bin', 'Pan-Pan-Artwork1.png', 114, 13, 1);

        self::expectException(InvalidDateException::class);

        $this->importer->import($node, $this->item, 1);
    }

    public function testItRaisesExceptionWhenFileCannotBeCopiedOnFilesystem(): void
    {
        $this->docman_file_storage->expects(self::once())->method('copy')
            ->with($this->extraction_path . '/documents/content-214.bin', 'Pan-Pan-Artwork1.png', 114, 13, 1)
            ->willReturn(false);

        self::expectException(UnableToCreateFileOnFilesystemException::class);

        $this->importer->import($this->node, $this->item, 1);
    }

    public function testItRaisesExceptionWhenVersionCannotBeCreatedInDb(): void
    {
        $root         = vfsStream::setup();
        $created_file = vfsStream::newFile('file.png')->at($root);
        $file_path    = $created_file->url();
        self::assertFileExists($file_path);

        $this->docman_file_storage->expects(self::once())->method('copy')
            ->with($this->extraction_path . '/documents/content-214.bin', 'Pan-Pan-Artwork1.png', 114, 13, 1)
            ->willReturn($file_path);

        $this->version_factory->expects(self::once())->method('create')->with([
            'item_id'   => 13,
            'number'    => 1,
            'user_id'   => 101,
            'filename'  => 'Pan-Pan-Artwork1.png',
            'filesize'  => filesize($file_path),
            'filetype'  => 'image/png',
            'path'      => $file_path,
            'date'      => $this->current_date->getTimestamp(),
            'label'     => '',
            'changelog' => '',
        ])->willReturn(false);

        $exception_caught = false;
        try {
            $this->importer->import($this->node, $this->item, 1);
        } catch (UnableToCreateVersionInDbException) {
            $exception_caught = true;
            self::assertFileDoesNotExist($file_path);
        }
        self::assertTrue($exception_caught);
    }

    public function testSuccessfulImport(): void
    {
        $root         = vfsStream::setup();
        $created_file = vfsStream::newFile('file.png')->at($root);
        $file_path    = $created_file->url();
        $this->assertFileExists($file_path);

        $this->docman_file_storage->expects(self::once())->method('copy')
            ->with($this->extraction_path . '/documents/content-214.bin', 'Pan-Pan-Artwork1.png', 114, 13, 1)
            ->willReturn($file_path);

        $this->version_factory->expects(self::once())->method('create')->with([
            'item_id'   => 13,
            'number'    => 1,
            'user_id'   => 101,
            'filename'  => 'Pan-Pan-Artwork1.png',
            'filesize'  => filesize($file_path),
            'filetype'  => 'image/png',
            'path'      => $file_path,
            'date'      => $this->current_date->getTimestamp(),
            'label'     => '',
            'changelog' => '',
        ])->willReturn(new Docman_Version());

        $this->importer->import($this->node, $this->item, 1);
        self::assertFileExists($file_path);
    }

    public function testSuccessfulImportWithGivenUser(): void
    {
        $root         = vfsStream::setup();
        $created_file = vfsStream::newFile('file.png')->at($root);
        $file_path    = $created_file->url();
        self::assertFileExists($file_path);

        $this->docman_file_storage->method('copy')->willReturn($file_path);

        $this->version_factory->expects(self::once())->method('create')->with([
            'item_id'   => 13,
            'number'    => 1,
            'user_id'   => 101,
            'filename'  => 'Pan-Pan-Artwork1.png',
            'filesize'  => filesize($file_path),
            'filetype'  => 'image/png',
            'path'      => $file_path,
            'date'      => $this->current_date->getTimestamp(),
            'label'     => '',
            'changelog' => '',
        ])->willReturn(new Docman_Version());

        $node = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <version>
                <filename>Pan-Pan-Artwork1.png</filename>
                <filetype>image/png</filetype>
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
        self::assertFileExists($file_path);

        $this->docman_file_storage->expects(self::once())->method('copy')
            ->with($this->extraction_path . '/documents/content-214.bin', 'Pan-Pan-Artwork1.png', 114, 13, 1)
            ->willReturn($file_path);

        $this->version_factory->expects(self::once())->method('create')->with([
            'item_id'   => 13,
            'number'    => 1,
            'user_id'   => 101,
            'filename'  => 'Pan-Pan-Artwork1.png',
            'filesize'  => filesize($file_path),
            'filetype'  => 'image/png',
            'path'      => $file_path,
            'date'      => 1234567890,
            'label'     => '',
            'changelog' => '',
        ])->willReturn(new Docman_Version());

        $node = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <version>
                <filename>Pan-Pan-Artwork1.png</filename>
                <filetype>image/png</filetype>
                <date format="ISO8601">2009-02-14T00:31:30+01:00</date>
                <content>documents/content-214.bin</content>
            </version>
            EOS
        );

        $this->importer->import($node, $this->item, 1);
        self::assertFileExists($file_path);
    }

    public function testSuccessfulImportWithLabelAndChangelog(): void
    {
        $root         = vfsStream::setup();
        $created_file = vfsStream::newFile('file.png')->at($root);
        $file_path    = $created_file->url();
        self::assertFileExists($file_path);

        $this->docman_file_storage->expects(self::once())->method('copy')
            ->with($this->extraction_path . '/documents/content-214.bin', 'Pan-Pan-Artwork1.png', 114, 13, 1)
            ->willReturn($file_path);

        $this->version_factory->expects(self::once())->method('create')->with([
            'item_id'   => 13,
            'number'    => 1,
            'user_id'   => 101,
            'filename'  => 'Pan-Pan-Artwork1.png',
            'filesize'  => filesize($file_path),
            'filetype'  => 'image/png',
            'path'      => $file_path,
            'date'      => $this->current_date->getTimestamp(),
            'label'     => 'The label',
            'changelog' => 'The changelog',
        ])->willReturn(new Docman_Version());

        $node = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <version>
                <filename>Pan-Pan-Artwork1.png</filename>
                <filetype>image/png</filetype>
                <label>The label</label>
                <changelog>The changelog</changelog>
                <content>documents/content-214.bin</content>
            </version>
            EOS
        );

        $this->importer->import($node, $this->item, 1);
        self::assertFileExists($file_path);
    }
}
