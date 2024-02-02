<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

namespace Tuleap\Git;

use DirectoryIterator;
use ForgeAccess;
use ForgeConfig;
use ForgeUpgradeConfig;
use Git;
use Git_Backend_Gitolite;
use Git_GitoliteDriver;
use GitDao;
use GitPlugin;
use GitRepository;
use GitRepositoryFactory;
use GitRepositoryManager;
use GitXmlImporter;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PermissionsManager;
use PFUser;
use PluginFactory;
use PluginManager;
use PluginResourceRestrictor;
use Project;
use ProjectManager;
use Psr\Log\NullLogger;
use SimpleXMLElement;
use SiteCache;
use System_Command;
use Tuleap\ForgeUpgrade\ForgeUpgrade;
use Tuleap\Git\Gitolite\GitoliteAccessURLGenerator;
use Tuleap\Git\Permissions\FineGrainedPermission;
use Tuleap\Git\Repository\Settings\ArtifactClosure\ConfigureAllowArtifactClosure;
use Tuleap\Git\Tests\Stub\DefaultBranch\DefaultBranchUpdateExecutorStub;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;
use UGroupManager;
use UserManager;
use XMLImportHelper;

final class GitXmlImporterTest extends TestIntegrationTestCase
{
    use MockeryPHPUnitIntegration;
    use TemporaryTestDirectory;
    use \Tuleap\GlobalLanguageMock;

    /**
     * @var XMLImportHelper
     */
    private $user_finder;
    /**
     * @var ProjectManager
     */
    private $project_manager;
    /**
     * @var GitXmlImporter
     */
    private $importer;
    /**
     * @var GitPlugin
     */
    private $git_plugin;
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
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Psr\Log\LoggerInterface
     */
    private $logger;

    private \PHPUnit\Framework\MockObject\MockObject|ConfigureAllowArtifactClosure $configure_artifact_closure;

