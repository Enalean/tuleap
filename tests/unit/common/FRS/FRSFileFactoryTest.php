<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\FRS;

use Backend;
use BackendSystem;
use DataAccessResult;
use EventManager;
use Exception;
use ForgeConfig;
use FRSFile;
use FRSFileDao;
use FRSFileDbException;
use FRSFileExistsException;
use FRSFileFactory;
use FRSFileForgeException;
use FRSFileIllegalNameException;
use FRSFileInvalidNameException;
use FRSFileMD5SumException;
use FRSFileToBeRestoredException;
use FRSPackageFactory;
use FRSRelease;
use FRSReleaseFactory;
use Project;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use RuntimeException;
use TestHelper;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use UserManager;

class FRSFileFactoryTest extends TestCase
{
    use ForgeConfigSandbox;
    use TemporaryTestDirectory;
    use GlobalLanguageMock;

    protected function setUp(): void
    {
        ForgeConfig::set('ftp_frs_dir_prefix', $this->getTmpDir());
        ForgeConfig::set('ftp_incoming_dir', $this->getTmpDir());
        copy(__DIR__ . '/_fixtures/file_sample', ForgeConfig::get('ftp_incoming_dir') . '/file_sample');
        $GLOBALS['Language']->method('getText')->willReturn('');
    }

    public function testGetUploadSubDirectory()
    {
        $package_id = random_int(1, 1000);
        $release_id = random_int(1, 1000);

        $release = new FRSRelease();
        $release->setPackageID($package_id);
        $release->setReleaseID($release_id);

        $file_fact = new FRSFileFactory();

        $sub_dir = $file_fact->getUploadSubDirectory($release);
        self::assertEquals($sub_dir, 'p' . $package_id . '_r' . $release_id);
    }

    public function testPurgeDeletedFiles()
    {
        $ff = $this->createPartialMock(FRSFileFactory::class, [
            'moveDeletedFilesToStagingArea',
            'purgeFiles',
            'cleanStaging',
            'restoreDeletedFiles',
        ]);
        $ff->setLogger($this->createMock(LoggerInterface::class));
        $backend = $this->createMock(BackendSystem::class);
        $ff->expects(self::once())->method('moveDeletedFilesToStagingArea')->willReturn(true);
        $ff->expects(self::once())->method('purgeFiles')->with(1287504083, $backend)->willReturn(true);
        $ff->expects(self::once())->method('cleanStaging')->willReturn(true);
        $ff->expects(self::once())->method('restoreDeletedFiles')->with($backend)->willReturn(true);

        self::assertTrue($ff->moveFiles(1287504083, $backend));
    }

    public function testMoveFilesMoveStagingError()
    {
        $ff = $this->createPartialMock(FRSFileFactory::class, [
            'moveDeletedFilesToStagingArea',
            'purgeFiles',
            'cleanStaging',
            'restoreDeletedFiles',
        ]);
        $ff->setLogger($this->createMock(LoggerInterface::class));
        $backend = $this->createMock(BackendSystem::class);
        $ff->expects(self::once())->method('moveDeletedFilesToStagingArea')->willReturn(false);
        $ff->expects(self::once())->method('purgeFiles')->with(1287504083, $backend)->willReturn(true);
        $ff->expects(self::once())->method('cleanStaging')->willReturn(true);
        $ff->expects(self::once())->method('restoreDeletedFiles')->with($backend)->willReturn(true);

        self::assertFalse($ff->moveFiles(1287504083, $backend));
    }

    public function testMoveFilesPurgeError()
    {
        $ff = $this->createPartialMock(FRSFileFactory::class, [
            'moveDeletedFilesToStagingArea',
            'purgeFiles',
            'cleanStaging',
            'restoreDeletedFiles',
        ]);
        $ff->setLogger($this->createMock(LoggerInterface::class));
        $backend = $this->createMock(BackendSystem::class);
        $ff->expects(self::once())->method('moveDeletedFilesToStagingArea')->willReturn(true);
        $ff->expects(self::once())->method('purgeFiles')->with(1287504083, $backend)->willReturn(false);
        $ff->expects(self::once())->method('cleanStaging')->willReturn(true);
        $ff->expects(self::once())->method('restoreDeletedFiles')->with($backend)->willReturn(true);

        self::assertFalse($ff->moveFiles(1287504083, $backend));
    }

    public function testMoveFilesCleanStagingError()
    {
        $ff = $this->createPartialMock(FRSFileFactory::class, [
            'moveDeletedFilesToStagingArea',
            'purgeFiles',
            'cleanStaging',
            'restoreDeletedFiles',
        ]);
        $ff->setLogger($this->createMock(LoggerInterface::class));
        $backend = $this->createMock(BackendSystem::class);
        $ff->expects(self::once())->method('moveDeletedFilesToStagingArea')->willReturn(true);
        $ff->expects(self::once())->method('purgeFiles')->with(1287504083, $backend)->willReturn(true);
        $ff->expects(self::once())->method('cleanStaging')->willReturn(false);
        $ff->expects(self::once())->method('restoreDeletedFiles')->with($backend)->willReturn(true);

        self::assertFalse($ff->moveFiles(1287504083, $backend));
    }

    public function testMoveFilesRestoreDeletedError()
    {
        $ff = $this->createPartialMock(FRSFileFactory::class, [
            'moveDeletedFilesToStagingArea',
            'purgeFiles',
            'cleanStaging',
            'restoreDeletedFiles',
        ]);
        $ff->setLogger($this->createMock(LoggerInterface::class));
        $backend = $this->createMock(BackendSystem::class);
        $ff->expects(self::once())->method('moveDeletedFilesToStagingArea')->willReturn(true);
        $ff->expects(self::once())->method('purgeFiles')->with(1287504083, $backend)->willReturn(true);
        $ff->expects(self::once())->method('cleanStaging')->willReturn(true);
        $ff->expects(self::once())->method('restoreDeletedFiles')->with($backend)->willReturn(false);

        self::assertFalse($ff->moveFiles(1287504083, $backend));
    }

    public function testMoveFilesCatchesExceptionAndLogThem()
    {
        $ff = $this->createPartialMock(FRSFileFactory::class, [
            'purgeFiles',
            'moveDeletedFilesToStagingArea',
            'cleanStaging',
            'restoreDeletedFiles',
        ]);
        $ff->setLogger($this->createMock(LoggerInterface::class));
        $ff->method('purgeFiles')->willThrowException(new RuntimeException("Error while doing things"));
        $ff->method('moveDeletedFilesToStagingArea')->willReturn(true);
        $ff->method('cleanStaging')->willReturn(true);
        $ff->method('restoreDeletedFiles')->willReturn(true);

        $backend = $this->createMock(BackendSystem::class);
        $backend->expects(self::once())->method('log')->with("Error while doing things", Backend::LOG_ERROR);

        self::assertFalse($ff->moveFiles(1287504083, $backend));
    }

    public function testMoveDeletedFilesToStagingAreaWithNoFiles()
    {
        $ff = $this->createPartialMock(FRSFileFactory::class, [
            '_getFRSFileDao',
            'moveDeletedFileToStagingArea',
        ]);
        $ff->setLogger($this->createMock(LoggerInterface::class));

        $dao = $this->createMock(FRSFileDao::class);
        $dao->method('searchStagingCandidates')->willReturn(TestHelper::emptyDar());
        $ff->method('_getFRSFileDao')->willReturn($dao);
        $backend = $this->createMock(BackendSystem::class);

        $ff->expects(self::never())->method('moveDeletedFileToStagingArea');

        self::assertTrue($ff->moveDeletedFilesToStagingArea($backend));
    }

