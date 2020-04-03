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

use Docman_Version;
use Docman_VersionFactory;
use EventManager;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

//phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
class Docman_VersionFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var string
     */
    private $tmp_tuleap_dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmp_tuleap_dir = vfsStream::setup()->url();
    }

    public function testPurgeDeletedVersionsWithNoVersions(): void
    {
        $dao = \Mockery::spy(\Docman_VersionDao::class);
        $dao->shouldReceive('listVersionsToPurge')->andReturns(\TestHelper::emptyDar());

        $versionFactory = \Mockery::mock(Docman_VersionFactory::class, ['_getVersionDao' => $dao])->makePartial();
        $versionFactory->shouldNotReceive('purgeDeletedVersion');

        $this->assertTrue($versionFactory->purgeDeletedVersions(1234567890));
    }

    public function testPurgeDeletedVersions(): void
    {
        $dao = \Mockery::spy(\Docman_VersionDao::class);
        $dao->shouldReceive('listVersionsToPurge')->andReturns(
            \TestHelper::arrayToDar(
                array(
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
                    'path'      => null
                )
            )
        );
        $versionFactory = \Mockery::mock(Docman_VersionFactory::class, ['_getVersionDao' => $dao])->makePartial();

        $versionFactory->shouldReceive('purgeDeletedVersion')->once();

        $this->assertTrue($versionFactory->purgeDeletedVersions(1234567890));
    }

    public function testPurgeDeletedVersionFileNotFound(): void
    {
        $versionFactory = \Mockery::mock(Docman_VersionFactory::class)->makePartial();

        $version = new Docman_Version(
            array(
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
                'path'      => $this->tmp_tuleap_dir . '/noFile'
            )
        );

        $versionFactory->shouldReceive('archiveBeforePurge')->with($version)->once();
        $this->assertFalse($versionFactory->purgeDeletedVersion($version));
    }

    public function testPurgeDeletedVersion(): void
    {
        $dao = \Mockery::spy(\Docman_VersionDao::class);
        $dao->shouldReceive('setPurgeDate')->andReturns(true);

        $versionFactory = \Mockery::mock(Docman_VersionFactory::class, ['_getVersionDao' => $dao])->makePartial();
        $version        = new Docman_Version(
            array(
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
                'path'      => $this->tmp_tuleap_dir . '/fileToPurge_txt'
            )
        );

        $fp = fopen($version->getPath(), 'w');

        $versionFactory->shouldReceive('archiveBeforePurge')->with($version)->once()->andReturn(true);

        $this->assertTrue($versionFactory->purgeDeletedVersion($version));
        $this->assertFalse(file_exists($version->getPath()));
    }

    public function itDoesNotRemoveLocalFileIfPurgeFails(): void
    {
        $dao = \Mockery::spy(\Docman_VersionDao::class);
        $dao->shouldReceive('setPurgeDate')->andReturns(true);

        $versionFactory = \Mockery::mock(Docman_VersionFactory::class, ['_getVersionDao' => $dao])->makePartial();
        $version        = new Docman_Version(
            array(
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
                'path'      => $this->tmp_tuleap_dir . '/fileToPurge_txt'
            )
        );
        fopen($version->getPath(), 'w');

        $versionFactory->shouldNotReceive('archiveBeforePurge');

        $this->assertTrue(file_exists($version->getPath()));

        unlink($version->getPath());
    }

    public function testRestoreOneVersion(): void
    {
        $filePath = $this->tmp_tuleap_dir . '/version.test';
        touch($filePath);
        $dao            = \Mockery::spy(\Docman_VersionDao::class);
        $versionFactory = \Mockery::mock(Docman_VersionFactory::class, ['_getVersionDao' => $dao])->makePartial();

        $dao->shouldReceive('searchDeletedVersion')->with(1664, 2)->andReturns(
            \TestHelper::arrayToDar(array('purge_date' => null, 'label' => 'Ho hisse la saucisse', 'path' => $filePath))
        );

        $file = \Mockery::spy(\Docman_File::class);
        $file->shouldReceive('getGroupId')->andReturns(114);
        $if = \Mockery::spy(\Docman_ItemFactory::class);
        $if->shouldReceive('getItemFromDb')->with(1664, array('ignore_deleted' => true))->once()->andReturns($file);
        $versionFactory->shouldReceive('_getItemFactory')->andReturn($if);

        $user = \Mockery::spy(\PFUser::class);
        $um   = \Mockery::spy(\UserManager::class);
        $um->shouldReceive('getCurrentUser')->andReturns($user);
        $versionFactory->shouldReceive('_getUserManager')->andReturn($um);

        $em = \Mockery::mock(EventManager::class);
        $em->shouldReceive('processEvent')->with(
            'plugin_docman_event_restore_version',
            array(
                'group_id'  => 114,
                'item'      => $file,
                'old_value' => '2 (Ho hisse la saucisse)',
                'user'      => $user
            )
        );
        $versionFactory->shouldReceive('_getEventManager')->andReturn($em);
        $dao->shouldReceive('restore')->with(1664, 2)->once()->andReturns(true);

        $version = \Mockery::spy(\Docman_Version::class);
        $version->shouldReceive('getNumber')->andReturns(2);
        $version->shouldReceive('getItemId')->andReturns(1664);

        $this->assertTrue($versionFactory->restore($version));
        unlink($filePath);
    }

    public function testRestoreOneVersionButFileIsDeleted(): void
    {
        $filePath       = $this->tmp_tuleap_dir . '/version.test';
        $dao            = \Mockery::spy(\Docman_VersionDao::class);
        $versionFactory = \Mockery::mock(Docman_VersionFactory::class, ['_getVersionDao' => $dao])->makePartial();

        $dao->shouldReceive('searchDeletedVersion')->with(1664, 2)->andReturns(
            \TestHelper::arrayToDar(array('purge_date' => null, 'path' => $filePath))
        );

        $em = \Mockery::mock(EventManager::class);
        $em->shouldNotReceive('processEvent')->with('plugin_docman_event_restore_version', \Mockery::any());
        $versionFactory->shouldReceive('_getEventManager')->andReturn($em);

        $dao->shouldReceive('restore')->never();

        $version = \Mockery::spy(\Docman_Version::class);
        $version->shouldReceive('getNumber')->andReturns(2);
        $version->shouldReceive('getItemId')->andReturns(1664);
        $version->shouldReceive('getPath')->andReturns($filePath);

        $this->assertFalse($versionFactory->restore($version));
    }

    public function testRestoreOneVersionAlreadyPurged(): void
    {
        $filePath       = $this->tmp_tuleap_dir . '/version.test';
        $dao            = \Mockery::spy(\Docman_VersionDao::class);
        $versionFactory = \Mockery::mock(Docman_VersionFactory::class, ['_getVersionDao' => $dao])->makePartial();

        $dao->shouldReceive('searchDeletedVersion')->with(1664, 2)->andReturns(
            \TestHelper::arrayToDar(array('purge_date' => 1234567890, 'path' => $filePath))
        );

        $em = \Mockery::mock(EventManager::class);
        $em->shouldNotReceive('processEvent')->with('plugin_docman_event_restore_version', \Mockery::any());
        $versionFactory->shouldReceive('_getEventManager')->andReturn($em);

        $dao->shouldReceive('restore')->never();

        $version = \Mockery::spy(\Docman_Version::class);
        $version->shouldReceive('getNumber')->andReturns(2);
        $version->shouldReceive('getItemId')->andReturns(1664);

        $this->assertFalse($versionFactory->restore($version));
    }
}
