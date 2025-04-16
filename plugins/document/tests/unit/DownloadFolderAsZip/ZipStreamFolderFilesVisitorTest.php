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

namespace Tuleap\Document\DownloadFolderAsZip;

use Docman_EmbeddedFile;
use Docman_Empty;
use Docman_File;
use Docman_Folder;
use Docman_Link;
use Docman_Version;
use Docman_Wiki;
use PHPUnit\Framework\MockObject\MockObject;
use PrioritizedList;
use ZipStream\Exception\FileNotFoundException;
use ZipStream\Exception\FileNotReadableException;
use ZipStream\ZipStream;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class ZipStreamFolderFilesVisitorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ZipStream&MockObject $zip;
    private ZipStreamerLoggingHelper&MockObject $error_logging_helper;

    protected function setUp(): void
    {
        $this->zip                  = $this->createMock(ZipStream::class);
        $this->error_logging_helper = $this->createMock(ZipStreamerLoggingHelper::class);
    }

    public function testItStreamsAllFilesInTheFolderKeepingItsStructure(): void
    {
        $visitor = new ZipStreamFolderFilesVisitor(
            $this->zip,
            $this->error_logging_helper,
            new ErrorsListingBuilder()
        );

        $root_folder = $this->getRootFolderWithItems();
        $matcher     = self::exactly(2);

        $this->zip->expects($matcher)->method('addFileFromPath')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame('/my files/file.pdf', $parameters[0]);
                self::assertSame('/path/to/file', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame('/my files/an embedded_file.html', $parameters[0]);
                self::assertSame('/path/to/embedded', $parameters[1]);
            }
        });

        $root_folder->accept($visitor, ['path' => '', 'base_folder_id' => $root_folder->getId()]);
    }

    public function testItLogsTheFileNotFoundError(): void
    {
        $visitor = new ZipStreamFolderFilesVisitor(
            $this->zip,
            $this->error_logging_helper,
            new ErrorsListingBuilder()
        );

        $root_folder = $this->getRootFolderWithItems();

        $this->zip->method('addFileFromPath')->willThrowException(new class extends FileNotFoundException {
            public function __construct()
            {
            }
        });

        $this->error_logging_helper->expects($this->atLeast(1))->method('logFileNotFoundException');

        $root_folder->accept($visitor, ['path' => '', 'base_folder_id' => $root_folder->getId()]);
    }

    public function testItLogsTheFileNotReadableError(): void
    {
        $visitor = new ZipStreamFolderFilesVisitor(
            $this->zip,
            $this->error_logging_helper,
            new ErrorsListingBuilder()
        );

        $root_folder = $this->getRootFolderWithItems();

        $this->zip->method('addFileFromPath')->willThrowException(new class extends FileNotReadableException
        {
            public function __construct()
            {
            }
        });

        $this->error_logging_helper->expects($this->atLeast(1))->method('logFileNotReadableException');

        $root_folder->accept($visitor, ['path' => '', 'base_folder_id' => $root_folder->getId()]);
    }

    public function testItLogsCorruptedFiles(): void
    {
        $visitor = new ZipStreamFolderFilesVisitor(
            $this->zip,
            $this->error_logging_helper,
            new ErrorsListingBuilder()
        );

        $root_folder = $this->getRootFolderWithItems(true);
        $matcher     = self::exactly(2);

        $this->zip->expects($matcher)->method('addFileFromPath')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame('/my files/file.pdf', $parameters[0]);
                self::assertSame('/path/to/file', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame('/my files/an embedded_file.html', $parameters[0]);
                self::assertSame('/path/to/embedded', $parameters[1]);
            }
        });

        $this->error_logging_helper->expects($this->atLeast(1))->method('logCorruptedFile');

        $root_folder->accept($visitor, ['path' => '', 'base_folder_id' => $root_folder->getId()]);
    }

    /**
     * Returns a Docman_Folder with the following structure:
     *
     * [v] Root folder
     *  | [a link]
     *  | [an empty item]
     *  | [a wiki]
     *  |
     *  | [v] my files
     *  |  | [a file]
     *  |  | [an embedded file]
     *
     */
    private function getRootFolderWithItems(bool $does_contain_corrupted_file = false): Docman_Folder
    {
        $root_folder = new Docman_Folder(
            [
                'item_id' => 10,
                'title'   => 'Root folder',
            ]
        );

        $subfolder = new Docman_Folder(['item_id' => 4, 'title' => 'my files']);

        $file = new Docman_File(['item_id' => 5, 'title' => 'a file in pdf']);
        $file->setCurrentVersion(new Docman_Version(['path' => '/path/to/file', 'filename' => 'file.pdf']));

        $embedded = new Docman_EmbeddedFile(['item_id' => 6, 'title' => 'an embedded/file']);
        $embedded->setCurrentVersion(new Docman_Version(['path' => '/path/to/embedded']));

        $subfolder->setItems(
            new PrioritizedList(
                [
                    $file,
                    $embedded,
                ]
            )
        );

        $root_folder_children = [
            new Docman_Link(['item_id' => 1, 'title' => 'a link', 'link_url' => 'https://link.com']),
            new Docman_Empty(['item_id' => 2, 'title' => 'an empty item']),
            new Docman_Wiki(['item_id' => 3, 'title' => 'a wiki', 'wiki_page' => 'wikis for dummies']),
            $subfolder,
        ];

        if ($does_contain_corrupted_file) {
            $root_folder_children[] = new Docman_File(['item_id' => 7, 'title' => 'corrupted file.png']);
        }

        $root_folder->setItems(
            new PrioritizedList($root_folder_children)
        );

        return $root_folder;
    }
}
