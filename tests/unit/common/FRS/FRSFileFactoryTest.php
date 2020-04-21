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

class FRSFileFactoryTest extends \PHPUnit\Framework\TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use \Tuleap\ForgeConfigSandbox;
    use \Tuleap\TemporaryTestDirectory;
    use \Tuleap\GlobalLanguageMock;

    protected function setUp(): void
    {
        $GLOBALS['ftp_frs_dir_prefix'] = $this->getTmpDir();
        $GLOBALS['ftp_incoming_dir']   = $this->getTmpDir();
        ForgeConfig::set('ftp_frs_dir_prefix', $GLOBALS['ftp_frs_dir_prefix']);
        ForgeConfig::set('ftp_incoming_dir', $GLOBALS['ftp_incoming_dir']);
        copy(__DIR__ . '/_fixtures/file_sample', $GLOBALS['ftp_incoming_dir'] . '/file_sample');
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['ftp_frs_dir_prefix']);
        unset($GLOBALS['ftp_incoming_dir']);
    }

    public function testgetUploadSubDirectory()
    {
        $package_id = rand(1, 1000);
        $release_id = rand(1, 1000);

        $release = new FRSRelease();
        $release->setPackageID($package_id);
        $release->setReleaseID($release_id);

        $file_fact = new FRSFileFactory();

        $sub_dir = $file_fact->getUploadSubDirectory($release);
        $this->assertEquals($sub_dir, 'p' . $package_id . '_r' . $release_id);
    }

    public function testPurgeDeletedFiles()
    {
        $ff = \Mockery::mock(\FRSFileFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $ff->setLogger(\Mockery::spy(\Psr\Log\LoggerInterface::class));
        $backend = \Mockery::spy(\BackendSystem::class);
        $ff->shouldReceive('moveDeletedFilesToStagingArea')->once()->andReturns(true);
        $ff->shouldReceive('purgeFiles')->with(1287504083, $backend)->once()->andReturns(true);
        $ff->shouldReceive('cleanStaging')->once()->andReturns(true);
        $ff->shouldReceive('restoreDeletedFiles')->with($backend)->once()->andReturns(true);

        $this->assertTrue($ff->moveFiles(1287504083, $backend));
    }

    public function testMoveFilesMoveStagingError()
    {
        $ff = \Mockery::mock(\FRSFileFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $ff->setLogger(\Mockery::spy(\Psr\Log\LoggerInterface::class));
        $ff->shouldReceive('moveDeletedFilesToStagingArea')->once()->andReturns(false);
        $backend = \Mockery::spy(\BackendSystem::class);
        $ff->shouldReceive('purgeFiles')->with(1287504083, $backend)->once()->andReturns(true);
        $ff->shouldReceive('cleanStaging')->once()->andReturns(true);
        $ff->shouldReceive('restoreDeletedFiles')->with($backend)->once()->andReturns(true);

        $this->assertFalse($ff->moveFiles(1287504083, $backend));
    }

    public function testMoveFilesPurgeError()
    {
        $ff = \Mockery::mock(\FRSFileFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $ff->setLogger(\Mockery::spy(\Psr\Log\LoggerInterface::class));
        $backend = \Mockery::spy(\BackendSystem::class);
        $ff->shouldReceive('moveDeletedFilesToStagingArea')->once()->andReturns(true);
        $ff->shouldReceive('purgeFiles')->with(1287504083, $backend)->once()->andReturns(false);
        $ff->shouldReceive('cleanStaging')->once()->andReturns(true);
        $ff->shouldReceive('restoreDeletedFiles')->with($backend)->once()->andReturns(true);

        $this->assertFalse($ff->moveFiles(1287504083, $backend));
    }

    public function testMoveFilesCleanStagingError()
    {
        $ff = \Mockery::mock(\FRSFileFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $ff->setLogger(\Mockery::spy(\Psr\Log\LoggerInterface::class));
        $backend = \Mockery::spy(\BackendSystem::class);
        $ff->shouldReceive('moveDeletedFilesToStagingArea')->once()->andReturns(true);
        $ff->shouldReceive('purgeFiles')->with(1287504083, $backend)->once()->andReturns(true);
        $ff->shouldReceive('cleanStaging')->once()->andReturns(false);
        $ff->shouldReceive('restoreDeletedFiles')->with($backend)->once()->andReturns(true);

        $this->assertFalse($ff->moveFiles(1287504083, $backend));
    }

    public function testMoveFilesRestoreDeletedError()
    {
        $ff = \Mockery::mock(\FRSFileFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $ff->setLogger(\Mockery::spy(\Psr\Log\LoggerInterface::class));
        $backend = \Mockery::spy(\BackendSystem::class);
        $ff->shouldReceive('moveDeletedFilesToStagingArea')->once()->andReturns(true);
        $ff->shouldReceive('purgeFiles')->with(1287504083, $backend)->once()->andReturns(true);
        $ff->shouldReceive('cleanStaging')->once()->andReturns(true);
        $ff->shouldReceive('restoreDeletedFiles')->with($backend)->once()->andReturns(false);

        $this->assertFalse($ff->moveFiles(1287504083, $backend));
    }

    public function testMoveFilesCatchesExceptionAndLogThem()
    {
        $ff = \Mockery::mock(\FRSFileFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $ff->setLogger(\Mockery::spy(\Psr\Log\LoggerInterface::class));
        $ff->shouldReceive('purgeFiles')->andThrows(new RuntimeException("Error while doing things"));
        $ff->shouldReceive('moveDeletedFilesToStagingArea')->andReturns(true);
        $ff->shouldReceive('cleanStaging')->andReturns(true);
        $ff->shouldReceive('restoreDeletedFiles')->andReturns(true);

        $backend = \Mockery::spy(\BackendSystem::class);
        $backend->shouldReceive('log')->with("Error while doing things", Backend::LOG_ERROR)->once();

        $this->assertFalse($ff->moveFiles(1287504083, $backend));
    }

    public function testMoveDeletedFilesToStagingAreaWithNoFiles()
    {
        $ff = \Mockery::mock(\FRSFileFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $ff->setLogger(\Mockery::spy(\Psr\Log\LoggerInterface::class));

        $dao = \Mockery::spy(\FRSFileDao::class);
        $dao->shouldReceive('searchStagingCandidates')->andReturns(\TestHelper::emptyDar());
        $ff->shouldReceive('_getFRSFileDao')->andReturns($dao);
        $backend = \Mockery::spy(\BackendSystem::class);

        $ff->shouldReceive('moveDeletedFileToStagingArea')->never();

        $this->assertTrue($ff->moveDeletedFilesToStagingArea($backend));
    }

    private function createReleaseDir($release_name, $dir_name)
    {
        // Create temp file in a fake release
        if (!is_dir($GLOBALS['ftp_frs_dir_prefix'] . "/$release_name/$dir_name")) {
            mkdir($GLOBALS['ftp_frs_dir_prefix'] . "/$release_name/$dir_name", 0750, true);
        }
    }

    public function testMoveDeletedFileToStagingArea()
    {
        $ff = \Mockery::mock(\FRSFileFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $ff->setLogger(\Mockery::spy(\Psr\Log\LoggerInterface::class));

        $this->createReleaseDir('prj', 'p1_r1');
        $filepath = $GLOBALS['ftp_frs_dir_prefix'] . '/prj/p1_r1/foobar.xls';
        touch($filepath);
        $this->assertTrue(is_file($filepath));
        $file = \Mockery::spy(\FRSFile::class);
        $file->shouldReceive('getFileID')->andReturns(12);
        $file->shouldReceive('getFileLocation')->andReturns($filepath);

        $dao = \Mockery::spy(\FRSFileDao::class);
        $dao->shouldReceive('setFileInDeletedList')->with(12)->once()->andReturns(true);
        $ff->shouldReceive('_getFRSFileDao')->andReturns($dao);
        $backend = \Mockery::spy(\BackendSystem::class);

        $this->assertTrue($ff->moveDeletedFileToStagingArea($file, $backend));

        $this->assertTrue(is_file($GLOBALS['ftp_frs_dir_prefix'] . '/DELETED/prj/p1_r1/foobar.xls.12'));
        $this->assertFalse(is_file($GLOBALS['ftp_frs_dir_prefix'] . '/prj/p1_r1/foobar.xls'));
        $this->assertFalse(is_dir($GLOBALS['ftp_frs_dir_prefix'] . '/prj/p1_r1'));
    }

    /**
     * If for one reason the file to delete appears to be already deleted, mark it as
     * deleted and purged at the very same date
     *
     */
    public function testMoveDeletedFileToStagingAreaButFileDoesntExist()
    {
        $ff = \Mockery::mock(\FRSFileFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $ff->setLogger(\Mockery::spy(\Psr\Log\LoggerInterface::class));

        // Create temp file in a fake release
        $this->createReleaseDir('prj', 'p1_r1');
        $filepath = $GLOBALS['ftp_frs_dir_prefix'] . '/prj/p1_r1/foobar.xls';
        $this->assertFalse(is_file($filepath), "The file shouldn't exist, this is the base of the test!");
        $file = \Mockery::spy(\FRSFile::class);
        $file->shouldReceive('getFileID')->andReturns(12);
        $file->shouldReceive('getFileLocation')->andReturns($filepath);

        $dao = \Mockery::spy(\FRSFileDao::class); // Mark as deleted
        $dao->shouldReceive('setFileInDeletedList')->with(12)->once()->andReturns(true); // Mark as purged
        $dao->shouldReceive('setPurgeDate')->with(12, $_SERVER['REQUEST_TIME'])->once()->andReturns(true);
        $ff->shouldReceive('_getFRSFileDao')->andReturns($dao);
        $backend = \Mockery::spy(\BackendSystem::class);
        $backend->shouldReceive('log')->with('*', 'warn')->ordered();
        $backend->shouldReceive('log')->with('*', 'error')->ordered();

        $this->assertFalse($ff->moveDeletedFileToStagingArea($file, $backend));

        $this->assertFalse(is_file($GLOBALS['ftp_frs_dir_prefix'] . '/DELETED/prj/p1_r1/foobar.xls.12'));
        $this->assertFalse(is_file($GLOBALS['ftp_frs_dir_prefix'] . '/prj/p1_r1/foobar.xls'));
        $this->assertFalse(is_dir($GLOBALS['ftp_frs_dir_prefix'] . '/prj/p1_r1'));
    }

    public function testMoveDeletedFileToStagingAreaReleaseNotEmpty()
    {
        $ff = \Mockery::mock(\FRSFileFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $ff->setLogger(\Mockery::spy(\Psr\Log\LoggerInterface::class));

        // Create temp file in a fake release
        $this->createReleaseDir('prj', 'p1_r1');
        $filepath = $GLOBALS['ftp_frs_dir_prefix'] . '/prj/p1_r1/foobar.xls';
        touch($filepath);
        $this->assertTrue(is_file($filepath));
        $file = \Mockery::spy(\FRSFile::class);
        $file->shouldReceive('getFileID')->andReturns(12);
        $file->shouldReceive('getFileLocation')->andReturns($filepath);
        // Second file, not deleted
        touch($GLOBALS['ftp_frs_dir_prefix'] . '/prj/p1_r1/barfoo.doc');

        $dao = \Mockery::spy(\FRSFileDao::class);
        $dao->shouldReceive('setFileInDeletedList')->with(12)->once()->andReturns(true);
        $ff->shouldReceive('_getFRSFileDao')->andReturns($dao);
        $backend = \Mockery::spy(\BackendSystem::class);

        $this->assertTrue($ff->moveDeletedFileToStagingArea($file, $backend));

        $this->assertTrue(is_file($GLOBALS['ftp_frs_dir_prefix'] . '/DELETED/prj/p1_r1/foobar.xls.12'));
        $this->assertFalse(is_file($GLOBALS['ftp_frs_dir_prefix'] . '/prj/p1_r1/foobar.xls'));
        $this->assertTrue(is_file($GLOBALS['ftp_frs_dir_prefix'] . '/prj/p1_r1/barfoo.doc'), 'The other file in the release must not be deleted');
    }

    public function testMoveDeletedFilesToStagingAreaFail()
    {
        $ff = \Mockery::mock(\FRSFileFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $ff->setLogger(\Mockery::spy(\Psr\Log\LoggerInterface::class));

        $dao = \Mockery::spy(\FRSFileDao::class);
        $dao->shouldReceive('searchStagingCandidates')->andReturns(TestHelper::arrayToDar(['file_id' => 12]));
        $ff->shouldReceive('_getFRSFileDao')->andReturns($dao);

        $backend = \Mockery::spy(\BackendSystem::class);
        $ff->shouldReceive('moveDeletedFileToStagingArea')->with(
            Mockery::on(function (FRSFile $file) {
                return $file->getFileID() === 12;
            }),
            $backend
        )->once()->andReturns(false);

        $this->assertFalse($ff->moveDeletedFilesToStagingArea($backend));
    }

    public function testMoveDeletedFilesToStagingAreaWithOneFile()
    {
        $ff = \Mockery::mock(\FRSFileFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $ff->setLogger(\Mockery::spy(\Psr\Log\LoggerInterface::class));

        $dao = \Mockery::spy(\FRSFileDao::class);
        $dao->shouldReceive('searchStagingCandidates')->once()->andReturn(TestHelper::arrayToDar(['file_id' => 12]));
        $ff->shouldReceive('_getFRSFileDao')->andReturns($dao);

        $backend = \Mockery::spy(\BackendSystem::class);
        $ff->shouldReceive('moveDeletedFileToStagingArea')->with(
            Mockery::on(function (FRSFile $file) {
                return $file->getFileID() === 12;
            }),
            $backend
        )->once()->andReturns(true);

        $this->assertTrue($ff->moveDeletedFilesToStagingArea($backend));
    }

    public function testPurgeFilesWithNoFiles()
    {
        $dao = \Mockery::spy(\FRSFileDao::class);
        $dao->shouldReceive('searchFilesToPurge')->with(1287504083)->once()->andReturn(TestHelper::emptyDar());

        $ff = \Mockery::mock(\FRSFileFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $ff->setLogger(\Mockery::spy(\Psr\Log\LoggerInterface::class));
        $ff->shouldReceive('_getFRSFileDao')->andReturns($dao);

        $ff->shouldReceive('purgeFile')->never();
        $backend = \Mockery::spy(\BackendSystem::class);
        $this->assertTrue($ff->purgeFiles(1287504083, $backend));
    }

    public function testPurgeFilesWithOneFile()
    {
        $dao = \Mockery::spy(\FRSFileDao::class);
        $dao->shouldReceive('searchFilesToPurge')->with(1287504083)->once()->andReturn(TestHelper::arrayToDar(['file_id' => 12]));

        $ff = \Mockery::mock(\FRSFileFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $ff->setLogger(\Mockery::spy(\Psr\Log\LoggerInterface::class));
        $ff->shouldReceive('_getFRSFileDao')->andReturns($dao);

        $backend = \Mockery::spy(\BackendSystem::class);
        $ff->shouldReceive('purgeFile')->with(
            Mockery::on(function (FRSFile $file) {
                return $file->getFileID() === 12;
            }),
            $backend
        )->once()->andReturns(true);

        $this->assertTrue($ff->purgeFiles(1287504083, $backend));
    }

    public function testPurgeFileSucceed()
    {
        $ff = \Mockery::mock(\FRSFileFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $ff->setLogger(\Mockery::spy(\Psr\Log\LoggerInterface::class));

        // Create temp file
        $filepath = $GLOBALS['ftp_frs_dir_prefix'] . '/DELETED/prj/p1_r1/foobar.xls.12';
        mkdir(dirname($filepath), 0750, true);
        touch($filepath);

        $this->assertTrue(is_file($filepath));
        $file = \Mockery::spy(\FRSFile::class);
        $file->shouldReceive('getFileID')->andReturns(12);
        $file->shouldReceive('getFileName')->andReturns('p1_r1/foobar.xls');
        $file->shouldReceive('getFileLocation')->andReturns($GLOBALS['ftp_frs_dir_prefix'] . '/prj/p1_r1/foobar.xls');

        $dao = \Mockery::spy(\FRSFileDao::class);
        $dao->shouldReceive('setPurgeDate')->with(12, Mockery::any())->once()->andReturns(true);
        $ff->shouldReceive('_getFRSFileDao')->andReturns($dao);

        $backend = \Mockery::spy(\BackendSystem::class);
        $backend->shouldReceive('log')->never();
        $ff->shouldReceive('archiveBeforePurge')->with(
            Mockery::on(function (FRSFile $file) {
                return $file->getFileID() === 12;
            }),
            $backend
        )->once()->andReturns(true);

        $ff->purgeFile($file, $backend);

        $this->assertFalse(is_file($filepath), "File should be deleted");
    }

    public function testPurgeFileDBUpdateFails()
    {
        $ff = \Mockery::mock(\FRSFileFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $ff->setLogger(\Mockery::spy(\Psr\Log\LoggerInterface::class));

        // Create temp file
        $filepath = $GLOBALS['ftp_frs_dir_prefix'] . '/DELETED/prj/p1_r1/foobar.xls.12';
        mkdir(dirname($filepath), 0750, true);
        touch($filepath);
        $this->assertTrue(is_file($filepath));
        $file = \Mockery::spy(\FRSFile::class);
        $file->shouldReceive('getFileID')->andReturns(12);
        $file->shouldReceive('getFileName')->andReturns('p1_r1/foobar.xls');
        $file->shouldReceive('getFileLocation')->andReturns($GLOBALS['ftp_frs_dir_prefix'] . '/prj/p1_r1/foobar.xls');

        $dao = \Mockery::spy(\FRSFileDao::class);
        $dao->shouldReceive('setPurgeDate')->with(12, Mockery::any())->once()->andReturns(false);
        $ff->shouldReceive('_getFRSFileDao')->andReturns($dao);
        $ff->shouldReceive('archiveBeforePurge')->andReturns(true);

        $backend = \Mockery::spy(\BackendSystem::class);
        $backend->shouldReceive('log')->with('File ' . $filepath . ' not purged, Set purge date in DB fail', 'error')->once();
        $this->assertFalse($ff->purgeFile($file, $backend));

        $this->assertFalse(is_file($filepath), "File should be deleted");
    }

    public function testPurgeFileSystemCopyFails()
    {
        $ff = \Mockery::mock(\FRSFileFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $ff->setLogger(\Mockery::spy(\Psr\Log\LoggerInterface::class));

        // Create temp file
        $filepath = $GLOBALS['ftp_frs_dir_prefix'] . '/DELETED/prj/p1_r1/foobar.xls.12';
        mkdir(dirname($filepath), 0750, true);
        touch($filepath);
        $this->assertTrue(is_file($filepath));
        $file = \Mockery::spy(\FRSFile::class);
        $file->shouldReceive('getFileID')->andReturns(12);
        $file->shouldReceive('getFileName')->andReturns('p1_r1/foobar.xls');
        $file->shouldReceive('getFileLocation')->andReturns($GLOBALS['ftp_frs_dir_prefix'] . '/prj/p1_r1/foobar.xls');

        $dao = \Mockery::spy(\FRSFileDao::class);
        $dao->shouldReceive('setPurgeDate')->never();
        $ff->shouldReceive('archiveBeforePurge')->andReturns(false);

        $backend = \Mockery::spy(\BackendSystem::class);
        $backend->shouldReceive('log')->with('File ' . $filepath . ' not purged, unlink failed', 'error')->once();
        $this->assertFalse($ff->purgeFile($file, $backend));
    }

    public function testPurgeFileWithFileNotFoundInFS()
    {
        $ff = \Mockery::mock(\FRSFileFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $ff->setLogger(\Mockery::spy(\Psr\Log\LoggerInterface::class));

        $filepath = $GLOBALS['ftp_frs_dir_prefix'] . '/DELETED/prj/p1_r1/foobar.xls.12';

        $this->assertFalse(is_file($filepath));
        $file = \Mockery::spy(\FRSFile::class);
        $file->shouldReceive('getFileID')->andReturns(12);
        $file->shouldReceive('getFileName')->andReturns('p1_r1/foobar.xls');
        $file->shouldReceive('getFileLocation')->andReturns($GLOBALS['ftp_frs_dir_prefix'] . '/prj/p1_r1/foobar.xls');

        $dao = \Mockery::spy(\FRSFileDao::class);
        $dao->shouldReceive('setPurgeDate')->with(12, Mockery::any())->once()->andReturns(true);
        $ff->shouldReceive('_getFRSFileDao')->andReturns($dao);

        $backend = \Mockery::spy(\BackendSystem::class);
        $backend->shouldReceive('log')->with('File ' . $filepath . ' not found on file system, automatically marked as purged', \Psr\Log\LogLevel::WARNING)->once();
        $this->assertTrue($ff->purgeFile($file, $backend));
        $ff->shouldReceive('archiveBeforePurge')->never();
    }

    private function createDeletedReleaseDir($release_name, $dir_name)
    {
        // Create temp file in a fake release
        if (!is_dir($GLOBALS['ftp_frs_dir_prefix'] . '/DELETED/' . $release_name . '/' . $dir_name)) {
            mkdir($GLOBALS['ftp_frs_dir_prefix'] . '/DELETED/' . $release_name . '/' . $dir_name, 0750, true);
        }
    }

    public function testRemoveStagingEmptyDirectories()
    {
        $ff = new FRSFileFactory();
        $backend = \Mockery::spy(\BackendSystem::class);

        $this->createDeletedReleaseDir('prj', 'p1_r1');
        $this->createDeletedReleaseDir('prj2', 'p2_r5');
        $this->createDeletedReleaseDir('prj3', 'p7_r8');
        $this->createDeletedReleaseDir('prj3', 'p9_r10');

        touch($GLOBALS['ftp_frs_dir_prefix'] . '/DELETED/prj2/p2_r5/file.txt.7');
        touch($GLOBALS['ftp_frs_dir_prefix'] . '/DELETED/prj3/p9_r10/foo.txt.12');

        $this->assertTrue($ff->cleanStaging($backend));
        $this->assertFalse(is_dir($GLOBALS['ftp_frs_dir_prefix'] . '/DELETED/prj'));
        $this->assertTrue(is_file($GLOBALS['ftp_frs_dir_prefix'] . '/DELETED/prj2/p2_r5/file.txt.7'));
        $this->assertFalse(is_dir($GLOBALS['ftp_frs_dir_prefix'] . '/DELETED/prj3/p7_r8'));
        $this->assertTrue(is_file($GLOBALS['ftp_frs_dir_prefix'] . '/DELETED/prj3/p9_r10/foo.txt.12'));
    }

    public function testRestoreFileSucceed()
    {
        $fileFactory = \Mockery::mock(\FRSFileFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $fileFactory->setLogger(\Mockery::spy(\Psr\Log\LoggerInterface::class));

        // Create temp file
        $filepath = $GLOBALS['ftp_frs_dir_prefix'] . '/DELETED/prj/p1_r1/toto.xls.12';
        $this->createDeletedReleaseDir('prj', 'p1_r1');
        touch($filepath);
        $this->assertTrue(is_dir(dirname($filepath)));

        $file = \Mockery::spy(\FRSFile::class);
        $file->shouldReceive('getFileID')->andReturns(12);
        $file->shouldReceive('getFileName')->andReturns('p1_r1/toto.xls');
        $file->shouldReceive('getFileLocation')->andReturns($GLOBALS['ftp_frs_dir_prefix'] . '/prj/p1_r1/toto.xls');
        $project = \Mockery::spy(\Project::class);
        $file->shouldReceive('getGroup')->andReturns($project);
        $this->createReleaseDir('prj', 'p1_r1');
        $this->assertTrue(is_dir($GLOBALS['ftp_frs_dir_prefix'] . '/prj/p1_r1/'));

        $dao = \Mockery::spy(\FRSFileDao::class);
        $dao->shouldReceive('restoreFile')->once()->andReturns(true);
        $fileFactory->shouldReceive('_getFRSFileDao')->andReturns($dao);
        $backend = \Mockery::spy(\BackendSystem::class);

        $user = \Mockery::spy(\PFUser::class);
        $um = \Mockery::spy(\UserManager::class);
        $um->shouldReceive('getCurrentUser')->andReturns($user);
        $fileFactory->shouldReceive('_getUserManager')->andReturns($um);
        $fileFactory->shouldReceive('_getEventManager')->andReturns(\Mockery::spy(EventManager::class));
        $release = \Mockery::spy(FRSRelease::class);
        $release->shouldReceive('isDeleted')->andReturns(false);
        $releaseFactory = \Mockery::spy(\FRSReleaseFactory::class);
        $releaseFactory->shouldReceive('getFRSReleaseFromDb')->andReturns($release);
        $fileFactory->shouldReceive('_getFRSReleaseFactory')->andReturns($releaseFactory);

        $this->assertTrue($fileFactory->restoreFile($file, $backend));
    }

    public function testRestoreFileNotExists()
    {
        $fileFactory = \Mockery::mock(\FRSFileFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $fileFactory->setLogger(\Mockery::spy(\Psr\Log\LoggerInterface::class));

        // Create temp file
        $filepath = $GLOBALS['ftp_frs_dir_prefix'] . '/DELETED/prj/p1_r1/toto.xls.5';
        $this->createDeletedReleaseDir('prj', 'p1_r1');
        $this->createReleaseDir('prj', 'p1_r1');
        $this->assertFalse(file_exists($filepath));

        $file = \Mockery::spy(\FRSFile::class);
        $file->shouldReceive('getFileID')->andReturns(5);
        $file->shouldReceive('getFileName')->andReturns('p1_r1/toto.xls');
        $file->shouldReceive('getFileLocation')->andReturns($GLOBALS['ftp_frs_dir_prefix'] . '/prj/p1_r1/toto.xls');
        $this->assertTrue(is_dir(dirname($GLOBALS['ftp_frs_dir_prefix'] . '/prj/p1_r1/')));

        $dao = \Mockery::spy(\FRSFileDao::class);
        $dao->shouldReceive('restoreFile')->never();
        $fileFactory->shouldReceive('_getFRSFileDao')->andReturns($dao);
        $backend = \Mockery::spy(\BackendSystem::class);
        $backend->shouldReceive('chgrp')->andReturns(true);

        $release = \Mockery::spy(FRSRelease::class);
        $release->shouldReceive('isDeleted')->andReturns(false);
        $releaseFactory = \Mockery::spy(\FRSReleaseFactory::class);
        $releaseFactory->shouldReceive('getFRSReleaseFromDb')->andReturns($release);
        $fileFactory->shouldReceive('_getFRSReleaseFactory')->andReturns($releaseFactory);

        $this->assertFalse($fileFactory->restoreFile($file, $backend));
    }

    public function testRestoreFileLocationNotExists()
    {
        $fileFactory = \Mockery::mock(\FRSFileFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $fileFactory->setLogger(\Mockery::spy(\Psr\Log\LoggerInterface::class));

        // Create temp file
        $filepath = $GLOBALS['ftp_frs_dir_prefix'] . '/DELETED/prj/p2_r1/toto.xls.12';
        $this->createReleaseDir('prj', 'p2_r1');
        $this->createDeletedReleaseDir('prj', 'p2_r1');
        touch($filepath);
        $this->assertTrue(is_dir(dirname($filepath)));
        $backend = \Mockery::spy(\BackendSystem::class);
        $file = \Mockery::spy(\FRSFile::class);
        $file->shouldReceive('getFileID')->andReturns(12);
        $file->shouldReceive('getFileName')->andReturns('p2_r1/toto.xls');
        $file->shouldReceive('getFileLocation')->andReturns($GLOBALS['ftp_frs_dir_prefix'] . '/prj/p2_r1/toto.xls');
        $project = \Mockery::spy(\Project::class);
        $file->shouldReceive('getGroup')->andReturns($project);
        $this->assertTrue(is_dir(dirname($GLOBALS['ftp_frs_dir_prefix'] . '/prj/p2_r1/')));
        $backend->shouldReceive('chgrp')->andReturns(true);

        $dao = \Mockery::spy(\FRSFileDao::class);
        $dao->shouldReceive('restoreFile')->once()->andReturns(true);
        $fileFactory->shouldReceive('_getFRSFileDao')->andReturns($dao);

        $user = \Mockery::spy(\PFUser::class);
        $um = \Mockery::spy(\UserManager::class);
        $um->shouldReceive('getCurrentUser')->andReturns($user);
        $fileFactory->shouldReceive('_getUserManager')->andReturns($um);
        $fileFactory->shouldReceive('_getEventManager')->andReturns(\Mockery::spy(EventManager::class));
        $release = \Mockery::spy(FRSRelease::class);
        $release->shouldReceive('isDeleted')->andReturns(false);
        $releaseFactory = \Mockery::spy(\FRSReleaseFactory::class);
        $releaseFactory->shouldReceive('getFRSReleaseFromDb')->andReturns($release);
        $fileFactory->shouldReceive('_getFRSReleaseFactory')->andReturns($releaseFactory);

        $this->assertTrue($fileFactory->restoreFile($file, $backend));
    }

    public function testRestoreFileDBUpdateFails()
    {
        $fileFactory = \Mockery::mock(\FRSFileFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $fileFactory->setLogger(\Mockery::spy(\Psr\Log\LoggerInterface::class));

        // Create temp file
        $filepath = $GLOBALS['ftp_frs_dir_prefix'] . '/DELETED/prj/p3_r1/toto.xls.12';
        $this->createDeletedReleaseDir('prj', 'p3_r1');
        $this->createReleaseDir('prj', 'p3_r1');
        touch($filepath);
        $this->assertTrue(is_dir(dirname($filepath)));

        $file = \Mockery::spy(\FRSFile::class);
        $file->shouldReceive('getFileID')->andReturns(12);
        $file->shouldReceive('getFileName')->andReturns('p3_r1/toto.xls');
        $file->shouldReceive('getFileLocation')->andReturns($GLOBALS['ftp_frs_dir_prefix'] . '/prj/p3_r1/toto.xls');
        $project = \Mockery::spy(\Project::class);
        $file->shouldReceive('getGroup')->andReturns($project);
        $this->assertTrue(is_dir(dirname($GLOBALS['ftp_frs_dir_prefix'] . '/prj/p3_r1/')));

        $dao = \Mockery::spy(\FRSFileDao::class);
        $dao->shouldReceive('restoreFile')->once()->andReturns(false);
        $fileFactory->shouldReceive('_getFRSFileDao')->andReturns($dao);
        $backend = \Mockery::spy(\BackendSystem::class);
        $backend->shouldReceive('chgrp')->andReturns(true);

        $user = \Mockery::spy(\PFUser::class);
        $um = \Mockery::spy(\UserManager::class);
        $um->shouldReceive('getCurrentUser')->andReturns($user);
        $fileFactory->shouldReceive('_getUserManager')->andReturns($um);
        $fileFactory->shouldReceive('_getEventManager')->andReturns(\Mockery::spy(EventManager::class));
        $release = \Mockery::spy(FRSRelease::class);
        $release->shouldReceive('isDeleted')->andReturns(false);
        $releaseFactory = \Mockery::spy(\FRSReleaseFactory::class);
        $releaseFactory->shouldReceive('getFRSReleaseFromDb')->andReturns($release);
        $fileFactory->shouldReceive('_getFRSReleaseFactory')->andReturns($releaseFactory);

        $this->assertFalse($fileFactory->restoreFile($file, $backend));
    }

    public function testRestoreFileInDeletedRelease()
    {
        $fileFactory = \Mockery::mock(\FRSFileFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $fileFactory->setLogger(\Mockery::spy(\Psr\Log\LoggerInterface::class));

        // Create temp file
        $filepath = $GLOBALS['ftp_frs_dir_prefix'] . '/DELETED/prj/p3_r1/toto.xls.12';
        $this->createDeletedReleaseDir('prj', 'p3_r1');
        touch($filepath);
        $this->assertTrue(is_dir(dirname($filepath)));

        $release = \Mockery::spy(FRSRelease::class);
        $release->shouldReceive('isDeleted')->andReturns(true);
        $releaseFactory = \Mockery::spy(\FRSReleaseFactory::class);
        $releaseFactory->shouldReceive('getFRSReleaseFromDb')->andReturns($release);
        $fileFactory->shouldReceive('_getFRSReleaseFactory')->andReturns($releaseFactory);
        $dao = \Mockery::spy(\FRSFileDao::class);
        $dao->shouldReceive('cancelRestore')->once();
        $fileFactory->shouldReceive('_getFRSFileDao')->andReturns($dao);
        $file = \Mockery::spy(\FRSFile::class);
        $backend = \Mockery::spy(\BackendSystem::class);

        $this->assertFalse($fileFactory->restoreFile($file, $backend));
        $this->assertTrue(is_dir(dirname($filepath)));
        $this->assertFalse(file_exists($GLOBALS['ftp_frs_dir_prefix'] . '/prj/p3_r1/toto.xls'));
        $this->assertFalse(is_dir($GLOBALS['ftp_frs_dir_prefix'] . '/prj/p3_r1'));
    }

    public function testRestoreDeletedFiles()
    {
        $refFile = new FRSFile(array('file_id' => 12));

        $dao = \Mockery::spy(\FRSFileDao::class);
        $dao->shouldReceive('searchFilesToRestore')->once()->andReturn(TestHelper::arrayToDar(['file_id' => 12]));

        $ff = \Mockery::mock(\FRSFileFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $ff->setLogger(\Mockery::spy(\Psr\Log\LoggerInterface::class));
        $ff->shouldReceive('_getFRSFileDao')->andReturns($dao);
        $backend  = \Mockery::spy(\BackendSystem::class);
        $ff->shouldReceive('restoreFile')->with(
            Mockery::any(function (FRSFile $file) {
                $file->getFileID() === 12;
            }),
            $backend
        )->once()->andReturns(true);

        $this->assertTrue($ff->restoreDeletedFiles($backend));
    }

    public function testRestoreDeletedFilesReturnFalse()
    {
        $dao = \Mockery::spy(\FRSFileDao::class);
        $dao->shouldReceive('searchFilesToRestore')->andReturn(TestHelper::arrayToDar(['file_id' => 12], ['file_id' => 13]));

        $ff = \Mockery::mock(\FRSFileFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $ff->setLogger(\Mockery::spy(\Psr\Log\LoggerInterface::class));
        $ff->shouldReceive('_getFRSFileDao')->andReturns($dao);
        $backend  = \Mockery::spy(\BackendSystem::class);
        $ff->shouldReceive('restoreFile')->once()->andReturns(false);
        $ff->shouldReceive('restoreFile')->once()->andReturns(true);

        $this->assertFalse($ff->restoreDeletedFiles($backend));
    }

    public function testRestoreDeletedFilesDBError()
    {
        $refFile = new FRSFile(array('file_id' => 12));

        $dao = \Mockery::spy(\FRSFileDao::class);
        $dao->shouldReceive('searchFilesToRestore')->once()->andReturns(Mockery::mock(DataAccessResult::class, ['isError' => true]));

        $ff = \Mockery::mock(\FRSFileFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $ff->setLogger(\Mockery::spy(\Psr\Log\LoggerInterface::class));
        $ff->shouldReceive('_getFRSFileDao')->andReturns($dao);
        $backend  = \Mockery::spy(\BackendSystem::class);
        $ff->shouldReceive('restoreFile')->never()->andReturns(false);

        $this->assertFalse($ff->restoreDeletedFiles($backend));
    }

    public function testRestoreDeletedFilesNoFiles()
    {
        $refFile = new FRSFile(array('file_id' => 12));

        $dao = \Mockery::spy(\FRSFileDao::class);
        $dao->shouldReceive('searchFilesToRestore')->once()->andReturn(TestHelper::emptyDar());

        $ff = \Mockery::mock(\FRSFileFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $ff->setLogger(\Mockery::spy(\Psr\Log\LoggerInterface::class));
        $ff->shouldReceive('_getFRSFileDao')->andReturns($dao);
        $backend  = \Mockery::spy(\BackendSystem::class);
        $ff->shouldReceive('restoreFile')->never()->andReturns(false);

        $this->assertTrue($ff->restoreDeletedFiles($backend));
    }

    public function testCompareMd5ChecksumsFail()
    {
        $fileFactory = new FRSFileFactory();
        $this->assertFalse($fileFactory->compareMd5Checksums('da1e100dc9e7bebb810985e37875de36', 'da1e100dc9e7bebb810985e37875de38'));
    }

    public function testCompareMd5ChecksumsSucceedeEmptyHashes()
    {
        $fileFactory = new FRSFileFactory();
        $this->assertTrue($fileFactory->compareMd5Checksums('', ''));
    }

    public function testCompareMd5ChecksumsSucceedeEmptyReference()
    {
        $fileFactory = new FRSFileFactory();
        $this->assertTrue($fileFactory->compareMd5Checksums('da1e100dc9e7bebb810985e37875de36', ''));
    }

    public function testCompareMd5ChecksumsSucceedeEmptyComputed()
    {
        $fileFactory = new FRSFileFactory();
        $this->assertTrue($fileFactory->compareMd5Checksums('', 'da1e100dc9e7bebb810985e37875de38'));
    }

    public function testCompareMd5ChecksumsSucceededComparison()
    {
        $fileFactory = new FRSFileFactory();
        $this->assertTrue($fileFactory->compareMd5Checksums('da1e100dc9e7bebb810985e37875de38', 'da1e100dc9e7bebb810985e37875de38'));
        $this->assertTrue($fileFactory->compareMd5Checksums('da1e100dc9e7bebb810985e37875de38', 'DA1E100DC9E7BEBB810985E37875DE38'));
    }

    public function testMoveFileforgeOk()
    {
        // Create target release directory
        $this->createReleaseDir('prj', 'p123_r456');
        touch($GLOBALS['ftp_incoming_dir'] . '/toto.txt');

        // Try to release a file named toto.txt in the same release
        $p = \Mockery::spy(\Project::class);
        $p->shouldReceive('getUnixName')->with(false)->once()->andReturns('prj');

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
        $this->assertTrue($res);
        $this->assertFileExists($GLOBALS['ftp_frs_dir_prefix'] . '/prj/' . $f->getFilePath());
    }

    public function testMoveFileforgeFileExist()
    {
        // Create toto.txt in the release directory
        $this->createReleaseDir('prj', 'p123_r456');
        touch($GLOBALS['ftp_frs_dir_prefix'] . '/prj/p123_r456/toto.txt_1299584211');

        // Try to release a file named toto.txt in the same release
        $p = \Mockery::spy(\Project::class);
        $p->shouldReceive('getUnixName')->with(false)->once()->andReturns('prj');

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
        $this->assertFalse($ff->moveFileForge($f));
    }

    public function testMoveFileforgeFileExistWithSpaces()
    {
        // Create toto.txt in the release directory
        $this->createReleaseDir('prj', 'p123_r456');
        touch($GLOBALS['ftp_frs_dir_prefix'] . '/prj/p123_r456/toto zataz.txt');

        // Try to release a file named 'toto zataz.txt' in the same release
        $p = \Mockery::spy(\Project::class);
        $p->shouldReceive('getUnixName')->with(false)->once()->andReturns('prj');

        $r = new FRSRelease();
        $r->setReleaseID(456);
        $r->setPackageID(123);
        $r->setProject($p);

        $f = new FRSFile();
        $f->setFileName('toto zataz.txt');
        $f->setRelease($r);

        $ff = new FRSFileFactory();
        $ff->setFileForge([__DIR__ . '/../../../../src/utils/fileforge.pl']);
        $this->assertFalse($ff->moveFileForge($f));
    }

    public function testCreateFileIllegalName()
    {
        $ff = new FRSFileFactory();
        $f = new FRSFile();
        $f->setFileName('%toto#.txt');

        $this->expectException(FRSFileIllegalNameException::class);

        $ff->createFile($f);
    }

    public function testCreateFileSetReleaseTime()
    {
        $p = \Mockery::spy(\Project::class);
        $p->shouldReceive('getUnixName')->andReturns('prj');
        $r = new FRSRelease();
        $r->setReleaseID(456);
        $r->setPackageID(123);
        $r->setGroupID(1112);
        $r->setProject($p);

        $f = new FRSFile();
        $f->setRelease($r);
        $f->setGroup($p);
        $f->setFileName('file_sample');

        $dao = \Mockery::spy(\FRSFileDao::class);
        $dao->shouldReceive('searchFileByName')->andReturns(TestHelper::emptyDar());
        $ff = \Mockery::mock(\FRSFileFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $ff->setLogger(\Mockery::spy(\Psr\Log\LoggerInterface::class));
        $ff->shouldReceive('create')->andReturns(55);
        $ff->shouldReceive('moveFileForge')->andReturns(true);
        $ff->dao = $dao;

        $rf = \Mockery::spy(\FRSReleaseFactory::class);
        $rf->shouldReceive('getFRSReleaseFromDb')->andReturns($r);
        $ff->release_factory = $rf;

        $before = time();
        $ff->createFile($f);
        $after = time();

        $this->assertTrue($f->getPostDate() >= $before && $f->getPostDate() <= $after);
        $this->assertTrue($f->getReleaseTime() >= $before && $f->getReleaseTime() <= $after);
    }

    public function testCreateFileDoNotSetReleaseTimeIfAlreadySet()
    {
        $p = \Mockery::spy(\Project::class);
        $p->shouldReceive('getUnixName')->andReturns('prj');
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

        $dao = \Mockery::spy(\FRSFileDao::class);
        $dao->shouldReceive('searchFileByName')->andReturns(TestHelper::emptyDar());
        $ff = \Mockery::mock(\FRSFileFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $ff->setLogger(\Mockery::spy(\Psr\Log\LoggerInterface::class));
        $ff->shouldReceive('create')->andReturns(55);
        $ff->shouldReceive('moveFileForge')->andReturns(true);
        $ff->dao = $dao;

        $rf = \Mockery::spy(\FRSReleaseFactory::class);
        $rf->shouldReceive('getFRSReleaseFromDb')->andReturns($r);
        $ff->release_factory = $rf;

        $before = time();
        $ff->createFile($f);
        $after = time();

        $this->assertTrue($f->getPostDate() == 3125);
        $this->assertTrue($f->getReleaseTime() == 3125);
    }

    /**
     * We should not be able to create a file with the same name,
     * if an active one exists.
     */
    public function testCreateFileAlreadyExistingAndActive()
    {
        // Create toto.txt in the release directory
        $this->createReleaseDir('prj', 'p123_r456');
        touch($GLOBALS['ftp_frs_dir_prefix'] . '/prj/p123_r456/toto.txt_1299584197');

        $p = \Mockery::spy(\Project::class);
        $p->shouldReceive('getUnixName')->andReturns('prj');

        $r = new FRSRelease();
        $r->setReleaseID(456);
        $r->setPackageID(123);
        $r->setGroupID(111);
        $r->setProject($p);

        $f = new FRSFile();
        $f->setFileName('toto.txt_1299584219');
        $f->setRelease($r);

        $ff = \Mockery::mock(\FRSFileFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $ff->setLogger(\Mockery::spy(\Psr\Log\LoggerInterface::class));
        $ff->shouldReceive('getSrcDir')->andReturns($GLOBALS['ftp_incoming_dir']);
        $ff->shouldReceive('isFileBaseNameExists')->andReturns(true);

        $this->expectException(FRSFileExistsException::class);

        $ff->createFile($f);
    }

    /**
     * We should be able to create a file with the same name,
     * even if the active one has been deleted but not yet moved to staging area.
     */
    public function testCreateFileAlreadyExistingAndMarkedToBeDeletedNotYetMoved()
    {
        // Create toto.txt in the release directory
        touch($GLOBALS['ftp_incoming_dir'] . '/toto.txt');
        $this->createReleaseDir('prj', 'p123_r456');
        // toto.txt_1299584187 is the file having been deleted but not yet moved
        touch($GLOBALS['ftp_frs_dir_prefix'] . '/prj/p123_r456/toto.txt_1299584187');

        $p = \Mockery::spy(\Project::class);
        $p->shouldReceive('getUnixName')->andReturns('prj');

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
        $f->setFileLocation($GLOBALS['ftp_frs_dir_prefix'] . '/prj/p123_r456');

        $ff = \Mockery::mock(\FRSFileFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $ff->setLogger(\Mockery::spy(\Psr\Log\LoggerInterface::class));
        $ff->shouldReceive('getSrcDir')->andReturns($GLOBALS['ftp_incoming_dir']);
        $ff->shouldReceive('isFileBaseNameExists')->andReturns(false);
        $ff->shouldReceive('isSameFileMarkedToBeRestored')->andReturns(false);

        //moveFielForge will copy the new file to its destination
        $ff->shouldReceive('moveFileForge')->andReturns(true);
        touch($GLOBALS['ftp_frs_dir_prefix'] . '/prj/p123_r456/toto.txt_1299584210');

        $ff->shouldReceive('create')->andReturns(15225);
        $this->assertEquals($ff->createFile($f, ~FRSFileFactory::COMPUTE_MD5), $f);
    }

    /**
     * We should not be able to create a file with the same name,
     * even if the restored one has not yet been moved to the corresponding  pi_rj.
     */

    public function testCreateFileAlreadyMarkedToBeRestoredNotYetMoved()
    {
        // Create toto.txt in the release directory
        touch($GLOBALS['ftp_incoming_dir'] . '/toto.txt');
        $this->createReleaseDir('prj', 'p123_r456');

        $p = \Mockery::spy(\Project::class);
        $p->shouldReceive('getUnixName')->andReturns('prj');

        $r = new FRSRelease();
        $r->setReleaseID(456);
        $r->setPackageID(123);
        $r->setGroupID(111);
        $r->setProject($p);

        $f = new FRSFile();
        $f->setFileName('toto.txt');
        $f->setFilePath('toto.txt_1299584210');
        $f->setRelease($r);
        $f->setFileLocation($GLOBALS['ftp_frs_dir_prefix'] . '/prj/p123_r456');

        $ff = \Mockery::mock(\FRSFileFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $ff->setLogger(\Mockery::spy(\Psr\Log\LoggerInterface::class));
        $ff->shouldReceive('getSrcDir')->andReturns($GLOBALS['ftp_incoming_dir']);
        $ff->shouldReceive('isFileBaseNameExists')->andReturns(false);
        $ff->shouldReceive('isSameFileMarkedToBeRestored')->andReturns(true);

        $this->expectException(FRSFileToBeRestoredException::class);

        $ff->createFile($f, ~FRSFileFactory::COMPUTE_MD5);
    }


    public function testCreateFileNotYetIncoming()
    {
        $p = \Mockery::spy(\Project::class);

        $r = new FRSRelease();
        $r->setReleaseID(456);
        $r->setPackageID(123);
        $r->setProject($p);

        $f = new FRSFile();
        $f->setFileName('toto.txt');
        $f->setRelease($r);
        $ff = \Mockery::mock(\FRSFileFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $ff->shouldReceive('isFileBaseNameExists')->andReturn(false);
        $ff->shouldReceive('isSameFileMarkedToBeRestored')->andReturn(false);
        $ff->setLogger(\Mockery::spy(\Psr\Log\LoggerInterface::class));
        $ff->shouldReceive('getSrcDir')->andReturns($GLOBALS['ftp_incoming_dir']);
        $this->assertFalse(is_file($GLOBALS['ftp_incoming_dir'] . '/toto.txt'));

        $this->expectException(FRSFileInvalidNameException::class);

        $ff->createFile($f, ~FRSFileFactory::COMPUTE_MD5);
    }

    public function testCreateFileSkipCompareMD5Checksums()
    {
        $p = \Mockery::spy(\Project::class);
        $p->shouldReceive('getUnixName')->andReturns('prj');

        $r = new FRSRelease();
        $r->setReleaseID(456);
        $r->setPackageID(123);
        $r->setGroupID(111);
        $r->setProject($p);

        $f = new FRSFile();
        $f->setFileName('toto.txt');
        $f->setRelease($r);
        $f->setFileLocation($GLOBALS['ftp_incoming_dir']);
        $ff = \Mockery::mock(\FRSFileFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $ff->shouldReceive('isFileBaseNameExists')->andReturn(false);
        $ff->shouldReceive('isSameFileMarkedToBeRestored')->andReturn(false);
        $ff->setLogger(\Mockery::spy(\Psr\Log\LoggerInterface::class));
        $ff->shouldReceive('getSrcDir')->andReturns($GLOBALS['ftp_incoming_dir']);

        $path = $GLOBALS['ftp_incoming_dir'] . '/' . $f->getFileName();
        touch($GLOBALS['ftp_incoming_dir'] . '/toto.txt');
        $ff->shouldReceive('moveFileForge')->andReturns(true);
        $ff->shouldReceive('create')->andReturns(true);

        $ff->shouldReceive('compareMd5Checksums')->never();
        $ff->createFile($f, ~FRSFileFactory::COMPUTE_MD5);

        unlink($GLOBALS['ftp_incoming_dir'] . '/toto.txt');
    }

    public function testCreateFileCompareMD5Checksums()
    {
        $project = new Project(['group_id' => 111]);

        $r = \Mockery::mock(FRSRelease::class . '[getProject]');
        $r->shouldReceive('getProject')->andReturn($project);
        $r->setReleaseID(456);
        $r->setPackageID(123);
        $r->setGroupID(111);

        $ff = \Mockery::mock(\FRSFileFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $ff->shouldReceive('isFileBaseNameExists')->andReturn(false);
        $ff->shouldReceive('isSameFileMarkedToBeRestored')->andReturn(false);
        $ff->shouldReceive('compareMd5Checksums')->andReturn(false);
        $ff->setLogger(\Mockery::spy(\Psr\Log\LoggerInterface::class));
        $ff->shouldReceive('getSrcDir')->andReturns($GLOBALS['ftp_incoming_dir']);

        $f = new FRSFile();
        $f->setRelease($r);
        $f->setFileName('toto.txt');

        touch($GLOBALS['ftp_incoming_dir'] . '/toto.txt');
        $path = $GLOBALS['ftp_incoming_dir'] . '/' . $f->getFileName();
        $f->setReferenceMd5('d41d8cd98f00b204e9800998ecf8427e');


        try {
            $ff->createFile($f, FRSFileFactory::COMPUTE_MD5);
        } catch (Exception $e) {
            $this->assertInstanceOf(FRSFileMD5SumException::class, $e);
        }

        $this->assertNotNull($f->getComputedMd5());
        $this->assertTrue(FRSFileFactory::compareMd5Checksums($f->getComputedMd5(), $f->getReferenceMd5()));

        unlink($GLOBALS['ftp_incoming_dir'] . '/toto.txt');
    }

    public function testCreateFileMoveFileForgeKo()
    {
        $project = new Project(['group_id' => 111]);

        $r = \Mockery::mock(FRSRelease::class . '[getProject]');
        $r->shouldReceive('getProject')->andReturn($project);
        $r->setReleaseID(456);
        $r->setPackageID(123);
        $r->setGroupID(111);

        $ff = \Mockery::mock(\FRSFileFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $ff->shouldReceive('isFileBaseNameExists')->andReturn(false);
        $ff->shouldReceive('isSameFileMarkedToBeRestored')->andReturn(false);
        $ff->setLogger(\Mockery::spy(\Psr\Log\LoggerInterface::class));
        $ff->shouldReceive('getSrcDir')->andReturns($GLOBALS['ftp_incoming_dir']);

        $f = new FRSFile();
        $f->setRelease($r);

        $ff->shouldReceive('moveFileForge')->andReturns(false);

        $this->expectException(FRSFileForgeException::class);

        $ff->createFile($f, ~FRSFileFactory::COMPUTE_MD5);
    }

    public function testCreateFileDbEntryMovedFile()
    {
        // Create toto.txt in the release directory
        $this->createReleaseDir('prj', 'p123_r456');
        touch($GLOBALS['ftp_frs_dir_prefix'] . '/prj/p123_r456/toto.txt');
        touch($GLOBALS['ftp_incoming_dir'] . '/toto.txt');

        $p = \Mockery::spy(\Project::class);
        $p->shouldReceive('getUnixName')->andReturns('prj');

        $r = new FRSRelease();
        $r->setReleaseID(456);
        $r->setPackageID(123);
        $r->setProject($p);

        $f = new FRSFile();
        $f->setFileName('toto.txt');
        $f->setRelease($r);
        $f->setFileLocation($GLOBALS['ftp_frs_dir_prefix'] . '/prj/p123_r456');

        $ff = \Mockery::mock(\FRSFileFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $ff->setLogger(\Mockery::spy(\Psr\Log\LoggerInterface::class));
        $ff->shouldReceive('isFileBaseNameExists')->andReturn(false);
        $ff->shouldReceive('isSameFileMarkedToBeRestored')->andReturn(false);
        $ff->shouldReceive('getSrcDir')->andReturns($GLOBALS['ftp_incoming_dir']);
        $ff->shouldReceive('moveFileForge')->andReturns(true);
        $ff->shouldReceive('create')->andReturns(false);

        $this->expectException(FRSFileDbException::class);

        $ff->createFile($f, ~FRSFileFactory::COMPUTE_MD5);
    }

    public function testDeleteProjectFRSPackagesFail()
    {
        $packageFactory = \Mockery::spy(\FRSPackageFactory::class);

        $releaseFactory = \Mockery::spy(\FRSReleaseFactory::class);
        $releaseFactory->shouldReceive('_getFRSPackageFactory')->andReturns($packageFactory);

        $fileFactory = \Mockery::mock(\FRSFileFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $fileFactory->setLogger(\Mockery::spy(\Psr\Log\LoggerInterface::class));
        $fileFactory->shouldReceive('_getFRSReleaseFactory')->andReturns($releaseFactory);

        $fileFactory->shouldReceive('moveDeletedFilesToStagingArea')->once()->andReturns(true);
        $releaseFactory->shouldReceive('deleteProjectReleases')->once()->andReturns(true);
        $packageFactory->shouldReceive('deleteProjectPackages')->once()->andReturns(false);
        $backend = \Mockery::spy(\BackendSystem::class);
        $this->assertFalse($fileFactory->deleteProjectFRS(1, $backend));
    }

    public function testDeleteProjectFRSReleasesFail()
    {
        $packageFactory = \Mockery::spy(\FRSPackageFactory::class);

        $releaseFactory = \Mockery::spy(\FRSReleaseFactory::class);
        $releaseFactory->shouldReceive('_getFRSPackageFactory')->andReturns($packageFactory);

        $fileFactory = \Mockery::mock(\FRSFileFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $fileFactory->setLogger(\Mockery::spy(\Psr\Log\LoggerInterface::class));
        $fileFactory->shouldReceive('_getFRSReleaseFactory')->andReturns($releaseFactory);

        $fileFactory->shouldReceive('moveDeletedFilesToStagingArea')->once()->andReturns(true);
        $releaseFactory->shouldReceive('deleteProjectReleases')->once()->andReturns(false);
        $packageFactory->shouldReceive('deleteProjectPackages')->once()->andReturns(true);
        $backend = \Mockery::spy(\BackendSystem::class);
        $this->assertFalse($fileFactory->deleteProjectFRS(1, $backend));
    }

    public function testDeleteProjectFRSMoveFail()
    {
        $packageFactory = \Mockery::spy(\FRSPackageFactory::class);

        $releaseFactory = \Mockery::spy(\FRSReleaseFactory::class);
        $releaseFactory->shouldReceive('_getFRSPackageFactory')->andReturns($packageFactory);

        $fileFactory = \Mockery::mock(\FRSFileFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $fileFactory->setLogger(\Mockery::spy(\Psr\Log\LoggerInterface::class));
        $fileFactory->shouldReceive('_getFRSReleaseFactory')->andReturns($releaseFactory);

        $fileFactory->shouldReceive('moveDeletedFilesToStagingArea')->once()->andReturns(false);
        $releaseFactory->shouldReceive('deleteProjectReleases')->once()->andReturns(true);
        $packageFactory->shouldReceive('deleteProjectPackages')->once()->andReturns(true);
        $backend = \Mockery::spy(\BackendSystem::class);
        $this->assertFalse($fileFactory->deleteProjectFRS(1, $backend));
    }

    public function testDeleteProjectFRSSuccess()
    {
        $packageFactory = \Mockery::spy(\FRSPackageFactory::class);

        $releaseFactory = \Mockery::spy(\FRSReleaseFactory::class);
        $releaseFactory->shouldReceive('_getFRSPackageFactory')->andReturns($packageFactory);

        $fileFactory = \Mockery::mock(\FRSFileFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $fileFactory->setLogger(\Mockery::spy(\Psr\Log\LoggerInterface::class));
        $fileFactory->shouldReceive('_getFRSReleaseFactory')->andReturns($releaseFactory);

        $fileFactory->shouldReceive('moveDeletedFilesToStagingArea')->once()->andReturns(true);
        $releaseFactory->shouldReceive('deleteProjectReleases')->once()->andReturns(true);
        $packageFactory->shouldReceive('deleteProjectPackages')->once()->andReturns(true);
        $backend = \Mockery::spy(\BackendSystem::class);
        $this->assertTrue($fileFactory->deleteProjectFRS(1, $backend));
    }
}
