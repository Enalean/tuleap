<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

class MediawikiSiteAdminAllowedProjectsPresenter //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    public const string TEMPLATE = 'grant-only-allowed-projects';

    /**
     * @var Project[]
     */
    public $allowed_projects;

    private $count_project_to_migrate;

    public function __construct($allowed_projects, $count_projects_to_migrate)
    {
        $this->allowed_projects         = $allowed_projects;
        $this->count_project_to_migrate = $count_projects_to_migrate;
    }

    public function there_is_no_project() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return count($this->allowed_projects) === 0;
    }

    public function update_allowed_projects_action() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return '/plugins/mediawiki/forge_admin.php?action=site_update_allowed_project_list';
    }

    public function update_allowed_projects_action_csrf() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return new CSRFSynchronizerToken($this->update_allowed_projects_action());
    }

    public function resource_allowed_project_title() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return dgettext('tuleap-mediawiki', 'MediaWiki');
    }

    public function resource_allowed_project_subtitle() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return dgettext('tuleap-mediawiki', 'Projects that use Mediawiki 1.23 (LTS)');
    }

    public function information()
    {
        if ($this->is_resource_restricted()) {
            return dgettext('tuleap-mediawiki', 'Please note that upgrading to Mediawiki 1.23 is a one-way process there is no way to revert this process. This progressive migration was meant to mitigate the risks of errors during upgrade process. Please ask the upgraded projects if they noticed any troubles and to report related issues.');
        } else {
            return dgettext('tuleap-mediawiki', 'All projects where upgraded to Mediawiki 1.23. This is a one-way process so it\'s not possible downgrade.');
        }
    }

    public function resource_allowed_project_list_allow_placeholder() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return dgettext('tuleap-mediawiki', 'Project name');
    }

    public function resource_allowed_project_list_filter_placeholder() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return dgettext('tuleap-mediawiki', 'Project id or project name');
    }

    public function resource_allowed_project_list_allow_project() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return dgettext('tuleap-mediawiki', 'Migrate');
    }

    public function resource_allowed_project_list_id() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return dgettext('tuleap-mediawiki', 'Id');
    }

    public function resource_allowed_project_list_name() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return dgettext('tuleap-mediawiki', 'Name');
    }

    public function resource_allowed_project_list_empty() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return dgettext('tuleap-mediawiki', 'Currently, there are no projects that uses Mediawiki 1.23.');
    }

    public function url_project() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return MEDIAWIKI_BASE_URL . '/wiki';
    }

    public function resource_allowed_project_filter_empty() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return $GLOBALS['Language']->getText('admin', 'allowed_projects_filter_empty');
    }

    public function allow_all_enabled() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return true;
    }

    public function restricted_resource_action() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return '/plugins/mediawiki/forge_admin.php?action=site_update_allow_all_projects';
    }

    public function restricted_resource_action_csrf() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return new CSRFSynchronizerToken($this->restricted_resource_action());
    }

    public function is_resource_restricted() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return $this->count_project_to_migrate > 0;
    }

    public function can_be_unrestricited() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return ! $this->is_resource_restricted();
    }

    public function resource_allowed_project_allow_all() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return dgettext('tuleap-mediawiki', 'Migrate all projects to Mediawiki 1.23');
    }

    public function resource_allowed_project_list() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return dgettext('tuleap-mediawiki', 'List of migrated projects');
    }
}