    private function createReleaseDir($release_name, $dir_name)
    {
        // Create temp file in a fake release
        if (! is_dir(ForgeConfig::get('ftp_frs_dir_prefix') . "/$release_name/$dir_name")) {
            mkdir(ForgeConfig::get('ftp_frs_dir_prefix') . "/$release_name/$dir_name", 0750, true);
        }
    }

    public function testMoveDeletedFileToStagingArea()
    {
        $ff = $this->createPartialMock(FRSFileFactory::class, [
            '_getFRSFileDao',
        ]);
        $ff->setLogger($this->createMock(LoggerInterface::class));

        $this->createReleaseDir('prj', 'p1_r1');
        $filepath = ForgeConfig::get('ftp_frs_dir_prefix') . '/prj/p1_r1/foobar.xls';
        touch($filepath);
        self::assertTrue(is_file($filepath));
        $file = $this->createMock(FRSFile::class);
        $file->method('getFileID')->willReturn(12);
        $file->method('getFileLocation')->willReturn($filepath);

        $dao = $this->createMock(FRSFileDao::class);
        $dao->expects(self::once())->method('setFileInDeletedList')->with(12)->willReturn(true);
        $ff->method('_getFRSFileDao')->willReturn($dao);
        $backend = $this->createMock(BackendSystem::class);

        self::assertTrue($ff->moveDeletedFileToStagingArea($file, $backend));

        self::assertTrue(is_file(ForgeConfig::get('ftp_frs_dir_prefix') . '/DELETED/prj/p1_r1/foobar.xls.12'));
        self::assertFalse(is_file(ForgeConfig::get('ftp_frs_dir_prefix') . '/prj/p1_r1/foobar.xls'));
        self::assertFalse(is_dir(ForgeConfig::get('ftp_frs_dir_prefix') . '/prj/p1_r1'));
    }

    /**
     * If for one reason the file to delete appears to be already deleted, mark it as
     * deleted and purged at the very same date
     *
     */
    public function testMoveDeletedFileToStagingAreaButFileDoesntExist()
    {
        $ff = $this->createPartialMock(FRSFileFactory::class, [
            '_getFRSFileDao',
        ]);
        $ff->setLogger($this->createMock(LoggerInterface::class));

        // Create temp file in a fake release
        $this->createReleaseDir('prj', 'p1_r1');
        $filepath = ForgeConfig::get('ftp_frs_dir_prefix') . '/prj/p1_r1/foobar.xls';
        self::assertFalse(is_file($filepath), "The file shouldn't exist, this is the base of the test!");
        $file = $this->createMock(FRSFile::class);
        $file->method('getFileID')->willReturn(12);
        $file->method('getFileLocation')->willReturn($filepath);

        $dao = $this->createMock(FRSFileDao::class); // Mark as deleted
        $dao->expects(self::once())->method('setFileInDeletedList')->with(12)->willReturn(true); // Mark as purged
        $dao->expects(self::once())->method('setPurgeDate')->with(12, $_SERVER['REQUEST_TIME'])->willReturn(true);
        $ff->method('_getFRSFileDao')->willReturn($dao);
        $backend = $this->createMock(BackendSystem::class);
        $backend->method('log')->withConsecutive(
            [self::anything(), self::stringContains('warn')],
            [self::anything(), self::stringContains('error')],
        );

        self::assertFalse($ff->moveDeletedFileToStagingArea($file, $backend));

        self::assertFalse(is_file(ForgeConfig::get('ftp_frs_dir_prefix') . '/DELETED/prj/p1_r1/foobar.xls.12'));
        self::assertFalse(is_file(ForgeConfig::get('ftp_frs_dir_prefix') . '/prj/p1_r1/foobar.xls'));
        self::assertFalse(is_dir(ForgeConfig::get('ftp_frs_dir_prefix') . '/prj/p1_r1'));
    }

    public function testMoveDeletedFileToStagingAreaReleaseNotEmpty()
    {
        $ff = $this->createPartialMock(FRSFileFactory::class, [
            '_getFRSFileDao',
        ]);
        $ff->setLogger($this->createMock(LoggerInterface::class));

        // Create temp file in a fake release
        $this->createReleaseDir('prj', 'p1_r1');
        $filepath = ForgeConfig::get('ftp_frs_dir_prefix') . '/prj/p1_r1/foobar.xls';
        touch($filepath);
        self::assertTrue(is_file($filepath));
        $file = $this->createMock(FRSFile::class);
        $file->method('getFileID')->willReturn(12);
        $file->method('getFileLocation')->willReturn($filepath);
        // Second file, not deleted
        touch(ForgeConfig::get('ftp_frs_dir_prefix') . '/prj/p1_r1/barfoo.doc');

        $dao = $this->createMock(FRSFileDao::class);
        $dao->expects(self::once())->method('setFileInDeletedList')->with(12)->willReturn(true);
        $ff->method('_getFRSFileDao')->willReturn($dao);
        $backend = $this->createMock(BackendSystem::class);

        self::assertTrue($ff->moveDeletedFileToStagingArea($file, $backend));

        self::assertTrue(is_file(ForgeConfig::get('ftp_frs_dir_prefix') . '/DELETED/prj/p1_r1/foobar.xls.12'));
        self::assertFalse(is_file(ForgeConfig::get('ftp_frs_dir_prefix') . '/prj/p1_r1/foobar.xls'));
        self::assertTrue(is_file(ForgeConfig::get('ftp_frs_dir_prefix') . '/prj/p1_r1/barfoo.doc'), 'The other file in the release must not be deleted');
    }

    public function testMoveDeletedFilesToStagingAreaFail()
    {
        $ff = $this->createPartialMock(FRSFileFactory::class, [
            '_getFRSFileDao',
            'moveDeletedFileToStagingArea',
        ]);
        $ff->setLogger($this->createMock(LoggerInterface::class));

        $dao = $this->createMock(FRSFileDao::class);
        $dao->method('searchStagingCandidates')->willReturn(TestHelper::arrayToDar(['file_id' => 12]));
        $ff->method('_getFRSFileDao')->willReturn($dao);

        $backend = $this->createMock(BackendSystem::class);
        $ff->expects(self::once())->method('moveDeletedFileToStagingArea')->with(
            self::callback(function (FRSFile $file) {
                return $file->getFileID() === 12;
            }),
            $backend
        )->willReturn(false);

        self::assertFalse($ff->moveDeletedFilesToStagingArea($backend));
    }

    public function testMoveDeletedFilesToStagingAreaWithOneFile()
    {
        $ff = $this->createPartialMock(FRSFileFactory::class, [
            '_getFRSFileDao',
            'moveDeletedFileToStagingArea',
        ]);
        $ff->setLogger($this->createMock(LoggerInterface::class));

        $dao = $this->createMock(FRSFileDao::class);
        $dao->expects(self::once())->method('searchStagingCandidates')->willReturn(TestHelper::arrayToDar(['file_id' => 12]));
        $ff->method('_getFRSFileDao')->willReturn($dao);

        $backend = $this->createMock(BackendSystem::class);
        $ff->expects(self::once())->method('moveDeletedFileToStagingArea')->with(
            self::callback(function (FRSFile $file) {
                return $file->getFileID() === 12;
            }),
            $backend
        )->willReturn(true);

        self::assertTrue($ff->moveDeletedFilesToStagingArea($backend));
    }

    public function testPurgeFilesWithNoFiles()
    {
        $dao = $this->createMock(FRSFileDao::class);
        $dao->expects(self::once())->method('searchFilesToPurge')->with(1287504083)->willReturn(TestHelper::emptyDar());

        $ff = $this->createPartialMock(FRSFileFactory::class, [
            '_getFRSFileDao',
            'purgeFile',
        ]);
        $ff->setLogger($this->createMock(LoggerInterface::class));
        $ff->method('_getFRSFileDao')->willReturn($dao);

        $ff->expects(self::never())->method('purgeFile');
        $backend = $this->createMock(BackendSystem::class);
        self::assertTrue($ff->purgeFiles(1287504083, $backend));
    }

