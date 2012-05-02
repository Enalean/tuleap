<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');    
require_once('www/project/admin/project_admin_utils.php');
require_once('account.php');
require_once('common/include/TemplateSingleton.class.php');
require_once('common/tracker/ArtifactType.class.php');
require_once('common/tracker/ArtifactTypeFactory.class.php');
require_once('www/project/admin/ugroup_utils.php');


// Valid group id
$vGroupId = new Valid_GroupId();
$vGroupId->required();
if(!$request->valid($vGroupId)) {
    exit_error($Language->getText('project_admin_index','invalid_p'), $Language->getText('project_admin_index','p_not_found'));
}
$group_id = $request->get('group_id');

//must be a project admin
session_require(array('group'=>$group_id,'admin_flags'=>'A'));

//
//  get the Group object
//
$pm    = ProjectManager::instance();
$group = $pm->getProject($group_id);
if (!$group || !is_object($group) || $group->isError()) {
  exit_no_group();
}

//if the project isn't active, require you to be a member of the super-admin group
if ($group->getStatus() != 'A') {
    session_require(array('group'=>1));
}

$em = EventManager::instance();

$vFunc = new Valid_WhiteList('func', array('adduser', 'rmuser', 'change_group_type', 'member_req_notif_group', 'member_req_notif_message'));
$vFunc->required();
if ($request->isPost() && $request->valid($vFunc)) {
    /*
      updating the database
    */
    switch ($request->get('func')) {
    case 'adduser':
        // add user to this project
        $res = account_add_user_to_group ($group_id,$form_unix_name);
        break;

    case 'rmuser':
        // remove a user from this portal
        account_remove_user_from_group($group_id, $rm_id);
        break;

    case 'change_group_type':
        if (user_is_super_user() && ($group->getType() != $form_project_type)) {
            group_add_history ('group_type',$group->getType(),$group_id);

            $template = TemplateSingleton::instance();
            $group->setType($form_project_type);

            //set also flag on trackers to be copied or not on project instanciation
            if ($template->isTemplate($form_project_type)) {
                db_query("UPDATE artifact_group_list SET instantiate_for_new_projects='1' WHERE group_id='$group_id'");
            } else {
                db_query("UPDATE artifact_group_list SET instantiate_for_new_projects='0' WHERE group_id='$group_id'");
            }

            // get current information, force update on group and project objects
            $group = $pm->getProject($group_id, true);

            $GLOBALS['Response']->addFeedback('info', $Language->getText('project_admin_index','changed_group_type'));
      }
      break;
    }
}

project_admin_header(array('title'=>$Language->getText('project_admin_index','p_admin',$group->getPublicName()),'group'=>$group_id,
			   'help' => 'ProjectAdministration.html'));

/*
	Show top box listing trove and other info
*/

echo '<TABLE width=100% border=0>
<TR valign=top><TD width=50%>';

$HTML->box1_top($Language->getText('project_admin_index','p_edit',$group->getPublicName())); 

$hp =& Codendi_HTMLPurifier::instance();

print '&nbsp;
<BR>
'.$Language->getText('project_admin_index','short_desc',$hp->purify($group->getDescription(), CODENDI_PURIFIER_LIGHT));
if ($group->usesHomePage()) {
    print '<P>'.$Language->getText('project_admin_index','home_page_link',$group->getHomePage()).'</B>';
 }

print '&nbsp;
<BR><P>
'.$Language->getText('project_admin_index','view_proj_activity',"/project/stats/?group_id=$group_id");

if ($GLOBALS['sys_use_trove'] != 0) {
    print '
<P>
<B>'.$Language->getText('project_admin_index','trove_cat_info').'

<UL>';

    // list all trove categories
    $res_trovecat = db_query('SELECT trove_cat.fullpath AS fullpath,'
			 .'trove_cat.trove_cat_id AS trove_cat_id '
			 .'FROM trove_cat,trove_group_link WHERE trove_cat.trove_cat_id='
			 .'trove_group_link.trove_cat_id AND trove_group_link.group_id='.$group_id
			 .' ORDER BY trove_cat.fullpath');
    while ($row_trovecat = db_fetch_array($res_trovecat)) {
        print ('<LI>'.$row_trovecat['fullpath'].' '
               .help_button('trove_cat',$row_trovecat['trove_cat_id'])."\n");
    }

    print '
</UL>
<P align="center">
<A href="/project/admin/group_trove.php?group_id='.$group_id.'">'
.'<B>'.$Language->getText('project_admin_index','edit_trove_cat').'</B></A>
';
 }


