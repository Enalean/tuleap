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

namespace Tuleap\PHPWiki\PerGroup;

use ForgeConfig;
use Project;
use TemplateRendererFactory;
use Tuleap\Project\Admin\PerGroup\PermissionPerGroupPanePresenter;
use Tuleap\Project\Admin\PerGroup\PermissionPerGroupUGroupFormatter;
use Tuleap\Project\Admin\Permission\PermissionPerGroupCollection;
use Tuleap\Project\Admin\Permission\PermissionPerGroupPaneCollector;
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

    public function __construct(
        Wiki_PermissionsManager $wiki_permissions_manager,
        PermissionPerGroupUGroupFormatter $formatter,
        UGroupManager $ugroup_manager
    ) {
        $this->formatter                = $formatter;
        $this->ugroup_manager           = $ugroup_manager;
        $this->wiki_permissions_manager = $wiki_permissions_manager;
    }

    public function buildPane(Project $project, PermissionPerGroupPaneCollector $event, $selected_ugroup_id)
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

        $ugroup = $this->ugroup_manager->getUGroup($event->getProject(), $selected_ugroup_id);

        $presenter = new PermissionPerGroupPanePresenter(
            $permissions->getPermissions(),
            $ugroup
        );

        $templates_dir = ForgeConfig::get('tuleap_dir') . '/src/templates/wiki/';
        $content       = TemplateRendererFactory::build()
            ->getRenderer($templates_dir)
            ->renderToString('project-admin-permission-per-group', $presenter);

        $event->addAdditionalPane($content);
    }

    /**
     * @return array
     */
    private function extractServicePermissions(
        Project $project,
        PermissionPerGroupCollection $permissions,
        $selected_ugroup_id = null
    ) {
        $ugroups = $this->getUgroups($selected_ugroup_id);

        if (count($ugroups) === 0) {
            return;
        }

        $formatted_group = [];
        foreach ($ugroups as $wiki_admins_group) {
            $formatted_group[] =  $this->formatter->formatGroup($project, $wiki_admins_group);
        }

        $permissions->addPermissions(array(
            'name' => _('Administrator'),
            'groups' => $formatted_group
        ));
    }

    /**
     * @return array
     */
    private function getUgroups($selected_ugroup_id)
    {
        if ($selected_ugroup_id) {
            $ugroups = in_array($selected_ugroup_id, $this->wiki_permissions_manager->getWikiAdminsGroups()) ? $this->wiki_permissions_manager->getWikiAdminsGroups() : [];
        } else {
            $ugroups = $this->wiki_permissions_manager->getWikiAdminsGroups();
        }

        return $ugroups;
    }
}