    public function testPurgeFilesWithOneFile()
    {
        $dao = $this->createMock(FRSFileDao::class);
        $dao->expects(self::once())->method('searchFilesToPurge')->with(1287504083)->willReturn(TestHelper::arrayToDar(['file_id' => 12]));

        $ff = $this->createPartialMock(FRSFileFactory::class, [
            '_getFRSFileDao',
            'purgeFile',
        ]);
        $ff->setLogger($this->createMock(LoggerInterface::class));
        $ff->method('_getFRSFileDao')->willReturn($dao);

        $backend = $this->createMock(BackendSystem::class);
        $ff->expects(self::once())->method('purgeFile')->with(
            self::callback(function (FRSFile $file) {
                return $file->getFileID() === 12;
            }),
            $backend
        )->willReturn(true);

        self::assertTrue($ff->purgeFiles(1287504083, $backend));
    }

    public function testPurgeFileSucceed()
    {
        $ff = $this->createPartialMock(FRSFileFactory::class, [
            '_getFRSFileDao',
            'archiveBeforePurge',
        ]);
        $ff->setLogger($this->createMock(LoggerInterface::class));

        // Create temp file
        $filepath = ForgeConfig::get('ftp_frs_dir_prefix') . '/DELETED/prj/p1_r1/foobar.xls.12';
        mkdir(dirname($filepath), 0750, true);
        touch($filepath);

        self::assertTrue(is_file($filepath));
        $file = $this->createMock(FRSFile::class);
        $file->method('getFileID')->willReturn(12);
        $file->method('getFileName')->willReturn('p1_r1/foobar.xls');
        $file->method('getFileLocation')->willReturn(ForgeConfig::get('ftp_frs_dir_prefix') . '/prj/p1_r1/foobar.xls');

        $dao = $this->createMock(FRSFileDao::class);
        $dao->expects(self::once())->method('setPurgeDate')->with(12, self::anything())->willReturn(true);
        $ff->method('_getFRSFileDao')->willReturn($dao);

        $backend = $this->createMock(BackendSystem::class);
        $backend->expects(self::never())->method('log');
        $ff->expects(self::once())->method('archiveBeforePurge')->with(
            self::callback(function (FRSFile $file) {
                return $file->getFileID() === 12;
            }),
            $backend
        )->willReturn(true);

        $ff->purgeFile($file, $backend);

        self::assertFalse(is_file($filepath), "File should be deleted");
    }

    public function testPurgeFileDBUpdateFails()
    {
        $ff = $this->createPartialMock(FRSFileFactory::class, [
            '_getFRSFileDao',
            'archiveBeforePurge',
        ]);
        $ff->setLogger($this->createMock(LoggerInterface::class));

        // Create temp file
        $filepath = ForgeConfig::get('ftp_frs_dir_prefix') . '/DELETED/prj/p1_r1/foobar.xls.12';
        mkdir(dirname($filepath), 0750, true);
        touch($filepath);
        self::assertTrue(is_file($filepath));
        $file = $this->createMock(FRSFile::class);
        $file->method('getFileID')->willReturn(12);
        $file->method('getFileName')->willReturn('p1_r1/foobar.xls');
        $file->method('getFileLocation')->willReturn(ForgeConfig::get('ftp_frs_dir_prefix') . '/prj/p1_r1/foobar.xls');

        $dao = $this->createMock(FRSFileDao::class);
        $dao->expects(self::once())->method('setPurgeDate')->with(12, self::anything())->willReturn(false);
        $ff->method('_getFRSFileDao')->willReturn($dao);
        $ff->method('archiveBeforePurge')->willReturn(true);

        $backend = $this->createMock(BackendSystem::class);
        $backend->expects(self::once())->method('log')->with('File ' . $filepath . ' not purged, Set purge date in DB fail', 'error');
        self::assertFalse($ff->purgeFile($file, $backend));

        self::assertFalse(is_file($filepath), "File should be deleted");
    }

    public function testPurgeFileSystemCopyFails()
    {
        $ff = $this->createPartialMock(FRSFileFactory::class, [
            'archiveBeforePurge',
        ]);
        $ff->setLogger($this->createMock(LoggerInterface::class));

        // Create temp file
        $filepath = ForgeConfig::get('ftp_frs_dir_prefix') . '/DELETED/prj/p1_r1/foobar.xls.12';
        mkdir(dirname($filepath), 0750, true);
        touch($filepath);
        self::assertTrue(is_file($filepath));
        $file = $this->createMock(FRSFile::class);
        $file->method('getFileID')->willReturn(12);
        $file->method('getFileName')->willReturn('p1_r1/foobar.xls');
        $file->method('getFileLocation')->willReturn(ForgeConfig::get('ftp_frs_dir_prefix') . '/prj/p1_r1/foobar.xls');

        $dao = $this->createMock(FRSFileDao::class);
        $dao->expects(self::never())->method('setPurgeDate');
        $ff->method('archiveBeforePurge')->willReturn(false);

        $backend = $this->createMock(BackendSystem::class);
        $backend->expects(self::once())->method('log')->with('File ' . $filepath . ' not purged, unlink failed', 'error');
        self::assertFalse($ff->purgeFile($file, $backend));
    }

    public function testPurgeFileWithFileNotFoundInFS()
    {
        $ff = $this->createPartialMock(FRSFileFactory::class, [
            '_getFRSFileDao',
            'archiveBeforePurge',
        ]);
        $ff->setLogger($this->createMock(LoggerInterface::class));

        $filepath = ForgeConfig::get('ftp_frs_dir_prefix') . '/DELETED/prj/p1_r1/foobar.xls.12';

        self::assertFalse(is_file($filepath));
        $file = $this->createMock(FRSFile::class);
        $file->method('getFileID')->willReturn(12);
        $file->method('getFileName')->willReturn('p1_r1/foobar.xls');
        $file->method('getFileLocation')->willReturn(ForgeConfig::get('ftp_frs_dir_prefix') . '/prj/p1_r1/foobar.xls');

        $dao = $this->createMock(FRSFileDao::class);
        $dao->expects(self::once())->method('setPurgeDate')->with(12, self::anything())->willReturn(true);
        $ff->method('_getFRSFileDao')->willReturn($dao);

        $backend = $this->createMock(BackendSystem::class);
        $backend->expects(self::once())->method('log')->with('File ' . $filepath . ' not found on file system, automatically marked as purged', LogLevel::WARNING);
        self::assertTrue($ff->purgeFile($file, $backend));
        $ff->expects(self::never())->method('archiveBeforePurge');
    }

    private function createDeletedReleaseDir($release_name, $dir_name)
    {
        // Create temp file in a fake release
        if (! is_dir(ForgeConfig::get('ftp_frs_dir_prefix') . '/DELETED/' . $release_name . '/' . $dir_name)) {
            mkdir(ForgeConfig::get('ftp_frs_dir_prefix') . '/DELETED/' . $release_name . '/' . $dir_name, 0750, true);
        }
    }

    public function testRemoveStagingEmptyDirectories()
    {
        $ff      = new FRSFileFactory();
        $backend = $this->createMock(BackendSystem::class);

        $this->createDeletedReleaseDir('prj', 'p1_r1');
        $this->createDeletedReleaseDir('prj2', 'p2_r5');
        $this->createDeletedReleaseDir('prj3', 'p7_r8');
        $this->createDeletedReleaseDir('prj3', 'p9_r10');

        touch(ForgeConfig::get('ftp_frs_dir_prefix') . '/DELETED/prj2/p2_r5/file.txt.7');
        touch(ForgeConfig::get('ftp_frs_dir_prefix') . '/DELETED/prj3/p9_r10/foo.txt.12');

        self::assertTrue($ff->cleanStaging($backend));
        self::assertFalse(is_dir(ForgeConfig::get('ftp_frs_dir_prefix') . '/DELETED/prj'));
        self::assertTrue(is_file(ForgeConfig::get('ftp_frs_dir_prefix') . '/DELETED/prj2/p2_r5/file.txt.7'));
        self::assertFalse(is_dir(ForgeConfig::get('ftp_frs_dir_prefix') . '/DELETED/prj3/p7_r8'));
        self::assertTrue(is_file(ForgeConfig::get('ftp_frs_dir_prefix') . '/DELETED/prj3/p9_r10/foo.txt.12'));
    }

