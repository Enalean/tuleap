<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\Git\Gitolite;

use EventManager;
use Git_Driver_Gerrit_ProjectCreatorStatus;
use Git_Gitolite_ConfigPermissionsSerializer;
use Git_Gitolite_ProjectSerializer;
use Git_GitRepositoryUrlManager;
use GitRepository;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PermissionsManager;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Git\BigObjectAuthorization\BigObjectAuthorizationManager;

require_once __DIR__ . '/../../bootstrap.php';

class ProjectSerializerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    /**
     * @var Git_Gitolite_ProjectSerializer
     */
    private $project_serializer;

    private $repository_factory;
    private $url_manager;
    private $gitolite_permissions_serializer;
    private $logger;
    private $fix_dir;
    private $permissions_manager;
    private $gerrit_project_status;
    private $big_object_authorization_manager;

    public function setUp(): void
    {
        parent::setUp();

        $this->fix_dir = __DIR__ . '/_fixtures';

        \ForgeConfig::set('sys_default_domain', 'localhost');

        PermissionsManager::setInstance(Mockery::spy(\PermissionsManager::class));
        $this->permissions_manager = PermissionsManager::instance();

        $this->repository_factory = Mockery::spy(\GitRepositoryFactory::class);

        $git_plugin = Mockery::spy(\GitPlugin::class);
        $git_plugin->shouldReceive('areFriendlyUrlsActivated')->andReturn(false);

        $this->url_manager = new Git_GitRepositoryUrlManager($git_plugin);

        $this->gerrit_project_status = Mockery::spy(\Git_Driver_Gerrit_ProjectCreatorStatus::class);

        $this->gitolite_permissions_serializer = new Git_Gitolite_ConfigPermissionsSerializer(
            $this->gerrit_project_status,
            'whatever',
            Mockery::spy(\Tuleap\Git\Permissions\FineGrainedRetriever::class),
            Mockery::spy(\Tuleap\Git\Permissions\FineGrainedPermissionFactory::class),
            Mockery::spy(\Tuleap\Git\Permissions\RegexpFineGrainedRetriever::class),
            Mockery::spy(EventManager::class)
        );

        $this->logger = Mockery::spy(\Psr\Log\LoggerInterface::class);

        $this->big_object_authorization_manager = Mockery::mock(BigObjectAuthorizationManager::class);

        $this->project_serializer = new Git_Gitolite_ProjectSerializer(
            $this->logger,
            $this->repository_factory,
            $this->gitolite_permissions_serializer,
            $this->url_manager,
            $this->big_object_authorization_manager,
        );
    }

    public function tearDown(): void
    {
        PermissionsManager::clearInstance();

        parent::tearDown();
    }

    public function testGetMailHookConfig()
    {
        $prj = Mockery::spy(\Project::class);
        $prj->shouldReceive('getUnixName')->andReturn('project1');
        $prj->shouldReceive('getID')->andReturn(101);

        // ShowRev
        $repo = new GitRepository();
        $repo->setId(5);
        $repo->setProject($prj);
        $repo->setName('test_default');
        $this->assertSame(
            file_get_contents($this->fix_dir . '/gitolite-mail-config/mailhook-rev.txt'),
            $this->project_serializer->fetchMailHookConfig($prj, $repo)
        );

        // ShowRev + Mailprefix
        $repo = new GitRepository();
        $repo->setId(5);
        $repo->setProject($prj);
        $repo->setName('test_default');
        $repo->setMailPrefix('[KOIN] ');
        $this->assertSame(
            file_get_contents($this->fix_dir . '/gitolite-mail-config/mailhook-rev-mail-prefix.txt'),
            $this->project_serializer->fetchMailHookConfig($prj, $repo)
        );

        // ShowRev + Mailprefix
        $repo = new GitRepository();
        $repo->setId(5);
        $repo->setProject($prj);
        $repo->setName('test_default');
        $repo->setMailPrefix('["\_o<"] \t');
        $this->assertSame(
            file_get_contents($this->fix_dir . '/gitolite-mail-config/mailhook-rev-mail-prefix-quote.txt'),
            $this->project_serializer->fetchMailHookConfig($prj, $repo)
        );
    }

    // The project has 2 repositories nb 4 & 5.
    // 4 has defaults
    // 5 has pimped perms
    public function testDumpProjectRepoPermissions()
    {
        $prj = Mockery::spy(\Project::class);
        $prj->shouldReceive('getUnixName')->andReturn('project1');
        $prj->shouldReceive('getID')->andReturn(404);

        $repo = new GitRepository();
        $repo->setId(4);
        $repo->setProject($prj);
        $repo->setName('test_default');
        $repo->setMailPrefix('[SCM]');
        $repo->setNamespace('');

        $repo2 = new GitRepository();
        $repo2->setId(5);
        $repo2->setProject($prj);
        $repo2->setName('test_pimped');
        $repo2->setMailPrefix('[KOIN] ');
        $repo2->setNamespace('');

        // List all repo
        $this->repository_factory->shouldReceive('getAllRepositoriesOfProject')
            ->with($prj)
            ->once()
            ->andReturn([$repo, $repo2]);

        // Repo 4 (test_default): R = registered_users | W = project_members | W+ = none
        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')->with($prj, 4, 'PLUGIN_GIT_READ')->andReturns(['2']);
        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')->with($prj, 4, 'PLUGIN_GIT_WRITE')->andReturns(['3']);
        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')->with($prj, 4, 'PLUGIN_GIT_WPLUS')->andReturns([]);

        // Repo 5 (test_pimped): R = project_members | W = project_admin | W+ = user groups 101
        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')->with($prj, 5, 'PLUGIN_GIT_READ')->andReturns(['3']);
        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')->with($prj, 5, 'PLUGIN_GIT_WRITE')->andReturns(['4']);
        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')->with($prj, 5, 'PLUGIN_GIT_WPLUS')->andReturns(['125']);

        $this->big_object_authorization_manager->shouldReceive('getAuthorizedProjects')->andReturn([]);

        // Ensure file is correct
        $result   = $this->project_serializer->dumpProjectRepoConf($prj);
        $expected = file_get_contents($this->fix_dir . '/perms/project1-full.conf');

        $this->assertSame($expected, $result);
    }

    public function testRewindAccessRightsToGerritUserWhenRepoIsMigratedToGerrit()
    {
        $prj = Mockery::spy(\Project::class);
        $prj->shouldReceive('getUnixName')->andReturns('project1');
        $prj->shouldReceive('getID')->andReturns(404);

        $repo = new GitRepository();
        $repo->setId(4);
        $repo->setProject($prj);
        $repo->setName('before_migration_to_gerrit');
        $repo->setNamespace('');

        $repo2 = new GitRepository();
        $repo2->setId(5);
        $repo2->setProject($prj);
        $repo2->setName('after_migration_to_gerrit');
        $repo2->setNamespace('');
        $repo2->setRemoteServerId(1);

        // List all repo
        $this->repository_factory->shouldReceive('getAllRepositoriesOfProject')
            ->with($prj)
            ->once()
            ->andReturn([$repo, $repo2]);

        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')->with($prj, 4, 'PLUGIN_GIT_READ')->andReturns(['2']);
        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')->with($prj, 4, 'PLUGIN_GIT_WRITE')->andReturns(['3']);
        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')->with($prj, 4, 'PLUGIN_GIT_WPLUS')->andReturns(['125']);

        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')->with($prj, 5, 'PLUGIN_GIT_READ')->andReturns(['2']);
        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')->with($prj, 5, 'PLUGIN_GIT_WRITE')->andReturns(['3']);
        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')->with($prj, 5, 'PLUGIN_GIT_WPLUS')->andReturns(['125']);

        $this->gerrit_project_status->shouldReceive('getStatus')->andReturn(Git_Driver_Gerrit_ProjectCreatorStatus::DONE);

        $this->big_object_authorization_manager->shouldReceive('getAuthorizedProjects')->andReturn([]);

        // Ensure file is correct
        $result   = $this->project_serializer->dumpProjectRepoConf($prj);
        $expected = file_get_contents($this->fix_dir . '/perms/migrated_to_gerrit.conf');

        $this->assertSame($expected, $result);
    }

    public function testDumpSuspendedProjectRepoPermissions()
    {
        $project = Mockery::spy(\Project::class);
        $project->shouldReceive('getUnixName')->andReturn('project1');
        $project->shouldReceive('getID')->andReturn(404);

        $repo = new GitRepository();
        $repo->setId(4);
        $repo->setProject($project);
        $repo->setName('test_default');
        $repo->setMailPrefix('[SCM]');
        $repo->setNamespace('');

        $repo2 = new GitRepository();
        $repo2->setId(5);
        $repo2->setProject($project);
        $repo2->setName('test_pimped');
        $repo2->setMailPrefix('[KOIN] ');
        $repo2->setNamespace('');

        // List all repo
        $this->repository_factory->shouldReceive('getAllRepositoriesOfProject')
            ->with($project)
            ->once()
            ->andReturn([$repo, $repo2]);

        // Ensure file is correct
        $result   = $this->project_serializer->dumpSuspendedProjectRepositoriesConfiguration($project);
        $expected = <<<EOS
repo project1/test_default
 - refs/.*$ = @all

repo project1/test_pimped
 - refs/.*$ = @all


EOS;

        $this->assertSame($expected, $result);
    }

    public function testRepoFullNameConcatsUnixProjectNameNamespaceAndName()
    {
        $unix_name = 'project1';

        $repo = $this->givenARepositoryWithNameAndNamespace('repo', 'toto');
        $this->assertSame('project1/toto/repo', $this->project_serializer->repoFullName($repo, $unix_name));

        $repo = $this->givenARepositoryWithNameAndNamespace('repo', '');
        $this->assertSame('project1/repo', $this->project_serializer->repoFullName($repo, $unix_name));
    }

    /**
     * @return GitRepository
     */
    private function givenARepositoryWithNameAndNamespace($name, $namespace)
    {
        $repo = new GitRepository();
        $repo->setName($name);
        $repo->setNamespace($namespace);
        return $repo;
    }

    public function testDoNotWriteBigObjectRuleIfProjectIsAuthorized()
    {
        $prj = Mockery::spy(\Project::class);
        $prj->shouldReceive('getUnixName')->andReturn('project1');
        $prj->shouldReceive('getID')->andReturn(404);

        $repo = new GitRepository();
        $repo->setId(4);
        $repo->setProject($prj);
        $repo->setName('test_default');
        $repo->setMailPrefix('[SCM]');
        $repo->setNamespace('');

        $repo2 = new GitRepository();
        $repo2->setId(5);
        $repo2->setProject($prj);
        $repo2->setName('test_pimped');
        $repo2->setMailPrefix('[KOIN] ');
        $repo2->setNamespace('');

        // List all repo
        $this->repository_factory->shouldReceive('getAllRepositoriesOfProject')
            ->with($prj)
            ->once()
            ->andReturn([$repo, $repo2]);

        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')->with($prj, 4, 'PLUGIN_GIT_READ')->andReturns([]);
        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')->with($prj, 4, 'PLUGIN_GIT_WRITE')->andReturns([]);
        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')->with($prj, 4, 'PLUGIN_GIT_WPLUS')->andReturns([]);

        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')->with($prj, 5, 'PLUGIN_GIT_READ')->andReturns([]);
        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')->with($prj, 5, 'PLUGIN_GIT_WRITE')->andReturns([]);
        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')->with($prj, 5, 'PLUGIN_GIT_WPLUS')->andReturns([]);

        $this->big_object_authorization_manager->shouldReceive('getAuthorizedProjects')->andReturn([$prj]);

        // Ensure file is correct
        $result   = $this->project_serializer->dumpProjectRepoConf($prj);
        $expected = file_get_contents($this->fix_dir . '/perms/bigobject.conf');

        $this->assertSame($expected, $result);
    }
}
