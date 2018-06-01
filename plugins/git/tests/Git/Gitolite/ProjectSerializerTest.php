<?php
/**
 * Copyright (c) Enalean, 2015-2018. All rights reserved
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

require_once dirname(__FILE__).'/../../bootstrap.php';
Mock::generate('Project');

class ProjectSerializerTest extends TuleapTestCase {
    private $project_serializer;
    private $repository_factory;
    private $url_manager;
    private $gitolite_permissions_serializer;
    private $logger;
    private $_fixDir;
    private $permissions_manager;
    private $gerrit_project_status;

    public function setUp() {
        parent::setUp();

        $this->_fixDir       = dirname(__FILE__).'/_fixtures';

        $this->http_request = mock('HTTPRequest');
        HTTPRequest::setInstance($this->http_request);
        stub($this->http_request)->getServerUrl()->returns('https://localhost');

        PermissionsManager::setInstance(mock('PermissionsManager'));
        $this->permissions_manager = PermissionsManager::instance();

        $this->repository_factory = mock('GitRepositoryFactory');

        $git_plugin        = stub('GitPlugin')->areFriendlyUrlsActivated()->returns(false);
        $this->url_manager = new Git_GitRepositoryUrlManager($git_plugin);

        $mirror_data_mapper = mock('Git_Mirror_MirrorDataMapper');
        stub($mirror_data_mapper)->fetchAllRepositoryMirrors()->returns(array());
        stub($mirror_data_mapper)->fetchAll()->returns(array());

        $this->gerrit_project_status = mock('Git_Driver_Gerrit_ProjectCreatorStatus');

        $this->gitolite_permissions_serializer = new Git_Gitolite_ConfigPermissionsSerializer(
            $mirror_data_mapper,
            $this->gerrit_project_status,
            'whatever',
            mock('Tuleap\Git\Permissions\FineGrainedRetriever'),
            mock('Tuleap\Git\Permissions\FineGrainedPermissionFactory'),
            mock('Tuleap\Git\Permissions\RegexpFineGrainedRetriever'),
            mock(EventManager::class)
        );

        $this->logger = mock('Logger');

        $this->project_serializer = new Git_Gitolite_ProjectSerializer(
            $this->logger,
            $this->repository_factory,
            $this->gitolite_permissions_serializer,
            $this->url_manager
        );
    }

    public function tearDown() {
        PermissionsManager::clearInstance();
        HTTPRequest::clearInstance();

        parent::tearDown();
    }

    public function testGetMailHookConfig() {
        $prj = new MockProject($this);
        $prj->setReturnValue('getUnixName', 'project1');
        $prj->setReturnValue('getId', 101);

        // ShowRev
        $repo = new GitRepository();
        $repo->setId(5);
        $repo->setProject($prj);
        $repo->setName('test_default');
        $this->assertIdentical(
            file_get_contents($this->_fixDir .'/gitolite-mail-config/mailhook-rev.txt'),
            $this->project_serializer->fetchMailHookConfig($prj, $repo)
        );

        // ShowRev + Mailprefix
        $repo = new GitRepository();
        $repo->setId(5);
        $repo->setProject($prj);
        $repo->setName('test_default');
        $repo->setMailPrefix('[KOIN] ');
        $this->assertIdentical(
            file_get_contents($this->_fixDir .'/gitolite-mail-config/mailhook-rev-mail-prefix.txt'),
            $this->project_serializer->fetchMailHookConfig($prj, $repo)
        );

        // ShowRev + Mailprefix
        $repo = new GitRepository();
        $repo->setId(5);
        $repo->setProject($prj);
        $repo->setName('test_default');
        $repo->setMailPrefix('["\_o<"] \t');
        $this->assertIdentical(
            file_get_contents($this->_fixDir .'/gitolite-mail-config/mailhook-rev-mail-prefix-quote.txt'),
            $this->project_serializer->fetchMailHookConfig($prj, $repo)
        );
    }

    //
    // The project has 2 repositories nb 4 & 5.
    // 4 has defaults
    // 5 has pimped perms
    public function testDumpProjectRepoPermissions() {
        $prj = new MockProject($this);
        $prj->setReturnValue('getUnixName', 'project1');
        $prj->setReturnValue('getId', 404);

        // List all repo
        stub($this->repository_factory)->getAllRepositoriesOfProject($prj)->once()->returns(
            array(
                aGitRepository()
                    ->withProject($prj)
                    ->withId(4)
                    ->withName('test_default')
                    ->withNamespace('')
                    ->withMailPrefix('[SCM]')
                    ->build(),
                aGitRepository()
                    ->withId(5)
                    ->withProject($prj)
                    ->withName('test_pimped')
                    ->withNamespace('')
                    ->withMailPrefix('[KOIN] ')
                    ->build()
            )
        );

        // Repo 4 (test_default): R = registered_users | W = project_members | W+ = none
        $this->permissions_manager->setReturnValue('getAuthorizedUGroupIdsForProject', array('2'),   array($prj, 4, 'PLUGIN_GIT_READ'));
        $this->permissions_manager->setReturnValue('getAuthorizedUGroupIdsForProject', array('3'),   array($prj, 4, 'PLUGIN_GIT_WRITE'));
        $this->permissions_manager->setReturnValue('getAuthorizedUGroupIdsForProject', array(),      array($prj, 4, 'PLUGIN_GIT_WPLUS'));

        // Repo 5 (test_pimped): R = project_members | W = project_admin | W+ = user groups 101
        $this->permissions_manager->setReturnValue('getAuthorizedUGroupIdsForProject', array('3'),   array($prj, 5, 'PLUGIN_GIT_READ'));
        $this->permissions_manager->setReturnValue('getAuthorizedUGroupIdsForProject', array('4'),   array($prj, 5, 'PLUGIN_GIT_WRITE'));
        $this->permissions_manager->setReturnValue('getAuthorizedUGroupIdsForProject', array('125'), array($prj, 5, 'PLUGIN_GIT_WPLUS'));

        // Ensure file is correct
        $result     = $this->project_serializer->dumpProjectRepoConf($prj);
        $expected   = file_get_contents($this->_fixDir .'/perms/project1-full.conf');
        $this->assertIdentical($expected, $result);
    }

    public function testRewindAccessRightsToGerritUserWhenRepoIsMigratedToGerrit() {
        $prj = new MockProject($this);
        $prj->setReturnValue('getUnixName', 'project1');
        $prj->setReturnValue('getId', 404);

        // List all repo
        stub($this->repository_factory)->getAllRepositoriesOfProject($prj)->once()->returns(
            array(
                aGitRepository()
                    ->withProject($prj)
                    ->withId(4)
                    ->withName('before_migration_to_gerrit')
                    ->withNamespace('')
                    ->build(),
                aGitRepository()
                    ->withId(5)
                    ->withProject($prj)
                    ->withName('after_migration_to_gerrit')
                    ->withNamespace('')
                    ->withRemoteServerId(1)
                    ->build()
            )
        );

        stub($this->permissions_manager)->getAuthorizedUGroupIdsForProject($prj, 4, 'PLUGIN_GIT_READ')->returns(array('2'));
        stub($this->permissions_manager)->getAuthorizedUGroupIdsForProject($prj, 4, 'PLUGIN_GIT_WRITE')->returns(array('3'));
        stub($this->permissions_manager)->getAuthorizedUGroupIdsForProject($prj, 4, 'PLUGIN_GIT_WPLUS')->returns(array('125'));
        stub($this->permissions_manager)->getAuthorizedUGroupIdsForProject($prj, 5, 'PLUGIN_GIT_READ')->returns(array('2'));
        stub($this->permissions_manager)->getAuthorizedUGroupIdsForProject($prj, 5, 'PLUGIN_GIT_WRITE')->returns(array('3'));
        stub($this->permissions_manager)->getAuthorizedUGroupIdsForProject($prj, 5, 'PLUGIN_GIT_WPLUS')->returns(array('125'));

        stub($this->gerrit_project_status)->getStatus()->returns(Git_Driver_Gerrit_ProjectCreatorStatus::DONE);

        // Ensure file is correct
        $result     = $this->project_serializer->dumpProjectRepoConf($prj);
        $expected = file_get_contents($this->_fixDir .'/perms/migrated_to_gerrit.conf');
        $this->assertIdentical($expected, $result);
    }

    public function testRepoFullNameConcats_UnixProjectName_Namespace_And_Name() {
        $unix_name = 'project1';

        $repo = $this->_GivenARepositoryWithNameAndNamespace('repo', 'toto');
        $this->assertEqual('project1/toto/repo', $this->project_serializer->repoFullName($repo, $unix_name));

        $repo = $this->_GivenARepositoryWithNameAndNamespace('repo', '');
        $this->assertEqual('project1/repo', $this->project_serializer->repoFullName($repo, $unix_name));
    }

    private function _GivenARepositoryWithNameAndNamespace($name, $namespace) {
        $repo = new GitRepository();
        $repo->setName($name);
        $repo->setNamespace($namespace);
        return $repo;
    }

}
