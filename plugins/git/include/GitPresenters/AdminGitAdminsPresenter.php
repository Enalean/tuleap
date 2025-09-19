<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

class GitPresenters_AdminGitAdminsPresenter extends GitPresenters_AdminPresenter //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    public const string GIT_ADMIN_SELECTBOX_NAME = 'git_admins';

    /** @var ProjectUGroup[] */
    private $static_ugroups;

    /** @var array */
    private $selected_ugroups;


    public function __construct(
        $project_id,
        array $external_pane_presenters,
        $static_ugroups,
        $selected_ugroups,
    ) {
        parent::__construct($project_id, $external_pane_presenters);

        $this->static_ugroups   = $static_ugroups;
        $this->selected_ugroups = $selected_ugroups;
    }

    public function git_admins_section() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return dgettext('tuleap-git', 'Git administrators');
    }

    public function git_admins_description() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return dgettext('tuleap-git', 'This section allows you to select Git service administrators, in addition to project administrators.');
    }

    public function git_admins_submit_button() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return dgettext('tuleap-git', 'Submit');
    }

    #[\Override]
    public function form_action(): string  //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return '/plugins/git/?group_id=' . $this->project_id . '&action=admin-git-admins';
    }

    public function git_admins_selectbox_name()  //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return self::GIT_ADMIN_SELECTBOX_NAME . '[]';
    }

    public function git_admins_selectbox_id() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return self::GIT_ADMIN_SELECTBOX_NAME;
    }

    public function git_admins_options() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return $this->getSelectorOptions();
    }

    private function getSelectorOptions()
    {
        $options = [$this->getProjectMembersOption()];
        foreach ($this->static_ugroups as $group) {
            $options[] = [
                'value'    => $group->getId(),
                'label'    => $group->getTranslatedName(),
                'selected' => isset($this->selected_ugroups) ? in_array($group->getId(), $this->selected_ugroups) : false,
            ];
        }
        return $options;
    }

    private function getProjectMembersOption()
    {
        return [
            'value'    => ProjectUGroup::PROJECT_MEMBERS,
            'label'    => $GLOBALS['Language']->getText('project_admin_editugroup', 'proj_members'),
            'selected' => isset($this->selected_ugroups) ? in_array(ProjectUGroup::PROJECT_MEMBERS, $this->selected_ugroups) : false,
        ];
    }
}
