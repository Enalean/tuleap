<?php
/**
 * Copyright (c) Sogilis, 2015. All Rights Reserved.
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

Mock::generate('GitDao', 'MockGitDao');

class MyMockGitDao extends MockGitDao {
    public $last_saved_repository;
    public function save(GitRepository $repository) {
        $this->last_saved_repository = $repository;
        return true;
    }
}

class GitXmlImporterTest extends TuleapTestCase {
    private $project_manager;
    private $old_sys_data_dir;
    private $importer;
    private $git_plugin;
    private $temp_project_dir;
    private $old_homedir;
    private $git_factory;
    private $git_manager;
    private $git_dao;
    private $git_systemeventmanager;
    private $permission_dao;
    private $old_cwd;
    private $system_command;

    public function setUp() {
        $this->old_cwd = getcwd();
        $this->system_command = new System_Command();
        parent::setUp();

        $this->old_sys_data_dir = $GLOBALS['sys_data_dir'];
        $GLOBALS['sys_data_dir'] = parent::getTmpDir();
        $GLOBALS['tmp_dir'] = dirname(__FILE__) . '/_fixtures/tmp';
        mkdir("${GLOBALS['sys_data_dir']}/gitolite/admin/", 0777, true);
        mkdir("${GLOBALS['sys_data_dir']}/gitolite/repositories/test_project", 0777, true);
        $sys_data_dir_arg = escapeshellarg($GLOBALS['sys_data_dir']);
        $this->system_command->exec("chmod -R 777 $sys_data_dir_arg/gitolite/repositories");

        ForgeConfig::store();

        $this->git_dao = new MyMockGitDao();
        $plugin_dao = mock('PluginDao');
        $this->project_manager = ProjectManager::instance();

        $this->logger = mock('Logger');
        $this->git_plugin = new GitPlugin(1);
        $this->git_factory = new GitRepositoryFactory($this->git_dao, $this->project_manager);

        $this->git_systemeventmanager = mock('Git_SystemEventManager');
        $this->git_manager = new GitRepositoryManager($this->git_factory, $this->git_systemeventmanager, $this->git_dao, parent::getTmpDir());

        $restricted_plugin_dao = mock('RestrictedPluginDao');
        $plugin_factory = new PluginFactory($plugin_dao, new PluginResourceRestrictor($restricted_plugin_dao));

        $plugin_manager = new PluginManager($plugin_factory, EventManager::instance(), new SiteCache($this->logger), new ForgeUpgradeConfig(new System_Command()));
        PluginManager::setInstance($plugin_manager);

        $this->permission_dao = mock('PermissionsDAO');
        $permissions_manager  = new PermissionsManager($this->permission_dao);
        $git_mirror_dao = mock('Git_Mirror_MirrorDao');
        $git_gitolite_driver = new Git_GitoliteDriver($this->logger, $this->git_systemeventmanager, mock('Git_GitRepositoryUrlManager'), $this->git_dao, $git_mirror_dao, $this->git_plugin);
        $gitolite = new Git_Backend_Gitolite($git_gitolite_driver, $this->logger);
        $this->importer = new GitXmlImporter($this->logger, $this->git_manager, $this->git_factory, $gitolite, $this->git_systemeventmanager, $permissions_manager);
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

    public function tearDown() {
        try {
            $sys_data_dir_arg = escapeshellarg($GLOBALS['sys_data_dir']);
            $this->system_command->exec("sudo -u gitolite /usr/share/tuleap/plugins/git/bin/gl-delete-test-repository.sh $sys_data_dir_arg/gitolite/repositories/test_project");
        } catch(Exception $e) {
            //ignore errors
        }
        parent::tearDown();
        $GLOBALS['sys_data_dir'] = $this->old_sys_data_dir;
        ForgeConfig::restore();
        PermissionsManager::clearInstance();
        PluginManager::clearInstance();
        UserManager::clearInstance();
        unset($GLOBALS['tmp_dir']);
        //revert gitolite driver setAdminPath in its builder
        chdir($this->old_cwd);
    }

    public function itShouldImportOneRepositoryWithOneCommit() {
        $xml = <<<XML
            <project>
                <git>
                    <repository bundle-path="stable.bundle" name="stable"/>
                </git>
            </project>
XML;
        $xml_element = new SimpleXMLElement($xml);
        $res = $this->importer->import($this->project, mock('PFUSer'), $xml_element, parent::getTmpDir());

        $sys_data_dir_arg = escapeshellarg($GLOBALS['sys_data_dir']);
        $nb_commit = shell_exec("cd $sys_data_dir_arg/gitolite/repositories/test_project/stable.git && git log --oneline| wc -l");
        $this->assertEqual(1, intval($nb_commit));
    }

    public function itShouldImportTwoRepositoriesWithOneCommit() {
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

    public function itShouldImportPermissions() {
        //allow anonymous to avoid overriding of the ugroups by PermissionsUGroupMapper when adding/updating permissions
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);

        $xml = <<<XML
            <project>
                <git>
                    <repository bundle-path="stable.bundle" name="stable">
                        <read>
                            <ugroup-id>1</ugroup-id>
                        </read>
                        <write>
                            <ugroup-id>2</ugroup-id>
                        </write>
                        <wplus>
                            <ugroup-id>4</ugroup-id>
                        </wplus>
                    </repository>
                </git>
            </project>
XML;
        stub($this->permission_dao)->addPermission(Git::PERM_READ,  '*', 1)->at(0);
        stub($this->permission_dao)->addPermission(Git::PERM_WRITE, '*', 2)->at(1);
        stub($this->permission_dao)->addPermission(Git::PERM_WPLUS, '*', '4')->at(2);
        $this->import(new SimpleXMLElement($xml));
    }

    public function itShouldValidateXMLFormat() {
        $xml = <<<XML
            <project>
                <git>
                    <repository bundle-path="stable.bundle" name="stable">
                        <stuff>
                            <ugroup-id>4</ugroup-id>
                        </stuff>
                        <read>
                            <stuff>4</stuff>
                        </read>
                        <write>
                            <stuff>4</stuff>
                        </write>
                        <wplus>
                            <stuff>4</stuff>
                        </wplus>
                    </repository>
                </git>
            </project>
XML;
        $xml_exception_catched = false;
        try {
            $this->import(new SimpleXMLElement($xml));
        } catch(XML_ParseException $e) {
            $xml_exception_catched = true;
        }
        $this->assertTrue($xml_exception_catched);
    }

    public function itShouldUpdateConfViaSystemEvents()  {
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

    public function itShouldImportDescription() {
        $xml = <<<XML
            <project>
                <git>
                    <repository bundle-path="stable.bundle" name="stable" description="description stable"/>
                </git>
            </project>
XML;
        $this->import(new SimpleXMLElement($xml));
        $this->assertEqual('description stable', $this->git_dao->last_saved_repository->getDescription());
    }

    public function itShouldImportDefaultDescription() {
        $xml = <<<XML
            <project>
                <git>
                    <repository bundle-path="stable.bundle" name="stable"/>
                </git>
            </project>
XML;
        $this->import(new SimpleXMLElement($xml));
        $this->assertEqual(GitRepository::DEFAULT_DESCRIPTION, $this->git_dao->last_saved_repository->getDescription());
    }

    private function import($xml) {
        return $this->importer->import($this->project, mock('PFUSer'), $xml, parent::getTmpDir());
    }
}
