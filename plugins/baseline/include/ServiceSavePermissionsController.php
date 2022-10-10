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
use Tuleap\Baseline\Adapter\Administration\ISaveProjectHistory;
use Tuleap\Baseline\Adapter\ProjectProxy;
use Tuleap\Baseline\Domain\Role;
use Tuleap\Baseline\Domain\RoleAssignment;
use Tuleap\Baseline\Domain\RoleAssignmentRepository;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Layout\Feedback\NewFeedback;
use Tuleap\Project\UGroupRetriever;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\ForbiddenException;

class ServiceSavePermissionsController extends DispatchablePSR15Compatible
{
    public function __construct(
        private RoleAssignmentRepository $role_assignment_repository,
        private UGroupRetriever $ugroup_retriever,
        private ISaveProjectHistory $project_history,
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
        $assigments    = $this->getAssignementsFromBody($project, $request->getParsedBody());

        $this->role_assignment_repository->saveAssignmentsForProject($project_proxy, ...$assigments);
        $this->project_history->saveHistory($project, ...$assigments);

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
     * @param array|object|null $body
     *
     * @return RoleAssignment[]
     */
    private function getAssignementsFromBody(\Project $project, $body): array
    {
        if (! is_array($body)) {
            throw new \LogicException("Expected body to be an associative array");
        }

        $administrators = array_values((array) ($body['administrators'] ?? []));
        $readers        = array_values((array) ($body['readers'] ?? []));

        $roles = array_merge(
            array_fill_keys($administrators, Role::ADMIN),
            array_fill_keys($readers, Role::READER)
        );

        $already_processed_ugroup_id = [];

        $project_proxy = ProjectProxy::buildFromProject($project);

        return array_values(
            array_filter(
                array_map(
                    function (string $ugroup_id, string $role) use ($project, $project_proxy, &$already_processed_ugroup_id) {
                        if (isset($already_processed_ugroup_id[$ugroup_id])) {
                            return null;
                        }

                        $already_processed_ugroup_id[$ugroup_id] = true;
                        if (! $this->ugroup_retriever->getUGroup($project, $ugroup_id)) {
                            throw new ForbiddenException("Invalid user group $ugroup_id");
                        }

                        return new RoleAssignment(
                            $project_proxy,
                            (int) $ugroup_id,
                            $role
                        );
                    },
                    [...$administrators, ...$readers],
                    array_values($roles),
                )
            )
        );
    }
}