    public function testRestoreFileSucceed()
    {
        $fileFactory = $this->createPartialMock(FRSFileFactory::class, [
            '_getFRSFileDao',
            '_getUserManager',
            '_getEventManager',
            '_getFRSReleaseFactory',
        ]);
        $fileFactory->setLogger($this->createMock(LoggerInterface::class));

        // Create temp file
        $filepath = ForgeConfig::get('ftp_frs_dir_prefix') . '/DELETED/prj/p1_r1/toto.xls.12';
        $this->createDeletedReleaseDir('prj', 'p1_r1');
        touch($filepath);
        self::assertTrue(is_dir(dirname($filepath)));

        $file = $this->createMock(FRSFile::class);
        $file->method('getFileID')->willReturn(12);
        $file->method('getFileName')->willReturn('p1_r1/toto.xls');
        $file->method('getFileLocation')->willReturn(ForgeConfig::get('ftp_frs_dir_prefix') . '/prj/p1_r1/toto.xls');
        $project = $this->createMock(Project::class);
        $file->method('getGroup')->willReturn($project);
        $file->method('getReleaseID')->willReturn(self::isType('int'));
        $project->method('getGroupId')->willReturn(self::isType('int'));
        $this->createReleaseDir('prj', 'p1_r1');
        self::assertTrue(is_dir(ForgeConfig::get('ftp_frs_dir_prefix') . '/prj/p1_r1/'));

        $dao = $this->createMock(FRSFileDao::class);
        $dao->expects(self::once())->method('restoreFile')->willReturn(true);
        $fileFactory->method('_getFRSFileDao')->willReturn($dao);
        $backend = $this->createMock(BackendSystem::class);

        $user = UserTestBuilder::buildWithDefaults();
        $um   = $this->createMock(UserManager::class);
        $um->method('getCurrentUser')->willReturn($user);
        $fileFactory->method('_getUserManager')->willReturn($um);
        $em = $this->createMock(EventManager::class);
        $em->method('processEvent');
        $fileFactory->method('_getEventManager')->willReturn($em);
        $release = $this->createMock(FRSRelease::class);
        $release->method('isDeleted')->willReturn(false);
        $releaseFactory = $this->createMock(FRSReleaseFactory::class);
        $releaseFactory->method('getFRSReleaseFromDb')->willReturn($release);
        $fileFactory->method('_getFRSReleaseFactory')->willReturn($releaseFactory);

        self::assertTrue($fileFactory->restoreFile($file, $backend));
    }

    public function testRestoreFileNotExists()
    {
        $fileFactory = $this->createPartialMock(FRSFileFactory::class, [
            '_getFRSFileDao',
            '_getFRSReleaseFactory',
        ]);
        $fileFactory->setLogger($this->createMock(LoggerInterface::class));

        // Create temp file
        $filepath = ForgeConfig::get('ftp_frs_dir_prefix') . '/DELETED/prj/p1_r1/toto.xls.5';
        $this->createDeletedReleaseDir('prj', 'p1_r1');
        $this->createReleaseDir('prj', 'p1_r1');
        self::assertFalse(file_exists($filepath));

        $file = $this->createMock(FRSFile::class);
        $file->method('getFileID')->willReturn(5);
        $file->method('getFileName')->willReturn('p1_r1/toto.xls');
        $file->method('getFileLocation')->willReturn(ForgeConfig::get('ftp_frs_dir_prefix') . '/prj/p1_r1/toto.xls');
        $file->method('getReleaseID')->willReturn(self::isType('int'));
        self::assertTrue(is_dir(dirname(ForgeConfig::get('ftp_frs_dir_prefix') . '/prj/p1_r1/')));

        $dao = $this->createMock(FRSFileDao::class);
        $dao->expects(self::never())->method('restoreFile');
        $fileFactory->method('_getFRSFileDao')->willReturn($dao);
        $backend = $this->createMock(BackendSystem::class);
        $backend->method('chgrp')->willReturn(true);
        $backend->method('log');

        $release = $this->createMock(FRSRelease::class);
        $release->method('isDeleted')->willReturn(false);
        $releaseFactory = $this->createMock(FRSReleaseFactory::class);
        $releaseFactory->method('getFRSReleaseFromDb')->willReturn($release);
        $fileFactory->method('_getFRSReleaseFactory')->willReturn($releaseFactory);

        self::assertFalse($fileFactory->restoreFile($file, $backend));
    }

    public function testRestoreFileLocationNotExists()
    {
        $fileFactory = $this->createPartialMock(FRSFileFactory::class, [
            '_getFRSFileDao',
            '_getUserManager',
            '_getEventManager',
            '_getFRSReleaseFactory',
        ]);
        $fileFactory->setLogger($this->createMock(LoggerInterface::class));

        // Create temp file
        $filepath = ForgeConfig::get('ftp_frs_dir_prefix') . '/DELETED/prj/p2_r1/toto.xls.12';
        $this->createReleaseDir('prj', 'p2_r1');
        $this->createDeletedReleaseDir('prj', 'p2_r1');
        touch($filepath);
        self::assertTrue(is_dir(dirname($filepath)));
        $backend = $this->createMock(BackendSystem::class);
        $file    = $this->createMock(FRSFile::class);
        $file->method('getFileID')->willReturn(12);
        $file->method('getFileName')->willReturn('p2_r1/toto.xls');
        $file->method('getFileLocation')->willReturn(ForgeConfig::get('ftp_frs_dir_prefix') . '/prj/p2_r1/toto.xls');
        $project = $this->createMock(Project::class);
        $file->method('getGroup')->willReturn($project);
        $file->method('getReleaseID')->willReturn(self::isType('int'));
        $project->method('getGroupId')->willReturn(self::isType('int'));
        self::assertTrue(is_dir(dirname(ForgeConfig::get('ftp_frs_dir_prefix') . '/prj/p2_r1/')));
        $backend->method('chgrp')->willReturn(true);

        $dao = $this->createMock(FRSFileDao::class);
        $dao->expects(self::once())->method('restoreFile')->willReturn(true);
        $fileFactory->method('_getFRSFileDao')->willReturn($dao);

        $user = $this->createMock(\PFUser::class);
        $um   = $this->createMock(UserManager::class);
        $um->method('getCurrentUser')->willReturn($user);
        $fileFactory->method('_getUserManager')->willReturn($um);
        $em = $this->createMock(EventManager::class);
        $fileFactory->method('_getEventManager')->willReturn($em);
        $em->method('processEvent');
        $release = $this->createMock(FRSRelease::class);
        $release->method('isDeleted')->willReturn(false);
        $releaseFactory = $this->createMock(FRSReleaseFactory::class);
        $releaseFactory->method('getFRSReleaseFromDb')->willReturn($release);
        $fileFactory->method('_getFRSReleaseFactory')->willReturn($releaseFactory);

        self::assertTrue($fileFactory->restoreFile($file, $backend));
    }

