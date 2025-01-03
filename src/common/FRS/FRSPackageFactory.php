<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface;
use Tuleap\FRS\FRSPackagePaginatedCollection;
use Tuleap\FRS\FRSPermissionManager;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementDao;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementFactory;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class FRSPackageFactory
{
    // Kept for legacy
    public $STATUS_ACTIVE  = FRSPackage::STATUS_ACTIVE;
    public $STATUS_DELETED = FRSPackage::STATUS_DELETED;
    public $STATUS_HIDDEN  = FRSPackage::STATUS_HIDDEN;
    private static ?self $instance;

    public static function instance()
    {
        if (empty(self::$instance)) {
            self::$instance = new FRSPackageFactory();
        }
        return self::$instance;
    }

    public static function setInstance(FRSPackageFactory $instance)
    {
        self::$instance = $instance;
    }

    public static function clearInstance()
    {
        self::$instance = null;
    }

    public function getFRSPackageFromArray(&$array)
    {
        $frs_package = new FRSPackage($array);

        return $frs_package;
    }

    /**
     * @return FRSPackage|void
     */
    public function getFRSPackageFromDb($package_id = null, $group_id = null, $extraFlags = 0)
    {
        $_id = (int) $package_id;
        $dao = $this->_getFRSPackageDao();
        if ($group_id) {
            $_group_id = (int) $group_id;
            $dar       = $dao->searchInGroupById($_id, $_group_id, $extraFlags);
        } else {
            $dar = $dao->searchById($_id, $extraFlags);
        }
        if ($dar->isError()) {
            return;
        }

        if (! $dar->valid()) {
            return;
        }

        $data_array = $dar->current();

        return($this->getFRSPackageFromArray($data_array));
    }

    public function getFRSPackageByFileIdFromDb($file_id)
    {
        $_id = (int) $file_id;
        $dao = $this->_getFRSPackageDao();
        $dar = $dao->searchByFileId($_id);

        if ($dar->isError()) {
            return;
        }

        if (! $dar->valid()) {
            return;
        }

        $data_array = $dar->current();

        return($this->getFRSPackageFromArray($data_array));
    }

    public function getFRSPackageByReleaseIDFromDb($release_id, $group_id)
    {
        $_id       = (int) $release_id;
        $_group_id = (int) $group_id;
        $dao       = $this->_getFRSPackageDao();
        $dar       = $dao->searchInGroupByReleaseId($_id, $_group_id);

        if ($dar->isError()) {
            return;
        }

        if (! $dar->valid()) {
            return;
        }

        $data_array = $dar->current();

        return($this->getFRSPackageFromArray($data_array));
    }

    /**
     * Return the list of all Packages for given project
     *
     * @param int $group_id
     *
     * @return FRSPackage[]
     */
    public function getFRSPackagesFromDb($group_id)
    {
        $_id = (int) $group_id;
        $dao = $this->_getFRSPackageDao();
        $dar = $dao->searchByGroupId($_id);

        $packages = [];
        if ($dar && ! $dar->isError()) {
            foreach ($dar as $data_array) {
                $packages[] = $this->getFRSPackageFromArray($data_array);
            }
        }

        return $packages;
    }

    /**
     * Return the list of active Packages for given project
     *
     * @param int $group_id
     *
     * @return FRSPackage[]
     */
    public function getActiveFRSPackages($group_id)
    {
        $user = UserManager::instance()->getCurrentUser();
        $dao  = $this->_getFRSPackageDao();
        $dar  = $dao->searchActivePackagesByGroupId($group_id);

        return $this->instantiateActivePackagesForUserFromDar($group_id, $user, $dar);
    }

    /**
     * @return FRSPackage[]
     */
    private function instantiateActivePackagesForUserFromDar($group_id, PFUser $user, LegacyDataAccessResultInterface $dar)
    {
        $packages = [];
        if ($dar && ! $dar->isError()) {
            $frsrf = new FRSReleaseFactory();

            foreach ($dar as $data_array) {
                if ($this->userCanRead($group_id, $data_array['package_id'], $user->getID())) {
                    $packages[] = $this->getFRSPackageFromArray($data_array);
                } else {
                    $authorised_releases = $frsrf->getActiveFRSReleases($data_array['package_id'], $group_id);
                    if ($authorised_releases && count($authorised_releases) > 0) {
                        $packages[] = $this->getFRSPackageFromArray($data_array);
                    }
                }
            }
        }

        return $packages;
    }

    /**
     * @return FRSPackagePaginatedCollection
     */
    public function getPaginatedActivePackagesForUser(Project $project, PFUser $user, $limit, $offset)
    {
        $dao        = $this->_getFRSPackageDao();
        $dar        = $dao->searchPaginatedActivePackagesByGroupId($project->getID(), $limit, $offset);
        $total_size = $dao->foundRows();

        $packages = $this->instantiateActivePackagesForUserFromDar($project->getID(), $user, $dar);

        return new FRSPackagePaginatedCollection($packages, $total_size);
    }

    public function getPackageIdByName($package_name, $group_id)
    {
        $_id = (int) $group_id;
        $dao = $this->_getFRSPackageDao();
        $dar = $dao->searchPackageByName($package_name, $_id);

        if ($dar->isError()) {
            return;
        }

        if (! $dar->valid()) {
            return;
        } else {
            $res = $dar->current();
            return $res['package_id'];
        }
    }

    public function isPackageNameExist($package_name, $group_id)
    {
        $_id = (int) $group_id;
        $dao = $this->_getFRSPackageDao();
        $dar = $dao->searchPackageByName($package_name, $_id);

        if ($dar->isError()) {
            return;
        }

        return $dar->valid();
    }

    public function update($data)
    {
        if ($data instanceof \FRSPackage) {
            $data = $data->toArray();
        }
        $dao = $this->_getFRSPackageDao();
        if ($dao->updateFromArray($data)) {
            $this->getEventManager()->processEvent(
                'frs_update_package',
                ['group_id' => $data['group_id'],
                    'item_id'    => $data['package_id'],
                ]
            );
            return true;
        }
        return false;
    }

    public function create(array $data_array)
    {
        $original_approval_license = null;
        if (isset($data_array['approve_license'])) {
            $original_approval_license     = $data_array['approve_license'];
            $data_array['approve_license'] = LicenseAgreementFactory::convertLicenseAgreementIdToPackageApprovalLicense((int) $data_array['approve_license']) ? '1' : '0';
        }
        $dao = $this->_getFRSPackageDao();
        $id  = $dao->createFromArray($data_array);
        if ($id) {
            $data_array['package_id'] = $id;
            $package                  = new FRSPackage($data_array);
            $this->setLicenseAgreementAtPackageCreation($package, $original_approval_license);
            $this->setDefaultPermissions($package);
            $this->getEventManager()->processEvent(
                'frs_create_package',
                ['group_id' => $data_array['group_id'],
                    'item_id' => $id,
                ]
            );
        }
        return $id;
    }

    protected function setLicenseAgreementAtPackageCreation(FRSPackage $package, ?int $original_approval_license)
    {
        if ($original_approval_license === null) {
            return;
        }
        $project = $this->getProjectManager()->getProject($package->getGroupID());
        if (! $project) {
            return;
        }
        (new LicenseAgreementFactory(new LicenseAgreementDao()))->updateLicenseAgreementForPackage(
            $project,
            $package,
            $original_approval_license
        );
    }

    // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    public function _delete($package_id)
    {
        $_id     = (int) $package_id;
        $package = $this->getFRSPackageFromDb($_id);
        $dao     = $this->_getFRSPackageDao();
        if ($dao->delete($_id, $this->STATUS_DELETED)) {
            $this->getEventManager()->processEvent(
                'frs_delete_package',
                ['group_id' => $package->getGroupID(),
                    'item_id'    => $_id,
                ]
            );
            return true;
        }
        return false;
    }

    /**
     * Delete an empty package
     * first, make sure the package is theirs
     * and delete the package from the database
     * return false if release not deleted, true otherwise
     *
     * @param int $group_id
     * @param int $package_id
     *
     * @return bool
     */
    public function delete_package($group_id, $package_id) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $package = $this->getFRSPackageFromDb($package_id, $group_id);

        if (! $package_id) {
            //package not found for this project
            return false;
        } else {
            //delete the package from the database
            $this->_delete($package_id);
            return true;
        }
    }

    /**
     * Delete all FRS packages of given project
     *
     * @param int $groupId Project ID
     *
     * @return bool
     */
    public function deleteProjectPackages($groupId)
    {
        $deleteState = true;
        $resPackages = $this->getFRSPackagesFromDb($groupId);
        if (! empty($resPackages)) {
            foreach ($resPackages as $package) {
                if (! $this->delete_package($groupId, $package->getPackageID())) {
                    $deleteState = false;
                }
            }
        }
        return $deleteState;
    }

    /**
     * @return bool
     */
    public function userCanAdmin(PFUser $user, $project_id)
    {
        $project = $this->getProjectManager()->getProject($project_id);
        return $this->getFRSPermissionManager()->isAdmin($project, $user);
    }

    /** @protected for testing purpose */
    protected function getProjectManager()
    {
        return ProjectManager::instance();
    }

    /** @protected for testing purpose */
    protected function getFRSPermissionManager()
    {
        return FRSPermissionManager::build();
    }

    public function userCanRead($project_id, $package_id, $user_id = false)
    {
        $frs_permission_manager = $this->getFRSPermissionManager();

        $user    = $this->getUser($user_id);
        $project = $this->getProjectManager()->getProject($project_id);

        $ok = $frs_permission_manager->isAdmin($project, $user)
            || (
                $frs_permission_manager->userCanRead($project, $user)
                &&
                $this->userCanReadPackage($project_id, $package_id, $user)
            );

        return $ok;
    }

    /** @return PFUser */
    private function getUser($user_id = false)
    {
        $user_manager = $this->getUserManager();
        if (! $user_id) {
            $user = $user_manager->getCurrentUser();
        } else {
            $user = $user_manager->getUserById($user_id);
        }

        return $user;
    }

    private function userCanReadPackage($project_id, $package_id, PFUser $user)
    {
        $global_permission_manager = $this->getPermissionsManager();

        $user_groups = $user->getUgroups($project_id, []);

        return $global_permission_manager->userHasPermission($package_id, FRSPackage::PERM_READ, $user_groups)
            || ! $global_permission_manager->isPermissionExist($package_id, FRSPackage::PERM_READ);
    }

    /**
     * Return true if user has Update permission on this package
     *
     * @param int $group_id The project this package is in
     * @param int $package_id The ID of the package to update
     * @param int $user_id if Not given or false, take the current user
     *
     * @return bool true of user can update the package $package_id, false otherwise
     */
    public function userCanUpdate($group_id, $package_id, $user_id = false)
    {
        return $this->userCanCreate($group_id, $user_id);
    }

    /**
     * Returns true if user has permissions to Create packages
     *
     * @return bool true if the user has permission to create packages, false otherwise
     */
    public function userCanCreate($project_id, $user_id = false)
    {
        $user_manager = $this->getUserManager();
        if (! $user_id) {
            $user = $user_manager->getCurrentUser();
        } else {
            $user = $user_manager->getUserById($user_id);
        }

        return $this->userCanAdmin($user, $project_id);
    }

    /**
     * By default, a package is readable by all registered users
     *
     * @param FRSPackage $package Permissions will apply on this Package
     */
    public function setDefaultPermissions(FRSPackage $package)
    {
        $this->getPermissionsManager()->addPermission(FRSPackage::PERM_READ, $package->getPackageID(), ProjectUGroup::REGISTERED);
        permission_add_history($package->getGroupID(), FRSPackage::PERM_READ, $package->getPackageID());
    }

    /**
     * Returns an instance of EventManager
     *
     * @return EventManager
     */
    public function getEventManager()
    {
         $em = EventManager::instance();
         FRSLog::instance();
         return $em;
    }

    /**
     * Return an instance of PermissionsManager
     *
     * @return PermissionsManager
     */
    public function getPermissionsManager()
    {
        return PermissionsManager::instance();
    }

    /**
     * @return UserManager
     */
    public function getUserManager()
    {
        return UserManager::instance();
    }

    public ?FRSPackageDao $dao = null;

    // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    public function _getFRSPackageDao()
    {
        if (! $this->dao) {
            $this->dao = new FRSPackageDao(CodendiDataAccess::instance(), $this->STATUS_DELETED);
        }
        return $this->dao;
    }
}
