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
use FRSPackageFactory;
use FRSReleaseFactory;
use HTTPRequest;
use PermissionsManager;
use PFUser;
use Project;
use ProjectUGroup;
use TemplateRendererFactory;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementFactory;
use User_ForgeUserGroupFactory;
use User_UGroup;
use Valid_UInt;

class FRSPackageController
{
    /** @var FRSPackageFactory  */
    private $package_factory;

    /** @var FRSReleaseFactory  */
    private $release_factory;

    /** @var  User_ForgeUserGroupFactory */
    private $ugroup_factory;

    /** @var PermissionsManager */
    private $permission_manager;

    /** @var LicenseAgreementFactory */
    private $license_agreement_factory;

    public function __construct(
        FRSPackageFactory $package_factory,
        FRSReleaseFactory $release_factory,
        User_ForgeUserGroupFactory $ugroup_factory,
        PermissionsManager $permission_manager,
        LicenseAgreementFactory $license_agreement_factory
    ) {
        $this->release_factory           = $release_factory;
        $this->package_factory           = $package_factory;
        $this->ugroup_factory            = $ugroup_factory;
        $this->permission_manager        = $permission_manager;
        $this->license_agreement_factory = $license_agreement_factory;
    }

    public function delete(HTTPRequest $request, FRSPackage $package, Project $project)
    {
        $valid_package_id = new Valid_UInt('id');
        if ($request->valid($valid_package_id)) {
            $res_release  = $this->release_factory->getFRSReleasesFromDb($package->getPackageID());
            $num_releases = count($res_release);

            if ($num_releases > 0) {
                throw new FRSPackageHasReleaseException();
            }
            if (! $this->package_factory->delete_package($project->getGroupId(), $package->getPackageID())) {
                throw new FRSDeletePackageNotYoursException();
            }

            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('file_admin_editpackages', 'p_del'));
        }
    }

    public function displayCreationForm(Project $project, array $existing_packages)
    {
        $title   = $GLOBALS['Language']->getText('file_admin_editpackages', 'create_new_p');
        $package = new FRSPackage(array('group_id' => $project->getGroupId()));
        frs_display_package_form($package, $title, '?group_id=' . $project->getGroupId() . '&amp;func=create', $existing_packages);
    }

    public function create(HTTPRequest $request, Project $project, array $existing_packages)
    {
        if (! $request->exist('submit')) {
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('file_admin_editpackages', 'create_canceled'));
            $GLOBALS['Response']->redirect('/file/?group_id=' . $project->getGroupId());
        } else {
            $package_data             = $request->get('package');
            $package_data['group_id'] = $project->getGroupId();
            $title                    = $GLOBALS['Language']->getText('file_admin_editpackages', 'create_new_p');

            $url = '?func=create&amp;group_id=' . $project->getGroupId();
            if (isset($package_data['name']) && isset($package_data['rank']) && isset($package_data['status_id'])) {
                if ($this->package_factory->isPackageNameExist($package_data['name'], $project->getGroupId())) {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('file_admin_editpackages', 'p_name_exists'));
                    $package = new FRSPackage($package_data);
                    frs_display_package_form($package, $title, $url, $existing_packages);
                } else {
                    $this->package_factory->create((array) $package_data);
                    $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('file_admin_editpackages', 'p_added'));
                    $GLOBALS['Response']->redirect('/file/?group_id=' . $project->getGroupId());
                }
            }
        }
    }

    public function edit(Project $project, FRSPackage $package, array $existing_packages)
    {
        $title = $GLOBALS['Language']->getText('file_admin_editpackages', 'edit_package');
        frs_display_package_form($package, $title, '?func=update&amp;group_id=' . $project->getGroupId() . '&amp;id=' . $package->getPackageID(), $existing_packages);
    }

    public function update(HTTPRequest $request, FRSPackage $package, Project $project, PFUser $user)
    {
        if (! $request->exist('submit')) {
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('file_admin_editpackages', 'update_canceled'));
            $GLOBALS['Response']->redirect('/file/?group_id=' . $project->getGroupId());
        }

        $package_data = $request->get('package');
        if ($package_data['name'] !== html_entity_decode($package->getName())
            && $this->package_factory->isPackageNameExist($package_data['name'], $project->getGroupId())
        ) {
            throw new FRSPackageNameAlreadyExistsException();
        }

        if ($package_data['status_id'] == $this->package_factory->STATUS_HIDDEN) {
            if ($this->release_factory->isActiveReleases($package->getPackageID())) {
                $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('file_admin_editpackages', 'cannot_hide'));
                $package_data['status_id'] = $this->package_factory->STATUS_ACTIVE;
            }
        }
        $package->setName($package_data['name']);
        $package->setRank($package_data['rank']);
        $package->setStatusId($package_data['status_id']);
        $this->license_agreement_factory->updateLicenseAgreementForPackage($project, $package, (int) $package_data['approve_license']);
        $this->package_factory->update($package);

        $ugroups = array();
        if ($request->get('ugroups')) {
            $ugroups = $request->get('ugroups');
        }
        $override_collection = $this->permission_manager->savePermissions($project, $package->getPackageID(), FRSPackage::PERM_READ, $ugroups);
        $override_collection->emitFeedback("");

        $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('file_admin_editpackages', 'p_updated', $package->getName()));
        $GLOBALS['Response']->redirect('/file/?group_id=' . $project->getGroupId());
    }

    public function displayUserGroups(Project $project, $permission_type, $object_id = null)
    {
        $renderer            = TemplateRendererFactory::build()->getRenderer($this->getTemplateDir());
        $all_project_ugroups = $this->ugroup_factory->getAllForProject($project);
        $ugroups             = $this->getFrsUGroupsByPermission($permission_type, $all_project_ugroups, $object_id);

        $presenter = new FRSPackagePermissionPresenter(
            $project,
            $ugroups,
            $permission_type
        );

        $renderer->renderToPage('permissions-presenter', $presenter);
    }


    private function getFrsUGroupsByPermission($permission_type, array $project_ugroups, $object_id = null)
    {
        $options         = array();
        foreach ($project_ugroups as $project_ugroup) {
            if ($this->isUgroupHidden($project_ugroup)) {
                continue;
            }

            $package_ugroups = $this->getAllUserGroups($permission_type, $object_id);

            $options[] = array(
                'id'       => $project_ugroup->getId(),
                'name'     => $project_ugroup->getName(),
                'selected' => $this->isUgroupSelected($project_ugroup, $package_ugroups)
            );
        }

        return $options;
    }

    private function isUgroupHidden(User_UGroup $project_ugroup)
    {
        return (int) $project_ugroup->getId() === ProjectUGroup::PROJECT_ADMIN;
    }

    private function getAllUserGroups($permission_type, $object_id)
    {
        $ugroups = array();

        $package_ugroups = permission_db_authorized_ugroups($permission_type, $object_id);
        while ($ugroup = db_fetch_array($package_ugroups)) {
            $ugroups[] = $ugroup['ugroup_id'];
        }

        return $ugroups;
    }

    private function isUgroupSelected(User_UGroup $user_group, array $package_ugroups)
    {
        return in_array($user_group->getId(), $package_ugroups);
    }

    private function getTemplateDir()
    {
        return ForgeConfig::get('codendi_dir') . '/src/templates/frs';
    }
}
