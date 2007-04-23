<?php

require_once('Widget.class.php');
require_once('common/tracker/ArtifactTypeFactory.class.php');

/**
* Widget_MyArtifacts
* 
* Artifact assigned to or submitted by this person
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* @author  N. Terray
*/
class Widget_MyArtifacts extends Widget {
    var $_artifact_show;
    function Widget_MyArtifacts() {
        $this->Widget('myartifacts');
        $this->_artifact_show = user_get_preference('my_artifacts_show');
        if($this->_artifact_show === false) {
            $this->_artifact_show = 'AS';
            user_set_preference('my_artifacts_show', $this->_artifact_show);
        }
    }
    function _getTitle() {
        return $GLOBALS['Language']->getText('my_index', 'my_arts');
    }
    function updatePreferences(&$request) {
        if (!$request->exist('cancel')) {
            if ($request->exist('show')) {
                switch($request->get('show')) {
                    case 'A':
                        $this->_artifact_show = 'A';
                        break;
                    case 'S':
                        $this->_artifact_show = 'S';
                        break;
                    case 'N':
                        $this->_artifact_show = 'N';
                        break;
                    case 'AS':
                    default:
                        $this->_artifact_show = 'AS';
                }
                user_set_preference('my_artifacts_show', $this->_artifact_show);
            }
        }
        return true;
    }
    function getPreferences() {
        $prefs  = '';
        $prefs .= '<form method="POST" action="widget.php?action=update&amp;name='. $this->id .'">';
        $prefs .= '<fieldset><legend>Preferences</legend>';
        $prefs .= 'Display artifacts: <select name="show">';
        $prefs .= '<option value="N"  '.($this->_artifact_show === 'N'?'selected="selected"':'').'>'.$GLOBALS['Language']->getText('my_index', 'no_info');
        $prefs .= '<option value="A"  '.($this->_artifact_show === 'A'?'selected="selected"':'').'>'.$GLOBALS['Language']->getText('my_index', 'a_info');
        $prefs .= '<option value="S"  '.($this->_artifact_show === 'S'?'selected="selected"':'').'>'.$GLOBALS['Language']->getText('my_index', 's_info');
        $prefs .= '<option value="AS" '.($this->_artifact_show === 'AS'?'selected="selected"':'').'>'.$GLOBALS['Language']->getText('my_index', 'as_info');
        $prefs .= '</select>';
        $prefs .= '<br />';
        $prefs .= '<input type="submit" name="cancel" value="'. $GLOBALS['Language']->getText('global', 'btn_cancel') .'" />&nbsp;';
        $prefs .= '<input type="submit" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" />';
        $prefs .= '</fieldset>';
        $prefs .= '</form>';
        return $prefs;
        
    }
    function _getContent() {
        $html_my_artifacts = '<table style="width:100%">';
        if ($atf = new ArtifactTypeFactory(false)) {
            $my_artifacts = $atf->getMyArtifacts(user_getid(), $this->_artifact_show);
            if (db_numrows($my_artifacts) > 0) {
                $html_my_artifacts .= $this->_display_artifacts($my_artifacts, 0);
            }
        } else {
            $html_my_artifacts = $GLOBALS['Language']->getText('my_index', 'err_artf');
        }
        $html_my_artifacts .= '<TR><TD COLSPAN="3">'.(($this->_artifact_show == 'N' || db_numrows($my_artifacts) > 0)?'&nbsp;':$GLOBALS['Language']->getText('global', 'none')).'</TD></TR>';
        $html_my_artifacts .= '</table>';
        return $html_my_artifacts;
    }
    function _display_artifacts($list_trackers, $print_box_begin) {
        $request =& HTTPRequest::instance();
        $hide_item_id  = $request->exist('hide_item_id')  ? $request->get('hide_item_id')  : null;
        $hide_artifact = $request->exist('hide_artifact') ? $request->get('hide_artifact') : null;
        
        $j = $print_box_begin;
        $html_my_artifacts = "";
        $html = "";
        $html_hdr = "";
        
        $aid_old  = 0;
        $atid_old = 0;
        $group_id_old = 0;
        $count_aids = 0;
        $group_name = "";
        $tracker_name = "";
        
        $artifact_types = array();
        
        while ($trackers_array = db_fetch_array($list_trackers)) {
            $atid = $trackers_array['group_artifact_id'];
            $group_id = $trackers_array['group_id'];
            
            // {{{ check permissions
            //create group
            $group = group_get_object($group_id);
            if (!$group || !is_object($group) || $group->isError()) {
                    exit_no_group();
            }
            //Create the ArtifactType object
            if (!isset($artifact_types[$group_id])) {
                $artifact_types[$group_id] = array();
            }
            if (!isset($artifact_types[$group_id][$atid])) {
                $artifact_types[$group_id][$atid] = array();
                $artifact_types[$group_id][$atid]['at'] =& new ArtifactType($group,$atid);
                $artifact_types[$group_id][$atid]['user_can_view_at']             = $artifact_types[$group_id][$atid]['at']->userCanView();
                $artifact_types[$group_id][$atid]['user_can_view_summary_or_aid'] = null;
            }
            //Check if user can view artifact
            if ($artifact_types[$group_id][$atid]['user_can_view_at'] && $artifact_types[$group_id][$atid]['user_can_view_summary_or_aid'] !== false) {
                if (is_null($artifact_types[$group_id][$atid]['user_can_view_summary_or_aid'])) {
                    $at =& $artifact_types[$group_id][$atid]['at'];
                    //Create ArtifactFieldFactory object
                    if (!isset($artifact_types[$group_id][$atid]['aff'])) {
                        $artifact_types[$group_id][$atid]['aff'] =& new ArtifactFieldFactory($at);
                    }
                    $aff =& $artifact_types[$group_id][$atid]['aff'];
                    //Retrieve artifact_id field
                    $field =& $aff->getFieldFromName('artifact_id');
                    //Check if user can read it
                    $user_can_view_aid = $field->userCanRead($group_id, $atid);
                    //Retrieve percent_complete field
                    $field =& $aff->getFieldFromName('percent_complete');
                    //Check if user can read it
                    $user_can_view_percent_complete = $field && $field->userCanRead($group_id, $atid);
                    //Retriebe summary field
                    $field =& $aff->getFieldFromName('summary');
                    //Check if user can read it
                    $user_can_view_summary = $field->userCanRead($group_id, $atid);
                    $artifact_types[$group_id][$atid]['user_can_view_summary_or_aid'] = $user_can_view_aid || $user_can_view_summary;
                }
                if ($artifact_types[$group_id][$atid]['user_can_view_summary_or_aid']) {
                    
                    //work on the tracker of the last round if there was one
                    if ($atid != $atid_old && $count_aids != 0) {
                        list($hide_now,$count_diff,$hide_url) = 
                            my_hide_url('artifact',$atid_old,$hide_item_id,$count_aids,$hide_artifact);
                        $html_hdr = ($j ? '<tr class="boxitem"><td colspan="3">' : '').
                        $hide_url.'<A HREF="/tracker/?group_id='.$group_id_old.'&atid='.$atid_old.'">'.
                        $group_name." - ".$tracker_name.'</A>&nbsp;&nbsp;&nbsp;&nbsp;';
                        $count_new = max(0, $count_diff);
                          
                        $html_hdr .= my_item_count($count_aids,$count_new).'</td></tr>';
                        $html_my_artifacts .= $html_hdr.$html;
                        
                        $count_aids = 0;
                        $html = '';
                        $j++;
                      
                    } 
                    
                    if ($count_aids == 0) {
                      //have to call it to get at least the hide_now even if count_aids is false at this point
                      $hide_now = my_hide('artifact',$atid,$hide_item_id,$hide_artifact);
                    }
                    
                    $group_name   = $trackers_array['group_name'];
                    $tracker_name = $trackers_array['name'];
                    $aid          = $trackers_array['artifact_id'];
                    $summary      = $trackers_array['summary'];
                    $atid_old     = $atid;
                    $group_id_old = $group_id;
            
                    // If user is assignee and submitter of an artifact, it will
                    // appears 2 times in the result set.
                    if($aid != $aid_old) {
                        $count_aids++;
                    }
            
                    if (!$hide_now && $aid != $aid_old) {
                      
                        // Form the 'Submitted by/Assigned to flag' for marking
                        $AS_flag = my_format_as_flag2($trackers_array['assignee'],$trackers_array['submitter']);
                        
                        //get percent_complete if this field is used in the tracker
                        $percent_complete = '';
                        if ($user_can_view_percent_complete) {
                            $sql = 
                                "SELECT afvl.value ".
                                "FROM artifact_field_value afv,artifact_field af, artifact_field_value_list afvl, artifact_field_usage afu ".
                                "WHERE af.field_id = afv.field_id AND af.field_name = 'percent_complete' ".
                                "AND afv.artifact_id = $aid ".
                                "AND afvl.group_artifact_id = $atid AND af.group_artifact_id = $atid ".
                                "AND afu.group_artifact_id = $atid AND afu.field_id = af.field_id AND afu.use_it = 1 ".
                                "AND afvl.field_id = af.field_id AND afvl.value_id = afv.valueInt";
                            $res = db_query($sql);
                            if (db_numrows($res) > 0) {
                                $percent_complete = '<TD class="small">'.db_result($res,0,'value').'</TD>';
                            }
                        }
                        $html .= '
                            <TR class="'.get_priority_color($trackers_array['severity']).
                            '"><TD class="small"><A HREF="/tracker/?func=detail&group_id='.
                        $group_id.'&aid='.$aid.'&atid='.$atid.
                            '">'.$aid.'</A></TD>'.
                            '<TD class="small"'.($percent_complete ? '>': ' colspan="2">');
                        if ($user_can_view_summary) {
                            $html .= stripslashes($summary);
                        }
                        $html .= '&nbsp;'.$AS_flag.'</TD>'.$percent_complete.'</TR>';
                      
                    }
                    $aid_old = $aid;
                }
            }
        }
        
        //work on the tracker of the last round if there was one
        if ($atid_old != 0 && $count_aids != 0) {
            list($hide_now,$count_diff,$hide_url) = my_hide_url('artifact',$atid_old,$hide_item_id,$count_aids,$hide_artifact);
            $html_hdr = ($j ? '<tr class="boxitem"><td colspan="3">' : '').
              $hide_url.'<A HREF="/tracker/?group_id='.$group_id_old.'&atid='.$atid_old.'">'.
              $group_name." - ".$tracker_name.'</A>&nbsp;&nbsp;&nbsp;&nbsp;';
            $count_new = max(0, $count_diff);
            
            $html_hdr .= my_item_count($count_aids,$count_new).'</td></tr>';
            $html_my_artifacts .= $html_hdr.$html;
        }
        
        return $html_my_artifacts;
        
    }
}
?>
