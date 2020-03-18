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

class ProjectRegistrationPresenter
{
    /**
     * @var string
     * @psalm-readonly
     */
    public $tuleap_templates;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $are_restricted_users_allowed;
    /**
     * @var string
     * @psalm-readonly
     */
    public $project_default_visibility;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $projects_must_be_approved;
    /**
     * @var string
     * @psalm-readonly
     */
    public $trove_categories;
    /**
     * @var string
     * @psalm-readonly
     */
    public $field_list;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $is_description_mandatory;
    /**
     * @var string
     * @psalm-readonly
     */
    public $company_templates;
    /**
     * @var string
     * @psalm-readonly
     */
    public $company_name;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $are_anonymous_allowed;
    /**
     * @var ?string
     * @psalm-readonly
     */
    public $default_project_template;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $is_default_project_template_available;
    /**
     * @var bool
     * @psalm-readonly
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
