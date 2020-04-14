<?php
/**
 * Copyright (c) Enalean, 2012 – Present. All Rights Reserved.
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

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../bootstrap.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class WikiAttachmentTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use \Tuleap\TemporaryTestDirectory;

    protected function tearDown(): void
    {
        clearstatcache();
        parent::tearDown();
    }

    public function testGetFilesystemName(): void
    {
        $wa = \Mockery::mock(\WikiAttachment::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $wa->shouldReceive('initWithId');
        $wa->setFilename('toto.txt');
        $this->assertEquals('toto.txt', $wa->getFilesystemName());
        $wa->initFilesystemName();
        $this->assertNotEquals('toto.txt', $wa->getFilesystemName());
        $this->assertMatchesRegularExpression('/toto.txt_[0..9]*/', $wa->getFilesystemName());
        $wa->filesystemName = 'titi.txt';
        $this->assertNotEquals('toto.txt', $wa->getFilesystemName());
        $this->assertDoesNotMatchRegularExpression('/toto.txt_[0..9]*/', $wa->getFilesystemName());
        $this->assertEquals('titi.txt', $wa->getFilesystemName());
    }

    public function testCreateNoFilesystemName(): void
    {
        $wa = \Mockery::mock(\WikiAttachment::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $wa->shouldReceive('initWithId');
        $wa->setFilename('testing.txt');
        $wa->basedir = $this->getTmpDir();
        $wa->shouldReceive('dbadd')->andReturns(true);

        $this->assertFalse(is_dir($wa->basedir . '/testing.txt'));
        $this->assertFalse($wa->exist());
        $this->assertTrue($wa->create());
        $this->assertTrue(is_dir($wa->basedir . '/testing.txt'));
        $this->assertTrue($wa->exist());
        rmdir($wa->basedir . '/testing.txt');
    }

    public function testCreateFolderAlreadyExistNoFilesystemName(): void
    {
        $wa = \Mockery::mock(\WikiAttachment::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $wa->shouldReceive('initWithId');
        $wa->setFilename('toto.txt');
        $wa->basedir = $this->getTmpDir();
        mkdir("$wa->basedir/toto.txt");
        $wa->shouldReceive('dbadd')->andReturns(true);

        $this->assertTrue(is_dir($wa->basedir . '/toto.txt'));
        $this->assertTrue($wa->exist());
        $this->assertTrue($wa->create());
        $this->assertTrue(is_dir($wa->basedir . '/toto.txt'));
        $this->assertTrue($wa->exist());
    }

    public function testCreateFolderAlreadyExistWithFilesystemName(): void
    {
        $wa = \Mockery::mock(\WikiAttachment::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $wa->shouldReceive('initWithId');
        $wa->setFilename('testing.txt');
        $wa->initFilesystemName();
        $wa->basedir = $this->getTmpDir();
        $wa->shouldReceive('dbadd')->andReturns(true);
        mkdir($wa->basedir . '/' . $wa->getFilesystemName());

        $this->assertTrue(is_dir($wa->basedir . '/' . $wa->getFilesystemName()));
        $this->assertTrue($wa->exist());
        $this->assertTrue($wa->create());
        $this->assertFalse(is_dir($wa->basedir . '/testing.txt'));
        $this->assertTrue(is_dir($wa->basedir . '/' . $wa->getFilesystemName()));
        $this->assertTrue($wa->exist());
        rmdir($wa->basedir . '/' . $wa->getFilesystemName());
    }

    public function testCreateWithFilesystemName(): void
    {
        $wa = \Mockery::mock(\WikiAttachment::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $wa->setFilename('testing.txt');
        $wa->initFilesystemName();
        $wa->basedir = $this->getTmpDir();
        $wa->shouldReceive('dbadd')->andReturns(true);

        $this->assertFalse(is_dir($wa->basedir . '/' . $wa->getFilesystemName()));
        $this->assertFalse($wa->exist());
        $this->assertTrue($wa->create());
        $this->assertFalse(is_dir($wa->basedir . '/testing.txt'));
        $this->assertTrue(is_dir($wa->basedir . '/' . $wa->getFilesystemName()));
        $this->assertTrue($wa->exist());
        rmdir($wa->basedir . '/' . $wa->getFilesystemName());
    }

    public function testPurgeAttachmentSucceeded(): void
    {
        $wa = \Mockery::mock(\WikiAttachment::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $wa->shouldReceive('initWithId');
        $wa->setFilename('testing.txt');
        $wa->initFilesystemName();
        $wa->basedir = $this->getTmpDir();
        mkdir($wa->basedir . '/' . $wa->getFilesystemName());
        touch($wa->basedir . '/' . $wa->getFilesystemName() . '/0');

        $dao = \Mockery::spy(\WikiAttachmentDao::class);
        $wa->shouldReceive('getDao')->andReturns($dao);
        $dao->shouldReceive('setPurgeDate')->once()->andReturns(true);

        $this->assertTrue($wa->purgeAttachment());
        $this->assertFalse($wa->exist());
    }

    public function testPurgeAttachmentDBFailure(): void
    {
        $wa = \Mockery::mock(\WikiAttachment::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $wa->shouldReceive('initWithId');
        $wa->setFilename('testing.txt');
        $wa->initFilesystemName();
        $wa->basedir = $this->getTmpDir();
        mkdir($wa->basedir . '/' . $wa->getFilesystemName());
        touch($wa->basedir . '/' . $wa->getFilesystemName() . '/0');
        touch($wa->basedir . '/' . $wa->getFilesystemName() . '/1');

        $dao = \Mockery::spy(\WikiAttachmentDao::class);
        $wa->shouldReceive('getDao')->andReturns($dao);
        $dao->shouldReceive('setPurgeDate')->once()->andReturns(false);

        $this->assertFalse($wa->purgeAttachment());
        $this->assertFalse($wa->exist());
    }

    public function testDeleteAttachmntSuccess(): void
    {
        $wa = \Mockery::mock(\WikiAttachment::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $wa->shouldReceive('isActive')->andReturns(true);

        $dao = \Mockery::spy(\WikiAttachmentDao::class);
        $wa->shouldReceive('getDao')->andReturns($dao);
        $dao->shouldReceive('delete')->once()->andReturns(true);
        $this->assertTrue($wa->deleteAttachment());
    }

    public function testDeleteAttachmentNotActive(): void
    {
        $wa = \Mockery::mock(\WikiAttachment::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $wa->shouldReceive('isActive')->andReturns(false);

        $dao = \Mockery::spy(\WikiAttachmentDao::class);
        $wa->shouldReceive('getDao')->andReturns($dao);
        $dao->shouldReceive('delete')->never();
        $this->assertFalse($wa->deleteAttachment());
    }

    public function testDeleteAttachmentDBFailure(): void
    {
        $wa = \Mockery::mock(\WikiAttachment::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $wa->shouldReceive('isActive')->andReturns(true);

        $dao = \Mockery::spy(\WikiAttachmentDao::class);
        $wa->shouldReceive('getDao')->andReturns($dao);
        $dao->shouldReceive('delete')->once()->andReturns(false);
        $this->assertFalse($wa->deleteAttachment());
    }

    public function testRestoreDeletedAttachmentActiveFileFailure(): void
    {
        $wa = \Mockery::mock(\WikiAttachment::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $wa->shouldReceive('initWithId');
        $wa->shouldReceive('isActive')->andReturns(true);
        $wa->shouldReceive('exist')->andReturns(true);

        $dao = \Mockery::spy(\WikiAttachmentDao::class);
        $wa->shouldReceive('getDao')->andReturns($dao);
        $dao->shouldReceive('restoreAttachment')->never();
        $this->assertFalse($wa->restoreDeletedAttachment(1));
    }

    public function testRestoreDeletedAttachmentFileSystemFailure(): void
    {
        $wa = \Mockery::mock(\WikiAttachment::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $wa->shouldReceive('initWithId');
        $wa->shouldReceive('isActive')->andReturns(false);
        $wa->shouldReceive('exist')->andReturns(false);

        $dao = \Mockery::spy(\WikiAttachmentDao::class);
        $wa->shouldReceive('getDao')->andReturns($dao);
        $dao->shouldReceive('restoreAttachment')->never();
        $this->assertFalse($wa->restoreDeletedAttachment(1));
    }

    public function testRestoreDeletedAttachmentDaoFailure(): void
    {
        $wa = \Mockery::mock(\WikiAttachment::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $wa->shouldReceive('initWithId');
        $wa->shouldReceive('isActive')->andReturns(false);
        $wa->shouldReceive('exist')->andReturns(true);

        $dao = \Mockery::spy(\WikiAttachmentDao::class);
        $wa->shouldReceive('getDao')->andReturns($dao);
        $dao->shouldReceive('restoreAttachment')->andReturns(false);
        $this->assertFalse($wa->restoreDeletedAttachment(1));
    }

    public function testRestoreDeletedAttachmentSuccess(): void
    {
        $wa = \Mockery::mock(\WikiAttachment::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $wa->shouldReceive('initWithId');
        $wa->shouldReceive('isActive')->andReturns(false);
        $wa->shouldReceive('exist')->andReturns(true);

        $dao = \Mockery::spy(\WikiAttachmentDao::class);
        $wa->shouldReceive('getDao')->andReturns($dao);
        $dao->shouldReceive('restoreAttachment')->andReturns(true);
        $this->assertTrue($wa->restoreDeletedAttachment(1));
    }
}
