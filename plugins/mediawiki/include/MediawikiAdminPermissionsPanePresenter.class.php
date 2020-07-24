<?php
/**
 * Copyright (c) Enalean, 2014 - 2015. All rights reserved
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

class MediawikiAdminPermissionsPanePresenter extends MediawikiAdminPanePresenter
{

    public $groups_permissions;
    public $read_ugroups;
    public $write_ugroups;

    private $is_compatibility_view_enabled = true;

    public function __construct(
        Project $project,
        array $groups_permissions,
        $is_compatibility_view_enabled,
        array $read_ugroups,
        array $write_ugroups
    ) {
        parent::__construct($project);
        $this->groups_permissions            = $groups_permissions;
        $this->is_compatibility_view_enabled = $is_compatibility_view_enabled;
        $this->read_ugroups                  = $read_ugroups;
        $this->write_ugroups                 = $write_ugroups;
    }

    public function can_show_options()
    {
        return forge_get_config('enable_compatibility_view', 'mediawiki');
    }

    public function is_compatibility_enabled_value()
    {
        return $this->is_compatibility_view_enabled;
    }

    public function compatibility_view_text()
    {
        return dgettext('tuleap-mediawiki', 'Tick this box if you want to activate Mediawiki in full screen. This compatibility mode will let you use WikiEditor extension, not available in standard embedded view.');
    }

    public function title()
    {
        return dgettext('tuleap-mediawiki', 'Mediawiki groups mapping');
    }

    public function help_intro()
    {
        return sprintf(dgettext('tuleap-mediawiki', 'Here you can map groups between %1$s and MediaWiki'), ForgeConfig::get('sys_name'));
    }

    public function help_link()
    {
        return sprintf(dgettext('tuleap-mediawiki', 'You can find more informations on what MediaWiki groups can do here: <a href="%1$s">Special:User groups rights</a>'), $this->getMWUrl('Special:ListGroupRights'));
    }

    public function help_project()
    {
        if ($this->project->isPublic()) {
            return dgettext('tuleap-mediawiki', 'The two MediaWiki groups "Anonymous" and "User" groups are hardcoded to "All users" and "Registered users". In this project (public) it means that all users will be able to browse the wiki content and all Registered users will be able to edit and create page (see the detailed list of permissions in the User Rights page).');
        }

        return dgettext('tuleap-mediawiki', 'The two MediaWiki groups "Anonymous" and "User" groups are hardcoded to "Nobody" and "Registered users". In this project (private) it means that only project members can access the wiki and they will be able to edit and create pages (see the detailed list of permissions in the User Rights page).');
    }

    private function getMWUrl($page)
    {
        return MEDIAWIKI_BASE_URL . '/wiki/' . $this->project->getUnixName() . '/index.php/' . $page;
    }

    public function route()
    {
        return MEDIAWIKI_BASE_URL . '/forge_admin.php?' . http_build_query([
            'group_id' => $this->project->getID(),
            'action'   => 'save_permissions'
        ]);
    }

    public function submit_label()
    {
        return dgettext('tuleap-mediawiki', 'Save all changes');
    }

    public function or_string()
    {
        return dgettext('tuleap-mediawiki', 'or');
    }

    public function restore_label()
    {
        return dgettext('tuleap-mediawiki', 'Apply defaults');
    }

    public function options_title()
    {
        return dgettext('tuleap-mediawiki', 'Options');
    }

    public function access_control_title()
    {
        return dgettext('tuleap-mediawiki', 'Access control');
    }

    public function access_control_intro()
    {
        return dgettext('tuleap-mediawiki', 'This section allows you to define the user groups that can read and/or write content in this Mediawiki.');
    }

    public function read()
    {
        return dgettext('tuleap-mediawiki', 'Read');
    }

    public function write()
    {
        return dgettext('tuleap-mediawiki', 'Write');
    }
}
