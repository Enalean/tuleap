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
use HTTPRequest;
use PFUser;
use Project;

class FRSPackageRouter
{
    /** @var FRSPackageController  */
    private $package_controller;

    /** @var FRSPackageFactory */
    private $package_factory;

    /** @var FRSPermissionManager */
    private $permission_manager;

    public function __construct(
        FRSPackageController $package_controller,
        FRSPackageFactory $package_factory,
        FRSPermissionManager $permission_manager
    ) {
        $this->package_controller         = $package_controller;
        $this->package_factory            = $package_factory;
        $this->permission_manager         = $permission_manager;
    }

    public function route(HTTPRequest $request, Project $project, PFUser $user)
    {
        $existing_packages = $this->getExistingPackagesForProject($project);
        if (! $request->get('func')) {
            $this->useDefaultRoute($project, $existing_packages);
            return;
        }

        if ($request->get('id')) {
            $package = $this->getFrsPackage($request, $project, $user);
        } else {
            if (! $this->permission_manager->isAdmin($project, $user)) {
                exit_permission_denied();
            }
        }

        switch ($request->get('func')) {
            case 'delete':
                try {
                    $this->package_controller->delete($request, $package, $project);
                } catch (FRSPackageHasReleaseException $e) {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('file_admin_editpackages', 'p_not_empty'));
                } catch (FRSDeletePackageNotYoursException $e) {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('file_admin_editpackages', 'p_not_yours'));
                }
                $GLOBALS['Response']->redirect('/file/?group_id=' . $project->getGroupId());
                break;
            case 'add':
                $this->package_controller->displayCreationForm($project, $existing_packages);
                break;
            case 'create':
                $this->package_controller->create($request, $project, $existing_packages);
                break;
            case 'edit':
                $this->package_controller->edit($project, $package, $existing_packages);
                break;
            case 'update':
                try {
                    $this->package_controller->update($request, $package, $project, $user);
                } catch (FRSPackageNameAlreadyExistsException $e) {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('file_admin_editpackages', 'p_name_exists'));
                    $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('file_admin_editpackages', 'update_canceled'));
                    $GLOBALS['Response']->redirect('/file/?group_id=' . $project->getGroupId());
                }
                break;
            default:
                $this->package_controller->displayCreationForm($project, $existing_packages);
                break;
        }
    }

    private function useDefaultRoute(Project $project, $existing_packages)
    {
        $this->package_controller->displayCreationForm($project, $existing_packages);
    }

    private function getFrsPackage(HTTPRequest $request, Project $project, PFUser $user)
    {
        $package_id = $request->get('id');
        $package    = $this->package_factory->getFRSPackageFromDb($package_id);
        if (! $package) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('file_admin_editpackages', 'p_not_exists'));
            $GLOBALS['Response']->redirect('/file/?group_id=' . $project->getGroupId());
        }
        if (! $this->permission_manager->isAdmin($project, $user)) {
            exit_permission_denied();
        }

        return $package;
    }

    private function getExistingPackagesForProject(Project $project)
    {
        $existing_packages = array();
        $packages = $this->package_factory->getFRSPackagesFromDb($project->getGroupId());
        foreach ($packages as $p => $nop) {
            $existing_packages[] = array(
                'id'   => $packages[$p]->getPackageId(),
                'name' => $packages[$p]->getName(),
                'rank' => $packages[$p]->getRank(),
            );
        }

        return $existing_packages;
    }
}
