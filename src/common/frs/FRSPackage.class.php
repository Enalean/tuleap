<?php
/**
 *
 * FRSPackage.class.php - File Release System Package class
 *
 * CodeX
 * Copyright 2006 (c) Xerox
 * http://codex.xerox.com
 *
 * @version   $Id$
 * @author Marc Nazarian (marc.nazarian@xrce.xerox.com)
 *
 */
 
require_once('common/include/Error.class.php');
require_once('common/frs/FRSReleaseFactory.class.php');



class FRSPackage extends Error {

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
     * @var boolean $approve_licence true if the licence has been approved, false otherwise
     */
    var $approve_licence;
    
    function FRSPackage($data_array = null) {
        $this->package_id       = null;
        $this->group_id         = null;
        $this->name             = null;
        $this->status_id        = null;
        $this->rank             = null;
        $this->approve_licence  = null;

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
        $this->rank = (int) $rank;
    }
    function getApproveLicence() {
        return $this->approve_licence;
    }
    function setApproveLicence($approve_licence) {
        $this->approve_licence = $approve_licence;
    }
    
    function initFromArray($array) {
        if (isset($array['package_id']))      $this->setPackageID($array['package_id']);
        if (isset($array['group_id']))        $this->setGroupID($array['group_id']);
        if (isset($array['name']))            $this->setName($array['name']);
        if (isset($array['status_id']))       $this->setStatusID($array['status_id']);
        if (isset($array['rank']))            $this->setRank($array['rank']);
        if (isset($array['approve_licence'])) $this->setApproveLicence($array['approve_licence']);
    }

    function toArray() {
        $array = array();
        $array['package_id']      = $this->getPackageID();
        $array['group_id']        = $this->getGroupID();
        $array['name']            = $this->getName();
        $array['status_id']       = $this->getStatusID();
        $array['rank']            = $this->getRank();
        $array['approve_licence'] = $this->getApproveLicence();
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
	 * The Group object.
	 *
	 * @var  object  $Group.
	 */
	var $Group; //group object

	


	/**
	 *  setMonitor - Add the current user to the list of people monitoring this package.
	 *
	 *  @return	boolean	success.
	 */
	function setMonitor() {
		global $Language;
		if (!session_loggedin()) {
			$this->setError($Language->getText('frs_package','error_set_monitor'));
			return false;
		}
		$sql="SELECT * FROM filemodule_monitor
			WHERE user_id='".user_getid()."'
			AND filemodule_id='".$this->getID()."';";
		$result = db_query($sql);

		if (!$result || db_numrows($result) < 1) {
			/*
				User is not already monitoring thread, so
				insert a row so monitoring can begin
			*/
			$sql="INSERT INTO filemodule_monitor (filemodule_id,user_id)
				VALUES ('".$this->getID()."','".user_getid()."')";

			$result = db_query($sql);

			if (!$result) {
				$this->setError('Unable to add monitor: '.db_error());
				return false;
			}

		}
		return true;
	}

	/**
	 *  stopMonitor - Remove the current user from the list of people monitoring this package.
	 *
	 *  @return	boolean	success.
	 */
	function stopMonitor() {
		global $Language;
		if (!session_loggedin()) {
			$this->setError($Language->getText('frs_package','error_set_monitor'));
			return false;
		}
		$sql="DELETE FROM filemodule_monitor
			WHERE user_id='".user_getid()."'
			AND filemodule_id='".$this->getID()."';";
		return db_query($sql);
	}

	/**
	 *	getMonitorCount - Get the count of people monitoring this package
	 *
	 *	@return int the count
	 */
	function getMonitorCount() {
		$sql = "select count(*) as count from filemodule_monitor where filemodule_id = ".$this->getID();
		$res = db_result(db_query($sql), 0, 0);
		if ($res < 0) {
			$this->setError('FRSPackage::getMonitorCount() Error On querying monitor count: '.db_error());
			return false;
		}
		return $res;
	}	

	/**
	 *  isMonitoring - Is the current user in the list of people monitoring this package.
	 *
	 *  @return	boolean	is_monitoring.
	 */
	function isMonitoring() {
		if (!session_loggedin()) {
			return false;
		}
		$sql="SELECT * FROM filemodule_monitor
			WHERE user_id='".user_getid()."'
			AND filemodule_id='".$this->getID()."';";

		$result = db_query($sql);

		if (!$result || db_numrows($result) < 1) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 *  getMonitorIDs - Return an array of user_id's of the list of people monitoring this package.
	 *
	 *  @return	array	The array of user_id's.
	 */
	function &getMonitorIDs() {
		$res=db_query("SELECT user_id
			FROM filemodule_monitor
			WHERE filemodule_id='".$this->getID()."'");
		return util_result_column_to_array($res);
	}


	/**
	 *	getReleases - gets Release objects for all the releases in this package.
	 *
	 *  return  array   Array of FRSRelease Objects.
	 */
	function &getReleases() {
		if (!is_array($this->package_releases) || count($this->package_releases) < 1) {
			$this->package_releases=array();
			$frspf = new FRSReleaseFactory();
			$this->package_releases = $frspf->getFRSReleasesFromDb($this->getID());
		}
		return $this->package_releases;
	}

}

?>
