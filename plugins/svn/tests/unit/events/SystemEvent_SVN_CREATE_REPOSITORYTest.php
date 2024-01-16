<?php
/**
 * Copyright (c) Enalean, 2017 - present. All Rights Reserved.
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

namespace Tuleap\SVN\Events;

use Tuleap\SVN\Repository\SvnRepository;
use Tuleap\SVNCore\Repository;
use Tuleap\SVNCore\Exception\SVNRepositoryCreationException;
use Tuleap\SVNCore\Exception\SVNRepositoryLayoutInitializationException;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class SystemEvent_SVN_CREATE_REPOSITORYTest extends \Tuleap\Test\PHPUnit\TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Tuleap\SVN\Repository\RepositoryManager
     */
    private $repository_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Tuleap\SVN\AccessControl\AccessFileHistoryCreator
     */
    private $access_file_history_creator;
    /**
     * @var \BackendSVN&\PHPUnit\Framework\MockObject\MockObject
     */
    private $backend_svn;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\UserManager
     */
    private $user_manager;

    protected function setUp(): void
    {
        $this->user_manager                = $this->createMock(\UserManager::class);
        $this->backend_svn                 = $this->createMock(\BackendSVN::class);
        $this->access_file_history_creator = $this->createMock(\Tuleap\SVN\AccessControl\AccessFileHistoryCreator::class);
        $this->repository_manager          = $this->createMock(\Tuleap\SVN\Repository\RepositoryManager::class);
    }

    public function testItRetrievesParameters(): void
    {
        $repository            = SvnRepository::buildActiveRepository(15, 'foo', ProjectTestBuilder::aProject()->withId(222)->build());
        $committer             = UserTestBuilder::anActiveUser()->withId(777)->build();
        $serialized_parameters = SystemEvent_SVN_CREATE_REPOSITORY::serializeParameters($repository, $committer, [], false);

        $system_event = new SystemEvent_SVN_CREATE_REPOSITORY(
            1,
            'Type',
            \SystemEvent::OWNER_ROOT,
            $serialized_parameters,
            \SystemEvent::PRIORITY_HIGH,
            \SystemEvent::STATUS_NEW,
            '2017-07-26 12:00:00',
            '0000-00-00 00:00:00',
            '0000-00-00 00:00:00',
            'Log'
        );

        self::assertEquals(
            [
                15,
                777,
                [],
                false,
            ],
            $system_event->getParametersAsArray()
        );
    }

    public function testItMarksTheEventAsDoneWhenTheRepositoryIsSuccessfullyCreated(): void
    {
        $system_event = $this->getMockBuilder(SystemEvent_SVN_CREATE_REPOSITORY::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['done'])
            ->getMock();

        $this->user_manager->method('getUserById')->willReturn($this->createMock(\PFUser::class));
        $this->backend_svn->expects($this->once())->method('createRepositorySVN');
        $this->access_file_history_creator->method('useAVersion')->willReturn(true);
        $this->repository_manager->method('getRepositoryById')->with(15)->willReturn($this->createMock(Repository::class));

        $system_event->injectDependencies(
            $this->access_file_history_creator,
            $this->repository_manager,
            $this->user_manager,
            $this->backend_svn,
            $this->createMock(\Tuleap\SVN\Migration\RepositoryCopier::class)
        );

        $system_event->expects(self::once())->method('done');

        $system_event->setParameters(
            SystemEvent_SVN_CREATE_REPOSITORY::serializeParameters(
                SvnRepository::buildActiveRepository(15, 'foo', ProjectTestBuilder::aProject()->build()),
                UserTestBuilder::anActiveUser()->build(),
                [],
                false,
            )
        );

        $system_event->process();
    }

    public function testItGeneratesAnErrorIfTheRepositoryCanNotBeCreated(): void
    {
        $system_event = $this->getMockBuilder(SystemEvent_SVN_CREATE_REPOSITORY::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['done', 'error'])
            ->getMock();

        $this->user_manager->method('getUserById')->willReturn($this->createMock(\PFUser::class));
        $this->backend_svn->method('createRepositorySVN')->willThrowException(new SVNRepositoryCreationException());
        $this->repository_manager->method('getRepositoryById')->willReturn(SvnRepository::buildActiveRepository(15, 'foo', ProjectTestBuilder::aProject()->build()));

        $system_event->injectDependencies(
            $this->access_file_history_creator,
            $this->repository_manager,
            $this->user_manager,
            $this->backend_svn,
            $this->createMock(\Tuleap\SVN\Migration\RepositoryCopier::class)
        );

        $system_event->expects(self::once())->method('error');
        $system_event->expects(self::never())->method('done');
        $system_event->setParameters(
            SystemEvent_SVN_CREATE_REPOSITORY::serializeParameters(
                SvnRepository::buildActiveRepository(15, 'foo', ProjectTestBuilder::aProject()->build()),
                UserTestBuilder::anActiveUser()->build(),
                [],
                false,
            )
        );

        $system_event->process();
    }

    public function testItGeneratesAWarningIfTheDirectoryLayoutCanNotBeCreated(): void
    {
        $system_event = $this->getMockBuilder(SystemEvent_SVN_CREATE_REPOSITORY::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['done', 'warning'])
            ->getMock();

        $this->user_manager->method('getUserById')->willReturn($this->createMock(\PFUser::class));
        $this->backend_svn->method('createRepositorySVN')->willThrowException(new SVNRepositoryLayoutInitializationException());
        $this->repository_manager->method('getRepositoryById')->willReturn(SvnRepository::buildActiveRepository(15, 'foo', ProjectTestBuilder::aProject()->build()));

        $system_event->injectDependencies(
            $this->access_file_history_creator,
            $this->repository_manager,
            $this->user_manager,
            $this->backend_svn,
            $this->createMock(\Tuleap\SVN\Migration\RepositoryCopier::class)
        );

        $system_event->expects(self::once())->method('warning');
        $system_event->expects(self::never())->method('done');
        $system_event->setParameters(
            SystemEvent_SVN_CREATE_REPOSITORY::serializeParameters(
                SvnRepository::buildActiveRepository(15, 'foo', ProjectTestBuilder::aProject()->build()),
                UserTestBuilder::anActiveUser()->build(),
                [
                    '/trunk',
                    '/tags',
                ],
                false,
            )
        );

        $system_event->process();
    }
}
