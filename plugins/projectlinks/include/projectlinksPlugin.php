<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet & Dave Kibble, 2007
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

use FastRoute\RouteCollector;
use Tuleap\Date\DateHelper;
use Tuleap\Layout\BaseLayout;
use Tuleap\Plugin\ListeningToEventClass;
use Tuleap\Project\Admin\Navigation\NavigationDropdownItemPresenter;
use Tuleap\Project\Admin\Navigation\NavigationPresenter;
use Tuleap\Project\Admin\Navigation\NavigationPresenterBuilder;
use Tuleap\Project\Registration\RegisterProjectCreationEvent;
use Tuleap\Project\ServiceInstrumentation;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Widget\Event\GetProjectWidgetList;
use Tuleap\Widget\Event\GetWidget;

require_once __DIR__ . '/../vendor/autoload.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class ProjectLinksPlugin extends Plugin implements DispatchableWithRequest
{
    public $pluginInfo;

    //========================================================================
    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
        $this->pluginInfo = null;

        bindtextdomain('tuleap-projectlinks', __DIR__ . '/../site-content');

        // add link - only visible when confirgured by a user from an allowed project
        $this->addHook('project_summary_title', 'projectSummaryTitle');

        $this->addHook(RegisterProjectCreationEvent::NAME);
        $this->addHook(GetWidget::NAME);
        $this->addHook(GetProjectWidgetList::NAME);
        $this->addHook(NavigationPresenter::NAME);
    }

    //========================================================================
    public function getPluginInfo()
    {
        if (! ($this->pluginInfo instanceof ProjectLinksPluginInfo)) {
            require_once('ProjectLinksPluginInfo.class.php');
            $this->pluginInfo = new ProjectLinksPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    //========================================================================
    public function _adminURI() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getPluginPath() . "/projectlinks_admin.php";
    }

    #[ListeningToEventClass]
    public function collectRoutesEvent(CollectRoutesEvent $event): void
    {
        $event->getRouteCollector()->addGroup($this->getPluginPath(), function (RouteCollector $r) {
            $r->addRoute(['GET', 'POST'], '[/[index.php]]', $this->getRouteHandler('routeAdminPage'));
        });
        $event->getRouteCollector()->addGroup($this->getPluginPath(), function (RouteCollector $r) {
            $r->addRoute(['GET', 'POST'], '/projectlinks_admin.php', $this->getRouteHandler('routeAdminPage'));
        });
    }

    public function routeAdminPage(): DispatchableWithRequest
    {
        return $this;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        ServiceInstrumentation::increment($this->getName());
        // serve the administration pages for project links

        global $Language;

        require_once __DIR__ . '/form_utils.php';
        require_once __DIR__ . '/../../../src/www/project/admin/project_admin_utils.php';

        $request  = HTTPRequest::instance();
        $group_id = (int) $request->get('group_id');

        // get current information
        $project = ProjectManager::instance()->getProject($group_id);
        $user    = UserManager::instance()->getCurrentUser();

        if (! $project) {
            exit_error(
                $Language->getText('project_admin_index', 'invalid_p'),
                $Language->getText('project_admin_index', 'p_not_found')
            );
        }

        //if project isn't active, user must be a member of super-admin group
        if (! $project->isActive() && ! $user->isSuperUser()) {
            return;
        }

        // must be a project admin
        if (! $user->isMember($group_id, 'A')) {
            return;
        }

        if ($request->exist('func')) { // updating the database?
            $this->adminPageUpdate_Service($request);
        }

        project_admin_header(
            $Language->getText('project_admin_servicebar', 'edit_s_bar'),
            'project_links'
        );
        if ($request->exist('disp')) {
            $disp = $request->get('disp');
            switch ($disp) {
                case 'edit_link_type':
                    if ($request->exist('link_type_id')) {
                        $link_type_id = (int) $request->get('link_type_id');
                    } else {
                        $link_type_id = null;
                    }
                    $this->_adminPage_UpdateLinkType($group_id, $link_type_id);
                    break;
                case 'resync_template':
                    $template_id = (int) $request->get('template_id');
                    $this->_adminPage_ResyncTemplate($group_id, $template_id);
                    break;
            }
        } else {
            $this->_adminPage_Default($group_id, $project);
        }
        project_admin_footer([]);
    }

    //========================================================================
    private function adminPageUpdate_Service(HTTPRequest $request) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        global $Language, $feedback;
        $group_id = (int) $request->get('group_id');
        switch ($request->get('func')) {
            case 'pl_config_update':
                if ($request->exist('EnableProjectLink')) {
                    user_set_preference("pl_GroupId_master", $group_id);
                } else {
                    user_del_preference("pl_GroupId_master");
                }
                $feedback .= ' ' . dgettext('tuleap-projectlinks', 'update OK');
                break;

            case 'pl_link_delete':
                // delete project link
                $link_id = (int) $request->get('link_id');
                // NB: use group_id to defend against malicious use
                if (
                    db_query(
                        "DELETE FROM plugin_projectlinks_relationship
                         WHERE (master_group_id=" . db_ei($group_id) . ")
                            AND (link_id=" . db_ei($link_id) . ");"
                    )
                ) {
                    $feedback .= ' ' . dgettext('tuleap-projectlinks', 'Project link deleted OK');
                } else {
                    $feedback .= ' ' . sprintf(dgettext('tuleap-projectlinks', 'update failed (MySQL reason: "%1$s")'), db_error());
                }
                break;

            case 'pl_type_delete':
                // delete project link type and all links using the type
                $link_type_id = (int) $request->get('link_type_id');
                // delete project relationship instances
                // NB: use group_id to defend against malicious use
                if (
                    ! db_query(
                        "DELETE FROM plugin_projectlinks_relationship
                    WHERE (master_group_id=" . db_ei($group_id) . ") AND (link_type_id=" . db_ei($link_type_id) . ");"
                    )
                ) {
                    $feedback .= ' ' . sprintf(dgettext('tuleap-projectlinks', 'update failed (MySQL reason: "%1$s")'), db_error());
                } else {
                    //delete the relationship type if no error deleting instances
                    if (
                        ! db_query(
                            "DELETE FROM plugin_projectlinks_link_type
                            WHERE (group_id=" . db_ei($group_id) . ")AND (link_type_id=" . db_ei($link_type_id) . ");"
                        )
                    ) {
                        $feedback .= ' ' . sprintf(dgettext('tuleap-projectlinks', 'update failed (MySQL reason: "%1$s")'), db_error());
                    } else {
                        $feedback .= ' ' . dgettext('tuleap-projectlinks', 'Project link deleted OK');
                    }
                    if (user_get_preference("pl_GroupId_master") == $group_id) {
                        // switch off linking to this project - it would be better
                        // to check if no types left, but this works well
                        user_del_preference("pl_GroupId_master");
                    }
                }
                break;

            case 'pl_type_update':
                $q_name         = "'" . db_es($request->get('name')) . "'";
                $q_reverse_name = "'" . db_es(nz($request->get('reverse_name'), $request->get('name'))) . "'";
                $q_description  = "'" . db_es($request->get('description')) . "'";
                /** **1 commented out for now - until we can decide how to deal with project links functionality
                 * $q_uri_plus = db_es($_REQUEST['uri_plus']);
                 **/
                $q_uri_plus = "'" . db_es('/projects/$projname/') . "'";
                // $link_type_id is not set when submitting a new link
                if ($request->exist('link_type_id')) {
                    $link_type_id = (int) $request->get('link_type_id');
                } else {
                    $link_type_id = null;
                }
                // check the change would not create a duplicate
                $pfcheck = db_query("SELECT name
                FROM plugin_projectlinks_link_type
                WHERE (((name=" . $q_name . ")
                        OR (reverse_name=" . $q_reverse_name . "))
                    AND ((group_id=" . db_ei($group_id) . ")" .
                    (is_null($link_type_id) ? "" :
                        " AND (link_type_id<>" . db_ei($link_type_id) . ")") .
                    ")
                );");
                if (db_numrows($pfcheck) > 0) {
                    $feedback .= ' ' . dgettext('tuleap-projectlinks', 'That change would create a duplicate link name or reverse-name - link type not updated');
                } elseif (
                    update_database(
                        "plugin_projectlinks_link_type",
                        [
                            "name" => $q_name,
                            "reverse_name" => $q_reverse_name,
                            "description" => $q_description,
                            "uri_plus" => $q_uri_plus,
                            "group_id" => db_ei($group_id),
                        ],
                        ($link_type_id === null ? null : "link_type_id=" . db_ei($link_type_id))
                    )
                ) {
                    $feedback .= ' ' . dgettext('tuleap-projectlinks', 'update OK') . ' ';
                } else {
                    $feedback .= ' ' . sprintf(dgettext('tuleap-projectlinks', 'update failed (MySQL reason: "%1$s")'), db_error());
                }
                break;

            case 'pl_link_update':
                $link_type_id = (int) $request->get('link_type_id');
                if ($request->exist('target_group_id')) {
                    $target_group_id = (int) $request->get('target_group_id');
                } else {
                    $prjManager = ProjectManager::instance();
                    $trgProject = $prjManager->getProjectFromAutocompleter($request->get('target_group'));
                    if ($trgProject !== false) {
                        $target_group_id = $trgProject->getId();
                    } else {
                        return;
                    }
                }
                $group_id = (int) $request->get('group_id');
                // NB: $link_id is not set when submitting a new link
                if ($request->exist('link_id')) {
                    $link_id = (int) $request->get('link_id');
                } else {
                    $link_id = null;
                    // if this is a new link to a template:
                    //  add links to all projects already created from the template
                    $db_res = db_query(
                        "SELECT group_id
                             FROM `groups`
                             WHERE (built_from_template = " . db_ei($target_group_id) . ");"
                    );
                    while ($row = db_fetch_array($db_res)) {
                        $feedback .= ' ' . $this->_link_unique_update($group_id, $row['group_id'], $link_type_id);
                    }
                }
                $feedback .= ' ' . $this->_link_unique_update($group_id, $target_group_id, $link_type_id, $link_id);
                break;

            case 'template_sync_type_add':
                $template_type_id = (int) $request->get('template_type_id');
                $db_res           = db_query("SELECT * FROM plugin_projectlinks_link_type
                                WHERE (link_type_id = " . db_ei($template_type_id) . ");");
                if (db_numrows($db_res) == 1) {
                    $row = db_fetch_array($db_res);
                    if (
                        db_query("INSERT INTO plugin_projectlinks_link_type (
                        group_id,
                        name,
                        reverse_name,
                        description,
                        uri_plus
                    ) VALUES (
                    " . db_ei($group_id) . ",
                        '" . db_es($row['name']) . "',
                        '" . db_es($row['reverse_name']) . "',
                        '" . db_es($row['description']) . "',
                        '" . db_es($row['uri_plus']) . "'
                    );")
                    ) {
                        $feedback .= ' ' . dgettext('tuleap-projectlinks', 'update OK');
                    }
                }
                break;

            default:
                $feedback .= " not implemented: '{$request->get('func')}'";
                break;
        }
    }

    //========================================================================
    public function _icon($icon, $params = null) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // returns the HTML to display the named icon
        global $Language;
        switch ($icon) {
            case 'main':
                $src    = $this->getThemePath() . "/images/project_link.png";
                $height = 21;
                $width  = 77;
                $alt    = dgettext('tuleap-projectlinks', 'Project Links');
                break;
            case 'add':
                $src    = $this->getThemePath() . "/images/add.png";
                $height = 10;
                $width  = 10;
                $alt    = dgettext('tuleap-projectlinks', 'add');
                break;
            case 'template':
                $src    = $this->getThemePath() . "/images/template.png";
                $height = 15;
                $width  = 10;
                $alt    = dgettext('tuleap-projectlinks', 'template project');
                break;
            case 'new':
                $src    = $this->getThemePath() . "/images/new.png";
                $height = 10;
                $width  = 10;
                $alt    = sprintf(dgettext('tuleap-projectlinks', 'link added: %1$s'), DateHelper::formatForLanguage($GLOBALS['Language'], $params['date'], false));
                break;
            case 'arrow-right':
                $src    = $this->getThemePath() . "/images/arrow-right.png";
                $height = 10;
                $width  = 10;
                $alt    = "";
                break;
            case 'trash':
                $src    = util_get_image_theme('ic/trash.png');
                $height = 16;
                $width  = 16;
                $alt    = dgettext('tuleap-projectlinks', 'Delete');
                break;
            case 'matched':
                $src    = util_get_image_theme('ic/check.png');
                $height = 15;
                $width  = 16;
                $alt    = dgettext('tuleap-projectlinks', 'matched');
                break;
        }
        return "<IMG SRC='$src' HEIGHT='$height' WIDTH='$width' BORDER='0'
            ALT='$alt' TITLE='$alt'>";
    }

    //========================================================================
    public function _getLinks($group_id) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // returns a record set of project link types belonging to
        //the passed group
        return db_query("SELECT link_type_id, name, reverse_name, description,
            uri_plus, group_id
            FROM plugin_projectlinks_link_type
            WHERE (group_id=" . db_ei($group_id) . ")
            ORDER BY (name);");
    }

    //========================================================================
    public function _adminPage_Default($group_id, $project) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore, PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        // show the default configuration page
        global $HTML, $Language;

        $db_res = $this->_getLinks($group_id);

        // link types
        $HTML->box1_top(
            dgettext('tuleap-projectlinks', 'Project Link Types') .
            " &nbsp; &nbsp; &nbsp; &nbsp; " .
            mkAH(
                dgettext('tuleap-projectlinks', 'Add a project link type'),
                $this->_adminURI() . "?disp=edit_link_type&group_id=$group_id"
            )
        );

        $purifier = Codendi_HTMLPurifier::instance();
        if (db_numrows($db_res) <= 0) {
            print sprintf(dgettext('tuleap-projectlinks', 'To enable project linking, you must first define at least one project link type - click <i>%1$s</i> to get started.'), dgettext('tuleap-projectlinks', 'Add a project link type'));
        } else {
            print html_build_list_table_top(
                [
                    dgettext('tuleap-projectlinks', 'Name'),
                    dgettext('tuleap-projectlinks', 'Reverse Name'),
                    dgettext('tuleap-projectlinks', 'Description'),
                    "",
                ],
                false, //links_arr
                false, //mass_change
                true //full_width
            );
            $cnt = 0;
            while ($row = db_fetch_array($db_res)) {
                $cls = "class='" . html_get_alt_row_color($cnt++) . "'";
                print "<TR>
                    <td $cls style='white-space: nowrap; vertical-align: top;'>" .
                    mkAH(
                        htmlentities($row['name']),
                        $this->_adminURI() . "?disp=edit_link_type" .
                        "&amp;group_id=" . $purifier->purify(urlencode($row["group_id"])) .
                        "&amp;link_type_id=" . $purifier->purify(urlencode($row["link_type_id"])),
                        dgettext('tuleap-projectlinks', 'update details')
                    ) . "</td>
                    <td $cls style='white-space: nowrap; vertical-align: top;'>" .
                      $purifier->purify($row['reverse_name']) . "</td>
                    <td $cls style='vertical-align: top;'>" .
                      $purifier->purify($row['description']) . "</td>\n";
                /** **1 commented out for now - until we can decide how to deal with project links functionality
                 * print "<td $cls style='vertical-align: top;'>".
                 * htmlentities($row['uri_plus'])."</td>\n";
                 **/
                print "<td $cls style='vertical-align: top;'>" .
                    mkAH(
                        $this->_icon('trash'),
                        $this->_adminURI() . "?func=pl_type_delete" .
                        "&amp;group_id=" . $purifier->purify(urlencode($group_id)) .
                        "&amp;link_type_id=" . $purifier->purify(urlencode($row["link_type_id"])),
                        dgettext('tuleap-projectlinks', 'Delete project link type (including all links of this type)'),
                        [
                            'onclick' => "return confirm('" .
                                dgettext('tuleap-projectlinks', 'Delete project link type (including all links of this type)') . "?')",
                        ]
                    ) . "
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
            form_hiddenParams([
                "disp" => 'resync_template',
                "group_id" => $group_id,
                "template_id" => $project->getTemplate(),
            ]);
            form_End(
                dgettext('tuleap-projectlinks', 'Re-Synchronise Project Links with Template'),
                FORM_NO_RESET_BUTTON
            );
        }
    }

    //========================================================================
    public function _adminPage_UpdateLinkType($group_id, $link_type_id) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore, PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        global $HTML, $Language;

        if (isset($link_type_id)) {
            $db_res = db_query("SELECT link_type_id, group_id, name,
                reverse_name, description, uri_plus
                FROM plugin_projectlinks_link_type
                WHERE ((group_id = " . db_ei($group_id) . ")
                    AND (link_type_id = " . db_ei($link_type_id) . "));");
            if (db_numrows($db_res) <> 1) {
                exit_error("invalid data", "2.2"); // unexpected - no i18l
            }
            $row = db_fetch_array($db_res);
            $def = [
                'name' => htmlentities($row['name']),
                'reverse_name' => htmlentities($row['reverse_name']),
                'description' => htmlentities($row['description']),
                'uri_plus' => htmlentities($row['uri_plus']),
            ];
        } else {
            $def = [
                'name' => "",
                'reverse_name' => "",
                'description' => "",
                'uri_plus' => '/projects/$projname/',
            ];
        }
        $HTML->box1_top(dgettext('tuleap-projectlinks', 'Project Links') .
            " " . $this->_icon('main') .
            " " . dgettext('tuleap-projectlinks', 'Change Project Link Type Definition'));
        print mkAH(
            "[" . $Language->getText('global', 'btn_cancel') . "]",
            $this->_adminURI() . "?group_id=$group_id"
        );
        print "<hr>\n";
        print "<table><tr><td>\n";
        $HTML->box1_top("");
        form_Start("");
        form_HiddenParams([
            "func" => 'pl_type_update',
            "group_id" => $group_id,
        ]);
        if (isset($link_type_id)) {
            form_HiddenParams(["link_type_id" => $link_type_id]);
        }
        form_GenTextBox(
            "name",
            htmlentities(dgettext('tuleap-projectlinks', 'Name')),
            $def['name'],
            20
        );
        form_Validation("name", FORM_VAL_IS_NOT_ZERO_LENGTH);
        form_NewRow();
        form_GenTextBox(
            "reverse_name",
            htmlentities(dgettext('tuleap-projectlinks', 'Reverse Name')),
            $def['reverse_name'],
            20
        );
        form_NewRow();
        form_GenTextArea(
            "description",
            htmlentities(dgettext('tuleap-projectlinks', 'Description')),
            $def['description']
        );
        foreach (["uri_plus", "name", "reverse_name", "description"] as $ref) {
            $formRefs[$ref] = form_JS_ElementRef($ref) . ".value";
        }
        form_End();
        $HTML->box1_bottom();
        print "</td><td>\n";
        $HTML->box1_top(dgettext('tuleap-projectlinks', 'Set to defaults'));
        print "<div style='padding: 5px; border: solid thin;
            vertical-align: middle;'>";
        print dgettext('tuleap-projectlinks', 'Replace all details in this form with the default values') .
            ":<p>";
        form_genJSButton(
            dgettext('tuleap-projectlinks', 'Sub-Projects'),
            "if (confirm('" . dgettext('tuleap-projectlinks', 'Replace all details in this form with the default values') . "?')){" .
            $formRefs["name"] . "='" . dgettext('tuleap-projectlinks', 'Sub-Projects') . "';" .
            $formRefs["reverse_name"] . "='" . dgettext('tuleap-projectlinks', 'Parent Projects') . "';" .
            $formRefs["description"] . "='" . dgettext('tuleap-projectlinks', 'Projects in the programme') . "';" .
            /** **1 commented out for now - until we can decide how to deal with project links functionality
             * $formRefs["uri_plus"]."='/projects/\$projname/';".
             **/
            "}"
        );
        print "<p>";
        form_genJSButton(
            dgettext('tuleap-projectlinks', 'Related Projects'),
            "if (confirm('" . dgettext('tuleap-projectlinks', 'Replace all details in this form with the default values') . "?')){" .
            $formRefs["name"] . "='" . dgettext('tuleap-projectlinks', 'Related Projects') . "';" .
            $formRefs["reverse_name"] . "='" . dgettext('tuleap-projectlinks', 'Related Projects') . "';" .
            $formRefs["description"] . "='" . dgettext('tuleap-projectlinks', 'Projects using similar technology or sharing resources') . "';" .
            /** **1 commented out for now - until we can decide how to deal with project links functionality
             * $formRefs["uri_plus"]."='/projects/\$projname/';".
             **/
            "}"
        );
        print "</div><p>";
        $HTML->box1_bottom();
        print "</td></tr></table>\n";

        if (isset($link_type_id)) {
            $purifier = Codendi_HTMLPurifier::instance();
            // Display list of linked projects
            $HTML->box1_top('Projects linked');
            print $this->_admin_links_table($link_type_id);

            // Admin can add new link
            print '<form name="plugin_projectlinks_add_link" method="post" action="?func=pl_link_update">';
            print '<input type="hidden" name="link_type_id" value="' . $purifier->purify($link_type_id) . '" />';
            print '<input type="hidden" name="group_id" value="' . $purifier->purify($group_id) . '" />';
            print '<input type="hidden" name="disp" value="edit_link_type" />';
            print '<p><label for="plugin_projectlinks_link_project">' . dgettext('tuleap-projectlinks', 'Add new project:') . '</label>';
            print '<input type="text" name="target_group" value="' .
                dgettext('tuleap-projectlinks', 'Project name (search as you type)') .
                '" size="60" id="plugin_projectlinks_link_project" /></p>';
            print '<input type="submit" value="' . $GLOBALS['Language']->getText('global', 'btn_create') . '" />';
            print '</form>';
            $HTML->box1_bottom();

            $HTML->includeFooterJavascriptSnippet("new ProjectAutoCompleter('plugin_projectlinks_link_project', '" . util_get_dir_image_theme() . "', false);");
        }
        $HTML->box1_bottom();
    }

    //========================================================================
    public function _adminPage_ResyncTemplate($group_id, $template_id) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore, PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $hp = Codendi_HTMLPurifier::instance();
        // re-synchronise project links and types with originating template
        global $HTML, $Language;

        $HTML->box1_top(
            dgettext('tuleap-projectlinks', 'Project Links') .
            " " . $this->_icon('main') . " " .
            dgettext('tuleap-projectlinks', 'Re-Synchronise Project Links with Template')
        );
        print mkAH(
            "[" . $Language->getText('global', 'btn_cancel') . "]",
            $this->_adminURI() . "?group_id=$group_id"
        );
        // ==== compare link types ====
        print "<hr>\n";
        print "<h2>" . dgettext('tuleap-projectlinks', 'Additional &amp; Modified project link types') . "</h2>\n";
        $lt_tmplt = db_query("SELECT link_type_id, group_id, name,
            reverse_name, description, uri_plus
            FROM plugin_projectlinks_link_type
            WHERE (group_id = " . db_ei($template_id) . ");");
        print html_build_list_table_top(
            [
                dgettext('tuleap-projectlinks', 'Action'),
                dgettext('tuleap-projectlinks', 'Name'),
                dgettext('tuleap-projectlinks', 'Reverse Name'),
                dgettext('tuleap-projectlinks', 'Description'),
                dgettext('tuleap-projectlinks', 'Link URI (template)'),
            ],
            false, //links_arr
            false, //mass_change
            false //full_width
        );
        $typeMatch = [];
        $cnt       = 0;
        while ($ltr_tmplt = db_fetch_array($lt_tmplt)) {
            $cls = "class='" . html_get_alt_row_color($cnt++) . "'";
            print "<tr style=' vertical-align: top;'>\n";
            $diffs    = [];
            $rs_grp   = db_query("SELECT link_type_id, name, reverse_name,
                description, uri_plus FROM plugin_projectlinks_link_type
                WHERE ((group_id = " . db_ei($group_id) . ")
                    AND (name = '" . db_es($ltr_tmplt['name']) . "')
                    );");
            $basicURI = $this->_adminURI() .
                "?disp=resync_template" .
                "&amp;group_id=" . $hp->purify(urlencode($group_id)) .
                "&amp;template_id=" . $hp->purify(urlencode($template_id)) .
                "&amp;template_type_id=" . $hp->purify(urlencode($ltr_tmplt['link_type_id']));
            if (db_numrows($rs_grp) == 0) {
                // does not exist
                print "<td $cls style='text-align: center;
                        vertical-align: middle;'>" .
                    mkAH(
                        $this->_icon("add"),
                        $basicURI . "&amp;func=template_sync_type_add"
                    ) . "</td>";
            } else {
                // same name - any differences?
                $ltr_grp                               = db_fetch_array($rs_grp);
                $basicURI                             .= "&link_type_id=" . $hp->purify(urlencode($ltr_tmplt['link_type_id']));
                $typeMatch[$ltr_tmplt['link_type_id']] = $ltr_grp['link_type_id'];
                foreach (['reverse_name', 'description', 'uri_plus'] as $param) {
                    if ($ltr_tmplt[$param] <> $ltr_grp[$param]) {
                        $diffs[$param] = $ltr_grp[$param];
                    }
                }
                if (count($diffs) > 0) {
                    print "<td $cls>";
                    print "<table border='0' cellspacing='0' cellpadding='3'>
                        <tr><td>" .
                        mkAH(
                            dgettext('tuleap-projectlinks', 'update'),
                            $basicURI .
                            "&name=" . $hp->purify(urlencode($ltr_tmplt['name'])) .
                            "&reverse_name=" . $hp->purify(urlencode($ltr_tmplt['reverse_name'])) .
                            "&description=" . $hp->purify(urlencode($ltr_tmplt['description'])) .
                            "&uri_plus=" . $hp->purify(urlencode($ltr_tmplt['uri_plus'])) .
                            "&link_type_id=" . $hp->purify(urlencode($ltr_grp['link_type_id'])) .
                            "&func=pl_type_update"
                        ) .
                        "</td></tr><tr>" .
                        "<td style='text-align: right;'>" .
                        $this->_icon("arrow-right") .
                        "</td></tr></table>";
                    print "</td>";
                } else {
                    print "<td $cls style='text-align: center;
                        vertical-align: middle;'>" .
                        $this->_icon('matched') .
                        "</td>";
                }
            }
            print "<td $cls>";
            if (count($diffs) > 0) {
                print "<table border='0' cellspacing='0' cellpadding='3'>
                    <tr><td style='white-space: nowrap;'>" .
                    htmlentities($ltr_tmplt['name']) .
                    "&nbsp; &nbsp; &nbsp; " .
                    "<span style='font-style:italic;'>" .
                    dgettext('tuleap-projectlinks', 'project') .
                    ":</span></td></tr><tr>
                    <td style='text-align: right;font-style:italic;'>" .
                    dgettext('tuleap-projectlinks', 'template') .
                    ":</td></tr></table>";
            } else {
                print htmlentities($ltr_tmplt['name']);
            }
            print "</td>";
            foreach (['reverse_name', 'description', 'uri_plus'] as $param) {
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
                    print "</td></tr><tr><td style='$style'>" .
                        nz(htmlentities($ltr_tmplt[$param]), "&nbsp;") .
                        "</td></tr></table>";
                }
                print "</td>";
            }
            print "</tr>\n";
        }
        print "</TABLE>\n";

        // ==== compare link instances ====
        print "<hr>\n";
        print "<h2>" . dgettext('tuleap-projectlinks', 'Additional links to other projects') .
            "</h2>\n";
        $templLinks = db_query("
            SELECT plugin_projectlinks_relationship.link_type_id,
                name AS link_name, type, `groups`.group_id,
                group_name, unix_group_name, uri_plus,
                link_id, creation_date, master_group_id, target_group_id
            FROM plugin_projectlinks_relationship,
                plugin_projectlinks_link_type,`groups`
            WHERE (plugin_projectlinks_relationship.link_type_id
                    = plugin_projectlinks_link_type.link_type_id)
                AND (plugin_projectlinks_relationship.target_group_id
                    = `groups`.group_id)
                AND ((master_group_id = " . db_ei($template_id) . ")
                    AND (target_group_id <> " . db_ei($group_id) . ")
                    AND (status = 'A'))
            ORDER BY name, type, group_name;");
        if (db_numrows($templLinks) > 0) {
            print sprintf(dgettext('tuleap-projectlinks', 'Click %1$s to add the links from the template'), $this->_icon("add"));
            $type_missing = false;
            print html_build_list_table_top(
                [
                    dgettext('tuleap-projectlinks', 'Action'),
                    dgettext('tuleap-projectlinks', 'Link Type'),
                    dgettext('tuleap-projectlinks', 'project'),
                ],
                false, //links_arr
                false, //mass_change
                false //full_width
            );
            $basicURI = $this->_adminURI() .
                "?disp=resync_template" .
                "&amp;func=pl_link_update" .
                "&amp;group_id=$group_id" .
                "&amp;template_id=$template_id";
            $cnt      = 0;
            while ($row_templLinks = db_fetch_array($templLinks)) {
                $cls = "class='" . html_get_alt_row_color($cnt++) . "'";
                print "<tr valign='top'>";
                print "<td $cls  style='text-align: center; vertical-align: middle;'>";
                // is there a matching link in the project?
                if (isset($typeMatch[$row_templLinks['link_type_id']])) {
                    // we found a matching type
                    $findlinks = db_query("
                        SELECT creation_date
                        FROM plugin_projectlinks_relationship
                        WHERE ((master_group_id = " . db_ei($group_id) . ")
                            AND (target_group_id =
                            " . db_ei($row_templLinks['target_group_id']) . ")
                            AND (link_type_id =
                            " . db_ei($typeMatch[$row_templLinks['link_type_id']]) . ")
                        );");
                    if (db_numrows($findlinks) <= 0) {
                        print mkAH(
                            $this->_icon("add"),
                            $basicURI .
                            "&amp;target_group_id=" .
                            $hp->purify(urlencode($row_templLinks['target_group_id'])) .
                            "&amp;link_type_id=" .
                            $hp->purify(urlencode($typeMatch[$row_templLinks['link_type_id']]))
                        );
                    } else {
                        print $this->_icon('matched');
                    }
                } else {
                    $type_missing = true;
                    print dgettext('tuleap-projectlinks', '<a title=\'needs link type first\'>**</a>');
                }
                print "</td>";
                print "<td $cls>" . $hp->purify($row_templLinks['link_name']) . "</td>";
                print "<td $cls>" . $hp->purify($row_templLinks['group_name']) . "</td>";
                print "</tr>";
            }
            print "</TABLE>\n";
            if ($type_missing) {
                print "<div style='width: 30em'><hr><i>" .
                    dgettext('tuleap-projectlinks', '<a title=\'needs link type first\'>**</a>') . "</i> " .
                    sprintf(dgettext('tuleap-projectlinks', 'means that the link cannot be added because there is no matching link type defined in the project. Create the link type by clicking on <i>%1$s</i> in <i>%2$s</i>.'), $this->_icon("add"), dgettext('tuleap-projectlinks', 'Additional &amp; Modified project link types')) .
                    "</div>";
            }
        }
        $HTML->box1_bottom();
    }

    //========================================================================
    public function _link_unique_update($group_id, $target_group_id, $link_type_id, $link_id = null) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore, PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        // update link, but check the change would not create a duplicate
        // (same target project and link type)
        global $Language;
        $hp = Codendi_HTMLPurifier::instance();

        $targetProject = ProjectManager::instance()->getProject($target_group_id);

        $feedback = "";
        $pfcheck  = db_query("SELECT link_type_id FROM
            plugin_projectlinks_relationship
            WHERE (
                (target_group_id=" . db_ei($target_group_id) . ")
                AND (master_group_id=" . db_ei($group_id) . ")
                AND (link_type_id=" . db_ei($link_type_id) . ")
                " . (is_null($link_id) ? "" : " AND (link_id<>" . db_ei($link_id) . ")") . "
            )");
        if (db_numrows($pfcheck) > 0) {
            $feedback = sprintf(dgettext('tuleap-projectlinks', 'Update cancelled (it would create a duplicate link to "%1$s")'), $hp->purify($targetProject->getPublicName()));
        } else {
            $updates = [
                "link_type_id" => db_ei($link_type_id),
                "target_group_id" => db_ei($target_group_id),
                "master_group_id" => db_ei($group_id),
            ];
            if (is_null($link_id)) {
                // new item - set date, otherwise leave it alone
                $updates["creation_date"] = time();
            }

            if (
                update_database(
                    "plugin_projectlinks_relationship",
                    $updates,
                    is_null($link_id) ? "" : "link_id=" . db_ei($link_id)
                )
            ) {
                $feedback = sprintf(dgettext('tuleap-projectlinks', 'update OK (%1$s)'), $hp->purify($targetProject->getPublicName())) . ' ';
            } else {
                $feedback = sprintf(dgettext('tuleap-projectlinks', 'update failed [%2$s] (MySQL reason: "%1$s")'), db_error(), $hp->purify($targetProject->getPublicName()));
            }
        }
        return $feedback;
    }

    /**
     * Display the project linked by the current projet to update or delete them
     *
     * @return string
     */
    public function _admin_links_table($link_type_id) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore, PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $hp   = Codendi_HTMLPurifier::instance();
        $html = '';

        $dao   = $this->getProjectLinksDao();
        $links = $dao->searchLinksByType($link_type_id);

        if ($links->rowCount() > 0) {
            $html .= html_build_list_table_top(
                [
                    dgettext('tuleap-projectlinks', 'Name'), '',
                ],
                false,
                false,
                false
            );

            foreach ($dao->searchLinksByType($link_type_id) as $row) {
                $html .= '<tr>';

                // Name
                $html .= '<td>' . $hp->purify($row['group_name']) . '</td>';

                // Delete
                $url   = "?func=pl_link_delete&amp;disp=edit_link_type&amp;link_type_id=" . urlencode($link_type_id) . "&amp;group_id=" . urlencode($row['master_group_id']) . "&amp;link_id=" . urlencode($row['link_id']);
                $warn  = dgettext('tuleap-projectlinks', 'Delete project link');
                $alt   = dgettext('tuleap-projectlinks', 'Delete project link');
                $html .= '<td>' . html_trash_link($url, $warn, $alt) . '</td>';

                $html .= '</tr>';
            }

            $html .= '</table>';
        }
        return $html;
    }

    public function registerProjectCreationEvent(RegisterProjectCreationEvent $event): void
    {
        // called during new project creation to inherit project links and
        // types from a template
        $group_id    = (int) $event->getJustCreatedProject()->getID();
        $template_id = (int) $event->getTemplateProject()->getID();

        // 1. copy link types from the template into the new project
        $db_res = db_query("SELECT * FROM plugin_projectlinks_link_type
                            WHERE (group_id = " . db_ei($template_id) . ");");
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
                " . db_ei($group_id) . ",
                    '" . db_es($row['name']) . "',
                    '" . db_es($row['reverse_name']) . "',
                    '" . db_es($row['description']) . "',
                    '" . db_es($row['uri_plus']) . "'
                );");
        }

        // 2. copy project links where the template is master
        $db_res = db_query("SELECT name, target_group_id
            FROM plugin_projectlinks_relationship,
                plugin_projectlinks_link_type
            WHERE ((plugin_projectlinks_relationship.link_type_id =
                    plugin_projectlinks_link_type.link_type_id)
                AND (master_group_id = " . db_ei($template_id) . "));");
        while ($row = db_fetch_array($db_res)) {
            $db_res2 = db_query("SELECT link_type_id FROM
                plugin_projectlinks_link_type
                WHERE ((group_id = " . db_ei($group_id) . ")
                    AND (name='" . db_es($row['name']) . "'));");
            if (db_numrows($db_res2) > 0) {
                $row2 = db_fetch_array($db_res2);
                db_query("INSERT INTO plugin_projectlinks_relationship (
                        link_type_id,
                        master_group_id,
                        target_group_id,
                        creation_date
                    ) VALUES (
                        " . db_ei($row2['link_type_id']) . ",
                        " . db_ei($group_id) . ",
                        " . db_ei($row['target_group_id']) . ",
                        " . db_ei(time()) . "
                    );");
            }
        }

        // 3. copy project links where the template is target - NB they are
        // made in the master project
        $db_res = db_query("SELECT link_type_id, master_group_id
            FROM plugin_projectlinks_relationship
            WHERE (target_group_id = " . db_ei($template_id) . ");");
        while ($row = db_fetch_array($db_res)) {
            db_query("INSERT INTO plugin_projectlinks_relationship (
                    link_type_id,
                    master_group_id,
                    target_group_id,
                    creation_date
                ) VALUES (
                    " . db_ei($row['link_type_id']) . ",
                    " . db_ei($row['master_group_id']) . ",
                    " . db_ei($group_id) . ",
                    " . db_ei(time()) . "
                );");
        }
    }

    //========================================================================
    public function registerProjectAbandon($params)
    {
        // deletes all project link information for the passed group -
        // usually when a user declines to accept a new project at the
        //  final step

        $group_id = $params['group_id'];
        db_query("DELETE FROM plugin_projectlinks_link_type WHERE group_id=" . db_ei($group_id));
        db_query("DELETE FROM plugin_projectlinks_relationship WHERE ((master_group_id=" . db_ei($group_id) . ") OR (target_group_id=" . db_ei($group_id) . "))");
    }

    public function widgetInstance(GetWidget $get_widget_event)
    {
        if ($get_widget_event->getName() === 'projectlinkshomepage') {
            $get_widget_event->setWidget(new ProjectLinks_Widget_HomePageLinks($this, Codendi_HTMLPurifier::instance()));
        }
    }

    public function getProjectWidgetList(GetProjectWidgetList $event)
    {
        $event->addWidget('projectlinkshomepage');
    }

    public function uninstall()
    {
        $this->removeOrphanWidgets(['projectlinkshomepage']);
    }

    /**
     * Return ProjectLinksDao
     *
     * @return ProjectLinksDao
     */
    public function getProjectLinksDao()
    {
        include_once 'ProjectLinksDao.class.php';
        return new ProjectLinksDao(CodendiDataAccess::instance());
    }

    public function collectProjectAdminNavigationItems(NavigationPresenter $presenter)
    {
        $project_id = $presenter->getProjectId();
        if (PluginManager::instance()->isPluginAllowedForProject($this, $project_id)) {
            $presenter->addDropdownItem(
                NavigationPresenterBuilder::OTHERS_ENTRY_SHORTNAME,
                new NavigationDropdownItemPresenter(
                    dgettext('tuleap-projectlinks', 'Project Links Configuration'),
                    $this->_adminURI() . '?' . http_build_query(
                        ['group_id' => $project_id, 'pane' => 'project_links']
                    )
                )
            );
        }
    }
}
