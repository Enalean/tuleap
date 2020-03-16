<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

declare(strict_types = 1);

namespace Tuleap\Project\Registration;

use ForgeConfig;
use ProjectManager;
use Tuleap\Project\ProjectDescriptionUsageRetriever;
use Tuleap\Project\Registration\Template\TemplatePresenter;

/**
 * @psalm-immutable
 */
class ProjectRegistrationPresenter
{
    /**
     * @var string
     */
    public $tuleap_templates;
    /**
     * @var bool
     */
    public $are_restricted_users_allowed;
    /**
     * @var string
     */
    public $project_default_visibility;
    /**
     * @var bool
     */
    public $projects_must_be_approved;
    /**
     * string
     */
    public $trove_categories;
    /**
     * @var string
     */
    public $field_list;
    /**
     * @var bool
     */
    public $is_description_mandatory;
    /**
     * @var string
     */
    public $company_templates;
    /**
     * @var string
     */
    public $company_name;
    /**
     * @var bool
     */
    public $are_anonymous_allowed;
    /**
     * @var ?string
     */
    public $default_project_template;
    /**
     * @var bool
     */
    public $is_default_project_template_available;
    /**
     * @var bool
     */
    public $can_user_choose_privacy;

    public function __construct(
        string $project_default_visibility,
        array $trove_categories,
        array $field_list,
        array $company_templates,
        ?TemplatePresenter $default_project_template,
        TemplatePresenter ...$tuleap_templates
    ) {
        $this->tuleap_templates                      = json_encode($tuleap_templates);
        $this->are_restricted_users_allowed          = (bool) ForgeConfig::areRestrictedUsersAllowed();
        $this->project_default_visibility            = $project_default_visibility;
        $this->projects_must_be_approved             = (bool) ForgeConfig::get(
            ProjectManager::CONFIG_PROJECT_APPROVAL,
            true
        );
        $this->trove_categories                      = json_encode($trove_categories, JSON_THROW_ON_ERROR);
        $this->is_description_mandatory              = ProjectDescriptionUsageRetriever::isDescriptionMandatory();
        $this->field_list                            = json_encode($field_list);
        $this->company_templates                     = json_encode($company_templates);
        $this->company_name                          = ForgeConfig::get('sys_org_name');
        $this->are_anonymous_allowed                 = (bool) ForgeConfig::areAnonymousAllowed();
        $this->can_user_choose_privacy               = (bool) ForgeConfig::get(
            ProjectManager::SYS_USER_CAN_CHOOSE_PROJECT_PRIVACY
        );
        $this->is_default_project_template_available = $default_project_template !== null;
        if ($default_project_template) {
            $this->default_project_template = json_encode($default_project_template);
        } else {
            $this->default_project_template = null;
        }
    }
}
