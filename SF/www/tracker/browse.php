<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2003. All rights reserved
//
// $Id$
//
//
//	Originally by to the SourceForge Team,1999-2000
//
//  Parts of code come from bug_util.php (written by Laurent Julliard)
//
//  Written for CodeX by Stephane Bouhet
//

//require_once($DOCUMENT_ROOT.'/../common/tracker/ArtifactFactory.class');

//
// HTTP GET arguments
// 
// $group_id = The group ID 
// $atid = The group artifact ID (artifact type id)
// $set = <custom|my|open> : different types of display
// $advsrch = <0|1> : advanced search or simple simple
// $msort = <0|1> : multi column sort activated
// $report_id = the report ID
// <field_name>[] = <default value> : list of each field and its default values associed 
// $chunksz = default 50 : number of artifact displayed in the page
// $morder = comma separated list of sort criteria followed by < for DESC and > for ASC order
// $order = last sort criteria selected in the UI
// $offset = the first element of the query result to display (used for the sql limit)
// $pv = printable version (=1)
//

//
//  make sure this person has permission to view artifacts
//
if (!$ath->userCanView()) {
	exit_permission_denied();
}

//
//  If the report type is not defined then get it from the user preferences.
//  If it is set then update the user preference.  Also initialize the
//  artifact report structures.
//
if (user_isloggedin()) {
    if (!isset($report_id)) {
		$report_id = user_get_preference('artifact_browse_report'.$atid);
	    if ($report_id == "") {
	    	// Default value
	    	$report_id = 100;
	    }
    } else {
		if ($report_id != user_get_preference('artifact_browse_report'.$atid))
	    	user_set_preference('artifact_browse_report'.$atid, $report_id);
    }
}

// Number of bugs displayed on screen in one chunk.
// Default 50
if (!$chunksz) { $chunksz = 50; }

// Make sure offset values, search and multisort flags are defined
// and have a correct value
if (!$offset || $offset < 0) { $offset=0; }
if (($advsrch != 0) && ($advsrch != 1)) { $advsrch = 0; }
if (($msort != 0) && ($msort != 1)) { $msort = 0; }

/* ==================================================
  If the report type is not defined then get it from the user preferences.
  If it is set then update the user preference.  Also initialize the
  bug report structures.
  ================================================== */
if (user_isloggedin()) {
    if (!isset($report_id)) {
		$report_id = user_get_preference('artifact_browse_report'.$atid);
    } else {
		if ($report_id != user_get_preference('artifact_browse_report'.$atid))
		    user_set_preference('artifact_browse_report'.$atid, $report_id);
    }
}

// If still not defined then force it to system 'Default' report
if (!$report_id) { $report_id=100; }


// Create factories
$report_fact = new ArtifactReportFactory();

// Retrieve HTTP GET variables and store them in $prefs array
$prefs = $art_field_fact->extractFieldList(false);

// Create the HTML report object
$art_report_html = $report_fact->getArtifactReportHtml($report_id,$atid);

/* ==================================================
   Make sure all URL arguments are captured as array. For simple
   search they'll be arrays with only one element at index 0 (this
   will avoid to deal with scalar in simple search and array in 
   advanced which would greatly complexifies the code)
 ================================================== */
while (list($field,$value_id) = each($prefs)) {
    if (!is_array($value_id)) {
		unset($prefs[$field]);
		$prefs[$field][] = $value_id;
		//echo '<br> DBG Setting $prefs['.$field.'] [] = '.$value_id;
	    } else {
		//echo '<br> DBG $prefs['.$field.'] = ('.implode(',',$value_id).')';
    }

	$field_object = $art_field_fact->getFieldFromName($field);
    if ( ($field_object)&&($field_object->isDateField()) ) {
		if ($advsrch) {
		    $field_end = $field.'_end';
		    $prefs[$field_end] = $$field_end;
		    //echo 'DBG Setting $prefs['.$field.'_end]= '.$prefs[$field.'_end'].'<br>';
		} else {
		    $field_op = $field.'_op';
		    $prefs[$field_op] = $$field_op;
		    if (!$prefs[$field_op])
				$prefs[$field_op] = '>';
		    //echo 'DBG Setting $prefs['.$field.'_op]= '.$prefs[$field.'_op'].'<br>';
		}
    }
}

/* ==================================================
   Memorize order by field as a user preference if explicitly specified.
   
   $morder = comma separated list of sort criteria followed by - for
     DESC and + for ASC order
   $order = last sort criteria selected in the UI
   $msort = 1 if multicolumn sort activated.
  ================================================== */
//echo "<br>DBG \$morder at top: [$morder ]";
//   if morder not defined then reuse the one in preferences
if (user_isloggedin() && !isset($morder)) {
    $morder = user_get_preference('artifact_browse_order'.$atid);
}

