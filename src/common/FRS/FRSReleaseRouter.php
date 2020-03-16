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

use FRSPackageFactory;
use FRSReleaseFactory;
use HTTPRequest;
use PFUser;
use Project;

class FRSReleaseRouter
{
    /** @var FRSReleaseController */
    private $release_controller;

    /** @var FRSReleaseFactory */
    private $release_factory;

    /** @var FRSPackageFactory */
    private $package_factory;

    public function __construct(
        FRSReleaseController $release_controller,
        FRSReleaseFactory $release_factory,
        FRSPackageFactory $package_factory
    ) {
        $this->release_controller = $release_controller;
        $this->release_factory    = $release_factory;
        $this->package_factory    = $package_factory;
    }

    public function route(HTTPRequest $request, Project $project, PFUser $user)
    {
        if (! $this->package_factory->userCanAdmin($user, $project->getGroupId())) {
            exit_permission_denied();
        }

        if ($request->get('id')) {
            $release_id = $request->get('id');
            $release    = $this->release_factory->getFRSReleaseFromDb($release_id, $project->getGroupId());
        }

        $package_id = $request->get('package_id');
        $package    = $this->package_factory->getFRSPackageFromDb($package_id, $project->getGroupId());
        if (! $package) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('file_admin_editpackages', 'p_not_exists'));
            $GLOBALS['Response']->redirect('/file/?group_id=' . $project->getGroupId());
        }

        switch ($request->get('func')) {
            case 'delete':
                try {
                    $this->release_controller->delete($project, $release);
                } catch (FRSDeleteReleaseNotYoursException $e) {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('file_admin_editreleases', 'rel_not_yours'));
                    $GLOBALS['Response']->redirect('/file/?group_id=' . $project->getGroupId());
                }
                break;
            case 'add':
                $this->release_controller->add($project, $package->getPackageID());
                break;
            case 'create':
                $this->release_controller->create($request, $project, $package);
                break;
            case 'edit':
                $this->release_controller->displayForm($project, $release);
                break;
            case 'update':
                $this->release_controller->update($request, $project, $release);
                break;
            default:
                $this->release_controller->displayForm($project, $release);
                break;
        }
    }
}
