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
$DIR = dirname(__FILE__);
require_once('GitUnitTestCase.class.php');
require_once($DIR.'/../include/GitBackend.class.php');
require_once($DIR.'/../include/GitRepository.class.php');
require_once('common/project/Project.class.php');
Mock::generate('GitRepository');
Mock::generate('GitDriver');
Mock::generate('GitDao');
Mock::generate('Project');
Mock::generatePartial('GitBackend', 'MockPartialGitBackend', array('getDao', 'getDriver','getGitRootPath','setPermissions') );

/**
 * Description of GitBackendTest
 *
 * @author gstorchi
 */
class GitBackendTest extends GitUnitTestCase {
    
    //put your code here    
    private $repository;    
    private $backend;
    private $dao;
    private $driver;
    
    public function setUp() {
        parent::setUp();
        $this->removeTestDir();
        mkdir($this->rootPath);
        chdir($this->rootPath);                
        $this->dao        = new MockGitDao();
        $this->driver     = new MockGitDriver();
        $this->project    = new MockProject();
        $this->project->setReturnValue('getUnixName','myproject');
        $this->repository = new MockGitRepository($this);
        $this->repository->setReturnReference('getProject', $this->project);
        //backend
        $this->backend    = new MockPartialGitBackend($this);
        $this->backend->setReturnReference('getDao', $this->dao);
        $this->backend->setReturnReference('getDriver', $this->driver );
        $this->backend->setReturnValue('getGitRootPath', $this->rootPath);
        
    }

    public function tearDown() {
        //$this->removeTestDir();
    }

    public function testCreateReference_AlreadyExists() {
        $this->repository->setReturnValue('exists', true);
        $r = $this->backend->createReference($this->repository);
        $this->assertFalse($r);
    }
    //TODO make a stub for GitDriver 
    public function testCreateReference_DoesNotExist() {
        $this->repository->setReturnValue('exists', false);
        //$repository->setReturnValue('getRootPath', 'myproject/mymodule');
        $this->repository->setReturnValue('getPath', 'myproject/mymodule/myrepo.git');
        $r = $this->backend->createReference($this->repository);        
        $this->assertTrue($r);        
    }

    public function testCreateFork_AlreadyExists() {
        $clone = $this->repository;
        $clone->setReturnValue('exists', true);
        $r = $this->backend->createFork($clone);
        $this->assertFalse($r);
    }

     public function testCreateFork_DoesNotExists() {
        $this->createRepo('myproject/mymodule/myrepo.git');
        Mock::generate('GitRepository');
        //save issues a insert into mysql -> we expect an id as a return value
        $this->dao->setReturnValue('save', 1);
        $this->driver->setReturnValue('fork', true);
        $parent = $this->repository;
        $parent->setReturnValue('load', true);
        $parent->setReturnValue('getRootPath', 'myproject/mymodule/');
        $parent->setReturnValue('getPath', 'myproject/mymodule/myrepo.git');
        //$parent->setReturnValue('getId', 1);
        $clone = new MockGitRepository();        
        $clone->setReturnReference('getParent', $parent);
        $clone->setReturnReference('getDao', $dao);
        $clone->setReturnValue('load', true);
        $clone->setReturnValue('exists', false);
        $clone->setReturnValue('getRootPath', 'myproject/mymodule/');
        $clone->setReturnValue('getPath', 'myproject/mymodule/myrepoclone.git');

        $r = $this->backend->createFork($clone);
        $this->assertTrue($r);
    }

    public function _testDelete_EmptyRootPath() {
        $this->repository->setReturnValue('getPath', '');
        $this->driver->throwOn('delete', new GitDriverErrorException('Empty path or permission denied') );
        $this->backend->delete($this->repository);
        $this->expectException( new GitDriverErrorException() );                
    }

    public function _testDelete_DaoFailed() {
        $this->createRepo('myproject/mymodule/myrepo.git');
        $this->repository->setReturnValue('getId', 0);
        $this->repository->setReturnValue('getPath', 'myproject/mymodule/myrepo.git');
        $this->dao->throwOn('delete', new GitDaoException() );
        $this->driver->setReturnValue('delete', true);     
        $this->backend->delete($this->repository);
        $this->expectException( new GitDaoException() );
    }

    public function _testDelete_DriverFailed() {
        $this->repository->setReturnValue('getId', 0);
        $this->repository->setReturnValue('getPath', 'myproject/mymodule/myrepo.git');
        $this->driver->throwOn('delete', new GitDriverErrorException() );
        $this->dao->setReturnValue('delete', true);
        $this->backend->delete($this->repository);
        $this->expectException( new GitDriverErrorException() );
    }

    public function testDelete_Succeed() {
        $this->repository->setReturnValue('getId', 0);
        $this->repository->setReturnValue('getPath', 'myproject/mymodule/myrepo.git');
        $this->driver->setReturnValue('delete', true );
        $this->dao->setReturnValue('delete', true);
        $r = $this->backend->delete($this->repository);
        $this->assertTrue($r);
    }

}

?>
