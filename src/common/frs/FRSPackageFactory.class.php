<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2016. All Rights Reserved.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('FRSPackage.class.php');
require_once('common/dao/FRSPackageDao.class.php');
require_once('common/user/UserManager.class.php');
require_once('common/permission/PermissionsManager.class.php');
require_once('FRSReleaseFactory.class.php');
require_once('www/project/admin/ugroup_utils.php');
require_once ('common/frs/FRSLog.class.php');

use Tuleap\FRS\FRSPermissionManager;
use Tuleap\FRS\FRSPermissionFactory;
use Tuleap\FRS\FRSPermissionDao;

/**
 * 
 */
class FRSPackageFactory {
    // Kept for legacy
    var $STATUS_ACTIVE  = FRSPackage::STATUS_ACTIVE;
    var $STATUS_DELETED = FRSPackage::STATUS_DELETED;
    var $STATUS_HIDDEN  = FRSPackage::STATUS_HIDDEN;
    private static $instance;

    public static function instance(){
        if(empty(self::$instance)) {
            self::$instance = new FRSPackageFactory();
        }
        return self::$instance;
    }

    public static function setInstance(FRSPackageFactory $instance){
        self::$instance = $instance;
    }

    public static function clearInstance(){
        self::$instance = null;
    }

    function getFRSPackageFromArray(&$array) {
        $frs_package = null;
        $frs_package = new FRSPackage($array);
        return $frs_package;
    }

    function getFRSPackageFromDb($package_id = null, $group_id=null, $extraFlags = 0) {
        $_id = (int) $package_id;
        $dao = $this->_getFRSPackageDao();
        if($group_id){
        	$_group_id = (int) $group_id;
        	$dar = $dao->searchInGroupById($_id, $_group_id, $extraFlags);
        }else{
        	$dar = $dao->searchById($_id, $extraFlags);
        }
        if($dar->isError()){
            return;
        }
        
        if(!$dar->valid()) {
            return;
        }

        $data_array = $dar->current();

        return($this->getFRSPackageFromArray($data_array));
    }
    
    function getFRSPackageByFileIdFromDb($file_id){
    	$_id = (int) $file_id;
        $dao = $this->_getFRSPackageDao();
        $dar = $dao->searchByFileId($_id);
        
        if($dar->isError()){
            return;
        }
        
        if(!$dar->valid()) {
            return;
        }
        
        $data_array = $dar->current();

        return($this->getFRSPackageFromArray($data_array));
    }
    
    function getFRSPackageByReleaseIDFromDb($release_id, $group_id) {
        $_id = (int) $release_id;
        $_group_id = (int) $group_id;
        $dao = $this->_getFRSPackageDao();
        $dar = $dao->searchInGroupByReleaseId($_id, $_group_id);

        if($dar->isError()){
            return;
        }
        
        if(!$dar->valid()) {
            return;
        }

        $data_array = $dar->current();

        return($this->getFRSPackageFromArray($data_array));
    }

    /**
     * Return the list of Packages for given project
     * 
     * @param Integer $group_id
     * @param String  $status_id
     * 
     * @return FRSPackage[]
     */
    function getFRSPackagesFromDb($group_id, $status_id=null) {
        $_id = (int) $group_id;
        $dao = $this->_getFRSPackageDao();
        if($status_id){
            $_status_id= (int) $status_id;
            $dar = $dao->searchActivePackagesByGroupId($_id, $this->STATUS_ACTIVE);
        } else {
            $dar = $dao->searchByGroupId($_id);
        }

        if ($dar && !$dar->isError()) {
            $packages = array();
            $um = UserManager::instance();
            $user = $um->getCurrentUser();

            foreach ($dar as $data_array) {
                if ($status_id){
                    if($this->userCanRead($group_id, $data_array['package_id'],$user->getID())){
                        $packages[] = $this->getFRSPackageFromArray($data_array);
                    }else{
                        $frsrf = new FRSReleaseFactory();
                        $authorised_releases = $frsrf->getFRSReleasesFromDb($data_array['package_id'], 1, $group_id);
                        if($authorised_releases && count($authorised_releases)>0){
                            $packages[] = $this->getFRSPackageFromArray($data_array);
                        }
                    }
                }else{
                    $packages[] = $this->getFRSPackageFromArray($data_array);
                }
            }
            return $packages;
        }
        return;
    }

    function getPackageIdByName($package_name, $group_id){
    	$_id = (int) $group_id;
        $dao = $this->_getFRSPackageDao();
        $dar = $dao->searchPackageByName($package_name, $_id);

        if($dar->isError()){
            return;
        }
        
        if(!$dar->valid()){
        	return;
        }else{
            $res = $dar->current();
            return $res['package_id'];
        }
    }

