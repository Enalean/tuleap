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
use BackendSVNFileForSimlinkAlreadyExistsException;
use ForgeConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use ProjectManager;
use SVN_DAO;
use TestHelper;
use Tuleap\Config\ConfigurationVariables;
use Tuleap\GlobalLanguageMock;
use Tuleap\SVNCore\Cache\Parameters;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

final class BackendSVNTest extends TestIntegrationTestCase
{
    use TemporaryTestDirectory;
    use GlobalLanguageMock;

    private string $fake_revprop;
    private Parameters&MockObject $cache_parameters;
    private ProjectManager&MockObject $project_manager;
    private BackendSVN&MockObject $backend;

    protected function setUp(): void
    {
        parent::setUp();
        $tmp_dir            = $this->getTmpDir();
        $bin_dir            = __DIR__ . '/_fixtures';
        $this->fake_revprop = $bin_dir . '/post-revprop-change.php';

        ForgeConfig::set('svn_prefix', $tmp_dir . '/svnroot');
        ForgeConfig::set('tmp_dir', $tmp_dir . '/tmp');
        ForgeConfig::set(ConfigurationVariables::NAME, 'Tuleap test');
        ForgeConfig::set('svnadmin_cmd', '/usr/bin/svnadmin --config-dir ' . __DIR__ . '/_fixtures/.subversion');

        ForgeConfig::set('sys_project_backup_path', $tmp_dir . '/backup');
        ForgeConfig::set('svn_root_file', $this->getTmpDir() . '/codendi_svnroot.conf');
        ForgeConfig::set('sys_http_user', 'codendiadm');
        mkdir(ForgeConfig::get('svn_prefix') . '/toto/hooks', 0777, true);
        mkdir(ForgeConfig::get('tmp_dir'), 0777, true);
        mkdir(ForgeConfig::get('sys_project_backup_path'), 0777, true);
        ForgeConfig::set('codendi_bin_prefix', $bin_dir);

        ForgeConfig::set('sys_custom_dir', $tmp_dir);
        mkdir($tmp_dir . '/conf');

        $this->project_manager  = $this->createMock(ProjectManager::class);
        $this->cache_parameters = $this->createMock(Parameters::class);

        $this->backend = $this->createPartialMock(BackendSVN::class, [
            'getSvnDao',
            'getProjectManager',
            'getSVNCacheParameters',
            'chmod',
        ]);
    }

    protected function tearDown(): void
    {
        //clear the cache between each tests
        Backend::clearInstances();
        ProjectManager::clearInstance();
    }

    public function testConstructor(): void
    {
        self::assertNotNull(BackendSVN::instance());
    }

    public function testGenerateSVNApacheConf(): void
    {
        $svn_dao = $this->createMock(SVN_DAO::class);
        $svn_dao->method('searchSvnRepositories')
            ->willReturn(TestHelper::argListToDar([[
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
            ],
            ]));
        $this->backend->method('getSvnDao')->willReturn($svn_dao);
        $this->backend->method('getProjectManager')->willReturn($this->project_manager);
        $this->backend->method('getSVNCacheParameters')->willReturn($this->cache_parameters);

        self::assertTrue($this->backend->generateSVNApacheConf());
        $svnroots = file_get_contents(ForgeConfig::get('svn_root_file'));

        self::assertNotFalse($svnroots);
        self::assertStringContainsString("gpig2", $svnroots, "Project name not found in SVN root");
    }

    public function testSetSVNPrivacyPrivate(): void
    {
        $this->backend->expects(self::once())->method('chmod')->with(ForgeConfig::get('svn_prefix') . '/' . 'toto', 0770)->willReturn(true);
        $this->backend->method('getProjectManager')->willReturn($this->project_manager);
        $project = $this->createMock(Project::class);
        $project->method('getUnixNameMixedCase')->willReturn('toto');
        $project->method('getSVNRootPath')->willReturn(ForgeConfig::get('svn_prefix') . '/toto');
        self::assertTrue($this->backend->setSVNPrivacy($project, true));
    }

    public function testsetSVNPrivacyPublic(): void
    {
        $this->backend->expects(self::once())->method('chmod')->with(ForgeConfig::get('svn_prefix') . '/' . 'toto', 0775)->willReturn(true);
        $project = $this->createMock(Project::class);
        $project->method('getUnixNameMixedCase')->willReturn('toto');
        $project->method('getSVNRootPath')->willReturn(ForgeConfig::get('svn_prefix') . '/toto');
        self::assertTrue($this->backend->setSVNPrivacy($project, false));
    }

    public function testSetSVNPrivacyNoRepository(): void
    {
        $path_that_doesnt_exist = $this->getTmpDir() . '/' . bin2hex(random_bytes(32));

        $this->backend->expects(self::never())->method('chmod');

        $project = $this->createMock(Project::class);
        $project->method('getUnixNameMixedCase')->willReturn($path_that_doesnt_exist);
        $project->method('getSVNRootPath')->willReturn(ForgeConfig::get('svn_prefix') . '/' . $path_that_doesnt_exist);

        self::assertFalse($this->backend->setSVNPrivacy($project, true));
        self::assertFalse($this->backend->setSVNPrivacy($project, false));
    }

    public function testItThrowsAnExceptionIfFileForSymlinkAlreadyExists(): void
    {
        $backend = $this->createPartialMock(BackendSVN::class, ['log']);
        $path    = ForgeConfig::get('svn_prefix') . '/toto/hooks';
        touch($path . '/post-revprop-change');

        $project = $this->createMock(Project::class);
        $project->method('getUnixName')->willReturn('toto');
        $project->method('getSVNRootPath')->willReturn(ForgeConfig::get('svn_prefix') . '/toto');
        $backend->expects(self::once())->method('log');

        self::expectException(BackendSVNFileForSimlinkAlreadyExistsException::class);
        $backend->updateHooks(
            $project,
            ForgeConfig::get('svn_prefix') . '/toto',
            true,
            ForgeConfig::get('codendi_bin_prefix'),
            'svn_post_commit.php',
            "",
            "svn_pre_commit.php"
        );
    }

    public function testDoesntThrowAnExceptionIfTheHookIsALinkToOurImplementation(): void
    {
        $backend = $this->createPartialMock(BackendSVN::class, ['log']);
        $path    = ForgeConfig::get('svn_prefix') . '/toto/hooks';

        // Create link to fake post-revprop-change
        symlink($this->fake_revprop, $path . '/post-revprop-change');

        $project = $this->createMock(Project::class);
        $project->method('getUnixName')->willReturn('toto');
        $project->method('getSVNRootPath')->willReturn(ForgeConfig::get('svn_prefix') . '/toto');
        $backend->expects(self::never())->method('log');

        $backend->updateHooks(
            $project,
            ForgeConfig::get('svn_prefix') . '/toto',
            true,
            ForgeConfig::get('codendi_bin_prefix'),
            'svn_post_commit.php',
            "",
            "svn_pre_commit.php"
        );
    }
}
