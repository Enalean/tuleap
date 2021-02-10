<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\ProjectAdmin;

use TemplateRenderer;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupCollection;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupPaneCollector;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupPanePresenter;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupFormatter;
use Tuleap\ProgramManagement\Adapter\Program\Plan\CanPrioritizeFeaturesDAO;
use UGroupManager;

class PermissionPerGroupSectionBuilder
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
     * @var TemplateRenderer
     */
    private $template_renderer;
    /**
     * @var CanPrioritizeFeaturesDAO
     */
    private $can_prioritize_features_dao;

    public function __construct(
        CanPrioritizeFeaturesDAO $can_prioritize_features_dao,
        PermissionPerGroupUGroupFormatter $formatter,
        UGroupManager $ugroup_manager,
        TemplateRenderer $template_renderer
    ) {
        $this->can_prioritize_features_dao = $can_prioritize_features_dao;
        $this->formatter                   = $formatter;
        $this->ugroup_manager              = $ugroup_manager;
        $this->template_renderer           = $template_renderer;
    }

    public function collectSections(PermissionPerGroupPaneCollector $event): void
    {
        $project = $event->getProject();
        $service = $project->getService(\program_managementPlugin::SERVICE_SHORTNAME);
        if ($service === null) {
            return;
        }

        $ugroup_ids = $this->can_prioritize_features_dao->searchUserGroupIDsWhoCanPrioritizeFeaturesByProjectID((int) $project->getID());
        if (count($ugroup_ids) === 0) {
            return;
        }

        $selected_ugroup = $this->ugroup_manager->getUGroup($project, $event->getSelectedUGroupId());
        if ($selected_ugroup !== null) {
            $selected_ugroup_id = $selected_ugroup->getId();
            if (! in_array($selected_ugroup_id, $ugroup_ids, true)) {
                $formatted_ugroups = [];
            } else {
                $formatted_ugroups = $this->formatter->getFormattedUGroups($project, [$selected_ugroup_id]);
            }
        } else {
            $formatted_ugroups = $this->formatter->getFormattedUGroups($project, $ugroup_ids);
        }

        $permissions = new PermissionPerGroupCollection();
        if (count($formatted_ugroups) > 0) {
            $permissions->addPermissions(
                [
                    'name'   => dgettext('tuleap-program_management', 'Can prioritize features'),
                    'groups' => $formatted_ugroups,
                ]
            );
        }

        $presenter = new PermissionPerGroupPanePresenter(
            $permissions->getPermissions(),
            $selected_ugroup
        );

        $event->addPane(
            $this->template_renderer->renderToString('project-admin-permission-per-group', $presenter),
            $service->getRank()
        );
    }
}
