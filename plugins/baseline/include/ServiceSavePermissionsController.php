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
use Tuleap\Baseline\Domain\ProjectIdentifier;
use Tuleap\Baseline\Domain\RetrieveBaselineUserGroup;
use Tuleap\Baseline\Domain\Role;
use Tuleap\Baseline\Domain\RoleAssignment;
use Tuleap\Baseline\Domain\RoleAssignmentRepository;
use Tuleap\Baseline\Domain\RoleAssignmentsUpdate;
use Tuleap\Baseline\Domain\RoleAssignmentsHistorySaver;
use Tuleap\Baseline\Domain\RoleBaselineAdmin;
use Tuleap\Baseline\Domain\RoleBaselineReader;
use Tuleap\Baseline\Domain\UserGroupDoesNotExistOrBelongToCurrentBaselineProjectException;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Layout\Feedback\NewFeedback;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\ForbiddenException;

class ServiceSavePermissionsController extends DispatchablePSR15Compatible
{
    public function __construct(
        private RoleAssignmentRepository $role_assignment_repository,
        private RetrieveBaselineUserGroup $ugroup_retriever,
        private RoleAssignmentsHistorySaver $role_assignments_history_saver,
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

        $project_proxy = ProjectProxy::buildFromProject($project);

        $body = $request->getParsedBody();
        if (! is_array($body)) {
            throw new \LogicException("Expected body to be an associative array");
        }

        $assignments = array_merge(
            $this->getAssignmentsFromBodyAndRole($project_proxy, new RoleBaselineAdmin(), array_values((array) ($body['administrators'] ?? []))),
            $this->getAssignmentsFromBodyAndRole($project_proxy, new RoleBaselineReader(), array_values((array) ($body['readers'] ?? []))),
        );


        $this->role_assignment_repository->saveAssignmentsForProject(
            $this->buildRoleAssignmentUpdate($project_proxy, $assignments)
        );
        $this->role_assignments_history_saver->saveHistory(
            $this->buildRoleAssignmentUpdate($project_proxy, $assignments)
        );


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

    /**
     * @param string[] $role_assignments_ids
     * @return RoleAssignment[]
     */
    private function getAssignmentsFromBodyAndRole(ProjectIdentifier $project, Role $role, array $role_assignments_ids): array
    {
        try {
            return RoleAssignment::fromRoleAssignmentsIds(
                $this->ugroup_retriever,
                $project,
                $role,
                ...array_map(static fn($id) => (int) $id, $role_assignments_ids)
            );
        } catch (UserGroupDoesNotExistOrBelongToCurrentBaselineProjectException $e) {
            throw new ForbiddenException($e->getMessage());
        }
    }

    private function buildRoleAssignmentUpdate(ProjectIdentifier $project, array $role_assignments): RoleAssignmentsUpdate
    {
        try {
            return RoleAssignmentsUpdate::build(
                $project,
                ...$role_assignments
            );
        } catch (UserGroupDoesNotExistOrBelongToCurrentBaselineProjectException $e) {
            throw new ForbiddenException($e->getMessage());
        }
    }
}