    protected function setUp(): void
    {
        $this->old_cwd        = getcwd();
        $this->system_command = new System_Command();

        $sys_data_dir = $this->getTmpDir();
        ForgeConfig::set('sys_data_dir', $sys_data_dir);
        mkdir("${sys_data_dir}/gitolite/admin/", 0777, true);
        mkdir("${sys_data_dir}/gitolite/repositories/test_project", 0777, true);
        $sys_data_dir_arg = escapeshellarg($sys_data_dir);
        $this->system_command->exec("chmod -R 0777 $sys_data_dir_arg/");

        ForgeConfig::set('tmp_dir', $this->getTmpDir());

        $this->git_dao = \Mockery::spy(GitDao::class);
        $this->git_dao->shouldReceive('save')->with(\Mockery::on(
            function (GitRepository $repository): bool {
                $this->last_saved_repository = $repository;
                return true;
            }
        ));
        $plugin_dao = \Mockery::spy(\PluginDao::class);
        ProjectManager::clearInstance();
        $this->project_manager = ProjectManager::instance();

        $this->logger = Mockery::mock(\Psr\Log\LoggerInterface::class);
        $this->logger->shouldReceive('debug');
        $this->git_plugin  = new GitPlugin(1);
        $this->git_factory = new GitRepositoryFactory($this->git_dao, $this->project_manager);

        $this->ugroup_dao     = \Mockery::spy(\UGroupDao::class);
        $this->ugroup_manager = new UGroupManager($this->ugroup_dao, \Mockery::spy(\EventManager::class));

        $this->git_systemeventmanager        = \Mockery::spy(\Git_SystemEventManager::class);
        $this->event_manager                 = \Mockery::spy(\EventManager::class);
        $this->fine_grained_updater          = \Mockery::spy(\Tuleap\Git\Permissions\FineGrainedUpdater::class);
        $this->regexp_fine_grained_retriever = \Mockery::spy(\Tuleap\Git\Permissions\RegexpFineGrainedRetriever::class);
        $this->regexp_fine_grained_enabler   = \Mockery::spy(\Tuleap\Git\Permissions\RegexpFineGrainedEnabler::class);
        $this->fine_grained_factory          = \Mockery::spy(\Tuleap\Git\Permissions\FineGrainedPermissionFactory::class);
        $this->fine_grained_saver            = \Mockery::spy(\Tuleap\Git\Permissions\FineGrainedPermissionSaver::class);
        $this->xml_ugroup_retriever          = new XmlUgroupRetriever(
            $this->logger,
            $this->ugroup_manager
        );

        $this->git_manager = new GitRepositoryManager(
            $this->git_factory,
            $this->git_systemeventmanager,
            $this->git_dao,
            $this->getTmpDir(),
            \Mockery::spy(\Tuleap\Git\Permissions\FineGrainedPermissionReplicator::class),
            \Mockery::spy(\ProjectHistoryDao::class),
            \Mockery::spy(\Tuleap\Git\Permissions\HistoryValueFormatter::class),
            $this->event_manager
        );

        $restricted_plugin_dao = \Mockery::spy(\RestrictedPluginDao::class);
        $plugin_factory        = new PluginFactory($plugin_dao, new PluginResourceRestrictor($restricted_plugin_dao));

        $plugin_manager = new PluginManager(
            $plugin_factory,
            new SiteCache($this->logger),
            new ForgeUpgradeConfig(new ForgeUpgrade($this->createMock(\PDO::class), new NullLogger())),
            \Tuleap\Markdown\CommonMarkInterpreter::build(\Codendi_HTMLPurifier::instance())
        );

        PluginManager::setInstance($plugin_manager);

        $this->permission_dao = \Mockery::spy(\PermissionsDao::class);
        $permissions_manager  = new PermissionsManager($this->permission_dao);
        $git_gitolite_driver  = new Git_GitoliteDriver(
            $this->logger,
            \Mockery::spy(\Git_GitRepositoryUrlManager::class),
            $this->git_dao,
            $this->git_plugin,
            \Mockery::spy(\Tuleap\Git\BigObjectAuthorization\BigObjectAuthorizationManager::class),
            null,
            null,
            \Mockery::spy(\Git_Gitolite_ConfigPermissionsSerializer::class),
            null,
            null,
        );
        $this->user_finder    = \Mockery::spy(XMLImportHelper::class);

        $this->configure_artifact_closure = $this->createMock(ConfigureAllowArtifactClosure::class);

        $gitolite       = new Git_Backend_Gitolite($git_gitolite_driver, \Mockery::spy(GitoliteAccessURLGenerator::class), new DefaultBranchUpdateExecutorStub(), $this->logger);
        $this->importer = new GitXmlImporter(
            $this->logger,
            $this->git_manager,
            $this->git_factory,
            $gitolite,
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
            $this->user_finder,
            $this->configure_artifact_closure,
        );

        $userManager = \Mockery::spy(\UserManager::class);
        $userManager->shouldReceive('getUserById')->andReturns(new PFUser());
        UserManager::setInstance($userManager);

        $this->permission_dao->shouldReceive('clearPermission')->andReturns(true);
        $this->permission_dao->shouldReceive('addPermission')->andReturns(true);
        $this->git_dao->shouldReceive('getProjectRepositoryList')->andReturns([]);

        copy(__DIR__ . '/_fixtures/stable_repo_one_commit.bundle', $this->getTmpDir() . DIRECTORY_SEPARATOR . 'stable.bundle');
        $this->project = $this->project_manager->getProjectFromDbRow(
            ['group_id' => 123, 'unix_group_name' => 'test_project', 'access' => Project::ACCESS_PUBLIC]
        );
    }

    protected function tearDown(): void
    {
        //revert gitolite driver setAdminPath in its builder
        chdir($this->old_cwd);
    }

