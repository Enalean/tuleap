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

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Baseline\Adapter\ProjectProxy;
use Tuleap\Baseline\Domain\Role;
use Tuleap\Baseline\Domain\RoleAssignment;
use Tuleap\Baseline\Domain\RoleAssignmentRepository;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Layout\Feedback\NewFeedback;
use Tuleap\Request\DispatchablePSR15Compatible;

class ServiceSavePermissionsController extends DispatchablePSR15Compatible
{
    public function __construct(
        private RoleAssignmentRepository $role_assignment_repository,
        private RedirectWithFeedbackFactory $redirect_with_feedback_factory,
        private CSRFSynchronizerTokenProvider $token_provider,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $project = $request->getAttribute(\Project::class);
        assert($project instanceof \Project);

        $this->token_provider->getCSRF($project)->check();

        $body           = $request->getParsedBody();
        $administrators = (array) ($body['administrators'] ?? []);

        $project_proxy = ProjectProxy::buildFromProject($project);

        $assigments = array_map(
            static fn (string $administrator_ugroup_id) =>
                new RoleAssignment($project_proxy, (int) $administrator_ugroup_id, Role::ADMIN),
            $administrators
        );

        $this->role_assignment_repository->saveAssignmentsForProject($project_proxy, ...$assigments);

        $user = $request->getAttribute(\PFUser::class);
        assert($user instanceof \PFUser);

        return $this->redirect_with_feedback_factory->createResponseForUser(
            $user,
            ServiceAdministrationController::getAdminUrl($project),
            new NewFeedback(
                \Feedback::SUCCESS,
                dgettext('tuleap-baseline', 'Baseline permissions have been saved')
            ),
        );
    }
}
