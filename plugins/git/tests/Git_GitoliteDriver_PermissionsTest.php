<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'bootstrap.php';

Mock::generate('Project');
Mock::generate('GitRepository');
Mock::generate('PermissionsManager');

class Git_GitoliteDriver_PermissionsTest extends TuleapTestCase {
    protected $driver;
    protected $project;
    protected $project_id    = 100;
    
    protected $repository;
    protected $repository_id = 200;
    protected $admin_dir     = '/tmp/gitolite-admin-permissions';
    protected $admin_ref_dir = '/tmp/gitolite-admin-permissions-ref';
    protected $oldCwd;
    protected $repository_factory;

    public function setUp() {
        parent::setUp();
        
        $this->project_id++;
        $this->repository_id++;
        
        $this->oldCwd     = getcwd();
        
        $this->project    = new MockProject();
        $this->project->setReturnValue('getId', $this->project_id);
        $this->project->setReturnValue('getUnixName', 'project' . $this->project_id);
        
        $this->repository = new MockGitRepository();
        $this->repository->setReturnValue('getId', $this->repository);
        PermissionsManager::setInstance(new MockPermissionsManager());
        $this->permissions_manager = PermissionsManager::instance();

        $this->repository_factory = mock('GitRepositoryFactory');

        mkdir($this->admin_dir);
        $this->driver     = new Git_GitoliteDriver($this->admin_dir, mock('Git_Exec'), $this->repository_factory);
        
    }

    public function tearDown() {
        parent::tearDown();
        chdir($this->oldCwd);
        system('rm -Rf '. $this->admin_dir);
        PermissionsManager::clearInstance();
    }

    public function itReturnsEmptyStringForUnknownType() {
        $this->permissions_manager->setReturnValue('getAuthorizedUgroupIds', array());
        $result = $this->driver->fetchConfigPermissions($this->project, $this->repository, '__none__');
        $this->assertIdentical('', $result);
    }
    
    public function itReturnsEmptyStringForAUserIdLowerOrEqualThan_100() {
        $this->permissions_manager->setReturnValue('getAuthorizedUgroupIds', array(100));
        $result = $this->driver->fetchConfigPermissions($this->project, $this->repository, Git::PERM_READ);
        $this->assertIdentical('', $result);
    }
    
    public function itReturnsStringWithUserIdIfIdGreaterThan_100() {
        $this->permissions_manager->setReturnValue('getAuthorizedUgroupIds', array(101));
        $result = $this->driver->fetchConfigPermissions($this->project, $this->repository, Git::PERM_READ);
        $this->assertPattern('/=\s@ug_101$/', $result);
    }
    
    public function itReturnsSiteActiveIfUserGroupIsRegistered() {
        $this->permissions_manager->setReturnValue('getAuthorizedUgroupIds', array($GLOBALS['UGROUP_REGISTERED']));
        $result = $this->driver->fetchConfigPermissions($this->project, $this->repository, Git::PERM_READ);
        $this->assertPattern('/=\s@site_active @'. $this->project->getUnixName() .'_project_members$/', $result);
    }
    
    public function itReturnsProjectNameWithProjectMemberIfUserIsProjectMember() {
        $this->permissions_manager->setReturnValue('getAuthorizedUgroupIds', array($GLOBALS['UGROUP_PROJECT_MEMBERS']));
        $result = $this->driver->fetchConfigPermissions($this->project, $this->repository, Git::PERM_READ);
        $project_name = 'project' . $this->project_id;
        $this->assertPattern('/=\s@'.$project_name.'_project_members$/', $result);
    }
    
    public function itReturnsProjectNameWithProjectAdminIfUserIsProjectAdmin() {
        $this->permissions_manager->setReturnValue('getAuthorizedUgroupIds', array($GLOBALS['UGROUP_PROJECT_ADMIN']));
        $result = $this->driver->fetchConfigPermissions($this->project, $this->repository, Git::PERM_READ);
        $project_name = 'project' . $this->project_id;
        $this->assertPattern('/=\s@'.$project_name.'_project_admin$/', $result);
    }
    
    public function itPrefixesWithRForReaders() {
        $this->permissions_manager->setReturnValue('getAuthorizedUgroupIds', array(101));
        $result = $this->driver->fetchConfigPermissions($this->project, $this->repository, Git::PERM_READ);
        $this->assertPattern('/^\sR\s\s\s=/', $result);
    }
    
    public function itPrefixesWithRWForWriters() {
        $this->permissions_manager->setReturnValue('getAuthorizedUgroupIds', array(101));
        $result = $this->driver->fetchConfigPermissions($this->project, $this->repository, Git::PERM_WRITE);
        $this->assertPattern('/^\sRW\s\s=/', $result);
    }
    
    public function itPrefixesWithRWPlusForWritersPlus() {
        $this->permissions_manager->setReturnValue('getAuthorizedUgroupIds', array(101));
        $result = $this->driver->fetchConfigPermissions($this->project, $this->repository, Git::PERM_WPLUS);
        $this->assertPattern('/^\sRW\+\s=/', $result);
    }
    
    public function itReturnsAllGroupsSeparatedBySpaceIfItHasDifferentGroups() {
        $this->permissions_manager->setReturnValue('getAuthorizedUgroupIds', array(666, $GLOBALS['UGROUP_REGISTERED']));
        $result = $this->driver->fetchConfigPermissions($this->project, $this->repository, Git::PERM_READ);
        $this->assertIdentical(' R   = @ug_666 @site_active @'. $this->project->getUnixName() .'_project_members' . PHP_EOL, $result);
    }

}

?>