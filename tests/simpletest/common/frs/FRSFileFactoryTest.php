<?php

require_once('common/frs/FRSFileFactory.class.php');
require_once('common/frs/FRSReleaseFactory.class.php');
require_once('common/backend/BackendSystem.class.php');
require_once 'common/valid/Rule.class.php';
require_once('common/language/BaseLanguage.class.php');

Mock::generate('PFUser');
Mock::generate('UserManager');
Mock::generate('EventManager');
require_once('common/project/Project.class.php');
Mock::generate('Project');
Mock::generate('ProjectManager');
Mock::generate('DataAccessResult');
Mock::generate('FRSReleaseFactory');
Mock::generate('FRSPackageFactory');
Mock::generate('FRSRelease');
Mock::generate('FRSFileDao');
Mock::generate('FRSFile');
Mock::generate('BackendSystem');
Mock::generate('BaseLanguage');
Mock::generatePartial('FRSFileFactory', 'FRSFileFactoryTestVersion', array('_getFRSReleaseFactory', '_getProjectManager', 'moveDeletedFilesToStagingArea'));
Mock::generatePartial('FRSFileFactory', 'FRSFileFactoryTestPurgeFiles', array('_getFRSFileDao', 'purgeFile', 'archiveBeforePurge'));
Mock::generatePartial('FRSFileFactory', 'FRSFileFactoryTestPurgeOneFile', array('_getFRSFileDao', 'archiveBeforePurge'));
Mock::generatePartial('FRSFileFactory', 'FRSFileFactoryTestMoveToStaging', array('_getFRSFileDao', 'moveDeletedFileToStagingArea'));
Mock::generatePartial('FRSFileFactory', 'FRSFileFactoryTestPurgeDeletedFiles', array('purgeFiles', 'moveDeletedFilesToStagingArea', 'cleanStaging', 'restoreDeletedFiles'));
Mock::generatePartial('FRSFileFactory', 'FRSFileFactoryTestRestore', array('_getFRSReleaseFactory', '_getFRSFileDao', '_getUserManager', '_getEventManager'));
Mock::generatePartial('FRSFileFactory', 'FRSFileFactoryTestRestoreFiles', array('_getFRSFileDao', 'restoreFile'));
Mock::generatePartial('FRSFileFactory', 'FRSFileFactoryTestCreateFiles', array('create', 'moveFileForge','isFileBaseNameExists', 'isSameFileMarkedToBeRestored', 'compareMd5Checksums'));

class FRSFileFactoryTest extends UnitTestCase {

    function setUp() {
        $GLOBALS['Language']           = new MockBaseLanguage($this);
        $GLOBALS['ftp_frs_dir_prefix'] = dirname(__FILE__).'/_fixtures';
        $GLOBALS['ftp_incoming_dir']   = dirname(__FILE__).'/_fixtures';


        $conf  = '<?php'.PHP_EOL;
        $conf .= '$ftp_frs_dir_prefix = "'.$GLOBALS['ftp_frs_dir_prefix'].'"'. PHP_EOL;
        $conf .= '$ftp_incoming_dir = "'.$GLOBALS['ftp_incoming_dir'].'"'. PHP_EOL;
        $conf .= '?>'.PHP_EOL;

        $this->fakeconf = $GLOBALS['ftp_frs_dir_prefix'].'/local.conf';
        file_put_contents($this->fakeconf, $conf);

        $this->beforeTest['CODENDI_LOCAL_INC'] = getenv('CODENDI_LOCAL_INC');
        putenv('CODENDI_LOCAL_INC='.$this->fakeconf);
    }

    function tearDown() {
        unset($GLOBALS['Language']);
        unset($GLOBALS['ftp_frs_dir_prefix']);
        unset($GLOBALS['ftp_incoming_dir']);
        unlink($this->fakeconf);
        putenv('CODENDI_LOCAL_INC='.$this->beforeTest['CODENDI_LOCAL_INC']);
    }

    function testgetUploadSubDirectory() {
        $package_id = rand(1, 1000);
        $release_id = rand(1, 1000);

        $release = new FRSRelease();
        $release->setPackageID($package_id);
        $release->setReleaseID($release_id);

        $file_fact = new FRSFileFactory();

        $sub_dir = $file_fact->getUploadSubDirectory($release);
        $this->assertEqual($sub_dir, 'p'.$package_id.'_r'.$release_id);
    }

    function testPurgeDeletedFiles() {
        $ff = new FRSFileFactoryTestPurgeDeletedFiles($this);
        $ff->setReturnValue('moveDeletedFilesToStagingArea', true);
        $ff->setReturnValue('purgeFiles', true);
        $ff->setReturnValue('cleanStaging', true);
        $ff->setReturnValue('restoreDeletedFiles', true);
        $backend = new MockBackendSystem($this);
        $ff->expectOnce('moveDeletedFilesToStagingArea');
        $ff->expectOnce('purgeFiles', array(1287504083, $backend));
        $ff->expectOnce('cleanStaging');
        $ff->expectOnce('restoreDeletedFiles', array($backend));

        $this->assertTrue($ff->moveFiles(1287504083, $backend));
    }

    function testMoveFilesMoveStagingError() {
        $ff = new FRSFileFactoryTestPurgeDeletedFiles($this);
        $ff->setReturnValue('moveDeletedFilesToStagingArea', false);
        $ff->setReturnValue('purgeFiles', true);
        $ff->setReturnValue('cleanStaging', true);
        $ff->setReturnValue('restoreDeletedFiles', true);
        $ff->expectOnce('moveDeletedFilesToStagingArea');
        $backend = new MockBackendSystem($this);
        $ff->expectOnce('purgeFiles', array(1287504083, $backend));
        $ff->expectOnce('cleanStaging');
        $ff->expectOnce('restoreDeletedFiles', array($backend));

        $this->assertFalse($ff->moveFiles(1287504083, $backend));
    }

    function testMoveFilesPurgeError() {
        $ff = new FRSFileFactoryTestPurgeDeletedFiles($this);
        $ff->setReturnValue('moveDeletedFilesToStagingArea', true);
        $ff->setReturnValue('purgeFiles', false);
        $ff->setReturnValue('cleanStaging', true);
        $ff->setReturnValue('restoreDeletedFiles', true);
        $backend = new MockBackendSystem($this);
        $ff->expectOnce('moveDeletedFilesToStagingArea');
        $ff->expectOnce('purgeFiles', array(1287504083, $backend));
        $ff->expectOnce('cleanStaging');
        $ff->expectOnce('restoreDeletedFiles', array($backend));

        $this->assertFalse($ff->moveFiles(1287504083, $backend));
    }

    function testMoveFilesCleanStagingError() {
        $ff = new FRSFileFactoryTestPurgeDeletedFiles($this);
        $ff->setReturnValue('moveDeletedFilesToStagingArea', true);
        $ff->setReturnValue('purgeFiles', true);
        $ff->setReturnValue('cleanStaging', false);
        $ff->setReturnValue('restoreDeletedFiles', true);
        $backend = new MockBackendSystem($this);
        $ff->expectOnce('moveDeletedFilesToStagingArea');
        $ff->expectOnce('purgeFiles', array(1287504083, $backend));
        $ff->expectOnce('cleanStaging');
        $ff->expectOnce('restoreDeletedFiles', array($backend));

        $this->assertFalse($ff->moveFiles(1287504083, $backend));
    }

