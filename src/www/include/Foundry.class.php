<?php

//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$


//require_once('common/include/Error.class.php');

/*

	An object wrapper for foundry (as opposed to project) data

	Extends the base object, Group

	Tim Perdue, August 28, 2000



	Example of proper use:

	//instantiate the object
	$grp = new Foundry($group_id);

	//now use the object to get the unix_name for the project
	$grp->getUnixName();


*/

/*	      
	associative array of foundry objects
	helps prevent the same object from being created more than once
	which would create unnecessary database calls
*/	      
$FOUNDRY_OBJ=array();

function foundry_get_object($group_id) {
	//create a common set of foundry objects
	//saves a little wear on the database

	global $FOUNDRY_OBJ;
	if (!$FOUNDRY_OBJ["_".$group_id."_"]) {
		$FOUNDRY_OBJ["_".$group_id."_"]= new Foundry($group_id);
		return $FOUNDRY_OBJ["_".$group_id."_"];
	} else {
		return $FOUNDRY_OBJ["_".$group_id."_"];
	}
}


class Foundry extends Group {

	//foundry preferences, etc - associative array from the database
	var $foundry_data_array;

	//database result set handle for foundry_data
	var $foundry_db_result;


	/*
		basically just call the parent to set up everything
	*/
	function Foundry($id) {
		$this->Group($id);

		//now set up the foundry data

		$this->foundry_db_result=db_query("SELECT * FROM foundry_data WHERE foundry_id='$id'");
		if (db_numrows($this->foundry_db_result) < 1) {
			//function in class we extended
			$this->setError('Foundry Data Not Found');
			$this->foundry_data_array=array(); 
		} else {
			//set up an associative array for use by other functions
			$this->foundry_data_array=db_fetch_array($this->foundry_db_result);
		}       
	}

	function getFreeformHTML1() {
		return $this->foundry_data_array['freeform1_html'];
	}

	function getFreeformHTML2() {
		return $this->foundry_data_array['freeform2_html'];
	}

	function getSponsorHTML1() {
		return $this->foundry_data_array['sponsor1_html'];
	}

	function getSponsorHTML2() {
		return $this->foundry_data_array['sponsor2_html'];
	}


	/*      
		The ID number that corresponds to the appropriate ID # in
		the db_images table
	*/
	function getGuideImageID() {
		return $this->foundry_data_array['guide_image_id'];
	}


	/*
		The ID number that corresponds to the appropriate ID # in 
		the db_images table
	*/
	function getLogoImageID() {
		return $this->foundry_data_array['logo_image_id'];
	}       

	function getTroveCategories() {
		return $this->foundry_data_array['trove_categories'];
	}

	/*
		Returns a comma separated list of member project ids
	*/
	function getProjectsCommaSep() {
		return implode(',',$this->getMemberProjects());
	}


	/*
		Returns an array of member project ids
	*/
	function getMemberProjects() {
		//return an array of group_id's in this project
		$sql="SELECT project_id FROM foundry_projects WHERE foundry_id='". $this->getGroupId() ."' ORDER BY project_id ASC";
		$result=db_query($sql);
		return util_result_column_to_array($result);
	}

}

?>