    public function testItShouldCreateOneEmptyRepository(): void
    {
        $xml         = <<<XML
            <project>
                <git>
                    <repository bundle-path="" name="empty"/>
                </git>
            </project>
XML;
        $xml_element = new SimpleXMLElement($xml);
        $res         = $this->importer->import(
            new \Tuleap\Project\XML\Import\ImportConfig(),
            $this->project,
            \Mockery::spy(\PFUser::class),
            $xml_element,
            $this->getTmpDir()
        );

        $this->assertEquals($this->last_saved_repository->getName(), 'empty');

        $iterator      = new DirectoryIterator(ForgeConfig::get('sys_data_dir') . '/gitolite/repositories/test_project');
        $empty_is_here = false;
        foreach ($iterator as $it) {
            if ($it->getFilename() == 'empty') {
                $empty_is_here = true;
            }
        }
        $this->assertFalse($empty_is_here);
    }

    public function testItShouldNotChangeAllowArtifactClosureOptionIfAttributeIsNotPresent(): void
    {
        $xml = <<<XML
            <project>
                <git>
                    <repository bundle-path="" name="empty"/>
                </git>
            </project>
            XML;

        $this->configure_artifact_closure
            ->expects(self::never())
            ->method('forbidArtifactClosureForRepository');

        $this->configure_artifact_closure
            ->expects(self::never())
            ->method('allowArtifactClosureForRepository');

        $this->importer->import(
            new \Tuleap\Project\XML\Import\ImportConfig(),
            $this->project,
            \Mockery::spy(\PFUser::class),
            new SimpleXMLElement($xml),
            $this->getTmpDir()
        );
    }

    public function testItShouldAllowArtifactClosure(): void
    {
        $xml = <<<XML
            <project>
                <git>
                    <repository bundle-path="" name="empty" allow_artifact_closure="1"/>
                </git>
            </project>
            XML;

        $this->configure_artifact_closure
            ->expects(self::never())
            ->method('forbidArtifactClosureForRepository');

        $this->configure_artifact_closure
            ->expects(self::once())
            ->method('allowArtifactClosureForRepository');

        $this->importer->import(
            new \Tuleap\Project\XML\Import\ImportConfig(),
            $this->project,
            \Mockery::spy(\PFUser::class),
            new SimpleXMLElement($xml),
            $this->getTmpDir()
        );
    }

    public function testItShouldForbidArtifactClosure(): void
    {
        $xml = <<<XML
            <project>
                <git>
                    <repository bundle-path="" name="empty" allow_artifact_closure="0"/>
                </git>
            </project>
            XML;

        $this->configure_artifact_closure
            ->expects(self::once())
            ->method('forbidArtifactClosureForRepository');

        $this->configure_artifact_closure
            ->expects(self::never())
            ->method('allowArtifactClosureForRepository');

        $this->importer->import(
            new \Tuleap\Project\XML\Import\ImportConfig(),
            $this->project,
            \Mockery::spy(\PFUser::class),
            new SimpleXMLElement($xml),
            $this->getTmpDir()
        );
    }

    public function testItShouldImportOneRepositoryWithOneCommit(): void
    {
        $xml         = <<<XML
            <project>
                <git>
                    <repository bundle-path="stable.bundle" name="stable"/>
                </git>
            </project>
XML;
        $xml_element = new SimpleXMLElement($xml);
        $res         = $this->importer->import(
            new \Tuleap\Project\XML\Import\ImportConfig(),
            $this->project,
            \Mockery::spy(\PFUser::class),
            $xml_element,
            $this->getTmpDir()
        );

        $sys_data_dir_arg = escapeshellarg(ForgeConfig::get('sys_data_dir'));
        shell_exec(\Git_Exec::getGitCommand() . " config --global --add safe.directory $sys_data_dir_arg/gitolite/repositories/test_project/stable.git");
        $nb_commit = shell_exec("cd $sys_data_dir_arg/gitolite/repositories/test_project/stable.git && " . \Git_Exec::getGitCommand() . " log --oneline| wc -l");
        $this->assertEquals(1, intval($nb_commit));
    }

