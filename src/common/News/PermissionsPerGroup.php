<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\News;

use HTTPRequest;
use PermissionsManager;
use Tuleap\Layout\BaseLayout;
use Tuleap\News\Admin\AdminNewsDao;
use Tuleap\News\Admin\PermissionsPerGroup\NewsJSONPermissionsRetriever;
use Tuleap\News\Admin\PermissionsPerGroup\NewsPermissionsManager;
use Tuleap\News\Admin\PermissionsPerGroup\NewsPermissionsRepresentationBuilder;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class PermissionsPerGroup implements DispatchableWithRequest
{
    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param array       $variables
     * @return void
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        if (! $request->getCurrentUser()->isAdmin($request->getProject()->getID())) {
            $layout->send400JSONErrors(
                [
                    'error' => [
                        'message' => _(
                            "You don't have permissions to see user groups."
                        ),
                    ],
                ]
            );
        }

        $news_permissions_retriever = new NewsJSONPermissionsRetriever(
            new NewsPermissionsRepresentationBuilder(
                new NewsPermissionsManager(
                    PermissionsManager::instance(),
                    new AdminNewsDao()
                )
            )
        );

        $news_permissions_retriever->retrieve(
            $request->getProject(),
            $request->get('selected_ugroup_id')
        );
    }
}
