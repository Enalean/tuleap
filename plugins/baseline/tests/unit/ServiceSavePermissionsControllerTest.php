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

use Tuleap\Baseline\Domain\ProjectIdentifier;
use Tuleap\Baseline\Domain\Role;
use Tuleap\Baseline\Domain\RoleAssignment;
use Tuleap\Baseline\Domain\RoleAssignmentRepository;
use Tuleap\Test\Stubs\CSRFSynchronizerTokenStub;
use Tuleap\Baseline\Support\NoopSapiEmitter;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Layout\Feedback\FeedbackSerializer;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

class ServiceSavePermissionsControllerTest extends TestCase
{
    use \Tuleap\TemporaryTestDirectory;

    public function testSaveSettings(): void
    {
        $feedback_serializer = $this->createStub(FeedbackSerializer::class);
        $feedback_serializer->method('serialize');

        $role_assignment_repository = new class implements RoleAssignmentRepository {
            private $captured_save_parameters = [];

            public function findByProjectAndRole(ProjectIdentifier $project, string $role): array
            {
                return [];
            }

            public function saveAssignmentsForProject(
                ProjectIdentifier $project,
                RoleAssignment ...$assignments,
            ): void {
                $this->captured_save_parameters = [$project, $assignments];
            }

            public function getCapturedSaveParameters(): array
            {
                return $this->captured_save_parameters;
            }
        };

        $token          = CSRFSynchronizerTokenStub::buildSelf();
        $token_provider = $this->createMock(CSRFSynchronizerTokenProvider::class);
        $token_provider->method('getCSRF')->willReturn($token);

        $controller = new ServiceSavePermissionsController(
            $role_assignment_repository,
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

        $save_parameters = $role_assignment_repository->getCapturedSaveParameters();
        self::assertEquals((int) $project->getID(), $save_parameters[0]->getID());
        self::assertEquals(102, $save_parameters[1][0]->getUserGroupId());
        self::assertEquals(Role::ADMIN, $save_parameters[1][0]->getRole());
        self::assertEquals(103, $save_parameters[1][1]->getUserGroupId());
        self::assertEquals(Role::ADMIN, $save_parameters[1][1]->getRole());
        self::assertEquals(104, $save_parameters[1][2]->getUserGroupId());
        self::assertEquals(Role::READER, $save_parameters[1][2]->getRole());
    }
}
