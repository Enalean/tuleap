<?php

//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2003. All rights reserved
//
// $Id$
//
//
//  Written for CodeX by Marie-Luise Schneider
//

require_once('pre.php');
require_once('www/project/admin/project_admin_utils.php');
require_once('common/tracker/ArtifactType.class');
require_once('common/tracker/ArtifactTypeFactory.class');

$Language->loadLanguageMsg('tracker/tracker');

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
    exit_error($Language->getText('global','error'),$Language->getText('tracker_import_admin','not_get_atf'));
  }


  $pg_title = $Language->getText('tracker_import_admin','art_import', $groupname);
  
  
  project_admin_header(array('title'=>$pg_title,
			     'help' => 'ArtifactImport.html'));

  $project=project_get_object($group_id);
  if (! $project->usesTracker()) {
      echo '<P> '.$Language->getText('tracker_import_admin','disabled');
      project_admin_footer(array());
  } else {

  // Display the welcome screen
  echo $Language->getText('tracker_import_admin','welcome');
		
  // Show all the fields currently available in the system
  echo '<p><TABLE WIDTH="100%" BORDER="0" CELLSPACING="1" CELLPADDING="2">';
  echo '
  <tr class="boxtable"> 
    <td class="boxtitle">&nbsp;</td>
    <td class="boxtitle"> 
      <div align="center"><b>'.$Language->getText('tracker_import_admin','art_data_import').'</b></div>
    </td>
    <td class="boxtitle"> 
      <div align="center"><b>'.$Language->getText('tracker_import_admin','import_format').'</b></div>
    </td>
 </tr>';
  
  // Get the artfact type list
  $at_arr = $atf->getArtifactTypes();
  
  if ($at_arr && count($at_arr) >= 1) {
    for ($j = 0; $j < count($at_arr); $j++) {
      echo '
		  <tr class="'.util_get_alt_row_color($j).'"> 
		    <td><b>'.$Language->getText('tracker_import_admin','tracker').': '.$at_arr[$j]->getName().'</b></td>
		    <td align="center">
                      <a href="/tracker/index.php?group_id='.$group_id.'&atid='.$at_arr[$j]->getID().'&user_id='.user_getid().'&func=import">'.$Language->getText('tracker_import_admin','import').'</a>
		    </td>
		    <td align="center"> 
		      <a href="/tracker/index.php?group_id='.$group_id.'&atid='.$at_arr[$j]->getID().'&user_id='.user_getid().'&mode=showformat&func=import">'.$Language->getText('tracker_import_admin','show_format').'</a>
		    </td>
		  </tr>';
    }
  }

  echo '</TABLE>';
  project_admin_footer(array());
  }

} else {
  exit_missing_param();
}

?>