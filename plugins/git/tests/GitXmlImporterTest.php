<?php
/**
 * Copyright (c) Enalean, 2015 - 2019. All Rights Reserved.
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

require_once 'bootstrap.php';

use Tuleap\Git\Gitolite\GitoliteAccessURLGenerator;
use Tuleap\Markdown\ContentInterpretor;
use Tuleap\Git\Permissions\FineGrainedPermission;
use Tuleap\Git\XmlUgroupRetriever;

class GitXmlImporterTest extends TuleapTestCase
{
    /**
     * @var XMLImportHelper
     */
    private $user_finder;
    /**
     * @var ProjectManager
     */
    private $project_manager;
    private $old_sys_data_dir;
    /**
     * @var GitXmlImporter
     */
    private $importer;
    /**
     * @var GitPlugin
     */
    private $git_plugin;
    private $temp_project_dir;
    /**
     * @var GitRepositoryFactory
     */
    private $git_factory;
    /**
     * @var GitRepositoryManager
     */
    private $git_manager;
    /**
     * @var GitDao
     */
    private $git_dao;
    /**
     * @var Git_SystemEventManager
     */
    private $git_systemeventmanager;
    /**
     * @var PermissionsDAO
     */
    private $permission_dao;
    private $old_cwd;
    /**
     * @var System_Command
     */
    private $system_command;
    /**
     * @var UGroupManager
     */
    private $ugroup_manager;
    /**
     * @var UGroupDao
     */
    private $ugroup_dao;
    /**
     * @var EventManager
     */
    private $event_manager;

    /**
     * @var GitRepository|null
     */
    private $last_saved_repository;

    public function setUp()
    {
        $this->old_cwd = getcwd();
        $this->system_command = new System_Command();
        parent::setUp();

        $this->old_sys_data_dir = isset($GLOBALS['sys_data_dir']) ? $GLOBALS['sys_data_dir'] : null;
        $GLOBALS['sys_data_dir'] = parent::getTmpDir();
        mkdir("${GLOBALS['sys_data_dir']}/gitolite/admin/", 0777, true);
        mkdir("${GLOBALS['sys_data_dir']}/gitolite/repositories/test_project", 0777, true);
        $sys_data_dir_arg = escapeshellarg($GLOBALS['sys_data_dir']);
        $this->system_command->exec("chown -R gitolite:gitolite $sys_data_dir_arg/");

        ForgeConfig::store();
        ForgeConfig::set('tmp_dir', parent::getTmpDir());

        $this->git_dao = \Mockery::spy(GitDao::class);
        $this->git_dao->shouldReceive('save')->with(\Mockery::on(
            function (GitRepository $repository) : bool {
                $this->last_saved_repository = $repository;
                return true;
            }
        ));
        $plugin_dao = mock('PluginDao');
        ProjectManager::clearInstance();
        $this->project_manager = ProjectManager::instance();

        $this->logger = mock('Logger');
        $this->git_plugin = new GitPlugin(1);
        $this->git_factory = new GitRepositoryFactory($this->git_dao, $this->project_manager);

        $this->ugroup_dao     = mock('UGroupDao');
        $this->ugroup_manager = new UGroupManager($this->ugroup_dao, \Mockery::spy(\EventManager::class));

        $this->git_systemeventmanager        = mock('Git_SystemEventManager');
        $this->mirror_updater                = mock('GitRepositoryMirrorUpdater');
        $this->mirror_data_mapper            = mock('Git_Mirror_MirrorDataMapper');
        $this->event_manager                 = \Mockery::spy(\EventManager::class);
        $this->fine_grained_updater          = mock('Tuleap\Git\Permissions\FineGrainedUpdater');
        $this->regexp_fine_grained_retriever = mock('Tuleap\Git\Permissions\RegexpFineGrainedRetriever');
        $this->regexp_fine_grained_enabler   = mock('Tuleap\Git\Permissions\RegexpFineGrainedEnabler');
        $this->fine_grained_factory          = mock('Tuleap\Git\Permissions\FineGrainedPermissionFactory');
        $this->fine_grained_saver            = mock('Tuleap\Git\Permissions\FineGrainedPermissionSaver');
        $this->xml_ugroup_retriever          = new XmlUgroupRetriever(
            $this->logger,
            $this->ugroup_manager
        );

        $this->git_manager = new GitRepositoryManager(
            $this->git_factory,
            $this->git_systemeventmanager,
            $this->git_dao,
            parent::getTmpDir(),
            $this->mirror_updater,
            $this->mirror_data_mapper,
            mock('Tuleap\Git\Permissions\FineGrainedPermissionReplicator'),
            mock('ProjectHistoryDao'),
            mock('Tuleap\Git\Permissions\HistoryValueFormatter'),
            $this->event_manager
        );

        $restricted_plugin_dao = mock('RestrictedPluginDao');
        $plugin_factory = new PluginFactory($plugin_dao, new PluginResourceRestrictor($restricted_plugin_dao));

        $plugin_manager = new PluginManager(
            $plugin_factory,
            new SiteCache($this->logger),
            new ForgeUpgradeConfig(new System_Command()),
            new ContentInterpretor()
        );

        PluginManager::setInstance($plugin_manager);

        $this->permission_dao = mock('PermissionsDao');
        $permissions_manager  = new PermissionsManager($this->permission_dao);
        $git_mirror_dao       = safe_mock(Git_Mirror_MirrorDao::class);
        $git_gitolite_driver  = new Git_GitoliteDriver(
            $this->logger,
            $this->git_systemeventmanager,
            mock('Git_GitRepositoryUrlManager'),
            $this->git_dao,
            $git_mirror_dao,
            $this->git_plugin,
            null,
            null,
            mock('Git_Gitolite_ConfigPermissionsSerializer'),
            null,
            null,
            mock('Git_Mirror_MirrorDataMapper'),
            mock('Tuleap\Git\BigObjectAuthorization\BigObjectAuthorizationManager'),
            mock('Tuleap\Git\Gitolite\VersionDetector')
        );
        $this->user_finder = mock(XMLImportHelper::class);

        $gitolite       = new Git_Backend_Gitolite($git_gitolite_driver, mock(GitoliteAccessURLGenerator::class), $this->logger);
        $this->importer = new GitXmlImporter(
            $this->logger,
            $this->git_manager,
            $this->git_factory,
            $gitolite,
            new XML_RNGValidator(),
            $this->git_systemeventmanager,
            $permissions_manager,
            $this->event_manager,
            $this->fine_grained_updater,
            $this->regexp_fine_grained_retriever,
            $this->regexp_fine_grained_enabler,
            $this->fine_grained_factory,
            $this->fine_grained_saver,
            $this->xml_ugroup_retriever,
            $this->git_dao,
            $this->user_finder
        );

        $this->temp_project_dir = parent::getTmpDir() . DIRECTORY_SEPARATOR . 'test_project';

        $userManager = mock('UserManager');
        stub($userManager)->getUserById()->returns(new PFUser());
        UserManager::setInstance($userManager);

        stub($this->permission_dao)->clearPermission()->returns(true);
        stub($this->permission_dao)->addPermission()->returns(true);
        stub($this->git_dao)->getProjectRepositoryList()->returns(array());

        copy(__DIR__ . '/_fixtures/stable_repo_one_commit.bundle', parent::getTmpDir() . DIRECTORY_SEPARATOR . 'stable.bundle');
        $this->project = $this->project_manager->getProjectFromDbRow(
            array('group_id' => 123, 'unix_group_name' => 'test_project', 'access' => Project::ACCESS_PUBLIC)
        );
    }

    public function tearDown()
    {
        try {
            $sys_data_dir_arg = escapeshellarg($GLOBALS['sys_data_dir']);
            $this->system_command->exec("sudo -u gitolite /usr/share/tuleap/plugins/git/bin/gl-delete-test-repository.sh $sys_data_dir_arg/gitolite/repositories/test_project");
        } catch (Exception $e) {
            //ignore errors
        }
        parent::tearDown();
        if ($this->old_sys_data_dir !== null) {
            $GLOBALS['sys_data_dir'] = $this->old_sys_data_dir;
        }
        ForgeConfig::restore();
        PermissionsManager::clearInstance();
        PluginManager::clearInstance();
        UserManager::clearInstance();
        //revert gitolite driver setAdminPath in its builder
        chdir($this->old_cwd);
    }

    public function itShouldCreateOneEmptyRepository()
    {
        $xml = <<<XML
            <project>
                <git>
                    <repository bundle-path="" name="empty"/>
                </git>
            </project>
XML;
        $xml_element = new SimpleXMLElement($xml);
        $res = $this->importer->import(new Tuleap\Project\XML\Import\ImportConfig(), $this->project, mock('PFUSer'), $xml_element, parent::getTmpDir());

        $this->assertEqual($this->last_saved_repository->getName(), 'empty');

        $iterator = new DirectoryIterator($GLOBALS['sys_data_dir'].'/gitolite/repositories/test_project');
        $empty_is_here = false;
        foreach ($iterator as $it) {
            if ($it->getFilename() == 'empty') {
                $empty_is_here = true;
            }
        }
        $this->assertFalse($empty_is_here);
    }

    public function itShouldImportOneRepositoryWithOneCommit()
    {
        $xml = <<<XML
            <project>
                <git>
                    <repository bundle-path="stable.bundle" name="stable"/>
                </git>
            </project>
XML;
        $xml_element = new SimpleXMLElement($xml);
        $res = $this->importer->import(
            new Tuleap\Project\XML\Import\ImportConfig(),
            $this->project,
            mock('PFUSer'),
            $xml_element,
            parent::getTmpDir()
        );

        $sys_data_dir_arg = escapeshellarg($GLOBALS['sys_data_dir']);
        $nb_commit = shell_exec("cd $sys_data_dir_arg/gitolite/repositories/test_project/stable.git && git log --oneline| wc -l");
        $this->assertEqual(1, intval($nb_commit));
    }

    public function itShouldImportTwoRepositoriesWithOneCommit()
    {
        $xml = <<<XML
            <project>
                <git>
                <repository bundle-path="stable.bundle" name="stable"/>
                <repository bundle-path="stable.bundle" name="stable2"/>
                </git>
            </project>
XML;
        $this->import(new SimpleXMLElement($xml));
        $sys_data_dir_arg = escapeshellarg($GLOBALS['sys_data_dir']);
        $nb_commit_stable = shell_exec("cd $sys_data_dir_arg/gitolite/repositories/test_project/stable.git && git log --oneline| wc -l");
        $this->assertEqual(1, intval($nb_commit_stable));

        $nb_commit_stable2 = shell_exec("cd $sys_data_dir_arg/gitolite/repositories/test_project/stable2.git && git log --oneline| wc -l");
        $this->assertEqual(1, intval($nb_commit_stable2));
    }

    public function itShouldImportStaticUgroups()
    {
        //allow anonymous to avoid overriding of the ugroups by PermissionsUGroupMapper when adding/updating permissions
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);

        $xml = <<<XML
            <project>
                <git>
                    <repository bundle-path="stable.bundle" name="stable">
                        <permissions>
                            <read>
                                <ugroup>project_members</ugroup>
                            </read>
                            <write>
                                <ugroup>project_members</ugroup>
                            </write>
                            <wplus>
                                <ugroup>project_admins</ugroup>
                            </wplus>
                        </permissions>
                    </repository>
                </git>
            </project>