// list all possible project types
// get current information
$template =& TemplateSingleton::instance(); 


print '
<HR NoShade SIZE="1">
<P>';
if (user_is_super_user()) {
    print '<TABLE WIDTH="100%" BORDER="0">
 <TR>
  <TD><B>'.$Language->getText('project_admin_index','group_type').' '.help_button('ProjectType.html').' : </B>
      <FORM action="?" method="post">
      <INPUT TYPE="HIDDEN" NAME="func" VALUE="change_group_type">
      <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'"></TD>
  <TD valign="top">'.$template->showTypeBox('form_project_type',$group->getType()).'
      <INPUT type="submit" name="Update" value="'.$Language->getText('global','btn_update').'">
      </FORM></TD>
 </TR>
</TABLE>
';
} else {
    print '<B>'.$Language->getText('project_admin_index','group_type').' '.help_button('ProjectType.html').' : '.$template->getLabel($group->getType()).'</B>';
}

$template_group = $pm->getProject($group->getTemplate());
$template_name = $template_group->getPublicName();
print '
<HR NoShade SIZE="1">
<P>
'.$Language->getText('project_admin_index','built_from_template','<A href="/projects/'.$template_group->getUnixName().'"> <B> '.$template_name.' </B></A>');

if ($group->isTemplate()) {
    echo '<hr NoShade SIZE="1" /><p><b>'. $GLOBALS['Language']->getText('project_admin_index', 'show_projects') .':</b> <a id="show_projects_link" href="projects.php?group_id='. $group_id .'">'. $GLOBALS['Language']->getText('project_admin_index', 'show_projects_show') .'</a></p><div id="show_projects_div"></div>';
    $show_projects_show = htmlentities($GLOBALS['Language']->getText('project_admin_index', 'show_projects_show'), ENT_QUOTES, 'UTF-8');
    $show_projects_hide = htmlentities($GLOBALS['Language']->getText('project_admin_index', 'show_projects_hide'), ENT_QUOTES, 'UTF-8');
    echo <<<EOS
    <script type="text/javascript">
    var show_projects_done = false;
    var show_projects_link_txt;
    Event.observe(window, 'load', function() {
        if ($('show_projects_link')) {
            Event.observe($('show_projects_link'), 'click', function (evt) {
                    if (!show_projects_done) {
                        new Ajax.Updater('show_projects_div', $('show_projects_link').href, {
                            onSuccess: function() {
                                show_projects_link_txt = '$show_projects_hide';
                                $('show_projects_link').update(show_projects_link_txt);
                                show_projects_done = true;
                            }
                        });
                    } else {
                        if (show_projects_link_txt == '$show_projects_hide') {
                            show_projects_link_txt = '$show_projects_show';
                        } else {
                            show_projects_link_txt = '$show_projects_hide';
                        }
                        $('show_projects_link').update(show_projects_link_txt);
                        $('show_projects_div').toggle();
                        Event.stop(evt);
                        return false;
                    }
                Event.stop(evt);
                return false;
            });
        }
    });
    </script>
EOS;
}

$HTML->box1_bottom(); 

echo '
</TD><TD>&nbsp;</TD><TD width=50%>';


$HTML->box1_top($Language->getText('project_admin_editugroup','proj_members')."&nbsp;".help_button('UserPermissions.html'));

/*

	Show the members of this project

*/

$res_memb = db_query("SELECT user.realname,user.user_id,user.user_name,user.status ".
		     "FROM user,user_group ".
		     "WHERE user.user_id=user_group.user_id ".
		     "AND user_group.group_id=$group_id ".
             "ORDER BY user.realname");
