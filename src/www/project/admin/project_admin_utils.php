<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

/*

	Standard header to be used on all /project/admin/* pages

*/
require_once('common/include/TemplateSingleton.class.php');


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
        
    $em = EventManager::instance();
    $em->processEvent('admin_toolbar_configuration', array('group_id' => $group_id));

        echo '</td><td>';
	if (isset($params['help'])) {
	    echo help_button($params['help'],false,$Language->getText('global','help'));
	}
        echo '</td></tr>';
        echo '</td></tr><tr><td><b>'.$Language->getText('project_admin_utils','menu_permissions').'</b></td><td><b>
	<A HREF="/project/admin/userperms.php?group_id='.$group_id.'">'.$Language->getText('project_admin_utils','user_perms').'</A> | 
	<A HREF="/project/admin/ugroup.php?group_id='.$group_id.'">'.$Language->getText('project_admin_utils','ug_admin').'</A> | 
	<A HREF="/project/admin/permission_request.php?group_id='.$group_id.'">'.$Language->getText('project_admin_ugroup','permission_request').'</A>';
        echo '</td><td></td></tr><tr><td><b>'.$Language->getText('project_admin_utils','menu_data').'</b></td><td><b>
	<A HREF="/project/export/index.php?group_id='.$group_id.'">'.$Language->getText('project_admin_utils','project_data_export').'</A> |
	<A HREF="/tracker/import_admin.php?group_id='.$group_id.'&mode=admin">'.$Language->getText('project_admin_utils','tracker_import').'</A> |
	<A HREF="/project/admin/history.php?group_id='.$group_id.'">'.$Language->getText('project_admin_history','proj_history').'</A> |
    <A HREF="/project/stats/source_code_access.php/?group_id='.$group_id.'">'.$Language->getText('project_admin_utils','access_logs').'</A>';
    //Call hook that can be displayed in this area
    $em->processEvent('admin_toolbar_data', array('group_id' => $group_id));

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

function group_get_history ($offset, $limit, $group_id=false) {

    $sql='select SQL_CALC_FOUND_ROWS group_history.field_name,group_history.old_value,group_history.date,user.user_name '.
         'FROM group_history,user '.
         'WHERE group_history.mod_by=user.user_id '.
         'AND group_id='.db_ei($group_id).' ORDER BY group_history.date DESC';
    if ($offset > 0 || $limit > 0) {
         $sql .= ' LIMIT '.db_ei($offset).', '.db_ei($limit);
    }

    $res = db_query($sql);
    $sql = 'SELECT FOUND_ROWS() as nb';
    $res_numrows = db_query($sql);
    $row = db_fetch_array($res_numrows);
    
    return array('history' => $res, 'numrows' => $row['nb']);
}       

function group_add_history ($field_name,$old_value,$group_id, $args=false) {
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
	$user_id = user_getid();
	if ($user_id == 0){
		$user_id = 100;
	}
	$sql= 'insert into group_history(group_id,field_name,old_value,mod_by,date) '.
		'VALUES ('.db_ei($group_id).' , "'.db_es($field_name). '", "'.db_es($old_value).'" , '.db_ei($user_id).' , '.db_ei(time()).')';
	return db_query($sql);
}	       

