<?php
//Copyright © STMicroelectronics, 2006. All Rights Reserved.
//
//Originally written by Dave Kibble, 2006.
//
//This file is a part of CodeX.
//
//CodeX is free software; you can redistribute it and/or modify
//it under the terms of the GNU General Public License as published by
//the Free Software Foundation; either version 2 of the License, or
//(at your option) any later version.
//
//CodeX is distributed in the hope that it will be useful,
//but WITHOUT ANY WARRANTY; without even the implied warranty of
//MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
//GNU General Public License for more details.
//
//You should have received a copy of the GNU General Public License
//along with CodeX; if not, write to the Free Software
//Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
//
require_once('pre.php');
require_once('vars.php');
require_once('www/project/admin/project_admin_utils.php');
require_once('pfamily.php');
require_once('form_utils.php');

$Language->loadLanguageMsg('project/project');
$group_id = (int) $_REQUEST['group_id'];

// get current information
$res_grp = group_get_result($group_id);
if (db_numrows($res_grp) < 1) {
  exit_error($Language->getText('project_admin_index','invalid_p'),$Language->getText('project_admin_index','p_not_found'));
}

//if the project isn't active, require you to be a member of the super-admin group
if (!(db_result($res_grp,0,'status') == 'A')) {
    session_require(array('group'=>1));
}

//must be a project admin
session_require(array('group'=>$group_id,'admin_flags'=>'A'));

//
//  get the Group object
//
$group = group_get_object($group_id);
if (!$group || !is_object($group) || $group->isError()) {
  exit_no_group();
}

if (isset($_REQUEST['func'])) { // updating the database?
    $func = $_REQUEST['func'];
    if (! pf_ActionHandler($group_id, $func)) {
        exit_error("unknown action: ".$func, "");    //should not occur (no translation required)
    }
}
project_admin_header(array('title'=>$Language->getText('project_admin_servicebar','edit_s_bar'),'group'=>$group_id, 'help' => 'ServiceConfiguration.html'));
if (isset($_REQUEST['disp'])) {
    $disp = $_REQUEST['disp'];
    switch ($disp) {
        case PF_ADMIN_LINK_SHOW:
            if (isset($_REQUEST['link_id'])) {
                $link_id = (int) $_REQUEST['link_id'];
            } else {
                $link_id = NULL;
            }
            pf_adminPage_updateLink($group_id, $target_group_id, $link_id);
            break;
        case PF_ADMIN_TYPE_SHOW:
            if (isset($_REQUEST['link_type_id'])) {
                $link_type_id = (int) $_REQUEST['link_type_id'];
            } else {
                $link_type_id = NULL;
            }
            $link_type_id = (int) $_REQUEST['link_type_id'];
            pf_adminPage_linkTypeUpdate($group_id, $link_type_id);
            break;
    }
} else {
    pf_adminPage_default($group_id, $group);
}
project_admin_footer(array());

//=============================================================================
//=============================================================================
//=============================================================================