    function testMoveFilesRestoreDeletedError() {
        $ff = new FRSFileFactoryTestPurgeDeletedFiles($this);
        $ff->setReturnValue('moveDeletedFilesToStagingArea', true);
        $ff->setReturnValue('purgeFiles', true);
        $ff->setReturnValue('cleanStaging', true);
        $ff->setReturnValue('restoreDeletedFiles', false);
        $backend = new MockBackendSystem($this);
        $ff->expectOnce('moveDeletedFilesToStagingArea');
        $ff->expectOnce('purgeFiles', array(1287504083, $backend));
        $ff->expectOnce('cleanStaging');
        $ff->expectOnce('restoreDeletedFiles', array($backend));

        $this->assertFalse($ff->moveFiles(1287504083, $backend));
    }

    function testMoveFilesCatchesExceptionAndLogThem() {
        $ff = new FRSFileFactoryTestPurgeDeletedFiles($this);
        $ff->throwOn('purgeFiles', new RuntimeException("Error while doing things"));
        $ff->setReturnValue('moveDeletedFilesToStagingArea', true);
        $ff->setReturnValue('cleanStaging', true);
        $ff->setReturnValue('restoreDeletedFiles', true);

        $backend = new MockBackendSystem();
        $backend->expectOnce('log', array("Error while doing things", Backend::LOG_ERROR));

        $this->assertFalse($ff->moveFiles(1287504083, $backend));
    }

    function testMoveDeletedFilesToStagingAreaWithNoFiles() {
        $ff = new FRSFileFactoryTestMoveToStaging($this);

        $dar = new MockDataAccessResult($this);
        $dar->setReturnValue('isError', false);
        $dar->setReturnValue('getRow', false);
        $dar->setReturnValue('valid', false);
        $dar->setReturnValue('rowCount', 0);

        $dao = new MockFRSFileDao($this);
        $dao->expectOnce('searchStagingCandidates');
        $dao->setReturnValue('searchStagingCandidates', $dar);
        $ff->setReturnValue('_getFRSFileDao', $dao);
        $backend = new MockBackendSystem($this);

        $ff->expectNever('moveDeletedFileToStagingArea');

        $this->assertTrue($ff->moveDeletedFilesToStagingArea($backend));
    }

    function testMoveDeletedFileToStagingArea() {
        $ff = new FRSFileFactoryTestPurgeOneFile($this);

        // Create temp file in a fake release
        mkdir(dirname(__FILE__).'/_fixtures/prj/p1_r1');
        $filepath = dirname(__FILE__).'/_fixtures/prj/p1_r1/foobar.xls';
        touch($filepath);
        $this->assertTrue(is_file($filepath));
        $file = new MockFRSFile($this);
        $file->setReturnValue('getFileID', 12);
        $file->setReturnValue('getFileLocation', $filepath);

        $dao = new MockFRSFileDao($this);
        $dao->expectOnce('setFileInDeletedList', array(12));
        $dao->setReturnValue('setFileInDeletedList', true);
        $ff->setReturnValue('_getFRSFileDao', $dao);
        $backend = new MockBackendSystem($this);

        $this->assertTrue($ff->moveDeletedFileToStagingArea($file, $backend));

        $this->assertTrue(is_file($GLOBALS['ftp_frs_dir_prefix'].'/DELETED/prj/p1_r1/foobar.xls.12'));
        $this->assertFalse(is_file(dirname(__FILE__).'/_fixtures/prj/p1_r1/foobar.xls'));
        $this->assertFalse(is_dir(dirname(__FILE__).'/_fixtures/prj/p1_r1'));

        // Clean-up
        unlink(dirname(__FILE__).'/_fixtures/DELETED/prj/p1_r1/foobar.xls.12');
        rmdir(dirname(__FILE__).'/_fixtures/DELETED/prj/p1_r1');
        rmdir(dirname(__FILE__).'/_fixtures/DELETED/prj');
    }

    /**
     * If for one reason the file to delete appears to be already deleted, mark it as
     * deleted and purged at the very same date
     *
     */
    function testMoveDeletedFileToStagingAreaButFileDoesntExist() {
        $ff = new FRSFileFactoryTestPurgeOneFile($this);

        // Create temp file in a fake release
        mkdir(dirname(__FILE__).'/_fixtures/prj/p1_r1');
        $filepath = dirname(__FILE__).'/_fixtures/prj/p1_r1/foobar.xls';
        $this->assertFalse(is_file($filepath), "The file shouldn't exist, this is the base of the test!");
        $file = new MockFRSFile($this);
        $file->setReturnValue('getFileID', 12);
        $file->setReturnValue('getFileLocation', $filepath);

        $dao = new MockFRSFileDao($this);
        $dao->expectOnce('setFileInDeletedList', array(12)); // Mark as deleted
        $dao->setReturnValue('setFileInDeletedList', true);
        $dao->expectOnce('setPurgeDate', array(12, $_SERVER['REQUEST_TIME'])); // Mark as purged
        $dao->setReturnValue('setPurgeDate', true);
        $ff->setReturnValue('_getFRSFileDao', $dao);
        $backend = new MockBackendSystem($this);
        $backend->expectAt('0','log', array('*', 'warn'));
        $backend->expectAt('1','log', array('*', 'error'));

        $this->assertFalse($ff->moveDeletedFileToStagingArea($file, $backend));

        $this->assertFalse(is_file($GLOBALS['ftp_frs_dir_prefix'].'/DELETED/prj/p1_r1/foobar.xls.12'));
        $this->assertFalse(is_file(dirname(__FILE__).'/_fixtures/prj/p1_r1/foobar.xls'));
        $this->assertFalse(is_dir(dirname(__FILE__).'/_fixtures/prj/p1_r1'));

        // Clean-up
        //unlink(dirname(__FILE__).'/_fixtures/DELETED/prj/p1_r1/foobar.xls.12');
        //rmdir(dirname(__FILE__).'/_fixtures/DELETED/prj/p1_r1');
        //rmdir(dirname(__FILE__).'/_fixtures/DELETED/prj');
    }

    function testMoveDeletedFileToStagingAreaReleaseNotEmpty() {
        $ff = new FRSFileFactoryTestPurgeOneFile($this);

        // Create temp file in a fake release
        mkdir(dirname(__FILE__).'/_fixtures/prj/p1_r1');
        $filepath = dirname(__FILE__).'/_fixtures/prj/p1_r1/foobar.xls';
        touch($filepath);
        $this->assertTrue(is_file($filepath));
        $file = new MockFRSFile($this);
        $file->setReturnValue('getFileID', 12);
        $file->setReturnValue('getFileLocation', $filepath);
        // Second file, not deleted
        touch(dirname(__FILE__).'/_fixtures/prj/p1_r1/barfoo.doc');

        $dao = new MockFRSFileDao($this);
        $dao->expectOnce('setFileInDeletedList', array(12));
        $dao->setReturnValue('setFileInDeletedList', true);
        $ff->setReturnValue('_getFRSFileDao', $dao);
        $backend = new MockBackendSystem($this);

        $this->assertTrue($ff->moveDeletedFileToStagingArea($file, $backend));

        $this->assertTrue(is_file($GLOBALS['ftp_frs_dir_prefix'].'/DELETED/prj/p1_r1/foobar.xls.12'));
        $this->assertFalse(is_file(dirname(__FILE__).'/_fixtures/prj/p1_r1/foobar.xls'));
        $this->assertTrue(is_file(dirname(__FILE__).'/_fixtures/prj/p1_r1/barfoo.doc'), 'The other file in the release must not be deleted');

        // Clean-up
        unlink(dirname(__FILE__).'/_fixtures/prj/p1_r1/barfoo.doc');
        rmdir(dirname(__FILE__).'/_fixtures/prj/p1_r1');
        unlink(dirname(__FILE__).'/_fixtures/DELETED/prj/p1_r1/foobar.xls.12');
        rmdir(dirname(__FILE__).'/_fixtures/DELETED/prj/p1_r1');
        rmdir(dirname(__FILE__).'/_fixtures/DELETED/prj');
    }

