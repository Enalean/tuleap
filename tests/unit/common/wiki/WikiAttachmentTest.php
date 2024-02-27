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

declare(strict_types=1);

use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\PHPUnit\TestCase;

require_once __DIR__ . '/../../bootstrap.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class WikiAttachmentTest extends TestCase
{
    use TemporaryTestDirectory;

    protected function tearDown(): void
    {
        clearstatcache();
        parent::tearDown();
    }

    public function testGetFilesystemName(): void
    {
        $wa = $this->createPartialMock(WikiAttachment::class, ['initWithId']);
        $wa->method('initWithId');
        $wa->setFilename('toto.txt');
        self::assertEquals('toto.txt', $wa->getFilesystemName());
        $wa->initFilesystemName();
        self::assertNotEquals('toto.txt', $wa->getFilesystemName());
        self::assertMatchesRegularExpression('/toto.txt_[0..9]*/', $wa->getFilesystemName());
        $wa->filesystemName = 'titi.txt';
        self::assertNotEquals('toto.txt', $wa->getFilesystemName());
        self::assertDoesNotMatchRegularExpression('/toto.txt_[0..9]*/', $wa->getFilesystemName());
        self::assertEquals('titi.txt', $wa->getFilesystemName());
    }

    public function testCreateNoFilesystemName(): void
    {
        $wa = $this->createPartialMock(WikiAttachment::class, ['initWithId', 'dbadd']);
        $wa->method('initWithId');
        $wa->setFilename('testing.txt');
        $wa->basedir = $this->getTmpDir();
        $wa->method('dbadd')->willReturn(true);

        self::assertFalse(is_dir($wa->basedir . '/testing.txt'));
        self::assertFalse($wa->exist());
        self::assertTrue($wa->create());
        self::assertTrue(is_dir($wa->basedir . '/testing.txt'));
        self::assertTrue($wa->exist());
        rmdir($wa->basedir . '/testing.txt');
    }

    public function testCreateFolderAlreadyExistNoFilesystemName(): void
    {
        $wa = $this->createPartialMock(WikiAttachment::class, ['initWithId', 'dbadd']);
        $wa->method('initWithId');
        $wa->setFilename('toto.txt');
        $wa->basedir = $this->getTmpDir();
        mkdir("$wa->basedir/toto.txt");
        $wa->method('dbadd')->willReturn(true);

        self::assertTrue(is_dir($wa->basedir . '/toto.txt'));
        self::assertTrue($wa->exist());
        self::assertTrue($wa->create());
        self::assertTrue(is_dir($wa->basedir . '/toto.txt'));
        self::assertTrue($wa->exist());
    }

    public function testCreateFolderAlreadyExistWithFilesystemName(): void
    {
        $wa = $this->createPartialMock(WikiAttachment::class, ['initWithId', 'dbadd']);
        $wa->method('initWithId');
        $wa->setFilename('testing.txt');
        $wa->initFilesystemName();
        $wa->basedir = $this->getTmpDir();
        $wa->method('dbadd')->willReturn(true);
        mkdir($wa->basedir . '/' . $wa->getFilesystemName());

        self::assertTrue(is_dir($wa->basedir . '/' . $wa->getFilesystemName()));
        self::assertTrue($wa->exist());
        self::assertTrue($wa->create());
        self::assertFalse(is_dir($wa->basedir . '/testing.txt'));
        self::assertTrue(is_dir($wa->basedir . '/' . $wa->getFilesystemName()));
        self::assertTrue($wa->exist());
        rmdir($wa->basedir . '/' . $wa->getFilesystemName());
    }

    public function testCreateWithFilesystemName(): void
    {
        $wa = $this->createPartialMock(WikiAttachment::class, ['dbadd']);
        $wa->setFilename('testing.txt');
        $wa->initFilesystemName();
        $wa->basedir = $this->getTmpDir();
        $wa->method('dbadd')->willReturn(true);

        self::assertFalse(is_dir($wa->basedir . '/' . $wa->getFilesystemName()));
        self::assertFalse($wa->exist());
        self::assertTrue($wa->create());
        self::assertFalse(is_dir($wa->basedir . '/testing.txt'));
        self::assertTrue(is_dir($wa->basedir . '/' . $wa->getFilesystemName()));
        self::assertTrue($wa->exist());
        rmdir($wa->basedir . '/' . $wa->getFilesystemName());
    }

    public function testPurgeAttachmentSucceeded(): void
    {
        $wa = $this->createPartialMock(WikiAttachment::class, ['initWithId', 'getDao']);
        $wa->method('initWithId');
        $wa->setFilename('testing.txt');
        $wa->initFilesystemName();
        $wa->basedir = $this->getTmpDir();
        mkdir($wa->basedir . '/' . $wa->getFilesystemName());
        touch($wa->basedir . '/' . $wa->getFilesystemName() . '/0');

        $dao = $this->createMock(WikiAttachmentDao::class);
        $wa->method('getDao')->willReturn($dao);
        $dao->expects(self::once())->method('setPurgeDate')->willReturn(true);

        self::assertTrue($wa->purgeAttachment());
        self::assertFalse($wa->exist());
    }

    public function testPurgeAttachmentDBFailure(): void
    {
        $wa = $this->createPartialMock(WikiAttachment::class, ['initWithId', 'getDao']);
        $wa->method('initWithId');
        $wa->setFilename('testing.txt');
        $wa->initFilesystemName();
        $wa->basedir = $this->getTmpDir();
        mkdir($wa->basedir . '/' . $wa->getFilesystemName());
        touch($wa->basedir . '/' . $wa->getFilesystemName() . '/0');
        touch($wa->basedir . '/' . $wa->getFilesystemName() . '/1');

        $dao = $this->createMock(WikiAttachmentDao::class);
        $wa->method('getDao')->willReturn($dao);
        $dao->expects(self::once())->method('setPurgeDate')->willReturn(false);

        self::assertFalse($wa->purgeAttachment());
        self::assertFalse($wa->exist());
    }

    public function testDeleteAttachmentSuccess(): void
    {
        $wa = $this->createPartialMock(WikiAttachment::class, ['isActive', 'getDao']);
        $wa->method('isActive')->willReturn(true);

        $dao = $this->createMock(WikiAttachmentDao::class);
        $wa->method('getDao')->willReturn($dao);
        $dao->expects(self::once())->method('delete')->willReturn(true);
        self::assertTrue($wa->deleteAttachment());
    }

    public function testDeleteAttachmentNotActive(): void
    {
        $wa = $this->createPartialMock(WikiAttachment::class, ['isActive', 'getDao']);
        $wa->method('isActive')->willReturn(false);

        $dao = $this->createMock(WikiAttachmentDao::class);
        $wa->method('getDao')->willReturn($dao);
        $dao->expects(self::never())->method('delete');
        self::assertFalse($wa->deleteAttachment());
    }

    public function testDeleteAttachmentDBFailure(): void
    {
        $wa = $this->createPartialMock(WikiAttachment::class, ['isActive', 'getDao']);
        $wa->method('isActive')->willReturn(true);

        $dao = $this->createMock(WikiAttachmentDao::class);
        $wa->method('getDao')->willReturn($dao);
        $dao->expects(self::once())->method('delete')->willReturn(false);
        self::assertFalse($wa->deleteAttachment());
    }

    public function testRestoreDeletedAttachmentActiveFileFailure(): void
    {
        $wa = $this->createPartialMock(WikiAttachment::class, ['initWithId', 'isActive', 'exist', 'getDao']);
        $wa->method('initWithId');
        $wa->method('isActive')->willReturn(true);
        $wa->method('exist')->willReturn(true);

        $dao = $this->createMock(WikiAttachmentDao::class);
        $wa->method('getDao')->willReturn($dao);
        $dao->expects(self::never())->method('restoreAttachment');
        self::assertFalse($wa->restoreDeletedAttachment(1));
    }

    public function testRestoreDeletedAttachmentFileSystemFailure(): void
    {
        $wa = $this->createPartialMock(WikiAttachment::class, ['initWithId', 'isActive', 'exist', 'getDao']);
        $wa->method('initWithId');
        $wa->method('isActive')->willReturn(false);
        $wa->method('exist')->willReturn(false);

        $dao = $this->createMock(WikiAttachmentDao::class);
        $wa->method('getDao')->willReturn($dao);
        $dao->expects(self::never())->method('restoreAttachment');
        self::assertFalse($wa->restoreDeletedAttachment(1));
    }

    public function testRestoreDeletedAttachmentDaoFailure(): void
    {
        $wa = $this->createPartialMock(WikiAttachment::class, ['initWithId', 'isActive', 'exist', 'getDao']);
        $wa->method('initWithId');
        $wa->method('isActive')->willReturn(false);
        $wa->method('exist')->willReturn(true);

        $dao = $this->createMock(WikiAttachmentDao::class);
        $wa->method('getDao')->willReturn($dao);
        $dao->method('restoreAttachment')->willReturn(false);
        self::assertFalse($wa->restoreDeletedAttachment(1));
    }

    public function testRestoreDeletedAttachmentSuccess(): void
    {
        $wa = $this->createPartialMock(WikiAttachment::class, ['initWithId', 'isActive', 'exist', 'getDao']);
        $wa->method('initWithId');
        $wa->method('isActive')->willReturn(false);
        $wa->method('exist')->willReturn(true);

        $dao = $this->createMock(WikiAttachmentDao::class);
        $wa->method('getDao')->willReturn($dao);
        $dao->method('restoreAttachment')->willReturn(true);
        self::assertTrue($wa->restoreDeletedAttachment(1));
    }
}
