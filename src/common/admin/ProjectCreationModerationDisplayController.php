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

use CSRFSynchronizerToken;
use ForgeConfig;
use HTTPRequest;
use Tuleap\CSRFSynchronizerTokenPresenter;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class ProjectCreationModerationDisplayController implements DispatchableWithRequest
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


        $presenter = new ProjectCreationModerationPresenter(
            new ProjectCreationNavBarPresenter('moderation'),
            CSRFSynchronizerTokenPresenter::fromToken(new CSRFSynchronizerToken('/admin/project-creation/moderation')),
            ForgeConfig::get(\ProjectManager::CONFIG_PROJECT_APPROVAL, true),
            ForgeConfig::get(\ProjectManager::CONFIG_NB_PROJECTS_WAITING_FOR_VALIDATION, -1),
            ForgeConfig::get(\ProjectManager::CONFIG_NB_PROJECTS_WAITING_FOR_VALIDATION_PER_USER, -1),
            ForgeConfig::areRestrictedUsersAllowed(),
            ForgeConfig::get(\ProjectManager::CONFIG_RESTRICTED_USERS_CAN_CREATE_PROJECTS, false),
            $this->isSysProjectApprovalStillInLocalInc()
        );

        $admin_page = new AdminPageRenderer();
        $admin_page->renderANoFramedPresenter(
            _('Project creation moderation settings'),
            ForgeConfig::get('codendi_dir') . '/src/templates/admin/projects/',
            'moderation',
            $presenter
        );
    }

    private function isSysProjectApprovalStillInLocalInc()
    {
        include($this->getLocalIncPath());
        $variables_in_localinc = get_defined_vars();

        return isset($variables_in_localinc['sys_project_approval']);
    }

    private function getLocalIncPath()
    {
        $default_path = '/etc/tuleap/conf/local.inc';
        $old_path     = '/etc/codendi/conf/local.inc';
        $local_inc    = getenv('TULEAP_LOCAL_INC') ? getenv('TULEAP_LOCAL_INC') : getenv('CODENDI_LOCAL_INC');

        if (! $local_inc) {
            if (is_file($default_path)) {
                $local_inc = $default_path;
            } else {
                $local_inc = $old_path;
            }
        }

        return $local_inc;
    }
}
