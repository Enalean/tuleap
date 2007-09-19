<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX, 2001-2004. All Rights Reserved
// http://codex.xerox.com
//
// 
//
// Originally written by Nicolas Guerin 2004, CodeX Team, Xerox
//

require_once('pre.php');
require_once('www/project/admin/permissions.php');
require_once('www/file/file_utils.php');
require_once('www/docman/doc_utils.php');
require_once('javascript_helpers.php');


$HTML->includeJavascriptFile('/scripts/prototype/prototype.js');
$HTML->includeJavascriptFile('/scripts/scriptaculous/scriptaculous.js');

$Language->loadLanguageMsg('project/project');

function display_name_and_desc_form($ugroup_name,$ugroup_description) {
  global $Language;

    echo '	<table border="0" cellpadding="5">
	<tr valign="top"> 
	  <td><b>'.$Language->getText('project_admin_editugroup','name').'</b>:</td>
	  <td> 
	    <input type="text" name="ugroup_name" value="'.$ugroup_name.'">
	  </td>
	</tr>
        <tr><td colspan=2><i>'.$Language->getText('project_admin_editugroup','avoid_special_ch').'</td></tr>
	<tr valign="top"> 
	  <td><b>'.$Language->getText('project_admin_editugroup','desc').'</b>:</td>
	  <td> 
	  <textarea name="ugroup_description" rows="3" cols="50">'.$ugroup_description.'</textarea>
	  </td>
	</tr>';
}




session_require(array('group'=>$group_id,'admin_flags'=>'A'));


if (browser_is_netscape4()) {
    exit_error($Language->getText('global','error'),$Language->getText('project_admin_editugroup','browser_not_accepted'));
    return;
}

if (!$func) $func='create';


if ($func=='do_create') {
    $ugroup_id=ugroup_create($_POST['group_id'], $_POST['ugroup_name'], $_POST['ugroup_description'], $_POST['group_templates']);
}



if ($func=='create') {
    project_admin_header(array('title'=>$Language->getText('project_admin_editugroup','create_ug'),'group'=>$group_id,
			   'help' => 'UserGroups.html#UGroupCreation'));
    $project=project_get_object($group_id);

    print '<P><h2>'.$Language->getText('project_admin_editugroup','create_ug_for',$project->getPublicName()).'</h2>';
    echo '<p>'.$Language->getText('project_admin_editugroup','fill_ug_desc').'</p>';
    echo '<form method="post" name="form_create" action="/project/admin/editugroup.php?group_id='.$group_id.'">
	<input type="hidden" name="func" value="do_create">
	<input type="hidden" name="group_id" value="'.$group_id.'">';
    display_name_and_desc_form(isset($ugroup_name)?$ugroup_name:'',isset($ugroup_description)?$ugroup_description:'');
    echo '<tr valign="top"> 
	  <td><b>'.$Language->getText('project_admin_editugroup','create_from').'</b>:</td>
	  <td>';
    //<textarea name="ugroup_description" rows="3" cols="50">'.$ugroup_description.'</textarea>
    $group_arr=array();
    $group_arr[]=$Language->getText('project_admin_editugroup','empty_g');
    $group_arr_value[]='cx_empty';
    $group_arr[]='-------------------';
    $group_arr_value[]='cx_empty2';
    $group_arr[]=$Language->getText('project_admin_editugroup','proj_members');
    $group_arr_value[]='cx_members';
    $group_arr[]=$Language->getText('project_admin_editugroup','proj_admins');
    $group_arr_value[]='cx_admins';
    $group_arr[]='-------------------';
    $group_arr_value[]='cx_empty2';
    $res= ugroup_db_get_existing_ugroups($group_id);
    while ($row = db_fetch_array($res)) {
        $group_arr[]=$row['name'];
        $group_arr_value[]=$row['ugroup_id'];
    }
    echo html_build_select_box_from_arrays ($group_arr_value,$group_arr,"group_templates",'cx_empty',false);
     
    echo '</td>
	</tr><tr valign="top"><td><input type="submit" value="'.$Language->getText('project_admin_editugroup','create_ug').'"></tr></td>
        </table>
      </form>';
}


