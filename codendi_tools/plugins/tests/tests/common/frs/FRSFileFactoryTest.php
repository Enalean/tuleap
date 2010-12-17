<?php

require_once('common/frs/FRSFileFactory.class.php');
require_once('common/frs/FRSReleaseFactory.class.php');
require_once('common/backend/BackendSystem.class.php');

Mock::generate('DataAccessResult');
Mock::generate('FRSReleaseFactory');
Mock::generate('FRSRelease');
Mock::generate('FRSFileDao');
Mock::generate('FRSFile');
Mock::generate('BackendSystem');
Mock::generatePartial('FRSFileFactory', 'FRSFileFactoryTestVersion', array('_getFRSReleaseFactory'));
Mock::generatePartial('FRSFileFactory', 'FRSFileFactoryTestPurgeFiles', array('_getFRSFileDao', 'purgeFile'));
Mock::generatePartial('FRSFileFactory', 'FRSFileFactoryTestPurgeOneFile', array('_getFRSFileDao'));
Mock::generatePartial('FRSFileFactory', 'FRSFileFactoryTestMoveToStaging', array('_getFRSFileDao', 'moveDeletedFileToStagingArea'));
Mock::generatePartial('FRSFileFactory', 'FRSFileFactoryTestPurgeDeletedFiles', array('purgeFiles', 'moveDeletedFilesToStagingArea', 'cleanStaging', 'restoreDeletedFiles'));
Mock::generatePartial('FRSFileFactory', 'FRSFileFactoryTestRestore', array('_getFRSFileDao'));
Mock::generatePartial('FRSFileFactory', 'FRSFileFactoryTestRestoreFiles', array('_getFRSFileDao', 'restoreFile'));
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * 
 *
 * Tests the FRSFileFactory class
 */
