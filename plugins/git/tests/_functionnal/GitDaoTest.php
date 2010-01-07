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
require_once($DIR.'/../GitUnitTestCase.class.php');
require_once($DIR.'/../../include/GitRepository.class.php');
require_once('common/project/Project.class.php');


Mock::generate('GitRepository');
Mock::generate('Project');


//Mock::generatePartial('GitDao', 'MockPartialGitDao', array('getTable') );
/**
 * Description of GitDaoTest
 *
 * @author gstorchi
 */
class GitDaoTest extends GitUnitTestCase {

   private $repository;
   private $dao;
   
   protected function createTempTable() {
       $query  = <<<SQL
 CREATE TABLE IF NOT EXISTS codendi.{$this->dao->getTable()} (
repository_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
repository_name VARCHAR( 255 ) NOT NULL ,
repository_description VARCHAR( 255 ) NULL ,
repository_path VARCHAR( 255 ) NOT NULL ,
repository_parent_id INT NULL ,
project_module_name VARCHAR( 255 ) NOT NULL ,
project_id INT NOT NULL default '0',
INDEX ( project_id )
) ENGINE = InnoDB        

SQL;
    
    return $this->dao->update($query);
   }

   protected function deleteData() {
    $query = 'TRUNCATE TABLE '.$this->dao->getTable();
    return $this->dao->update($query);
   }

   protected function insertData() {
       $query = <<< SQL
REPLACE INTO {$this->dao->getTable()}
(repository_id, repository_name, repository_description,
repository_path, repository_parent_id, project_module_name, project_id)
VALUES
(1, 'test1', 'description test1', 'myproject/mymodule/test1.git', NULL, 'mymodule', 0),
(2, 'test2', 'description test2', 'myproject/mymodule/test2.git', NULL, 'mymodule', 1)
SQL;
       return $this->dao->update($query);
   }

   public function setUp() {
       parent::setUp();
       $this->dao = new GitDao();
      // $this->dao = new MockPartialGitDao();
      // $this->dao->setReturnValue('getTable', 'tmp_git_plugin_test');
       $this->dao->setTable('tmp_git_plugin_test');
       $this->repository = new MockGitRepository();
       
       $this->createTempTable();
       $this->insertData();
   }

   public function tearDown() {
       parent::tearDown();
       $this->deleteData();
   }

   public function testExists_True() {
        $r = $this->dao->exists(1);
        $this->assertTrue($r);
   }

   public function testExists_False() {
        $r = $this->dao->exists(0);
        $this->assertFalse($r);
   }

   public function testDelete_EmptyId() {
       $this->repository->setReturnValue('getId', 0);
       try {
            $this->dao->delete($this->repository);
       } catch (GitDaoException $e) {
            $this->assertEqual($e->getMessage(), 'Unknown (empty) repository id');
            return;
       }
       $this->fail();
   }

   public function testDelete_QueryFailure() {
       $id = 123;
       $this->repository->setReturnValue('getId', $id);
       try {
           $this->dao->delete($this->repository);
       } catch (GitDaoException $e) {
           $this->assertEqual($e->getMessage(), 'Unable to delete repository from database '.$id );
           return;
       }
       $this->fail();
   }

   public function testDelete_QuerySuccess() {
       $id = 1;
       $this->repository->setReturnValue('getId', $id);       
       $r = $this->dao->delete($this->repository);
       $this->assertTrue($r);
   }

   public function testGetProjectRepositoryById_EmptyId() {
       $id = 0;
       $this->repository->setReturnValue('getId', $id);
       $r = $this->dao->getProjectRepositoryById($this->repository);
       $this->assertFalse($r);
   }

   public function testGetProjectRepositoryById_EmptyResult() {
       $id = 123;
       $this->repository->setReturnValue('getId', $id);
       $r = $this->dao->getProjectRepositoryById($this->repository);
       $this->assertFalse($r);
   }

    public function testGetProjectRepositoryById_Success() {
       Mock::generatePartial('GitRepository', 'MockPartialGitRepository', array('load') );
       $repository = new MockPartialGitrepository();
       $id = 1;
       $repository->setId($id);
       $repository->setReturnValue('load', true);
       $r = $this->dao->getProjectRepositoryById($repository);       
       $this->assertTrue($r);
       $this->assertEqual( $repository->getPath() , 'myproject/mymodule/test1.git');
       $this->assertEqual( $repository->getDescription() , 'description test1');
       $this->assertEqual( $repository->getModule() , 'mymodule');
       $this->assertEqual( $repository->getParentId() , 0);

   }

