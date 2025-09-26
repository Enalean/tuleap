<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Attachment;

use ForgeConfig;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_Artifact_Attachment_TemporaryFile;
use Tracker_Artifact_Attachment_TemporaryFileManager;
use Tracker_Artifact_Attachment_TemporaryFileManagerDao;
use Tuleap\ForgeConfigSandbox;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TemporaryFileManagerAppendChunkTest extends TestCase
{
    use ForgeConfigSandbox;
    use TemporaryTestDirectory;

    private Tracker_Artifact_Attachment_TemporaryFile $empty_file;
    private Tracker_Artifact_Attachment_TemporaryFile $wrong_path_file;
    private Tracker_Artifact_Attachment_TemporaryFileManager $file_manager;
    private string $cache_dir;
    private Tracker_Artifact_Attachment_TemporaryFileManagerDao&MockObject $dao;

    #[\Override]
    public function setUp(): void
    {
        $this->cache_dir = $this->getTmpDir();
        ForgeConfig::set('codendi_cache_dir', $this->cache_dir);

        $this->dao = $this->createMock(Tracker_Artifact_Attachment_TemporaryFileManagerDao::class);
        $this->dao->method('create')->willReturn(1);

        $this->file_manager = new Tracker_Artifact_Attachment_TemporaryFileManager(
            RetrieveUserByIdStub::withUser(new PFUser(['user_id' => 101, 'language_id' => 'en_US'])),
            $this->dao,
            3,
            new DBTransactionExecutorPassthrough(),
        );

        ForgeConfig::set('sys_max_size_upload', 10);
        $this->empty_file = new Tracker_Artifact_Attachment_TemporaryFile(
            1,
            'jette_lit.png',
            'random_tmpname',
            'Mugshot',
            0,
            101,
            0,
            'image/png'
        );
        touch($this->cache_dir . '/rest_attachement_temp_101_' . $this->empty_file->getTemporaryName());

        $this->wrong_path_file = new Tracker_Artifact_Attachment_TemporaryFile(
            1,
            'jette_lit.png',
            'wrong_path',
            'Mugshot',
            0,
            101,
            0,
            'image/png'
        );
    }

    public function testItThrowsExceptionIfOffsetIsNotValid(): void
    {
        $this->expectException('Tracker_Artifact_Attachment_InvalidOffsetException');

        $this->file_manager->appendChunk(base64_encode('le content'), $this->empty_file, 2);
    }

    public function testItThrowsExceptionIfFileDoesNotExist(): void
    {
        $this->expectException('Tracker_Artifact_Attachment_InvalidPathException');

        $this->file_manager->appendChunk(base64_encode('le content'), $this->wrong_path_file, 1);
    }

    public function testItWritesChunkOnTheDisk(): void
    {
        $filepath = $this->cache_dir . '/rest_attachement_temp_101_' . $this->empty_file->getTemporaryName();

        $this->dao->method('updateFileInfo')->willReturn(true);

        $this->file_manager->appendChunk(base64_encode('le content'), $this->empty_file, 1);

        self::assertEquals('le content', file_get_contents($filepath));
    }

    public function testItThrowsExceptionIfChunkIsTooBig(): void
    {
        $filepath = $this->cache_dir . '/rest_attachement_temp_101_' . $this->empty_file->getTemporaryName();
        $this->expectException('Tuleap\Tracker\Artifact\Attachment\QuotaExceededException');

        $this->file_manager->appendChunk(base64_encode('le too big content'), $this->empty_file, 1);

        self::assertEquals('', file_get_contents($filepath));
    }

    public function testThrowsWhenContentIsNotBase64Encoded(): void
    {
        $this->expectException(InvalidBase64ContentChunkException::class);
        $this->file_manager->appendChunk('wrong', $this->empty_file, 1);
    }
}