    public function testRestoreFileDBUpdateFails()
    {
        $fileFactory = $this->createPartialMock(FRSFileFactory::class, [
            '_getUserManager',
            '_getEventManager',
            '_getFRSReleaseFactory',
            '_getFRSFileDao',
        ]);
        $fileFactory->setLogger($this->createMock(LoggerInterface::class));

        // Create temp file
        $filepath = ForgeConfig::get('ftp_frs_dir_prefix') . '/DELETED/prj/p3_r1/toto.xls.12';
        $this->createDeletedReleaseDir('prj', 'p3_r1');
        $this->createReleaseDir('prj', 'p3_r1');
        touch($filepath);
        self::assertTrue(is_dir(dirname($filepath)));

        $file = $this->createMock(FRSFile::class);
        $file->method('getFileID')->willReturn(12);
        $file->method('getFileName')->willReturn('p3_r1/toto.xls');
        $file->method('getFileLocation')->willReturn(ForgeConfig::get('ftp_frs_dir_prefix') . '/prj/p3_r1/toto.xls');
        $project = $this->createMock(Project::class);
        $file->method('getGroup')->willReturn($project);
        $file->method('getReleaseID')->willReturn(self::isType('int'));
        self::assertTrue(is_dir(dirname(ForgeConfig::get('ftp_frs_dir_prefix') . '/prj/p3_r1/')));

        $dao = $this->createMock(FRSFileDao::class);
        $dao->expects(self::once())->method('restoreFile')->willReturn(false);
        $fileFactory->method('_getFRSFileDao')->willReturn($dao);
        $backend = $this->createMock(BackendSystem::class);
        $backend->method('chgrp')->willReturn(true);
        $backend->method('log');

        $user = $this->createMock(\PFUser::class);
        $um   = $this->createMock(UserManager::class);
        $um->method('getCurrentUser')->willReturn($user);
        $fileFactory->method('_getUserManager')->willReturn($um);
        $fileFactory->method('_getEventManager')->willReturn($this->createMock(EventManager::class));
        $release = $this->createMock(FRSRelease::class);
        $release->method('isDeleted')->willReturn(false);
        $releaseFactory = $this->createMock(FRSReleaseFactory::class);
        $releaseFactory->method('getFRSReleaseFromDb')->willReturn($release);
        $fileFactory->method('_getFRSReleaseFactory')->willReturn($releaseFactory);

        self::assertFalse($fileFactory->restoreFile($file, $backend));
    }

    public function testRestoreFileInDeletedRelease()
    {
        $fileFactory = $this->createPartialMock(FRSFileFactory::class, [
            '_getFRSReleaseFactory',
            '_getFRSFileDao',
        ]);
        $fileFactory->setLogger($this->createMock(LoggerInterface::class));

        // Create temp file
        $filepath = ForgeConfig::get('ftp_frs_dir_prefix') . '/DELETED/prj/p3_r1/toto.xls.12';
        $this->createDeletedReleaseDir('prj', 'p3_r1');
        touch($filepath);
        self::assertTrue(is_dir(dirname($filepath)));

        $release = $this->createMock(FRSRelease::class);
        $release->method('isDeleted')->willReturn(true);
        $release->method('getName');
        $release->method('getReleaseID');
        $releaseFactory = $this->createMock(FRSReleaseFactory::class);
        $releaseFactory->method('getFRSReleaseFromDb')->willReturn($release);
        $fileFactory->method('_getFRSReleaseFactory')->willReturn($releaseFactory);
        $dao = $this->createMock(FRSFileDao::class);
        $dao->expects(self::once())->method('cancelRestore');
        $fileFactory->method('_getFRSFileDao')->willReturn($dao);
        $file = $this->createMock(FRSFile::class);
        $file->method('getReleaseID');
        $file->method('getFileID');
        $file->method('getFileLocation');
        $backend = $this->createMock(BackendSystem::class);
        $backend->method('log');

        self::assertFalse($fileFactory->restoreFile($file, $backend));
        self::assertTrue(is_dir(dirname($filepath)));
        self::assertFalse(file_exists(ForgeConfig::get('ftp_frs_dir_prefix') . '/prj/p3_r1/toto.xls'));
        self::assertFalse(is_dir(ForgeConfig::get('ftp_frs_dir_prefix') . '/prj/p3_r1'));
    }

    public function testRestoreDeletedFiles()
    {
        $refFile = new FRSFile(['file_id' => 12]);

        $dao = $this->createMock(FRSFileDao::class);
        $dao->expects(self::once())->method('searchFilesToRestore')->willReturn(TestHelper::arrayToDar(['file_id' => 12]));

        $ff = $this->createPartialMock(FRSFileFactory::class, [
            '_getFRSFileDao',
            'restoreFile',
        ]);
        $ff->setLogger($this->createMock(LoggerInterface::class));
        $ff->method('_getFRSFileDao')->willReturn($dao);
        $backend = $this->createMock(BackendSystem::class);
        $ff->expects(self::once())->method('restoreFile')->with(
            self::callback(function (FRSFile $file) {
                return $file->getFileID() === 12;
            }),
            $backend
        )->willReturn(true);

        self::assertTrue($ff->restoreDeletedFiles($backend));
    }

    public function testRestoreDeletedFilesReturnFalse()
    {
        $dao = $this->createMock(FRSFileDao::class);
        $dao->method('searchFilesToRestore')->willReturn(TestHelper::arrayToDar(['file_id' => 12], ['file_id' => 13]));

        $ff = $this->createPartialMock(FRSFileFactory::class, [
            '_getFRSFileDao',
            'restoreFile',
        ]);
        $ff->setLogger($this->createMock(LoggerInterface::class));
        $ff->method('_getFRSFileDao')->willReturn($dao);
        $backend = $this->createMock(BackendSystem::class);
        $ff->method('restoreFile')->willReturnOnConsecutiveCalls(false, true);

        self::assertFalse($ff->restoreDeletedFiles($backend));
    }

    public function testRestoreDeletedFilesDBError()
    {
        $refFile = new FRSFile(['file_id' => 12]);

        $dao = $this->createMock(FRSFileDao::class);
        $dao->expects(self::once())->method('searchFilesToRestore')->willReturn($this->createConfiguredMock(DataAccessResult::class, ['isError' => true]));

        $ff = $this->createPartialMock(FRSFileFactory::class, [
            '_getFRSFileDao',
            'restoreFile',
        ]);
        $ff->setLogger($this->createMock(LoggerInterface::class));
        $ff->method('_getFRSFileDao')->willReturn($dao);
        $backend = $this->createMock(BackendSystem::class);
        $ff->expects(self::never())->method('restoreFile');

        self::assertFalse($ff->restoreDeletedFiles($backend));
    }

    public function testRestoreDeletedFilesNoFiles()
    {
        $refFile = new FRSFile(['file_id' => 12]);

        $dao = $this->createMock(FRSFileDao::class);
        $dao->expects(self::once())->method('searchFilesToRestore')->willReturn(TestHelper::emptyDar());

        $ff = $this->createPartialMock(FRSFileFactory::class, [
            '_getFRSFileDao',
            'restoreFile',
        ]);
        $ff->setLogger($this->createMock(LoggerInterface::class));
        $ff->method('_getFRSFileDao')->willReturn($dao);
        $backend = $this->createMock(BackendSystem::class);
        $ff->expects(self::never())->method('restoreFile');

        self::assertTrue($ff->restoreDeletedFiles($backend));
    }

    public function testCompareMd5ChecksumsFail()
    {
        $fileFactory = new FRSFileFactory();
        self::assertFalse($fileFactory->compareMd5Checksums('da1e100dc9e7bebb810985e37875de36', 'da1e100dc9e7bebb810985e37875de38'));
    }

    public function testCompareMd5ChecksumsSucceedeEmptyHashes()
    {
        $fileFactory = new FRSFileFactory();
        self::assertTrue($fileFactory->compareMd5Checksums('', ''));
    }

