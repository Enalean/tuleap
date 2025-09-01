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

namespace Tuleap\Baseline\Adapter\Routing;

use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tuleap\Baseline\Adapter\ProjectProxy;
use Tuleap\Baseline\Adapter\UserProxy;
use Tuleap\Baseline\Domain\Authorizations;
use Tuleap\Project\Admin\Routing\ProjectAdministratorChecker;
use Tuleap\Request\ForbiddenException;
use Tuleap\User\ProvideCurrentUser;

class RejectNonBaselineAdministratorMiddleware implements \Psr\Http\Server\MiddlewareInterface
{
    public function __construct(
        private ProvideCurrentUser $current_user_provider,
        private ProjectAdministratorChecker $project_administrator_checker,
        private Authorizations $authorizations,
    ) {
    }

    /**
     * @throws ForbiddenException
     */
    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $project = $request->getAttribute(\Project::class);
        if ($project === null) {
            throw new \LogicException('This middleware needs ProjectRetrieverMiddleware to function.');
        }
        $user                                      = $this->current_user_provider->getCurrentUser();
        $can_user_administrate_baseline_on_project = $this->authorizations->canUserAdministrateBaselineOnProject(
            UserProxy::fromUser($user),
            ProjectProxy::buildFromProject($project)
        );
        if (! $can_user_administrate_baseline_on_project) {
            $this->project_administrator_checker->checkUserIsProjectAdministrator($user, $project);
        }

        $enriched_request = $request->withAttribute(\PFUser::class, $user);

        return $handler->handle($enriched_request);
    }
}
