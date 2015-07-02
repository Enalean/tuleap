<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class ProjectVisibilityPresenter {

    /**
     * @var string
     */
    public $section_title;

    /**
     * @var string
     */
    public $choose_visbility;

    /**
     * @var array
     */
    public $options;

    /**
     * @var boolean
     */
    public $platform_allows_restricted;

    /**
     * @var string
     */
    public $restricted_warning_message;

    /**
     * @var string
     */
    private $project_visibility;

    /**
     * @var BaseLanguage
     */
    private $language;

    public function __construct(BaseLanguage $language, $platform_allows_restricted, $project_visibility) {
        $this->language                   = $language;
        $this->platform_allows_restricted = (bool) $platform_allows_restricted;
        $this->project_visibility         = $project_visibility;
        $this->section_title              = $this->language->getText('project_admin_editgroupinfo', 'visibility_section');
        $this->choose_visbility           = $this->language->getText('project_admin_editgroupinfo', 'choose_visbility');
        $this->restricted_warning_message = '';

        $this->generateVisibilityOptions();
    }

    private function generateVisibilityOptions() {
        $user_group_factory    = new User_ForgeUserGroupFactory(new UserGroupDao());
        $project_members_label = $user_group_factory->getDynamicForgeUserGroupByName(User_ForgeUGroup::PROJECT_MEMBERS)->getName();

        $options = array();
        if (ForgeConfig::areAnonymousAllowed()) {
            $public_users_label = $user_group_factory->getDynamicForgeUserGroupByName(User_ForgeUGroup::ANON)->getName();
            $options[] = array(
                'value'      => Project::ACCESS_PUBLIC,
                'label'      => $public_users_label,
                'selected'   => ($this->project_visibility === Project::ACCESS_PUBLIC) ? 'selected = "selected"' : '',
            );
        }

        if ($this->platform_allows_restricted) {
            $unrestricted_users_label =  $user_group_factory->getDynamicForgeUserGroupByName(User_ForgeUGroup::AUTHENTICATED)->getName();
            $options[] = array(
                'value'      => Project::ACCESS_PUBLIC_UNRESTRICTED,
                'label'      => $unrestricted_users_label,
                'selected'   => ($this->project_visibility === Project::ACCESS_PUBLIC_UNRESTRICTED) ? 'selected = "selected"' : '',
            );

            $this->restricted_warning_message = $this->language->getText('project_admin_editgroupinfo', 'restricted_warning', array($unrestricted_users_label));
        }

        if (! ForgeConfig::areAnonymousAllowed()) {
            $public_users_label = $user_group_factory->getDynamicForgeUserGroupByName(User_ForgeUGroup::REGISTERED)->getName();
            $options[] = array(
                'value'      => Project::ACCESS_PUBLIC,
                'label'      => $public_users_label,
                'selected'   => ($this->project_visibility === Project::ACCESS_PUBLIC) ? 'selected = "selected"' : '',
            );
        }

        $options[] = array(
            'value'      => Project::ACCESS_PRIVATE,
            'label'      => $project_members_label,
            'selected'   => ($this->project_visibility === Project::ACCESS_PRIVATE) ? 'selected = "selected"' : '',
        );

        $this->options = $options;
    }
}