if (isset($order)) {

    if ($order != '') {
		// Add the criteria to the list of existing ones
		$morder = $art_report_html->addSortCriteria($morder, $order, $msort);
    } else {
		// reset list of sort criteria
		$morder = '';
    }
}

if (isset($morder)) {

    if (user_isloggedin()) {
		if ($morder != user_get_preference('artifact_browse_order'.$atid)) {
		    user_set_preference('artifact_browse_order'.$atid, $morder);
		}
    }
}

//echo "<BR> DBG Order by = $morder";



/* ==================================================
  Now see what type of bug set is requested (set is one of none, 
  'my', 'open', 'custom'). 
    - if no set is passed in, see if a preference was set ('custom' set).
    - if no preference and logged in then use 'my' set
    - if no preference and not logged in the use 'open' set
     (Prefs is a string of the form  &field1[]=value_id1&field2[]=value_id2&.... )
  ================================================== */
if (!$set) {

    if (user_isloggedin()) {

		$custom_pref=user_get_preference('artifact_brow_cust'.$atid);
	
		if ($custom_pref) {
		    $pref_arr = explode('&',substr($custom_pref,1));
		    while (list(,$expr) = each($pref_arr)) {
				// Extract left and right parts of the assignment
				// and remove the '[]' array symbol from the left part
				list($field,$value_id) = explode('=',$expr);
				$field = str_replace('[]','',$field);
				if ($field == 'advsrch') 
				    $advsrch = ($value_id ? 1 : 0);
				else if ($field == 'msort')
				    $msort = ($value_id ? 1 : 0);
				else if ($field == 'chunksz')
				    $chunksz = $value_id;
				else if ($field == 'report_id')
				    $report_id = $value_id;
				else
				    $prefs[$field][] = $value_id;
		
				//echo '<br>DBG restoring prefs : $prefs['.$field.'] []='.$value_id;
		    }
		    $set='custom';
	
		} else {
		    $set='my';
		}

    } else {
		$set='open';
    }
}


if ($set=='my') {
    /*
      My bugs - backwards compat can be removed 9/10
    */
    $prefs['status_id'][]=1; // Open status
    // Check if the current user is in the assigned_to list
	$field_object = $art_field_fact->getFieldFromName('assigned_to');
    if ( ($field_object)&&($field_object->checkValueInPredefinedValues($atid,user_getid())) ) {
	    $prefs['assigned_to'][]=user_getid();
	} else {
		// Any value
	    $prefs['assigned_to'][]=0;
	}		

} else if ($set=='custom') {

    // Get the list of bug fields used in the form (they are in the URL - GET method)
    // and then build the preferences array accordingly
    // Exclude the group_id parameter
    reset($prefs);
    while (list($field,$arr_val) = each($prefs)) {
		while (list(,$value_id) = each($arr_val)) {
		    $pref_stg .= '&'.$field.'[]='.$value_id;
		}
	
		// build part of the HTML title of this page for more friendly bookmarking
		// Do not add the criteria in the header if value is "Any"
		if ($value_id != 0) {
		    $hdr .= ' By '.$field->getLabel().': '.
			$field->getValue($group_id,$value_id);
		}
    }
    $pref_stg .= '&advsrch='.($advsrch ? 1 : 0);
    $pref_stg .= '&msort='.($msort ? 1 : 0);
    $pref_stg .= '&chunksz='.$chunksz;
    $pref_stg .= '&report_id='.$report_id;
    
    if ($pref_stg != user_get_preference('artifact_brow_cust'.$atid)) {
		//echo "<br> DBG setting pref = $pref_stg";
		user_set_preference('artifact_brow_cust'.$atid,$pref_stg);
    }

} else {
    // Open bugs - backwards compat can be removed 9/10
    $prefs['status_id'][]=1;
}


/* ==================================================
   At this point make sure that all paramaters are defined
   as well as all the arguments that serves as selection criteria
   If not defined then defaults to ANY (0)
  ================================================== */

if ( !$pv ) {
	// Display the menus
	$ath->header(array('title'=>'Browse Trackers','titlevals'=>array($ath->getName()),'pagename'=>'tracker_browse',
		'atid'=>$ath->getID(),'sectionvals'=>array($group->getPublicName()),
		'help' => 'ArtifactBrowsing.html'));
} else {
    help_header('Tracker Search Report - '.format_date($sys_datefmt,time()),false);
}	

// Display the artifact items according to all the parameters
$art_report_html->displayReport($prefs,$group_id,$report_id,$set,$advsrch,$msort,$morder,$order,$pref_stg,$offset,$chunksz,$pv);

if ( !$pv ) {
    // Display footer page
    $ath->footer(array());
} else {
    help_footer();
}	

?>