//=============================================================================
function pf_adminPage_default($group_id, $group)
{
    // show the default configuration page
    global $HTML, $Language;

    print "<TABLE width='100%' cellpadding='2' cellspacing='2' border='0'>
        <TR valign='top'><TD width='70%'>";
    // admin set-up
    $HTML->box1_top($Language->getText('plugin_pfamily', 'project_setup'));
    //project families: allow the admin user to enable linking to other projects
    form_Start();
    form_HiddenParams(array("func" => PF_ADMIN_CONFIG_UPDATE, "group_id" => $group_id));
    form_SectionStart(pf_getImg_mainIcon()." ".$Language->getText('plugin_pfamily','project_families'));
    form_SectionStart();
    form_genCheckbox("EnableProjectLink", $Language->getText('plugin_pfamily','link_enable'), "Y", ((user_get_preference("ProjectFamilies_GroupId_master") == $group_id)?"Y":""), SUBMIT_ON_CHANGE);
    form_text($Language->getText('plugin_pfamily','link_enable_explanation', pf_get_img_add_link()));
    form_Text($Language->getText('plugin_pfamily', 'note_personal_settings'));
    form_End(FORM_NO_SUBMIT_BUTTON, FORM_NO_RESET_BUTTON);  // this form is submitted when the checkbox is clicked
    $HTML->box1_bottom();

    // link types
    $HTML->box1_top($Language->getText('plugin_pfamily', 'link_types').
        " &nbsp; &nbsp; &nbsp; &nbsp; ".mkAH($Language->getText('plugin_pfamily', 'create_type'), "/project/admin/pfamilyadmin.php?disp=".PF_ADMIN_TYPE_SHOW."&group_id=$group_id"));
    $db_res = pf_getLinks($group_id);
    print "<TABLE width='100%' cellpadding='2' cellspacing='2' border='0'>
        <TR valign='top'>";
    print "<th style='text-align: left;'>".htmlentities($Language->getText('plugin_pfamily', 'dbfn_name'))."</th>
            <th style='text-align: left;'>".htmlentities($Language->getText('plugin_pfamily', 'dbfn_reverse_name'))."</th>
            <th style='text-align: left;'>".htmlentities($Language->getText('plugin_pfamily', 'dbfn_description'))."</th>
            <th style='text-align: left;'>".htmlentities($Language->getText('plugin_pfamily', 'dbfn_uri_plus'))."</th>
        ";
    while ($row = db_fetch_array($db_res)) {
        print "<TR>
            <td style='white-space: nowrap; vertical-align: top;'>".mkAH(htmlentities($row['name']), "/project/admin/pfamilyadmin.php?disp=".PF_ADMIN_TYPE_SHOW."&group_id=".$row["group_id"]."&link_type_id=".$row["link_type_id"], $Language->getText('plugin_pfamily', 'tooltip_update')).
            "</td>
            <td style='white-space: nowrap; vertical-align: top;'>".htmlentities($row['reverse_name'])."</td>
            <td style='vertical-align: top;'>".htmlentities($row['description'])."</td>
            <td style='vertical-align: top;'>".htmlentities($row['uri_plus'])."</td>
            <td style='vertical-align: top;'>".mkAH(pf_getImg_trash(), "/project/admin/pfamilyadmin.php?func=".PF_ADMIN_TYPE_DELETE."&group_id=".htmlentities($group_id)."&link_type_id=".$row["link_type_id"],
                $Language->getText('plugin_pfamily', 'delete_type'), @array('onclick'=>"return confirm('".$Language->getText('plugin_pfamily', 'delete_type')."?')"))."
            </td>
            </TR>";
    }
    print "</TABLE>";
    $HTML->box1_bottom();

    if ($group->getTemplate() > 100) {
        // project was built from a proper template - don't support re-sync with site template (yet?)
        form_Start();
        form_hiddenParams(array("disp"=>PROJECT_FAMILY_ADMIN_TEMPLATE_SYNC, "group_id"=>$group_id, "template_id"=>$group->getTemplate()));
        form_End($Language->getText('plugin_pfamily', 'synchronise_with_template'), FORM_NO_RESET_BUTTON);
    }
    print "
    </TD>&nbsp;</TD><TD width='30%'>";

    // project family links
    pf_showLinks($group_id, TRUE);

    print "</TABLE> <HR NoShade SIZE='1'>";
}

