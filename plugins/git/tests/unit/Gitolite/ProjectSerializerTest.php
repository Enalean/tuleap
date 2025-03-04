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

declare(strict_types=1);

namespace Tuleap\Git\Gitolite;

use EventManager;
use ForgeConfig;
use Git_Driver_Gerrit_ProjectCreatorStatus;
use Git_Gitolite_ConfigPermissionsSerializer;
use Git_Gitolite_ProjectSerializer;
use Git_GitRepositoryUrlManager;
use GitPlugin;
use GitRepository;
use GitRepositoryFactory;
use PermissionsManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Git\BigObjectAuthorization\BigObjectAuthorizationManager;
use Tuleap\Git\Permissions\FineGrainedPermissionFactory;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Git\Permissions\RegexpFineGrainedRetriever;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProjectSerializerTest extends TestCase
{
    use ForgeConfigSandbox;

    private Git_Gitolite_ProjectSerializer $project_serializer;
    private GitRepositoryFactory&MockObject $repository_factory;
    private string $fix_dir;
    private PermissionsManager&MockObject $permissions_manager;
    private Git_Driver_Gerrit_ProjectCreatorStatus&MockObject $gerrit_project_status;
    private BigObjectAuthorizationManager&MockObject $big_object_authorization_manager;

    public function setUp(): void
    {
        $this->fix_dir = __DIR__ . '/_fixtures';

        ForgeConfig::set('sys_default_domain', 'localhost');

        $this->permissions_manager = $this->createMock(PermissionsManager::class);
        PermissionsManager::setInstance($this->permissions_manager);

        $this->repository_factory = $this->createMock(GitRepositoryFactory::class);

        $git_plugin = $this->createMock(GitPlugin::class);
        $git_plugin->method('areFriendlyUrlsActivated')->willReturn(false);

        $url_manager = new Git_GitRepositoryUrlManager($git_plugin);

        $this->gerrit_project_status = $this->createMock(Git_Driver_Gerrit_ProjectCreatorStatus::class);

        $event_manager                   = $this->createMock(EventManager::class);
        $fine_grained_retriever          = $this->createMock(FineGrainedRetriever::class);
        $gitolite_permissions_serializer = new Git_Gitolite_ConfigPermissionsSerializer(
            $this->gerrit_project_status,
            'whatever',
            $fine_grained_retriever,
            $this->createMock(FineGrainedPermissionFactory::class),
            $this->createMock(RegexpFineGrainedRetriever::class),
            $event_manager,
        );
        $event_manager->method('processEvent');
        $fine_grained_retriever->method('doesRepositoryUseFineGrainedPermissions');

        $this->big_object_authorization_manager = $this->createMock(BigObjectAuthorizationManager::class);

        $this->project_serializer = new Git_Gitolite_ProjectSerializer(
            new NullLogger(),
            $this->repository_factory,
            $gitolite_permissions_serializer,
            $url_manager,
            $this->big_object_authorization_manager,
        );
    }

    public function tearDown(): void
    {
        PermissionsManager::clearInstance();
    }

    public function testGetMailHookConfig(): void
    {
        $prj = ProjectTestBuilder::aProject()->withUnixName('project1')->withId(101)->build();

        // ShowRev
        $repo = new GitRepository();
        $repo->setId(5);
        $repo->setProject($prj);
        $repo->setName('test_default');
        self::assertSame(
            file_get_contents($this->fix_dir . '/gitolite-mail-config/mailhook-rev.txt'),
            $this->project_serializer->fetchMailHookConfig($prj, $repo)
        );

        // ShowRev + Mailprefix
        $repo = new GitRepository();
        $repo->setId(5);
        $repo->setProject($prj);
        $repo->setName('test_default');
        $repo->setMailPrefix('[KOIN] ');
        self::assertSame(
            file_get_contents($this->fix_dir . '/gitolite-mail-config/mailhook-rev-mail-prefix.txt'),
            $this->project_serializer->fetchMailHookConfig($prj, $repo)
        );

        // ShowRev + Mailprefix
        $repo = new GitRepository();
        $repo->setId(5);
        $repo->setProject($prj);
        $repo->setName('test_default');
        $repo->setMailPrefix('["\_o<"] \t');
        self::assertSame(
            file_get_contents($this->fix_dir . '/gitolite-mail-config/mailhook-rev-mail-prefix-quote.txt'),
            $this->project_serializer->fetchMailHookConfig($prj, $repo)
        );
    }

    // The project has 2 repositories nb 4 & 5.
    // 4 has defaults
    // 5 has pimped perms
    public function testDumpProjectRepoPermissions(): void
    {
        $prj = ProjectTestBuilder::aProject()->withUnixName('project1')->withId(404)->build();

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
        $this->repository_factory->expects(self::once())->method('getAllRepositoriesOfProject')
            ->with($prj)->willReturn([$repo, $repo2]);

        // Repo 4 (test_default): R = registered_users | W = project_members | W+ = none
        // Repo 5 (test_pimped): R = project_members | W = project_admin | W+ = user groups 101
        $this->permissions_manager->method('getAuthorizedUGroupIdsForProject')
            ->willReturnCallback(static fn($project, $id, $permission) => match ($id) {
                4 => match ($permission) {
                    'PLUGIN_GIT_READ'  => [2],
                    'PLUGIN_GIT_WRITE' => [3],
                    'PLUGIN_GIT_WPLUS' => [],
                },
                5 => match ($permission) {
                    'PLUGIN_GIT_READ'  => [3],
                    'PLUGIN_GIT_WRITE' => [4],
                    'PLUGIN_GIT_WPLUS' => [125],
                }
            });

        $this->big_object_authorization_manager->method('getAuthorizedProjects')->willReturn([]);

        // Ensure file is correct
        $result   = $this->project_serializer->dumpProjectRepoConf($prj);
        $expected = file_get_contents($this->fix_dir . '/perms/project1-full.conf');

        self::assertSame($expected, $result);
    }

    public function testRewindAccessRightsToGerritUserWhenRepoIsMigratedToGerrit(): void
    {
        $prj = ProjectTestBuilder::aProject()->withUnixName('project1')->withId(404)->build();

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
        $this->repository_factory->expects(self::once())->method('getAllRepositoriesOfProject')
            ->with($prj)->willReturn([$repo, $repo2]);

        $this->permissions_manager->method('getAuthorizedUGroupIdsForProject')
            ->willReturnCallback(static fn($project, $id, $permission) => match ($permission) {
                'PLUGIN_GIT_READ'  => [2],
                'PLUGIN_GIT_WRITE' => [3],
                'PLUGIN_GIT_WPLUS' => [125],
            });

        $this->gerrit_project_status->method('getStatus')->willReturn(Git_Driver_Gerrit_ProjectCreatorStatus::DONE);

        $this->big_object_authorization_manager->method('getAuthorizedProjects')->willReturn([]);

        // Ensure file is correct
        $result   = $this->project_serializer->dumpProjectRepoConf($prj);
        $expected = file_get_contents($this->fix_dir . '/perms/migrated_to_gerrit.conf');

        self::assertSame($expected, $result);
    }

    public function testDumpSuspendedProjectRepoPermissions(): void
    {
        $project = ProjectTestBuilder::aProject()->withUnixName('project1')->withId(404)->build();

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
        $this->repository_factory->expects(self::once())->method('getAllRepositoriesOfProject')
            ->with($project)->willReturn([$repo, $repo2]);

        // Ensure file is correct
        $result   = $this->project_serializer->dumpSuspendedProjectRepositoriesConfiguration($project);
        $expected = <<<EOS
repo project1/test_default
 - refs/.*$ = @all

repo project1/test_pimped
 - refs/.*$ = @all


EOS;

        self::assertSame($expected, $result);
    }

    public function testRepoFullNameConcatsUnixProjectNameNamespaceAndName(): void
    {
        $unix_name = 'project1';

        $repo = $this->givenARepositoryWithNameAndNamespace('repo', 'toto');
        self::assertSame('project1/toto/repo', $this->project_serializer->repoFullName($repo, $unix_name));

        $repo = $this->givenARepositoryWithNameAndNamespace('repo', '');
        self::assertSame('project1/repo', $this->project_serializer->repoFullName($repo, $unix_name));
    }

    private function givenARepositoryWithNameAndNamespace($name, $namespace): GitRepository
    {
        $repo = new GitRepository();
        $repo->setName($name);
        $repo->setNamespace($namespace);
        return $repo;
    }

    public function testDoNotWriteBigObjectRuleIfProjectIsAuthorized(): void
    {
        $prj = ProjectTestBuilder::aProject()->withUnixName('project1')->withId(404)->build();

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
        $this->repository_factory->expects(self::once())->method('getAllRepositoriesOfProject')
            ->with($prj)->willReturn([$repo, $repo2]);

        $this->permissions_manager->method('getAuthorizedUGroupIdsForProject')->willReturn([]);
        $this->big_object_authorization_manager->method('getAuthorizedProjects')->willReturn([$prj]);

        // Ensure file is correct
        $result   = $this->project_serializer->dumpProjectRepoConf($prj);
        $expected = file_get_contents($this->fix_dir . '/perms/bigobject.conf');

        self::assertSame($expected, $result);
    }
}