class FRSFileFactoryTest extends UnitTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function FRSFileFactoryTest($name = 'FRSfileFactory test') {
        $this->UnitTestCase($name);
    }

    function setUp() {
        $GLOBALS['ftp_frs_dir_prefix'] = dirname(__FILE__).'/_fixtures';
    }

    function tearDown() {
        unset($GLOBALS['ftp_frs_dir_prefix']);
    }

    function testgetUploadSubDirectory() {
        $package_id = rand(1, 1000);
        $release_id = rand(1, 1000);
        
        $release =& new MockFRSRelease($this);
        $release->setReturnValue('getPackageID', $package_id);
        $release->setReturnValue('getReleaseID', $release_id);
        
        $release_fact =& new MockFRSReleaseFactory($this);
        $release_fact->setReturnReference('getFRSReleaseFromDb', $release);
        
        $file_fact =& new FRSFileFactoryTestVersion();
        $file_fact->setReturnReference('_getFRSReleaseFactory', $release_fact);
        $file_fact->FRSFileFactory();
        
        $sub_dir = $file_fact->getUploadSubDirectory($release_id);
        $this->assertEqual($sub_dir, 'p'.$package_id.'_r'.$release_id);
    }

   function testPurgeDeletedFiles() {
        $ff = new FRSFileFactoryTestPurgeDeletedFiles($this);
        $ff->expectOnce('moveDeletedFilesToStagingArea');
        $ff->expectOnce('purgeFiles', array(1287504083));
        $ff->expectOnce('cleanStaging');
        $backend = new MockBackendSystem($this);
        $ff->expectOnce('restoreDeletedFiles', array($backend));
        
        $ff->purgeDeletedFiles(1287504083, $backend);
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

        $ff->expectNever('moveDeletedFileToStagingArea');
        
        $this->assertTrue($ff->moveDeletedFilesToStagingArea());
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

        $this->assertTrue($ff->moveDeletedFileToStagingArea($file));

        $this->assertTrue(is_file($GLOBALS['ftp_frs_dir_prefix'].'/DELETED/prj/p1_r1/foobar.xls.12'));
        $this->assertFalse(is_file(dirname(__FILE__).'/_fixtures/prj/p1_r1/foobar.xls'));
        $this->assertFalse(is_dir(dirname(__FILE__).'/_fixtures/prj/p1_r1'));

        // Clean-up
        unlink(dirname(__FILE__).'/_fixtures/DELETED/prj/p1_r1/foobar.xls.12');
        rmdir(dirname(__FILE__).'/_fixtures/DELETED/prj/p1_r1');
        rmdir(dirname(__FILE__).'/_fixtures/DELETED/prj');
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

        $this->assertTrue($ff->moveDeletedFileToStagingArea($file));

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
        $ff->expectOnce('moveDeletedFileToStagingArea', array($refFile));
        
        $this->assertTrue($ff->moveDeletedFilesToStagingArea());
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
        
        $this->assertTrue($ff->purgeFiles(1287504083));
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
        
        $ff->expectOnce('purgeFile', array($refFile));
        
        $this->assertTrue($ff->purgeFiles(1287504083));
    }

    function testPurgeFile() {
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
        
        $this->assertTrue($ff->purgeFile($file));
        $this->assertFalse(is_file($filepath), "File should be deleted");
        
        // Cleanup
        rmdir($GLOBALS['ftp_frs_dir_prefix'].'/DELETED/prj/p1_r1');
        rmdir($GLOBALS['ftp_frs_dir_prefix'].'/DELETED/prj');
    }

    function testRemoveStagingEmptyDirectories() {
        $ff = new FRSFileFactory();

        mkdir($GLOBALS['ftp_frs_dir_prefix'].'/DELETED/prj/p1_r1', 0750, true);
        mkdir($GLOBALS['ftp_frs_dir_prefix'].'/DELETED/prj2/p2_r5', 0750, true);
        touch($GLOBALS['ftp_frs_dir_prefix'].'/DELETED/prj2/p2_r5/file.txt.7');
        mkdir($GLOBALS['ftp_frs_dir_prefix'].'/DELETED/prj3/p7_r8', 0750, true);
        mkdir($GLOBALS['ftp_frs_dir_prefix'].'/DELETED/prj3/p9_r10', 0750, true);
        touch($GLOBALS['ftp_frs_dir_prefix'].'/DELETED/prj3/p9_r10/foo.txt.12');
        
        $this->assertTrue($ff->cleanStaging());
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
        mkdir($GLOBALS['ftp_frs_dir_prefix'].'/prj/p1_r1/', 0750, true);
        $this->assertTrue(is_dir($GLOBALS['ftp_frs_dir_prefix'].'/prj/p1_r1/'));

        $dao = new MockFRSFileDao($this);
        $dao->expectOnce('restoreFile');
        $dao->setReturnValue('restoreFile', true);
        $fileFactory->setReturnValue('_getFRSFileDao', $dao);
        $backend = new MockBackendSystem($this);
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
        $this->assertTrue(is_dir(dirname($GLOBALS['ftp_frs_dir_prefix'].'/prj/p2_r1/')));
        $backend->setReturnValue('chgrp', true);

        $dao = new MockFRSFileDao($this);
        $dao->expectOnce('restoreFile');
        $dao->setReturnValue('restoreFile', true);
        $fileFactory->setReturnValue('_getFRSFileDao', $dao);

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
        $this->assertTrue(is_dir(dirname($GLOBALS['ftp_frs_dir_prefix'].'/prj/p3_r1/')));

        $dao = new MockFRSFileDao($this);
        $dao->expectOnce('restoreFile');
        $dao->setReturnValue('restoreFile', false);
        $fileFactory->setReturnValue('_getFRSFileDao', $dao);
        $backend = new MockBackendSystem($this);
        $backend->setReturnValue('chgrp', true);
        $this->assertFalse($fileFactory->restoreFile($file, $backend));

        // Cleanup
        rmdir($GLOBALS['ftp_frs_dir_prefix'].'/DELETED/prj/p3_r1');
        rmdir($GLOBALS['ftp_frs_dir_prefix'].'/DELETED/prj');
        unlink($GLOBALS['ftp_frs_dir_prefix'].'/prj/p3_r1/toto.xls');
        rmdir($GLOBALS['ftp_frs_dir_prefix'].'/prj/p3_r1');
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
    }
}
?>