    function isPackageNameExist($package_name, $group_id){
    	$_id = (int) $group_id;
        $dao = $this->_getFRSPackageDao();
        $dar = $dao->searchPackageByName($package_name, $_id);

        if($dar->isError()){
            return;
        }
        
        return $dar->valid();
    }
        
    function update($data) {
        if (is_a($data, 'FRSPackage')) {
            $data = $data->toArray();
        }
        $dao = $this->_getFRSPackageDao();
        if ($dao->updateFromArray($data)) {
            $this->getEventManager()->processEvent('frs_update_package',
                                                   array('group_id' => $data['group_id'],
                                                         'item_id'    => $data['package_id']));
            return true;
        }
        return false;
    }
    
    
    function create($data_array) {
        $dao = $this->_getFRSPackageDao();
        $id = $dao->createFromArray($data_array);
        if ($id) {
            $data_array['package_id'] = $id;
            $package = new FRSPackage($data_array);   
            $this->setDefaultPermissions($package);     
            $this->getEventManager()->processEvent('frs_create_package',
                                                   array('group_id' => $data_array['group_id'],
                                                         'item_id' => $id));
        }
        return $id;
    }
    
    function _delete($package_id){
        $_id = (int) $package_id;
        $package = $this->getFRSPackageFromDb($_id);
        $dao = $this->_getFRSPackageDao();
        if ($dao->delete($_id, $this->STATUS_DELETED)) {
            $this->getEventManager()->processEvent('frs_delete_package',
                                                   array('group_id' => $package->getGroupID(),
                                                         'item_id'    => $_id));
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
     * @param Integer $group_id
     * @param Integer $package_id
     * 
     * @return Boolean
     */
    function delete_package($group_id, $package_id) {
        $package = $this->getFRSPackageFromDb($package_id, $group_id);
        
        if (!$package_id) {
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
     * @param Integer $groupId Project ID
     *
     * @return Boolean
     */
    function deleteProjectPackages($groupId) {
        $deleteState = true;
        $resPackages = $this->getFRSPackagesFromDb($groupId);
        if (!empty($resPackages)) {
            foreach ($resPackages as $package) {
                if (!$this->delete_package($groupId, $package->getPackageID())) {
                    $deleteState = false;
                }
            }
        }
        return $deleteState;
    }

    /**
     * @return Boolean
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
        return new FRSPermissionManager(
            new FRSPermissionDao(),
            new FRSPermissionFactory(new FRSPermissionDao())
        );
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

        $user_groups = $user->getUgroups($project_id, array());

        return $global_permission_manager->userHasPermission($package_id, FRSPackage::PERM_READ, $user_groups)
            || ! $global_permission_manager->isPermissionExist($package_id, FRSPackage::PERM_READ);
    }

    /**
     * Return true if user has Update permission on this package
     *
     * @param Integer $group_id   The project this package is in
     * @param Integer $package_id The ID of the package to update
     * @param Integer $user_id if Not given or false, take the current user
     *
     * @return Boolean true of user can update the package $package_id, false otherwise
     */ 
	function userCanUpdate($group_id, $package_id, $user_id=false) {
        return $this->userCanCreate($group_id, $user_id);
	}

    /**
     * Returns true if user has permissions to Create packages
     *
     * @return Boolean true if the user has permission to create packages, false otherwise
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
    function setDefaultPermissions(FRSPackage $package) {
        $this->getPermissionsManager()->addPermission(FRSPackage::PERM_READ, $package->getPackageID(), $GLOBALS['UGROUP_REGISTERED']);
        permission_add_history($package->getGroupID(), FRSPackage::PERM_READ, $package->getPackageID());
    }

    /**
     * Returns an instance of EventManager
     *
     * @return EventManager
     */
    function getEventManager() {
         $em = EventManager::instance();
         FRSLog::instance();
         return $em;
    }

    /**
     * Return an instance of PermissionsManager
     *
     * @return PermissionsManager
     */
    function getPermissionsManager() {
        return PermissionsManager::instance();
    }

    /**
     * @return UserManager
     */
    function getUserManager() {
        return UserManager::instance();
    }

    var $dao;
    function _getFRSPackageDao() {
        if (!$this->dao) {
            $this->dao = new FRSPackageDao(CodendiDataAccess::instance(), $this->STATUS_DELETED);
        }
        return $this->dao;
    }
}