if (($func=='edit')||($func=='do_create')) {
    // Sanity check
    if (!$ugroup_id) { 
        exit_error($Language->getText('global','error'),'The ugroup ID is missing');
    }
    $res=ugroup_db_get_ugroup($ugroup_id);
    if (!$res) {
        exit_error($Language->getText('global','error'),$Language->getText('project_admin_editugroup','ug_not_found',array($ugroup_id,db_error())));
    }
    if (!isset($ugroup_name) || !$ugroup_name) { $ugroup_name=db_result($res,0,'name'); }
    if (!isset($ugroup_description) || !$ugroup_description) { $ugroup_description=db_result($res,0,'description'); }
    else {
        $ugroup_description = stripslashes($ugroup_description);
    }

    project_admin_header(array('title'=>$Language->getText('project_admin_editugroup','edit_ug'),'group'=>$group_id,
			   'help' => 'UserGroups.html#UGroupCreation'));
    print '<P><h2>'.$Language->getText('project_admin_editugroup','ug_admin',$ugroup_name).'</h2>';
    echo '<p>'.$Language->getText('project_admin_editugroup','upd_ug_name').'</p>';
    echo '<form method="post" name="form_create" action="/project/admin/ugroup.php?group_id='.$group_id.'">
	<input type="hidden" name="func" value="do_update">
	<input type="hidden" name="group_id" value="'.$group_id.'">
	<input type="hidden" name="ugroup_id" value="'.$ugroup_id.'">';
    display_name_and_desc_form($ugroup_name,$ugroup_description);
	
    // Get existing members from group
    $sql="SELECT user.user_id, user.user_name FROM ugroup_user INNER JOIN user USING (user_id) WHERE ugroup_id=$ugroup_id";
    $res = db_query($sql);
    $user_in_group = array();
    if (db_numrows($res)>0) {
        while ($row = db_fetch_array($res)) {
            $user_in_group[$row['user_id']]=$row['user_name'];
        }
    }

    echo '
    <TR valign="top">
        <td>
            <b>'.$Language->getText('project_admin_editugroup','select_ug_members').'</b>
        </td>
        <td>
            <style>
            #ugroup_members_empty {
                font-style:italic;
                text-align:center;
                padding:10px 2px;
            }
            #ugroup_members_add_panel {
            }
            #ugroup_members_add_panel_field {
                visibility:hidden;
            }
            .ugroup_members_member {
                padding:2px 1px;
            }
            </style>
            <table>
                <tr>
                    <td>';
                    $trash = $HTML->getImage('ic/trash.png');
                    echo '<fieldset><legend>Members</legend><div id="ugroup_members">';
                    $attr_if_not_empty = count($user_in_group) ? 'style="display:none;"' : '';
                    echo '<div id="ugroup_members_empty" '. $attr_if_not_empty .'>'. 'This group is empty.' .'</div>';
                    foreach($user_in_group as $user_id => $user_name) {
                        echo '<div class="ugroup_members_member"><table width="100%" cellpadding="0" cellspacing="0"><tr class="boxitem"><td>'. $user_name .'<input type="hidden" name="PickList[]" value="'. $user_name .'" /></td><td align="right"><a href="#remove" onclick="this.parentNode.parentNode.parentNode.parentNode.parentNode.remove(); if ($(\'ugroup_members\').childNodes.length == 1) {var empty = $(\'ugroup_members_empty\'); if (empty) { empty.show(); }} return false;">'. $trash .'</a></td></tr></table></div>';
                    }
                    echo '</div></fieldset>';
                    echo '<div id="ugroup_members_add_panel">';
                    echo '<div id="ugroup_members_add_panel_button"><a href="add_user_to_ugroup.php" onclick="Element.hide(this); $(\'ugroup_members_add_panel_field\').style.visibility = \'visible\'; $(\'ugroup_members_add_field\').focus(); return false;">Add a user</a></div>';
                    echo '<div id="ugroup_members_add_panel_field">';
                    echo 'Type the user to add: '. '<input type="text" class="textfield_medium" id="ugroup_members_add_field" />';
                    autocomplete_for_users('ugroup_members_add_field', 'ugroup_members_add_autocomplete', array(
                        'afterUpdateElement' => <<<EOS
var empty = $('ugroup_members_empty');
if (empty && empty.visible()) {
    empty.hide();
}
var t = new Template('<div class="ugroup_members_member"><table width="100%" cellpadding="0" cellspacing="0"><tr class="boxitemalt"><td>#{name}<input type="hidden" name="PickList[]" value="#{name}" /></td><td align="right"><a href="#remove" onclick="this.parentNode.parentNode.parentNode.parentNode.parentNode.remove(); if ($(\'ugroup_members\').childNodes.length == 1) {var empty = $(\'ugroup_members_empty\'); if (empty) { empty.show(); }} return false;">$trash</a></td></tr></table></div>');
new Insertion.Bottom('ugroup_members', t.evaluate({name: element.value}));
element.value = '';
$('ugroup_members').cleanWhitespace();
console.log(selectedElement);
EOS
                    ));
                    echo '</div><br />';
                    echo '<div id="ugroup_members_all">';
                    link_to_remote('List all members', 'list_all_users.php?group_id='.$group_id, array('update' => 'ugroup_members_all'));
                    echo ' (may take some times to load)';
                    echo '</div>';
                    echo '</div>';
                    echo '
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td colspan="2" style="text-align:center;">
            <br />
            <INPUT TYPE="submit" VALUE="'.$Language->getText('global','btn_submit').'">
        </td>
    </tr>
