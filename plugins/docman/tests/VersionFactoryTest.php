<?php

/**
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once(dirname(__FILE__).'/../include/Docman_ItemFactory.class.php');

Mock::generate('DataAccessResult');
Mock::generate('Docman_VersionDao');
Mock::generate('Docman_Version');
Mock::generatePartial('Docman_VersionFactory', 'Docman_VersionFactoryTestVersion', array('purgeDeletedVersion', '_getVersionDao', '_purge'));

class Docman_VersionFactoryTest extends UnitTestCase {

    function testPurgeDeletedVersionsWithNoVersions() {
        $versionFactory = new Docman_VersionFactoryTestVersion($this);

        $dar = new MockDataAccessResult($this);
        $dar->setReturnValue('isError', false);
        $dar->setReturnValue('getRow', false);
        $dar->setReturnValue('valid', false);
        $dar->setReturnValue('rowCount', 0);

        $dao = new MockDocman_VersionDao($this);
        $dao->expectOnce('listVersionsToPurge');
        $dao->setReturnValue('listVersionsToPurge', $dar);
        $versionFactory->setReturnValue('_getVersionDao', $dao);

        $versionFactory->expectNever('purgeDeletedVersion');

        $this->assertTrue($versionFactory->PurgeDeletedVersions(1234567890));
    }

    function testPurgeDeletedVersions() {
        $versionFactory = new Docman_VersionFactoryTestVersion($this);

        $dar = new MockDataAccessResult($this);
        $dar->setReturnValue('isError', false);
        $dar->setReturnValue('current', array('id'        => null,
                                              'user_id'   => null,
                                              'item_id'   => null,
                                              'number'    => null,
                                              'label'     => null,
                                              'changelog' => null,
                                              'date'      => null,
                                              'filename'  => null,
                                              'filesize'  => null,
                                              'filetype'  => null,
                                              'path'      => null));
        $dar->setReturnValueAt(0, 'valid', true);
        $dar->setReturnValueAt(1, 'valid', false);
        $dar->setReturnValue('rowCount', 1);

        $dao = new MockDocman_VersionDao($this);
        $dao->expectOnce('listVersionsToPurge');
        $dao->setReturnValue('listVersionsToPurge', $dar);
        $versionFactory->setReturnValue('_getVersionDao', $dao);

        $versionFactory->expectOnce('purgeDeletedVersion');

        $this->assertTrue($versionFactory->PurgeDeletedVersions(1234567890));
    }

    function testRestoreOneVersion() {
        $versionFactory = new Docman_VersionFactoryTestVersion($this);
        $dao            = new MockDocman_VersionDao($this);
        $versionFactory->setReturnValue('_getVersionDao', $dao);

        $dar = new MockDataAccessResult($this);
        $dar->setReturnValue('isError', false);
        $dar->setReturnValue('getRow', array('purge_date' => null));
        $dao->expectOnce('searchDeletedVersion', array(1664, 2));
        $dao->setReturnValue('searchDeletedVersion', $dar);

        $dao->expectOnce('restore', array(1664, 2));
        $dao->setReturnValue('restore', true);

        $version = new MockDocman_Version($this);
        $version->setReturnValue('getNumber', 2);
        $version->setReturnValue('getItemId', 1664);

        $this->assertTrue($versionFactory->restore($version));
    }

    function testRestoreOneVersionAlreadyPurged() {
        $versionFactory = new Docman_VersionFactoryTestVersion($this);
        $dao            = new MockDocman_VersionDao($this);
        $versionFactory->setReturnValue('_getVersionDao', $dao);

        $dar = new MockDataAccessResult($this);
        $dar->setReturnValue('isError', false);
        $dar->setReturnValue('getRow', array('purge_date' => 1234567890));
        $dao->expectOnce('searchDeletedVersion', array(1664, 2));
        $dao->setReturnValue('searchDeletedVersion', $dar);

        $dao->expectNever('restore');

        $version = new MockDocman_Version($this);
        $version->setReturnValue('getNumber', 2);
        $version->setReturnValue('getItemId', 1664);

        $this->assertFalse($versionFactory->restore($version));
    }
}

?>