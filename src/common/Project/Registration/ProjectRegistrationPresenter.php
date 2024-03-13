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

declare(strict_types=1);

namespace Tuleap\Project\Registration;

use ForgeConfig;
use ProjectManager;
use Tuleap\Project\ProjectDescriptionUsageRetriever;

/**
* @psalm-immutable
 */
final class ProjectRegistrationPresenter
{
    public readonly string $tuleap_templates;
    public readonly bool $are_restricted_users_allowed;
    public readonly string $project_default_visibility;
    public readonly bool $projects_must_be_approved;
    public readonly string $trove_categories;
    public readonly string $field_list;
    public readonly bool $is_description_mandatory;
    public readonly string $company_templates;
    public readonly string $company_name;
    public readonly bool $can_user_choose_privacy;
    public readonly string $external_templates;

    public function __construct(
        string $project_default_visibility,
        array $trove_categories,
        array $field_list,
        array $company_templates,
        array $tuleap_templates,
        array $external_templates,
        public readonly bool $can_create_from_project_file,
    ) {
        $this->tuleap_templates             = json_encode($tuleap_templates);
        $this->are_restricted_users_allowed = ForgeConfig::areRestrictedUsersAllowed();
        $this->project_default_visibility   = $project_default_visibility;
        $this->projects_must_be_approved    = (bool) ForgeConfig::get(
            ProjectManager::CONFIG_PROJECT_APPROVAL,
            true
        );
        $this->trove_categories             = json_encode($trove_categories, JSON_THROW_ON_ERROR);
        $this->is_description_mandatory     = ProjectDescriptionUsageRetriever::isDescriptionMandatory();
        $this->field_list                   = json_encode($field_list);
        $this->company_templates            = json_encode($company_templates);
        $this->company_name                 = ForgeConfig::get('sys_org_name');
        $this->can_user_choose_privacy      = (bool) ForgeConfig::get(
            ProjectManager::SYS_USER_CAN_CHOOSE_PROJECT_PRIVACY
        );
        $this->external_templates           = json_encode($external_templates);
    }
}
