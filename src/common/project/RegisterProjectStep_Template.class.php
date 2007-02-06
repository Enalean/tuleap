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
        include($GLOBALS['Language']->getContent('project/template'));
        $rows=db_numrows($this->db_templates);
        if ($rows > 0) {
            $GLOBALS['Language']->loadLanguageMsg('new/new');
          $GLOBALS['HTML']->box1_top($GLOBALS['Language']->getText('register_template','choose'));
          print '
          <TABLE width="100%">';
        
          for ($i=0; $i<$rows; $i++) {
            
                print '
              <TR>';
            
            $group_id = db_result($this->db_templates,$i,'group_id');
            $check = "";
            $title = '<B>'.db_result($this->db_templates,$i,'group_name').
            '</B> (' . date($GLOBALS['sys_datefmt_short'],db_result($this->db_templates,$i,'register_time')) . ')';
            if ($group_id == '100') {
              $check = "checked";
            } else {
              $title = '<A href="/projects/'.db_result($this->db_templates,$i,'unix_group_name').'" > '.$title.' </A>';
            }
        
            print '
                <TD><input type="radio" name="built_from_template" value="'.$group_id.'" '.$check.'></TD>
                <TD>'.$title.'
                <TD rowspan="2" align="left" valign="top"><I>'.db_result($this->db_templates,$i,'short_description').'</I></TD>
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
        
          print '
          </TABLE>';
          $GLOBALS['HTML']->box1_bottom();
        
         }
    }
    function onLeave($request, &$data) {
        $data['project']['built_from_template'] = $request->get('built_from_template');
        return $this->validate($data);
    }
    function validate($data) {
        if (!$data['project']['built_from_template']) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('register_projectname', 'info_missed'));
            return false;
        }
        return true;
    }
}

?>
