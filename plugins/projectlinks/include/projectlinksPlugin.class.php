<?php
/*
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet & Dave Kibble, 2007
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
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once('common/plugin/Plugin.class.php');

//=============================================================================
class ProjectLinksPlugin extends Plugin {
    var $pluginInfo;

    //========================================================================
    function __construct($id) {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
        $this->pluginInfo = NULL;

        $this->_addHook('admin_toolbar_configuration',
            'adminToolbarConfiguration', false);

        // add link - only visible when confirgured by a user from an allowed project
        $this->_addHook('project_summary_title',
            'projectSummaryTitle', false);

        // only does anythign if template authorised, or linked to
        $this->_addHook('register_project_creation',
            'registerProjectCreation', false);

        $this->_addHook('cssfile',         'cssfile',         false);
        $this->_addHook('widget_instance', 'widget_instance', false);
        $this->_addHook('widgets',         'widgets',         false);
    }

    //========================================================================
    function getPluginInfo() {
        if (!($this->pluginInfo instanceof ProjectLinksPluginInfo)) {
            require_once('ProjectLinksPluginInfo.class.php');
            $this->pluginInfo = new ProjectLinksPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    //========================================================================
    function adminToolbarConfiguration($params) {
        $pM = PluginManager::instance();
        if ($pM->isPluginAllowedForProject($this, $params['group_id'])) {
            // only if allowed for this project...

            $url = $this->_adminURI().'?group_id='.$params['group_id'];
            $html = '<A HREF="'.$url.'">'.
            $GLOBALS['Language']->getText('plugin_plinks',
                'project_links_admin').'</A>';
            print ' | '.$html."\n";
        }
    }

    //========================================================================
    function _adminURI() {
        return $this->getPluginPath()."/projectlinks_admin.php";
    }

    //========================================================================
    function adminPage() {
        // serve the administration pages for project links

        global $Language;

        require_once('pre.php');
        require_once('vars.php');
        require_once('form_utils.php');
        require_once('www/project/admin/project_admin_utils.php');

        $group_id = (int) $_REQUEST['group_id'];

        // get current information
        $project = ProjectManager::instance()->getProject($group_id);
        $user    = UserManager::instance()->getCurrentUser();
        
        if (!$project) {
            exit_error($Language->getText('project_admin_index','invalid_p'),
            $Language->getText('project_admin_index','p_not_found'));
        }

        //if project isn't active, user must be a member of super-admin group
        if (!$project->isActive() && !$user->isSuperUser()) {
            return;
        }

        // must be a project admin
        if (!$user->isMember($group_id, 'A')) {
            return;
        }

        if (isset($_REQUEST['func'])) { // updating the database?
            $this->_adminPageUpdate_Service($_REQUEST);
        }
        project_admin_header(array(
            'title' => $Language->getText('project_admin_servicebar',
                'edit_s_bar'),
            'group' => $group_id, 'help' => 'ProjectLinks.html'));
        if (isset($_REQUEST['disp'])) {
            $disp = $_REQUEST['disp'];
            switch ($disp) {
                case 'edit_link_type':
                    if (isset($_REQUEST['link_type_id'])) {
                        $link_type_id = (int) $_REQUEST['link_type_id'];
                    } else {
                        $link_type_id = NULL;
                    }
                    $this->_adminPage_UpdateLinkType($group_id, $link_type_id);
                    break;
                case 'resync_template':
                    $template_id = (int) $_REQUEST['template_id'];
                    $this->_adminPage_ResyncTemplate($group_id, $template_id);
                    break;
            }
        } else {
            $this->_adminPage_Default($group_id, $project);
        }
        project_admin_footer(array());
    }

    //========================================================================
    function _adminPageUpdate_Service($_REQUEST) {
        global $Language, $feedback;
        $group_id = (int) $_REQUEST['group_id'];
        switch ($_REQUEST['func']) {
            case 'pl_config_update':
                if (isset($_REQUEST['EnableProjectLink'])) {
                    user_set_preference("pl_GroupId_master", $group_id);
                } else {
                    user_del_preference("pl_GroupId_master");
                }
                $feedback .= ' '.$Language->getText('plugin_plinks', 'update_ok');
                break;

            case 'pl_link_delete':
                // delete project link
                $link_id = (int) $_REQUEST['link_id'];
                // NB: use group_id to defend against malicious use
                if (db_query("DELETE FROM plugin_projectlinks_relationship
                            WHERE (master_group_id=".db_ei($group_id).")
                                AND (link_id=".db_ei($link_id).");"
                )) {
                    $feedback .= ' '.$Language->getText('plugin_plinks',
                    'project_link_deleted_OK');
                } else {
                    $feedback .= ' '.$Language->getText('plugin_plinks',
                    'update_failed', db_error());
                }
                break;

            case 'pl_type_delete':
                // delete project link type and all links using the type
                $link_type_id = (int) $_REQUEST['link_type_id'];
                // delete project relationship instances
                // NB: use group_id to defend against malicious use
                if (! db_query("DELETE FROM plugin_projectlinks_relationship
                    WHERE (master_group_id=".db_ei($group_id).")
                        AND (link_type_id=".db_ei($link_type_id).");")
                ) {
                    $feedback .= ' '.$Language->getText('plugin_plinks',
                    'update_failed', db_error());
                } else {
                    //delete the relationship type if no error deleting instances
                    if (! db_query("DELETE FROM plugin_projectlinks_link_type
                        WHERE (group_id=".db_ei($group_id).")
                            AND (link_type_id=".db_ei($link_type_id).");")
                    ) {
                        $feedback .= ' '.$Language->getText('plugin_plinks',
                        'update_failed', db_error());
                    } else {
                        $feedback .= ' '.$Language->getText('plugin_plinks',
                        'project_link_deleted_OK');
                    }
                    if (user_get_preference("pl_GroupId_master")
                    == $group_id) {
                        // switch off linking to this project - it would be better
                        // to check if no types left, but this works well
                        user_del_preference("pl_GroupId_master");
                    }
                }
                break;

            case 'pl_type_update':
                $q_name = "'".db_es($_REQUEST['name'])."'";
                $q_reverse_name = "'".db_es(
                nz($_REQUEST['reverse_name'], $_REQUEST['name']))."'";
                $q_description = "'".db_es($_REQUEST['description'])."'";
                /** **1 commented out for now - until we can decide how to deal with project links functionality
                 $q_uri_plus = db_es($_REQUEST['uri_plus']);
                 **/
                $q_uri_plus = "'".db_es('/projects/$projname/')."'";
                // $link_type_id is not set when submitting a new link
                if (isset($_REQUEST['link_type_id'])) {
                    $link_type_id = (int) $_REQUEST['link_type_id'];
                } else {
                    $link_type_id = NULL;
                }
                // check the change would not create a duplicate
                $pfcheck = db_query("SELECT name
                FROM plugin_projectlinks_link_type
                WHERE (((name=".$q_name.")
                        OR (reverse_name=".$q_reverse_name."))
                    AND ((group_id=".db_ei($group_id).")".
                (is_null($link_type_id)?"":
                        " AND (link_type_id<>".db_ei($link_type_id).")").
                    ")
                );");
                if (db_numrows($pfcheck) > 0) {
                    $feedback .= ' '.$Language->getText('plugin_plinks',
                    'project_link_type_change_makes_duplicate');
                } elseif (update_database("plugin_projectlinks_link_type",
                array(
                    "name" => $q_name,
                    "reverse_name" => $q_reverse_name,
                    "description" => $q_description,
                    "uri_plus" => $q_uri_plus,
                    "group_id" => $group_id
                ), (is_null($link_type_id)?
                NULL:"link_type_id=$link_type_id"))
                ) {
                    $this->addWidgetOnSummaryPage($group_id);
                    $feedback .= ' '.$Language->getText('plugin_plinks',
                    'update_ok').' ';
                } else {
                    $feedback .= ' '.$Language->getText('plugin_plinks',
                'update_failed', db_error());
                }
                break;

            case 'pl_link_update':
                $link_type_id = (int) $_REQUEST['link_type_id'];
                if (isset($_REQUEST['target_group_id'])) {
                    $target_group_id = (int) $_REQUEST['target_group_id'];
                } else {
                    $prjManager = ProjectManager::instance();
                    $trgProject = $prjManager->getProjectFromAutocompleter($_REQUEST['target_group']);
                    if ($trgProject !== false) {
                        $target_group_id = $trgProject->getId();
                    } else {
                        return;
                    }
                }
                $group_id = (int) $_REQUEST['group_id'];
                // NB: $link_id is not set when submitting a new link
                if (isset($_REQUEST['link_id'])) {
                    $link_id = (int) $_REQUEST['link_id'];
                } else {
                    $link_id = NULL;
                    // if this is a new link to a template:
                    //  add links to all projects already created from the template
                    $db_res = db_query("SELECT group_id
                    FROM groups
                    WHERE (built_from_template = ".db_ei($target_group_id).");");
                    while ($row = db_fetch_array($db_res)) {
                        $feedback .= ' '.$this->_link_unique_update($group_id,
                        $row['group_id'], $link_type_id);
                    }
                }
                $feedback .= ' '.$this->_link_unique_update(
                $group_id,
                $target_group_id,
                $link_type_id,
                $link_id);
                break;

            case 'template_sync_type_add':
                $template_type_id = (int) $_REQUEST['template_type_id'];
                $db_res = db_query("SELECT * FROM plugin_projectlinks_link_type
                                WHERE (link_type_id = ".db_ei($template_type_id).");");
                if (db_numrows($db_res) == 1) {
                    $row = db_fetch_array($db_res);
                    if (db_query("INSERT INTO plugin_projectlinks_link_type (
                        group_id,
                        name,
                        reverse_name,
                        description,
                        uri_plus
                    ) VALUES (
                    $group_id,
                        '".db_es($row['name'])."',
                        '".db_es($row['reverse_name'])."',
                        '".db_es($row['description'])."',
                        '".db_es($row['uri_plus'])."'
                    );")) {
                    $feedback .= ' '.$Language->getText('plugin_plinks', 'update_ok');
                    }
                }
                break;

            default:
                $feedback .= " not implemented: '{$_REQUEST['func']}'";
                break;
        }
    }

    //========================================================================
    function _icon($icon, $params = NULL) {
        // returns the HTML to display the named icon
        global $Language;
        switch ($icon) {
            case 'main':
                $src = $this->getThemePath()."/images/project_link.png";
                $height = 21;
                $width = 77;
                $alt = $Language->getText('plugin_plinks', 'project_links');
                break;
            case 'add':
                $src = $this->getThemePath()."/images/add.png";
                $height = 10;
                $width = 10;
                $alt = $Language->getText('plugin_plinks', 'add');
                break;
            case 'template':
                $src = $this->getThemePath()."/images/template.png";
                $height = 15;
                $width = 10;
                $alt = $Language->getText('plugin_plinks', 'template_marker');
                break;
            case 'new':
                $src = $this->getThemePath()."/images/new.png";
                $height = 10;
                $width = 10;
                $alt = $Language->getText('plugin_plinks',
                'newly_added', util_timestamp_to_userdateformat($params['date']));
                break;
            case 'arrow-right':
                $src = $this->getThemePath()."/images/arrow-right.png";
                $height = 10;
                $width = 10;
                $alt = "";
                break;
            case 'trash':
                $src = util_get_image_theme('ic/trash.png');
                $height = 16;
                $width = 16;
                $alt = $Language->getText('plugin_plinks', 'delete');
                break;
            case 'matched':
                $src = util_get_image_theme('ic/check.png');
                $height = 15;
                $width = 16;
                $alt = $Language->getText('plugin_plinks', 'matched');
                break;
        }
        return "<IMG SRC='$src' HEIGHT='$height' WIDTH='$width' BORDER='0'
            ALT='$alt' TITLE='$alt'>";
    }

    //========================================================================
    function _getLinks($group_id) {
        // returns a record set of project link types belonging to
        //the passed group
        return db_query("SELECT link_type_id, name, reverse_name, description,
            uri_plus, group_id
            FROM plugin_projectlinks_link_type
            WHERE (group_id=".db_ei($group_id).")
            ORDER BY (name);");
    }

    //========================================================================
    function _adminPage_Default($group_id, $project) {
        // show the default configuration page
        global $HTML, $Language;

        $db_res = $this->_getLinks($group_id);

        // link types
        $HTML->box1_top($Language->getText('plugin_plinks', 'link_types').
            " &nbsp; &nbsp; &nbsp; &nbsp; ".
        mkAH($Language->getText('plugin_plinks', 'create_type'),
        $this->_adminURI()."?disp=edit_link_type&group_id=$group_id"));
        if (db_numrows($db_res) <= 0) {
            print $Language->getText('plugin_plinks',
                'no_link_types_enable_explanation',
            $Language->getText('plugin_plinks', 'create_type'));
        } else {
            print html_build_list_table_top(
            array(
            $Language->getText('plugin_plinks', 'dbfn_name'),
            $Language->getText('plugin_plinks', 'dbfn_reverse_name'),
            $Language->getText('plugin_plinks', 'dbfn_description'),
            /** **1 commented out for now - until we can decide how to deal with project links functionality
             $Language->getText('plugin_plinks', 'dbfn_uri_plus'),
             **/
                    ""
                    ),
                    false, //links_arr
                    false, //mass_change
                    true); //full_width
                    $cnt = 0;
                    while ($row = db_fetch_array($db_res)) {
                        $cls = "class='".html_get_alt_row_color($cnt++)."'";
                        print "<TR>
                    <td $cls style='white-space: nowrap; vertical-align: top;'>".
                        mkAH(htmlentities($row['name']),
                        $this->_adminURI()."?disp=edit_link_type".
                            "&amp;group_id=".$row["group_id"].
                            "&amp;link_type_id=".$row["link_type_id"],
                        $Language->getText('plugin_plinks', 'update_details')).
                    "</td>
                    <td $cls style='white-space: nowrap; vertical-align: top;'>".
                        htmlentities($row['reverse_name'])."</td>
                    <td $cls style='vertical-align: top;'>".
                        htmlentities($row['description'])."</td>\n";
                        /** **1 commented out for now - until we can decide how to deal with project links functionality
                         print "<td $cls style='vertical-align: top;'>".
                         htmlentities($row['uri_plus'])."</td>\n";
                         **/
                        print "<td $cls style='vertical-align: top;'>".
                        mkAH($this->_icon('trash'),
                        $this->_adminURI()."?func=pl_type_delete".
                            "&amp;group_id=$group_id".
                            "&amp;link_type_id=".$row["link_type_id"],
                        $Language->getText('plugin_plinks', 'delete_type'),
                        array('onclick' => "return confirm('".
                        $Language->getText('plugin_plinks',
                                    'delete_type')."?')"))."
                    </td>
                    </TR>\n";
                    }
                    print "</TABLE>\n";
        }
        $HTML->box1_bottom();

        if ($project->getTemplate() > 100) {
            // project was built from a proper template - don't support
            // re-sync with site template (yet?)
            form_Start();
            form_hiddenParams(array(
                "disp" => 'resync_template',
                "group_id" => $group_id,
                "template_id" => $project->getTemplate()
            ));
            form_End($Language->getText('plugin_plinks',
                'synchronise_with_template'), FORM_NO_RESET_BUTTON);
        }
    }

    //========================================================================
    function _adminPage_UpdateLinkType($group_id, $link_type_id) {
        global $HTML, $Language;

        if (isset($link_type_id)) {
            $db_res = db_query("SELECT link_type_id, group_id, name,
                reverse_name, description, uri_plus
                FROM plugin_projectlinks_link_type
                WHERE ((group_id = ".db_ei($group_id).")
                    AND (link_type_id = ".db_ei($link_type_id)."));");
            if (db_numrows($db_res) <> 1) {
                exit_error("invalid data", "2.2"); // unexpected - no i18l
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
                    'uri_plus' => '/projects/$projname/'
                    );
        }
        $HTML->box1_top($Language->getText('plugin_plinks','project_links').
            " ".$this->_icon('main').
            " ".$Language->getText('plugin_plinks', 'link_type_update'));
        print mkAH("[".$Language->getText('global', 'btn_cancel')."]",
        $this->_adminURI()."?group_id=$group_id");
        print "<hr>\n";
        print "<table><tr><td>\n";
        $HTML->box1_top("");
        form_Start("");
        form_HiddenParams(array(
            "func" => 'pl_type_update',
            "group_id" => $group_id));
        if (isset($link_type_id)) {
            form_HiddenParams(array("link_type_id" => $link_type_id));
        }
        form_GenTextBox("name",
        htmlentities($Language->getText('plugin_plinks', 'dbfn_name')),
        $def['name'], 20);
        form_Validation("name", FORM_VAL_IS_NOT_ZERO_LENGTH);
        form_NewRow();
        form_GenTextBox("reverse_name",
        htmlentities($Language->getText('plugin_plinks',
                'dbfn_reverse_name')), $def['reverse_name'], 20);
        form_NewRow();
        form_GenTextArea("description",
        htmlentities($Language->getText('plugin_plinks',
                'dbfn_description')),
        $def['description']);
        /** **1 commented out for now - until we can decide how to deal with project links functionality
         form_NewRow();
         form_GenTextBox("uri_plus",
         htmlentities($Language->getText('plugin_plinks', 'dbfn_uri_plus')),
         $def['uri_plus'], 85);
         form_Validation("uri_plus", FORM_VAL_IS_NOT_ZERO_LENGTH);
         **/
        foreach (array("uri_plus", "name", "reverse_name", "description")
        as $ref)  {
            $formRefs[$ref] = form_JS_ElementRef($ref).".value";
        }
        form_End();
        $HTML->box1_bottom();
        print "</td><td>\n";
        $HTML->box1_top($Language->getText('plugin_plinks',
            'set_to_defaults'));
        print "<div style='padding: 5px; border: solid thin;
            vertical-align: middle;'>";
        print $Language->getText('plugin_plinks', 'replace_form_details').
            ":<p>";
        form_genJSButton($Language->getText('plugin_plinks', 'def_sp_name'),
            "if (confirm('".$Language->getText('plugin_plinks',
                'replace_form_details')."?')){".
        $formRefs["name"]."='".$Language->getText('plugin_plinks', 'def_sp_name')."';".
        $formRefs["reverse_name"]."='".$Language->getText('plugin_plinks', 'def_sp_rname')."';".
        $formRefs["description"]."='".$Language->getText('plugin_plinks', 'def_sp_desc')."';".
        /** **1 commented out for now - until we can decide how to deal with project links functionality
         $formRefs["uri_plus"]."='/projects/\$projname/';".
         **/
            "}");
        print "<p>";
        form_genJSButton($Language->getText('plugin_plinks', 'def_rp_name'),
            "if (confirm('".$Language->getText('plugin_plinks',
                'replace_form_details')."?')){".
        $formRefs["name"]."='".$Language->getText('plugin_plinks', 'def_rp_name')."';".
        $formRefs["reverse_name"]."='".$Language->getText('plugin_plinks', 'def_rp_rname')."';".
        $formRefs["description"]."='".$Language->getText('plugin_plinks', 'def_rp_desc')."';".
        /** **1 commented out for now - until we can decide how to deal with project links functionality
         $formRefs["uri_plus"]."='/projects/\$projname/';".
         **/
            "}");
        print "</div><p>";
        /** **1 commented out for now - until we can decide how to deal with project links functionality
         print "<div style='padding: 5px; border: solid thin;
         vertical-align: middle;'>";
         form_genJSButton($Language->getText('plugin_plinks', 'def_link_summary'),
         $formRefs["uri_plus"]."='/projects/\$projname/';");
         print "<p>";
         form_genJSButton($Language->getText('plugin_plinks', 'def_link_doc'),
         $formRefs["uri_plus"]."='/plugins/docman/?group_id=\$group_id';"
         );
         print "</div>";
         **/
        $HTML->box1_bottom();
        print "</td></tr></table>\n";
        
        if (isset($link_type_id)) {
            // Display list of linked projects
            $HTML->box1_top('Projects linked');
            print $this->_admin_links_table($link_type_id);

            // Admin can add new link
            print '<form name="plugin_projectlinks_add_link" method="post" action="?func=pl_link_update">';
            print '<input type="hidden" name="link_type_id" value="'.$link_type_id.'" />';
            print '<input type="hidden" name="group_id" value="'.$group_id.'" />';
            print '<input type="hidden" name="disp" value="edit_link_type" />';
            print '<p><label for="plugin_projectlinks_link_project">'.$GLOBALS['Language']->getText('plugin_plinks', 'add_project').'</label>';
            print '<input type="text" name="target_group" value="'.$GLOBALS['Language']->getText('plugin_plinks', 'add_project_autocompleter').'" size="60" id="plugin_projectlinks_link_project" /></p>';
            print '<input type="submit" value="'.$GLOBALS['Language']->getText('global', 'btn_create').'" />';
            print '</form>';
            $HTML->box1_bottom();

            $HTML->includeFooterJavascriptSnippet("new ProjectAutoCompleter('plugin_projectlinks_link_project', '".util_get_dir_image_theme()."', false);");
        }
        $HTML->box1_bottom();
    }

    //========================================================================
    function _adminPage_ResyncTemplate($group_id, $template_id) {
        // re-synchronise project links and types with originating template
        global $HTML, $Language;

        $HTML->box1_top($Language->getText('plugin_plinks','project_links').
            " ".$this->_icon('main')." ".
        $Language->getText('plugin_plinks', 'synchronise_with_template'));
        print mkAH("[".$Language->getText('global', 'btn_cancel')."]",
        $this->_adminURI()."?group_id=$group_id");
        // ==== compare link types ====
        print "<hr>\n";
        print "<h2>".$Language->getText('plugin_plinks','sync_type')."</h2>\n";
        $lt_tmplt = db_query("SELECT link_type_id, group_id, name,
            reverse_name, description, uri_plus
            FROM plugin_projectlinks_link_type
            WHERE (group_id = ".db_ei($template_id).");");
        print html_build_list_table_top(
        array(
        $Language->getText('plugin_plinks', 'action'),
        $Language->getText('plugin_plinks', 'dbfn_name'),
        $Language->getText('plugin_plinks', 'dbfn_reverse_name'),
        $Language->getText('plugin_plinks', 'dbfn_description'),
        $Language->getText('plugin_plinks', 'dbfn_uri_plus')
        ),
        false, //links_arr
        false, //mass_change
        false); //full_width
        $typeMatch = array();
        $cnt = 0;
        while ($ltr_tmplt = db_fetch_array($lt_tmplt)) {
            $cls = "class='".html_get_alt_row_color($cnt++)."'";
            print "<tr style=' vertical-align: top;'>\n";
            $diffs = array();
            $rs_grp = db_query("SELECT link_type_id, name, reverse_name,
                description, uri_plus FROM plugin_projectlinks_link_type
                WHERE ((group_id = ".db_ei($group_id).")
                    AND (name = '".db_es($ltr_tmplt['name'])."')
                    );");
            $basicURI = $this->_adminURI().
                "?disp=resync_template".
                "&amp;group_id=$group_id".
                "&amp;template_id=$template_id".
                "&amp;template_type_id={$ltr_tmplt['link_type_id']}";
            if (db_numrows($rs_grp) == 0) {
                // does not exist
                print "<td $cls style='text-align: center;
                        vertical-align: middle;'>".
                mkAH(
                $this->_icon("add"),
                $basicURI."&amp;func=template_sync_type_add").
                    "</td>";
            } else {
                // same name - any differences?
                $ltr_grp = db_fetch_array($rs_grp);
                $basicURI .= "&link_type_id={$ltr_grp['link_type_id']}";
                $typeMatch[$ltr_tmplt['link_type_id']] =
                $ltr_grp['link_type_id'];
                foreach (array('reverse_name', 'description', 'uri_plus')
                as $param) {
                    if ($ltr_tmplt[$param] <> $ltr_grp[$param]) {
                        $diffs[$param] = $ltr_grp[$param];
                    }
                }
                if (count($diffs) > 0) {
                    print "<td $cls>";
                    print "<table border='0' cellspacing='0' cellpadding='3'>
                        <tr><td>".
                    mkAH($Language->getText('plugin_plinks',
                                'sync_link_update'),
                    $basicURI.
                            "&name=".urlencode($ltr_tmplt['name']).
                            "&reverse_name=".urlencode(
                    $ltr_tmplt['reverse_name']).
                            "&description=".urlencode(
                    $ltr_tmplt['description']).
                            "&uri_plus=".urlencode($ltr_tmplt['uri_plus']).
                            "&link_type_id=".urlencode(
                    $ltr_grp['link_type_id']).
                            "&func=pl_type_update").
                        "</td></tr><tr>".
                        "<td style='text-align: right;'>".
                    $this->_icon("arrow-right").
                        "</td></tr></table>";
                    print "</td>";
                } else {
                    print "<td $cls style='text-align: center;
                        vertical-align: middle;'>".
                    $this->_icon('matched').
                        "</td>";
                }
            }
            print "<td $cls>";
            if (count($diffs) > 0) {
                print "<table border='0' cellspacing='0' cellpadding='3'>
                    <tr><td style='white-space: nowrap;'>".
                htmlentities($ltr_tmplt['name']).
                    "&nbsp; &nbsp; &nbsp; ".
                    "<span style='font-style:italic;'>".
                $Language->getText('plugin_plinks', 'project').
                    ":</span></td></tr><tr>
                    <td style='text-align: right;font-style:italic;'>".
                $Language->getText('plugin_plinks',
                        'sync_link_template').
                    ":</td></tr></table>";
            } else {
                print htmlentities($ltr_tmplt['name']);
            }
            print "</td>";
            foreach (array('reverse_name', 'description', 'uri_plus')
            as $param) {
                $style = "";
                if ($param <> 'description') {
                    $style .= "white-space: nowrap;";
                }
                if (isset($diffs[$param])) {
                    $style .= " font-weight: bold;";
                }
                print "<td $cls style='$style'>";
                if (count($diffs) > 0) {
                    print "<table border='0' cellspacing='0' cellpadding='3'>
                        <tr><td style='$style'>";
                }
                if (isset($diffs[$param])) {
                    print nz(htmlentities($diffs[$param]), "&nbsp;");
                } else {
                    print htmlentities($ltr_tmplt[$param]);
                }
                if (count($diffs) > 0) {
                    print "</td></tr><tr><td style='$style'>".
                    nz(htmlentities($ltr_tmplt[$param]), "&nbsp;").
                        "</td></tr></table>";
                }
                print "</td>";
            }
            print "</tr>\n";
        }
        print "</TABLE>\n";

        // ==== compare link instances ====
        print "<hr>\n";
        print "<h2>".$Language->getText('plugin_plinks','sync_link_new').
            "</h2>\n";
        $templLinks = db_query("
            SELECT plugin_projectlinks_relationship.link_type_id,
                name AS link_name, type, groups.group_id,
                group_name, unix_group_name, uri_plus,
                link_id, creation_date, master_group_id, target_group_id
            FROM plugin_projectlinks_relationship,
                plugin_projectlinks_link_type,groups
            WHERE (plugin_projectlinks_relationship.link_type_id
                    = plugin_projectlinks_link_type.link_type_id)
                AND (plugin_projectlinks_relationship.target_group_id
                    = groups.group_id)
                AND ((master_group_id = ".db_ei($template_id).")
                    AND (target_group_id <> ".db_ei($group_id).")
                    AND (status = 'A'))
            ORDER BY name, type, group_name;");
        if (db_numrows($templLinks) > 0) {
            print $Language->getText('plugin_plinks', 'synchronise_clickit',
            $this->_icon("add"));
            $type_missing = false;
            print html_build_list_table_top(
            array(
            $Language->getText('plugin_plinks', 'action'),
            $Language->getText('plugin_plinks', 'dbfn_link_type_id'),
            $Language->getText('plugin_plinks', 'project')
            ),
            false, //links_arr
            false, //mass_change
            false); //full_width
            $basicURI = $this->_adminURI().
                "?disp=resync_template".
                "&amp;func=pl_link_update".
                "&amp;group_id=$group_id".
                "&amp;template_id=$template_id";
            $cnt = 0;
            while ($row_templLinks = db_fetch_array($templLinks)) {
                $cls = "class='".html_get_alt_row_color($cnt++)."'";
                print "<tr valign='top'>";
                print "<td $cls  style='text-align: center; vertical-align: middle;'>";
                // is there a matching link in the project?
                if (isset($typeMatch[$row_templLinks['link_type_id']])) {
                    // we found a matching type
                    $findlinks = db_query("
                        SELECT creation_date
                        FROM plugin_projectlinks_relationship
                        WHERE ((master_group_id = ".db_ei($group_id).")
                            AND (target_group_id =
                            ".db_ei($row_templLinks['target_group_id']).")
                            AND (link_type_id =
                            ".db_ei($typeMatch[$row_templLinks['link_type_id']]).")
                        );");
                            if (db_numrows($findlinks) <= 0) {
                                print mkAH($this->_icon("add"),
                                $basicURI.
                            "&amp;target_group_id=".
                                $row_templLinks['target_group_id'].
                            "&amp;link_type_id=".
                                $typeMatch[$row_templLinks['link_type_id']]);
                            } else {
                                print $this->_icon('matched');
                            }
                } else {
                    $type_missing = true;
                    print $Language->getText('plugin_plinks',
                        'sync_link_needs_type');
                }
                print "</td>";
                print "<td $cls>".htmlentities($row_templLinks['link_name'])."</td>";
                print "<td $cls>".htmlentities($row_templLinks['group_name'])."</td>";
                print "</tr>";
            }
            print "</TABLE>\n";
            if ($type_missing) {
                print "<div style='width: 30em'><hr><i>".
                $Language->getText('plugin_plinks',
                        'sync_link_needs_type')."</i> ".
                $Language->getText('plugin_plinks',
                        'sync_link_new_no_type_explain', array(
                $this->_icon("add"),
                $Language->getText('plugin_plinks','sync_type')
                )
                ).
                    "</div>"
                    ;
            }
        }
        $HTML->box1_bottom();
    }

    //========================================================================
    function _link_unique_update($group_id, $target_group_id, $link_type_id, $link_id = NULL) {
        // update link, but check the change would not create a duplicate
        // (same target project and link type)
        global $Language;

        $targetProject = ProjectManager::instance()->getProject($target_group_id);
        
        $feedback = "";
        $pfcheck = db_query("SELECT link_type_id FROM
            plugin_projectlinks_relationship
            WHERE (
                (target_group_id=".db_ei($target_group_id).")
                AND (master_group_id=".db_ei($group_id).")
                AND (link_type_id=".db_ei($link_type_id).")
                ".(is_null($link_id)?"":" AND (link_id<>".db_ei($link_id).")")."
            )");
        if (db_numrows($pfcheck) > 0) {
            $feedback = $Language->getText('plugin_plinks',
                'project_link_change_makes_duplicate',
            $targetProject->getPublicName());
        } else {
            $updates = array(
                    "link_type_id" => $link_type_id,
                    "target_group_id" => $target_group_id,
                    "master_group_id" => $group_id
            );
            if (is_null($link_id)) {
                // new item - set date, otherwise leave it alone
                $updates["creation_date"] = time();
            }
            
            if (update_database("plugin_projectlinks_relationship", $updates, is_null($link_id)?"":"link_id=$link_id")) {
                $this->addWidgetOnSummaryPage($target_group_id);
                $feedback = $Language->getText('plugin_plinks', 'update_ok_named', $targetProject->getPublicName()).' ';
            } else {
                $feedback = $Language->getText('plugin_plinks', 'update_failed_named', array(db_error(), $targetProject->getPublicName()));
            }
        }
        return $feedback;
    }

    /**
     * Display the project linked by the current projet to update or delete them
     *
     * @param  Integer $group_id Group id
     * @return String
     */
    function _admin_links_table($link_type_id) {
        $html = '';

        $dao = $this->getProjectLinksDao();
        $links = $dao->searchLinksByType($link_type_id);
        
        if($links->rowCount() > 0) {
            $html .= html_build_list_table_top(array($GLOBALS['Language']->getText('plugin_plinks', 'dbfn_name'), ''), false, false, false);

            foreach($dao->searchLinksByType($link_type_id) as $row) {
                $html .= '<tr>';

                // Name
                $html .= '<td>'.$row['group_name'].'</td>';

                // Delete
                $url   = "?func=pl_link_delete&amp;disp=edit_link_type&amp;link_type_id=".$link_type_id."&amp;group_id=".$row['master_group_id']."&amp;link_id=".$row['link_id'];
                $warn  = $GLOBALS['Language']->getText('plugin_plinks', 'delete_link');
                $alt   = $GLOBALS['Language']->getText('plugin_plinks', 'delete_link');
                $html .= '<td>'.html_trash_link($url, $warn, $alt).'</td>';

                $html .= '</tr>';
            }

            $html .= '</table>';
        }
        return $html;
    }

    //========================================================================
    function registerProjectCreation($params) {
        // called during new project creation to inherit project links and
        // types from a template
        $group_id = $params['group_id'];
        $template_id = $params['template_id'];

        // 1. copy link types from the template into the new project
        $db_res = db_query("SELECT * FROM plugin_projectlinks_link_type
                            WHERE (group_id = ".db_ei($template_id).");");
        // documentation says we can't INSERT and SELECT in the same table in
        // MySQL, so we need to loop and insert
        while ($row = db_fetch_array($db_res)) {
            db_query("INSERT INTO plugin_projectlinks_link_type (
                    group_id,
                    name,
                    reverse_name,
                    description,
                    uri_plus
                ) VALUES (
                $group_id,
                    '".db_es($row['name'])."',
                    '".db_es($row['reverse_name'])."',
                    '".db_es($row['description'])."',
                    '".db_es($row['uri_plus'])."'
                );");
        }

        // 2. copy project links where the template is master
        $db_res = db_query("SELECT name, target_group_id
            FROM plugin_projectlinks_relationship,
                plugin_projectlinks_link_type
            WHERE ((plugin_projectlinks_relationship.link_type_id =
                    plugin_projectlinks_link_type.link_type_id)
                AND (master_group_id = ".db_ei($template_id)."));");
        while ($row = db_fetch_array($db_res)) {
            $db_res2 = db_query("SELECT link_type_id FROM
                plugin_projectlinks_link_type
                WHERE ((group_id = ".db_ei($group_id).")
                    AND (name='".db_es($row['name'])."'));");
            if (db_numrows($db_res2) > 0) {
                $row2 = db_fetch_array($db_res2);
                db_query("INSERT INTO plugin_projectlinks_relationship (
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

        // 3. copy project links where the template is target - NB they are
        // made in the master project
        $db_res = db_query("SELECT link_type_id, master_group_id
            FROM plugin_projectlinks_relationship
            WHERE (target_group_id = ".db_ei($template_id).");");
        while ($row = db_fetch_array($db_res)) {
            db_query("INSERT INTO plugin_projectlinks_relationship (
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

    //========================================================================
    function registerProjectAbandon($params) {
        // deletes all project link information for the passed group -
        // usually when a user declines to accept a new project at the
        //  final step

        $group_id = $params['group_id'];
        db_query("DELETE FROM plugin_projectlinks_link_type
            WHERE group_id=".db_ei($group_id));
        db_query("DELETE FROM plugin_projectlinks_relationship
            WHERE ((master_group_id=".db_ei($group_id).") OR (target_group_id=".db_ei($group_id)."))"
        );
    }

    function cssfile($params) {
        // Only show the stylesheet if we're in project home page
        if (strpos($_SERVER['REQUEST_URI'], '/projects') === 0) {
            echo '    <link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />'."\n";
        }
    }

    function widget_instance($params) {
        if ($params['widget'] == 'projectlinkshomepage') {
            include_once 'ProjectLinks_Widget_HomePageLinks.class.php';
            $params['instance'] = new ProjectLinks_Widget_HomePageLinks($this);
        }
    }

    function widgets($params) {
        include_once 'common/widget/WidgetLayoutManager.class.php';
        if ($params['owner_type'] == WidgetLayoutManager::OWNER_TYPE_GROUP) {
            $params['Codendi_widgets'][] = 'projectlinkshomepage';
        }
    }

    /**
     * Add a projectlink widget on project summary page if not already here
     * 
     * @param Integer $groupId Project id on which the widget is to add
     * 
     * @return void
     */
    function addWidgetOnSummaryPage($groupId) {
        include_once 'common/widget/WidgetLayoutManager.class.php';
        $widgetLayoutManager = new WidgetLayoutManager();

        $layoutId = 1;
        // 4.0 only
        // $layoutId = $widgetLayoutManager->getDefaultLayoutId($groupId, $widgetLayoutManager->OWNER_TYPE_GROUP);

        $sql = "SELECT NULL".
               " FROM layouts_contents".
               " WHERE owner_type = '". WidgetLayoutManager::OWNER_TYPE_GROUP ."'".
               " AND owner_id = ". $groupId.
               " AND layout_id = ". $layoutId.
               " AND name = 'projectlinkshomepage'";
        $res = db_query($sql);
        if ($res && db_numrows($res) == 0) {
            include_once 'ProjectLinks_Widget_HomePageLinks.class.php';
            $request = HTTPRequest::instance();
            $widget  = new ProjectLinks_Widget_HomePageLinks($this);
            $widgetLayoutManager->addWidget($groupId, WidgetLayoutManager::OWNER_TYPE_GROUP, $layoutId, 'projectlinkshomepage', $widget, $request);
        }
    }

    /**
     * Return ProjectLinksDao
     *
     * @return ProjectLinksDao
     */
    function getProjectLinksDao() {
        include_once 'ProjectLinksDao.class.php';
        return new ProjectLinksDao(CodendiDataAccess::instance());
    }

}
?>