    public function testCompareMd5ChecksumsSucceedeEmptyReference()
    {
        $fileFactory = new FRSFileFactory();
        self::assertTrue($fileFactory->compareMd5Checksums('da1e100dc9e7bebb810985e37875de36', ''));
    }

    public function testCompareMd5ChecksumsSucceedeEmptyComputed()
    {
        $fileFactory = new FRSFileFactory();
        self::assertTrue($fileFactory->compareMd5Checksums('', 'da1e100dc9e7bebb810985e37875de38'));
    }

    public function testCompareMd5ChecksumsSucceededComparison()
    {
        $fileFactory = new FRSFileFactory();
        self::assertTrue($fileFactory->compareMd5Checksums('da1e100dc9e7bebb810985e37875de38', 'da1e100dc9e7bebb810985e37875de38'));
        self::assertTrue($fileFactory->compareMd5Checksums('da1e100dc9e7bebb810985e37875de38', 'DA1E100DC9E7BEBB810985E37875DE38'));
    }

    public function testMoveFileforgeOk()
    {
        if (! is_file('/usr/bin/perl')) {
            self::markTestSkipped('Perl is not available at /usr/bin/perl');
        }
        // Create target release directory
        $this->createReleaseDir('prj', 'p123_r456');
        touch(ForgeConfig::get('ftp_incoming_dir') . '/toto.txt');

        // Try to release a file named toto.txt in the same release
        $p = $this->createMock(Project::class);
        $p->expects(self::once())->method('getUnixName')->with(false)->willReturn('prj');

        $r = new FRSRelease();
        $r->setReleaseID(456);
        $r->setPackageID(123);
        $r->setProject($p);

        $f = new FRSFile();
        $f->setFileName('toto.txt');
        $f->setRelease($r);

        $ff = new FRSFileFactory();
        $ff->setFileForge([__DIR__ . '/../../../../src/utils/fileforge.pl']);

        $res = $ff->moveFileForge($f, $r);
        self::assertTrue($res);
        self::assertFileExists(ForgeConfig::get('ftp_frs_dir_prefix') . '/prj/' . $f->getFilePath());
    }

    public function testMoveFileforgeFileExist()
    {
        // Create toto.txt in the release directory
        $this->createReleaseDir('prj', 'p123_r456');
        touch(ForgeConfig::get('ftp_frs_dir_prefix') . '/prj/p123_r456/toto.txt_1299584211');

        // Try to release a file named toto.txt in the same release
        $p = $this->createMock(Project::class);
        $p->expects(self::once())->method('getUnixName')->with(false)->willReturn('prj');

        $r = new FRSRelease();
        $r->setReleaseID(456);
        $r->setPackageID(123);
        $r->setProject($p);

        $f = new FRSFile();
        $f->setFileName('toto.txt');
        $f->setFilePath('toto.txt_1299584211');
        $f->setRelease($r);

        $ff = new FRSFileFactory();
        $ff->setFileForge([__DIR__ . '/../../../../src/utils/fileforge.pl']);
        self::assertFalse($ff->moveFileForge($f));
    }

    public function testMoveFileforgeFileExistWithSpaces()
    {
        // Create toto.txt in the release directory
        $this->createReleaseDir('prj', 'p123_r456');
        touch(ForgeConfig::get('ftp_frs_dir_prefix') . '/prj/p123_r456/toto zataz.txt');

        // Try to release a file named 'toto zataz.txt' in the same release
        $p = $this->createMock(Project::class);
        $p->expects(self::once())->method('getUnixName')->with(false)->willReturn('prj');

        $r = new FRSRelease();
        $r->setReleaseID(456);
        $r->setPackageID(123);
        $r->setProject($p);

        $f = new FRSFile();
        $f->setFileName('toto zataz.txt');
        $f->setRelease($r);

        $ff = new FRSFileFactory();
        $ff->setFileForge([__DIR__ . '/../../../../src/utils/fileforge.pl']);
        self::assertFalse($ff->moveFileForge($f));
    }

    public function testCreateFileIllegalName()
    {
        $ff = new FRSFileFactory();
        $f  = new FRSFile();
        $f->setFileName('%toto#.txt');

        self::expectException(FRSFileIllegalNameException::class);

        $ff->createFile($f);
    }

    public function testCreateFileSetReleaseTime()
    {
        $p = $this->createMock(Project::class);
        $p->method('getUnixName')->willReturn('prj');
        $r = new FRSRelease();
        $r->setReleaseID(456);
        $r->setPackageID(123);
        $r->setGroupID(1112);
        $r->setProject($p);

        $f = new FRSFile();
        $f->setRelease($r);
        $f->setGroup($p);
        $f->setFileName('file_sample');

        $dao = $this->createMock(FRSFileDao::class);
        $dao->method('searchFileByName')->willReturn(TestHelper::emptyDar());
        $dao->method('isMarkedToBeRestored');
        $ff     = $this->createPartialMock(FRSFileFactory::class, [
            'create',
            'moveFileForge',
        ]);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->method('log');
        $ff->setLogger($logger);
        $ff->method('create')->willReturn(55);
        $ff->method('moveFileForge')->willReturn(true);
        $ff->dao = $dao;

        $rf = $this->createMock(FRSReleaseFactory::class);
        $rf->method('getFRSReleaseFromDb')->willReturn($r);
        $ff->release_factory = $rf;

        $before = time();
        $ff->createFile($f);
        $after = time();

        self::assertTrue($f->getPostDate() >= $before && $f->getPostDate() <= $after);
        self::assertTrue($f->getReleaseTime() >= $before && $f->getReleaseTime() <= $after);
    }

    public function testCreateFileDoNotSetReleaseTimeIfAlreadySet()
    {
        $p = $this->createMock(Project::class);
        $p->method('getUnixName')->willReturn('prj');
        $r = new FRSRelease();
        $r->setReleaseID(456);
        $r->setPackageID(123);
        $r->setGroupID(1113);
        $r->setProject($p);

        $f = new FRSFile();
        $f->setRelease($r);
        $f->setGroup($p);
        $f->setFileName('file_sample');
        $f->setReleaseTime(3125);
        $f->setPostDate(3125);

        $dao = $this->createMock(FRSFileDao::class);
        $dao->method('searchFileByName')->willReturn(TestHelper::emptyDar());
        $dao->method('isMarkedToBeRestored');
        $ff     = $this->createPartialMock(FRSFileFactory::class, [
            'create',
            'moveFileForge',
        ]);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->method('log');
        $ff->setLogger($logger);
        $ff->method('create')->willReturn(55);
        $ff->method('moveFileForge')->willReturn(true);
        $ff->dao = $dao;

        $rf = $this->createMock(FRSReleaseFactory::class);
        $rf->method('getFRSReleaseFromDb')->willReturn($r);
        $ff->release_factory = $rf;

        $before = time();
        $ff->createFile($f);
        $after = time();

        self::assertTrue($f->getPostDate() == 3125);
        self::assertTrue($f->getReleaseTime() == 3125);
    }

    /**
     * We should not be able to create a file with the same name,
     * if an active one exists.
     */
    public function testCreateFileAlreadyExistingAndActive()
    {
        // Create toto.txt in the release directory
        $this->createReleaseDir('prj', 'p123_r456');
        touch(ForgeConfig::get('ftp_frs_dir_prefix') . '/prj/p123_r456/toto.txt_1299584197');

        $p = $this->createMock(Project::class);
        $p->method('getUnixName')->willReturn('prj');

        $r = new FRSRelease();
        $r->setReleaseID(456);
        $r->setPackageID(123);
        $r->setGroupID(111);
        $r->setProject($p);

        $f = new FRSFile();
        $f->setFileName('toto.txt_1299584219');
        $f->setRelease($r);

        $ff     = $this->createPartialMock(FRSFileFactory::class, [
            'getSrcDir',
            'isFileBaseNameExists',
        ]);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->method('log');
        $ff->setLogger($logger);
        $ff->method('getSrcDir')->willReturn(ForgeConfig::get('ftp_incoming_dir'));
        $ff->method('isFileBaseNameExists')->willReturn(true);

        self::expectException(FRSFileExistsException::class);

        $ff->createFile($f);
    }

