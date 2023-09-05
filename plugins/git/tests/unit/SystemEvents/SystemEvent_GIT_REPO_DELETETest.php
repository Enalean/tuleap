<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\Git\Notifications\UsersToNotifyDao;
use Tuleap\Git\Notifications\UgroupsToNotifyDao;

require_once __DIR__ . '/../bootstrap.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class SystemEvent_GIT_REPO_DELETETest extends \Tuleap\Test\PHPUnit\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private $project_id;
    private $repository_id;
    private $repository;
    private $repository_factory;
    private $ugroups_to_notify_dao;
    private $users_to_notify_dao;

    /** @var SystemEvent_GIT_REPO_DELETE */
    private $event;
    /**
     * @var a|\Mockery\MockInterface|EventManager
     */
    private $event_manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project_id    = 101;
        $this->repository_id = 69;

        $this->repository = \Mockery::spy(\GitRepository::class);
        $this->repository->shouldReceive('getId')->andReturns($this->repository_id);
        $this->repository->shouldReceive('getProjectId')->andReturns($this->project_id);

        $this->repository_factory = \Mockery::spy(\GitRepositoryFactory::class);
        $this->repository_factory->shouldReceive('getDeletedRepository')->with($this->repository_id)->andReturns($this->repository);

        $this->ugroups_to_notify_dao = \Mockery::spy(UgroupsToNotifyDao::class);
        $this->users_to_notify_dao   = \Mockery::spy(UsersToNotifyDao::class);
        $this->event_manager         = \Mockery::spy(\EventManager::class);

        $this->event = \Mockery::mock(\SystemEvent_GIT_REPO_DELETE::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->event->setParameters($this->project_id . SystemEvent::PARAMETER_SEPARATOR . $this->repository_id);
        $this->event->injectDependencies(
            $this->repository_factory,
            \Mockery::spy(\Psr\Log\LoggerInterface::class),
            $this->ugroups_to_notify_dao,
            $this->users_to_notify_dao,
            $this->event_manager
        );
    }

    public function testItDeletesTheRepository(): void
    {
        $this->repository->shouldReceive('delete')->once();

        $this->event->process();
    }

    public function testItDeletesNotifications(): void
    {
        $this->ugroups_to_notify_dao->shouldReceive('deleteByRepositoryId')->with(69)->once();
        $this->users_to_notify_dao->shouldReceive('deleteByRepositoryId')->with(69)->once();

        $this->event->process();
    }

    public function testItLaunchesAnEventToLetOthersDeleteStuffLinkedToTheRepository(): void
    {
        $this->event_manager->shouldReceive('processEvent')->with(Mockery::on(function ($param) {
            return $param instanceof \Tuleap\Git\GitRepositoryDeletionEvent;
        }))->atLeast()->once();

        $this->event->process();
    }
}
