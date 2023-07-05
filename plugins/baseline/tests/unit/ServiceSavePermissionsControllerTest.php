<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Baseline;

use Tuleap\Baseline\Adapter\ProjectProxy;
use Tuleap\Baseline\Domain\RoleAssignmentsHistorySaver;
use Tuleap\Baseline\Domain\RoleAssignmentsSaver;
use Tuleap\Baseline\Domain\RoleBaselineAdmin;
use Tuleap\Baseline\Domain\RoleBaselineReader;
use Tuleap\Baseline\Stub\AddRoleAssignmentsHistoryEntryStub;
use Tuleap\Baseline\Stub\RetrieveBaselineUserGroupStub;
use Tuleap\Baseline\Stub\RoleAssignmentRepositoryStub;
use Tuleap\Baseline\Support\RoleAssignmentTestBuilder;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\Helpers\NoopSapiEmitter;
use Tuleap\Test\Stubs\CSRFSynchronizerTokenStub;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Layout\Feedback\FeedbackSerializer;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class ServiceSavePermissionsControllerTest extends TestCase
{
    use \Tuleap\TemporaryTestDirectory;

    public function testSaveSettings(): void
    {
        $feedback_serializer = $this->createStub(FeedbackSerializer::class);
        $feedback_serializer->method('serialize');

        $history_entry_adder = AddRoleAssignmentsHistoryEntryStub::build();

        $role_assignment_repository = RoleAssignmentRepositoryStub::buildDefault();

        $token          = CSRFSynchronizerTokenStub::buildSelf();
        $token_provider = $this->createMock(CSRFSynchronizerTokenProvider::class);
        $token_provider->method('getCSRF')->willReturn($token);

        $controller = new ServiceSavePermissionsController(
            new RoleAssignmentsSaver(
                $role_assignment_repository,
                RetrieveBaselineUserGroupStub::withUserGroups(
                    ProjectUGroupTestBuilder::aCustomUserGroup(102)->build(),
                    ProjectUGroupTestBuilder::aCustomUserGroup(103)->build(),
                    ProjectUGroupTestBuilder::aCustomUserGroup(104)->build(),
                ),
                new RoleAssignmentsHistorySaver($history_entry_adder),
            ),
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), $feedback_serializer),
            $token_provider,
            new NoopSapiEmitter(),
        );

        $project = ProjectTestBuilder::aProject()->build();

        $request = (new NullServerRequest())
            ->withAttribute(\Project::class, $project)
            ->withAttribute(\PFUser::class, UserTestBuilder::anActiveUser()->build())
            ->withParsedBody(
                [
                    'administrators' => ['102', '103'],
                    'readers' => ['103', '104'],
                ]
            );

        $response = $controller->handle($request);

        self::assertTrue($token->hasBeenChecked());
        self::assertEquals(302, $response->getStatusCode());

        $save_parameters = $role_assignment_repository->getLastAssignmentUpdate();

        self::assertNotNull($save_parameters);
        self::assertEquals((int) $project->getID(), $save_parameters->getProject()->getID());
        self::assertEquals(102, $save_parameters->getAssignments()[0]->getUserGroupId());
        self::assertEquals(RoleBaselineAdmin::NAME, $save_parameters->getAssignments()[0]->getRoleName());
        self::assertEquals(103, $save_parameters->getAssignments()[1]->getUserGroupId());
        self::assertEquals(RoleBaselineAdmin::NAME, $save_parameters->getAssignments()[1]->getRoleName());
        self::assertEquals(103, $save_parameters->getAssignments()[2]->getUserGroupId());
        self::assertEquals(RoleBaselineReader::NAME, $save_parameters->getAssignments()[2]->getRoleName());
        self::assertEquals(104, $save_parameters->getAssignments()[3]->getUserGroupId());
        self::assertEquals(RoleBaselineReader::NAME, $save_parameters->getAssignments()[3]->getRoleName());


        $save_history_parameters = $history_entry_adder->getAddedHistoryEntries();
        self::assertCount(2, $save_history_parameters);

        self::assertEquals(
            [
                ProjectProxy::buildFromProject($project),
                'perm_granted_for_baseline_readers',
                RoleAssignmentTestBuilder::aRoleAssignment(new RoleBaselineReader())->withUserGroups(
                    ProjectUGroupTestBuilder::aCustomUserGroup(103)->build(),
                    ProjectUGroupTestBuilder::aCustomUserGroup(104)->build(),
                )->build(),
            ],
            $save_history_parameters[0]
        );

        self::assertEquals(
            [
                ProjectProxy::buildFromProject($project),
                'perm_granted_for_baseline_administrators',
                RoleAssignmentTestBuilder::aRoleAssignment(new RoleBaselineAdmin())->withUserGroups(
                    ProjectUGroupTestBuilder::aCustomUserGroup(102)->build(),
                    ProjectUGroupTestBuilder::aCustomUserGroup(103)->build(),
                )->build(),
            ],
            $save_history_parameters[1]
        );
    }

    public function testExceptionWhenUGroupIsNotValid(): void
    {
        $feedback_serializer = $this->createStub(FeedbackSerializer::class);
        $feedback_serializer->method('serialize');

        $token          = CSRFSynchronizerTokenStub::buildSelf();
        $token_provider = $this->createMock(CSRFSynchronizerTokenProvider::class);
        $token_provider->method('getCSRF')->willReturn($token);

        $controller = new ServiceSavePermissionsController(
            new RoleAssignmentsSaver(
                RoleAssignmentRepositoryStub::buildDefault(),
                RetrieveBaselineUserGroupStub::withUserGroups(),
                new RoleAssignmentsHistorySaver(AddRoleAssignmentsHistoryEntryStub::build()),
            ),
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), $feedback_serializer),
            $token_provider,
            new NoopSapiEmitter(),
        );

        $project = ProjectTestBuilder::aProject()->build();

        $request = (new NullServerRequest())
            ->withAttribute(\Project::class, $project)
            ->withAttribute(\PFUser::class, UserTestBuilder::anActiveUser()->build())
            ->withParsedBody(
                [
                    'administrators' => ['102', '103'],
                    'readers' => ['103', '104'],
                ]
            );

        $this->expectException(ForbiddenException::class);
        $controller->handle($request);
    }
}
