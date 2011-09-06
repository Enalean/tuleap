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

function group_get_history ($offset, $limit, $group_id=false, $history_filter=null) {

    $sql='select SQL_CALC_FOUND_ROWS group_history.field_name,group_history.old_value,group_history.date,user.user_name '.
         'FROM group_history,user '.
         'WHERE group_history.mod_by=user.user_id ';
    if ($history_filter) {
        $sql .= $history_filter;
    }
    $sql.=' AND group_id='.db_ei($group_id).' ORDER BY group_history.date DESC';
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

/**
 * Builds the group history filter
 * 
 * @param String $event
 * @param Array  $subEventsBox
 * @param String $value
 * @param Date   $startDate
 * @param Date   $endDate
 * @param String $by
 * 
 * @return String
 */
function build_grouphistory_filter ($event = null, $subEventsBox = null, $value = null, $startDate = null, $endDate = null, $by = null) {
    $filter = '';
    if (!empty($by)) {
        $uh = UserHelper::instance();
        $filter .= $uh->getUserNamePatternSQLQuery($by);
    }
    if(!empty($startDate)) {
        list($timestamp,) = util_date_to_unixtime($startDate." 00:00:00");
        $filter .= " AND group_history.date > '".$timestamp."'";
    }
    if(!empty($endDate)) {
        list($timestamp,) = util_date_to_unixtime($endDate." 23:59:59");
        $filter .= " AND group_history.date < '".$timestamp."'";
    }
    if(!empty($value)) {
        //all_users need specific treatement
        if(stristr($value, $GLOBALS["Language"]->getText('project_ugroup', 'ugroup_anonymous_users_name_key'))) {
            $value =  'ugroup_anonymous_users_name_key';
        }
        $filter .= " AND group_history.old_value LIKE '%".$value."%'";
    }
    if(!empty($event)) {
        $filter .= " AND ( 0 ";
        if(!empty($subEventsBox)) {
            foreach ($subEventsBox as $key => $value) {
                $filter .= " OR group_history.field_name LIKE '".$key."%'";
            }
        } else {
            $subEventsList = get_history_entries();
            foreach ($subEventsList[$event] as $key => $value) {
                $filter .= " OR group_history.field_name LIKE '".$value."%'";
            }
        }
        $filter .= " ) ";
    }
    return $filter;
}

function get_history_entries() {
    return array('Permissions' => array('perm_reset_for_field',
                                        'perm_reset_for_tracker',
                                        'perm_reset_for_package',
                                        'perm_reset_for_release',
                                        'perm_reset_for_document',
                                        'perm_reset_for_folder',
                                        'perm_reset_for_docgroup',
                                        'perm_reset_for_wiki',
                                        'perm_reset_for_wikipage',
                                        'perm_reset_for_wikiattachment',
                                        'perm_reset_for_object',
                                        'perm_granted_for_field',
                                        'perm_granted_for_tracker',
                                        'perm_granted_for_package',
                                        'perm_granted_for_release', 
                                        'perm_granted_for_document',
                                        'perm_granted_for_folder',
                                        'perm_granted_for_docgroup',
                                        'perm_granted_for_wiki',
                                        'perm_granted_for_wikipage',
                                        'perm_granted_for_wikiattachment',
                                        'perm_granted_for_object'),
                 'Project' =>     array('rename_done',
                                        'rename_with_error',
                                        'approved',
                                        'deleted',
                                        'rename_request',
                                        'is_public',
                                        'group_type',
                                        'http_domain',
                                        'unix_box',
                                        'changed_public_info',
                                        'changed_trove',
                                        'membership_request_updated',
                                        'import',
                                        'mass_change'),
                 'User Group' =>  array('upd_ug',
                                        'del_ug',
                                        'changed_member_perm'),
                 'Users' =>       array('changed_personal_email_notif',
                                        'added_user',
                                        'removed_user'),
                 'Others' =>      array('changed_bts_form_message',
                                        'changed_bts_allow_anon',
                                        'changed_patch_mgr_settings',
                                        'changed_task_mgr_other_settings',
                                        'changed_sr_settings'),
                 'choose' =>      array('choose_event'));
}

/**
 * Nicely html-formatted output of this group's audit trail
 * 
 * @param Integer $group_id
 * @param Integer $offset  
 * @param Intager $limit
 * @param String  $event  
 * @param Array   $subEventsBox
 * @param String  $value
 * @param Integer $startDate
 * @param Integer $endDate
 * @param String  $by
 */
function show_grouphistory ($group_id, $offset, $limit, $event = null, $subEventsBox = null, $value = null, $startDate = null, $endDate = null, $by = null) {
    /*
     show the group_history rows that are relevant to
     this group_id
     */
    global $Language;

    $history_filter = build_grouphistory_filter($event, $subEventsBox, $value, $startDate, $endDate, $by);
    $res = group_get_history($offset, $limit, $group_id, $history_filter);

    $hp =& Codendi_HTMLPurifier::instance();

    if (isset($subEventsBox)) {
        $subEventsString = implode(",", array_keys($subEventsBox));
        $forwardSubEvents = '&event='.$event.'&subEventsBox='.$subEventsString;
    } else {
        $forwardSubEvents = '&event='.$event;
    }

    echo '
        <H2>'.$Language->getText('project_admin_utils','g_change_history').'</H2>';
    echo'<SPAN title="'.$Language->getText('project_admin_utils','toggle_search').'" id="history_search_title"><img src="'.util_get_image_theme("ic/toggle_minus.png").'" id="toggle_form_icon"><B>'.$Language->getText('project_admin_utils','history_search_title').'</B></SPAN>';
    echo '<FORM METHOD="POST" ACTION="?group_id='.$group_id.'" id="project_history_form" name="project_history_form" enctype="multipart/form-data">';

    echo '<TABLE ID="project_history_search">';
    echo '<TH colspan="2" style="text-align:left">'.$Language->getText('project_admin_utils','event').'</TH>
              <TH style="text-align:left">'.$Language->getText('project_admin_utils','val').'</TH>
              <TH style="text-align:left">'.$Language->getText('project_export_task_export', 'start_date').'</TH>
              <TH style="text-align:left">'.$Language->getText('project_export_task_export', 'end_date').'</TH>
              <TH style="text-align:left">'.$Language->getText('global','by').'</TH>
              <TR VALIGN="TOP"><TD>';

    //Event select Box
    $events = array('Any'         => $GLOBALS["Language"]->getText('global','any'),
                    'Permissions' => $GLOBALS["Language"]->getText("project_admin_utils", "event_permission"), 
                    'Project'     => $GLOBALS["Language"]->getText("project_admin_utils", "event_project"), 
                    'Users'       => $GLOBALS["Language"]->getText("project_admin_utils", "event_user"),
                    'User Group'  => $GLOBALS["Language"]->getText("project_admin_utils", "event_ug"),
                    'Others'      => $GLOBALS["Language"]->getText("project_admin_utils", "event_others"));
    echo  html_build_select_box_from_array($events, 'events_box', $event, false, true);

    //SubEvent select Box
    echo '</TD><TD><select id="sub_events_box" name="sub_events_box[]" multiple>
         <Option value="choose_event" disabled="disabled">'.$GLOBALS['Language']->getText('project_admin_utils', 'choose_event').'</Option>
         </select>';

    echo '</TD><TD><input type="text" name="value" value="'.$value.'"></TD>
              <TD>';
    echo html_field_date('start', $startDate, false, 10, 10, 'project_history_form', false);
    echo '</TD>
              <TD>';
    echo html_field_date('end', $endDate, false, 10, 10, 'project_history_form', false);
    echo '</TD>
              <TD><input type="text" name="by" id="by" value="'.$by.'"></TD>
              </TR>';
    echo '<TR><TD id="events_array"></TD></TR>';
    echo '<TR><TD><input type="submit" name="filter"></TD></TR>
              </TABLE>';
    echo'<P>';
    if ($res['numrows'] > 0) {
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

            echo '<TR class="'. html_get_alt_row_color($i++) .'"><TD>'. $hp->purify($msg, CODENDI_PURIFIER_BASIC, $group_id).'</TD><TD>';
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

            echo '</TD><TD>'.format_date($GLOBALS['Language']->getText('system', 'datefmt'),$row['date']).'</TD>'.
                             '<TD>'.user_get_name_display_from_unix($row['user_name']).'</TD></TR>';
        }

        echo '</TABLE>';

        echo '<div style="text-align:center" class="'. util_get_alt_row_color($i++) .'">';

        if ($offset > 0) {
            echo  '<a href="?group_id='.$group_id.'&offset='.($offset-$limit).$forwardSubEvents.'&value='.$value.'&start='.$startDate.'&end='.$endDate.'&by='.$by.'">[ '.$Language->getText('project_admin_utils', 'previous').' ]</a>';
            echo '&nbsp;';
        }
        if (($offset + $limit) < $res['numrows']) {
            echo '&nbsp;';
            echo '<a href="?group_id='.$group_id.'&offset='.($offset+$limit).$forwardSubEvents.'&value='.$value.'&start='.$startDate.'&end='.$endDate.'&by='.$by.'">[ '.$Language->getText('project_admin_utils', 'next').' ]</a>';
        }
        echo '</div>';
        echo '<div style="text-align:center" class="'. util_get_alt_row_color($i++) .'">';
        echo ($offset+$i-3).'/'.$res['numrows'];
        echo '</div>';
        echo '<BR><TABLE align="left"><TR><TD>
                 <input type="submit" name="export" value="'.$GLOBALS['Language']->getText('project_admin_utils', 'export_history').'">
                 </TD></TR></TABLE></FORM><BR><P>';
    } else {
        echo '<H3>'.$Language->getText('project_admin_utils','no_g_change').'</H3>';
    }

    $translatedEvents = util_php_array_to_js_array2(get_history_entries());

    if(isset($subEventsString)) {
        $selectedSubEvents = explode(",", $subEventsString);
        foreach ($selectedSubEvents as $element) {
            $subEventsBox[] = $element;
        }
    }
    $translatedSelectedEvents = util_php_array_to_js_array($subEventsBox);

    $js = "options = new Array();
           options['defaultValueActsAsHint'] = false;
           new UserAutoCompleter('by', '".util_get_dir_image_theme()."', false, options);
           new ProjectHistory(".$translatedEvents.", ".$translatedSelectedEvents.");";
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
function export_grouphistory ($group_id, $event = null, $subEventsBox = null, $value = null, $startDate = null, $endDate = null, $by = null) {
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

    $history_filter = build_grouphistory_filter($event, $subEventsBox, $value, $startDate, $endDate, $by);
    $res = group_get_history(0, 0, $group_id, $history_filter);

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