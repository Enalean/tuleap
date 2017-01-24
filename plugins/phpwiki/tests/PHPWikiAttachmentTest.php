<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
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
require_once(dirname(__FILE__) . '/../include/lib/PHPWikiAttachment.class.php');

Mock::generate('PHPWikiAttachmentDao');
Mock::generatePartial('PHPWikiAttachment', 'PHPWikiAttachmentTestVersion', array('initWithId', 'dbadd', 'getDao', 'isActive'));
Mock::generatePartial('PHPWikiAttachment', 'PHPWikiAttachmentTestVersionRestoreDeleted', array('initWithId', 'getDao', 'isActive', 'exist'));

class PHPWikiAttachmentTest extends TuleapTestCase {

    public function tearDown() {
        clearstatcache();
        parent::tearDown();
    }

    function testGetFilesystemName() {
        $wa = new PHPWikiAttachmentTestVersion();
        $wa->setFilename('toto.txt');
        $this->assertEqual('toto.txt', $wa->getFilesystemName());
        $wa->initFilesystemName();
        $this->assertNotEqual('toto.txt', $wa->getFilesystemName());
        $this->assertPattern('/toto.txt_[0..9]*/', $wa->getFilesystemName());
        $wa->filesystemName = 'titi.txt';
        $this->assertNotEqual('toto.txt', $wa->getFilesystemName());
        $this->assertNoPattern('/toto.txt_[0..9]*/', $wa->getFilesystemName());
        $this->assertEqual('titi.txt', $wa->getFilesystemName());
    }

    function testCreateNoFilesystemName() {
        $wa = new PHPWikiAttachmentTestVersion();
        $wa->setFilename('testing.txt');
        $wa->basedir = dirname(__FILE__).'/_fixtures';
        $wa->setReturnValue('dbadd', true);

        $this->assertFalse(is_dir($wa->basedir.'/testing.txt'));
        $this->assertFalse($wa->exist());
        $this->assertTrue($wa->create());
        $this->assertTrue(is_dir($wa->basedir.'/testing.txt'));
        $this->assertTrue($wa->exist());
        rmdir($wa->basedir.'/testing.txt');
    }

    function testCreateFolderAlreadyExistNoFilesystemName() {
        $wa = new PHPWikiAttachmentTestVersion();
        $wa->setFilename('toto.txt');
        $wa->basedir = dirname(__FILE__).'/_fixtures';
        $wa->setReturnValue('dbadd', true);

        $this->assertTrue(is_dir($wa->basedir.'/toto.txt'));
        $this->assertTrue($wa->exist());
        $this->assertTrue($wa->create());
        $this->assertTrue(is_dir($wa->basedir.'/toto.txt'));
        $this->assertTrue($wa->exist());
    }

    function testCreateFolderAlreadyExistWithFilesystemName() {
        $wa = new PHPWikiAttachmentTestVersion();
        $wa->setFilename('testing.txt');
        $wa->initFilesystemName();
        $wa->basedir = dirname(__FILE__).'/_fixtures';
        $wa->setReturnValue('dbadd', true);
        mkdir($wa->basedir.'/'.$wa->getFilesystemName());

        $this->assertTrue(is_dir($wa->basedir.'/'.$wa->getFilesystemName()));
        $this->assertTrue($wa->exist());
        $this->assertTrue($wa->create());
        $this->assertFalse(is_dir($wa->basedir.'/testing.txt'));
        $this->assertTrue(is_dir($wa->basedir.'/'.$wa->getFilesystemName()));
        $this->assertTrue($wa->exist());
        rmdir($wa->basedir.'/'.$wa->getFilesystemName());
    }

    function testCreateWithFilesystemName() {
        $wa = new PHPWikiAttachmentTestVersion();
        $wa->setFilename('testing.txt');
        $wa->initFilesystemName();
        $wa->basedir = dirname(__FILE__).'/_fixtures';
        $wa->setReturnValue('dbadd', true);

        $this->assertFalse(is_dir($wa->basedir.'/'.$wa->getFilesystemName()));
        $this->assertFalse($wa->exist());
        $this->assertTrue($wa->create());
        $this->assertFalse(is_dir($wa->basedir.'/testing.txt'));
        $this->assertTrue(is_dir($wa->basedir.'/'.$wa->getFilesystemName()));
        $this->assertTrue($wa->exist());
        rmdir($wa->basedir.'/'.$wa->getFilesystemName());
    }