    function testMoveDeletedFilesToStagingAreaFail() {
        $ff = new FRSFileFactoryTestMoveToStaging($this);

        $dar = new MockDataAccessResult($this);
        $dar->setReturnValue('isError', false);
        $dar->setReturnValue('current', array('file_id' => 12));
        $dar->setReturnValueAt(0, 'valid', true);
        $dar->setReturnValueAt(1, 'valid', false);
        $dar->setReturnValue('rowCount', 1);

        $dao = new MockFRSFileDao($this);
        $dao->expectOnce('searchStagingCandidates');
        $dao->setReturnValue('searchStagingCandidates', $dar);
        $ff->setReturnValue('_getFRSFileDao', $dao);

        $refFile = new FRSFile(array('file_id' => 12));
        $backend = new MockBackendSystem($this);
        $ff->setReturnValue('moveDeletedFileToStagingArea', false);
        $ff->expectOnce('moveDeletedFileToStagingArea', array($refFile, $backend));

        $this->assertFalse($ff->moveDeletedFilesToStagingArea($backend));
    }

    function testMoveDeletedFilesToStagingAreaWithOneFile() {
        $ff = new FRSFileFactoryTestMoveToStaging($this);

        $dar = new MockDataAccessResult($this);
        $dar->setReturnValue('isError', false);
        $dar->setReturnValue('current', array('file_id' => 12));
        $dar->setReturnValueAt(0, 'valid', true);
        $dar->setReturnValueAt(1, 'valid', false);
        $dar->setReturnValue('rowCount', 1);

        $dao = new MockFRSFileDao($this);
        $dao->expectOnce('searchStagingCandidates');
        $dao->setReturnValue('searchStagingCandidates', $dar);
        $ff->setReturnValue('_getFRSFileDao', $dao);

        $refFile = new FRSFile(array('file_id' => 12));
        $backend = new MockBackendSystem($this);
        $ff->setReturnValue('moveDeletedFileToStagingArea', true);
        $ff->expectOnce('moveDeletedFileToStagingArea', array($refFile, $backend));

        $this->assertTrue($ff->moveDeletedFilesToStagingArea($backend));
    }

    function testPurgeFilesWithNoFiles() {
        $dar = new MockDataAccessResult($this);
        $dar->setReturnValue('isError', false);
        $dar->setReturnValue('getRow', false);
        $dar->setReturnValue('valid', false);
        $dar->setReturnValue('rowCount', 0);

        $dao = new MockFRSFileDao($this);
        $dao->expectOnce('searchFilesToPurge', array(1287504083));
        $dao->setReturnValue('searchFilesToPurge', $dar);

        $ff = new FRSFileFactoryTestPurgeFiles($this);
        $ff->setReturnValue('_getFRSFileDao', $dao);

        $ff->expectNever('purgeFile');
        $backend = new MockBackendSystem($this);
        $this->assertTrue($ff->purgeFiles(1287504083, $backend));
    }

    function testPurgeFilesWithOneFile() {
        $refFile = new FRSFile(array('file_id' => 12));

        $dar = new MockDataAccessResult($this);
        $dar->setReturnValue('isError', false);
        $dar->setReturnValue('current', array('file_id' => 12));
        $dar->setReturnValueAt(0, 'valid', true);
        $dar->setReturnValueAt(1, 'valid', false);
        $dar->setReturnValue('rowCount', 1);

        $dao = new MockFRSFileDao($this);
        $dao->expectOnce('searchFilesToPurge', array(1287504083));
        $dao->setReturnValue('searchFilesToPurge', $dar);

        $ff = new FRSFileFactoryTestPurgeFiles($this);
        $ff->setReturnValue('_getFRSFileDao', $dao);

        $backend = new MockBackendSystem($this);
        $ff->expectOnce('purgeFile', array($refFile, $backend));
        $ff->setReturnValue('purgeFile', true);
        $this->assertTrue($ff->purgeFiles(1287504083, $backend));
    }

    function testPurgeFileSucceed() {
        $ff = new FRSFileFactoryTestPurgeOneFile($this);

        // Create temp file
        $filepath = dirname(__FILE__).'/_fixtures/DELETED/prj/p1_r1/foobar.xls.12';
        mkdir(dirname($filepath), 0750, true);
        touch($filepath);

        $this->assertTrue(is_file($filepath));
        $file = new MockFRSFile($this);
        $file->setReturnValue('getFileID', 12);
        $file->setReturnValue('getFileName', 'p1_r1/foobar.xls');
        $file->setReturnValue('getFileLocation', $GLOBALS['ftp_frs_dir_prefix'].'/prj/p1_r1/foobar.xls');

        $dao = new MockFRSFileDao($this);
        $dao->expectOnce('setPurgeDate', array(12, '*'));
        $dao->setReturnValue('setPurgeDate', true);
        $ff->setReturnValue('_getFRSFileDao', $dao);

        $backend = new MockBackendSystem();
        $backend->expectNever('log', array('File p1_r1/foobar.xls(12) not purged, Set purge date in DB fail', 'error'));
        $this->assertTrue($ff->purgeFile($file, $backend));
        $ff->expectOnce('archiveBeforePurge', array($file, $backend));

        $this->assertFalse(is_file($filepath), "File should be deleted");

        // Cleanup
        rmdir($GLOBALS['ftp_frs_dir_prefix'].'/DELETED/prj/p1_r1');
        rmdir($GLOBALS['ftp_frs_dir_prefix'].'/DELETED/prj');
    }

    function testPurgeFileDBUpdateFails() {
        $ff = new FRSFileFactoryTestPurgeOneFile($this);

        // Create temp file
        $filepath = dirname(__FILE__).'/_fixtures/DELETED/prj/p1_r1/foobar.xls.12';
        mkdir(dirname($filepath), 0750, true);
        touch($filepath);
        $this->assertTrue(is_file($filepath));
        $file = new MockFRSFile($this);
        $file->setReturnValue('getFileID', 12);
        $file->setReturnValue('getFileName', 'p1_r1/foobar.xls');
        $file->setReturnValue('getFileLocation', $GLOBALS['ftp_frs_dir_prefix'].'/prj/p1_r1/foobar.xls');

        $dao = new MockFRSFileDao($this);
        $dao->expectOnce('setPurgeDate', array(12, '*'));
        $dao->setReturnValue('setPurgeDate', false);
        $ff->setReturnValue('_getFRSFileDao', $dao);

        $backend = new MockBackendSystem();
        $backend->expectOnce('log', array('File '.$filepath.' not purged, Set purge date in DB fail', 'error'));
        $this->assertFalse($ff->purgeFile($file, $backend));
        $ff->expectOnce('archiveBeforePurge', array($file, $backend));

        $this->assertFalse(is_file($filepath), "File should be deleted");

        // Cleanup
        rmdir($GLOBALS['ftp_frs_dir_prefix'].'/DELETED/prj/p1_r1');
        rmdir($GLOBALS['ftp_frs_dir_prefix'].'/DELETED/prj');
    }

