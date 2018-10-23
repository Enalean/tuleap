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

namespace Tuleap\admin\ProjectCreation\ProjectVisibility;

use CSRFSynchronizerToken;
use ForgeAccess_ForgePropertiesManager;
use ForgeConfig;
use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class ProjectVisibilityConfigUpdateController implements DispatchableWithRequest
{
    /**
     * @var ForgeAccess_ForgePropertiesManager
     */
    private $visibility_config_manager;

    public function __construct(ProjectVisibilityConfigManager $visibility_config_manager)
    {
        $this->visibility_config_manager = $visibility_config_manager;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        if (! $request->getCurrentUser()->isSuperUser()) {
            throw new ForbiddenException();
        }

        $csrf_token = new CSRFSynchronizerToken('/admin/project-creation/visibility');
        $csrf_token->check();

        $this->visibility_config_manager->updateVisibilityOption(
            ProjectVisibilityConfigManager::PROJECT_ADMIN_CAN_CHOOSE_VISIBILITY,
            $request->get('project_admin_can_choose_visibility')
        );

        $this->visibility_config_manager->updateVisibilityOption(
            ProjectVisibilityConfigManager::SEND_MAIL_ON_PROJECT_VISIBILITY_CHANGE,
            $request->get('send_mail_on_visibility_change')
        );

        $GLOBALS['Response']->addFeedback(\Feedback::INFO, _('Successfully updated.'));

        $layout->redirect('/admin/project-creation/visibility');
    }
}
