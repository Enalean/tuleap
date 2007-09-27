<?php
/**
 * GForge File Release Facility
 *
 * Copyright 2002 GForge, LLC
 * http://gforge.org/
 *
 * @version   $Id: FRSRelease.class,v 1.23.2.1 2005/10/31 18:17:56 lcorso Exp $
 *
 * This file is part of GForge.
 *
 * GForge is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GForge is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GForge; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */


require_once('common/include/Error.class.php');
require_once('common/frs/FRSFileFactory.class.php');



class FRSRelease extends Error {

	/**
     * @var int $release_id the ID of this FRSRelease
     */
    var $release_id;
    /**
     * @var int $package_id the ID of the package this FRSRelease belong to
     */
    var $package_id;
    /**
     * @var string $name the name of this FRSRelease
     */
    var $name;
    /**
     * @var string $notes the notes of this FRSRelease
     */
    var $notes;
    /**
     * @var int $changes the changes of this FRSRelease
     */
    var $changes;
    /**
     * @var int $status_id the ID of the status of this FRSRelease
     */
    var $status_id;
    /**
     * @var int $preformatted 1 if the text is preformatted, 0 otherwise
     */
    var $preformatted;
    /**
     * @var int $release_date the creation date of this FRSRelease
     */
    var $release_date;
    /**
     * @var int $released_by the ID of the user who creates this FRSRelease
     */
    var $released_by;
    
    function FRSRelease($data_array = null) {
        $this->release_id       = null;
        $this->package_id       = null;
        $this->name             = null;
        $this->notes            = null;
        $this->changes          = null;
        $this->status_id        = null;
        $this->preformatted     = null;
        $this->release_date     = null;
        $this->released_by      = null;

        if ($data_array) {
            $this->initFromArray($data_array);
        }
    }
    
    function getReleaseID() {
        return $this->release_id;
    }
    
    function setReleaseID($release_id) {
        $this->release_id = (int) $release_id;
    }
    
    function getPackageID() {
        return $this->package_id;
    }
    
    function setPackageID($package_id) {
        $this->package_id = (int) $package_id;
    }
    
    function getName() {
        return $this->name;
    }
    
    function setName($name) {
        $this->name = $name;
    }
    
    function getNotes() {
        return $this->notes;
    }
    
    function setNotes($notes) {
        $this->notes = $notes;
    }
    
    function getChanges() {
        return $this->changes;
    }
    
    function setChanges($changes) {
        $this->changes = $changes;
    }
    
    function getStatusID() {
        return $this->status_id;
    }
    
    function setStatusID($status_id) {
        $this->status_id = $status_id;
    }
    
    function getPreformatted() {
        return $this->preformatted;
    }
    
    function setPreformatted($preformatted) {
        $this->preformatted = $preformatted;
    }
    
    function getReleaseDate() {
        return $this->release_date;
    }
    
    function setReleaseDate($release_date) {
        $this->release_date = $release_date;
    }
    
    function getReleasedBy() {
        return $this->released_by;
    }
    
    function setReleasedBy($released_by) {
        $this->released_by = $released_by;
    }
    
    /**
     * Determines if the release is active or not
     * @return boolean true if the release is active, false otherwise
     */
    function isActive() {
        return $this->getStatusID() == 1;
    }
    
    /**
     * Determines if the release notes and changes are preformatted or not
     * @return boolean true if the release notes and changes are preformatted, false otherwise
     */
    function isPreformatted() {
        return $this->getPreformatted() == 1;
    }
    
    /**
     * Returns the group ID the release belongs to
     */
    function getGroupID() {
        $package_id = $this->getPackageID();
        $package_fact = new FRSPackageFactory();
        $package =& $package_fact->getFRSPackageFromDb($package_id);
        $group_id = $package->getGroupID();
        return $group_id;
    }
    
	function initFromArray($array) {
		if (isset($array['release_id']))      $this->setReleaseID($array['release_id']);
        if (isset($array['package_id']))      $this->setPackageID($array['package_id']);
        if (isset($array['name']))            $this->setName($array['name']);
        if (isset($array['notes']))           $this->setNotes($array['notes']);
        if (isset($array['changes']))         $this->setChanges($array['changes']);
        if (isset($array['status_id']))       $this->setStatusID($array['status_id']);
        if (isset($array['preformatted']))    $this->setPreformatted($array['preformatted']);
        if (isset($array['release_date']))    $this->setReleaseDate($array['release_date']);
        if (isset($array['released_by']))     $this->setReleasedBy($array['released_by']);
    }

    function toArray() {
        $array = array();
        $array['release_id']   = $this->getReleaseID();
        $array['package_id']   = $this->getPackageID();
        $array['name']         = $this->getName();
        $array['notes']        = $this->getNotes();
        $array['changes']      = $this->getChanges();
        $array['status_id']    = $this->getStatusID();
        $array['preformatted'] = $this->getPreformatted();
        $array['release_date'] = $this->getReleaseDate();
        $array['released_by'] = $this->getReleasedBy();
        return $array;
    }

    /**
	 * Associative array of data from db.
	 *
	 * @var  array   $data_array.
	 */
	var $data_array;
	var $release_files;

	/**
	 *	getFiles - gets all the file objects for files in this release.
	 *
	 *	return	array	Array of FRSFile Objects.
	 */
	function &getFiles() {
		if (!is_array($this->release_files) || count($this->release_files) < 1) {
			$this->release_files=array();
			$frsff = new FRSFileFactory();
			$this->release_files = $frsff->getFRSFilesFromDb($this->getReleaseID());
		}
		return $this->release_files;
	}
    
    /**
     * Function userCanRead : determine if the user can view this release or not
     *
	 * @param int $user_id if not given or 0 take the current user
     * @return boolean true if user has Read access to this release, false otherwise
	 */ 
	function userCanRead($user_id=0) {
        $release_factory = new FRSReleaseFactory();
        return $release_factory->userCanRead($this->getGroupID(), $this->getPackageID(), $this->getReleaseID(), $user_id);
	}
}

?>
