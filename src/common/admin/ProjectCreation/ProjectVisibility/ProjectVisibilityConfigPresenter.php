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
 */

namespace Tuleap\admin\ProjectCreation\ProjectVisibility;

use ForgeConfig;
use Tuleap\Admin\ProjectCreationNavBarPresenter;
use Tuleap\Project\Admin\ProjectVisibilityOptionsForPresenterGenerator;
use Tuleap\Project\Admin\Visibility\UpdateVisibilityStatus;

class ProjectVisibilityConfigPresenter
{
    /**
     * @var ProjectCreationNavBarPresenter
     */
    public $navbar;

    /**
     * @var \CSRFSynchronizerToken
     */
    public $csrf_token;

    /**
     * @var bool
     */
    public $project_admin_can_choose;

    /**
     * @var bool
     */
    public $send_mail_on_project_visibility_change;

    /**
     * @var array
     */
    public $default_project_visibility_options;

    public function __construct(
        ProjectCreationNavBarPresenter $menu_tabs,
        ProjectVisibilityOptionsForPresenterGenerator $project_visibility_options_generator,
        string $current_default_project_visibility_retriever,
        \CSRFSynchronizerToken $csrf_token,
    ) {
        $this->navbar                                 = $menu_tabs;
        $this->csrf_token                             = $csrf_token;
        $this->project_admin_can_choose               = (bool) ForgeConfig::get(ProjectVisibilityConfigManager::PROJECT_ADMIN_CAN_CHOOSE_VISIBILITY);
        $this->send_mail_on_project_visibility_change = (bool) ForgeConfig::get(ProjectVisibilityConfigManager::SEND_MAIL_ON_PROJECT_VISIBILITY_CHANGE);
        $this->default_project_visibility_options     = $project_visibility_options_generator->generateVisibilityOptions(
            ForgeConfig::areRestrictedUsersAllowed(),
            UpdateVisibilityStatus::buildStatusSwitchIsAllowed(),
            $current_default_project_visibility_retriever
        );
    }
}
