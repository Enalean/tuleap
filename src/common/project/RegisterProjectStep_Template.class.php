<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('RegisterProjectStep.class.php');

require_once('common/include/TemplateSingleton.class.php');

/**
* RegisterProjectStep_Template
*/
class RegisterProjectStep_Template extends RegisterProjectStep {
    var $db_templates;
    function RegisterProjectStep_Template() {
        $this->RegisterProjectStep(
            $GLOBALS['Language']->getText('register_title', 'template'),
            'new-project.html'
        );
        $template =& TemplateSingleton::instance();
        $this->db_templates = $template->getTemplates();
    }
    
    function display($data) {
        echo '<fieldset><legend style="font-size:1.2em;">Choose the template of the project</legend>';
        include($GLOBALS['Language']->getContent('project/template'));
        
        $rows=db_numrows($this->db_templates);
        if ($rows > 0) {
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
            include($GLOBALS['Language']->getContent('project/template_my'));
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
    }
    function onLeave($request, &$data) {
        $data['project']['built_from_template'] = $request->get('built_from_template');
        return $this->validate($data);
    }
    function validate($data) {
        if (!$data['project']['built_from_template']) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('register_projectname', 'info_missed'));
            return false;
        } else {
            $pm = ProjectManager::instance();
            $p = $pm->getProject($data['project']['built_from_template']);
            if (!$p->isTemplate() && !user_ismember($data['project']['built_from_template'],'A')) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global', 'perm_denied'));
                return false;
            }
        }
        return true;
    }
    
    function _displayProject($group_id, $group_name, $register_time, $unix_group_name, $short_description) {
        $hp = Codendi_HTMLPurifier::instance();
        print '<TR>';
        $check = "";
        $title = '<B>'.  $hp->purify(util_unconvert_htmlspecialchars($group_name), CODENDI_PURIFIER_CONVERT_HTML)  .
        '</B> (' . date($GLOBALS['Language']->getText('system', 'datefmt_short'), $register_time) . ')';
        if ($group_id == '100') {
            $check = "checked";
        } else {
            $title = '<A href="/projects/'. $unix_group_name .'" > '. $title .' </A>';
        }
        
        print '
        <TD><input type="radio" name="built_from_template" value="'.$group_id.'" '.$check.'></TD>
        <TD>'.$title.'</td>
        <TD rowspan="2" align="left" valign="top"><I>'.  $hp->purify(util_unconvert_htmlspecialchars($short_description), CODENDI_PURIFIER_LIGHT, $group_id)  .'</I></TD>
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
