<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Git\PermissionsPerGroup;

use HTTPRequest;
use Tuleap\Git\RouterLink;

class PermissionPerGroupController extends RouterLink
{
    /**
     * @var GitJSONPermissionsRetriever
     */
    private $permissions_retriever;

    public function __construct(GitJSONPermissionsRetriever $permissions_retriever)
    {
        $this->permissions_retriever = $permissions_retriever;
    }

    public function process(HTTPRequest $request)
    {
        switch ($request->get('action')) {
            case 'permission-per-group':
                $this->getPermissions($request);
                break;
            default:
                parent::process($request);
                break;
        }
    }

    public function getPermissions(HTTPRequest $request)
    {
        if (! $request->getCurrentUser()->isAdmin($request->getProject()->getID())) {
            $GLOBALS['Response']->send400JSONErrors(
                [
                    'error' => [
                        'message' => dgettext(
                            'tuleap-git',
                            "You don't have permissions to see user groups."
                        ),
                    ],
                ]
            );
        }

        $this->permissions_retriever->retrieve($request->getProject(), $request->get('selected_ugroup_id'));
    }
}
