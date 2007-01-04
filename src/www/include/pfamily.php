<?php
require_once('twistie.php');
require_once('form_utils.php');
$Language->loadLanguageMsg('pfamily', 'pfamily');

// database update services ($func)
define("PROJECT_FAMILY_ADMIN_CONFIG_UPDATE", "project_family_admin_config_update");
define("PROJECT_FAMILY_ADMIN_LINK_DELETE", "project_family_admin_link_delete");
define("PROJECT_FAMILY_ADMIN_TYPE_DELETE", "project_family_admin_type_delete");
define("PROJECT_FAMILY_ADMIN_LINK_UPDATE", "project_family_admin_link_update");
define("PROJECT_FAMILY_ADMIN_TYPE_UPDATE", "project_family_admin_type_update");
define("PROJECT_FAMILY_ADMIN_TEMPLATE_SYNC_UPDATE", "project_family_admin_template_sync_update");
define("PROJECT_FAMILY_ADMIN_TEMPLATE_SYNC_TYPE_ADD", "project_family_admin_template_sync_type_add");
define("PROJECT_FAMILY_ADMIN_TEMPLATE_SYNC_TYPE_UPDATE", "project_family_admin_template_sync_type_update");

// forms for user to inspect/update data
define("PROJECT_FAMILY_ADMIN_LINK_SHOW", "project_family_admin_link_show");
define("PROJECT_FAMILY_ADMIN_TYPE_SHOW", "project_family_admin_type_show");
define("PROJECT_FAMILY_ADMIN_TEMPLATE_SYNC", "project_family_admin_template_sync");

// default values for linking uri
define("PF_DEFAULT_PROJECT_LINK", '/projects/$projname/');
define("PF_DEFAULT_DOCMAN_LINK", '/plugins/docman/?group_id=$group_id');