/*
 * Nicely html-formatted output of this group's audit trail
 * @param Integer $group_id
 * @param Integer $offset  
 * @param Intager $limit
 * 
*/
function show_grouphistory ($group_id, $offset, $limit) {
	/*      
		show the group_history rows that are relevant to 
		this group_id
	*/
	global $Language;
	
	$res = group_get_history($offset, $limit, $group_id );	
	
	$hp =& Codendi_HTMLPurifier::instance();

	if ($res['numrows'] > 0) {

        echo '
        <H2>'.$Language->getText('project_admin_utils','g_change_history').'</H2>
        <SPAN title="'.$Language->getText('project_admin_utils','toggle_search').'" id="history_search_title"><img src="'.util_get_image_theme("ic/toggle_minus.png").'" id="toggle_form_icon"><B>'.$Language->getText('project_admin_utils','history_search_title').'</B></SPAN>';
        // TODO : Keep values of the last submitted form
        echo '<FORM METHOD="POST" NAME="project_history_form">
              <TABLE ID="project_history_search">';
        echo '<TH style="text-align:left">'.$Language->getText('project_admin_utils','event').'</TH>
              <TH style="text-align:left">'.$Language->getText('project_admin_utils','val').'</TH>
              <TH style="text-align:left">'.$Language->getText('project_export_task_export', 'start_date').'</TH>
              <TH style="text-align:left">'.$Language->getText('project_export_task_export', 'end_date').'</TH>
              <TH style="text-align:left">'.$Language->getText('global','by').'</TH>
              <TR VALIGN="TOP"><TD>';

        echo '<script>
function SelectSubEvent(){
removeAllOptions(document.project_history_form.SubEvent);
addOption(document.project_history_form.SubEvent, "Any", "Any", "");

//Permission section
if(document.project_history_form.events.value == "Permissions"){
addOption(document.project_history_form.SubEvent,"perm_reset_for_field");
addOption(document.project_history_form.SubEvent,"perm_reset_for_tracker");  
addOption(document.project_history_form.SubEvent,"perm_reset_for_package"); 
addOption(document.project_history_form.SubEvent,"perm_reset_for_release");  
addOption(document.project_history_form.SubEvent,"perm_reset_for_document"); 
addOption(document.project_history_form.SubEvent,"perm_reset_for_folder");   
addOption(document.project_history_form.SubEvent,"perm_reset_for_docgroup"); 
addOption(document.project_history_form.SubEvent,"perm_reset_for_wiki"); 
addOption(document.project_history_form.SubEvent,"perm_reset_for_wikipage"); 
addOption(document.project_history_form.SubEvent,"perm_reset_for_wikiattachment");   
addOption(document.project_history_form.SubEvent,"perm_reset_for_object");
addOption(document.project_history_form.SubEvent,"perm_granted_for_field");
addOption(document.project_history_form.SubEvent,"perm_granted_for_tracker");  
addOption(document.project_history_form.SubEvent,"perm_granted_for_package"); 
addOption(document.project_history_form.SubEvent,"perm_granted_for_release");  
addOption(document.project_history_form.SubEvent,"perm_granted_for_document"); 
addOption(document.project_history_form.SubEvent,"perm_granted_for_folder");   
addOption(document.project_history_form.SubEvent,"perm_granted_for_docgroup"); 
addOption(document.project_history_form.SubEvent,"perm_granted_for_wiki"); 
addOption(document.project_history_form.SubEvent,"perm_granted_for_wikipage"); 
addOption(document.project_history_form.SubEvent,"perm_granted_for_wikiattachment");
addOption(document.project_history_form.SubEvent,"perm_granted_for_object", "");
}

//Project section
if(document.project_history_form.events.value == "Project"){
addOption(document.project_history_form.SubEvent,"rename_done");
addOption(document.project_history_form.SubEvent,"rename_with_error");
addOption(document.project_history_form.SubEvent,"approved");
addOption(document.project_history_form.SubEvent,"deleted");
addOption(document.project_history_form.SubEvent,"rename_request");
addOption(document.project_history_form.SubEvent,"status");
addOption(document.project_history_form.SubEvent,"is_public");
addOption(document.project_history_form.SubEvent,"group_type");
addOption(document.project_history_form.SubEvent,"http_domain");
addOption(document.project_history_form.SubEvent,"unix_box");
addOption(document.project_history_form.SubEvent,"changed_public_info");
addOption(document.project_history_form.SubEvent,"changed_trove");
addOption(document.project_history_form.SubEvent,"membership_request_updated");
addOption(document.project_history_form.SubEvent,"import");
addOption(document.project_history_form.SubEvent,"mass_change");
}

//User group section
if(document.project_history_form.events.value == "User Group"){
addOption(document.project_history_form.SubEvent,"upd_ug");
addOption(document.project_history_form.SubEvent,"del_ug");
addOption(document.project_history_form.SubEvent,"changed_member_perm", "");
}

//Users section
if(document.project_history_form.events.value == "Users"){
addOption(document.project_history_form.SubEvent,"changed_personal_email_notif");
addOption(document.project_history_form.SubEvent,"added_user");
addOption(document.project_history_form.SubEvent,"removed_user");
}

//Uncatogorised items section

if(document.project_history_form.events.value == "Others"){
addOption(document.project_history_form.SubEvent,"changed_bts_form_message");
addOption(document.project_history_form.SubEvent,"changed_bts_allow_anon");
addOption(document.project_history_form.SubEvent,"changed_patch_mgr_settings");
addOption(document.project_history_form.SubEvent,"changed_task_mgr_other_settings");
addOption(document.project_history_form.SubEvent,"changed_sr_settings");
}

}

function removeAllOptions(selectbox)
{
    var i;
    for(i=selectbox.options.length-1;i>=0;i--)
    {
        //selectbox.options.remove(i);
        selectbox.remove(i);
    }
}


function addOption(selectbox, value )
{
    var optn = document.createElement("option");
    
    //Need to find how to use the value as index in the tab file
    //optn.text = <?php echo $GLOBALS["Language"]->getText("project_admin_utils", $value);?>;
    optn.text = value;
    optn.value = value;

    selectbox.options.add(optn);
}
        </script>';
        //Event select Box
        echo '<select name="events" onChange="SelectSubEvent();" >
        <Option value="Any">Any</Option>
        <Option value="Permissions">Permissions</Option>
        <Option value="Project">Project</Option>
        <Option value="Users">Users</Option>
        <Option value="User Group">User Group</Option>
        <Option value="Others">Others</Option>
        </select>&nbsp';
        
        //SubEvent select Box
         echo '<select id="SubEvent" name="SubEvent">
         <Option value="Any">Any</Option>
         </select>';

        echo '</TD><TD><INPUT TYPE="TEXT" NAME="VALUE"></TD>
              <TD>';
        echo html_field_date('start', '', false, 10, 10, 'project_history_form', false);
        echo '</TD>
              <TD>';
        echo html_field_date('end', '', false, 10, 10, 'project_history_form', false);
        echo '</TD>
              <TD><INPUT TYPE="TEXT" NAME="BY" ID="BY" CLASS="BY"></TD>
              </TR>';

        echo '<TR><TD><INPUT TYPE="SUBMIT" NAME="FILTER"></TD></TR>
              </TABLE>
		<P>';
		$title_arr=array();
		$title_arr[]=$Language->getText('project_admin_utils','event');
		$title_arr[]=$Language->getText('project_admin_utils','val');
		$title_arr[]=$Language->getText('project_admin_utils','date');
		$title_arr[]=$Language->getText('global','by');
		
		echo html_build_list_table_top ($title_arr);
		$i=1;
		
		while ($row = db_fetch_array($res['history'])) {
			$field = $row['field_name'];

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
			<TR class="'. html_get_alt_row_color($i++) .'"><TD>'. $hp->purify($msg, CODENDI_PURIFIER_BASIC, $group_id).'</TD><TD>';
			$val = $row['old_value'];
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

			echo $hp->purify($val);
						
			echo '</TD>'.
				'<TD>'.format_date($GLOBALS['Language']->getText('system', 'datefmt'),$row['date']).'</TD>'.
				'<TD>'.user_get_name_display_from_unix($row['user_name']).'</TD></TR>';
		}	       
				
		echo '	 
		</TABLE>'; 
		
			echo '<div style="text-align:center" class="'. util_get_alt_row_color($i++) .'">';
			
			if ($offset > 0) {
				echo  '<a href="?group_id='.$group_id.'&offset='.($offset-$limit).'">[ '.$Language->getText('project_admin_utils', 'previous').'  ]</a>';
                echo '&nbsp;';
            }
            if (($offset + $limit) < $res['numrows']) {
                echo '&nbsp;';
                echo '<a href="?group_id='.$group_id.'&offset='.($offset+$limit).'">[ '.$Language->getText('project_admin_utils', 'next').' ]</a>';
            }
            echo '</div>';
            echo '<div style="text-align:center" class="'. util_get_alt_row_color($i++) .'">';
            echo ($offset+$i-3).'/'.$res['numrows'];
            echo '</div>';
        
    } else {
        echo '<H3>'.$Language->getText('project_admin_utils','no_g_change').'</H3>';
    }
    echo '<BR><TABLE align="left"><TR><TD>
          <INPUT TYPE="SUBMIT" NAME="EXPORT" VALUE="'.$GLOBALS['Language']->getText('project_admin_utils', 'export_history').'">
          </TD></TR></TABLE></FORM><BR><P>';
    $js = "new UserAutoCompleter('BY', '".util_get_dir_image_theme()."', true);
           new ProjectHistory();";
    $GLOBALS['HTML']->includeFooterJavascriptFile('/scripts/codendi/ProjectHistory.js');
    $GLOBALS['Response']->includeFooterJavascriptSnippet($js);
}