    function testPurgeFileWithFileNotFoundInFS() {
        $ff = new FRSFileFactoryTestPurgeOneFile($this);

        $filepath = dirname(__FILE__).'/_fixtures/DELETED/prj/p1_r1/foobar.xls.12';

        $this->assertFalse(is_file($filepath));
        $file = new MockFRSFile($this);
        $file->setReturnValue('getFileID', 12);
        $file->setReturnValue('getFileName', 'p1_r1/foobar.xls');
        $file->setReturnValue('getFileLocation', $GLOBALS['ftp_frs_dir_prefix'].'/prj/p1_r1/foobar.xls');

        $dao = new MockFRSFileDao($this);
        $dao->expectOnce('setPurgeDate', array(12, '*'));
        $dao->setReturnValue('setPurgeDate', true);
        $ff->setReturnValue('_getFRSFileDao', $dao);

        $backend = new MockBackendSystem();
        $backend->expectOnce('log', array('File '.$filepath.' not found on file system, automatically marked as purged', 'warn'));
        $this->assertTrue($ff->purgeFile($file, $backend));
        $ff->expectNever('archiveBeforePurge', array($file, $backend));
    }

    function testRemoveStagingEmptyDirectories() {
        $ff = new FRSFileFactory();
        $backend = new MockBackendSystem($this);

        mkdir($GLOBALS['ftp_frs_dir_prefix'].'/DELETED/prj/p1_r1', 0750, true);
        mkdir($GLOBALS['ftp_frs_dir_prefix'].'/DELETED/prj2/p2_r5', 0750, true);
        touch($GLOBALS['ftp_frs_dir_prefix'].'/DELETED/prj2/p2_r5/file.txt.7');
        mkdir($GLOBALS['ftp_frs_dir_prefix'].'/DELETED/prj3/p7_r8', 0750, true);
        mkdir($GLOBALS['ftp_frs_dir_prefix'].'/DELETED/prj3/p9_r10', 0750, true);
        touch($GLOBALS['ftp_frs_dir_prefix'].'/DELETED/prj3/p9_r10/foo.txt.12');

        $this->assertTrue($ff->cleanStaging($backend));
        $this->assertFalse(is_dir($GLOBALS['ftp_frs_dir_prefix'].'/DELETED/prj'));
        $this->assertTrue(is_file($GLOBALS['ftp_frs_dir_prefix'].'/DELETED/prj2/p2_r5/file.txt.7'));
        $this->assertFalse(is_dir($GLOBALS['ftp_frs_dir_prefix'].'/DELETED/prj3/p7_r8'));
        $this->assertTrue(is_file($GLOBALS['ftp_frs_dir_prefix'].'/DELETED/prj3/p9_r10/foo.txt.12'));

        // Cleanup
        unlink($GLOBALS['ftp_frs_dir_prefix'].'/DELETED/prj2/p2_r5/file.txt.7');
        rmdir($GLOBALS['ftp_frs_dir_prefix'].'/DELETED/prj2/p2_r5');
        rmdir($GLOBALS['ftp_frs_dir_prefix'].'/DELETED/prj2');
        unlink($GLOBALS['ftp_frs_dir_prefix'].'/DELETED/prj3/p9_r10/foo.txt.12');
        rmdir($GLOBALS['ftp_frs_dir_prefix'].'/DELETED/prj3/p9_r10');
        rmdir($GLOBALS['ftp_frs_dir_prefix'].'/DELETED/prj3');
    }

    function testRestoreFileSucceed() {
        $fileFactory = new FRSFileFactoryTestRestore();

        // Create temp file
        $filepath = dirname(__FILE__).'/_fixtures/DELETED/prj/p1_r1/toto.xls.12';
        mkdir(dirname($filepath), 0750, true);
        touch($filepath);
        $this->assertTrue(is_dir(dirname($filepath)));

        $file = new MockFRSFile($this);
        $file->setReturnValue('getFileID', 12);
        $file->setReturnValue('getFileName', 'p1_r1/toto.xls');
        $file->setReturnValue('getFileLocation', $GLOBALS['ftp_frs_dir_prefix'].'/prj/p1_r1/toto.xls');
        $project = new MockProject($this);
        $file->setReturnValue('getGroup', $project);
        mkdir($GLOBALS['ftp_frs_dir_prefix'].'/prj/p1_r1/', 0750, true);
        $this->assertTrue(is_dir($GLOBALS['ftp_frs_dir_prefix'].'/prj/p1_r1/'));

        $dao = new MockFRSFileDao($this);
        $dao->expectOnce('restoreFile');
        $dao->setReturnValue('restoreFile', true);
        $fileFactory->setReturnValue('_getFRSFileDao', $dao);
        $backend = new MockBackendSystem($this);

        $user = mock('PFUser');
        $um = new MockUserManager($this);
        $um->setReturnValue('getCurrentUser', $user);
        $fileFactory->setReturnValue('_getUserManager', $um);
        $em = new MockEventManager($this);
        $fileFactory->setReturnValue('_getEventManager', $em);
        $release = new MockFRSRelease($this);
        $release->setReturnValue('isDeleted', false);
        $releaseFactory = new MockFRSReleaseFactory($this);
        $releaseFactory->setReturnValue('getFRSReleaseFromDb', $release);
        $fileFactory->setReturnValue('_getFRSReleaseFactory', $releaseFactory);

        $this->assertTrue($fileFactory->restoreFile($file, $backend));
         
        // Cleanup

        rmdir($GLOBALS['ftp_frs_dir_prefix'].'/DELETED/prj/p1_r1');
        rmdir($GLOBALS['ftp_frs_dir_prefix'].'/DELETED/prj');
        unlink($GLOBALS['ftp_frs_dir_prefix'].'/prj/p1_r1/toto.xls');
        rmdir($GLOBALS['ftp_frs_dir_prefix'].'/prj/p1_r1');

    }

    function testRestoreFileNotExists() {
        $fileFactory = new FRSFileFactoryTestRestore();

        // Create temp file
        $filepath = dirname(__FILE__).'/_fixtures/DELETED/prj/p1_r1/toto.xls.5';
        mkdir(dirname($filepath), 0750, true);
        $this->assertFalse(file_exists($filepath));

        $file = new MockFRSFile($this);
        $file->setReturnValue('getFileID', 5);
        $file->setReturnValue('getFileName', 'p1_r1/toto.xls');
        $file->setReturnValue('getFileLocation', $GLOBALS['ftp_frs_dir_prefix'].'/prj/p1_r1/toto.xls');
        $this->assertTrue(is_dir(dirname($GLOBALS['ftp_frs_dir_prefix'].'/prj/p1_r1/')));

        $dao = new MockFRSFileDao($this);
        $dao->expectNever('restoreFile');
        $fileFactory->setReturnValue('_getFRSFileDao', $dao);
        $backend = new MockBackendSystem($this);
        $backend->setReturnValue('chgrp', true);

        $release = new MockFRSRelease($this);
        $release->setReturnValue('isDeleted', false);
        $releaseFactory = new MockFRSReleaseFactory($this);
        $releaseFactory->setReturnValue('getFRSReleaseFromDb', $release);
        $fileFactory->setReturnValue('_getFRSReleaseFactory', $releaseFactory);

        $this->assertFalse($fileFactory->restoreFile($file, $backend));
         
        // Cleanup

        rmdir($GLOBALS['ftp_frs_dir_prefix'].'/DELETED/prj/p1_r1');
        rmdir($GLOBALS['ftp_frs_dir_prefix'].'/DELETED/prj');
    }