XML;
        $result = mock('DataAccessResult');
        stub($result)->getRow()->returns(false);
        stub($this->ugroup_dao)->searchByGroupIdAndName()->returns($result);
        stub($this->permission_dao)->addPermission(Git::PERM_READ, '*', 3)->at(0);
        stub($this->permission_dao)->addPermission(Git::PERM_WRITE, '*', 3)->at(1);
        stub($this->permission_dao)->addPermission(Git::PERM_WPLUS, '*', 4)->at(2);
        $this->import(new SimpleXMLElement($xml));
    }

    public function itShouldImportLegacyPermissions()
    {
        //allow anonymous to avoid overriding of the ugroups by PermissionsUGroupMapper when adding/updating permissions
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);

        $xml = <<<XML
            <project>
                <git>
                    <repository bundle-path="stable.bundle" name="stable">
                        <read>
                            <ugroup>project_members</ugroup>
                        </read>
                        <write>
                            <ugroup>project_members</ugroup>
                        </write>
                        <wplus>
                            <ugroup>project_admins</ugroup>
                        </wplus>
                    </repository>
                </git>
            </project>
XML;
        $result = mock('DataAccessResult');
        stub($result)->getRow()->returns(false);
        stub($this->ugroup_dao)->searchByGroupIdAndName()->returns($result);
        stub($this->permission_dao)->addPermission(Git::PERM_READ, '*', 3)->at(0);
        stub($this->permission_dao)->addPermission(Git::PERM_WRITE, '*', 3)->at(1);
        stub($this->permission_dao)->addPermission(Git::PERM_WPLUS, '*', 4)->at(2);
        $this->import(new SimpleXMLElement($xml));
    }

    public function itShouldUpdateConfViaSystemEvents()
    {
        $xml = <<<XML
            <project>
                <git>
                    <repository bundle-path="stable.bundle" name="stable"/>
                </git>
            </project>
XML;
        stub($this->git_systemeventmanager)->queueProjectsConfigurationUpdate(array(123))->atLeastOnce();
        $this->import(new SimpleXMLElement($xml));
    }

    public function itShouldImportDescription()
    {
        $xml = <<<XML
            <project>
                <git>
                    <repository bundle-path="stable.bundle" name="stable" description="description stable"/>
                </git>
            </project>
XML;
        $this->import(new SimpleXMLElement($xml));
        $this->assertEqual('description stable', $this->last_saved_repository->getDescription());
    }

    public function itShouldImportDefaultDescription()
    {
        $xml = <<<XML
            <project>
                <git>
                    <repository bundle-path="stable.bundle" name="stable"/>
                </git>
            </project>
XML;
        $this->import(new SimpleXMLElement($xml));
        $this->assertEqual(GitRepository::DEFAULT_DESCRIPTION, $this->last_saved_repository->getDescription());
    }

    public function itShouldAtLeastSetProjectsAdminAsGitAdmins()
    {
        $xml = <<<XML
            <project>
                <git>
                    <repository bundle-path="stable.bundle" name="stable"/>
                </git>
            </project>
XML;
        expect($this->permission_dao)->addPermission(Git::PERM_ADMIN, $this->project->getId(), 4)->once();
        $this->import(new SimpleXMLElement($xml));
    }

    public function itShouldImportGitAdmins()
    {
        $xml = <<<XML
            <project>
                <git>
                    <repository bundle-path="stable.bundle" name="stable"/>
                    <ugroups-admin>
                        <ugroup>project_members</ugroup>
                    </ugroups-admin>
                </git>
            </project>
XML;
        $result = mock('DataAccessResult');
        stub($result)->getRow()->returns(false);
        stub($this->ugroup_dao)->searchByGroupIdAndName()->returns($result);

        expect($this->permission_dao)->addPermission(Git::PERM_ADMIN, $this->project->getId(), 3)->at(0);
        expect($this->permission_dao)->addPermission(Git::PERM_ADMIN, $this->project->getId(), 4)->at(1);
        $this->import(new SimpleXMLElement($xml));
    }

    public function itShouldImportReferences()
    {
        $xml = <<<XML
            <project>
                <git>
                    <repository bundle-path="stable.bundle" name="stable">
                        <references>
                            <reference source="cmmt1234" target="sha1" />
                        </references>
                    </repository>
                </git>
            </project>
XML;
        stub($this->ugroup_dao)->searchByGroupIdAndName()->returnsEmptyDar();

        expect($this->event_manager)->processEvent()->count(2);

        $this->import(new SimpleXMLElement($xml));
    }

    public function itShouldNotImportFineGrainedPermissionsWhenNoNode()
    {
        $xml = <<<XML
            <project>
                <git>
                    <repository bundle-path="stable.bundle" name="stable">
                    </repository>
                </git>
            </project>
XML;
        stub($this->ugroup_dao)->searchByGroupIdAndName()->returnsEmptyDar();

        expect($this->fine_grained_updater)->enableRepository()->never();
        expect($this->regexp_fine_grained_enabler)->enableForRepository()->never();

        $this->import(new SimpleXMLElement($xml));
    }

    public function itShouldImportFineGrainedPermissions()
    {
        $xml = <<<XML
            <project>
                <git>
                    <repository bundle-path="stable.bundle" name="stable">
                        <permissions>
                            <fine_grained enabled="1" use_regexp="0" />
                        </permissions>
                    </repository>
                </git>
            </project>
XML;
        stub($this->ugroup_dao)->searchByGroupIdAndName()->returnsEmptyDar();

        expect($this->fine_grained_updater)->enableRepository()->once();
        expect($this->regexp_fine_grained_enabler)->enableForRepository()->never();

        $this->import(new SimpleXMLElement($xml));
    }

    public function itShouldImportRegexpFineGrainedPermissions()
    {
        $xml = <<<XML
            <project>
                <git>
                    <repository bundle-path="stable.bundle" name="stable">
                        <permissions>
                            <fine_grained enabled="1" use_regexp="1" />
                        </permissions>
                    </repository>
                </git>
            </project>
XML;
        stub($this->ugroup_dao)->searchByGroupIdAndName()->returnsEmptyDar();
        stub($this->regexp_fine_grained_retriever)->areRegexpActivatedAtSiteLevel()->returns(true);

        expect($this->fine_grained_updater)->enableRepository()->once();
        expect($this->regexp_fine_grained_enabler)->enableForRepository()->once();

        $this->import(new SimpleXMLElement($xml));
    }

    public function itShouldNotImportRegexpFineGrainedPermissionsIfNotAvailableAtSiteLevel()
    {
        $xml = <<<XML
            <project>
                <git>
                    <repository bundle-path="stable.bundle" name="stable">
                        <permissions>
                            <fine_grained enabled="1" use_regexp="1" />
                        </permissions>
                    </repository>
                </git>
            </project>
XML;
        stub($this->ugroup_dao)->searchByGroupIdAndName()->returnsEmptyDar();
        stub($this->regexp_fine_grained_retriever)->areRegexpActivatedAtSiteLevel()->returns(false);

        expect($this->fine_grained_updater)->enableRepository()->once();
        expect($this->regexp_fine_grained_enabler)->enableForRepository()->never();

        $this->import(new SimpleXMLElement($xml));
    }

    public function itShouldImportRegexpFineGrainedPermissionsEvenWhenFineGrainedAreNotUsed()
    {
        $xml = <<<XML
            <project>
                <git>
                    <repository bundle-path="stable.bundle" name="stable">
                        <permissions>
                            <fine_grained enabled="0" use_regexp="1" />
                        </permissions>
                    </repository>
                </git>
            </project>
XML;
        stub($this->ugroup_dao)->searchByGroupIdAndName()->returnsEmptyDar();
        stub($this->regexp_fine_grained_retriever)->areRegexpActivatedAtSiteLevel()->returns(true);

        expect($this->fine_grained_updater)->enableRepository()->never();
        expect($this->regexp_fine_grained_enabler)->enableForRepository()->once();

        $this->import(new SimpleXMLElement($xml));
    }

    public function itImportPatternsWithoutRegexp()
    {
        $xml = <<<XML
            <project>
                <git>
                    <repository bundle-path="stable.bundle" name="stable">
                        <permissions>
                            <fine_grained enabled="1" use_regexp="0">
                                <pattern value="*" type="branch" />
                                <pattern value="*" type="tag" />
                            </fine_grained>
                        </permissions>
                    </repository>
                </git>
            </project>
XML;

        $representation = new FineGrainedPermission(
            0,
            1,
            '*',
            array(),
            array()
        );

        stub($this->ugroup_dao)->searchByGroupIdAndName()->returnsEmptyDar();
        stub($this->regexp_fine_grained_retriever)->areRegexpActivatedAtSiteLevel()->returns(true);
        stub($this->fine_grained_factory)->getFineGrainedPermissionFromXML()->returns($representation);

        expect($this->fine_grained_saver)->saveBranchPermission()->once();
        expect($this->fine_grained_saver)->saveTagPermission()->once();

        $this->import(new SimpleXMLElement($xml));
    }

    public function itDoesNotImportRegexpWhenNotActivated()
    {
        $xml = <<<XML
            <project>
                <git>
                    <repository bundle-path="stable.bundle" name="stable">
                        <permissions>
                            <fine_grained enabled="1" use_regexp="0">
                                <pattern value="*" type="branch" />
                                <pattern value="branch[0-9]" type="branch" />
                                <pattern value="*" type="tag" />
                            </fine_grained>
                        </permissions>
                    </repository>
                </git>
            </project>
XML;

        $representation = new FineGrainedPermission(
            0,
            1,
            '*',
            array(),
            array()
        );

        stub($this->ugroup_dao)->searchByGroupIdAndName()->returnsEmptyDar();
        stub($this->regexp_fine_grained_retriever)->areRegexpActivatedAtSiteLevel()->returns(true);
        stub($this->fine_grained_factory)->getFineGrainedPermissionFromXML()->returnsAt(0, $representation);
        stub($this->fine_grained_factory)->getFineGrainedPermissionFromXML()->returnsAt(1, false);
        stub($this->fine_grained_factory)->getFineGrainedPermissionFromXML()->returnsAt(2, $representation);

        expect($this->fine_grained_saver)->saveBranchPermission()->once();
        expect($this->fine_grained_saver)->saveTagPermission()->once();

        $this->import(new SimpleXMLElement($xml));
    }

    public function itImportsRegexpPatterns()
    {
        $xml = <<<XML
            <project>
                <git>
                    <repository bundle-path="stable.bundle" name="stable">
                        <permissions>
                            <fine_grained enabled="1" use_regexp="1">
                                <pattern value="*" type="branch" />
                                <pattern value="branch[0-9]" type="branch" />
                                <pattern value="*" type="tag" />
                            </fine_grained>
                        </permissions>
                    </repository>
                </git>
            </project>
XML;

        $representation_01 = new FineGrainedPermission(
            0,
            1,
            '*',
            array(),
            array()
        );

        $representation_02 = new FineGrainedPermission(
            0,
            1,
            '*',
            array(),
            array()
        );

        stub($this->ugroup_dao)->searchByGroupIdAndName()->returnsEmptyDar();
        stub($this->regexp_fine_grained_retriever)->areRegexpActivatedAtSiteLevel()->returns(true);
        stub($this->fine_grained_factory)->getFineGrainedPermissionFromXML()->returnsAt(0, $representation_01);
        stub($this->fine_grained_factory)->getFineGrainedPermissionFromXML()->returnsAt(1, $representation_02);
        stub($this->fine_grained_factory)->getFineGrainedPermissionFromXML()->returnsAt(2, $representation_01);

        $this->fine_grained_saver->expectCallCount('saveBranchPermission', 2);
        expect($this->fine_grained_saver)->saveTagPermission()->once();

        $this->import(new SimpleXMLElement($xml));
    }

    private function import($xml)
    {
        return $this->importer->import(new Tuleap\Project\XML\Import\ImportConfig(), $this->project, mock('PFUSer'), $xml, parent::getTmpDir());
    }

    public function itShouldImportGitLastPushData()
    {
        $xml = <<<XML
            <project>
                <git>
                    <repository bundle-path="stable.bundle" name="stable">
                        <last-push-date push_date="1527145846" commits_number="1" refname="refs/heads/master" operation_type="create" refname_type="branch">
                            <user format="id">102</user>
                        </last-push-date>
                    </repository>
                </git>
            </project>
XML;

        stub($this->user_finder)->getUser()->returns(aUser()->withId(102)->build());
        expect($this->git_dao)->logGitPush()->once();
        $this->import(new SimpleXMLElement($xml));
        $this->assertEqual(GitRepository::DEFAULT_DESCRIPTION, $this->last_saved_repository->getDescription());
    }
}
