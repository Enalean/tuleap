<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\Admin;

class AdminSidebarPresenter
{
    public $all_users_count;
    public $users_need_approval;
    public $pending_users_count;
    public $validated_users_count;
    public $all_projects_count;
    public $pending_projects_count;
    public $are_trove_categories_enabled;
    public $plugins;

    public $users_section_title;
    public $users_search_placeholder;
    public $users_nav_all_users;
    public $users_nav_new_user;
    public $users_nav_pending_users;
    public $users_nav_permission_delegation;

    public $projects_section_title;
    public $projects_search_placeholder;
    public $projects_nav_all_projects;
    public $projects_nav_pending_projects;
    public $projects_nav_configuration;
    public $projects_nav_deleted_projects;

    public $configuration_section_title;
    public $configuration_nav_global_access_rights;
    public $configuration_nav_homepage;
    public $configuration_nav_categories;

    public $utils_section_title;
    public $utils_nav_system_events;
    public $utils_nav_mass_mail;
    public $links_nav_doc;

    public $plugins_section_title;
    public $plugins_manage_all;
    /**
     * @var string
     */
    public $configuration_nav_project_fields;

    public function __construct(
        $all_users_count,
        $users_need_approval,
        $pending_users_count,
        $validated_users_count,
        $all_projects_count,
        $pending_projects_count,
        $plugins,
        public bool $has_invitations,
    ) {
        $this->all_users_count        = $all_users_count;
        $this->users_need_approval    = $users_need_approval;
        $this->pending_users_count    = $pending_users_count;
        $this->validated_users_count  = $validated_users_count;
        $this->all_projects_count     = $all_projects_count;
        $this->pending_projects_count = $pending_projects_count;
        $this->plugins                = $plugins;

        $this->users_section_title             = _('Users');
        $this->users_search_placeholder        = _('Email, username...');
        $this->users_nav_all_users             = _('All users');
        $this->users_nav_new_user              = _(' New user');
        $this->users_nav_pending_users         = _('Pending users');
        $this->users_nav_validated_users       = _('Users pending email activation');
        $this->users_nav_permission_delegation = _('Permission delegation');

        $this->projects_section_title        = _('Projects');
        $this->projects_search_placeholder   = _('Id, unix name...');
        $this->projects_nav_all_projects     = _('All projects');
        $this->projects_nav_pending_projects = _('Pending projects');
        $this->projects_nav_configuration    = _('Project settings');

        $this->configuration_section_title             = _('Global configuration');
        $this->configuration_nav_global_access_rights  = _('Global access rights');
        $this->configuration_nav_homepage              = _('Homepage');
        $this->configuration_nav_categories            = _('Public project categories');
        $this->configuration_nav_project_fields        =  _('Project fields');
        $this->configuration_nav_predefined_references = _('Predefined project references');
        $this->configuration_nav_tracker_restore       = _('Pending trackers removal');

        $this->utils_section_title     = _('Global utils');
        $this->utils_nav_system_events = _('System events');
        $this->utils_nav_mass_mail     = _('Mass emailing');

        $this->links_nav_doc = _('Doc');

        $this->plugins_section_title = _('Plugins');
        $this->plugins_manage_all    = _('Manage all plugins');
    }

    public function isTherePendingUsers()
    {
        return $this->pending_users_count > 0;
    }

    public function isThereValidatedUsers()
    {
        return $this->validated_users_count > 0;
    }

    public function isTherePendingProjects()
    {
        return $this->pending_projects_count > 0;
    }
}