    function testRestoreFileLocationNotExists(){
        $fileFactory = new FRSFileFactoryTestRestore();

        // Create temp file
        $filepath = dirname(__FILE__).'/_fixtures/DELETED/prj/p2_r1/toto.xls.12';
        mkdir(dirname($filepath), 0750, true);
        touch($filepath);
        $this->assertTrue(is_dir(dirname($filepath)));
        $backend = new MockBackendSystem($this);
        $file = new MockFRSFile($this);
        $file->setReturnValue('getFileID', 12);
        $file->setReturnValue('getFileName', 'p2_r1/toto.xls');
        $file->setReturnValue('getFileLocation', $GLOBALS['ftp_frs_dir_prefix'].'/prj/p2_r1/toto.xls');
        $project = new MockProject($this);
        $file->setReturnValue('getGroup', $project);
        $this->assertTrue(is_dir(dirname($GLOBALS['ftp_frs_dir_prefix'].'/prj/p2_r1/')));
        $backend->setReturnValue('chgrp', true);

        $dao = new MockFRSFileDao($this);
        $dao->expectOnce('restoreFile');
        $dao->setReturnValue('restoreFile', true);
        $fileFactory->setReturnValue('_getFRSFileDao', $dao);

        $user = mock('PFUser');
        $um = new MockUserManager($this);
        $um->setReturnValue('getCurrentUser', $user);
        $fileFactory->setReturnValue('_getUserManager', $um);
        $em = new MockEventManager($this);
        $fileFactory->setReturnValue('_getEventManager', $em);
        $release = new MockFRSRelease($this);
        $release->setReturnValue('isDeleted', false);
        $releaseFactory = new MockFRSReleaseFactory($this);
        $releaseFactory->setReturnValue('getFRSReleaseFromDb', $release);
        $fileFactory->setReturnValue('_getFRSReleaseFactory', $releaseFactory);

        $this->assertTrue($fileFactory->restoreFile($file, $backend));

        // Cleanup
        rmdir($GLOBALS['ftp_frs_dir_prefix'].'/DELETED/prj/p2_r1');
        rmdir($GLOBALS['ftp_frs_dir_prefix'].'/DELETED/prj');
        unlink($GLOBALS['ftp_frs_dir_prefix'].'/prj/p2_r1/toto.xls');
        rmdir($GLOBALS['ftp_frs_dir_prefix'].'/prj/p2_r1');
    }

    function testRestoreFileDBUpdateFails(){
        $fileFactory = new FRSFileFactoryTestRestore();

        // Create temp file
        $filepath = dirname(__FILE__).'/_fixtures/DELETED/prj/p3_r1/toto.xls.12';
        mkdir(dirname($filepath), 0750, true);
        touch($filepath);
        $this->assertTrue(is_dir(dirname($filepath)));

        $file = new MockFRSFile($this);
        $file->setReturnValue('getFileID', 12);
        $file->setReturnValue('getFileName', 'p3_r1/toto.xls');
        $file->setReturnValue('getFileLocation', $GLOBALS['ftp_frs_dir_prefix'].'/prj/p3_r1/toto.xls');
        $project = new MockProject($this);
        $file->setReturnValue('getGroup', $project);
        $this->assertTrue(is_dir(dirname($GLOBALS['ftp_frs_dir_prefix'].'/prj/p3_r1/')));

        $dao = new MockFRSFileDao($this);
        $dao->expectOnce('restoreFile');
        $dao->setReturnValue('restoreFile', false);
        $fileFactory->setReturnValue('_getFRSFileDao', $dao);
        $backend = new MockBackendSystem($this);
        $backend->setReturnValue('chgrp', true);

        $user = mock('PFUser');
        $um = new MockUserManager($this);
        $um->setReturnValue('getCurrentUser', $user);
        $fileFactory->setReturnValue('_getUserManager', $um);
        $em = new MockEventManager($this);
        $fileFactory->setReturnValue('_getEventManager', $em);
        $release = new MockFRSRelease($this);
        $release->setReturnValue('isDeleted', false);
        $releaseFactory = new MockFRSReleaseFactory($this);
        $releaseFactory->setReturnValue('getFRSReleaseFromDb', $release);
        $fileFactory->setReturnValue('_getFRSReleaseFactory', $releaseFactory);

        $this->assertFalse($fileFactory->restoreFile($file, $backend));

        // Cleanup
        rmdir($GLOBALS['ftp_frs_dir_prefix'].'/DELETED/prj/p3_r1');
        rmdir($GLOBALS['ftp_frs_dir_prefix'].'/DELETED/prj');
        unlink($GLOBALS['ftp_frs_dir_prefix'].'/prj/p3_r1/toto.xls');
        rmdir($GLOBALS['ftp_frs_dir_prefix'].'/prj/p3_r1');
    }

    function testRestoreFileInDeletedRelease(){
        $fileFactory = new FRSFileFactoryTestRestore();

        // Create temp file
        $filepath = dirname(__FILE__).'/_fixtures/DELETED/prj/p3_r1/toto.xls.12';
        mkdir(dirname($filepath), 0750, true);
        touch($filepath);
        $this->assertTrue(is_dir(dirname($filepath)));

        $release = new MockFRSRelease($this);
        $release->setReturnValue('isDeleted', true);
        $releaseFactory = new MockFRSReleaseFactory($this);
        $releaseFactory->setReturnValue('getFRSReleaseFromDb', $release);
        $fileFactory->setReturnValue('_getFRSReleaseFactory', $releaseFactory);
        $dao = new MockFRSFileDao($this);
        $dao->expectOnce('cancelRestore');
        $fileFactory->setReturnValue('_getFRSFileDao', $dao);
        $file = new MockFRSFile($this);
        $backend = new MockBackendSystem($this);

        $this->assertFalse($fileFactory->restoreFile($file, $backend));
        $this->assertTrue(is_dir(dirname($filepath)));
        $this->assertFalse(file_exists($GLOBALS['ftp_frs_dir_prefix'].'/prj/p3_r1/toto.xls'));
        $this->assertFalse(is_dir($GLOBALS['ftp_frs_dir_prefix'].'/prj/p3_r1'));

        // Cleanup
        unlink($GLOBALS['ftp_frs_dir_prefix'].'/DELETED/prj/p3_r1/toto.xls.12');
        rmdir($GLOBALS['ftp_frs_dir_prefix'].'/DELETED/prj/p3_r1');
        rmdir($GLOBALS['ftp_frs_dir_prefix'].'/DELETED/prj');
    }

