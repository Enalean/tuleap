<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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
    public $ppending_news_count;
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
    public $utils_nav_news;
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
        $pending_news_count,
        $plugins
    ) {
        $this->all_users_count              = $all_users_count;
        $this->users_need_approval          = $users_need_approval;
        $this->pending_users_count          = $pending_users_count;
        $this->validated_users_count        = $validated_users_count;
        $this->all_projects_count           = $all_projects_count;
        $this->pending_projects_count       = $pending_projects_count;
        $this->pending_news_count           = $pending_news_count;
        $this->plugins                      = $plugins;

        $this->users_section_title                     = $GLOBALS['Language']->getText('admin_sidebar', 'users_section_title');
        $this->users_search_placeholder                = $GLOBALS['Language']->getText('admin_sidebar', 'users_search_placeholder');
        $this->users_nav_all_users                     = $GLOBALS['Language']->getText('admin_sidebar', 'users_nav_all_users');
        $this->users_nav_new_user                      = $GLOBALS['Language']->getText('admin_sidebar', 'users_nav_new_user');
        $this->users_nav_pending_users                 = $GLOBALS['Language']->getText('admin_sidebar', 'users_nav_pending_users');
        $this->users_nav_validated_users               = $GLOBALS['Language']->getText('admin_sidebar', 'users_nav_validated_users');
        $this->users_nav_permission_delegation         = $GLOBALS['Language']->getText('admin_sidebar', 'users_nav_permission_delegation');

        $this->projects_section_title                  = $GLOBALS['Language']->getText('admin_sidebar', 'projects_section_title');
        $this->projects_search_placeholder             = $GLOBALS['Language']->getText('admin_sidebar', 'projects_search_placeholder');
        $this->projects_nav_all_projects               = $GLOBALS['Language']->getText('admin_sidebar', 'projects_nav_all_projects');
        $this->projects_nav_pending_projects           = $GLOBALS['Language']->getText('admin_sidebar', 'projects_nav_pending_projects');
        $this->projects_nav_configuration              = $GLOBALS['Language']->getText('admin_sidebar', 'projects_nav_configuration');

        $this->configuration_section_title             = $GLOBALS['Language']->getText('admin_sidebar', 'configuration_section_title');
        $this->configuration_nav_global_access_rights  = $GLOBALS['Language']->getText('admin_sidebar', 'configuration_nav_global_access_rights');
        $this->configuration_nav_homepage              = $GLOBALS['Language']->getText('admin_sidebar', 'configuration_nav_homepage');
        $this->configuration_nav_categories            = $GLOBALS['Language']->getText('admin_sidebar', 'configuration_nav_categories');
        $this->configuration_nav_project_fields        =  _('Project fields');
        $this->configuration_nav_predefined_references = $GLOBALS['Language']->getText('admin_sidebar', 'configuration_nav_predefined_references');
        $this->configuration_nav_tracker_restore       = $GLOBALS['Language']->getText('admin_sidebar', 'configuration_nav_tracker_restore');
        $this->configuration_nav_svn                   = $GLOBALS['Language']->getText('admin_sidebar', 'configuration_nav_svn');

        $this->utils_section_title                     = $GLOBALS['Language']->getText('admin_sidebar', 'utils_section_title');
        $this->utils_nav_system_events                 = $GLOBALS['Language']->getText('admin_sidebar', 'utils_nav_system_events');
        $this->utils_nav_news                          = $GLOBALS['Language']->getText('admin_sidebar', 'utils_nav_news');
        $this->utils_nav_mass_mail                     = $GLOBALS['Language']->getText('admin_sidebar', 'utils_nav_mass_mail');

        $this->links_nav_doc                           = $GLOBALS['Language']->getText('admin_sidebar', 'links_nav_doc');

        $this->plugins_section_title = $GLOBALS['Language']->getText('admin_main', 'header_plugins');
        $this->plugins_manage_all    = $GLOBALS['Language']->getText('admin_main', 'manage_all_plugins');
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

    public function isTherePendingNews()
    {
        return $this->pending_news_count > 0;
    }
}
