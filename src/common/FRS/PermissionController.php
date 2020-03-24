<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\FRS;

use TemplateRendererFactory;
use Project;
use Service;
use ForgeConfig;
use User_UGroup;
use Feedback;
use PFUser;
use User_ForgeUserGroupFactory;
use ProjectUGroup;
use ServiceFile;
use TemplateRenderer;

class PermissionController extends BaseFrsPresenter
{
    /** @var FRSPermissionFactory */
    private $permission_factory;
    /** @var FRSPermissionCreator */
    private $permission_creator;
    /** @var FRSPermissionManager */
    private $permission_manager;
    /** @var User_ForgeUserGroupFactory */
    private $ugroup_factory;

    public function __construct(
        FRSPermissionFactory $permission_factory,
        FRSPermissionCreator $permission_creator,
        FRSPermissionManager $permission_manager,
        User_ForgeUserGroupFactory $ugroup_factory
    ) {
        $this->permission_factory = $permission_factory;
        $this->ugroup_factory     = $ugroup_factory;
        $this->permission_creator = $permission_creator;
        $this->permission_manager = $permission_manager;
    }

    public function displayPermissions(Project $project, PFUser $user)
    {
        if (! $this->permission_manager->isAdmin($project, $user)) {
            return;
        }

        $service = $project->getService(Service::FILE);
        if ($service === null) {
            return;
        }
        assert($service instanceof ServiceFile);
        $renderer = $this->getRenderer();

        $this->displayHeader($service, $renderer);

        $all_project_ugroups   = $this->ugroup_factory->getAllForProject($project);
        $admin_project_ugroups = $this->ugroup_factory->getProjectUGroupsWithAdministratorAndMembers($project);

        $presenter = new PermissionPresenter(
            $project,
            $this->getFrsUGroupsByPermission($project, FRSPermission::FRS_ADMIN, $admin_project_ugroups),
            $this->getFrsUGroupsByPermission($project, FRSPermission::FRS_READER, $all_project_ugroups)
        );

        $renderer->renderToPage('permissions-global-presenter', $presenter);

        $service->displayFooter();
    }

    private function getFrsUGroupsByPermission(Project $project, $permission_type, array $project_ugroups)
    {
        $options     = array();
        $frs_ugroups = $this->permission_factory->getFrsUGroupsByPermission($project, $permission_type);

        foreach ($project_ugroups as $project_ugroup) {
            if ($this->isUgroupHidden($project_ugroup)) {
                continue;
            }

            $options[] = array(
                'id'       => $project_ugroup->getId(),
                'name'     => $project_ugroup->getName(),
                'selected' => $this->isUgroupSelected($frs_ugroups, $project_ugroup)
            );
        }

        return $options;
    }

    private function isUgroupHidden(User_UGroup $project_ugroup)
    {
        return (int) $project_ugroup->getId() === ProjectUGroup::PROJECT_ADMIN;
    }

    private function isUgroupSelected(array $frs_ugroups, User_UGroup $project_ugroup)
    {
        return isset($frs_ugroups[$project_ugroup->getId()]);
    }

    public function updatePermissions(Project $project, PFUser $user, array $admin_ugroup_ids, array $reader_group_ids)
    {
        if ($project->isError() || ! $this->permission_manager->isAdmin($project, $user)) {
            return;
        }

        $this->permission_creator->savePermissions(
            $project,
            $admin_ugroup_ids,
            FRSPermission::FRS_ADMIN
        );

        $this->permission_creator->savePermissions(
            $project,
            $reader_group_ids,
            FRSPermission::FRS_READER
        );

        $GLOBALS['Response']->addFeedback(Feedback::INFO, $GLOBALS['Language']->getText('file_file_utils', 'updated_permissions'));
    }

    private function displayHeader(ServiceFile $service, TemplateRenderer $renderer)
    {
        $project = $service->getProject();

        $title = _('Files Administration');
        $toolbar_presenter = new ToolbarPresenter($project);

        $toolbar_presenter->setPermissionIsActive();
        $toolbar_presenter->displaySectionNavigation();

        $service->displayFRSHeader($project, $title);
        $renderer->renderToPage('toolbar-presenter', $toolbar_presenter);
    }

    /** @return TemplateRenderer */
    private function getRenderer()
    {
        $template_dir = ForgeConfig::get('codendi_dir') . '/src/templates/frs';

        return TemplateRendererFactory::build()->getRenderer($template_dir);
    }
}
