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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
class FRSPackageFactory
{
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

    public function getFRSPackageFromDb($package_id = null, $group_id = null, $extraFlags = 0): ?FRSPackage
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
            return null;
        }

        if (! $dar->valid()) {
            return null;
        }

        $data_array = $dar->current();

        return $this->getFRSPackageFromArray($data_array);
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
                if ($this->userCanRead($data_array['package_id'], (int) $user->getID())) {
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

    public function delete(FRSPackage $package, PFUser $user): bool
    {
        if (! $this->userCanUpdate($package, $user)) {
            return false;
        }

        return $this->deleteWithoutPermissionsVerification($package);
    }

    protected function deleteWithoutPermissionsVerification(FRSPackage $package): bool
    {
        $id = (int) $package->getPackageID();
        if ($this->_getFRSPackageDao()->delete($id, FRSPackage::STATUS_DELETED)) {
            $this->getEventManager()->processEvent(
                'frs_delete_package',
                ['group_id' => $package->getGroupID(),
                    'item_id'    => $id,
                ]
            );
            return true;
        }
        return false;
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
                if (! $this->deleteWithoutPermissionsVerification($package)) {
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

    public function userCanRead(int $package_id, int $user_id): bool
    {
        $frs_permission_manager = $this->getFRSPermissionManager();

        $package = $this->getFRSPackageFromDb($package_id);
        if ($package === null) {
            return false;
        }
        $project = $this->getProjectManager()->getProject($package->getGroupID());
        if ($project === null) {
            return false;
        }

        $user = $this->getUser($user_id);

        if ($package->isHidden()) {
            return $frs_permission_manager->isAdmin($project, $user);
        }

        if ($package->isActive()) {
            return $frs_permission_manager->userCanRead($project, $user) &&
                $this->userCanReadPackage($package, $user);
        }

        return false;
    }

    private function getUser(int $user_id): PFUser
    {
        $user_manager = $this->getUserManager();
        if (! $user_id) {
            $user = $user_manager->getCurrentUser();
        } else {
            $user = $user_manager->getUserById($user_id);
        }

        return $user;
    }

    private function userCanReadPackage(FRSPackage $package, PFUser $user): bool
    {
        $global_permission_manager = $this->getPermissionsManager();

        $user_groups = $user->getUgroups($package->getGroupID(), []);

        $package_id = $package->getPackageID();

        return $global_permission_manager->userHasPermission($package_id, FRSPackage::PERM_READ, $user_groups)
            || ! $global_permission_manager->isPermissionExist($package_id, FRSPackage::PERM_READ);
    }

    public function userCanUpdate(FRSPackage $package, PFUser $user): bool
    {
        return ($package->isActive() || $package->isHidden()) &&
            $this->userCanCreate((int) $package->getGroupID(), $user);
    }

    public function userCanCreate(int $project_id, ?PFUser $user = null): bool
    {
        $user_manager = $this->getUserManager();
        if ($user === null) {
            $user = $user_manager->getCurrentUser();
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
            $this->dao = new FRSPackageDao(CodendiDataAccess::instance(), FRSPackage::STATUS_DELETED);
        }
        return $this->dao;
    }
}