   public function testGetProjectRepository_EmptyId() {
       Mock::generatePartial('GitRepository', 'MockPartialGitRepository2', array('getName') );
       $repository = new MockPartialGitRepository2();
       $project    = new MockProject();
       $project->setReturnValue('getId', '0');
       $repository->setReturnValue('getName', '');
       $repository->setProject($project);
       $r = $this->dao->getProjectRepository($repository);
       $this->assertFalse($r);
   }

   public function testGetProjectRepository_EmptyResult() {
       Mock::generatePartial('GitRepository', 'MockPartialGitRepository3', array('getName') );
       $repository = new MockPartialGitRepository3();
       $project    = new MockProject();
       $project->setReturnValue('getId', '234234');
       $repository->setReturnValue('getName', 'asdasd');
       $repository->setProject($project);
       $r = $this->dao->getProjectRepository($repository);
       $this->assertFalse($r);
   }

    public function testGetProjectRepository_Success() {
       Mock::generatePartial('GitRepository', 'MockPartialGitRepository4', array('load') );
       $repository = new MockPartialGitrepository4();
       $project    = new MockProject();
       $project->setReturnValue('getId', 1);       
       $repository->setProject($project);
       $repository->setName('test2');
       $repository->setReturnValue('load', true);
       $r = $this->dao->getProjectRepository($repository);
       $this->assertTrue($r);       
       $this->assertEqual( $repository->getPath() , 'myproject/mymodule/test2.git');
       $this->assertEqual( $repository->getDescription() , 'description test2');
       $this->assertEqual( $repository->getModule() , 'mymodule');
       $this->assertEqual( $repository->getParentId() , 0);
   }

   public function testSave_Update() {
       Mock::generatePartial('GitRepository', 'MockPartialGitRepository5', array('load','getId') );
       $parent = clone $this->repository;
       $parent->setReturnValue('getId', 0);
       $this->repository->setReturnValue('getId', 1);
       $this->repository->setReturnValue('getName', 'testupdate1');
       $this->repository->setReturnValue('getModule', 'mymodule');
       $this->repository->setReturnReference('getParent', $parent);
       $this->repository->setReturnValue('getPath', 'myproject/mymodule/testupdate1.git');
       $this->repository->setReturnValue('getDescription', 'test of repository info update');
       $this->dao->save($this->repository);
       $repositoryObject = new MockPartialGitRepository5();
       $repositoryObject->setReturnValue('getId', 1);
       $repositoryObject->setReturnValue('load', true);
       $project = new MockProject();
       $repositoryObject->setParent($parent);
       $repositoryObject->setProject($project);
       $r = $this->dao->getProjectRepositoryById($repositoryObject);
       $this->assertTrue($r);
       //only update description
       $this->assertEqual($repositoryObject->getDescription(), 'test of repository info update');
   }

   public function testSave_Insert() {
       Mock::generatePartial('GitRepository', 'MockPartialGitRepository6', array('load','getId') );
       $parent = clone $this->repository;
       $parent->setReturnValue('getId', 0);
       $this->repository->setReturnValue('getId', 12312398923);
       $this->repository->setReturnValue('getName', 'testupdate1');
       $this->repository->setReturnValue('getModule', 'mymodule');
       $this->repository->setReturnReference('getParent', $parent);
       $this->repository->setReturnValue('getPath', 'myproject/mymodule/testupdate1.git');
       $this->repository->setReturnValue('getDescription', 'test of repository info update');
       $id = $this->dao->save($this->repository);
       $this->assertEqual($id, 3);
       $repositoryObject = new MockPartialGitRepository5();
       $repositoryObject->setReturnValue('getId', $id);
       $repositoryObject->setReturnValue('load', true);
       $project = new MockProject();
       $repositoryObject->setParent($parent);
       $repositoryObject->setProject($project);
       $this->dao->getProjectRepositoryById($repositoryObject);
       //only update description
       $this->assertEqual($repositoryObject->getDescription(), 'test of repository info update');
       
   }

   public function testSave_failed() {
       $this->skip();
       return;
       Mock::generatePartial('GitRepository', 'MockPartialGitRepository7', array('load','getId') );
       $parent = clone $this->repository;
       $parent->setReturnValue('getId', 'thistringwillmakethetestfail');
       $this->repository->setReturnValue('getId', 12312398923);
       $this->repository->setReturnValue('getName', 'testupdate1');
       $this->repository->setReturnValue('getModule', 'mymodule');
       $this->repository->setReturnReference('getParent', $parent);
       $this->repository->setReturnValue('getPath', 'myproject/mymodule/testupdate1.git');
       $this->repository->setReturnValue('getDescription', 'test of repository info update');
       try {
           $this->dao->save($this->repository);
       } catch (GitDaoException $e ) {
           $this->assertEqual($e->getMessage(), 'Query failed');
           return;
       }
       $this->fail();

   }
   

}

?>