    function testRestoreDeletedFiles() {
        $refFile = new FRSFile(array('file_id' => 12));

        $dar = new MockDataAccessResult($this);
        $dar->setReturnValue('isError', false);
        $dar->setReturnValue('current', array('file_id' => 12));
        $dar->setReturnValueAt(0, 'valid', true);
        $dar->setReturnValueAt(1, 'valid', false);
        $dar->setReturnValue('rowCount', 1);

        $dao = new MockFRSFileDao($this);
        $dao->expectOnce('searchFilesToRestore');
        $dao->setReturnValue('searchFilesToRestore', $dar);

        $ff = new FRSFileFactoryTestRestoreFiles($this);
        $ff->setReturnValue('_getFRSFileDao', $dao);
        $backend  = new MockBackendSystem($this);
        $ff->expectOnce('restoreFile', array($refFile, $backend));
        $ff->setReturnValue('restoreFile', true);

        $this->assertTrue($ff->restoreDeletedFiles($backend));
    }

    function testRestoreDeletedFilesReturnFalse() {
        $dar = new MockDataAccessResult($this);
        $dar->setReturnValue('isError', false);
        $dar->setReturnValueAt(0, 'current', array('file_id' => 12));
        $dar->setReturnValueAt(1, 'current', array('file_id' => 13));
        $dar->setReturnValueAt(0, 'valid', true);
        $dar->setReturnValueAt(1, 'valid', true);
        $dar->setReturnValueAt(2, 'valid', false);
        $dar->setReturnValue('rowCount', 1);

        $dao = new MockFRSFileDao($this);
        $dao->expectOnce('searchFilesToRestore');
        $dao->setReturnValue('searchFilesToRestore', $dar);

        $ff = new FRSFileFactoryTestRestoreFiles($this);
        $ff->setReturnValue('_getFRSFileDao', $dao);
        $backend  = new MockBackendSystem($this);
        $ff->expectCallCount('restoreFile', 2);
        $ff->setReturnValueAt(0, 'restoreFile', false);
        $ff->setReturnValueAt(1, 'restoreFile', true);

        $this->assertFalse($ff->restoreDeletedFiles($backend));
    }

    function testRestoreDeletedFilesDBError() {
        $refFile = new FRSFile(array('file_id' => 12));

        $dar = new MockDataAccessResult($this);
        $dar->setReturnValue('isError', true);

        $dao = new MockFRSFileDao($this);
        $dao->expectOnce('searchFilesToRestore');
        $dao->setReturnValue('searchFilesToRestore', $dar);

        $ff = new FRSFileFactoryTestRestoreFiles($this);
        $ff->setReturnValue('_getFRSFileDao', $dao);
        $backend  = new MockBackendSystem($this);
        $ff->expectNever('restoreFile', array($refFile, $backend));
        $ff->setReturnValue('restoreFile', false);

        $this->assertFalse($ff->restoreDeletedFiles($backend));
    }

    function testRestoreDeletedFilesNoFiles() {
        $refFile = new FRSFile(array('file_id' => 12));

        $dar = new MockDataAccessResult($this);
        $dar->setReturnValue('isError', false);
        $dar->setReturnValue('rowCount', 0);

        $dao = new MockFRSFileDao($this);
        $dao->expectOnce('searchFilesToRestore');
        $dao->setReturnValue('searchFilesToRestore', $dar);

        $ff = new FRSFileFactoryTestRestoreFiles($this);
        $ff->setReturnValue('_getFRSFileDao', $dao);
        $backend  = new MockBackendSystem($this);
        $ff->expectNever('restoreFile', array($refFile, $backend));
        $ff->setReturnValue('restoreFile', false);

        $this->assertTrue($ff->restoreDeletedFiles($backend));
    }

    function testCompareMd5ChecksumsFail() {
        $fileFactory = new FRSFileFactory();
        $this->assertFalse($fileFactory->compareMd5Checksums('da1e100dc9e7bebb810985e37875de36', 'da1e100dc9e7bebb810985e37875de38'));
    }

    function testCompareMd5ChecksumsSucceedeEmptyHashes() {
        $fileFactory = new FRSFileFactory();
        $this->assertTrue($fileFactory->compareMd5Checksums('', ''));
    }

    function testCompareMd5ChecksumsSucceedeEmptyReference() {
        $fileFactory = new FRSFileFactory();
        $this->assertTrue($fileFactory->compareMd5Checksums('da1e100dc9e7bebb810985e37875de36', ''));
    }

    function testCompareMd5ChecksumsSucceedeEmptyComputed() {
        $fileFactory = new FRSFileFactory();
        $this->assertTrue($fileFactory->compareMd5Checksums('', 'da1e100dc9e7bebb810985e37875de38'));
    }

    function testCompareMd5ChecksumsSucceededComparison() {
        $fileFactory = new FRSFileFactory();
        $this->assertTrue($fileFactory->compareMd5Checksums('da1e100dc9e7bebb810985e37875de38', 'da1e100dc9e7bebb810985e37875de38'));
        $this->assertTrue($fileFactory->compareMd5Checksums('da1e100dc9e7bebb810985e37875de38', 'DA1E100DC9E7BEBB810985E37875DE38'));
    }

    function testMoveFileforgeOk() {
        // Create target release directory
        mkdir($GLOBALS['ftp_frs_dir_prefix'].'/prj/p123_r456');
        touch($GLOBALS['ftp_incoming_dir'].'/toto.txt');

        // Try to release a file named toto.txt in the same release
        $p = new MockProject($this);
        $p->expectOnce('getUnixName', array(false), 'Must have project name with capital letters if any');
        $p->setReturnValue('getUnixName', 'prj');

        $r = new FRSRelease();
        $r->setReleaseID(456);
        $r->setPackageID(123);
        $r->setProject($p);

        $f = new FRSFile();
        $f->setFileName('toto.txt');
        $f->setRelease($r);

        $ff = new FRSFileFactory();
        //var_dump(realpath(dirname(__FILE__).'/../../../'));
        $ff->setFileForge(dirname(__FILE__).'/../../../../src/utils/fileforge.pl');

        $res = $ff->moveFileForge($f, $r);
        $this->assertTrue($res);
        $this->assertTrue(file_exists($GLOBALS['ftp_frs_dir_prefix'].'/prj/'.$f->getFilePath()));

        unlink($GLOBALS['ftp_frs_dir_prefix'].'/prj/'.$f->getFilePath());
        rmdir($GLOBALS['ftp_frs_dir_prefix'].'/prj/p123_r456');
    }

    function testMoveFileforgeFileExist() {
        // Create toto.txt in the release directory
        mkdir($GLOBALS['ftp_frs_dir_prefix'].'/prj/p123_r456');
        touch($GLOBALS['ftp_frs_dir_prefix'].'/prj/p123_r456/toto.txt_1299584211');

        // Try to release a file named toto.txt in the same release
        $p = new MockProject();
        $p->expectOnce('getUnixName', array(false), 'Must have project name with capital letters if any');
        $p->setReturnValue('getUnixName', 'prj');

        $r = new FRSRelease();
        $r->setReleaseID(456);
        $r->setPackageID(123);
        $r->setProject($p);

        $f = new FRSFile();
        $f->setFileName('toto.txt');
        $f->setFilePath('toto.txt_1299584211');
        $f->setRelease($r);

        $ff = new FRSFileFactory();
        $this->assertFalse($ff->moveFileForge($f));

        unlink($GLOBALS['ftp_frs_dir_prefix'].'/prj/p123_r456/toto.txt_1299584211');
        rmdir($GLOBALS['ftp_frs_dir_prefix'].'/prj/p123_r456');
    }

