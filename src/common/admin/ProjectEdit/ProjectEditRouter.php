<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\admin\ProjectEdit;

class ProjectEditRouter
{
    /**
     * @var ProjectEditController
     */
    private $project_edit_controller;

    public function __construct(ProjectEditController $project_edit_controller)
    {
        $this->project_edit_controller = $project_edit_controller;
    }

    public function route(\HTTPRequest $request, \CSRFSynchronizerToken $csrf_token)
    {
        switch ($request->get('action')) {
            case 'update-project':
                $csrf_token->check();
                $this->project_edit_controller->updateProject($request);
                break;
            default:
                $this->project_edit_controller->index();
                break;
        }
    }
}
