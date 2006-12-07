<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

/*

	Standard header to be used on all /project/admin/* pages

*/
require_once('common/include/TemplateSingleton.class.php');

$GLOBALS['Language']->loadLanguageMsg('project/project');

function project_admin_header($params) {
	global $group_id,$feedback,$Language;

	$params['toptab']='admin';
	$params['group']=$group_id;
	site_project_header($params);

	echo '
	<P><TABLE width="100%"><TR>';
        echo '<TD width="1"><b>'.$Language->getText('project_admin_utils','menu_config').'</b></td><td><b>
	<A HREF="/project/admin/editgroupinfo.php?group_id='.$group_id.'">'.$Language->getText('project_admin_utils','edit_public_info').'</A> |
	<A HREF="/project/admin/servicebar.php?group_id='.$group_id.'">'.$Language->getText('project_admin_editservice','s_conf').'</A> |
	<A HREF="/project/admin/reference.php?group_id='.$group_id.'">'.$Language->getText('project_admin_utils','references').'</A>';
        echo '</td><td>';
	if (isset($params['help'])) {
	    echo help_button($params['help'],false,$Language->getText('global','help'));
	}
        echo '</td></tr>';
        echo '</td></tr><tr><td><b>'.$Language->getText('project_admin_utils','menu_permissions').'</b></td><td><b>
	<A HREF="/project/admin/userperms.php?group_id='.$group_id.'">'.$Language->getText('project_admin_utils','user_perms').'</A> | 
	<A HREF="/project/admin/ugroup.php?group_id='.$group_id.'">'.$Language->getText('project_admin_utils','ug_admin').'</A>';
        echo '</td><td></td></tr><tr><td><b>'.$Language->getText('project_admin_utils','menu_data').'</b></td><td><b>
	<A HREF="/project/export/index.php?group_id='.$group_id.'">'.$Language->getText('project_admin_utils','project_data_export').'</A> |
	<A HREF="/tracker/import_admin.php?group_id='.$group_id.'&mode=admin">'.$Language->getText('project_admin_utils','tracker_import').'</A> |
	<A HREF="/project/admin/history.php?group_id='.$group_id.'">'.$Language->getText('project_admin_history','proj_history').'</A> |
        <A HREF="/project/stats/source_code_access.php/?group_id='.$group_id.'">'.$Language->getText('project_admin_utils','access_logs').'</A>';

	//<A HREF="/project/admin/?group_id='.$group_id.'&func=import">Tracker Import</A>

        echo '</td><td></td></tr></table>';
	echo '</B>
	<P>';
}

/*

	Standard footer to be used on all /project/admin/* pages

*/

function project_admin_footer($params) {
	site_project_footer($params);
}



/*

	The following three functions are for group
	audit trail

	When changes like adduser/rmuser/change status
	are made to a group, a row is added to audit trail
	using group_add_history()

*/

function group_get_history ($group_id=false) {
	$sql="select group_history.field_name,group_history.old_value,group_history.date,user.user_name ".
		"FROM group_history,user ".
		"WHERE group_history.mod_by=user.user_id ".
		"AND group_id='$group_id' ORDER BY group_history.date DESC";
	return db_query($sql);
}	       
	
function group_add_history ($field_name,$old_value,$group_id,$args=false) {
	/*      
		handle the insertion of history for these parameters
		$args is an array containing a list of parameters to use when
		   the message is to be displayed by the history.php script
		   The array is stored as a string at the end of the field_name
		   with the following format:
		   field_name %% [arg1, arg2...]
	*/
	
    if ($args) {
	    $field_name .= " %% ".implode("||", $args);
	}
	$sql="insert into group_history(group_id,field_name,old_value,mod_by,date) ".
		"VALUES ('$group_id','$field_name','$old_value','". user_getid() ."','".time()."')";
	return db_query($sql);
}	       

/*

	Nicely html-formatted output of this group's audit trail

*/

function show_grouphistory ($group_id) {
	/*      
		show the group_history rows that are relevant to 
		this group_id
	*/
	global $sys_datefmt,$Language;
	$result=group_get_history($group_id);
	$rows=db_numrows($result);
	
	if ($rows > 0) {
	
		echo '
		<H3>'.$Language->getText('project_admin_utils','g_change_history').'</H3>
		<P>';
		$title_arr=array();
		$title_arr[]=$Language->getText('project_admin_utils','event');
		$title_arr[]=$Language->getText('project_admin_utils','val');
		$title_arr[]=$Language->getText('project_admin_utils','date');
		$title_arr[]=$Language->getText('global','by');
		
		echo html_build_list_table_top ($title_arr);
		
		for ($i=0; $i < $rows; $i++) { 
			$field=db_result($result, $i, 'field_name');

			// see if there are any arguments after the message key 
			// format is "msg_key ## arg1||arg2||...
			// If msg_key cannot be found in the localized message
			// catalog then display the msg has is because this is very
			// likely a legacy message (pre-localization version)
                        if (strpos($field," %% ") !== false) {
                                list($msg_key, $args) = explode(" %% ",$field);
                                if ($args) {
                                    $arr_args = explode('||',$args);
                                }
                        } else {
                            $msg_key=$field;
                            $arr_args="";
                        }
			$msg = $Language->getText('project_admin_utils', $msg_key, $arr_args);
			if (!(strpos($msg,"*** Unkown msg") === false)) {
			    $msg = $field;
			}

			echo '
			<TR class="'. html_get_alt_row_color($i) .'"><TD>'.$msg.'</TD><TD>';
			$val = db_result($result, $i, 'old_value');
			if ($msg_key == "perm_granted_for_field") {
			  $pattern = '/ugroup_([^ ,]*)_name_key/';
			  preg_match_all($pattern,$val,$matches);

			  if (!empty($matches[0])) {
			    foreach ($matches[0] as $match) {
			      $val = str_replace($match,$Language->getText('project_ugroup', $match),$val);
			    }
			  }
			} else if ($msg_key == "group_type") {
			  $template =& TemplateSingleton::instance();
			  $val = $template->getLabel($val);
			}

			echo $val;
						
			echo '</TD>'.
				'<TD>'.format_date($sys_datefmt,db_result($result, $i, 'date')).'</TD>'.
				'<TD>'.db_result($result, $i, 'user_name').'</TD></TR>';
		}	       
				
		echo '	 
		</TABLE>';      
		
	} else {
		echo '  
		<H3>'.$Language->getText('project_admin_utils','no_g_change').'</H3>';
	}       
}       

?>
