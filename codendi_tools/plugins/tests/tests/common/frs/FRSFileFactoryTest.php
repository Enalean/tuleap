<?php

require_once('common/frs/FRSFileFactory.class.php');
require_once('common/frs/FRSReleaseFactory.class.php');

Mock::generate('DataAccessResult');
Mock::generate('FRSReleaseFactory');
Mock::generate('FRSRelease');
Mock::generate('FRSFileDao');
Mock::generate('FRSFile');
Mock::generatePartial('FRSFileFactory', 'FRSFileFactoryTestVersion', array('_getFRSReleaseFactory'));
Mock::generatePartial('FRSFileFactory', 'FRSFileFactoryTestPurgeFiles', array('_getFRSFileDao', 'purgeFile'));
Mock::generatePartial('FRSFileFactory', 'FRSFileFactoryTestPurgeOneFile', array('_getFRSFileDao'));
Mock::generatePartial('FRSFileFactory', 'FRSFileFactoryTestMoveToStaging', array('_getFRSFileDao', 'moveDeletedFileToStagingArea'));

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

        // Clean-up
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
    }
}
?>
