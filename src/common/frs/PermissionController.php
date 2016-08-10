<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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
use ForgeConfig;
use UGroupManager;
use HTTPRequest;
use Feedback;
use PFUser;
use User_ForgeUserGroupFactory;
use ProjectUGroup;

class PermissionController extends BaseFrsPresenter
{
    /** @var UGroupManager */
    private $ugroup_manager;
    /** @var FRSPermissionFactory */
    private $permission_factory;
    /** @var FRSPermissionCreator */
    private $permission_creator;
    /** @var FRSPermissionManager */
    private $permission_manager;
    /** @var UGr */
    private $ugroup_factory;

    public function __construct(
        UGroupManager $ugroup_manager,
        FRSPermissionFactory $permission_factory,
        FRSPermissionCreator $permission_creator,
        FRSPermissionManager $permission_manager,
        User_ForgeUserGroupFactory $ugroup_factory
    ) {
        $this->ugroup_manager     = $ugroup_manager;
        $this->permission_factory = $permission_factory;
        $this->ugroup_factory     = $ugroup_factory;
        $this->permission_creator = $permission_creator;
        $this->permission_manager = $permission_manager;
    }

    public function displayToolbar(Project $project)
    {
        $renderer          = TemplateRendererFactory::build()->getRenderer($this->getTemplateDir());

        $title             = $GLOBALS['Language']->getText('file_file_utils', 'permissions');
        $toolbar_presenter = new ToolbarPresenter($project, $title);

        $toolbar_presenter->setPermissionIsActive();
        $toolbar_presenter->displaySectionNavigation();

        echo $renderer->renderToString('toolbar-presenter', $toolbar_presenter);
    }

    public function displayPermissions(Project $project, PFUser $user)
    {
        if (! $this->permission_manager->isAdmin($project, $user)) {
            return;
        }

        $renderer  = TemplateRendererFactory::build()->getRenderer($this->getTemplateDir());
        $presenter = new PermissionPresenter(
            $project,
            $this->getOptions($project)
        );

        echo $renderer->renderToString('permissions-presenter', $presenter);
    }

    private function getOptions(Project $project)
    {
        $options         = array();
        $project_ugroups = $this->ugroup_factory->getProjectUGroupsWithAdministratorAndMembers($project);
        $frs_ugroups     = $this->permission_factory->getFrsUgroupsByPermission($project, FRSPermission::FRS_ADMIN);

        foreach ($project_ugroups as $project_ugroup) {
            if ($project_ugroup->getId() == ProjectUGroup::ANONYMOUS) {
                continue;
            }

            $selected  = isset($frs_ugroups[$project_ugroup->getId()]) ? true : false;
            $options[] = array(
                'id'       => $project_ugroup->getId(),
                'name'     => $project_ugroup->getName(),
                'selected' => $selected
            );
        }

        return $options;
    }

    public function updatePermissions(Project $project, PFUser $user, array $ugroup_ids)
    {
        if ($project->isError() || ! $this->permission_manager->isAdmin($project, $user)) {
            return;
        }

        $this->permission_creator->savePermissions(
            $project,
            $ugroup_ids
        );

        $GLOBALS['Response']->addFeedback(Feedback::INFO, $GLOBALS['Language']->getText('file_file_utils', 'updated_permissions'));
    }

    private function getTemplateDir()
    {
        return ForgeConfig::get('codendi_dir') .'/src/templates/frs';
    }
}
