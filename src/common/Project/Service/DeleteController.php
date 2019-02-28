<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

use Feedback;
use HTTPRequest;
use Project;
use ServiceDao;

class DeleteController
{
    /**
     * @var ServiceDao
     */
    private $dao;

    public function __construct(ServiceDao $dao)
    {
        $this->dao = $dao;
    }

    public function delete(HTTPRequest $request)
    {
        $service_id = $request->getValidated('service_id', 'uint', 0);
        if (! $service_id) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('project_admin_servicebar', 's_id_not_given')
            );

            return;
        }

        $project_id = $request->getProject()->getID();
        if ($this->dao->delete($project_id, $service_id)) {
            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                $GLOBALS['Language']->getText('project_admin_servicebar', 's_del')
            );
        } else {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('project_admin_editgroupinfo', 'upd_fail')
            );
        }

        $this->deleteFromAllProjects($request, $project_id);
    }

    private function deleteFromAllProjects(HTTPRequest $request, $project_id)
    {
        if ((int)$project_id !== Project::ADMIN_PROJECT_ID) {
            return;
        }

        $short_name = $request->getValidated('short_name', 'string', '');
        if (! $short_name) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('project_admin_servicebar', 'cant_delete_s_from_p')
            );

            return;
        }

        if ($this->dao->deleteFromAllProjects($short_name)) {
            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                $GLOBALS['Language']->getText('project_admin_servicebar', 's_del_from_p', db_affected_rows($result))
            );
        } else {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('project_admin_servicebar', 'del_fail')
            );
        }
    }
}