</TABLE>
</FORM>
';

    // Display associated permissions
    $sql="SELECT * FROM permissions WHERE ugroup_id=$ugroup_id ORDER BY permission_type";
    $res=db_query($sql);
    if (db_numrows($res)>0) {
        echo '
<hr><p><b>'.$Language->getText('project_admin_editugroup','ug_perms').'</b>
<p>';
        
        $title_arr=array();
        $title_arr[]=$Language->getText('project_admin_editugroup','permission');
        $title_arr[]=$Language->getText('project_admin_editugroup','resource_name');
        echo html_build_list_table_top($title_arr,false,false,false);
        $row_num=0;
        
        while ($row = db_fetch_array($res)) {
            if (strpos($row['permission_type'], 'TRACKER_FIELD') === 0) {
                $atid =permission_extract_atid($row['object_id']);
                if (isset($tracker_field_displayed[$atid])) continue;
                $objname=permission_get_object_name('TRACKER_ACCESS_FULL',$atid);
            } else {
                $objname=permission_get_object_name($row['permission_type'],$row['object_id']);
            }
            echo '<TR class="'. util_get_alt_row_color($row_num) .'">';
            echo '<TD>'.permission_get_name($row['permission_type']).'</TD>';
            if ($row['permission_type'] == 'PACKAGE_READ') {
                echo '<TD>'.$Language->getText('project_admin_editugroup','package')
                    .' <a href="/file/admin/editpackagepermissions.php?package_id='
                    .$row['object_id'].'&group_id='.$group_id.'">'
                    .$objname.'</a></TD>';
            } else if ($row['permission_type'] == 'RELEASE_READ') {
                $package_id=file_get_package_id_from_release_id($row['object_id']);
                echo '<TD>'.$Language->getText('project_admin_editugroup','release')
                    .' <a href="/file/admin/editreleasepermissions.php?release_id='.$row['object_id'].'&group_id='.$group_id.'&package_id='.$package_id.'">'
                    .file_get_release_name_from_id($row['object_id']).'</a> ('
                    .$Language->getText('project_admin_editugroup','from_package')
                    .' <a href="/file/admin/editreleases.php?package_id='.$package_id.'&group_id='.$group_id.'">'
                    .$objname.'</a></TD>';
            } else if ($row['permission_type'] == 'DOCUMENT_READ') {
                echo '<TD>'.$Language->getText('project_admin_editugroup','document')
                    .' <a href="/docman/admin/editdocpermissions.php?docid='.$row['object_id'].'&group_id='.$group_id.'">'
                    .$objname.'</a></TD>';
            } else if ($row['permission_type'] == 'DOCGROUP_READ') {
                echo '<TD>'.$Language->getText('project_admin_editugroup','document_group')
                    .' <a href="/docman/admin/editdocgrouppermissions.php?doc_group='.$row['object_id'].'&group_id='.$group_id.'">'
                    .$objname.'</a></TD>';
            } else if ($row['permission_type'] == 'WIKI_READ') {
                echo '<TD>'.$Language->getText('project_admin_editugroup','wiki')
                    .' <a href="/wiki/admin/index.php?view=wikiPerms&group_id='.$group_id.'">'
                    .$objname.'</a></TD>';
            } else if ($row['permission_type'] == 'WIKIPAGE_READ') {
                echo '<TD>'.$Language->getText('project_admin_editugroup','wiki_page')
                    .' <a href="/wiki/admin/index.php?group_id='.$group_id.'&view=pagePerms&id='.$row['object_id'].'">'
                    .$objname.'</a></TD>';
            } else if (strpos($row['permission_type'], 'TRACKER_ACCESS') === 0) {
                echo '<TD>'.$Language->getText('project_admin_editugroup','tracker') 
                    .' <a href="/tracker/admin/?func=permissions&perm_type=tracker&group_id='.$group_id.'&atid='.$row['object_id'].'">'
                    .$objname.'</a></TD>';
            } else if (strpos($row['permission_type'], 'TRACKER_FIELD') === 0) {
                $tracker_field_displayed[$atid]=1;
                $atid =permission_extract_atid($row['object_id']);
                echo '<TD>'.$Language->getText('project_admin_editugroup','tracker_field')
                    .' <a href="/tracker/admin/?group_id='.$group_id.'&atid='.$atid.'&func=permissions&perm_type=fields&group_first=1&selected_id='.$ugroup_id.'">' 
                    .$objname.'</a></TD>';
            } else {
                $results = false;
                $em =& EventManager::instance();
                $em->processEvent('permissions_for_ugroup', array(
                    'permission_type' => $row['permission_type'],
                    'object_id'       => $row['object_id'],
                    'group_id'        => $group_id,
                    'results'         => &$results
                ));
                if ($results) {
                    echo '<TD>'.$results.'</TD>';
                } else {
                    echo '<TD>'.$row['object_id'].'</TD>';
                }
            }

            echo '</TR>
';
            $row_num++;
        }
        echo '</table><p>';
    }


}


$HTML->footer(array());



?>