print '<div  style="max-height:200px; overflow:auto;">';
print '<TABLE WIDTH="100%" BORDER="0">';
$user_helper = new UserHelper();
while ($row_memb=db_fetch_array($res_memb)) {
    $display_name = '';
    $em->processEvent('get_user_display_name', array(
        'user_id'           => $row_memb['user_id'],
        'user_name'         => $row_memb['user_name'],
        'realname'          => $row_memb['realname'],
        'user_display_name' => &$display_name
    ));
    if (!$display_name) {
        $display_name = $hp->purify($user_helper->getDisplayName($row_memb['user_name'], $row_memb['realname']));
    }
    print '<FORM ACTION="?" METHOD="POST"><INPUT TYPE="HIDDEN" NAME="func" VALUE="rmuser">'.
	'<INPUT TYPE="HIDDEN" NAME="rm_id" VALUE="'.$row_memb['user_id'].'">'.
	'<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'. $group_id .'">'.
	'<TR><TD ALIGN="center"><INPUT TYPE="IMAGE" NAME="DELETE" SRC="'.util_get_image_theme("ic/trash.png").'" HEIGHT="16" WIDTH="16" BORDER="0"></TD></FORM>'.
	'<TD><A href="/users/'.$row_memb['user_name'].'/">'. $display_name .' </A></TD></TR>';
}

print '</TABLE></div> <HR NoShade SIZE="1">';

/*
	Add member form
*/

echo '
          <FORM ACTION="?" METHOD="POST">
          <INPUT TYPE="hidden" NAME="func" VALUE="adduser">
          <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'. $group_id .'">
          <TABLE WIDTH="100%" BORDER="0">
          <TR><TD><B>'.$Language->getText('project_admin_index','login_name').'</B></TD><TD><INPUT TYPE="TEXT" NAME="form_unix_name" VALUE="" id="add_user"></TD></TR>
';

// JS code for autocompletion on "add_user" field defined on top.
$js = "new UserAutoCompleter('add_user',
                          '".util_get_dir_image_theme()."',
                          false);";
$GLOBALS['Response']->includeFooterJavascriptSnippet($js);

echo '
          <TR><TD COLSPAN="2" ALIGN="CENTER"><INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="'.$Language->getText('project_admin_index','add_user').'"></TD></TR></FORM>
          </TABLE>
';

$em->processEvent('project_admin_add_user_form', array('groupId' => $group_id));

echo '
         <HR NoShade SIZE="1">
         <div align="center">
         <A href="/project/admin/userimport.php?group_id='. $group_id.'">'.$Language->getText('project_admin_index','import_user').'</A>       
         </div>
                
         <HR NoShade SIZE="1">
         <div align="center">
         <A href="/project/admin/userperms.php?group_id='. $group_id.'">'.$Language->getText('project_admin_index','edit_member_perm').'</A>
         </div>
         </TD></TR>';
 
$HTML->box1_bottom();


echo '</TD></TR>

	<TR valign=top><TD width=50%>';

/*
	Links to Services administration pages
*/

$HTML->box1_top($Language->getText('project_admin_index','s_admin').'&nbsp;'.help_button('ServicesAdministration.html'));


echo '
	<BR>';
