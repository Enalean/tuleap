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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\FRS;

use FRSPackage;
use FRSPackageFactory;
use FRSReleaseFactory;
use HTTPRequest;
use PFUser;
use Project;
use Valid_UInt;

class FRSPackageController
{
    /** @var FRSPackageFactory  */
    private $package_factory;

    /** @var FRSReleaseFactory  */
    private $release_factory;

    public function __construct(
        FRSPackageFactory $package_factory,
        FRSReleaseFactory $release_factory
    ) {
        $this->release_factory = $release_factory;
        $this->package_factory = $package_factory;
    }

    public function delete(HTTPRequest $request, FRSPackage $package, Project $project)
    {
        $valid_package_id = new Valid_UInt('id');
        if ($request->valid($valid_package_id)) {
            $res_release  = $this->release_factory->getFRSReleasesFromDb($package->getPackageID(), null, $project->getGroupId());
            $num_releases = count($res_release);

            if ($num_releases>0) {
                throw new FRSPackageHasReleaseException();
            }
            if (! $this->package_factory->delete_package($project->getGroupId(), $package->getPackageID())) {
                throw new FRSDeletePackageNotYoursException();
            }

            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('file_admin_editpackages', 'p_del'));
        }
        $GLOBALS['Response']->redirect('/file/?group_id='.$project->getGroupId());
    }

    public function displayCreationForm(Project $project, array $existing_packages)
    {
        $title   = $GLOBALS['Language']->getText('file_admin_editpackages', 'create_new_p');
        $package = new FRSPackage(array('group_id' => $project->getGroupId()));
        frs_display_package_form($package, $title, '?group_id='. $project->getGroupId() .'&amp;func=create', $existing_packages);
    }

    public function create(HTTPRequest $request, Project $project, array $existing_packages)
    {
        if (! $request->exist('submit')) {
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('file_admin_editpackages', 'create_canceled'));
            $GLOBALS['Response']->redirect('/file/?group_id='.$project->getGroupId());
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
                    $this->package_factory->create($package_data);
                    $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('file_admin_editpackages', 'p_added'));
                    $GLOBALS['Response']->redirect('/file/?group_id='.$project->getGroupId());
                }
            }
        }
    }

    public function edit(Project $project, FRSPackage $package, array $existing_packages)
    {
        $title = $GLOBALS['Language']->getText('file_admin_editpackages', 'edit_package');
        frs_display_package_form($package, $title, '?func=update&amp;group_id='. $project->getGroupId() .'&amp;id='. $package->getPackageID(), $existing_packages);
    }

    public function update(HTTPRequest $request, FRSPackage $package, Project $project, PFUser $user)
    {
        if (! $request->exist('submit')) {
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('file_admin_editpackages', 'update_canceled'));
            $GLOBALS['Response']->redirect('/file/?group_id='.$project->getGroupId());
        }

        $package_data = $request->get('package');
        if (! ($package_data['name'] == html_entity_decode($package->getName())
            && $this->package_factory->isPackageNameExist($package_data['name'], $project->getGroupId()))
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
        $package->setApproveLicense($package_data['approve_license']);
        $this->package_factory->update($package);

        //Permissions
        $vUgroups = new Valid_UInt('ugroups');
        if (! $request->validArray($vUgroups)) {
            $GLOBALS['Response']->redirect('../showfiles.php?group_id='.$project->getGroupId());
        }
        $ugroups = $request->get('ugroups');

        list ($return_code, $feedback) = permission_process_selection_form($project->getGroupId(), 'PACKAGE_READ', $package->getPackageID(), $ugroups);
        if (!$return_code) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('file_admin_editpackages', 'perm_update_err'));
            $GLOBALS['Response']->addFeedback('error', $feedback);
        } else {
            $package_is_updated = true;
        }

        if ($package_is_updated) {
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('file_admin_editpackages', 'p_updated', $package->getName()));
        } else {
            $GLOBALS['Response']->addFeedback('info', 'Package not updated');
        }
        $GLOBALS['Response']->redirect('/file/?group_id='.$project->getGroupId());
    }
}
