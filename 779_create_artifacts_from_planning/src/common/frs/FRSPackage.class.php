<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

require_once('common/include/Error.class.php');
require_once('common/frs/FRSReleaseFactory.class.php');
require_once('common/frs/FRSPackageFactory.class.php');

/**
 *
 * FRSPackage.class.php - File Release System Package class
 *
 */
class FRSPackage extends Error {
    const PERM_READ      = 'PACKAGE_READ';

    const STATUS_ACTIVE  = 1;
    const STATUS_DELETED = 2;
    const STATUS_HIDDEN  = 3;

    const EVT_CREATE = 101;
    const EVT_UPDATE = 102;
    const EVT_DELETE = 103;

    /**
     * @var int $package_id the ID of this FRSPackage
     */
    var $package_id;
    /**
     * @var int $group_id the ID of the group this FRSPackage belong to
     */
    var $group_id;
    /**
     * @var string $name the name of this FRSPackage
     */
    var $name;
    /**
     * @var int $status_id the ID of the status of this FRSPackage
     */
    var $status_id;
    /**
     * @var int $rank the rank of this FRSPackage
     */
    var $rank;
    /**
     * @var boolean $approve_license true if the license has been approved, false otherwise
     */
    var $approve_license;
    
    function FRSPackage($data_array = null) {
        $this->package_id       = null;
        $this->group_id         = null;
        $this->name             = null;
        $this->status_id        = null;
        $this->rank             = null;
        $this->approve_license  = null;

        if ($data_array) {
            $this->initFromArray($data_array);
        }
    }

    function getPackageID() {
        return $this->package_id;
    }
    function setPackageID($package_id) {
        $this->package_id = (int) $package_id;
    }
    function getGroupID() {
        return $this->group_id;
    }
    function setGroupID($group_id) {
        $this->group_id = (int) $group_id;
    }
    function getName() {
        return $this->name;
    }
    function setName($name) {
        $this->name = $name;
    }
    function getStatusID() {
        return $this->status_id;
    }
    function setStatusID($status_id) {
        $this->status_id = (int) $status_id;
    }
    function getRank() {
        return $this->rank;
    }
    function setRank($rank) {
        $this->rank = $rank;
    }
    function getApproveLicense() {
        return $this->approve_license;
    }
    function setApproveLicense($approve_license) {
        $this->approve_license = $approve_license;
    }
    
    /**
     * Determines if the package is active or not
     * @return boolean true if the package is active, false otherwise
     */
    function isActive() {
        $frsrf = new FRSPackageFactory();
        return $this->getStatusID() == $frsrf->STATUS_ACTIVE;
    }
    
    /**
     * Determines if the package is hidden or not
     * @return boolean true if the package is hidden, false otherwise
     */
    function isHidden() {
        $frsrf = new FRSPackageFactory();
        return $this->getStatusID() == $frsrf->STATUS_HIDDEN;
    }
    
    /**
     * Determines if the package is deleted or not
     * @return boolean true if the package is deleted, false otherwise
     */
    function isDeleted() {
        $frsrf = new FRSPackageFactory();
        return $this->getStatusID() == $frsrf->STATUS_DELETED;
    }
    
    function initFromArray($array) {
        if (isset($array['package_id']))      $this->setPackageID($array['package_id']);
        if (isset($array['group_id']))        $this->setGroupID($array['group_id']);
        if (isset($array['name']))            $this->setName($array['name']);
        if (isset($array['status_id']))       $this->setStatusID($array['status_id']);
        if (isset($array['rank']))            $this->setRank($array['rank']);
        if (isset($array['approve_license'])) $this->setApproveLicense($array['approve_license']);
    }

    function toArray() {
        $array = array();
        $array['package_id']      = $this->getPackageID();
        $array['group_id']        = $this->getGroupID();
        $array['name']            = $this->getName();
        $array['status_id']       = $this->getStatusID();
        $array['rank']            = $this->getRank();
        $array['approve_license'] = $this->getApproveLicense();
        return $array;
    }
    
    /**
	 * Associative array of data from db.
	 *
	 * @var  array   $data_array.
	 */
	var $data_array;
	var $package_releases;


	/**
	 *	getReleases - gets Release objects for all the releases in this package.
	 *
	 *  return  array   Array of FRSRelease Objects.
	 */
	function &getReleases() {
		if (!is_array($this->package_releases) || count($this->package_releases) < 1) {
			$this->package_releases=array();
			$frsrf = new FRSReleaseFactory();
			$this->package_releases = $frsrf->getFRSReleasesFromDb($this->getPackageID());
		}
		return $this->package_releases;
	}
    
    /**
     * Function userCanRead : determine if the user can view this package or not
     *
	 * @param int $user_id if not given or 0 take the current user
     * @return boolean true if user has Read access to this package, false otherwise
	 */ 
	function userCanRead($user_id=0) {
        $frspf = new FRSPackageFactory();
        return $frspf->userCanRead($this->getGroupID(), $this->getPackageID(), $user_id);
	}


}

?>