    public function testItShouldImportTwoRepositoriesWithOneCommit(): void
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
        $sys_data_dir_arg = escapeshellarg(ForgeConfig::get('sys_data_dir'));
        shell_exec(\Git_Exec::getGitCommand() . " config --global --add safe.directory $sys_data_dir_arg/gitolite/repositories/test_project/stable.git");
        $nb_commit_stable = shell_exec("cd $sys_data_dir_arg/gitolite/repositories/test_project/stable.git && " . \Git_Exec::getGitCommand() . " log --oneline| wc -l");
        $this->assertEquals(1, (int) $nb_commit_stable);

        shell_exec(\Git_Exec::getGitCommand() . " config --global --add safe.directory $sys_data_dir_arg/gitolite/repositories/test_project/stable2.git");
        $nb_commit_stable2 = shell_exec("cd $sys_data_dir_arg/gitolite/repositories/test_project/stable2.git && " . \Git_Exec::getGitCommand() . " log --oneline| wc -l");
        $this->assertEquals(1, (int) $nb_commit_stable2);
    }

    public function testItShouldImportStaticUgroups(): void
    {
        //allow anonymous to avoid overriding of the ugroups by PermissionsUGroupMapper when adding/updating permissions
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);

        $xml    = <<<XML
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
        $result = \Mockery::spy(\DataAccessResult::class);
        $result->shouldReceive('getRow')->andReturns(false);
        $this->ugroup_dao->shouldReceive('searchByGroupIdAndName')->andReturns($result);
        $this->permission_dao->shouldReceive('addPermission')->with(Git::PERM_READ, \Mockery::any(), 3)->ordered();
        $this->permission_dao->shouldReceive('addPermission')->with(Git::PERM_WRITE, \Mockery::any(), 3)->ordered();
        $this->permission_dao->shouldReceive('addPermission')->with(Git::PERM_WPLUS, \Mockery::any(), 4)->ordered();
        self::assertTrue($this->import(new SimpleXMLElement($xml)));
    }

    public function testItShouldImportLegacyPermissions(): void
    {
        //allow anonymous to avoid overriding of the ugroups by PermissionsUGroupMapper when adding/updating permissions
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);

        $xml    = <<<XML
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
        $result = \Mockery::spy(\DataAccessResult::class);
        $result->shouldReceive('getRow')->andReturns(false);
        $this->ugroup_dao->shouldReceive('searchByGroupIdAndName')->andReturns($result);
        $this->permission_dao->shouldReceive('addPermission')->with(Git::PERM_READ, \Mockery::any(), 3)->ordered();
        $this->permission_dao->shouldReceive('addPermission')->with(Git::PERM_WRITE, \Mockery::any(), 3)->ordered();
        $this->permission_dao->shouldReceive('addPermission')->with(Git::PERM_WPLUS, \Mockery::any(), 4)->ordered();
        self::assertTrue($this->import(new SimpleXMLElement($xml)));
    }

    public function testItShouldUpdateConfViaSystemEvents(): void
    {
        $xml = <<<XML
            <project>
                <git>
                    <repository bundle-path="stable.bundle" name="stable"/>
                </git>
            </project>
XML;
        $this->git_systemeventmanager->shouldReceive('queueProjectsConfigurationUpdate')->with([123])->atLeast()->once();
        $this->import(new SimpleXMLElement($xml));
    }

    public function testItShouldImportDescription(): void
    {
        $xml = <<<XML
            <project>
                <git>
                    <repository bundle-path="stable.bundle" name="stable" description="description stable"/>
                </git>
            </project>
XML;
        $this->import(new SimpleXMLElement($xml));
        $this->assertEquals('description stable', $this->last_saved_repository->getDescription());
    }

    public function testItShouldImportDefaultDescription(): void
    {
        $xml = <<<XML
            <project>
                <git>
                    <repository bundle-path="stable.bundle" name="stable"/>
                </git>
            </project>
XML;
        $this->import(new SimpleXMLElement($xml));
        $this->assertEquals(GitRepository::DEFAULT_DESCRIPTION, $this->last_saved_repository->getDescription());
    }

    public function testItShouldAtLeastSetProjectsAdminAsGitAdmins(): void
    {
        $xml = <<<XML
            <project>
                <git>
                    <repository bundle-path="stable.bundle" name="stable"/>
                </git>
            </project>
XML;
        $this->permission_dao->shouldReceive('addPermission')->with(Git::PERM_ADMIN, $this->project->getId(), 4);
        self::assertTrue($this->import(new SimpleXMLElement($xml)));
    }

    public function testItShouldImportGitAdmins(): void
    {
        $xml    = <<<XML
            <project>
                <git>
                    <repository bundle-path="stable.bundle" name="stable"/>
                    <ugroups-admin>
                        <ugroup>project_members</ugroup>
                    </ugroups-admin>
                </git>
            </project>
XML;
        $result = \Mockery::spy(\DataAccessResult::class);
        $result->shouldReceive('getRow')->andReturns(false);
        $this->ugroup_dao->shouldReceive('searchByGroupIdAndName')->andReturns($result);

        $this->permission_dao->shouldReceive('addPermission')->with(Git::PERM_ADMIN, $this->project->getId(), 3)->ordered();
        $this->permission_dao->shouldReceive('addPermission')->with(Git::PERM_ADMIN, $this->project->getId(), 4)->ordered();
        self::assertTrue($this->import(new SimpleXMLElement($xml)));
    }

    public function testItShouldImportReferences(): void
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
        $this->ugroup_dao->shouldReceive('searchByGroupIdAndName')->andReturns(\TestHelper::emptyDar());

        $this->event_manager->shouldReceive('processEvent')->times(3);

        $this->import(new SimpleXMLElement($xml));
    }

    public function testItShouldNotImportFineGrainedPermissionsWhenNoNode(): void
    {
        $xml = <<<XML
            <project>
                <git>
                    <repository bundle-path="stable.bundle" name="stable">
                    </repository>
                </git>
            </project>
XML;
        $this->ugroup_dao->shouldReceive('searchByGroupIdAndName')->andReturns(\TestHelper::emptyDar());

        $this->fine_grained_updater->shouldReceive('enableRepository')->never();
        $this->regexp_fine_grained_enabler->shouldReceive('enableForRepository')->never();

        $this->import(new SimpleXMLElement($xml));
    }

    public function testItShouldImportFineGrainedPermissions(): void
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
        $this->logger->shouldReceive('warning')->once();
        $this->ugroup_dao->shouldReceive('searchByGroupIdAndName')->andReturns(\TestHelper::emptyDar());

        $this->fine_grained_updater->shouldReceive('enableRepository')->once();
        $this->regexp_fine_grained_enabler->shouldReceive('enableForRepository')->never();

        $this->import(new SimpleXMLElement($xml));
    }

    public function testItShouldImportRegexpFineGrainedPermissions(): void
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
        $this->ugroup_dao->shouldReceive('searchByGroupIdAndName')->andReturns(\TestHelper::emptyDar());
        $this->regexp_fine_grained_retriever->shouldReceive('areRegexpActivatedAtSiteLevel')->andReturns(true);

        $this->fine_grained_updater->shouldReceive('enableRepository')->once();
        $this->regexp_fine_grained_enabler->shouldReceive('enableForRepository')->once();

        $this->import(new SimpleXMLElement($xml));
    }

    public function testItShouldNotImportRegexpFineGrainedPermissionsIfNotAvailableAtSiteLevel(): void
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
        $this->ugroup_dao->shouldReceive('searchByGroupIdAndName')->andReturns(\TestHelper::emptyDar());
        $this->regexp_fine_grained_retriever->shouldReceive('areRegexpActivatedAtSiteLevel')->andReturns(false);

        $this->logger->shouldReceive('warning')->once();
        $this->fine_grained_updater->shouldReceive('enableRepository')->once();
        $this->regexp_fine_grained_enabler->shouldReceive('enableForRepository')->never();

        $this->import(new SimpleXMLElement($xml));
    }

    public function testItShouldImportRegexpFineGrainedPermissionsEvenWhenFineGrainedAreNotUsed(): void
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
        $this->ugroup_dao->shouldReceive('searchByGroupIdAndName')->andReturns(\TestHelper::emptyDar());
        $this->regexp_fine_grained_retriever->shouldReceive('areRegexpActivatedAtSiteLevel')->andReturns(true);

        $this->fine_grained_updater->shouldReceive('enableRepository')->never();
        $this->regexp_fine_grained_enabler->shouldReceive('enableForRepository')->once();

        $this->import(new SimpleXMLElement($xml));
    }

    public function testItImportPatternsWithoutRegexp(): void
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
            [],
            []
        );

        $this->ugroup_dao->shouldReceive('searchByGroupIdAndName')->andReturns(\TestHelper::emptyDar());
        $this->regexp_fine_grained_retriever->shouldReceive('areRegexpActivatedAtSiteLevel')->andReturns(true);
        $this->fine_grained_factory->shouldReceive('getFineGrainedPermissionFromXML')->andReturns($representation);

        $this->fine_grained_saver->shouldReceive('saveBranchPermission')->once();
        $this->fine_grained_saver->shouldReceive('saveTagPermission')->once();

        $this->import(new SimpleXMLElement($xml));
    }

    public function testItDoesNotImportRegexpWhenNotActivated(): void
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
            [],
            []
        );

        $this->logger->shouldReceive('warning')->once();
        $this->ugroup_dao->shouldReceive('searchByGroupIdAndName')->andReturns(\TestHelper::emptyDar());
        $this->regexp_fine_grained_retriever->shouldReceive('areRegexpActivatedAtSiteLevel')->andReturns(true);
        $this->fine_grained_factory->shouldReceive('getFineGrainedPermissionFromXML')->andReturns($representation, false, $representation);

        $this->fine_grained_saver->shouldReceive('saveBranchPermission')->once();
        $this->fine_grained_saver->shouldReceive('saveTagPermission')->once();

        $this->import(new SimpleXMLElement($xml));
    }

    public function testItImportsRegexpPatterns(): void
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
            [],
            []
        );

        $representation_02 = new FineGrainedPermission(
            0,
            1,
            '*',
            [],
            []
        );

        $this->ugroup_dao->shouldReceive('searchByGroupIdAndName')->andReturns(\TestHelper::emptyDar());
        $this->regexp_fine_grained_retriever->shouldReceive('areRegexpActivatedAtSiteLevel')->andReturns(true);
        $this->fine_grained_factory->shouldReceive('getFineGrainedPermissionFromXML')->andReturns($representation_01)->ordered();
        $this->fine_grained_factory->shouldReceive('getFineGrainedPermissionFromXML')->andReturns($representation_02)->ordered();
        $this->fine_grained_factory->shouldReceive('getFineGrainedPermissionFromXML')->andReturns($representation_01)->ordered();

        $this->fine_grained_saver->shouldReceive('saveBranchPermission')->times(2);
        $this->fine_grained_saver->shouldReceive('saveTagPermission')->once();

        $this->import(new SimpleXMLElement($xml));
    }

    private function import($xml)
    {
        return $this->importer->import(new \Tuleap\Project\XML\Import\ImportConfig(), $this->project, \Mockery::spy(\PFUser::class), $xml, $this->getTmpDir());
    }

    public function testItShouldImportGitLastPushData(): void
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

        $this->user_finder->shouldReceive('getUser')->atLeast()->once()->andReturn(new PFUser(['user_id' => 102, 'language_id' => 'en']));
        $this->git_dao->shouldReceive('logGitPush')->once();
        $this->import(new SimpleXMLElement($xml));
        $this->assertEquals(GitRepository::DEFAULT_DESCRIPTION, $this->last_saved_repository->getDescription());
    }
}
