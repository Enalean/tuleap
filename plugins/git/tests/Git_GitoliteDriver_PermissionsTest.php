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

require_once dirname(__FILE__).'/../include/Git_GitoliteDriver.class.php';
require_once dirname(__FILE__).'/../include/GitRepository.class.php';

Mock::generate('Project');
Mock::generate('GitRepository');
Mock::generate('PermissionsManager');

class Git_GitoliteDriver_PermissionsTest extends TuleapTestCase {
    protected $driver;
    protected $project;
    protected $project_id    = 100;
    
    protected $repository;
    protected $repository_id = 200;
    protected $admin_dir     = '/tmp/gitolite-admin';
    protected $admin_ref_dir = '/tmp/gitolite-admin-ref';
    public function setUp() {
        parent::setUp();
        $this->project_id++;
        $this->repository_id++;
        
        $this->project    = new MockProject();
        $this->project->setReturnValue('getId', $this->project_id);
        $this->project->setReturnValue('getUnixName', 'project' . $this->project_id);
        
        $this->repository = new MockGitRepository();
        $this->repository->setReturnValue('getId', $this->repository);
        PermissionsManager::setInstance(new MockPermissionsManager());
        
        mkdir($this->admin_dir);
        $this->driver     = new Git_GitoliteDriver($this->admin_dir);
        
    }

    public function tearDown() {
        parent::tearDown();
        rmdir($this->admin_dir);
        PermissionsManager::clearInstance();
    }

    public function itReturnsEmptyStringForUnknownType() {
        PermissionsManager::instance()->setReturnValue('getAuthorizedUgroups', TestHelper::arrayToDar(array()));
        $result = $this->driver->fetchConfigPermissions($this->project, $this->repository, '__none__');
        $this->assertEqual('', $result);
    }

    function FetchPermissions() {
        $prj = new MockProject($this);
        $prj->setReturnValue('getUnixName', 'project1');

        $ug_1 = 130;
        $ug_2 = 140;
        $ug_3 = 150;
        $ug_4 = 160;
        $ug_5 = 170;
        $ug_6 = 180;
        $ug_n = 100;

        $this->assertIdentical('',
            $driver->fetchPermissions($prj, array(), array(), array())
        );

        $this->assertIdentical('',
            $driver->fetchPermissions($prj, array($ug_n), array($ug_n), array($ug_n))
        );

        $this->assertIdentical(
            file_get_contents($this->_fixDir .'/perms/one-reader.conf'),
            $driver->fetchPermissions($prj, array($ug_1), array(), array())
        );
        
        $this->assertIdentical(
            file_get_contents($this->_fixDir .'/perms/one-writer.conf'),
            $driver->fetchPermissions($prj, array(), array($ug_1), array())
        );
        
        $this->assertIdentical(
            file_get_contents($this->_fixDir .'/perms/one-rewinder.conf'),
            $driver->fetchPermissions($prj, array(), array(), array($ug_1))
        );
        
        $this->assertIdentical(
            file_get_contents($this->_fixDir .'/perms/two-readers.conf'),
            $driver->fetchPermissions($prj, array($ug_1, $ug_2), array(), array())
        );
        
        $this->assertIdentical(
            file_get_contents($this->_fixDir .'/perms/two-writers.conf'),
            $driver->fetchPermissions($prj, array(), array($ug_1, $ug_2), array())
        );
        
        $this->assertIdentical(
            file_get_contents($this->_fixDir .'/perms/two-rewinders.conf'),
            $driver->fetchPermissions($prj, array(), array(), array($ug_1, $ug_2))
        );
        
        $this->assertIdentical(
            file_get_contents($this->_fixDir .'/perms/full.conf'),
            $driver->fetchPermissions($prj, array($ug_1, $ug_2), array($ug_3, $ug_4), array($ug_5, $ug_6))
        );
        
        $this->assertIdentical(
            file_get_contents($this->_fixDir .'/perms/default.conf'),
            $driver->fetchPermissions($prj, array('2'), array('3'), array())
        );
    }

}

?>