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

use Codendi_HTMLPurifier;
use ColinODell\PsrTestLogger\TestLogger;
use DirectoryIterator;
use EventManager;
use ForgeAccess;
use ForgeConfig;
use ForgeUpgradeConfig;
use Git;
use Git_Backend_Gitolite;
use Git_Exec;
use Git_Gitolite_ConfigPermissionsSerializer;
use Git_GitoliteDriver;
use Git_GitRepositoryUrlManager;
use Git_SystemEventManager;
use GitDao;
use GitPlugin;
use GitRepository;
use GitRepositoryFactory;
use GitRepositoryManager;
use GitXmlImporter;
use PermissionsDao;
use PermissionsManager;
use PHPUnit\Framework\MockObject\MockObject;
use PluginDao;
use PluginFactory;
use PluginManager;
use PluginResourceRestrictor;
use Project;
use ProjectHistoryDao;
use ProjectManager;
use Psr\Log\NullLogger;
use RestrictedPluginDao;
use SimpleXMLElement;
use SiteCache;
use System_Command;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\DB\DBFactory;
use Tuleap\ForgeUpgrade\ForgeUpgrade;
use Tuleap\Git\BigObjectAuthorization\BigObjectAuthorizationManager;
use Tuleap\Git\DefaultBranch\DefaultBranchUpdateExecutorAsGitoliteUser;
use Tuleap\Git\Gitolite\GitoliteAccessURLGenerator;
use Tuleap\Git\Permissions\FineGrainedPermission;
use Tuleap\Git\Permissions\FineGrainedPermissionFactory;
use Tuleap\Git\Permissions\FineGrainedPermissionReplicator;
use Tuleap\Git\Permissions\FineGrainedPermissionSaver;
use Tuleap\Git\Permissions\FineGrainedUpdater;
use Tuleap\Git\Permissions\HistoryValueFormatter;
use Tuleap\Git\Permissions\RegexpFineGrainedEnabler;
use Tuleap\Git\Permissions\RegexpFineGrainedRetriever;
use Tuleap\Git\Repository\Settings\ArtifactClosure\ConfigureAllowArtifactClosure;
use Tuleap\Git\SystemEvent\OngoingDeletionDAO;
use Tuleap\Git\Tests\Stub\DefaultBranch\DefaultBranchUpdateExecutorStub;
use Tuleap\GlobalLanguageMock;
use Tuleap\Markdown\CommonMarkInterpreter;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;
use UGroupDao;
use UGroupManager;
use UserManager;
use XMLImportHelper;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitXmlImporterTest extends TestIntegrationTestCase
{
    use TemporaryTestDirectory;
    use GlobalLanguageMock;

    private XMLImportHelper&MockObject $user_finder;
    private GitXmlImporter $importer;
    private GitDao&MockObject $git_dao;
    private Git_SystemEventManager&MockObject $git_systemeventmanager;
    private PermissionsDao&MockObject $permission_dao;
    private string $old_cwd;
    private UGroupDao&MockObject $ugroup_dao;
    private EventManager&MockObject $event_manager;
    private ?GitRepository $last_saved_repository;
    private TestLogger $logger;
    private ConfigureAllowArtifactClosure&MockObject $configure_artifact_closure;
    private FineGrainedUpdater&MockObject $fine_grained_updater;
    private RegexpFineGrainedEnabler&MockObject $regexp_fine_grained_enabler;
    private RegexpFineGrainedRetriever&MockObject $regexp_fine_grained_retriever;
    private FineGrainedPermissionFactory&MockObject $fine_grained_factory;
    private FineGrainedPermissionSaver&MockObject $fine_grained_saver;
    private Project $project;

    protected function setUp(): void
    {
        $this->old_cwd  = getcwd();
        $system_command = new System_Command();

        $sys_data_dir = $this->getTmpDir();
        ForgeConfig::set('sys_data_dir', $sys_data_dir);
        mkdir("$sys_data_dir/gitolite/admin/", 0777, true);
        mkdir("$sys_data_dir/gitolite/repositories/test_project", 0777, true);
        $sys_data_dir_arg = escapeshellarg($sys_data_dir);
        $system_command->exec("chmod -R 0777 $sys_data_dir_arg/");

        ForgeConfig::set('tmp_dir', $this->getTmpDir());

        $this->git_dao = $this->createMock(GitDao::class);
        $this->git_dao->method('save')->with(self::callback(
            function (GitRepository $repository): bool {
                $this->last_saved_repository = $repository;
                return true;
            }
        ));
        $plugin_dao = $this->createMock(PluginDao::class);
        ProjectManager::clearInstance();
        $project_manager = ProjectManager::instance();

        $this->logger = new TestLogger();
        $git_plugin   = new GitPlugin(1);
        $git_factory  = new GitRepositoryFactory($this->git_dao, $project_manager);

        $this->ugroup_dao    = $this->createMock(UGroupDao::class);
        $this->event_manager = $this->createMock(EventManager::class);
        $ugroup_manager      = new UGroupManager($this->ugroup_dao, $this->event_manager);

        $this->git_systemeventmanager        = $this->createMock(Git_SystemEventManager::class);
        $this->fine_grained_updater          = $this->createMock(FineGrainedUpdater::class);
        $this->regexp_fine_grained_retriever = $this->createMock(RegexpFineGrainedRetriever::class);
        $this->regexp_fine_grained_enabler   = $this->createMock(RegexpFineGrainedEnabler::class);
        $this->fine_grained_factory          = $this->createMock(FineGrainedPermissionFactory::class);
        $this->fine_grained_saver            = $this->createMock(FineGrainedPermissionSaver::class);
        $xml_ugroup_retriever                = new XmlUgroupRetriever($this->logger, $ugroup_manager);

        $ongoing_deletion_dao = $this->createMock(OngoingDeletionDAO::class);
        $ongoing_deletion_dao->method('isADeletionForPathOngoingInProject')->willReturn(false);

        $git_manager = new GitRepositoryManager(
            $git_factory,
            $this->git_systemeventmanager,
            $this->git_dao,
            $this->getTmpDir(),
            $this->createMock(FineGrainedPermissionReplicator::class),
            $this->createMock(ProjectHistoryDao::class),
            $this->createMock(HistoryValueFormatter::class),
            $this->event_manager,
            $ongoing_deletion_dao,
        );

        $restricted_plugin_dao = $this->createMock(RestrictedPluginDao::class);
        $plugin_factory        = new PluginFactory($plugin_dao, new PluginResourceRestrictor($restricted_plugin_dao));

        $plugin_manager = new PluginManager(
            $plugin_factory,
            new SiteCache($this->logger),
            new ForgeUpgradeConfig(new ForgeUpgrade(DBFactory::getMainTuleapDBConnection()->getDB()->getPdo(), new NullLogger(), new DatabaseUUIDV7Factory())),
            CommonMarkInterpreter::build(Codendi_HTMLPurifier::instance())
        );

        PluginManager::setInstance($plugin_manager);

        $this->permission_dao = $this->createMock(PermissionsDao::class);
        $permissions_manager  = new PermissionsManager($this->permission_dao);
        $git_gitolite_driver  = new Git_GitoliteDriver(
            $this->logger,
            $this->createMock(Git_GitRepositoryUrlManager::class),
            $this->git_dao,
            $git_plugin,
            $this->createMock(BigObjectAuthorizationManager::class),
            null,
            null,
            $this->createMock(Git_Gitolite_ConfigPermissionsSerializer::class),
            null,
            null,
        );
        $this->user_finder    = $this->createMock(XMLImportHelper::class);

        $this->configure_artifact_closure = $this->createMock(ConfigureAllowArtifactClosure::class);

        $gitolite       = new Git_Backend_Gitolite($git_gitolite_driver, $this->createMock(GitoliteAccessURLGenerator::class), new DefaultBranchUpdateExecutorStub(), $this->logger);
        $this->importer = new GitXmlImporter(
            $this->logger,
            $git_manager,
            $git_factory,
            $gitolite,
            $this->git_systemeventmanager,
            $permissions_manager,
            $this->event_manager,
            $this->fine_grained_updater,
            $this->regexp_fine_grained_retriever,
            $this->regexp_fine_grained_enabler,
            $this->fine_grained_factory,
            $this->fine_grained_saver,
            $xml_ugroup_retriever,
            $this->git_dao,
            $this->user_finder,
            $this->configure_artifact_closure,
            new GitXMLImportDefaultBranchRetriever(),
            new DefaultBranchUpdateExecutorAsGitoliteUser(),
        );

        $user_manager = $this->createMock(UserManager::class);
        $user_manager->method('getUserById')->willReturn(UserTestBuilder::buildWithDefaults());
        UserManager::setInstance($user_manager);

        $this->permission_dao->method('clearPermission')->willReturn(true);
        $this->permission_dao->method('addPermission')->willReturn(true);
        $this->permission_dao->method('addHistory');
        $this->git_dao->method('getProjectRepositoryList')->willReturn([]);

        copy(__DIR__ . '/_fixtures/stable_repo_one_commit.bundle', $this->getTmpDir() . DIRECTORY_SEPARATOR . 'stable.bundle');
        $this->project = $project_manager->getProjectFromDbRow(
            ['group_id' => 123, 'unix_group_name' => 'test_project', 'access' => Project::ACCESS_PUBLIC]
        );
        $this->git_systemeventmanager->method('queueRepositoryUpdate');
        $this->git_systemeventmanager->method('queueProjectsConfigurationUpdate');
        $this->event_manager->method('processEvent');
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
        $this->importer->import(
            new ImportConfig(),
            $this->project,
            UserTestBuilder::buildWithDefaults(),
            $xml_element,
            $this->getTmpDir()
        );

        self::assertEquals('empty', $this->last_saved_repository->getName());

        $iterator      = new DirectoryIterator(ForgeConfig::get('sys_data_dir') . '/gitolite/repositories/test_project');
        $empty_is_here = false;
        foreach ($iterator as $it) {
            if ($it->getFilename() == 'empty') {
                $empty_is_here = true;
            }
        }
        self::assertFalse($empty_is_here);
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
            new ImportConfig(),
            $this->project,
            UserTestBuilder::buildWithDefaults(),
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
            new ImportConfig(),
            $this->project,
            UserTestBuilder::buildWithDefaults(),
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
            new ImportConfig(),
            $this->project,
            UserTestBuilder::buildWithDefaults(),
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
        $this->importer->import(
            new ImportConfig(),
            $this->project,
            UserTestBuilder::buildWithDefaults(),
            $xml_element,
            $this->getTmpDir()
        );

        $sys_data_dir_arg = escapeshellarg(ForgeConfig::get('sys_data_dir'));
        shell_exec(Git_Exec::getGitCommand() . " config --global --add safe.directory $sys_data_dir_arg/gitolite/repositories/test_project/stable.git");
        $nb_commit = shell_exec("cd $sys_data_dir_arg/gitolite/repositories/test_project/stable.git && " . Git_Exec::getGitCommand() . ' log --oneline| wc -l');
        self::assertEquals(1, intval($nb_commit));
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
        shell_exec(Git_Exec::getGitCommand() . " config --global --add safe.directory $sys_data_dir_arg/gitolite/repositories/test_project/stable.git");
        $nb_commit_stable = shell_exec("cd $sys_data_dir_arg/gitolite/repositories/test_project/stable.git && " . Git_Exec::getGitCommand() . ' log --oneline| wc -l');
        self::assertEquals(1, (int) $nb_commit_stable);

        shell_exec(Git_Exec::getGitCommand() . " config --global --add safe.directory $sys_data_dir_arg/gitolite/repositories/test_project/stable2.git");
        $nb_commit_stable2 = shell_exec("cd $sys_data_dir_arg/gitolite/repositories/test_project/stable2.git && " . Git_Exec::getGitCommand() . ' log --oneline| wc -l');
        self::assertEquals(1, (int) $nb_commit_stable2);
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
        $result = [];
        $this->ugroup_dao->method('searchByGroupIdAndName')->willReturn($result);
        $this->permission_dao->method('addPermission')
            ->willReturnMap([
                [Git::PERM_READ, self::anything(), 3, true],
                [Git::PERM_WRITE, self::anything(), 3, true],
                [Git::PERM_WPLUS, self::anything(), 4, true],
            ]);
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
        $result = [];
        $this->ugroup_dao->method('searchByGroupIdAndName')->willReturn($result);
        $this->permission_dao->method('addPermission')
            ->willReturnMap([
                [Git::PERM_READ, self::anything(), 3, true],
                [Git::PERM_WRITE, self::anything(), 3, true],
                [Git::PERM_WPLUS, self::anything(), 4, true],
            ]);
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
        $this->git_systemeventmanager->expects(self::atLeastOnce())->method('queueProjectsConfigurationUpdate')->with([123]);
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
        self::assertEquals('description stable', $this->last_saved_repository->getDescription());
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
        self::assertEquals(GitRepository::DEFAULT_DESCRIPTION, $this->last_saved_repository->getDescription());
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
        $this->permission_dao->method('addPermission')->with(Git::PERM_ADMIN, $this->project->getId(), 4);
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
        $result = [];
        $this->ugroup_dao->method('searchByGroupIdAndName')->willReturn($result);

        $this->permission_dao->method('addPermission')->with(Git::PERM_ADMIN, $this->project->getId(), 3);

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
        $this->ugroup_dao->method('searchByGroupIdAndName')->willReturn([]);

        $this->event_manager->expects(self::exactly(3))->method('processEvent');

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
        $this->ugroup_dao->method('searchByGroupIdAndName')->willReturn([]);

        $this->fine_grained_updater->expects(self::never())->method('enableRepository');
        $this->regexp_fine_grained_enabler->expects(self::never())->method('enableForRepository');

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
        $this->ugroup_dao->method('searchByGroupIdAndName')->willReturn([]);

        $this->fine_grained_updater->expects(self::once())->method('enableRepository');
        $this->regexp_fine_grained_enabler->expects(self::never())->method('enableForRepository');
        $this->regexp_fine_grained_retriever->method('areRegexpActivatedAtSiteLevel');

        $this->import(new SimpleXMLElement($xml));
        self::assertTrue($this->logger->hasWarningRecords());
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
        $this->ugroup_dao->method('searchByGroupIdAndName')->willReturn([]);
        $this->regexp_fine_grained_retriever->method('areRegexpActivatedAtSiteLevel')->willReturn(true);

        $this->fine_grained_updater->expects(self::once())->method('enableRepository');
        $this->regexp_fine_grained_enabler->expects(self::once())->method('enableForRepository');

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
        $this->ugroup_dao->method('searchByGroupIdAndName')->willReturn([]);
        $this->regexp_fine_grained_retriever->method('areRegexpActivatedAtSiteLevel')->willReturn(false);

        $this->fine_grained_updater->expects(self::once())->method('enableRepository');
        $this->regexp_fine_grained_enabler->expects(self::never())->method('enableForRepository');

        $this->import(new SimpleXMLElement($xml));
        self::assertTrue($this->logger->hasWarningRecords());
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
        $this->ugroup_dao->method('searchByGroupIdAndName')->willReturn([]);
        $this->regexp_fine_grained_retriever->method('areRegexpActivatedAtSiteLevel')->willReturn(true);

        $this->fine_grained_updater->expects(self::never())->method('enableRepository');
        $this->regexp_fine_grained_enabler->expects(self::once())->method('enableForRepository');

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

        $this->ugroup_dao->method('searchByGroupIdAndName')->willReturn([]);
        $this->regexp_fine_grained_retriever->method('areRegexpActivatedAtSiteLevel')->willReturn(true);
        $this->fine_grained_factory->method('getFineGrainedPermissionFromXML')->willReturn($representation);

        $this->fine_grained_saver->expects(self::once())->method('saveBranchPermission');
        $this->fine_grained_saver->expects(self::once())->method('saveTagPermission');
        $this->fine_grained_updater->method('enableRepository');

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

        $this->ugroup_dao->method('searchByGroupIdAndName')->willReturn([]);
        $this->regexp_fine_grained_retriever->method('areRegexpActivatedAtSiteLevel')->willReturn(true);
        $this->fine_grained_factory->method('getFineGrainedPermissionFromXML')->willReturn($representation, false, $representation);

        $this->fine_grained_saver->expects(self::once())->method('saveBranchPermission');
        $this->fine_grained_saver->expects(self::once())->method('saveTagPermission');
        $this->fine_grained_updater->method('enableRepository');

        $this->import(new SimpleXMLElement($xml));
        self::assertTrue($this->logger->hasWarningRecords());
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

        $this->ugroup_dao->method('searchByGroupIdAndName')->willReturn([]);
        $this->regexp_fine_grained_retriever->method('areRegexpActivatedAtSiteLevel')->willReturn(true);
        $this->fine_grained_factory->method('getFineGrainedPermissionFromXML')
            ->willReturnOnConsecutiveCalls($representation_01, $representation_02, $representation_01);

        $this->fine_grained_saver->expects(self::exactly(2))->method('saveBranchPermission');
        $this->fine_grained_saver->expects(self::once())->method('saveTagPermission');
        $this->fine_grained_updater->method('enableRepository');
        $this->regexp_fine_grained_enabler->method('enableForRepository');

        $this->import(new SimpleXMLElement($xml));
    }

    private function import(SimpleXMLElement $xml): bool
    {
        return $this->importer->import(new ImportConfig(), $this->project, UserTestBuilder::buildWithDefaults(), $xml, $this->getTmpDir());
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

        $this->user_finder->expects(self::atLeastOnce())->method('getUser')->willReturn(UserTestBuilder::buildWithId(102));
        $this->git_dao->expects(self::once())->method('logGitPush');
        $this->import(new SimpleXMLElement($xml));
        self::assertEquals(GitRepository::DEFAULT_DESCRIPTION, $this->last_saved_repository->getDescription());
    }
}