    /**
     * We should be able to create a file with the same name,
     * even if the active one has been deleted but not yet moved to staging area.
     */
    public function testCreateFileAlreadyExistingAndMarkedToBeDeletedNotYetMoved()
    {
        // Create toto.txt in the release directory
        touch(ForgeConfig::get('ftp_incoming_dir') . '/toto.txt');
        $this->createReleaseDir('prj', 'p123_r456');
        // toto.txt_1299584187 is the file having been deleted but not yet moved
        touch(ForgeConfig::get('ftp_frs_dir_prefix') . '/prj/p123_r456/toto.txt_1299584187');

        $p = $this->createMock(Project::class);
        $p->method('getUnixName')->willReturn('prj');

        $r = new FRSRelease();
        $r->setReleaseID(456);
        $r->setPackageID(123);
        $r->setGroupID(111);
        $r->setProject($p);

        $f = new FRSFile();
        $f->setFileName('toto.txt');
        $f->setFilePath('toto.txt_1299584210');
        $f->setRelease($r);
        $f->setFileID(15225);
        $f->setFileLocation(ForgeConfig::get('ftp_frs_dir_prefix') . '/prj/p123_r456');

        $ff     = $this->createPartialMock(FRSFileFactory::class, [
            'getSrcDir',
            'isFileBaseNameExists',
            'isSameFileMarkedToBeRestored',
            'moveFileForge',
            'create',
        ]);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->method('log');
        $ff->setLogger($logger);
        $ff->method('getSrcDir')->willReturn(ForgeConfig::get('ftp_incoming_dir'));
        $ff->method('isFileBaseNameExists')->willReturn(false);
        $ff->method('isSameFileMarkedToBeRestored')->willReturn(false);

        //moveFileForge will copy the new file to its destination
        $ff->method('moveFileForge')->willReturn(true);
        touch(ForgeConfig::get('ftp_frs_dir_prefix') . '/prj/p123_r456/toto.txt_1299584210');

        $ff->method('create')->willReturn(15225);
        self::assertEquals($ff->createFile($f, ~FRSFileFactory::COMPUTE_MD5), $f);
    }

    /**
     * We should not be able to create a file with the same name,
     * even if the restored one has not yet been moved to the corresponding  pi_rj.
     */

    public function testCreateFileAlreadyMarkedToBeRestoredNotYetMoved()
    {
        // Create toto.txt in the release directory
        touch(ForgeConfig::get('ftp_incoming_dir') . '/toto.txt');
        $this->createReleaseDir('prj', 'p123_r456');

        $p = $this->createMock(Project::class);
        $p->method('getUnixName')->willReturn('prj');

        $r = new FRSRelease();
        $r->setReleaseID(456);
        $r->setPackageID(123);
        $r->setGroupID(111);
        $r->setProject($p);

        $f = new FRSFile();
        $f->setFileName('toto.txt');
        $f->setFilePath('toto.txt_1299584210');
        $f->setRelease($r);
        $f->setFileLocation(ForgeConfig::get('ftp_frs_dir_prefix') . '/prj/p123_r456');

        $ff     = $this->createPartialMock(FRSFileFactory::class, [
            'getSrcDir',
            'isFileBaseNameExists',
            'isSameFileMarkedToBeRestored',
        ]);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->method('log');
        $ff->setLogger($logger);
        $ff->method('getSrcDir')->willReturn(ForgeConfig::get('ftp_incoming_dir'));
        $ff->method('isFileBaseNameExists')->willReturn(false);
        $ff->method('isSameFileMarkedToBeRestored')->willReturn(true);

        self::expectException(FRSFileToBeRestoredException::class);

        $ff->createFile($f, ~FRSFileFactory::COMPUTE_MD5);
    }

    public function testCreateFileNotYetIncoming()
    {
        $p = $this->createMock(Project::class);
        $p->method('getID');

        $r = new FRSRelease();
        $r->setReleaseID(456);
        $r->setPackageID(123);
        $r->setProject($p);

        $f = new FRSFile();
        $f->setFileName('toto.txt');
        $f->setRelease($r);
        $ff = $this->createPartialMock(FRSFileFactory::class, [
            'isFileBaseNameExists',
            'isSameFileMarkedToBeRestored',
            'getSrcDir',
        ]);
        $ff->method('isFileBaseNameExists')->willReturn(false);
        $ff->method('isSameFileMarkedToBeRestored')->willReturn(false);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->method('log');
        $ff->setLogger($logger);
        $ff->method('getSrcDir')->willReturn(ForgeConfig::get('ftp_incoming_dir'));
        self::assertFalse(is_file(ForgeConfig::get('ftp_incoming_dir') . '/toto.txt'));

        self::expectException(FRSFileInvalidNameException::class);

        $ff->createFile($f, ~FRSFileFactory::COMPUTE_MD5);
    }

    public function testCreateFileSkipCompareMD5Checksums()
    {
        $p = $this->createMock(Project::class);
        $p->method('getUnixName')->willReturn('prj');

        $r = new FRSRelease();
        $r->setReleaseID(456);
        $r->setPackageID(123);
        $r->setGroupID(111);
        $r->setProject($p);

        $f = new FRSFile();
        $f->setFileName('toto.txt');
        $f->setRelease($r);
        $f->setFileLocation(ForgeConfig::get('ftp_incoming_dir'));
        $ff = $this->createPartialMock(FRSFileFactory::class, [
            'isFileBaseNameExists',
            'isSameFileMarkedToBeRestored',
            'getSrcDir',
            'moveFileForge',
            'create',
            'compareMd5Checksums',
        ]);
        $ff->method('isFileBaseNameExists')->willReturn(false);
        $ff->method('isSameFileMarkedToBeRestored')->willReturn(false);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->method('log');
        $ff->setLogger($logger);
        $ff->method('getSrcDir')->willReturn(ForgeConfig::get('ftp_incoming_dir'));

        $path = ForgeConfig::get('ftp_incoming_dir') . '/' . $f->getFileName();
        touch(ForgeConfig::get('ftp_incoming_dir') . '/toto.txt');
        $ff->method('moveFileForge')->willReturn(true);
        $ff->method('create')->willReturn(true);

        $ff->expects(self::never())->method('compareMd5Checksums');
        $ff->createFile($f, ~FRSFileFactory::COMPUTE_MD5);

        unlink(ForgeConfig::get('ftp_incoming_dir') . '/toto.txt');
    }

    public function testCreateFileCompareMD5Checksums()
    {
        $project = new Project(['group_id' => 111]);

        $r = $this->createPartialMock(FRSRelease::class, [
            'getProject',
        ]);
        $r->method('getProject')->willReturn($project);
        $r->setReleaseID(456);
        $r->setPackageID(123);
        $r->setGroupID(111);

        $ff = $this->createPartialMock(\FRSFileFactory::class, [
            'isFileBaseNameExists',
            'isSameFileMarkedToBeRestored',
            'compareMd5Checksums',
            'getSrcDir',
        ]);
        $ff->method('isFileBaseNameExists')->willReturn(false);
        $ff->method('isSameFileMarkedToBeRestored')->willReturn(false);
        $ff->method('compareMd5Checksums')->willReturn(false);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->method('log');
        $ff->setLogger($logger);
        $ff->method('getSrcDir')->willReturn(ForgeConfig::get('ftp_incoming_dir'));

        $f = new FRSFile();
        $f->setRelease($r);
        $f->setFileName('toto.txt');

        touch(ForgeConfig::get('ftp_incoming_dir') . '/toto.txt');
        $path = ForgeConfig::get('ftp_incoming_dir') . '/' . $f->getFileName();
        $f->setReferenceMd5('d41d8cd98f00b204e9800998ecf8427e');


        try {
            $ff->createFile($f, FRSFileFactory::COMPUTE_MD5);
        } catch (Exception $e) {
            self::assertInstanceOf(FRSFileMD5SumException::class, $e);
        }

        self::assertNotNull($f->getComputedMd5());
        $frs_file_factory = new FRSFileFactory();
        self::assertTrue($frs_file_factory->compareMd5Checksums($f->getComputedMd5(), $f->getReferenceMd5()));

        unlink(ForgeConfig::get('ftp_incoming_dir') . '/toto.txt');
    }

