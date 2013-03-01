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

require_once('common/event/EventManager.class.php');
Mock::generate('EventManager');

Mock::generate('UserManager');
Mock::generate('PFUser');

Mock::generate('DataAccessResult');
Mock::generate('Docman_VersionDao');
Mock::generate('Docman_Version');
Mock::generate('Docman_ItemFactory');
Mock::generate('Docman_File');
Mock::generatePartial('Docman_VersionFactory', 'Docman_VersionFactoryTestVersionDeleteFile', array('_getVersionDao', 'archiveBeforePurge'));

Mock::generatePartial('Docman_VersionFactory', 'Docman_VersionFactoryTestVersion', array('purgeDeletedVersion', '_getVersionDao', '_purge', '_getEventManager', '_getItemFactory', '_getUserManager'));

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

    function testPurgeDeletedVersionFileNotFound() {
        $versionFactory = new Docman_VersionFactoryTestVersionDeleteFile($this);

        $version = new Docman_Version(array('id'        => null,
                                            'user_id'   => null,
                                            'item_id'   => null,
                                            'number'    => null,
                                            'label'     => null,
                                            'changelog' => null,
                                            'date'      => null,
                                            'filename'  => 'noFile',
                                            'filesize'  => null,
                                            'filetype'  => null,
                                            'path'      => dirname(__FILE__).'/_fixtures/noFile'));

        $versionFactory->expectOnce('archiveBeforePurge', array($version));
        $this->assertFalse($versionFactory->PurgeDeletedVersion($version));
    }

function testPurgeDeletedVersion() {
        $versionFactory = new Docman_VersionFactoryTestVersionDeleteFile($this);

        $dao = new MockDocman_VersionDao($this);
        $dao->setReturnValue('setPurgeDate', true);
        $versionFactory->setReturnValue('_getVersionDao', $dao);

        $version = new Docman_Version(array('id'        => null,
                                            'user_id'   => null,
                                            'item_id'   => null,
                                            'number'    => null,
                                            'label'     => null,
                                            'changelog' => null,
                                            'date'      => null,
                                            'filename'  => 'fileToPurge.txt',
                                            'filesize'  => null,
                                            'filetype'  => null,
                                            'path'      => dirname(__FILE__).'/_fixtures/fileToPurge_txt'));

        $fp = fopen($version->getPath(), 'w');
        $versionFactory->expectOnce('archiveBeforePurge', array($version));

        $this->assertTrue($versionFactory->PurgeDeletedVersion($version));
        $this->assertFalse(file_exists($version->getPath()));
    }

    function testRestoreOneVersion() {
        $filePath       = dirname(__FILE__).'/_fixtures/version.test';
        touch($filePath);
        $versionFactory = new Docman_VersionFactoryTestVersion($this);
        $dao            = new MockDocman_VersionDao($this);
        $versionFactory->setReturnValue('_getVersionDao', $dao);

        $dar = new MockDataAccessResult($this);
        $dar->setReturnValue('isError', false);
        $dar->setReturnValue('getRow', array('purge_date' => null, 'label' => 'Ho hisse la saucisse', 'path' => $filePath));
        $dao->expectOnce('searchDeletedVersion', array(1664, 2));
        $dao->setReturnValue('searchDeletedVersion', $dar);

        $file = new MockDocman_File($this);
        $file->setReturnValue('getGroupId', 114);
        $if = new MockDocman_ItemFactory($this);
        $if->expectOnce('getItemFromDb', array(1664, array('ignore_deleted' => true)));
        $if->setReturnValue('getItemFromDb', $file);
        $versionFactory->setReturnValue('_getItemFactory', $if);
        
        $user = mock('PFUser');
        $um   = new MockUserManager($this);
        $um->setReturnValue('getCurrentUser', $user);
        $versionFactory->setReturnValue('_getUserManager', $um);
        
        $em = new MockEventManager($this);
        $em->expectOnce('processEvent', array('plugin_docman_event_restore_version', array('group_id' => 114, 'item' => $file, 'old_value' => '2 (Ho hisse la saucisse)', 'user' => $user)));
        $versionFactory->setReturnValue('_getEventManager', $em);

        $dao->expectOnce('restore', array(1664, 2));
        $dao->setReturnValue('restore', true);

        $version = new MockDocman_Version($this);
        $version->setReturnValue('getNumber', 2);
        $version->setReturnValue('getItemId', 1664);

        $this->assertTrue($versionFactory->restore($version));
        unlink($filePath);
    }

    function testRestoreOneVersionButFileIsDeleted() {
        $filePath       = dirname(__FILE__).'/_fixtures/version.test';
        $versionFactory = new Docman_VersionFactoryTestVersion($this);
        $dao            = new MockDocman_VersionDao($this);
        $versionFactory->setReturnValue('_getVersionDao', $dao);

        $dar = new MockDataAccessResult($this);
        $dar->setReturnValue('isError', false);
        $dar->setReturnValue('getRow', array('purge_date' => null, 'path' => $filePath));
        $dao->expectOnce('searchDeletedVersion', array(1664, 2));
        $dao->setReturnValue('searchDeletedVersion', $dar);

        $em = new MockEventManager($this);
        $em->expectNever('processEvent', array('plugin_docman_event_restore_version'));
        $versionFactory->setReturnValue('_getEventManager', $em);

        $dao->expectNever('restore', array(1664, 2));

        $version = new MockDocman_Version($this);
        $version->setReturnValue('getNumber', 2);
        $version->setReturnValue('getItemId', 1664);
        $version->setReturnValue('getPath', $filePath);

        $this->assertFalse($versionFactory->restore($version));
    }

    function testRestoreOneVersionAlreadyPurged() {
        $filePath       = dirname(__FILE__).'/_fixtures/version.test';
        $versionFactory = new Docman_VersionFactoryTestVersion($this);
        $dao            = new MockDocman_VersionDao($this);
        $versionFactory->setReturnValue('_getVersionDao', $dao);

        $dar = new MockDataAccessResult($this);
        $dar->setReturnValue('isError', false);
        $dar->setReturnValue('getRow', array('purge_date' => 1234567890, 'path' => $filePath));
        $dao->expectOnce('searchDeletedVersion', array(1664, 2));
        $dao->setReturnValue('searchDeletedVersion', $dar);

        $em = new MockEventManager($this);
        $em->expectNever('processEvent', array('plugin_docman_event_restore_version'));
        $versionFactory->setReturnValue('_getEventManager', $em);
        
        $dao->expectNever('restore');

        $version = new MockDocman_Version($this);
        $version->setReturnValue('getNumber', 2);
        $version->setReturnValue('getItemId', 1664);

        $this->assertFalse($versionFactory->restore($version));
    }

}

?>