//======================================================================================================
function ProjectFamilyActionHandler($group_id, $func)
{
    // IMPORTANT: this should only be called after verifying user is admin for the project

    // this action handler updates the database using data submitted by forms and is called after includes but before any output is generated

    global $feedback, $Language;
    if (!(is_numeric($group_id))) {
        exit_error("invalid data", "1"); // unexpected error - no translation reqd.
    }
    $handledIt = FALSE;
    switch ($func) {
        case PROJECT_FAMILY_ADMIN_CONFIG_UPDATE:
            global $EnableProjectLink, $EnableBackLinks;    // form parameters

            if (isset($EnableProjectLink)) {
                user_set_preference("ProjectFamilies_GroupId_master", $group_id);
            } else {
                user_del_preference("ProjectFamilies_GroupId_master");
            }
            $feedback .= ' '.$Language->getText('plugin_pfamily', 'update_ok');
            $handledIt = TRUE;
            break;

        case PROJECT_FAMILY_ADMIN_LINK_DELETE:
            // delete project link
            global $link_id; // expected from the submitted form
            if (!(is_numeric($link_id))) {
                exit_error("invalid data", "3"); // unexpected error - no translation reqd.
            }
            if (db_query("DELETE FROM plugin_related_project_relationship
                        WHERE (master_group_id=$group_id) AND (link_id=$link_id);")) {  // NB: use group_id to  to defend against malicious use
                $feedback .= ' '.$Language->getText('plugin_pfamily', 'project_link_deleted_OK');
            } else {
                $feedback .= ' '.$Language->getText('plugin_pfamily', 'update_failed', db_error());
            }
            $handledIt = TRUE;
            break;

        case PROJECT_FAMILY_ADMIN_TYPE_DELETE:
            // delete project link type and all links using the  type
            global $link_type_id; // expected from the submitted form
            if (!(is_numeric($link_type_id))) {
                exit_error("invalid data", "4"); // unexpected error - no translation reqd.
            }
            // delete project relationship instances
            if (! db_query("DELETE FROM plugin_related_project_relationship
                        WHERE (master_group_id=$group_id) AND (link_type_id=$link_type_id);")) { // NB: use group_id to defend against malicious use
                $feedback .= ' '.$Language->getText('plugin_pfamily', 'update_failed', db_error());
            } else {
                // delete the relationship type
                if (! db_query("DELETE FROM plugin_related_project_link_type
                            WHERE (group_id=$group_id) AND (link_type_id=$link_type_id);")) {
                    $feedback .= ' '.$Language->getText('plugin_pfamily', 'update_failed', db_error());
                } else {
                    $feedback .= ' '.$Language->getText('plugin_pfamily', 'project_link_deleted_OK');
                }
            }
            $handledIt = TRUE;
            break;

        case PROJECT_FAMILY_ADMIN_TYPE_UPDATE:
            global $name, $reverse_name, $description, $uri_plus, $group_id, $link_type_id; // expected from the submitted form
            // NB: $link_type_id is not set when submitting a new link
            $reverse_name = Nz($reverse_name, $name);    // default reverse name to match fwd name
            if (!(is_numeric($group_id))) {
                exit_error("invalid data", "2.1"); // unexpected error - no translation reqd.
            }
            if (isset($link_type_id)) {
                if (!(is_numeric($link_type_id))) {
                    exit_error("invalid data", "2.2"); // unexpected error - no translation reqd.
                }
            }
            // check the change would not create a duplicate (same name OR same reverse_name)
            $pfcheck = db_query("SELECT name FROM plugin_related_project_link_type WHERE (
                    ((name=".DataAccess::quoteSmart($name).") OR (reverse_name=".DataAccess::quoteSmart($reverse_name)."))
                    AND ((group_id=$group_id)".(isset($link_type_id)?" AND (link_type_id<>$link_type_id)":"").")
                );");
            if (db_numrows($pfcheck) > 0) {
                $feedback .= ' '.$Language->getText('plugin_pfamily', 'project_link_type_change_makes_duplicate');
            } elseif (db_update("plugin_related_project_link_type", array(
                            "name" => DataAccess::quoteSmart($name),
                            "reverse_name" => DataAccess::quoteSmart($reverse_name),
                            "description" => DataAccess::quoteSmart($description),
                            "uri_plus" => DataAccess::quoteSmart($uri_plus),
                            "group_id" => $group_id
                            ), isset($link_type_id)?"link_type_id=$link_type_id":"")) {
                    $feedback .= ' '.$Language->getText('plugin_pfamily', 'update_ok').' ';
            } else {
                $feedback .= ' '.$Language->getText('plugin_pfamily', 'update_failed', db_error());
            }
            $handledIt = TRUE;
            break;

        case PROJECT_FAMILY_ADMIN_LINK_UPDATE:
            global $link_type_id, $target_group_id, $group_id, $link_id; // expected from the submitted form
            // NB: $link_id is not set when submitting a new link
            if (!(is_numeric($link_type_id) && is_numeric($target_group_id) && is_numeric($group_id))) {
                exit_error("invalid data", "3.1"); // unexpected error - no translation reqd.
            }
            if (isset($link_id)) {
                if (!(is_numeric($link_id))) {
                    exit_error("invalid data", "3.2"); // unexpected error - no translation reqd.
                }
            }
            $feedback .= ' '.pf_link_unique_update($group_id, $target_group_id, $link_type_id, (isset($link_id)?$link_id:NULL));
            if (! isset($link_id)) {
                // if this is a new link to a template: add links to all the projects created from the template already
                $db_res = db_query("SELECT group_id
                    FROM groups
                    WHERE (built_from_template = $target_group_id);");
                while ($row = db_fetch_array($db_res)) {
                    $feedback .= ' '.pf_link_unique_update($group_id, $row['group_id'], $link_type_id);
                }
            }
            $handledIt = TRUE;
            break;

        case PROJECT_FAMILY_ADMIN_TEMPLATE_SYNC_TYPE_ADD:
            // add template-defined type
            //template_type_id
            $handledIt = TRUE;
            break;

        case PROJECT_FAMILY_ADMIN_TEMPLATE_SYNC_TYPE_UPDATE:
            // copy from template into existing link type
            //template_type_id
            //link_type_id
            $handledIt = TRUE;
            break;

        case PROJECT_FAMILY_ADMIN_TEMPLATE_SYNC_UPDATE:
            // add template-defined links
            //link_id
            $handledIt = TRUE;
            break;
    }
    return $handledIt;
}

//======================================================================================================
function pf_link_unique_update($group_id, $target_group_id, $link_type_id, $link_id = NULL)
{
    // check the change would not create a duplicate (same target project and link type)
    global $Language;

    $pfcheck = db_query("SELECT link_type_id FROM plugin_related_project_relationship WHERE (
            (target_group_id=$target_group_id)
            AND (master_group_id=$group_id)
            AND (link_type_id=$link_type_id)
            ".(is_null($link_id)?"":" AND (link_id<>$link_id)")."
        )");
    if (db_numrows($pfcheck) > 0) {
        $feedback = $Language->getText('plugin_pfamily', 'project_link_change_makes_duplicate', group_getname($target_group_id));
    } else {
        $updates = array(
                "link_type_id" => $link_type_id,
                "target_group_id" => $target_group_id,
                "master_group_id" => $group_id
                );
        if (is_null($link_id)) {
            $updates["creation_date"] = time(); // new item - set date, otherwise leave it alone
        }
        if (db_update("plugin_related_project_relationship", $updates, is_null($link_id)?"":"link_id=$link_id")) {
            $feedback = $Language->getText('plugin_pfamily', 'update_ok_named', group_getname($target_group_id)).' ';
        } else {
            $feedback = $Language->getText('plugin_pfamily', 'update_failed_named', array(db_error(), group_getname($target_group_id)));
        }
    }
    return $feedback;
}

//======================================================================================================
function showProjectFamilyLinkButton($group_id)
{
    // display "make a link to this project" button (if it is enabled and this is not the master project of the proposed link)
    global $Language;

    if (!(is_numeric($group_id))) {
        exit_error("invalid data", "6"); // unexpected error - no translation reqd.
    }
    $ProjectFamilyMaster = user_get_preference("ProjectFamilies_GroupId_master");
    if ($ProjectFamilyMaster && ($ProjectFamilyMaster != $group_id)) {
        print " ".MkAH(pf_get_img_add_link(), "/project/admin/pfamilyadmin.php?disp=".PROJECT_FAMILY_ADMIN_LINK_SHOW."&amp;target_group_id=$group_id&amp;group_id=$ProjectFamilyMaster", $Language->getText('plugin_pfamily','link_to_me', group_getname($ProjectFamilyMaster)));
    }
}

//======================================================================================================
function showProjectFamilylinks($group_id, $ShowAsAdmin)
{
    // display the list of project links - both for admin and regular display

    global $Language, $HTML;

    if (!(is_numeric($group_id))) {
        exit_error("invalid data", "7"); // unexpected error - no translation reqd.
    }

    function pf_Header()
    {
        global $Language, $HTML;
        static $doneHeader = false;

        if (! $doneHeader) {
            $doneHeader = TRUE;
            print $HTML->box1_top("<span style='white-space: nowrap;'>".$Language->getText('plugin_pfamily', 'project_families')."</span>
                <div style='width: 18em;'>");
        }
    }

    function DisplayPFamilyRst($pfLinks, $group_id, $ShowAsAdmin)
    {
        // display the passed recordset as project links
        global $Language, $HTML;
        static $twistieDifferentiator = 0;

        $twistieDifferentiator += 1;
        $cLinkTypeName = "";
        $twistieOpen = FALSE;
        while ($row_pfLinks = db_fetch_array($pfLinks)) {
            if ($cLinkTypeName <> $row_pfLinks['link_name']) {
                $cLinkTypeName = $row_pfLinks['link_name'];
                if ($twistieOpen) {
                    TwistieEnd();
                }
                TwistieStart(htmlentities($cLinkTypeName), $cLinkTypeName.$twistieDifferentiator, $ShowAsAdmin?TRUE:NULL);
                $twistieOpen = TRUE;
            } else {
                print "<BR>";
            }
            print "<span style='white-space: nowrap;'>";
            //print util_timestamp_to_userdateformat($row_pfLinks['creation_date']);
            if ($row_pfLinks['type'] <> 1) {
                print pf_get_img_template()." ";
            }
            if ($row_pfLinks['master_group_id'] != $group_id) {
                // current project is not link master - just link to the master project's summary page (these are linking project)
                print MkAH(htmlentities($row_pfLinks['group_name']), "/projects/".htmlentities($row_pfLinks['unix_group_name']));
            } else {
                if ($ShowAsAdmin) {
                    // link to admin for the project link
                    print MkAH(pf_get_img_trash(), "/project/admin/pfamilyadmin.php?func=".PROJECT_FAMILY_ADMIN_LINK_DELETE."&amp;group_id=$group_id&amp;link_id=".$row_pfLinks['link_id'],
                                $Language->getText('plugin_pfamily', 'delete_link'),
                                array('onClick'=>"return confirm('".$Language->getText('plugin_pfamily', 'delete_link')."?')"))." ";
                    $uri = "/project/admin/pfamilyadmin.php?disp=".PROJECT_FAMILY_ADMIN_LINK_SHOW."&group_id=$group_id&link_id=".$row_pfLinks['link_id'].'&target_group_id='.$row_pfLinks['target_group_id'];
                    $title = $Language->getText('plugin_pfamily', 'tooltip_update');
                } else {
                    $uri = Nz($row_pfLinks['uri_plus'], PF_DEFAULT_PROJECT_LINK);
                    $title = "";
                    foreach (array(
                                '$group_id' => htmlentities($row_pfLinks['group_id']),
                                '$projname' => htmlentities($row_pfLinks['unix_group_name']))
                            as $str => $replace) {
                        $uri = str_replace($str, $replace, $uri);
                    }
                }
                print MkAH(htmlentities($row_pfLinks['group_name']), $uri, $title);
            }
            if ((time() - $row_pfLinks['creation_date']) < 604800) {    //created within the week?
                print pf_get_img_new($row_pfLinks['creation_date']) . " ";
            }
            print "</span>";
        }
        if ($twistieOpen) {
            TwistieEnd();
        }
    }

    $doFooter = FALSE;
    $pfLinks = db_query("SELECT name AS link_name, type, groups.group_id, group_name, unix_group_name, uri_plus, link_id, creation_date, master_group_id, target_group_id
                FROM plugin_related_project_relationship,plugin_related_project_link_type,groups
                WHERE (plugin_related_project_relationship.link_type_id = plugin_related_project_link_type.link_type_id)
                    AND (plugin_related_project_relationship.target_group_id = groups.group_id)
                    AND ((master_group_id = $group_id) AND (status = 'A'))
                ORDER BY name, type, group_name;");
    if (db_numrows($pfLinks) > 0) {
        $doFooter = True;
        pf_Header();
        DisplayPFamilyRst($pfLinks, $group_id, $ShowAsAdmin);
    }
    $pfLinks = db_query("SELECT reverse_name AS link_name, type, groups.group_id, group_name, unix_group_name, uri_plus, link_id, creation_date, master_group_id, target_group_id
                FROM plugin_related_project_relationship,plugin_related_project_link_type,groups
                WHERE (plugin_related_project_relationship.link_type_id = plugin_related_project_link_type.link_type_id)
                    AND (plugin_related_project_relationship.master_group_id = groups.group_id)
                    AND ((target_group_id = $group_id) AND (status = 'A'))
                ORDER BY name, type, group_name;");
    if (db_numrows($pfLinks) > 0) {
        // display back links
        $doFooter = True;
        pf_Header();
        TwistieStart($Language->getText('plugin_pfamily', 'back_links'), "twpf_".$Language->getText('plugin_pfamily', 'back_links'),($ShowAsAdmin?False:NULL));
        //print "<br><span style='white-space: nowrap;'><u>".$Language->getText('plugin_pfamily', 'back_links')."</u></span>";
        DisplayPFamilyRst($pfLinks, $group_id, $ShowAsAdmin);
        TwistieEnd();
    }
    if ($doFooter) {
        print "</div>".$HTML->box1_bottom();
    }
}

//======================================================================================================
function ProjectFamilyInheritFromTemplate($group_id, $templateGroup_id)
{
    // called during new project creation to inherit project familuy links and types from a template

    if (!(is_numeric($group_id) && is_numeric($templateGroup_id))) {
        exit_error("invalid data", "8"); // unexpected error - no translation reqd.
    }

    // 1. copy types
    $db_res = db_query("SELECT * FROM plugin_related_project_link_type
                        WHERE (group_id = $templateGroup_id);");
    // documentation says we can't INSERT and SELECT in the same table in MySQL, so we need to loop and insert
    while ($row = db_fetch_array($db_res)) {
        db_query("INSERT INTO plugin_related_project_link_type (
                group_id,
                name,
                reverse_name,
                description,
                uri_plus
            ) VALUES (
                $group_id,
                ".DataAccess::quoteSmart($row['name']).",
                ".DataAccess::quoteSmart($row['reverse_name']).",
                ".DataAccess::quoteSmart($row['description']).",
                ".DataAccess::quoteSmart($row['uri_plus'])."
            );");
    }

    // 2. copy project links where the template is master
    $db_res = db_query("SELECT name, target_group_id
        FROM plugin_related_project_relationship,plugin_related_project_link_type
        WHERE ((plugin_related_project_relationship.link_type_id = plugin_related_project_link_type.link_type_id)
            AND (master_group_id = $templateGroup_id));");
    while ($row = db_fetch_array($db_res)) {
        $db_res2 = db_query("SELECT link_type_id FROM plugin_related_project_link_type
            WHERE ((group_id = $group_id) AND (name=".DataAccess::quoteSmart($row['name'])."));");
        if (db_numrows($db_res2) > 0) {
            $row2 = db_fetch_array($db_res2);
            db_query("INSERT INTO plugin_related_project_relationship (
                    link_type_id,
                    master_group_id,
                    target_group_id,
                    creation_date
                ) VALUES (
                    ".$row2['link_type_id'].",
                    $group_id,
                    ".$row['target_group_id'].",
                    ".time()."
                );");
        }
    }

    // 3. copy project links where the template is target - NB they are made in the master project
    $db_res = db_query("SELECT link_type_id, master_group_id
        FROM plugin_related_project_relationship
        WHERE (target_group_id = $templateGroup_id);");
    while ($row = db_fetch_array($db_res)) {
        db_query("INSERT INTO plugin_related_project_relationship (
                link_type_id,
                master_group_id,
                target_group_id,
                creation_date
            ) VALUES (
                ".$row['link_type_id'].",
                ".$row['master_group_id'].",
                $group_id,
                ".time()."
            );");
    }
}

//======================================================================================================
function ProjectFamilyDeleteAll($group_id)
{
    // deletes all project family information for the passed group - usually when a user declines to accept a new project at the final step

    if (!(is_numeric($group_id))) {
        exit_error("invalid data", "9"); // unexpected error - no translation reqd.
    }
    db_query("DELETE FROM trove_group_link WHERE group_id=$group_id");
    db_query("DELETE FROM plugin_related_project_link_type WHERE group_id=$group_id");
    db_query("DELETE FROM plugin_related_project_relationship WHERE ((master_group_id=$group_id) OR (target_group_id=$group_id))");
}

//=============================================================================================
function pfamily_get_links($group_id)
{
    // always returns a record set of project link types belonging to the passed group. if there are none, it creates the default set

    global $Language, $feedback;

    $pfLinkQuery = "SELECT link_type_id, name, reverse_name, description, uri_plus, group_id
                FROM plugin_related_project_link_type
                WHERE (group_id=$group_id)
                ORDER BY (name);";
    $pfLinks = db_query($pfLinkQuery);
    if (db_numrows($pfLinks) <= 0) {
        // no link types defined for this project - silently insert the defaults
        if (! db_query("INSERT INTO plugin_related_project_link_type
            (group_id, name, reverse_name, description, uri_plus)
            VALUES (
                $group_id,
                ".DataAccess::quoteSmart(htmlentities($Language->getText('plugin_pfamily','db_sp_name'))).",
                ".DataAccess::quoteSmart(htmlentities($Language->getText('plugin_pfamily','db_sp_rname'))).",
                ".DataAccess::quoteSmart(htmlentities($Language->getText('plugin_pfamily','db_sp_desc'))).",
                ".DataAccess::quoteSmart(PF_DEFAULT_DOCMAN_LINK)."
            );"
        )) {
            exit_error("FATAL ERROR - INSERT 1 FAILED TO CREATE LINK TYPES!", "");    //should not occur (no translation required)
        }
        if (! db_query("INSERT INTO plugin_related_project_link_type
            (group_id, name, reverse_name, description, uri_plus)
            VALUES (
                $group_id,
                ".DataAccess::quoteSmart(htmlentities($Language->getText('plugin_pfamily','db_rp_name'))).",
                ".DataAccess::quoteSmart(htmlentities($Language->getText('plugin_pfamily','db_rp_rname'))).",
                ".DataAccess::quoteSmart(htmlentities($Language->getText('plugin_pfamily','db_rp_desc'))).",
                ".DataAccess::quoteSmart(PF_DEFAULT_PROJECT_LINK)."
            );"
        )) {
            exit_error("FATAL ERROR - INSERT 2 FAILED TO CREATE LINK TYPES!", "");    //should not occur (no translation required)
        }
        $feedback .= ' - '.$Language->getText('plugin_pfamily', 'default_link_types_created');
    }
    // requery
    $pfLinks = db_query($pfLinkQuery);
    if (db_numrows($pfLinks) <= 0) {
        exit_error("FATAL ERROR - FAILED TO CREATE LINK TYPES!", "");    //should not occur (no translation required)
    }
    return $pfLinks;
}
//======================================================================================================
function pf_get_img_main_icon()
{
    return "<IMG SRC='".util_get_image_theme("project_linking.png")."' HEIGHT='40' WIDTH='101' BORDER='0' ALT='project linking'>";
}
function pf_get_img_add_link()
{
    // returns the HTML to display the project linking create icon
    return '<IMG SRC="'.util_get_image_theme("project_linking_plus.png").'" HEIGHT="21" WIDTH="58" BORDER="0" ALT="add project link">';
}
function pf_get_img_template()
{
    global $Language;
    return "<img src='".util_get_image_theme("ic/template.png")."' border=0 title='".$Language->getText('plugin_pfamily', 'template_marker')."' alt='".$Language->getText('plugin_pfamily', 'template_marker')."'>";
}
function pf_get_img_trash()
{
    return "<IMG SRC='".util_get_image_theme("ic/trash.png")."' HEIGHT='16' WIDTH='16' BORDER='0' ALT='DELETE'>";
}
function  pf_get_img_new($date)
{
    global $Language;
    return "<img src='".util_get_image_theme("ic/new.png")."' border='0' alt='new' title='".$Language->getText('plugin_pfamily', 'newly_added', util_timestamp_to_userdateformat($date))."'>";
}
?>