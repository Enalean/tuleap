<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

declare(strict_types=1);

namespace Tuleap\Docman;

use Docman_File;
use Docman_ItemFactory;
use Docman_Version;
use Docman_VersionDao;
use Docman_VersionFactory;
use EventManager;
use org\bovigo\vfs\vfsStream;
use TestHelper;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use UserManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class VersionFactoryTest extends TestCase
{
    private string $tmp_tuleap_dir;

    #[\Override]
    protected function setUp(): void
    {
        $this->tmp_tuleap_dir = vfsStream::setup()->url();
    }

    public function testPurgeDeletedVersionsWithNoVersions(): void
    {
        $dao = $this->createMock(Docman_VersionDao::class);
        $dao->method('listVersionsToPurge')->willReturn(TestHelper::emptyDar());

        $versionFactory = $this->createPartialMock(Docman_VersionFactory::class, [
            '_getVersionDao',
            'purgeDeletedVersion',
        ]);
        $versionFactory->method('_getVersionDao')->willReturn($dao);
        $versionFactory->expects($this->never())->method('purgeDeletedVersion');

        self::assertTrue($versionFactory->purgeDeletedVersions(1234567890));
    }

    public function testPurgeDeletedVersions(): void
    {
        $dao = $this->createMock(Docman_VersionDao::class);
        $dao->method('listVersionsToPurge')->willReturn(TestHelper::arrayToDar([
            'id'        => null,
            'user_id'   => null,
            'item_id'   => null,
            'number'    => null,
            'label'     => null,
            'changelog' => null,
            'date'      => null,
            'filename'  => null,
            'filesize'  => null,
            'filetype'  => null,
            'path'      => null,
        ]));
        $versionFactory = $this->createPartialMock(Docman_VersionFactory::class, [
            '_getVersionDao',
            'purgeDeletedVersion',
        ]);
        $versionFactory->method('_getVersionDao')->willReturn($dao);
        $versionFactory->expects($this->once())->method('purgeDeletedVersion');

        self::assertTrue($versionFactory->purgeDeletedVersions(1234567890));
    }

    public function testPurgeDeletedVersionFileNotFound(): void
    {
        $versionFactory = $this->createPartialMock(Docman_VersionFactory::class, ['archiveBeforePurge']);

        $version = new Docman_Version([
            'id'        => null,
            'user_id'   => null,
            'item_id'   => null,
            'number'    => null,
            'label'     => null,
            'changelog' => null,
            'date'      => null,
            'filename'  => 'noFile',
            'filesize'  => null,
            'filetype'  => null,
            'path'      => $this->tmp_tuleap_dir . '/noFile',
        ]);

        $versionFactory->expects($this->once())->method('archiveBeforePurge')->with($version);
        self::assertFalse($versionFactory->purgeDeletedVersion($version));
    }

    public function testPurgeDeletedVersion(): void
    {
        $dao = $this->createMock(Docman_VersionDao::class);
        $dao->method('setPurgeDate')->willReturn(true);

        $versionFactory = $this->createPartialMock(Docman_VersionFactory::class, [
            '_getVersionDao',
            'archiveBeforePurge',
        ]);
        $versionFactory->method('_getVersionDao')->willReturn($dao);
        $version = new Docman_Version([
            'id'        => null,
            'user_id'   => null,
            'item_id'   => null,
            'number'    => null,
            'label'     => null,
            'changelog' => null,
            'date'      => null,
            'filename'  => 'fileToPurge.txt',
            'filesize'  => null,
            'filetype'  => null,
            'path'      => $this->tmp_tuleap_dir . '/fileToPurge_txt',
        ]);
        fopen($version->getPath(), 'w');
        $versionFactory->expects($this->once())->method('archiveBeforePurge')->with($version)->willReturn(true);

        self::assertTrue($versionFactory->purgeDeletedVersion($version));
        self::assertFalse(file_exists($version->getPath()));
    }

    public function itDoesNotRemoveLocalFileIfPurgeFails(): void
    {
        $dao = $this->createMock(Docman_VersionDao::class);
        $dao->method('setPurgeDate')->willReturn(true);

        $versionFactory = $this->createPartialMock(Docman_VersionFactory::class, [
            'archiveBeforePurge',
        ]);
        $version        = new Docman_Version([
            'id'        => null,
            'user_id'   => null,
            'item_id'   => null,
            'number'    => null,
            'label'     => null,
            'changelog' => null,
            'date'      => null,
            'filename'  => 'fileToDontPurge.txt',
            'filesize'  => null,
            'filetype'  => null,
            'path'      => $this->tmp_tuleap_dir . '/fileToPurge_txt',
        ]);
        fopen($version->getPath(), 'w');

        $versionFactory->expects($this->never())->method('archiveBeforePurge');

        self::assertTrue(file_exists($version->getPath()));

        unlink($version->getPath());
    }

    public function testRestoreOneVersion(): void
    {
        $filePath = $this->tmp_tuleap_dir . '/version.test';
        touch($filePath);
        $dao            = $this->createMock(Docman_VersionDao::class);
        $versionFactory = $this->createPartialMock(Docman_VersionFactory::class, [
            '_getVersionDao',
            '_getItemFactory',
            '_getUserManager',
            '_getEventManager',
        ]);
        $versionFactory->method('_getVersionDao')->willReturn($dao);

        $dao->method('searchDeletedVersion')->with(1664, 2)->willReturn(
            TestHelper::arrayToDar(['purge_date' => null, 'label' => 'Ho hisse la saucisse', 'path' => $filePath])
        );

        $file = new Docman_File(['group_id' => 114]);
        $if   = $this->createMock(Docman_ItemFactory::class);
        $if->expects($this->once())->method('getItemFromDb')->with(1664, ['ignore_deleted' => true])->willReturn($file);
        $versionFactory->method('_getItemFactory')->willReturn($if);

        $user = UserTestBuilder::buildWithDefaults();
        $um   = $this->createMock(UserManager::class);
        $um->method('getCurrentUser')->willReturn($user);
        $versionFactory->method('_getUserManager')->willReturn($um);

        $em = $this->createMock(EventManager::class);
        $em->method('processEvent')->with(
            'plugin_docman_event_restore_version',
            [
                'group_id'  => 114,
                'item'      => $file,
                'old_value' => '2 (Ho hisse la saucisse)',
                'user'      => $user,
            ]
        );
        $versionFactory->method('_getEventManager')->willReturn($em);
        $dao->expects($this->once())->method('restore')->with(1664, 2)->willReturn(true);

        $version = new Docman_Version(['item_id' => 1664, 'number' => 2]);

        self::assertTrue($versionFactory->restore($version));
        unlink($filePath);
    }

    public function testRestoreOneVersionButFileIsDeleted(): void
    {
        $filePath       = $this->tmp_tuleap_dir . '/version.test';
        $dao            = $this->createMock(Docman_VersionDao::class);
        $versionFactory = $this->createPartialMock(Docman_VersionFactory::class, [
            '_getVersionDao',
            '_getEventManager',
        ]);
        $versionFactory->method('_getVersionDao')->willReturn($dao);

        $dao->method('searchDeletedVersion')->with(1664, 2)->willReturn(
            TestHelper::arrayToDar(['purge_date' => null, 'path' => $filePath])
        );

        $em = $this->createMock(EventManager::class);
        $em->expects($this->never())->method('processEvent')->with('plugin_docman_event_restore_version', self::anything());
        $versionFactory->method('_getEventManager')->willReturn($em);

        $dao->expects($this->never())->method('restore');

        $version = new Docman_Version(['item_id' => 1664, 'number' => 2, 'path' => $filePath]);

        self::assertFalse($versionFactory->restore($version));
    }

    public function testRestoreOneVersionAlreadyPurged(): void
    {
        $filePath       = $this->tmp_tuleap_dir . '/version.test';
        $dao            = $this->createMock(Docman_VersionDao::class);
        $versionFactory = $this->createPartialMock(Docman_VersionFactory::class, [
            '_getVersionDao',
            '_getEventManager',
        ]);
        $versionFactory->method('_getVersionDao')->willReturn($dao);

        $dao->method('searchDeletedVersion')->with(1664, 2)->willReturn(
            TestHelper::arrayToDar(['purge_date' => 1234567890, 'path' => $filePath])
        );

        $em = $this->createMock(EventManager::class);
        $em->expects($this->never())->method('processEvent')->with('plugin_docman_event_restore_version', self::anything());
        $versionFactory->method('_getEventManager')->willReturn($em);

        $dao->expects($this->never())->method('restore');

        $version = new Docman_Version(['item_id' => 1664, 'number' => 2]);

        self::assertFalse($versionFactory->restore($version));
    }
}
