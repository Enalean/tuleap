<?php
require_once('pre.php');
require_once('vars.php');
require_once('www/project/admin/project_admin_utils.php');
require_once('pfamily.php');
require_once('form_utils.php');

$Language->loadLanguageMsg('project/project');

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

if (isset($func)) { // updating the database?
    if (! ProjectFamilyActionHandler($group_id, $func)) {
        exit_error("unknown action: ".$func, "");    //should not occur (no translation required)
    }
}

project_admin_header(array('title'=>$Language->getText('project_admin_servicebar','edit_s_bar'),'group'=>$group_id, 'help' => 'ServiceConfiguration.html'));
if (isset($disp)) {
    switch ($disp) {
        case PROJECT_FAMILY_ADMIN_LINK_SHOW:
            pfAdminPage_updateLink($group_id, $target_group_id, isset($link_id)?$link_id:NULL);
            break;
        case PROJECT_FAMILY_ADMIN_TYPE_SHOW:
            pfAdminPage_linkTypeUpdate($group_id, $link_type_id);
            break;
    }
} else {
    pfAdminPage_default($group_id);
}
project_admin_footer(array());

//======================================================================================================
//======================================================================================================
//======================================================================================================

//======================================================================================================
function pfAdminPage_default($group_id)
{
    // show the default configuration page
    global $HTML, $Language;

    if (!(is_numeric($group_id))) {
        exit_error("invalid data", "4"); // unexpected error - no translation reqd.
    }
    echo '<TABLE width=100% cellpadding=2 cellspacing=2 border=0>
        <TR valign="top"><TD width=70%>';
    // admin set-up
    $HTML->box1_top($Language->getText('plugin_pfamily', 'project_setup'));
    //project families: allow the admin user to enable linking to other projects
    form_Start();
    form_HiddenParams(array("func" => PROJECT_FAMILY_ADMIN_CONFIG_UPDATE, "group_id" => $group_id));
    form_SectionStart(pf_get_img_main_icon()." ".$Language->getText('plugin_pfamily','project_families'));
    form_SectionStart();
    form_genCheckbox("EnableProjectLink", $Language->getText('plugin_pfamily','link_enable'), "Y", ((user_get_preference("ProjectFamilies_GroupId_master") == $group_id)?"Y":""), SUBMIT_ON_CHANGE);
    form_text($Language->getText('plugin_pfamily','link_enable_explanation', pf_get_img_link()));
    form_Text($Language->getText('plugin_pfamily', 'note_personal_settings'));
    form_End(FORM_NO_SUBMIT_BUTTON);
    $HTML->box1_bottom();

    // link types
    $HTML->box1_top($Language->getText('plugin_pfamily', 'link_types').
        " &nbsp; &nbsp; &nbsp; &nbsp; ".MkAH($Language->getText('plugin_pfamily', 'create_type'), "/project/admin/pfamilyadmin.php?disp=".PROJECT_FAMILY_ADMIN_TYPE_SHOW."&group_id=$group_id"));
    $db_res = pfamily_get_links($group_id);
    echo '<TABLE width=100% cellpadding=2 cellspacing=2 border=0>
        <TR valign="top">';
    print "<th style='text-align: left;'>".htmlentities($Language->getText('plugin_pfamily', 'dbfn_name'))."</th>
            <th style='text-align: left;'>".htmlentities($Language->getText('plugin_pfamily', 'dbfn_reverse_name'))."</th>
            <th style='text-align: left;'>".htmlentities($Language->getText('plugin_pfamily', 'dbfn_description'))."</th>
            <th style='text-align: left;'>".htmlentities($Language->getText('plugin_pfamily', 'dbfn_uri_plus'))."</th>
        ";
    while ($row = db_fetch_array($db_res)) {
        echo "<TR>
            <td style='white-space: nowrap; vertical-align: top;'>".MkAH(htmlentities($row['name']), "/project/admin/pfamilyadmin.php?disp=".PROJECT_FAMILY_ADMIN_TYPE_SHOW."&group_id=".$row["group_id"]."&link_type_id=".$row["link_type_id"], $Language->getText('plugin_pfamily', 'tooltip_update')).
            "</td>
            <td style='white-space: nowrap; vertical-align: top;'>".htmlentities($row['reverse_name'])."</td>
            <td style='vertical-align: top;'>".htmlentities($row['description'])."</td>
            <td style='vertical-align: top;'>".htmlentities($row['uri_plus'])."</td>
            <td style='vertical-align: top;'>".MkAH(pf_get_img_trash(), "/project/admin/pfamilyadmin.php?func=".PROJECT_FAMILY_ADMIN_TYPE_DELETE."&group_id=".htmlentities($group_id)."&link_type_id=".$row["link_type_id"],
                $Language->getText('plugin_pfamily', 'delete_type'), @array('onclick'=>"return confirm('".$Language->getText('plugin_pfamily', 'delete_type')."?')"))."
            </td>
            </TR>";
    }
    echo "</TABLE>";
    $HTML->box1_bottom();

    echo "
    </TD>&nbsp;</TD><TD width='30%'>";

    // project family links
    showProjectFamilylinks($group_id, TRUE);

    print '</TABLE> <HR NoShade SIZE="1">';
}

//======================================================================================================
function pfAdminPage_updateLink($group_id, $target_group_id, $link_id = NULL)
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
    $pfLinks = pfamily_get_links($group_id); // check if project already has project link types - otherwise create the defaults
    echo '<TABLE width=100% cellpadding="3" cellspacing="0" border="0">
        <TR valign=top><TD width=50%>';
    $HTML->box1_top(pf_get_img_link()." ".$Language->getText('plugin_pfamily','link_update_head', array(group_getname($group_id), group_getname($target_group_id))));
    print MkAH("[".$Language->getText('global', 'btn_cancel')."]", "/project/admin/pfamilyadmin.php?group_id=$group_id");
    print "<hr>\n";
    print "<TABLE width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
    print "<tr><td>\n";
    form_start();
    form_hiddenParams(array(
        "func" => PROJECT_FAMILY_ADMIN_LINK_UPDATE,
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
    print '<TABLE cellpadding="3" cellspacing="0" border="1">
        <tr><th colspan="2">'.$Language->getText('plugin_pfamily', 'link_types').'</th></tr>
            ';
    while ($row_pfLinks = db_fetch_array($pfLinks)) {
        print '<tr><td>'.htmlentities($row_pfLinks['name']).'</td>
            <td>'.Nz(htmlentities($row_pfLinks['description']), "&nbsp;").'</td>
            </tr>';
    }
    print "</TABLE>\n";

    print "</td></tr></TABLE>\n";
    echo $HTML->box1_bottom().'
        </TD>
        </TR>
        </TABLE>
        ';
}

//======================================================================================================
function pfAdminPage_linkTypeUpdate($group_id, $link_type_id)
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
    $HTML->box1_top($Language->getText('plugin_pfamily', 'link_type_update'));
    form_Start("");
    form_HiddenParams(array(
        "func" => PROJECT_FAMILY_ADMIN_TYPE_UPDATE,
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
?>