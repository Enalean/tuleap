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
require_once('common/frs/FRSFile.class.php');

/**
 *	  Factory method which creates a FRSRelease from an release id
 *
 *	  @param int	  The release id
 *	  @param array	The result array, if it's passed in
 *	  @return object  FRSRelease object
 */
/*function &frsrelease_get_object($release_id, $data=false) {
	global $FRSRELEASE_OBJ;
	if (!isset($FRSRELEASE_OBJ['_'.$release_id.'_'])) {
		if ($data) {
					//the db result handle was passed in
		} else {
			$res=db_query("SELECT * FROM frs_release WHERE
			release_id='$release_id'");
			if (db_numrows($res)<1 ) {
				$FRSRELEASE_OBJ['_'.$release_id.'_']=false;
				return false;
			}
			$data =& db_fetch_array($res);
		}
		$FRSPackage =& frspackage_get_object($data['package_id']);
		$FRSRELEASE_OBJ['_'.$release_id.'_']= new FRSRelease($FRSPackage,$data['release_id'],$data);
	}
	return $FRSRELEASE_OBJ['_'.$release_id.'_'];
}*/

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
	 *  sendNotice - the logic to send an email/jabber notice for a release.
	 *
	 *  @return	boolean	success.
	 */
	function sendNotice() {
		global $Language;
		$arr =& $this->FRSPackage->getMonitorIDs();

		$date = date('Y-m-d H:i',time());
		$proto = "http://";
		if ($GLOBALS['sys_use_ssl']) {
			$proto = "https://";
		}

		$subject = $Language->getText('frs_release','email_title',array(
		$this->FRSPackage->Group->getUnixName(),
		$this->FRSPackage->getName()));
		$text = stripcslashes($Language->getText('frs_release','email_text',array(
		$this->FRSPackage->Group->getPublicName(),
		$this->FRSPackage->Group->getUnixName(),
		$this->FRSPackage->getName(),
		"<${proto}".getStringFromServer('HTTP_HOST')."/project/showfiles.php?group_id=". $this->FRSPackage->Group->getID() ."&release_id=". $this->getID().">",
		$GLOBALS['sys_name'],
		"<${proto}".getStringFromServer('HTTP_HOST')."/frs/monitor.php?filemodule_id=".$this->FRSPackage->getID()."&group_id=".$this->FRSPackage->Group->getID()."&stop=1>")));
			

		$text = util_line_wrap($text);
		if (count($arr)) {
			util_handle_message(array_unique($arr),$subject,$text);
		}
		
	}

	/**
	 *	getFiles - gets all the file objects for files in this release.
	 *
	 *	return	array	Array of FRSFile Objects.
	 */
	function &getFiles() {
		if (!is_array($this->release_files) || count($this->release_files) < 1) {
			$this->release_files=array();
			$res=db_query("SELECT * FROM frs_file WHERE release_id='".$this->getReleaseID()."'");
			while ($arr = db_fetch_array($res)) {
				$this->release_files[] = new FRSFile($arr);
			}
		}
        return $this->release_files;
	}
}

?>
