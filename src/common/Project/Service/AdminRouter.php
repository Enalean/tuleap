<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

namespace Tuleap\Project\Service;

use CSRFSynchronizerToken;
use HTTPRequest;
use Project;

class AdminRouter
{
    /**
     * @var IndexController
     */
    private $index_controller;
    /**
     * @var DeleteController
     */
    private $delete_controller;
    /**
     * @var AddController
     */
    private $add_controller;
    /**
     * @var EditController
     */
    private $edit_controller;

    public function __construct(
        IndexController $index_controller,
        DeleteController $delete_controller,
        AddController $add_controller,
        EditController $edit_controller
    ) {
        $this->index_controller  = $index_controller;
        $this->delete_controller = $delete_controller;
        $this->add_controller    = $add_controller;
        $this->edit_controller   = $edit_controller;
    }

    public function process(HTTPRequest $request)
    {
        $project = $request->getProject();
        $csrf    = new CSRFSynchronizerToken($this->getUrl($project));
        switch ($request->get('action')) {
            case 'delete':
                $csrf->check();
                $this->delete_controller->delete($request);
                $this->redirect($project);
                break;
            case 'edit':
                $csrf->check();
                $this->edit_controller->edit($request);
                $this->redirect($project);
                break;
            case 'add':
                $csrf->check();
                $this->add_controller->add($request);
                $this->redirect($project);
                break;
            default:
                $this->index_controller->display($project, $csrf, $request->getCurrentUser());
        }
    }

    private function getUrl(Project $project)
    {
        return '/project/admin/servicebar.php?' . http_build_query(
            array(
                'group_id' => $project->getID()
            )
        );
    }

    private function redirect(Project $project)
    {
        $GLOBALS['Response']->redirect($this->getUrl($project));
    }
}