    function testPurgeAttachmentSucceeded() {
        $wa = new PHPWikiAttachmentTestVersion();
        $wa->setFilename('testing.txt');
        $wa->initFilesystemName();
        $wa->basedir = dirname(__FILE__).'/_fixtures';
        mkdir($wa->basedir.'/'.$wa->getFilesystemName());
        touch($wa->basedir.'/'.$wa->getFilesystemName().'/0');

        $dao = new MockPHPWikiAttachmentDao($this);
        $wa->setReturnValue('getDao', $dao);
        $dao->setReturnValue('setPurgeDate', true);
        $dao->expectOnce('setPurgeDate');

        $this->assertTrue($wa->purgeAttachment());
        $this->assertFalse($wa->exist());
    }

    function testPurgeAttachmentDBFailure() {
        $wa = new PHPWikiAttachmentTestVersion();
        $wa->setFilename('testing.txt');
        $wa->initFilesystemName();
        $wa->basedir = dirname(__FILE__).'/_fixtures';
        mkdir($wa->basedir.'/'.$wa->getFilesystemName());
        touch($wa->basedir.'/'.$wa->getFilesystemName().'/0');
        touch($wa->basedir.'/'.$wa->getFilesystemName().'/1');

        $dao = new MockPHPWikiAttachmentDao($this);
        $wa->setReturnValue('getDao', $dao);
        $dao->setReturnValue('setPurgeDate', false);
        $dao->expectOnce('setPurgeDate');

        $this->assertFalse($wa->purgeAttachment());
        $this->assertFalse($wa->exist());
    }

    function testDeleteAttachmntSuccess() {
        $wa = new PHPWikiAttachmentTestVersion();
        $wa->setReturnValue('isActive', true);

        $dao = new MockPHPWikiAttachmentDao($this);
        $wa->setReturnValue('getDao', $dao);
        $dao->setReturnValue('delete', true);
        $dao->expectOnce('delete');
        $this->assertTrue($wa->deleteAttachment());
    }

    function testDeleteAttachmentNotActive() {
        $wa = new PHPWikiAttachmentTestVersion();
        $wa->setReturnValue('isActive', false);

        $dao = new MockPHPWikiAttachmentDao($this);
        $wa->setReturnValue('getDao', $dao);
        $dao->expectNever('delete');
        $this->assertFalse($wa->deleteAttachment());
    }

    function testDeleteAttachmentDBFailure() {
        $wa = new PHPWikiAttachmentTestVersion();
        $wa->setReturnValue('isActive', true);

        $dao = new MockPHPWikiAttachmentDao($this);
        $wa->setReturnValue('getDao', $dao);
        $dao->setReturnValue('delete', false);
        $dao->expectOnce('delete');
        $this->assertFalse($wa->deleteAttachment());
    }

    function testRestoreDeletedAttachmentActiveFileFailure() {
        $wa = new PHPWikiAttachmentTestVersionRestoreDeleted();
        $wa->setReturnValue('isActive', true);
        $wa->setReturnValue('exist', true);

        $dao = new MockPHPWikiAttachmentDao($this);
        $wa->setReturnValue('getDao', $dao);
        $dao->expectNever('restoreAttachment');
        $this->assertFalse($wa->restoreDeletedAttachment(1));
    }

    function testRestoreDeletedAttachmentFileSystemFailure() {
        $wa = new PHPWikiAttachmentTestVersionRestoreDeleted();
        $wa->setReturnValue('isActive', False);
        $wa->setReturnValue('exist', False);

        $dao = new MockPHPWikiAttachmentDao($this);
        $wa->setReturnValue('getDao', $dao);
        $dao->expectNever('restoreAttachment');
        $this->assertFalse($wa->restoreDeletedAttachment(1));
    }

    function testRestoreDeletedAttachmentDaoFailure() {
        $wa = new PHPWikiAttachmentTestVersionRestoreDeleted();
        $wa->setReturnValue('isActive', False);
        $wa->setReturnValue('exist', true);

        $dao = new MockPHPWikiAttachmentDao($this);
        $wa->setReturnValue('getDao', $dao);
        $dao->setReturnValue('restoreAttachment', False);
        $this->assertFalse($wa->restoreDeletedAttachment(1));
    }

    function testRestoreDeletedAttachmentSuccess() {
        $wa = new PHPWikiAttachmentTestVersionRestoreDeleted();
        $wa->setReturnValue('isActive', False);
        $wa->setReturnValue('exist', true);

        $dao = new MockPHPWikiAttachmentDao($this);
        $wa->setReturnValue('getDao', $dao);
        $dao->setReturnValue('restoreAttachment', true);
        $this->assertTrue($wa->restoreDeletedAttachment(1));
    }
}
