<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\PHPWiki\PermissionsPerGroup;

use ForgeConfig;
use Project;
use TemplateRendererFactory;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupPanePresenter;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupFormatter;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupCollection;
use UGroupManager;
use Wiki_PermissionsManager;

class PHPWikiPermissionPerGroupPaneBuilder
{
    /**
     * @var PermissionPerGroupUGroupFormatter
     */
    private $formatter;

    /**
     * @var UGroupManager
     */
    private $ugroup_manager;

    /**
     * @var Wiki_PermissionsManager
     */
    private $wiki_permissions_manager;

    /**
     * @var TemplateRendererFactory
     */
    private $template_renderer_factory;

    public function __construct(
        Wiki_PermissionsManager $wiki_permissions_manager,
        PermissionPerGroupUGroupFormatter $formatter,
        UGroupManager $ugroup_manager,
        TemplateRendererFactory $template_renderer_factory
    ) {
        $this->formatter                 = $formatter;
        $this->ugroup_manager            = $ugroup_manager;
        $this->wiki_permissions_manager  = $wiki_permissions_manager;
        $this->template_renderer_factory = $template_renderer_factory;
    }

    public function getPaneContent(Project $project, $selected_ugroup_id)
    {
        if (! $project->usesWiki()) {
            return;
        }

        $permissions = new PermissionPerGroupCollection();
        $this->extractServicePermissions(
            $project,
            $permissions,
            $selected_ugroup_id
        );

        $ugroup = $this->ugroup_manager->getUGroup($project, $selected_ugroup_id);

        $presenter = new PermissionPerGroupPanePresenter(
            $permissions->getPermissions(),
            $ugroup
        );

        $templates_dir = ForgeConfig::get('tuleap_dir') . '/src/templates/wiki/';

        return $this->template_renderer_factory
            ->getRenderer($templates_dir)
            ->renderToString('project-admin-permission-per-group', $presenter);
    }

    /**
     * @return array
     */
    private function extractServicePermissions(
        Project $project,
        PermissionPerGroupCollection $permissions,
        $selected_ugroup_id = null
    ) {
        $this->addAdministrationPermission($project, $permissions, $selected_ugroup_id);
        $this->addGlobalAccessPermission($project, $permissions, $selected_ugroup_id);
    }

    private function addAdministrationPermission(
        Project $project,
        PermissionPerGroupCollection $permissions,
        $selected_ugroup_id = null
    ) {
        $ugroups = $this->getAdministrationUgroups($selected_ugroup_id);

        if (count($ugroups) === 0) {
            return;
        }

        $formatted_group = $this->formatter->getFormattedUgroups($project, $ugroups);

        $permissions->addPermissions(
            [
                'name'    => _('Administrator'),
                'groups'  => $formatted_group,
                'url'     => ''
            ]
        );
    }

    /**
     * @return array
     */
    private function getAdministrationUgroups($selected_ugroup_id)
    {
        return $this->getUgroups(
            $this->wiki_permissions_manager->getWikiAdminsGroups(),
            $selected_ugroup_id
        );
    }

    private function addGlobalAccessPermission(
        Project $project,
        PermissionPerGroupCollection $permissions,
        $selected_ugroup_id = null
    ) {
        $ugroups = $this->getGlobalAccessUgroups($project, $selected_ugroup_id);

        if (count($ugroups) === 0) {
            return;
        }

        $formatted_group = $this->formatter->getFormattedUgroups($project, $ugroups);

        $permissions->addPermissions(
            [
                'name'    => _('Global access'),
                'groups'  => $formatted_group,
                'url'     => $this->getGlobalAdminLink($project)
            ]
        );
    }

    /**
     * @return array
     */
    private function getGlobalAccessUgroups(Project $project, $selected_ugroup_id)
    {
        return $this->getUgroups(
            $this->wiki_permissions_manager->getWikiServicePermissions($project),
            $selected_ugroup_id
        );
    }

    private function getUgroups(array $ugroups, $selected_ugroup_id)
    {
        if ($selected_ugroup_id && ! in_array($selected_ugroup_id, $ugroups)) {
            return [];
        }

        return $ugroups;
    }

    private function getGlobalAdminLink(Project $project)
    {
        return '/wiki/admin/index.php?' . http_build_query(
            [
                "group_id" => $project->getID(),
                "view"     => "wikiPerms"
            ]
        );
    }
}
