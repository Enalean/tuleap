<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Project\Admin\Routing;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tuleap\Project\Admin\MembershipDelegationDao;
use Tuleap\Request\ForbiddenException;
use Tuleap\User\ProvideCurrentUser;

final class RejectNonProjectMembersAdministratorMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ProvideCurrentUser $user_manager,
        private MembershipDelegationDao $membership_delegation_dao,
    ) {
    }

    /**
     * @throws ForbiddenException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $project = $request->getAttribute(\Project::class);
        if ($project === null) {
            throw new \LogicException('This middleware needs ProjectRetrieverMiddleware to function.');
        }

        $user = $this->user_manager->getCurrentUser();
        $this->checkUserCanManageProjectMembers($user, $project);

        $enriched_request = $request->withAttribute(\PFUser::class, $user);

        return $handler->handle($enriched_request);
    }

    private function checkUserCanManageProjectMembers(\PFUser $user, \Project $project): void
    {
        $is_project_admin = $user->isAdmin((int) $project->getID());
        if ($is_project_admin) {
            return;
        }

        if ($this->membership_delegation_dao->doesUserHasMembershipDelegation($user->getId(), $project->getID())) {
            return;
        }

        throw new ForbiddenException(
            gettext("You don't have permission to manage members of this project.")
        );
    }
}
