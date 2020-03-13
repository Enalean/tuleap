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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\FRS;

require_once __DIR__ . '/../../www/file/file_utils.php';

use ForgeConfig;
use FRSPackage;
use FRSRelease;
use FRSReleaseFactory;
use HTTPRequest;
use Project;
use ProjectUGroup;
use TemplateRendererFactory;
use User_ForgeUserGroupFactory;
use User_UGroup;

class FRSReleaseController
{
    /** @var FRSReleaseFactory */
    private $release_factory;

    /** @var User_ForgeUserGroupFactory  */
    private $ugroup_factory;

    public function __construct(FRSReleaseFactory $release_factory, User_ForgeUserGroupFactory $ugroup_factory)
    {
        $this->release_factory = $release_factory;
        $this->ugroup_factory  = $ugroup_factory;
    }

    public function delete(Project $project, FRSRelease $release)
    {
        if (! $this->release_factory->delete_release($project->getGroupId(), $release->getReleaseID())) {
            throw new FRSDeleteReleaseNotYoursException();
        }

        $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('file_admin_editreleases', 'rel_del'));
        $GLOBALS['Response']->redirect('/file/?group_id=' . $project->getGroupId());
    }

    public function add(Project $project, $package_id)
    {
        $release = new FRSRelease();
        $release->setPackageID($package_id);
        $release->setStatusID($this->release_factory->STATUS_ACTIVE);
        $release->setReleaseDate(time());

        $title = $GLOBALS['Language']->getText('file_admin_editreleases', 'create_new_release');
        $url   = '?func=create&amp;postExpected=&amp;group_id=' . $project->getGroupId() . '&amp;package_id=' . $package_id;
        frs_display_release_form($is_update = false, $release, $project->getGroupId(), $title, $url);
    }

    public function create(HTTPRequest $request, Project $project, FRSPackage $package)
    {
        if ($request->exist('cancel')) {
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('file_admin_editreleases', 'create_canceled'));
            $GLOBALS['Response']->redirect('/file/?group_id=' . $project->getGroupId());
        }

        frs_process_release_form(
            $is_update = false,
            $request,
            $project->getGroupId(),
            $GLOBALS['Language']->getText('file_admin_editreleases', 'release_new_file_version'),
            '?func=create&amp;postExpected=&amp;group_id=' . $project->getGroupId() . '&amp;package_id=' . $package->getPackageID()
        );
    }

    public function displayForm(Project $project, FRSRelease $release)
    {
        frs_display_release_form(
            $is_update = true,
            $release,
            $project->getGroupId(),
            $GLOBALS['Language']->getText('file_admin_editreleases', 'edit_release'),
            '?func=update&amp;postExpected=&amp;group_id=' . $project->getGroupId() . '&amp;package_id=' . $release->getPackageID() . '&amp;id=' . $release->getReleaseID()
        );
    }

    public function update(HTTPRequest $request, Project $project, FRSRelease $release)
    {
        frs_process_release_form(
            $is_update = true,
            $request,
            $project->getGroupId(),
            $GLOBALS['Language']->getText('file_admin_editreleases', 'edit_release'),
            '?func=update&amp;postExpected=&amp;group_id=' . $project->getGroupId() . '&amp;package_id=' . $release->getPackageID() . '&amp;id=' . $release->getReleaseID()
        );
    }

    public function displayUserGroups(Project $project, $permission_type, $release_id = null)
    {
        $renderer            = TemplateRendererFactory::build()->getRenderer($this->getTemplateDir());
        $all_project_ugroups = $this->ugroup_factory->getAllForProject($project);
        $ugroups             = $this->getFrsUGroupsByPermission($permission_type, $all_project_ugroups, $release_id);

        $presenter = new FRSReleasePermissionPresenter(
            $project,
            $ugroups,
            $permission_type
        );

        $renderer->renderToPage('permissions-presenter', $presenter);
    }


    private function getFrsUGroupsByPermission($permission_type, array $project_ugroups, $release_id = null)
    {
        $options         = array();
        foreach ($project_ugroups as $project_ugroup) {
            if ($this->isUgroupHidden($project_ugroup)) {
                continue;
            }

            $release_ugroups = $this->getAllUserGroups($permission_type, $release_id);

            $options[] = array(
                'id'       => $project_ugroup->getId(),
                'name'     => $project_ugroup->getName(),
                'selected' => $this->isUgroupSelected($project_ugroup, $release_ugroups)
            );
        }

        return $options;
    }

    private function isUgroupHidden(User_UGroup $project_ugroup)
    {
        return (int) $project_ugroup->getId() === ProjectUGroup::PROJECT_ADMIN;
    }

    private function getAllUserGroups($permission_type, $release_id)
    {
        $ugroups = array();

        $release_ugroups = permission_db_authorized_ugroups($permission_type, $release_id);
        while ($ugroup = db_fetch_array($release_ugroups)) {
            $ugroups[] = $ugroup['ugroup_id'];
        }

        return $ugroups;
    }

    private function isUgroupSelected(User_UGroup $user_group, array $release_ugroups)
    {
        return in_array($user_group->getId(), $release_ugroups);
    }

    private function getTemplateDir()
    {
        return ForgeConfig::get('codendi_dir') . '/src/templates/frs';
    }
}
