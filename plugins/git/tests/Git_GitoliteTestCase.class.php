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
require_once(dirname(__FILE__).'/../include/constants.php');
require_once dirname(__FILE__).'/../include/Git.class.php';
require_once dirname(__FILE__).'/../include/Git_GitoliteDriver.class.php';

Mock::generate('Project');
Mock::generate('PFUser');
Mock::generate('GitDao');
Mock::generate('PermissionsManager');
Mock::generate('DataAccessResult');
Mock::generate('Git_PostReceiveMailManager');

abstract class Git_GitoliteTestCase extends TuleapTestCase {
    
    /** @var Git_GitoliteDriver */
    protected $driver;
    /** @var UserManager */
    protected $user_manager;
    /** @var Git_Exec */
    protected $gitExec;
    
    public function setUp() {
        parent::setUp();
        $this->cwd           = getcwd();
        $this->_fixDir       = dirname(__FILE__).'/_fixtures';
        $tmpDir              = $this->getTmpDir();
        $this->_glAdmDirRef  = $tmpDir.'/gitolite-admin-ref';
        $this->_glAdmDir     = $tmpDir.'/gitolite-admin';
        $this->repoDir       = $tmpDir.'/repositories';
        
        // Copy the reference to save time & create symlink because
        // git is very sensitive to path you are using. Just symlinking
        // spots bugs
        system('tar -xf '. $this->_fixDir.'/gitolite-admin-ref' .'.tar --directory '.$tmpDir);
        symlink($this->_glAdmDirRef, $this->_glAdmDir);

        mkdir($this->repoDir);

        $this->httpsHost = $GLOBALS['sys_https_host'];

        $GLOBALS['sys_https_host'] = 'localhost';
        PermissionsManager::setInstance(new MockPermissionsManager());
        $this->permissions_manager = PermissionsManager::instance();
        $this->gitExec = partial_mock('Git_Exec', array('push'), array($this->_glAdmDir));
        stub($this->gitExec)->push()->returns(true);
        
        $this->user_manager = mock('UserManager');
        $this->dumper = new Git_Gitolite_SSHKeyDumper($this->_glAdmDir, $this->gitExec, $this->user_manager);
        
        $this->driver = new Git_GitoliteDriver($this->_glAdmDir, $this->gitExec, $this->dumper);
    }
    
    public function tearDown() {
        parent::tearDown();
        chdir($this->cwd);
    
        $GLOBALS['sys_https_host'] = $this->httpsHost;
        PermissionsManager::clearInstance();
    }
    
    public function assertEmptyGitStatus() {
        $cwd = getcwd();
        chdir($this->_glAdmDir);
        exec('git status --porcelain', $output, $ret_val);
        chdir($cwd);
        $this->assertEqual($output, array());
        $this->assertEqual($ret_val, 0);
    }
}
?>