    function testMoveFileforgeFileExistWithSpaces() {
        // Create toto.txt in the release directory
        mkdir($GLOBALS['ftp_frs_dir_prefix'].'/prj/p123_r456');
        touch($GLOBALS['ftp_frs_dir_prefix'].'/prj/p123_r456/toto zataz.txt');

        // Try to release a file named 'toto zataz.txt' in the same release
        $p = new MockProject();
        $p->expectOnce('getUnixName', array(false), 'Must have project name with capital letters if any');
        $p->setReturnValue('getUnixName', 'prj');

        $r = new FRSRelease();
        $r->setReleaseID(456);
        $r->setPackageID(123);
        $r->setProject($p);

        $f = new FRSFile();
        $f->setFileName('toto zataz.txt');
        $f->setRelease($r);

        $ff = new FRSFileFactory();
        $this->assertFalse($ff->moveFileForge($f));

        unlink($GLOBALS['ftp_frs_dir_prefix'].'/prj/p123_r456/toto zataz.txt');
        rmdir($GLOBALS['ftp_frs_dir_prefix'].'/prj/p123_r456');
    }

    function testCreateFileIllegalName(){
        $ff = new FRSFileFactory();
        $f = new FRSFile();
        $f->setFileName('%toto#.txt');

        try {
            $ff->createFile($f);
        }
        catch (Exception $e) {
            $this->assertIsA($e, 'FRSFileIllegalNameException');
        }
    }

    /**
     * We should not be able to create a file with the same name,
     * if an active one exists.
     */
    function testCreateFileAlreadyExistingAndActive() {
        // Create toto.txt in the release directory
        mkdir($GLOBALS['ftp_frs_dir_prefix'].'/prj/p123_r456');
        touch($GLOBALS['ftp_frs_dir_prefix'].'/prj/p123_r456/toto.txt_1299584197');

        $p = new MockProject($this);
        $p->setReturnValue('getUnixName', 'prj');

        $r = new FRSRelease();
        $r->setReleaseID(456);
        $r->setPackageID(123);
        $r->setGroupID(111);
        $r->setProject($p);

        $f = new FRSFile();
        $f->setFileName('toto.txt_1299584219');
        $f->setRelease($r);

        $ff = new FRSFileFactoryTestCreateFiles();
        $ff->setReturnValue('isFileBaseNameExists', True);
        try {
            $ff->createFile($f);
        }
        catch (Exception $e) {
            $this->assertIsA($e, 'FRSFileExistsException');
        }

        unlink($GLOBALS['ftp_frs_dir_prefix'].'/prj/p123_r456/toto.txt_1299584197');
        rmdir($GLOBALS['ftp_frs_dir_prefix'].'/prj/p123_r456');
    }

    /**
     * We should be able to create a file with the same name,
     * even if the active one has been deleted but not yet moved to staging area.
     */
    function testCreateFileAlreadyExistingAndMarkedToBeDeletedNotYetMoved() {
        // Create toto.txt in the release directory
        touch($GLOBALS['ftp_incoming_dir'].'/toto.txt');
        mkdir($GLOBALS['ftp_frs_dir_prefix'].'/prj/p123_r456');
        // toto.txt_1299584187 is the file having been deleted but not yet moved
        touch($GLOBALS['ftp_frs_dir_prefix'].'/prj/p123_r456/toto.txt_1299584187');

        $p = new MockProject($this);
        $p->setReturnValue('getUnixName', 'prj');

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
        $f->setFileLocation($GLOBALS['ftp_frs_dir_prefix'].'/prj/p123_r456');


        $ff = new FRSFileFactoryTestCreateFiles();
        $ff->setReturnValue('isFileBaseNameExists', False);
        $ff->setReturnValue('isSameFileMarkedToBeRestored', False);

        //moveFielForge will copy the new file to its destination
        $ff->setReturnValue('moveFileForge', True);
        touch($GLOBALS['ftp_frs_dir_prefix'].'/prj/p123_r456/toto.txt_1299584210');

        $ff->setReturnValue('create', 15225);
        $this->assertEqual($ff->createFile($f, ~FRSFileFactory::COMPUTE_MD5), $f);

        unlink($GLOBALS['ftp_incoming_dir'].'/toto.txt');
        unlink($GLOBALS['ftp_frs_dir_prefix'].'/prj/p123_r456/toto.txt_1299584210');
        unlink($GLOBALS['ftp_frs_dir_prefix'].'/prj/p123_r456/toto.txt_1299584187');
        rmdir($GLOBALS['ftp_frs_dir_prefix'].'/prj/p123_r456');
    }

    /**
     * We should not be able to create a file with the same name,
     * even if the restored one has not yet been moved to the corresponding  pi_rj.
     */

    function testCreateFileAlreadyMarkedToBeRestoredNotYetMoved() {
        // Create toto.txt in the release directory
        touch($GLOBALS['ftp_incoming_dir'].'/toto.txt');
        mkdir($GLOBALS['ftp_frs_dir_prefix'].'/prj/p123_r456');

        $p = new MockProject($this);
        $p->setReturnValue('getUnixName', 'prj');

        $r = new FRSRelease();
        $r->setReleaseID(456);
        $r->setPackageID(123);
        $r->setGroupID(111);
        $r->setProject($p);


        $f = new FRSFile();
        $f->setFileName('toto.txt');
        $f->setFilePath('toto.txt_1299584210');
        $f->setRelease($r);
        $f->setFileLocation($GLOBALS['ftp_frs_dir_prefix'].'/prj/p123_r456');


        $ff = new FRSFileFactoryTestCreateFiles();
        $ff->setReturnValue('isFileBaseNameExists', False);
        $ff->setReturnValue('isSameFileMarkedToBeRestored', True);

        try {
            $ff->createFile($f, ~FRSFileFactory::COMPUTE_MD5);
        }
        catch (Exception $e) {
            $this->assertIsA($e, 'FRSFileToBeRestoredException');
        }

        unlink($GLOBALS['ftp_incoming_dir'].'/toto.txt');
        rmdir($GLOBALS['ftp_frs_dir_prefix'].'/prj/p123_r456');
    }


    function testCreateFileNotYetIncoming(){
        $p = new MockProject();

        $r = new FRSRelease();
        $r->setReleaseID(456);
        $r->setPackageID(123);
        $r->setProject($p);

        $f = new FRSFile();
        $f->setFileName('toto.txt');
        $f->setRelease($r);
        $ff = new FRSFileFactoryTestCreateFiles();
        $this->assertFalse(is_file($GLOBALS['ftp_incoming_dir'].'/toto.txt'));
        try {
            $ff->createFile($f, ~FRSFileFactory::COMPUTE_MD5);
        }
        catch (Exception $e) {
            $this->assertIsA($e, 'FRSFileInvalidNameException');
        }
    }

    function testCreateFileSkipCompareMD5Checksums(){
        $p = new MockProject($this);
        $p->setReturnValue('getUnixName', 'prj');

        $r = new FRSRelease();
        $r->setReleaseID(456);
        $r->setPackageID(123);
        $r->setGroupID(111);
        $r->setProject($p);

        $f = new FRSFile();
        $f->setFileName('toto.txt');
        $f->setRelease($r);
        $f->setFileLocation($GLOBALS['ftp_incoming_dir']);
        $ff = new FRSFileFactoryTestCreateFiles();

        $path = $GLOBALS['ftp_incoming_dir'].'/'.$f->getFileName();
        touch($GLOBALS['ftp_incoming_dir'].'/toto.txt');
        $ff->setReturnValue('moveFileForge', True);
        $ff->setReturnValue('create', True);

        $ff->expectNever('compareMd5Checksums');
        $ff->createFile($f, ~FRSFileFactory::COMPUTE_MD5);

        unlink($GLOBALS['ftp_incoming_dir'].'/toto.txt');
    }