    public function testCreateFileMoveFileForgeKo()
    {
        $project = new Project(['group_id' => 111]);

        $r = $this->createPartialMock(FRSRelease::class, [
            'getProject',
        ]);
        $r->method('getProject')->willReturn($project);
        $r->setReleaseID(456);
        $r->setPackageID(123);
        $r->setGroupID(111);

        $ff = $this->createPartialMock(FRSFileFactory::class, [
            'isFileBaseNameExists',
            'isSameFileMarkedToBeRestored',
            'getSrcDir',
            'moveFileForge',
        ]);
        $ff->method('isFileBaseNameExists')->willReturn(false);
        $ff->method('isSameFileMarkedToBeRestored')->willReturn(false);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->method('log');
        $ff->setLogger($logger);
        $ff->method('getSrcDir')->willReturn(ForgeConfig::get('ftp_incoming_dir'));

        $f = new FRSFile();
        $f->setRelease($r);

        $ff->method('moveFileForge')->willReturn(false);

        self::expectException(FRSFileForgeException::class);

        $ff->createFile($f, ~FRSFileFactory::COMPUTE_MD5);
    }

    public function testCreateFileDbEntryMovedFile()
    {
        // Create toto.txt in the release directory
        $this->createReleaseDir('prj', 'p123_r456');
        touch(ForgeConfig::get('ftp_frs_dir_prefix') . '/prj/p123_r456/toto.txt');
        touch(ForgeConfig::get('ftp_incoming_dir') . '/toto.txt');

        $p = $this->createMock(Project::class);
        $p->method('getUnixName')->willReturn('prj');
        $p->method('getID');

        $r = new FRSRelease();
        $r->setReleaseID(456);
        $r->setPackageID(123);
        $r->setProject($p);

        $f = new FRSFile();
        $f->setFileName('toto.txt');
        $f->setRelease($r);
        $f->setFileLocation(ForgeConfig::get('ftp_frs_dir_prefix') . '/prj/p123_r456');

        $ff     = $this->createPartialMock(FRSFileFactory::class, [
            'isFileBaseNameExists',
            'isSameFileMarkedToBeRestored',
            'getSrcDir',
            'moveFileForge',
            'create',
        ]);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->method('log');
        $ff->setLogger($logger);
        $ff->method('isFileBaseNameExists')->willReturn(false);
        $ff->method('isSameFileMarkedToBeRestored')->willReturn(false);
        $ff->method('getSrcDir')->willReturn(ForgeConfig::get('ftp_incoming_dir'));
        $ff->method('moveFileForge')->willReturn(true);
        $ff->method('create')->willReturn(false);

        self::expectException(FRSFileDbException::class);

        $ff->createFile($f, ~FRSFileFactory::COMPUTE_MD5);
    }

    public function testDeleteProjectFRSPackagesFail()
    {
        $packageFactory = $this->createMock(FRSPackageFactory::class);

        $releaseFactory = $this->createMock(FRSReleaseFactory::class);
        $releaseFactory->method('_getFRSPackageFactory')->willReturn($packageFactory);

        $fileFactory = $this->createPartialMock(FRSFileFactory::class, [
            '_getFRSReleaseFactory',
            'moveDeletedFilesToStagingArea',
        ]);
        $fileFactory->setLogger($this->createMock(LoggerInterface::class));
        $fileFactory->method('_getFRSReleaseFactory')->willReturn($releaseFactory);

        $fileFactory->expects(self::once())->method('moveDeletedFilesToStagingArea')->willReturn(true);
        $releaseFactory->expects(self::once())->method('deleteProjectReleases')->willReturn(true);
        $packageFactory->expects(self::once())->method('deleteProjectPackages')->willReturn(false);
        $backend = $this->createMock(BackendSystem::class);
        self::assertFalse($fileFactory->deleteProjectFRS(1, $backend));
    }

    public function testDeleteProjectFRSReleasesFail()
    {
        $packageFactory = $this->createMock(FRSPackageFactory::class);

        $releaseFactory = $this->createMock(FRSReleaseFactory::class);
        $releaseFactory->method('_getFRSPackageFactory')->willReturn($packageFactory);

        $fileFactory = $this->createPartialMock(FRSFileFactory::class, [
            '_getFRSReleaseFactory',
            'moveDeletedFilesToStagingArea',
        ]);
        $fileFactory->setLogger($this->createMock(LoggerInterface::class));
        $fileFactory->method('_getFRSReleaseFactory')->willReturn($releaseFactory);

        $fileFactory->expects(self::once())->method('moveDeletedFilesToStagingArea')->willReturn(true);
        $releaseFactory->expects(self::once())->method('deleteProjectReleases')->willReturn(false);
        $packageFactory->expects(self::once())->method('deleteProjectPackages')->willReturn(true);
        $backend = $this->createMock(BackendSystem::class);
        self::assertFalse($fileFactory->deleteProjectFRS(1, $backend));
    }

    public function testDeleteProjectFRSMoveFail()
    {
        $packageFactory = $this->createMock(FRSPackageFactory::class);

        $releaseFactory = $this->createMock(FRSReleaseFactory::class);
        $releaseFactory->method('_getFRSPackageFactory')->willReturn($packageFactory);

        $fileFactory = $this->createPartialMock(FRSFileFactory::class, [
            '_getFRSReleaseFactory',
            'moveDeletedFilesToStagingArea',
        ]);
        $fileFactory->setLogger($this->createMock(LoggerInterface::class));
        $fileFactory->method('_getFRSReleaseFactory')->willReturn($releaseFactory);

        $fileFactory->expects(self::once())->method('moveDeletedFilesToStagingArea')->willReturn(false);
        $releaseFactory->expects(self::once())->method('deleteProjectReleases')->willReturn(true);
        $packageFactory->expects(self::once())->method('deleteProjectPackages')->willReturn(true);
        $backend = $this->createMock(BackendSystem::class);
        self::assertFalse($fileFactory->deleteProjectFRS(1, $backend));
    }

    public function testDeleteProjectFRSSuccess()
    {
        $packageFactory = $this->createMock(FRSPackageFactory::class);

        $releaseFactory = $this->createMock(FRSReleaseFactory::class);
        $releaseFactory->method('_getFRSPackageFactory')->willReturn($packageFactory);

        $fileFactory = $this->createPartialMock(FRSFileFactory::class, [
            '_getFRSReleaseFactory',
            'moveDeletedFilesToStagingArea',
        ]);
        $fileFactory->setLogger($this->createMock(LoggerInterface::class));
        $fileFactory->method('_getFRSReleaseFactory')->willReturn($releaseFactory);

        $fileFactory->expects(self::once())->method('moveDeletedFilesToStagingArea')->willReturn(true);
        $releaseFactory->expects(self::once())->method('deleteProjectReleases')->willReturn(true);
        $packageFactory->expects(self::once())->method('deleteProjectPackages')->willReturn(true);
        $backend = $this->createMock(BackendSystem::class);
        self::assertTrue($fileFactory->deleteProjectFRS(1, $backend));
    }
}
