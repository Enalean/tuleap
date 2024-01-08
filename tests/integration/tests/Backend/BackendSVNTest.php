<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Backend;

use Backend;
use BackendSVN;
use ForgeConfig;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use ProjectManager;

final class BackendSVNTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use \Tuleap\TemporaryTestDirectory;
    use \Tuleap\GlobalLanguageMock;

    // Cannot use ForgeConfigSandbox here, you will generate an instance of CodendiDataAccess with invalid DB creds.
    // It will break in other tests as soon as you try to access something with CodendiDataAccess
    // use ForgeConfigSandbox;

    private $tmp_dir;
    private $bin_dir;
    private $fake_revprop;
    private $cache_parameters;
    private $initial_sys_project_backup_path;
    private $initial_svn_root_file;
    private $initial_http_user;
    private $initial_codendi_bin_prefix;
    private $initial_svn_prefix;
    private $initial_tmp_dir;
    private $initial_sys_name;
    private $initial_svnadmin_cmd;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ProjectManager
     */
    private $project_manager;
    private \BackendSVN|\Mockery\MockInterface $backend;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmp_dir      = $this->getTmpDir();
        $this->bin_dir      = __DIR__ . '/_fixtures';
        $this->fake_revprop = $this->bin_dir . '/post-revprop-change.php';

        $this->initial_svn_prefix = ForgeConfig::get('svn_prefix');
        ForgeConfig::set('svn_prefix', $this->tmp_dir . '/svnroot');
        $this->initial_tmp_dir = ForgeConfig::get('tmp_dir');
        ForgeConfig::set('tmp_dir', $this->tmp_dir . '/tmp');
        $this->initial_sys_name = ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME);
        ForgeConfig::set(\Tuleap\Config\ConfigurationVariables::NAME, 'Tuleap test');
        $this->initial_svnadmin_cmd = ForgeConfig::get('svnadmin_cmd');
        ForgeConfig::set('svnadmin_cmd', '/usr/bin/svnadmin --config-dir ' . __DIR__ . '/_fixtures/.subversion');

        $this->initial_sys_project_backup_path = ForgeConfig::get('sys_project_backup_path');
        ForgeConfig::set('sys_project_backup_path', $this->tmp_dir . '/backup');
        $this->initial_svn_root_file = ForgeConfig::get('svn_root_file');
        ForgeConfig::set('svn_root_file', $this->getTmpDir() . '/codendi_svnroot.conf');
        $this->initial_http_user = ForgeConfig::get('sys_http_user');
        ForgeConfig::set('sys_http_user', 'codendiadm');
        mkdir(ForgeConfig::get('svn_prefix') . '/toto/hooks', 0777, true);
        mkdir(ForgeConfig::get('tmp_dir'), 0777, true);
        mkdir(ForgeConfig::get('sys_project_backup_path'), 0777, true);
        $this->initial_codendi_bin_prefix = ForgeConfig::get('codendi_bin_prefix');
        ForgeConfig::set('codendi_bin_prefix', $this->bin_dir);

        ForgeConfig::set('sys_custom_dir', $this->tmp_dir);
        mkdir($this->tmp_dir . '/conf');

        $this->project_manager  = \Mockery::spy(\ProjectManager::class);
        $this->cache_parameters = \Mockery::spy(\Tuleap\SVNCore\Cache\Parameters::class);

        $this->backend = \Mockery::mock(\BackendSVN::class)->makePartial()->shouldAllowMockingProtectedMethods();
    }

    protected function tearDown(): void
    {
        //clear the cache between each tests
        Backend::clearInstances();
        ProjectManager::clearInstance();
        ForgeConfig::set('sys_project_backup_path', $this->initial_sys_project_backup_path);
        ForgeConfig::set('svn_root_file', $this->initial_svn_root_file);
        ForgeConfig::set('sys_http_user', $this->initial_http_user);
        ForgeConfig::set('codendi_bin_prefix', $this->initial_codendi_bin_prefix);
        ForgeConfig::set('svn_prefix', $this->initial_svn_prefix);
        ForgeConfig::set('tmp_dir', $this->initial_tmp_dir);
        ForgeConfig::set(\Tuleap\Config\ConfigurationVariables::NAME, $this->initial_sys_name);
        ForgeConfig::set('svnadmin_cmd', $this->initial_svnadmin_cmd);
    }

    public function testConstructor(): void
    {
        $this->assertNotNull(BackendSVN::instance());
    }

    public function testArchiveProjectSVN(): void
    {
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getUnixNameMixedCase')->andReturns('TestProj');
        $project->shouldReceive('getSVNRootPath')->andReturns(ForgeConfig::get('svn_prefix') . '/TestProj');

        $pm = \Mockery::spy(\ProjectManager::class);
        $pm->shouldReceive('getProject')->with(142)->andReturns($project);

        $this->backend->shouldReceive('getProjectManager')->andReturns($pm);

        $projdir = ForgeConfig::get('svn_prefix') . "/TestProj";

        // Setup test data
        mkdir($projdir);
        mkdir($projdir . "/db");

        $this->assertEquals($this->backend->archiveProjectSVN(142), true);
        $this->assertFalse(is_dir($projdir), "Project SVN repository should be deleted");
        $this->assertTrue(is_file(ForgeConfig::get('sys_project_backup_path') . "/TestProj-svn.tgz"), "SVN Archive should be created");

        // Check that a wrong project id does not raise an error
        $this->assertEquals($this->backend->archiveProjectSVN(99999), false);
    }

    public function testGenerateSVNApacheConf(): void
    {
        $svn_dao = \Mockery::spy(\SVN_DAO::class)->shouldReceive('searchSvnRepositories')->andReturns(\TestHelper::arrayToDar([
            "group_id"        => "101",
            "group_name"      => "Guinea Pig",
            "unix_group_name" => "gpig",
        ], [
            "group_id"        => "102",
            "group_name"      => "Guinea Pig is \"back\"",
            "unix_group_name" => "gpig2",
        ], [
            "group_id"        => "103",
            "group_name"      => "Guinea Pig is 'angry'",
            "unix_group_name" => "gpig3",
        ]))->getMock();
        $this->backend->shouldReceive('getSvnDao')->andReturns($svn_dao);
        $this->backend->shouldReceive('getProjectManager')->andReturns($this->project_manager);
        $this->backend->shouldReceive('getSVNCacheParameters')->andReturns($this->cache_parameters);

        $this->assertTrue($this->backend->generateSVNApacheConf());
        $svnroots = file_get_contents(ForgeConfig::get('svn_root_file'));

        $this->assertNotFalse($svnroots);
        $this->assertStringContainsString("gpig2", $svnroots, "Project name not found in SVN root");
    }

    public function testSetSVNPrivacyPrivate(): void
    {
        $this->backend->shouldReceive('chmod')->with(ForgeConfig::get('svn_prefix') . '/' . 'toto', 0770)->once()->andReturns(true);
        $this->backend->shouldReceive('getProjectManager')->andReturns($this->project_manager);
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getUnixNameMixedCase')->andReturns('toto');
        $project->shouldReceive('getSVNRootPath')->andReturns(ForgeConfig::get('svn_prefix') . '/toto');
        $this->assertTrue($this->backend->setSVNPrivacy($project, true));
    }

    public function testsetSVNPrivacyPublic(): void
    {
        $this->backend->shouldReceive('chmod')->with(ForgeConfig::get('svn_prefix') . '/' . 'toto', 0775)->once()->andReturns(true);
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getUnixNameMixedCase')->andReturns('toto');
        $project->shouldReceive('getSVNRootPath')->andReturns(ForgeConfig::get('svn_prefix') . '/toto');
        $this->assertTrue($this->backend->setSVNPrivacy($project, false));
    }

    public function testSetSVNPrivacyNoRepository(): void
    {
        $path_that_doesnt_exist = $this->getTmpDir() . '/' . bin2hex(random_bytes(32));

        $this->backend->shouldReceive('chmod')->never();

        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getUnixNameMixedCase')->andReturns($path_that_doesnt_exist);
        $project->shouldReceive('getSVNRootPath')->andReturns(ForgeConfig::get('svn_prefix') . '/' . $path_that_doesnt_exist);

        $this->assertFalse($this->backend->setSVNPrivacy($project, true));
        $this->assertFalse($this->backend->setSVNPrivacy($project, false));
    }

    public function testItThrowsAnExceptionIfFileForSymlinkAlreadyExists(): void
    {
        $backend = \Mockery::mock(\BackendSVN::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $path    = ForgeConfig::get('svn_prefix') . '/toto/hooks';
        touch($path . '/post-revprop-change');

        $project = \Mockery::spy(\Project::class)->shouldReceive('getUnixName')->andReturns('toto')->getMock();
        $project->shouldReceive('getSVNRootPath')->andReturns(ForgeConfig::get('svn_prefix') . '/toto');
        $backend->shouldReceive('log')->once();

        $this->expectException(\BackendSVNFileForSimlinkAlreadyExistsException::class);
        $backend->updateHooks(
            $project,
            ForgeConfig::get('svn_prefix') . '/toto',
            true,
            ForgeConfig::get('codendi_bin_prefix'),
            'commit-email.pl',
            "",
            "codendi_svn_pre_commit.php"
        );
    }

    public function testDoesntThrowAnExceptionIfTheHookIsALinkToOurImplementation(): void
    {
        $backend = \Mockery::mock(\BackendSVN::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $path    = ForgeConfig::get('svn_prefix') . '/toto/hooks';

        // Create link to fake post-revprop-change
        symlink($this->fake_revprop, $path . '/post-revprop-change');

        $project = \Mockery::spy(\Project::class)->shouldReceive('getUnixName')->andReturns('toto')->getMock();
        $project->shouldReceive('getSVNRootPath')->andReturns(ForgeConfig::get('svn_prefix') . '/toto');
        $backend->shouldReceive('log')->never();

        $backend->updateHooks(
            $project,
            ForgeConfig::get('svn_prefix') . '/toto',
            true,
            ForgeConfig::get('codendi_bin_prefix'),
            'commit-email.pl',
            "",
            "codendi_svn_pre_commit.php"
        );
    }
}
