<?php

require_once('RegisterProjectStep.class.php');

require_once('common/include/TemplateSingleton.class.php');

/**
* RegisterProjectStep_Template
* 
* TODO: description
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* @author  N. Terray
*/
class RegisterProjectStep_Template extends RegisterProjectStep {
    var $db_templates;
    function RegisterProjectStep_Template() {
        $this->RegisterProjectStep(
            $GLOBALS['Language']->getText('register_title', 'template'),
            'CreatingANewProject.html'
        );
        $template =& TemplateSingleton::instance();
        $this->db_templates = $template->getTemplates();
    }
    
    function display($data) {
        $GLOBALS['Language']->loadLanguageMsg('project/project');
        echo '<fieldset><legend style="font-size:1.2em;">Choose the template of the project</legend>';
        include($GLOBALS['Language']->getContent('project/template'));
        
        $rows=db_numrows($this->db_templates);
        if ($rows > 0) {
            $GLOBALS['Language']->loadLanguageMsg('new/new');
            //echo '<h3>From templates</h3><blockquote>';
            
          $GLOBALS['HTML']->box1_top($GLOBALS['Language']->getText('register_template','choose'));
          print '
          <TABLE width="100%">';
        
          for ($i=0; $i<$rows; $i++) {
                $this->_displayProject(
                    db_result($this->db_templates,$i,'group_id'),
                    db_result($this->db_templates,$i,'group_name'),
                    db_result($this->db_templates,$i,'register_time'),
                    db_result($this->db_templates,$i,'unix_group_name'),
                    db_result($this->db_templates,$i,'short_description')
                );
          }
        
          print '</TABLE>';
          $GLOBALS['HTML']->box1_bottom();
          //echo '</blockquote>';
          
        }
        
        //{{{ Projects where current user is admin
        $result = db_query("SELECT groups.group_name AS group_name, "
            . "groups.group_id AS group_id, "
            . "groups.unix_group_name AS unix_group_name, "
            . "groups.register_time AS register_time, "
            . "groups.short_description AS short_description "
            . "FROM groups, user_group "
            . "WHERE groups.group_id = user_group.group_id "
            . "AND user_group.user_id = '". user_getid() ."' "
            . "AND user_group.admin_flags = 'A' "
            . "AND groups.status='A' ORDER BY group_name");
        echo db_error($result);
        $rows = db_numrows($result);
        if ($result && $rows) {
            echo '<br />';
            $GLOBALS['HTML']->box1_top($GLOBALS['Language']->getText('register_template','choose_admin'));
            print '<TABLE width="100%">';
            for ($i=0; $i<$rows; $i++) {
                $this->_displayProject(
                    db_result($result,$i,'group_id'),
                    db_result($result,$i,'group_name'),
                    db_result($result,$i,'register_time'),
                    db_result($result,$i,'unix_group_name'),
                    db_result($result,$i,'short_description')
                );
            }
            print '</TABLE>';
            $GLOBALS['HTML']->box1_bottom();
        }
        //}}}
        
        echo '</fieldset>';
        
        echo '<fieldset><legend style="font-size:1.2em;">Choose the project type</legend>';
        echo '<p>'. 'Please note that the project type cannot be changed after creation' .'</p>';
        echo '<B>'.$GLOBALS['Language']->getText('project_admin_index','group_type').' '.help_button('ProjectAdministration.html#ProjectType').' : </B> ';
        echo html_build_select_box_from_arrays(array('0', '1'),array($GLOBALS['Language']->getText('include_common_template','project'),$GLOBALS['Language']->getText('include_common_template','test_project')),'is_test','0',false);
        echo '</fieldset>';
    }
    function onLeave($request, &$data) {
        $data['project']['built_from_template'] = $request->get('built_from_template');
        $data['project']['is_test'] = $request->get('is_test') ? '1' : '0';
        return $this->validate($data);
    }
    function validate($data) {
        if (!$data['project']['built_from_template']) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('register_projectname', 'info_missed'));
            return false;
        } else {
            $p =& group_get_object($data['project']['built_from_template']);
            var_dump($data['project']['built_from_template'], user_ismember($data['project']['built_from_template'],'A'));
            if (!$p->isTemplate() && !user_ismember($data['project']['built_from_template'],'A')) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global', 'perm_denied'));
                return false;
            }
        }
        return true;
    }
    
    function _displayProject($group_id, $group_name, $register_time, $unix_group_name, $short_description) {
        print '<TR>';
        $check = "";
        $title = '<B>'. $group_name .
        '</B> (' . date($GLOBALS['sys_datefmt_short'], $register_time) . ')';
        if ($group_id == '100') {
            $check = "checked";
        } else {
            $title = '<A href="/projects/'. $unix_group_name .'" > '. $title .' </A>';
        }
        
        print '
        <TD><input type="radio" name="built_from_template" value="'.$group_id.'" '.$check.'></TD>
        <TD>'.$title.'</td>
        <TD rowspan="2" align="left" valign="top"><I>'. $short_description .'</I></TD>
        </TR>
        ';
        
        // Get Project admin as contacts
        if ($group_id == '100') {
            $res_admin = db_query("SELECT user_name AS user_name "
            . "FROM user "
            . "WHERE user_id='101'");
        } else {
            $res_admin = db_query("SELECT user.user_name AS user_name "
            . "FROM user,user_group "
            . "WHERE user_group.user_id=user.user_id AND user_group.group_id=$group_id AND "
            . "user_group.admin_flags = 'A'");
        }
        $admins = array();
        while ($row_admin = db_fetch_array($res_admin)) {
            $admins[] = '<A href="/users/'.$row_admin['user_name'].'/">'.$row_admin['user_name'].'</A>';
        }
        print '
        <TR>
        <TD> &nbsp</TD>
        <TD><I>'.$GLOBALS['Language']->getText('new_index','contact').': '.join(',',$admins).'</I></TD>
        </TR>
        <TR><TD colspan="3"><HR></TD></TR>';
    }
}

?>
