<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

require_once __DIR__ . '/bootstrap.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class GitActionsDeleteTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    protected $git_actions;
    protected $project_id;
    protected $repository_id;
    protected $repository;
    protected $git_system_event_manager;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Git
     */
    private $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project_id    = 101;
        $this->repository_id = 69;

        $this->repository = \Mockery::spy(\GitRepository::class);
        $this->repository->shouldReceive('getId')->andReturns($this->repository_id);
        $this->repository->shouldReceive('getProjectId')->andReturns($this->project_id);

        $this->git_system_event_manager = \Mockery::spy(\Git_SystemEventManager::class);

        $this->controller = Mockery::mock(\Git::class)
            ->shouldReceive('getPlugin')
            ->andReturns(\Mockery::spy(\GitPlugin::class))
            ->getMock();

        $this->controller->shouldReceive('getUser');
        $this->controller->shouldReceive('getRequest');

        $git_repository_factory = \Mockery::spy(\GitRepositoryFactory::class);
        $git_repository_factory->shouldReceive('getRepositoryById')->with($this->repository_id)->andReturns($this->repository);

        $git_plugin = Mockery::mock(\GitPlugin::class)
            ->shouldReceive('areFriendlyUrlsActivated')
            ->andReturnFalse()
            ->getMock();

        $url_manager = new Git_GitRepositoryUrlManager($git_plugin);

        $this->git_actions = new GitActions(
            $this->controller,
            $this->git_system_event_manager,
            $git_repository_factory,
            \Mockery::spy(\GitRepositoryManager::class),
            \Mockery::spy(\Git_RemoteServer_GerritServerFactory::class),
            Mockery::mock(\Git_Driver_Gerrit_GerritDriverFactory::class)
                ->shouldReceive('getDriver')
                ->andReturn(\Mockery::spy(\Git_Driver_Gerrit::class))
                ->getMock(),
            \Mockery::spy(\Git_Driver_Gerrit_UserAccountManager::class),
            \Mockery::spy(\Git_Driver_Gerrit_ProjectCreator::class),
            \Mockery::spy(\Git_Driver_Gerrit_Template_TemplateFactory::class),
            \Mockery::spy(\ProjectManager::class),
            \Mockery::spy(\GitPermissionsManager::class),
            $url_manager,
            \Mockery::spy(\Psr\Log\LoggerInterface::class),
            \Mockery::spy(\ProjectHistoryDao::class),
            \Mockery::spy(\Tuleap\Git\RemoteServer\Gerrit\MigrationHandler::class),
            \Mockery::spy(\Tuleap\Git\RemoteServer\GerritCanMigrateChecker::class),
            \Mockery::spy(\Tuleap\Git\Permissions\FineGrainedUpdater::class),
            \Mockery::spy(\Tuleap\Git\Permissions\FineGrainedPermissionSaver::class),
            \Mockery::spy(\Tuleap\Git\Permissions\FineGrainedRetriever::class),
            \Mockery::spy(\Tuleap\Git\Permissions\HistoryValueFormatter::class),
            \Mockery::spy(\Tuleap\Git\Permissions\PermissionChangesDetector::class),
            \Mockery::spy(\Tuleap\Git\Permissions\RegexpFineGrainedEnabler::class),
            \Mockery::spy(\Tuleap\Git\Permissions\RegexpFineGrainedDisabler::class),
            \Mockery::spy(\Tuleap\Git\Permissions\RegexpPermissionFilter::class),
            \Mockery::spy(\Tuleap\Git\Permissions\RegexpFineGrainedRetriever::class),
            \Mockery::spy(\Tuleap\Git\Notifications\UsersToNotifyDao::class),
            \Mockery::spy(\Tuleap\Git\Notifications\UgroupsToNotifyDao::class),
            \Mockery::spy(\UGroupManager::class)
        );
    }

    public function testItMarksRepositoryAsDeleted(): void
    {
        $this->controller->shouldReceive('addInfo');
        $this->controller->shouldReceive('redirect')->once();

        $this->repository->shouldReceive('canBeDeleted')->andReturns(true);
        $this->repository->shouldReceive('markAsDeleted')->once();

        $this->git_actions->deleteRepository($this->project_id, $this->repository_id);
    }

    public function testItTriggersASystemEventForPhysicalRemove(): void
    {
        $this->controller->shouldReceive('addInfo');
        $this->controller->shouldReceive('redirect')->once();

        $this->repository->shouldReceive('canBeDeleted')->andReturns(true);
        $this->repository->shouldReceive('getBackend')->andReturns(\Mockery::spy(\Git_Backend_Gitolite::class));

        $this->git_system_event_manager->shouldReceive('queueRepositoryDeletion')->with($this->repository)->once();

        $this->git_actions->deleteRepository($this->project_id, $this->repository_id);
    }

    public function testItDoesntDeleteWhenRepositoryCannotBeDeleted(): void
    {
        $this->controller->shouldReceive('addError');
        $this->controller->shouldReceive('redirect')->once();

        $this->repository->shouldReceive('canBeDeleted')->andReturns(false);
        $this->repository->shouldReceive('markAsDeleted')->never();

        $this->git_system_event_manager->shouldReceive('queueRepositoryDeletion')->never();

        $this->git_actions->deleteRepository($this->project_id, $this->repository_id);
    }
}
