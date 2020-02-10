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
 *
 */

namespace Tuleap\Admin;

use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class ProjectCreationModerationUpdateController implements DispatchableWithRequest
{

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param array $variables
     * @throws NotFoundException
     * @throws ForbiddenException
     * @return void
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        if (! $request->getCurrentUser()->isSuperUser()) {
            throw new ForbiddenException();
        }

        (new \CSRFSynchronizerToken('/admin/project-creation/moderation'))->check();

        $project_approval = $request->getToggleVariable('projects_must_be_approved');
        $restricted_can_create = $request->getToggleVariable('restricted_users_can_create_projects');
        $nb_max_global    = $this->getInputNotLowerThanMinusOne($request, 'nb_max_projects_waiting_for_validation');
        $nb_max_per_user  = $this->getInputNotLowerThanMinusOne($request, 'nb_max_projects_waiting_for_validation_per_user');

        $config_dao = new \ConfigDao();
        $config_dao->save(\ProjectManager::CONFIG_PROJECT_APPROVAL, $project_approval);
        $config_dao->save(\ProjectManager::CONFIG_RESTRICTED_USERS_CAN_CREATE_PROJECTS, $restricted_can_create);
        $config_dao->save(\ProjectManager::CONFIG_NB_PROJECTS_WAITING_FOR_VALIDATION, $nb_max_global);
        $config_dao->save(\ProjectManager::CONFIG_NB_PROJECTS_WAITING_FOR_VALIDATION_PER_USER, $nb_max_per_user);

        $layout->addFeedback(\Feedback::INFO, _('Settings saved'));

        $layout->redirect('/admin/project-creation/moderation');
    }

    private function getInputNotLowerThanMinusOne(HTTPRequest $request, $variable)
    {
        return $this->sanitizeInteger($request->getValidated($variable, 'int', -1));
    }

    private function sanitizeInteger($val)
    {
        if ($val < 0) {
            return -1;
        }
        return $val;
    }
}