if ($group->usesForum()) {
    echo '	<A HREF="/forum/admin/?group_id='.$group_id.'">'.$Language->getText('project_admin_index','forum_admin').'</A><BR>';
}
if ($group->usesMail()) {
    echo '	<A HREF="/mail/admin/?group_id='.$group_id.'">'.$Language->getText('project_admin_index','lists_admin').'</A><BR>';
}
if ($group->usesDocman()) {
    echo '	<A HREF="/docman/admin/?group_id='.$group_id.'">'.$Language->getText('project_admin_index','doc_admin').'</A><BR>';
}
if ($group->usesWiki()) {
    echo '	<A HREF="/wiki/admin/?group_id='.$group_id.'">'.$Language->getText('project_admin_index','wiki_admin').'</A><BR>';
}
if ($group->usesSurvey()) {
    echo '	<A HREF="/survey/admin/?group_id='.$group_id.'">'.$Language->getText('project_admin_index','survey_admin').'</A><BR>';
}
if ($group->usesNews()) {
    echo '	<A HREF="/news/admin/?group_id='.$group_id.'">'.$Language->getText('project_admin_index','news_admin').'</A><BR>';
}
if ($group->usesCVS()) {
    echo '	<A HREF="/cvs/?func=admin&group_id='.$group_id.'">'.$Language->getText('project_admin_index','cvs_admin').'</A><BR>';
}
if ($group->usesSVN()) {
    echo '	<A HREF="/svn/admin/?group_id='.$group_id.'">'.$Language->getText('project_admin_index','svn_admin').'</A><BR>';
}
if ($group->usesFile()) {
    echo '	<A HREF="/file/admin/?group_id='.$group_id.'">'.$Language->getText('project_admin_index','file_admin').'</A><BR>';
}
if ( $group->usesTracker()) {
    echo '	<A HREF="/tracker/admin/?group_id='.$group_id.'">'.$Language->getText('project_admin_index','tracker_admin').'</A>';
    $atf = new ArtifactTypeFactory($group);
    if (!$group || !is_object($group) || $group->isError()) {
        exit_error($Language->getText('global','error'),'Could Not Get ArtifactTypeFactory');
    }
    
    // Get the artfact type list
    $at_arr = $atf->getArtifactTypes();
    
    if (!$at_arr || count($at_arr) < 1) {
        echo "<br><i>-&nbsp;".$Language->getText('project_admin_index','no_tracker_found').'</i>';
    } else {
        for ($j = 0; $j < count($at_arr); $j++) {
            echo '<br><i>-&nbsp;
			<a href="/tracker/admin/?atid='. $at_arr[$j]->getID() .
                '&group_id='.$group_id.'">' .
                $at_arr[$j]->getName() .' '.$Language->getText('project_admin_index','admin').'</a></i>';
        }
    }

}
// {{{ Plugins
$admin_pages = array();
$params = array('project' => &$group, 'admin_pages' => &$admin_pages);

$em->processEvent('service_admin_pages', $params);

foreach($admin_pages as $admin_page) {
    print '<br />';
    print $admin_page;
}

// }}}

$HTML->box1_bottom(); 




echo '</TD>

	<TD>&nbsp;</TD>

	<TD width=50%>';

/*
	Delegate notifications
*/
$HTML->box1_top($Language->getText('project_admin_index','member_request_delegation_title'));

//Retrieve the saved ugroups for notification from DB
$dar = $pm->getMembershipRequestNotificationUGroup($group_id);
if ($dar && !$dar->isError() && $dar->rowCount() > 0) {
    foreach ($dar as $row) {
        if ($row['ugroup_id'] == $GLOBALS['UGROUP_PROJECT_ADMIN']) {
            $selectedUgroup[] = util_translate_name_ugroup('project_admin');
        } else {
            $selectedUgroup[] = ugroup_get_name_from_id($row['ugroup_id']);
        }
    }
} else {
    $selectedUgroup = array(util_translate_name_ugroup('project_admin'));
}
echo '<b>'.$Language->getText('project_admin_utils','selected_ugroups_title').'</b>';
echo '<ul>';
foreach ($selectedUgroup as $ugroup) {
    echo '<li>'.$ugroup.'</li>';
}
echo '</ul>';
$message = $GLOBALS['Language']->getText('project_admin_index', 'member_request_delegation_msg_to_requester');
$pm = ProjectManager::instance();
$dar = $pm->getMessageToRequesterForAccessProject($group_id);
if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
    $row = $dar->current();
    if ($row['msg_to_requester'] != "member_request_delegation_msg_to_requester" ) {
        $message = $row['msg_to_requester'];
    }
}
echo '<hr size="1" noshade="">';
echo '<b>'.$Language->getText('project_admin_utils','notif_message_title').'</b><br/>';
echo '<p><div class="admin_delegation"><pre>'.$message.'</pre></div></p>';
echo '<hr size="1" noshade="">';
echo '<tr><td colspan="2">';
echo '<p align="center">';
echo '<A HREF="/project/admin/permission_request.php?group_id='.$group_id.'"><b>['.$Language->getText('project_admin_utils','permission_request').']</b></A>';
echo '</p></td></tr>';
echo $HTML->box1_bottom();
?>
</TD>
</TR>
</TABLE>

<?php
project_admin_footer(array());

?>
