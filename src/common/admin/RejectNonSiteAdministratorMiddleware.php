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

namespace Tuleap\Admin;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tuleap\Request\ForbiddenException;
use UserManager;

final class RejectNonSiteAdministratorMiddleware implements MiddlewareInterface
{
    /**
     * @var UserManager
     */
    private $user_manager;

    public function __construct(UserManager $user_manager)
    {
        $this->user_manager = $user_manager;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $current_user = $this->user_manager->getCurrentUser();

        if (! $current_user->isSuperUser()) {
            throw new ForbiddenException(_('You do not have permission to access the site administration.'));
        }

        $enriched_request = $request->withAttribute(\PFUser::class, $current_user);
        return $handler->handle($enriched_request);
    }
}