    function testCreateFileCompareMD5Checksums(){
        $r = new FRSRelease();
        $r->setReleaseID(456);
        $r->setPackageID(123);
        $r->setGroupID(111);

        $ff = new FRSFileFactoryTestCreateFiles();

        $f = new FRSFile();
        $f->setRelease($r);
        $f->setFileName('toto.txt');

        touch($GLOBALS['ftp_incoming_dir'].'/toto.txt');
        $path = $GLOBALS['ftp_incoming_dir'].'/'.$f->getFileName();
        $f->setReferenceMd5('d41d8cd98f00b204e9800998ecf8427e');

        try {
            $ff->createFile($f, FRSFileFactory::COMPUTE_MD5);
        }
        catch (Exception $e) {
            $this->assertIsA($e, 'FRSFileMD5SumException');
        }

        $this->assertNotNull($f->getComputedMd5());
        $this->assertTrue(FRSFileFactory::compareMd5Checksums($f->getComputedMd5(), $f->getReferenceMd5()));

        unlink($GLOBALS['ftp_incoming_dir'].'/toto.txt');
    }

    function testCreateFileMoveFileForgeKo(){
        $r = new FRSRelease();
        $r->setReleaseID(456);
        $r->setPackageID(123);
        $r->setGroupID(111);

        $ff = new FRSFileFactoryTestCreateFiles();
        $f = new FRSFile();
        $f->setRelease($r);

        $ff->setReturnValue('moveFileForge', False);
        try {
            $ff->createFile($f, ~FRSFileFactory::COMPUTE_MD5);
        }
        catch (Exception $e) {
            $this->assertIsA($e, 'FRSFileForgeException');
        }
    }

    function testCreateFileDbEntryMovedFile(){
        // Create toto.txt in the release directory
        mkdir($GLOBALS['ftp_frs_dir_prefix'].'/prj/p123_r456');
        touch($GLOBALS['ftp_frs_dir_prefix'].'/prj/p123_r456/toto.txt');
        touch($GLOBALS['ftp_incoming_dir'].'/toto.txt');

        $p = new MockProject();
        $p->setReturnValue('getUnixName', 'prj');

        $r = new FRSRelease();
        $r->setReleaseID(456);
        $r->setPackageID(123);
        $r->setProject($p);

        $f = new FRSFile();
        $f->setFileName('toto.txt');
        $f->setRelease($r);
        $f->setFileLocation($GLOBALS['ftp_frs_dir_prefix'].'/prj/p123_r456');

        $ff = new FRSFileFactoryTestCreateFiles();

        $ff->setReturnValue('moveFileForge', True);
        $ff->setReturnValue('create', False);

        try {
            $ff->createFile($f, ~FRSFileFactory::COMPUTE_MD5);
        }
        catch (Exception $e) {
            $this->assertIsA($e, 'FRSFileDbException');
        }

        //Cleanup
        unlink($GLOBALS['ftp_frs_dir_prefix'].'/prj/p123_r456/toto.txt');
        unlink($GLOBALS['ftp_incoming_dir'].'/toto.txt');
        rmdir($GLOBALS['ftp_frs_dir_prefix'].'/prj/p123_r456');
    }

    function testDeleteProjectFRSPackagesFail() {
        $packageFactory = new MockFRSPackageFactory();
        $packageFactory->setReturnValue('deleteProjectPackages', false);

        $releaseFactory = new MockFRSReleaseFactory();
        $releaseFactory->setReturnValue('deleteProjectReleases', true);
        $releaseFactory->setReturnValue('_getFRSPackageFactory', $packageFactory);

        $fileFactory = new FRSFileFactoryTestVersion();
        $fileFactory->setReturnValue('_getFRSReleaseFactory', $releaseFactory);
        $fileFactory->setReturnValue('moveDeletedFilesToStagingArea', true);

        $fileFactory->expectOnce('moveDeletedFilesToStagingArea');
        $releaseFactory->expectOnce('deleteProjectReleases');
        $packageFactory->expectOnce('deleteProjectPackages');
        $backend = new MockBackendSystem();
        $this->assertFalse($fileFactory->deleteProjectFRS(1, $backend));
    }

    function testDeleteProjectFRSReleasesFail() {
        $packageFactory = new MockFRSPackageFactory();
        $packageFactory->setReturnValue('deleteProjectPackages', true);

        $releaseFactory = new MockFRSReleaseFactory();
        $releaseFactory->setReturnValue('deleteProjectReleases', false);
        $releaseFactory->setReturnValue('_getFRSPackageFactory', $packageFactory);

        $fileFactory = new FRSFileFactoryTestVersion();
        $fileFactory->setReturnValue('_getFRSReleaseFactory', $releaseFactory);
        $fileFactory->setReturnValue('moveDeletedFilesToStagingArea', true);

        $fileFactory->expectOnce('moveDeletedFilesToStagingArea');
        $releaseFactory->expectOnce('deleteProjectReleases');
        $packageFactory->expectOnce('deleteProjectPackages');
        $backend = new MockBackendSystem();
        $this->assertFalse($fileFactory->deleteProjectFRS(1, $backend));
    }

    function testDeleteProjectFRSMoveFail() {
        $packageFactory = new MockFRSPackageFactory();
        $packageFactory->setReturnValue('deleteProjectPackages', true);

        $releaseFactory = new MockFRSReleaseFactory();
        $releaseFactory->setReturnValue('deleteProjectReleases', true);
        $releaseFactory->setReturnValue('_getFRSPackageFactory', $packageFactory);

        $fileFactory = new FRSFileFactoryTestVersion();
        $fileFactory->setReturnValue('_getFRSReleaseFactory', $releaseFactory);
        $fileFactory->setReturnValue('moveDeletedFilesToStagingArea', false);

        $fileFactory->expectOnce('moveDeletedFilesToStagingArea');
        $releaseFactory->expectOnce('deleteProjectReleases');
        $packageFactory->expectOnce('deleteProjectPackages');
        $backend = new MockBackendSystem();
        $this->assertFalse($fileFactory->deleteProjectFRS(1, $backend));
    }

    function testDeleteProjectFRSSuccess() {
        $packageFactory = new MockFRSPackageFactory();
        $packageFactory->setReturnValue('deleteProjectPackages', true);

        $releaseFactory = new MockFRSReleaseFactory();
        $releaseFactory->setReturnValue('deleteProjectReleases', true);
        $releaseFactory->setReturnValue('_getFRSPackageFactory', $packageFactory);

        $fileFactory = new FRSFileFactoryTestVersion();
        $fileFactory->setReturnValue('_getFRSReleaseFactory', $releaseFactory);
        $fileFactory->setReturnValue('moveDeletedFilesToStagingArea', true);

        $fileFactory->expectOnce('moveDeletedFilesToStagingArea');
        $releaseFactory->expectOnce('deleteProjectReleases');
        $packageFactory->expectOnce('deleteProjectPackages');
        $backend = new MockBackendSystem();
        $this->assertTrue($fileFactory->deleteProjectFRS(1, $backend));
    }

}

?>