//=============================================================================
function pf_adminPage_updateLink($group_id, $target_group_id, $link_id = NULL)
{
    // $link_id NULL to create a new one
    global $HTML, $Language;

    if (is_null($link_id)) {
        // create new link
        if (!(is_numeric($group_id) && is_numeric($target_group_id))) {
            exit_error("invalid data", "10"); // unexpected error - no translation reqd.
        }
        $pfLinks = db_query("SELECT group_id FROM groups
                        WHERE (group_id=$group_id) OR (group_id=$target_group_id);");
        if (db_numrows($pfLinks) <> 2) {
            exit_error("FATAL ERROR - at least one of the projects is invalid!", "");    //should not occur (no translation required)  - somone messing with the URI
        }
        $def_link_type_id = "";
        $creation_date = time();
    } else {
        // update existing link
        if (!(is_numeric($link_id) && is_numeric($group_id) && is_numeric($target_group_id))) {
            exit_error("invalid data", "1.1"); // unexpected error - no translation reqd.
        }
        $db_res = db_query("SELECT link_type_id, target_group_id, creation_date
                            FROM plugin_related_project_relationship
                            WHERE ((master_group_id = $group_id) AND (target_group_id = $target_group_id) AND (link_id = $link_id));");
        if (db_numrows($db_res) <> 1) {
            exit_error("invalid data", "1.2"); // unexpected error - no translation reqd.
        }
        $row = db_fetch_array($db_res);
        $def_link_type_id = $row["link_type_id"];
        $creation_date = $row["creation_date"];
    }
    $pfLinks = pf_getLinks($group_id); // check if project already has project link types - otherwise create the defaults
    print "<TABLE width='100%' cellpadding='3' cellspacing='0' border='0'>
        <TR valign='top'><TD width='50%'>";
    $HTML->box1_top($Language->getText('plugin_pfamily','project_families')." ".pf_getImg_mainIcon()." ".$Language->getText('plugin_pfamily','link_update_head', array(group_getname($group_id), group_getname($target_group_id))));
    print mkAH("[".$Language->getText('global', 'btn_cancel')."]", "/project/admin/pfamilyadmin.php?group_id=$group_id");
    print "<hr>\n";
    print "<TABLE width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
    print "<tr><td>\n";
    form_start();
    form_hiddenParams(array(
        "func" => PF_ADMIN_LINK_UPDATE,
        "group_id" => $group_id,
        "target_group_id" => $target_group_id));
    if (isset($link_id)) {
        form_HiddenParams(array("link_id" => $link_id));
    }
    form_genShowBox($Language->getText('plugin_pfamily','dbfn_master_group_id'), group_getname($group_id));
    form_NewRow();
    form_genShowBox($Language->getText('plugin_pfamily','dbfn_target_group_id'), group_getname($target_group_id));
    form_NewRow();
    if (isset($link_id)) {
        form_genShowBox($Language->getText('plugin_pfamily','dbfn_creation_date'), util_timestamp_to_userdateformat($creation_date));
        form_NewRow();
    }
    form_genSelectBoxFromSQL("link_type_id", $Language->getText('plugin_pfamily', 'dbfn_link_type_id'),
                "SELECT link_type_id, name
                FROM plugin_related_project_link_type
                WHERE (group_id=$group_id)
                ORDER BY (name);", $def_link_type_id);
    form_End();
    print "</td><td>\n";

    // display a table of the link types
    print "<TABLE cellpadding='3' cellspacing='0' border='1'>
        <tr><th colspan='2'>".$Language->getText('plugin_pfamily', 'link_types')."</th></tr>
            ";
    while ($row_pfLinks = db_fetch_array($pfLinks)) {
        print "<tr><td style='white-space: nowrap; vertical-align:top;'>".htmlentities($row_pfLinks['name'])."</td>
            <td style='vertical-align:top;'>".nz(htmlentities($row_pfLinks['description']), "&nbsp;")."</td>
            </tr>";
    }
    print "</TABLE>\n";

    print "</td></tr></TABLE>\n";
    print $HTML->box1_bottom()."
        </TD>
        </TR>
        </TABLE>
        ";
}

//=============================================================================
function pf_adminPage_linkTypeUpdate($group_id, $link_type_id)
{
    global $HTML, $Language;

    if (!(is_numeric($group_id))) {
        exit_error("invalid data", "2.1"); // unexpected error - no translation reqd.
    }
    if (isset($link_type_id)) {
        if (!(is_numeric($link_type_id))) {
            exit_error("invalid data", "2.1"); // unexpected error - no translation reqd.
        }
        $db_res = db_query("SELECT link_type_id, group_id, name, reverse_name, description, uri_plus
                            FROM plugin_related_project_link_type
                            WHERE ((group_id = $group_id) AND (link_type_id = $link_type_id));");
        if (db_numrows($db_res) <> 1) {
            exit_error("invalid data", "2.2"); // unexpected error - no translation reqd.
        }
        $row = db_fetch_array($db_res);
        $def = array(
                'name' => htmlentities($row['name']),
                'reverse_name' => htmlentities($row['reverse_name']),
                'description' => htmlentities($row['description']),
                'uri_plus' => htmlentities($row['uri_plus'])
            );
    } else {
        $def = array(
                'name' => "",
                'reverse_name' => "",
                'description' => "",
                'uri_plus' => PF_DEFAULT_PROJECT_LINK
            );
    }
    $HTML->box1_top($Language->getText('plugin_pfamily','project_families')." ".pf_getImg_mainIcon()." ".$Language->getText('plugin_pfamily', 'link_type_update'));
    print mkAH("[".$Language->getText('global', 'btn_cancel')."]", "/project/admin/pfamilyadmin.php?group_id=$group_id");
    print "<hr>\n";
    form_Start("");
    form_HiddenParams(array(
        "func" => PF_ADMIN_TYPE_UPDATE,
        "group_id" => $group_id));
    if (isset($link_type_id)) {
        form_HiddenParams(array("link_type_id" => $link_type_id));
    }
    form_GenTextBox("name", htmlentities($Language->getText('plugin_pfamily', 'dbfn_name')), $def['name'], 20);
    form_Validation("name", FORM_VAL_IS_NOT_ZERO_LENGTH);
    form_NewRow();
    form_GenTextBox("reverse_name", htmlentities($Language->getText('plugin_pfamily', 'dbfn_reverse_name')), $def['reverse_name'], 20);
    form_NewRow();
    form_GenTextArea("description", htmlentities($Language->getText('plugin_pfamily', 'dbfn_description')), $def['description']);
    form_NewRow();
    form_GenTextBox("uri_plus", htmlentities($Language->getText('plugin_pfamily', 'dbfn_uri_plus')), $def['uri_plus'], 130);
    form_genJSButton($Language->getText('plugin_pfamily', 'set_to_default'), form_JS_ElementRef("uri_plus").".value='".PF_DEFAULT_PROJECT_LINK."'");
    form_Validation("uri_plus", FORM_VAL_IS_NOT_ZERO_LENGTH);
    form_End();
    $HTML->box1_bottom();
}

//======================================================================================================
function pfAdminPage_syncTemplate($group_id, $template_id)
{
    // re-synchronise proejct family types with originating template
    global $HTML, $Language;
    
    if (!(is_numeric($group_id))) {
        exit_error("invalid data", "2.1"); // unexpected error - no translation reqd.
    }
    $HTML->box1_top($Language->getText('plugin_pfamily','project_families')." ".pf_get_img_main_icon()." ".$Language->getText('plugin_pfamily', 'synchronise_with_template'));
    print MkAH("[".$Language->getText('global', 'btn_cancel')."]", "/project/admin/pfamilyadmin.php?group_id=$group_id");
    print "<hr>\n";
    print "<h2>".$Language->getText('plugin_pfamily','sync_type')."</h2>\n";
    $lt_tmplt = db_query("SELECT link_type_id, group_id, name, reverse_name, description, uri_plus
                        FROM plugin_related_project_link_type
                        WHERE (group_id = $template_id);");
    print "<TABLE width='100%' cellpadding='2' cellspacing='2' border='0'>";
    $typeMatch = array();
    while ($lt_t_row = db_fetch_array($lt_tmplt)) {
        print "<tr style=' vertical-align: top;'>\n";
        $diffs = array();
        $rs_grp = db_query("SELECT link_type_id, name, reverse_name, description, uri_plus FROM plugin_related_project_link_type
                WHERE ((group_id = $group_id) AND (name = ".DataAccess::quoteSmart($lt_t_row['name'])."));");
        $basicURI = "/project/admin/pfamilyadmin.php?disp=".PROJECT_FAMILY_ADMIN_TEMPLATE_SYNC."&group_id=$group_id&template_id=$template_id&template_type_id={$lt_t_row['link_type_id']}";
        if (db_numrows($rs_grp) == 0) {
            // does not exist
            print "<td><b>".MkAH($Language->getText('plugin_pfamily','sync_link_add'), $basicURI."&func=".PROJECT_FAMILY_ADMIN_TEMPLATE_SYNC_TYPE_ADD)."</b></td>";
        } else {
            // same name - any differences?
            $lt_p_row = db_fetch_array($rs_grp);
            $basicURI .= "&link_type_id={$lt_p_row['link_type_id']}";
            $typeMatch[$lt_t_row['link_type_id']] = $lt_p_row['link_type_id'];
            foreach (array('reverse_name', 'description', 'uri_plus') as $param) {
                if ($lt_t_row[$param] <> $lt_p_row[$param]) {
                    $diffs[$param] = $lt_p_row[$param];
                }
            }
            if (count($diffs) > 0) {
                print "<td><b>".MkAH($Language->getText('plugin_pfamily','sync_link_update'), $basicURI."&func=".PROJECT_FAMILY_ADMIN_TEMPLATE_SYNC_TYPE_UPDATE)."</b></td>";
            } else {
                print "<td>&nbsp;</td>";
            }
        }
        foreach (array('name', 'reverse_name', 'description', 'uri_plus') as $param) {
            $style = "";
            if ($param <> 'description') {
                $style .= "white-space: nowrap;";
            }
            if (isset($diffs[$param])) {
                $style .= " font-weight: bold;";
            }
            if (isset($diffs[$param])) {
                print "<td style='text-align: right;'>".$Language->getText('plugin_pfamily','sync_link_template').":
                    <br><i>".$Language->getText('plugin_pfamily','sync_link_project').":</td>
                    <td style='$style'>".htmlentities($lt_t_row[$param])."
                    <br><i>".htmlentities($diffs[$param])."</i>";
            } else {
                print "<td style='$style' colspan='2'>";
                print htmlentities($lt_t_row[$param]);
            }
            print "</td>";
        }
        print "</tr>\n";
    }

    //Additional links to other projects
    $pfLinks = db_query("SELECT plugin_related_project_relationship.link_type_id, name AS link_name, type, groups.group_id, group_name, unix_group_name, uri_plus, link_id, creation_date, master_group_id, target_group_id
                FROM plugin_related_project_relationship,plugin_related_project_link_type,groups
                WHERE (plugin_related_project_relationship.link_type_id = plugin_related_project_link_type.link_type_id)
                    AND (plugin_related_project_relationship.target_group_id = groups.group_id)
                    AND ((master_group_id = $template_id) AND (status = 'A'))
                ORDER BY name, type, group_name;");
    if (db_numrows($pfLinks) > 0) {
        form_Start("");
        form_HiddenParams(array(
            "func" => PROJECT_FAMILY_ADMIN_TEMPLATE_SYNC_UPDATE,
            "group_id" => $group_id));
        form_SectionStart();
        form_text($Language->getText('plugin_pfamily','synchronise_checkit'));
        form_SectionStart($Language->getText('plugin_pfamily','sync_link_new'));
        while ($row_pfLinks = db_fetch_array($pfLinks)) {
            // is there a matching link in the project?
            $matchedType = isset($typeMatch[$row_pfLinks['link_type_id']]);
            $matchedLink = false;
            if ($matchedType) {
                // we found a matching type
                $findlinks = db_query("SELECT creation_date
                    FROM plugin_related_project_relationship
                    WHERE ((master_group_id=$group_id)
                        AND (target_group_id={$row_pfLinks['target_group_id']})
                        AND (link_type_id={$typeMatch[$row_pfLinks['link_type_id']]})
                    );");
                $matchedLink = (db_numrows($findlinks) > 0);
            }
            if (! $matchedType) {
                form_Text("(".$Language->getText('plugin_pfamily','sync_link_needs_type').")");
            } elseif (! $matchedLink) {
                form_genCheckBox("link_id", "", $row_pfLinks['link_id']);
            } else {
                form_Text("(".$Language->getText('plugin_pfamily','sync_link_matched').")");
            }
            print "<td>".htmlentities($row_pfLinks['link_name']).":</td>";
            print "<td>".htmlentities($row_pfLinks['group_name'])."</td>";
            form_NewRow();
        }
        form_SectionEnd();
        form_End();
    }
    $HTML->box1_bottom();
}
?>