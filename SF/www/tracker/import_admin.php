<?php

//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2003. All rights reserved
//
// $Id$
//
//
//  Written for CodeX by Marie-Luise Schneider
//

require($DOCUMENT_ROOT.'/include/pre.php');
require($DOCUMENT_ROOT.'/project/admin/project_admin_utils.php');
require($DOCUMENT_ROOT.'/../common/tracker/ArtifactType.class');
require($DOCUMENT_ROOT.'/../common/tracker/ArtifactTypeFactory.class');

if ($group_id && $mode == "admin") {


  //   the welcome screen when entering the import facility from admin page ******************************************
  
  session_require(array('group'=>$group_id,'admin_flags'=>'A'));

  //	  
  //  get the Group object
  //	  
  $group = group_get_object($group_id);
  if (!$group || !is_object($group) || $group->isError()) {
	exit_no_group();
  }		   
  $atf = new ArtifactTypeFactory($group);
  if (!$group || !is_object($group) || $group->isError()) {
	exit_error('Error','Could Not Get ArtifactTypeFactory');
  }


  $pg_title = 'Tracker Artifact Import '.$groupname;
  
  
  project_admin_header(array('title'=>$pg_title,
			     'help' => 'ArtifactImport.html'));
  // Display the welcome screen
  echo '
    <P> You can import artifacts into a specific tracker from
    a text file (CSV format).
																			 
    <h3>Tracker Artifact Import</h3>
										
    <P>Click on the links below to import artifacts (insert of new artifacts or update of existing ones) or to see the CSV import format.
    <P>';
		
  // Show all the fields currently available in the system
  echo '<p><TABLE WIDTH="100%" BORDER="0" CELLSPACING="1" CELLPADDING="2">';
  echo '
  <tr class="boxtable"> 
    <td class="boxtitle">&nbsp;</td>
    <td class="boxtitle"> 
      <div align="center"><b>Artifacts Data Import</b></div>
    </td>
    <td class="boxtitle"> 
      <div align="center"><b>Import Format</b></div>
    </td>
 </tr>';
  
  // Get the artfact type list
  $at_arr = $atf->getArtifactTypes();
  
  if ($at_arr && count($at_arr) >= 1) {
    for ($j = 0; $j < count($at_arr); $j++) {
      echo '
		  <tr class="'.util_get_alt_row_color($j).'"> 
		    <td><b>Tracker: '.$at_arr[$j]->getName().'</b></td>
		    <td align="center">
                      <a href="/tracker/index.php?group_id='.$group_id.'&atid='.$at_arr[$j]->getID().'&user_id='.user_getid().'&func=import">Import</a>
		    </td>
		    <td align="center"> 
		      <a href="/tracker/index.php?group_id='.$group_id.'&atid='.$at_arr[$j]->getID().'&user_id='.user_getid().'&mode=showformat&func=import">Show Format</a>
		    </td>
		  </tr>';
    }
  }

  echo '</TABLE>';


} else {
  exit_missing_param();
}

?>