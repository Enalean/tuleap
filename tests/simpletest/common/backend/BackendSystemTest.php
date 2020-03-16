<?php
/**
 * Copyright (c) Enalean, 2012 - 2018. All Rights Reserved.
 * Copyright (c) The Codendi Team, Xerox, 2009. All Rights Reserved.
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
 *
 *
 */


Mock::generate('UserManager');
Mock::generate('PFUser');
Mock::generate('ProjectManager');
Mock::generate('Project');
Mock::generate('WikiAttachment');
Mock::generatePartial('BackendSystem', 'BackendTestVersion', array('getUserManager',
                                                             'getProjectManager',
                                                             'chown',
                                                             'chgrp',
                                                             'chmod',
                                                             'getFRSFileFactory',
                                                             'getWikiAttachment'
                                                             ));


class BackendSystemTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();
        $GLOBALS['codendi_shell_skel']        = dirname(__FILE__) . '/_fixtures/etc/skel_codendi';
        $GLOBALS['tmp_dir']                   = $this->getTmpDir() . '/var/tmp';
        $GLOBALS['ftp_frs_dir_prefix']        = $this->getTmpDir() . '/var/lib/codendi/ftp/codendi';
        $GLOBALS['sys_file_deletion_delay']   = 5;
        $GLOBALS['codendi_log']               = $GLOBALS['tmp_dir'];
        ForgeConfig::store();
        ForgeConfig::set('sys_incdir', $GLOBALS['tmp_dir']);
        ForgeConfig::set('sys_custom_incdir', $GLOBALS['tmp_dir']);
        ForgeConfig::set('sys_project_backup_path', $GLOBALS['tmp_dir']);
        ForgeConfig::set('homedir_prefix', $this->getTmpDir() . '/home/users');
        ForgeConfig::set('grpdir_prefix', $this->getTmpDir() . '/home/groups');
        ForgeConfig::set('ftp_anon_dir_prefix', $this->getTmpDir() . '/var/lib/codendi/ftp/pub');
        ForgeConfig::set('ftp_frs_dir_prefix', $GLOBALS['ftp_frs_dir_prefix']);

        mkdir(ForgeConfig::get('homedir_prefix'), 0770, true);
        mkdir(ForgeConfig::get('grpdir_prefix'), 0770, true);
        mkdir($GLOBALS['tmp_dir'], 0770, true);
        mkdir($GLOBALS['ftp_frs_dir_prefix'], 0770, true);
        mkdir(ForgeConfig::get('ftp_anon_dir_prefix'), 0770, true);
    }


    public function tearDown()
    {
        Backend::clearInstances();
        ForgeConfig::restore();

        parent::tearDown();
    }

    public function testConstructor()
    {
        $backend = BackendSystem::instance();
    }


    public function testCreateUserHome()
    {
        // We use codendiadm uid/gid to avoid chown warnings (because test is not run as root)
        $user = new PFUser([
            'language_id' => 'en',
            'user_name' => 'codendiadm',
        ]);

        $backend = new BackendTestVersion();

        $this->assertEqual($backend->createUserHome($user), true);
        $this->assertTrue(is_dir(ForgeConfig::get('homedir_prefix') . "/codendiadm"), "Home dir should be created");

        $this->assertTrue(is_file(ForgeConfig::get('homedir_prefix') . "/codendiadm/.profile"), "User files from /etc/codendi_skel should be created");

        // Cleanup
        $backend->recurseDeleteInDir(ForgeConfig::get('homedir_prefix') . "/codendiadm");
        rmdir(ForgeConfig::get('homedir_prefix') . "/codendiadm");
    }

    public function testCreateProjectHome()
    {
        $project = new MockProject($this);
        $project->setReturnValue('getUnixNameMixedCase', 'TestPrj');
        $project->setReturnValue('getUnixName', 'testprj', array(true));

        $pm = new MockProjectManager();
        $pm->setReturnReference('getProject', $project, array(142));

        $backend = new BackendTestVersion($this);
        $backend->setReturnValue('getProjectManager', $pm);

        $projdir = ForgeConfig::get('grpdir_prefix') . "/TestPrj";
        $ftpdir = ForgeConfig::get('ftp_anon_dir_prefix') . "/TestPrj";
        $frsdir = $GLOBALS['ftp_frs_dir_prefix'] . "/TestPrj";

        $this->assertEqual($backend->createProjectHome(142), true);
        $this->assertTrue(is_dir($projdir), "Project Home should be created");
        $this->assertTrue(is_dir($ftpdir), "Ftp dir should be created");
        $this->assertTrue(is_dir($frsdir), "Frs dir should be created");

        // Check that a wrong project id does not raise an error
        $this->assertEqual($backend->createProjectHome(99999), false);

        // Cleanup
        $backend->recurseDeleteInDir($projdir);
        unlink(ForgeConfig::get('grpdir_prefix') . "/testprj");
        rmdir($projdir);

        $backend->recurseDeleteInDir($ftpdir);
        rmdir($ftpdir);

        $backend->recurseDeleteInDir($frsdir);
        rmdir($frsdir);
    }

    public function testArchiveUserHome()
    {
        // We use codendiadm uid/gid to avoid chown warnings (because test is not run as root)
        $user = new PFUser([
            'language_id' => 'en',
            'user_name' => 'codendiadm',
        ]);

        $backend = new BackendTestVersion();

        $backend->createUserHome($user);
        $this->assertTrue(is_dir(ForgeConfig::get('homedir_prefix') . "/codendiadm"), "Home dir should be created");

        // Run test
        $um = new MockUserManager();
        $um->setReturnReference('getUserById', $user, array(104));

        $backend->setReturnValue('getUserManager', $um);

        $this->assertEqual($backend->archiveUserHome(104), true);
        $this->assertFalse(is_dir(ForgeConfig::get('homedir_prefix') . "/codendiadm"), "Home dir should be deleted");
        $this->assertTrue(is_file(ForgeConfig::get('sys_project_backup_path') . "/codendiadm.tgz"), "Archive should be created");

        // Cleanup
        unlink(ForgeConfig::get('sys_project_backup_path') . "/codendiadm.tgz");
    }

    public function testArchiveProjectHome()
    {
        $project = new MockProject($this);
        $project->setReturnValue('getUnixName', 'TestProj', array(false));
        $project->setReturnValue('getUnixName', 'testproj', array(true));

        $pm = new MockProjectManager();
        $pm->setReturnReference('getProject', $project, array(142));
        //$pm->setReturnReference('getProject', $project);

        $backend = new BackendTestVersion($this);
        $backend->setReturnValue('getProjectManager', $pm);

        $projdir = ForgeConfig::get('grpdir_prefix') . "/TestProj";
        $lcprojlnk = ForgeConfig::get('grpdir_prefix') . "/testproj";

        // Setup test data
        mkdir($projdir);
        touch($projdir . "/testfile.txt");
        symlink($projdir, $lcprojlnk);

        //$this->assertTrue(is_dir($projdir),"Project dir should be created");

        $this->assertEqual($backend->archiveProjectHome(142), true);
        $this->assertFalse(is_dir($projdir), "Project dir should be deleted");
        $this->assertFalse(is_link($lcprojlnk), "Project link should be deleted");
        $this->assertTrue(is_file(ForgeConfig::get('sys_project_backup_path') . "/TestProj.tgz"), "Archive should be created");

        // Check that a wrong project id does not raise an error
        $this->assertEqual($backend->archiveProjectHome(99999), false);

        // Cleanup
        unlink(ForgeConfig::get('sys_project_backup_path') . "/TestProj.tgz");
    }

    public function testRenameProjectHomeDirectory()
    {
        $project = new MockProject($this);
        $project->setReturnValue('getUnixName', 'TestProject', array(false));
        $project->setReturnValue('getUnixNameMixedCase', 'TestProject');
        $project->setReturnValue('getUnixName', 'testproject', array(true));

        $pm = new MockProjectManager();
        $pm->setReturnReference('getProject', $project, array(142));

        $backend = new BackendTestVersion($this);
        $backend->setReturnValue('getProjectManager', $pm);

        $backend->createProjectHome(142);

        $this->assertEqual($backend->renameProjectHomeDirectory($project, "FooBar"), true);

        $this->assertFalse(file_exists(ForgeConfig::get('grpdir_prefix') . "/TestProject"), "Old project home should no longer exists");
        $this->assertTrue(is_dir(ForgeConfig::get('grpdir_prefix') . "/FooBar"), "Project home should be renamed");

        $this->assertFalse(file_exists(ForgeConfig::get('grpdir_prefix') . "/testproject"), "Old project home lowercase version should no longer exists");
        $this->assertTrue(is_link(ForgeConfig::get('grpdir_prefix') . "/foobar"), "Project home lowercase version should be renamed");
        $this->assertEqual(readlink(ForgeConfig::get('grpdir_prefix') . "/foobar"), ForgeConfig::get('grpdir_prefix') . "/FooBar", "Project home lowercase version should be link to the uppercase version");

        // Cleanup
        $backend->recurseDeleteInDir(ForgeConfig::get('grpdir_prefix') . "/FooBar");
        unlink(ForgeConfig::get('grpdir_prefix') . "/foobar");
        rmdir(ForgeConfig::get('grpdir_prefix') . "/FooBar");

        rmdir(ForgeConfig::get('ftp_anon_dir_prefix') . "/TestProject");
        rmdir($GLOBALS['ftp_frs_dir_prefix'] . "/TestProject");
    }

    /**
     * Special case when the project rename is just about changing case
     * TestProject -> testproject
     */
    public function testRenameProjectHomeDirectoryToLowerCase()
    {
        $project = new MockProject($this);
        $project->setReturnValue('getUnixName', 'TestProject', array(false));
        $project->setReturnValue('getUnixNameMixedCase', 'TestProject');
        $project->setReturnValue('getUnixName', 'testproject', array(true));

        $pm = new MockProjectManager();
        $pm->setReturnReference('getProject', $project, array(142));

        $backend = new BackendTestVersion($this);
        $backend->setReturnValue('getProjectManager', $pm);

        $backend->createProjectHome(142);

        $this->assertEqual($backend->renameProjectHomeDirectory($project, "testproject"), true);

        $this->assertFalse(file_exists(ForgeConfig::get('grpdir_prefix') . "/TestProject"), "Old project home should no longer exists");
        $this->assertTrue(is_dir(ForgeConfig::get('grpdir_prefix') . "/testproject"), "Project home should be renamed");

        // Cleanup
        $backend->recurseDeleteInDir(ForgeConfig::get('grpdir_prefix') . "/testproject");
        rmdir(ForgeConfig::get('grpdir_prefix') . "/testproject");

        rmdir(ForgeConfig::get('ftp_anon_dir_prefix') . "/TestProject");
        rmdir($GLOBALS['ftp_frs_dir_prefix'] . "/TestProject");
    }

   /**
     * Special case when the project rename is just about changing case
     * testproject -> TestProject
     */
    public function testRenameProjectHomeDirectoryToUpperCase()
    {
        $project = new MockProject($this);
        $project->setReturnValue('getUnixName', 'testproject', array(false));
        $project->setReturnValue('getUnixNameMixedCase', 'testproject');
        $project->setReturnValue('getUnixName', 'testproject', array(true));

        $pm = new MockProjectManager();
        $pm->setReturnReference('getProject', $project, array(142));

        $backend = new BackendTestVersion($this);
        $backend->setReturnValue('getProjectManager', $pm);

        $backend->createProjectHome(142);

        $this->assertEqual($backend->renameProjectHomeDirectory($project, "TestProject"), true);

        // Not test possible with is_dir because is_dir resolve the link.
        // Testing lower case as a link is enough (see below).
        //$this->assertFalse(is_dir(ForgeConfig::get('grpdir_prefix')."/testproject"), "Old project home should no longer exists as directory (it's a link now)");
        $this->assertTrue(is_dir(ForgeConfig::get('grpdir_prefix') . "/TestProject"), "Project home should be renamed");
        $this->assertEqual(readlink(ForgeConfig::get('grpdir_prefix') . '/testproject'), ForgeConfig::get('grpdir_prefix') . '/TestProject', "The lower case of project should be a link");

        // Cleanup
        $backend->recurseDeleteInDir(ForgeConfig::get('grpdir_prefix') . "/TestProject");
        rmdir(ForgeConfig::get('grpdir_prefix') . "/TestProject");
        unlink(ForgeConfig::get('grpdir_prefix') . '/testproject');

        rmdir(ForgeConfig::get('ftp_anon_dir_prefix') . "/testproject");
        rmdir($GLOBALS['ftp_frs_dir_prefix'] . "/testproject");
    }

    /**
     * testproject -> projecttest
     */
    public function testRenameProjectHomeDirectoryLowerCase()
    {
        $project = new MockProject($this);
        $project->setReturnValue('getUnixName', 'testproject', array(false));
        $project->setReturnValue('getUnixNameMixedCase', 'testproject');
        $project->setReturnValue('getUnixName', 'testproject', array(true));

        $pm = new MockProjectManager();
        $pm->setReturnReference('getProject', $project, array(142));

        $backend = new BackendTestVersion($this);
        $backend->setReturnValue('getProjectManager', $pm);

        $backend->createProjectHome(142);

        $this->assertEqual($backend->renameProjectHomeDirectory($project, "projecttest"), true);

        $this->assertFalse(file_exists(ForgeConfig::get('grpdir_prefix') . "/testproject"), "Old project home should no longer exists");
        $this->assertTrue(is_dir(ForgeConfig::get('grpdir_prefix') . "/projecttest"), "Project home should be renamed");

        // Cleanup
        $backend->recurseDeleteInDir(ForgeConfig::get('grpdir_prefix') . "/projecttest");
        rmdir(ForgeConfig::get('grpdir_prefix') . "/projecttest");

        rmdir(ForgeConfig::get('ftp_anon_dir_prefix') . "/testproject");
        rmdir($GLOBALS['ftp_frs_dir_prefix'] . "/testproject");
    }

    public function testIsProjectNameAvailableWithExistingFileInProjectHome()
    {
        touch(ForgeConfig::get('grpdir_prefix') . "/testproject");
        $backend = new BackendTestVersion($this);
        $this->assertFalse($backend->isProjectNameAvailable('testproject'), 'A file with the same name exists in home/groups/');
        unlink(ForgeConfig::get('grpdir_prefix') . "/testproject");
    }

    public function testIsProjectNameAvailableWithExistingFileInProjectHomeWithMixedCase()
    {
        touch(ForgeConfig::get('grpdir_prefix') . "/testproject");
        $backend = new BackendTestVersion($this);
        $this->assertFalse($backend->isProjectNameAvailable('TestProject'), 'A file with the same name in lowercase exists in home/groups/');
        unlink(ForgeConfig::get('grpdir_prefix') . "/testproject");
    }

    public function testIsProjectNameAvailableWithExistingFileInFRS()
    {
        touch($GLOBALS['ftp_frs_dir_prefix'] . "/testproject");
        $backend = new BackendTestVersion($this);
        $this->assertFalse($backend->isProjectNameAvailable('testproject'), 'A file with the same name exists in var/lib/codendi/ftp/codendi');
        unlink($GLOBALS['ftp_frs_dir_prefix'] . "/testproject");
    }

    public function testIsProjectNameAvailableWithExistingFileInAnnoFtp()
    {
        touch(ForgeConfig::get('ftp_anon_dir_prefix') . "/testproject");
        $backend = new BackendTestVersion($this);
        $this->assertFalse($backend->isProjectNameAvailable('testproject'), 'A file with the same name exists in var/lib/codendi/ftp/pub');
        unlink(ForgeConfig::get('ftp_anon_dir_prefix') . "/testproject");
    }

    public function testRenameUserHomeDirectory()
    {
        // We use codendiadm uid/gid to avoid chown warnings (because test is not run as root)
        $user = new PFUser([
            'language_id' => 'en',
            'user_name' => 'codendiadm',
        ]);

        $backend = new BackendTestVersion($this);

        $backend->createUserHome($user);
        $this->assertEqual($backend->renameUserHomeDirectory($user, 'toto'), true);
        $this->assertTrue(is_dir(ForgeConfig::get('homedir_prefix') . "/toto"), "Home dir should be created");

        $this->assertFalse(is_dir(ForgeConfig::get('homedir_prefix') . "/codendiadm"), "Home dir should no more exists");

        // Cleanup
        $backend->recurseDeleteInDir(ForgeConfig::get('homedir_prefix') . "/toto");
        rmdir(ForgeConfig::get('homedir_prefix') . "/toto");
    }

    public function testCleanupFrs()
    {
        $backend = new BackendTestVersion($this);

        $daysBefore     = $_SERVER['REQUEST_TIME'] - (24 * 3600 * 5);

        $ff = \Mockery::mock(FRSFileFactory::class);
        $ff->shouldReceive('moveFiles')->andReturn(true);
        //$ff->expectOnce('moveFiles', array($daysBefore, $backend));

        $wiki = new MockWikiAttachment($this);
        $wiki->setReturnValue('purgeAttachments', true);

        $backend->setReturnValue('getFRSFileFactory', $ff);
        $backend->setReturnValue('getWikiAttachment', $wiki);

        $this->assertTrue($backend->cleanupFRS());
    }
}
