<?php
/*
 * Copyright (c) Xerox, 2009. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2009. Xerox Codendi Team.
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
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 * Description of GitDriverTest
 *
 * @author gstorchi
 */
$DIR = dirname(__FILE__);
require_once($DIR.'/../GitUnitTestCase.class.php');
require_once($DIR.'/../../include/GitDriver.class.php');


class GitDriverTest extends GitUnitTestCase {
    
    private $driver;
    
    //TODO find a remote git repoository
    private $remoteUrlToClone = '';
    private $fakeBranches = array('b1','b2','b3');

    public function setUp() {
        parent::setUp();
        $this->driver   = GitDriver::instance();        
        $this->removeTestDir();
        mkdir($this->rootPath);
        chdir($this->rootPath);
    }

    public function tearDown() {

    }
    
    /**
     * fork()
     */
    public function testFork_sourceIsFake() {
        $srcPath  = 'turlututuchapeaupointu';
        $destPath = '';
        try {
            $this->driver->fork($srcPath, $destPath);
        } catch ( GitDriverSourceNotFoundException $e ) {
            $this->pass();
        }
    }

    public function testFork_destIsFake() {
        if ( $this->createTestBareRepo('testFork_destIsFake') === false) {
            $this->fail('Preparing testFork_destIsFake failed');
            return false;
        }
        $srcPath  = '../repotestFork_destIsFake';
        $destPath = '/';
        try {
            $this->driver->fork($srcPath, $destPath);
        } catch ( GitDriverDestinationNotEmptyException $e ) {
            $this->pass();
        }

    }

    public function testFork_destIsOk() {
        if ( $this->createTestBareRepo('testFork_destIsOk') === false ) {
            $this->fail('Preparing testFork_destIsOk failed');
            return false;
        }
        $srcPath = '../repotestFork_destIsOk';
        $destPath = '';
        try {            
            $this->driver->fork($srcPath, $destPath);
        } catch(GitDriverException $e) {
            $this->fail($e->getMessage());
            return false;
        }
    }

    /**
     * init()
     */
    public function testInit_bareRepository() {
        $fileList = array(
            './config',
            './objects',
            './objects/pack',
            './objects/info',
            './HEAD',
            './refs',
            './refs/heads',
            './refs/tags',
            './branches',
            './description',
            './info',
            './info/exclude',
            './hooks',
            './hooks/update.sample',
            './hooks/post-update.sample',
            './hooks/post-receive.sample',
            './hooks/pre-commit.sample',
            './hooks/prepare-commit-msg.sample',
            './hooks/pre-applypatch.sample',
            './hooks/applypatch-msg.sample',
            './hooks/commit-msg.sample',
            './hooks/post-commit.sample',
            './hooks/pre-rebase.sample'
            );
        $rval = false;               
        try {
            $rval = $this->driver->init(true);
            $actualFileList = explode("\n",trim(shell_exec('find . ! -name .')));
            $diff = array_diff_assoc($actualFileList, $fileList);
            $this->assertTrue(empty($diff));
        } catch (GitDriverErrorException $e) {
            $this->assertErrorPattern('Git Driver Error', $e->getMessage());
            $this->fail($e->getMessage());
        }
        //checkFilelist
        $this->assertTrue($rval);

    }

    public function testInit_notBareRepository() {

        $fileList = array('./.git',
                    './.git/config',
                    './.git/objects',
                    './.git/objects/pack',
                    './.git/objects/info',
                    './.git/HEAD',
                    './.git/refs',
                    './.git/refs/heads',
                    './.git/refs/tags',
                    './.git/branches',
                    './.git/description',
                    './.git/info',
                    './.git/info/exclude',
                    './.git/hooks',
                    './.git/hooks/update.sample',
                    './.git/hooks/post-update.sample',
                    './.git/hooks/post-receive.sample',
                    './.git/hooks/pre-commit.sample',
                    './.git/hooks/prepare-commit-msg.sample',
                    './.git/hooks/pre-applypatch.sample',
                    './.git/hooks/applypatch-msg.sample',
                    './.git/hooks/commit-msg.sample',
                    './.git/hooks/post-commit.sample',
                    './.git/hooks/pre-rebase.sample'
                    );

        $rval = false;                
        try {            
            $rval = $this->driver->init(false);
            $actualFileList = explode("\n", trim(shell_exec("find . ! -name .")));            
            $diff = array_diff_assoc($actualFileList, $fileList);           
            $this->assertTrue(empty($diff));            
        } catch (GitDriverErrorException $e) {
            $this->assertErrorPattern('Git Driver Error', $e->getMessage());
            $this->fail($e->getMessage());
        }
        //checkFilelist
        $this->assertTrue($rval);
    }


    public function testDelete_FailedEmptyPath() {
        try {
            $this->driver->delete('');
        } catch (GitDriverErrorException $e) {
            $this->assertEqual( $e->getMessage(), 'Git Driver Error Empty path or permission denied ');
            return;
        }
        $this->fail();
    }

    public function testDelete_Error() {
        $this->skip();
        return;
        try {
            $this->driver->delete('sasdasdasd');
        } catch (GitDriverErrorException $e) {
            $this->assertEqual( $e->getMessage(), 'Git Driver Error Empty path or permission denied ');
            return;
        }
        $this->fail();
    }

    public function testDelete_Success() {
        $path    = 'myproject/myrepo.git';
        $absPath =  $this->rootPath.DIRECTORY_SEPARATOR.'myproject/myrepo.git';
        $this->createRepo($path);
        $r = $this->driver->delete($absPath);
        if ( $r === true && !file_exists($absPath) ) {
            $this->pass();
        } else {
            $this->fail();
        }
    }

    public function testInit_bareInitFailed() {
        $this->skip();
    }

    public function testInit_notBareInitFailed() {
        $this->skip();
    }

    public function testListBranch() {
        $this->skip();
        return;
        $repoPath = $this->createTestRepo('testInit_bareInitFailed');
        foreach ($this->fakeBranches as $branch) {
            $this->createBranch($branch);
        }
        $expectedList = array();
        $actualList = $this->driver->listBranch($repoPath);
        $diff = array_diff_assoc($actualList, $expectedList);
        $this->assertTrue( empty($diff) );
    }
}

?>
