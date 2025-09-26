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

namespace Tuleap\Docman\Upload\Version;

use DateTimeImmutable;
use Docman_Item;
use DocmanPlugin;
use ForgeConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Docman\Upload\UploadCreationConflictException;
use Tuleap\Docman\Upload\UploadCreationFileMismatchException;
use Tuleap\Docman\Upload\UploadMaxSizeExceededException;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class VersionToUploadCreatorTest extends TestCase
{
    use ForgeConfigSandbox;

    private DocumentOnGoingVersionToUploadDAO&MockObject $dao;

    #[\Override]
    public function setUp(): void
    {
        $this->dao = $this->createMock(DocumentOnGoingVersionToUploadDAO::class);
    }

    public function testCreation(): void
    {
        $creator = new VersionToUploadCreator($this->dao, new DBTransactionExecutorPassthrough());

        ForgeConfig::set(DocmanPlugin::PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING, '999999');
        $item         = new Docman_Item(['item_id' => 11]);
        $user         = UserTestBuilder::buildWithId(102);
        $current_time = new DateTimeImmutable();

        $this->dao->method('searchDocumentVersionOngoingUploadByItemIdAndExpirationDate')->willReturn([]);
        $this->dao->expects($this->once())->method('saveDocumentVersionOngoingUpload')->willReturn(12);

        $document_to_upload = $creator->create(
            $item,
            $user,
            $current_time,
            'version title',
            'changelog',
            'filename',
            123456,
            false,
            101,
            155815815,
            'My new title',
            'new description',
            'copy'
        );

        self::assertSame(12, $document_to_upload->getVersionId());
    }

    public function testANewItemIsNotCreatedIfAnUploadIsOngoingWithTheSameFile(): void
    {
        $creator = new VersionToUploadCreator($this->dao, new DBTransactionExecutorPassthrough());

        ForgeConfig::set(DocmanPlugin::PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING, '999999');
        $parent_item  = new Docman_Item(['item_id' => 11]);
        $user         = UserTestBuilder::buildWithId(102);
        $current_time = new DateTimeImmutable();

        $this->dao->method('searchDocumentVersionOngoingUploadByItemIdAndExpirationDate')->willReturn([
            ['id' => 12, 'user_id' => 102, 'filename' => 'filename', 'filesize' => 123456],
        ]);

        $document_to_upload = $creator->create(
            $parent_item,
            $user,
            $current_time,
            'version title',
            'changelog',
            'filename',
            123456,
            false,
            101,
            155815815,
            'My new title',
            'new description',
            'copy'
        );

        self::assertSame(12, $document_to_upload->getVersionId());
    }

    public function testCreationIsRejectedWhenAnotherUserIsCreatingTheDocument(): void
    {
        $creator = new VersionToUploadCreator($this->dao, new DBTransactionExecutorPassthrough());

        ForgeConfig::set(DocmanPlugin::PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING, '999999');
        $parent_item  = new Docman_Item(['item_id' => 11]);
        $user         = UserTestBuilder::buildWithId(102);
        $current_time = new DateTimeImmutable();

        $this->dao->method('searchDocumentVersionOngoingUploadByItemIdAndExpirationDate')->willReturn([['user_id' => 103]]);

        $this->expectException(UploadCreationConflictException::class);

        $creator->create(
            $parent_item,
            $user,
            $current_time,
            'version title',
            'changelog',
            'filename',
            123456,
            false,
            101,
            155815815,
            'My new title',
            'new description',
            'empty'
        );
    }

    public function testCreationIsRejectedWhenTheUserIsAlreadyCreatingTheDocumentWithAnotherFile(): void
    {
        $creator = new VersionToUploadCreator($this->dao, new DBTransactionExecutorPassthrough());

        ForgeConfig::set(DocmanPlugin::PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING, '999999');
        $parent_item  = new Docman_Item(['item_id' => 11]);
        $user         = UserTestBuilder::buildWithId(102);
        $current_time = new DateTimeImmutable();

        $this->dao->method('searchDocumentVersionOngoingUploadByItemIdAndExpirationDate')->willReturn([
            ['user_id' => 102, 'filename' => 'filename1', 'filesize' => 123456],
        ]);

        $this->expectException(UploadCreationFileMismatchException::class);

        $creator->create(
            $parent_item,
            $user,
            $current_time,
            'version title',
            'changelog',
            'filename2',
            789,
            false,
            101,
            155815815,
            'My new title',
            'new description',
            'reset'
        );
    }

    public function testCreationIsRejectedIfTheFileIsBiggerThanTheConfigurationLimit(): void
    {
        $creator = new VersionToUploadCreator($this->dao, new DBTransactionExecutorPassthrough());

        ForgeConfig::set(DocmanPlugin::PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING, '1');
        $parent_item  = new Docman_Item();
        $user         = UserTestBuilder::buildWithDefaults();
        $current_time = new DateTimeImmutable();

        $this->expectException(UploadMaxSizeExceededException::class);

        $creator->create(
            $parent_item,
            $user,
            $current_time,
            'version title',
            'changelog',
            'filename',
            2,
            false,
            101,
            155815815,
            'My new title',
            'new description',
            'reset'
        );
    }
}