/**
 * Export project history to a csv file
 *
 * @param Integer $group_id
 * @param Integer $offset
 * @param Integer $limit
 *
 * @return void
 */
function export_grouphistory ($group_id) {
    global $Language;

    header ('Content-Type: text/csv');
    header ('Content-Disposition: filename=project_history.csv');

    $eol = "\n";

    $col_list = array('event', 'val', 'date', 'by');
    $documents_title = array ('event' => $Language->getText('project_admin_utils','event'),
                              'val'   => $Language->getText('project_admin_utils','val'),
                              'date'  => $Language->getText('project_admin_utils','date'),
                              'by'    => $Language->getText('global','by'));
    echo build_csv_header($col_list, $documents_title).$eol;

    $res = group_get_history(0, 0, $group_id );

    $hp = Codendi_HTMLPurifier::instance();

    if ($res['numrows'] > 0) {
        while ($row = db_fetch_array($res['history'])) {
            $field = $row['field_name'];

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

            $val = $row['old_value'];
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

            $documents_body = array ('event' => $hp->purify($msg, CODENDI_PURIFIER_BASIC, $group_id),
                                     'val'   => $hp->purify($val),
                                     'date'  => format_date($GLOBALS['Language']->getText('system', 'datefmt'),$row['date']),
                                     'by'    => user_get_name_display_from_unix($row['user_name']));
            echo build_csv_record($col_list, $documents_body).$eol;
        }

    }
    echo build_csv_header($col_list, array()).$eol;
}

function project_admin_display_bullet_user($user_id, $action, $url = null) {
    if ($action == 'add') {
        $icon       = '/ic/add.png';
    } else {
        $icon       = '/ic/cross.png';
    }
    if (!$url) {
        $url = $_SERVER['REQUEST_URI'] .'&user['. $user_id .']='. $action;
    }
    echo '<a href="'. $url .'">';
    echo '<img alt="'. $action .'" src="'. util_get_dir_image_theme() . $icon .'" />';
    echo '</a>';
}
?>
