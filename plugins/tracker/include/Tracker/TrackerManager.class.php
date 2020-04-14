<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Event\Events\ProjectProviderEvent;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupRepresentationBuilder;
use Tuleap\Tracker\Admin\GlobalAdminController;
use Tuleap\Tracker\Creation\TrackerCreationController;
use Tuleap\Tracker\Creation\TrackerCreationDataChecker;
use Tuleap\Tracker\Creation\TrackerCreationHasFailedException;
use Tuleap\Tracker\Creation\TrackerCreator;
use Tuleap\Tracker\ForgeUserGroupPermission\TrackerAdminAllProjects;
use Tuleap\Tracker\PermissionsPerGroup\TrackerPermissionPerGroupJSONRetriever;
use Tuleap\Tracker\PermissionsPerGroup\TrackerPermissionPerGroupPermissionRepresentationBuilder;
use Tuleap\Tracker\PermissionsPerGroup\TrackerPermissionPerGroupRepresentationBuilder;
use Tuleap\Tracker\TrackerIsInvalidException;

class TrackerManager implements Tracker_IFetchTrackerSwitcher
{
    public const DELETED_TRACKERS_TEMPLATE_NAME = 'deleted_trackers';

    /**
     * Check that the service is used and the plugin is allowed for project $project
     * if it is not the case then exit with an error
     *
     *
     * @return bool true if success. Otherwise the process terminates.
     */
    public function checkServiceEnabled(Project $project, Codendi_Request $request)
    {
        if ($project->usesService('plugin_tracker')) {
            return true;
        }

        header("HTTP/1.0 404 Not Found");
        if (!$request->isAjax()) {
            $GLOBALS['Response']->addFeedback('error', "The project doesn't use the 'tracker v5' service");
            $GLOBALS['HTML']->redirect('/projects/' . $project->getUnixName() . '/');
        }
        exit();
    }

    /**
     * Check that tracker can be accessed by user
     *
     * @param Tracker         $tracker
     * @param PFUser            $user
     *
     * @throws Tracker_CannotAccessTrackerException
     */
    public function checkUserCanAccessTracker($tracker, $user, Codendi_Request $request)
    {
        $this->checkServiceEnabled($tracker->getProject(), $request);

        if (! $tracker->isActive()) {
            throw new Tracker_CannotAccessTrackerException($GLOBALS['Language']->getText('plugin_tracker_common_type', 'tracker_not_exist'));
        }
        if (! $tracker->userCanView($user)) {
            if ($user->isAnonymous()) {
                $url_redirect = new URLRedirect(EventManager::instance());

                throw new Tracker_CannotAccessTrackerException(
                    $GLOBALS['Language']->getText(
                        'plugin_tracker_common_type',
                        'no_view_permission_anonymous',
                        array($url_redirect->buildReturnToLogin($_SERVER))
                    )
                );
            } else {
                throw new Tracker_CannotAccessTrackerException($GLOBALS['Language']->getText('plugin_tracker_common_type', 'no_view_permission'));
            }
        }
    }

    /**
     * Propagate process dispatch to sub-tracker elements
     *
     */
    protected function processSubElement(Tracker_Dispatchable_Interface $object, HTTPRequest $request, PFUser $user)
    {
        // Tracker related check
        $this->checkUserCanAccessTracker($object->getTracker(), $user, $request);
        $GLOBALS['group_id'] = $object->getTracker()->getGroupId();

        $event = new ProjectProviderEvent($object->getTracker()->getProject());
        EventManager::instance()->processEvent($event);

        // Need specific treatment for artifact
        // TODO: transfer in Tracker_Artifact::process
        if ($object instanceof Tracker_Artifact) {
            $artifact = $object;
            if ((int) $request->get('aid')) {
                if ($artifact->userCanView($user)) {
                    $artifact->process($this, $request, $user);
                } else {
                    if ($user->isAnonymous()) {
                        $url_redirect = new URLRedirect(EventManager::instance());

                        $GLOBALS['Response']->addFeedback(
                            'error',
                            $GLOBALS['Language']->getText(
                                'plugin_tracker_common_type',
                                'no_view_permission_on_artifact_anonymous',
                                array($url_redirect->buildReturnToLogin($_SERVER))
                            ),
                            CODENDI_PURIFIER_LIGHT
                        );
                    } else {
                        $GLOBALS['Response']->addFeedback(
                            'error',
                            $GLOBALS['Language']->getText('plugin_tracker_common_type', 'no_view_permission_on_artifact')
                        );
                    }

                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?tracker=' . $artifact->getTrackerId());
                }
            } elseif ($request->get('func') == 'new-artifact-link') {
                echo '<html>';
                echo '<head>';
                $GLOBALS['HTML']->displayStylesheetElements(array());
                $GLOBALS['HTML']->displayJavascriptElements(array());
                echo '</head>';

                echo '<body>';
                echo '<div class="contenttable">';

                $project = $artifact->getTracker()->getProject();
                echo $this->fetchTrackerSwitcher($user, ' ', $project, null);
            } elseif ((int) $request->get('link-artifact-id')) {
                $artifact->getTracker()->displayAReport($this, $request, $user);
            }
        } else {
            $object->process($this, $request, $user);
        }
    }

    /**
     * Controler
     *
     * @param HTTPRequest       $request The request
     * @param PFUser            $user    The user that execute the request
     *
     * @return void
     */
    public function process($request, $user)
    {
        $url = $this->getUrl();

        try {
            $object = $url->getDispatchableFromRequest($request, $user);
            $this->processSubElement($object, $request, $user);
        } catch (Tracker_ResourceDoesntExistException $e) {
             exit_error($GLOBALS['Language']->getText('global', 'error'), $e->getMessage());
        } catch (Tracker_CannotAccessTrackerException $e) {
            if (isset($object) && ! $request->isAjax()) {
                $GLOBALS['Response']->addFeedback('error', $e->getMessage(), CODENDI_PURIFIER_LIGHT);
                $this->displayAllTrackers($object->getTracker()->getProject(), $user);
            } else {
                $GLOBALS['Response']->send401UnauthorizedHeader();
            }
        } catch (Tracker_NoMachingResourceException $e) {
            //show, admin all trackers
            if ((int) $request->get('group_id')) {
                $group_id = (int) $request->get('group_id');
                if ($project = $this->getProject($group_id)) {
                    if ($this->checkServiceEnabled($project, $request)) {
                        switch ($request->get('func')) {
                            case 'docreate':
                                if ($this->userCanCreateTracker($group_id)) {
                                      $this->doCreateTracker($project, $request);
                                } else {
                                    $this->redirectToTrackerHomepage($group_id);
                                }
                                break;
                            case 'create':
                                if ($this->userCanCreateTracker($group_id)) {
                                    $this->displayCreateTracker($project, $request);
                                } else {
                                    $this->redirectToTrackerHomepage($group_id);
                                }
                                break;
                            case 'check_ugroup_consistency':
                                $tracker = $this->getTrackerFactory()->getTrackerByid($request->get('template_tracker_id'));
                                if (! $tracker) {
                                    return;
                                }

                                $checker = new Tracker_UgroupPermissionsConsistencyChecker(
                                    new Tracker_UgroupPermissionsGoldenRetriever(
                                        new Tracker_PermissionsDao(),
                                        new UGroupManager()
                                    ),
                                    new UGroupManager(),
                                    new Tracker_UgroupPermissionsConsistencyMessenger()
                                );
                                $checker->checkConsistency($tracker, $project);
                                break;
                            case 'csvimportoverview':
                                $this->displayCSVImportOverview($project, $group_id, $user);
                                break;
                            case 'restore-tracker':
                                if ($this->userIsTrackerAdmin($project, $user)) {
                                    $tracker_id   = $request->get('tracker_id');
                                    $group_id     = $request->get('group_id');
                                    $token      = new CSRFSynchronizerToken('/tracker/admin/restore.php');
                                    $token->check();
                                    $tracker = $this->getTrackerFactory()->getTrackerById($tracker_id);
                                    if ($tracker === null) {
                                        throw new RuntimeException('Tracker does not exist');
                                    }
                                    $tracker_name = $tracker->getName();
                                    $this->restoreDeletedTracker($tracker_id);
                                    $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_tracker', 'info_tracker_restored', $tracker_name));
                                    $GLOBALS['Response']->redirect('/tracker/admin/restore.php');
                                } else {
                                    $this->redirectToTrackerHomepage($group_id);
                                }
                                break;

                            case 'permissions-per-group':
                                if (! $request->getCurrentUser()->isAdmin($request->getProject()->getID())) {
                                    $GLOBALS['Response']->send400JSONErrors(
                                        array(
                                            'error' => dgettext(
                                                "tuleap-tracker",
                                                "You don't have permissions to see user groups."
                                            )
                                        )
                                    );
                                }

                                $ugroup_manager       = new UGroupManager();
                                $permission_retriever = new TrackerPermissionPerGroupJSONRetriever(
                                    new TrackerPermissionPerGroupRepresentationBuilder(
                                        TrackerFactory::instance(),
                                        new TrackerPermissionPerGroupPermissionRepresentationBuilder(
                                            $ugroup_manager,
                                            new PermissionPerGroupUGroupRepresentationBuilder(
                                                $ugroup_manager
                                            )
                                        )
                                    )
                                );

                                $permission_retriever->retrieve(
                                    $request->getProject(),
                                    $request->get('selected_ugroup_id')
                                );

                                break;
                            default:
                                $this->displayAllTrackers($project, $user);
                                break;
                        }
                    }
                }
            }
        }
    }

    private function redirectToTrackerHomepage($project_id)
    {
        $GLOBALS['Response']->addFeedback(
            Feedback::ERROR,
            $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied')
        );

        $url = $this->getTrackerHomepageURL($project_id);

        $GLOBALS['Response']->redirect($url);
    }

    private function getTrackerHomepageURL($project_id)
    {
        return TRACKER_BASE_URL . '/?' . http_build_query(array(
            'group_id' => $project_id
        ));
    }

    /**
     * Restore a deleted tracker.
     *
     * @param int $tracker_id ID of the tracker marked as deleted
     *
     * @return bool
     */
    private function restoreDeletedTracker($tracker_id)
    {
        return $this->getTrackerFactory()->restoreDeletedTracker($tracker_id);
    }

    public function displayHeader($project, $title, $breadcrumbs, $toolbar, array $params)
    {
        $breadcrumbs = array_merge(
            [
                $this->getServiceTrackerBreadcrumb($project)
            ],
            $breadcrumbs
        );

        if ($service = $project->getService('plugin_tracker')) {
            $service->displayHeader($title, $breadcrumbs, $toolbar, $params);
            echo '<div id="submit-new-by-mail-popover-container"></div>';
        }
    }

    private function getServiceTrackerBreadcrumb(Project $project)
    {
        $service_tracker_breadcrumb = [
            'title'     => $GLOBALS['Language']->getText('plugin_tracker', 'trackers'),
            'url'       => TRACKER_BASE_URL . '/?group_id=' . $project->getID(),
            'icon_name' => 'fa-list-ol'
        ];

        if ($this->getCurrentUser()->isAdmin($project->getID())) {
            $service_tracker_breadcrumb['sub_items'] = [
                [
                    'title' => $GLOBALS['Language']->getText('global', 'Administration'),
                    'url'   => GlobalAdminController::getTrackerGlobalAdministrationURL($project)
                ]
            ];
        }

        return $service_tracker_breadcrumb;
    }

    private function getCurrentUser()
    {
        return UserManager::instance()->getCurrentUser();
    }

    public function displayFooter($project)
    {
        if ($service = $project->getService('plugin_tracker')) {
            $service->displayFooter();
        }
    }

    public function doCreateTracker(Project $project, Codendi_Request $request)
    {
        $new_tracker = null;

        $name          = trim($request->get('name'));
        $description   = trim($request->get('description'));
        $itemname      = trim($request->get('itemname'));
        $color         = null;
        $atid_template = $request->getValidated('atid_template', 'uint', 0);

        if (! $request->existAndNonEmpty('create_mode')) {
            return;
        }

        // First try XML
        if ($request->get('create_mode') == 'xml') {
            $vFile = new Valid_File('tracker_new_xml_file');
            $vFile->required();
            if (! $request->validFile($vFile)) {
                $GLOBALS['Response']->addFeedback(
                    Feedback::ERROR,
                    dgettext("tuleap-tracker", "The provided file is invalid")
                );
                $this->displayCreateTracker($project, $request, $name, $description, $itemname);
                return;
            }

            try {
                $new_tracker = $this->getTrackerCreator()->createTrackerFromXml(
                    $project,
                    $_FILES["tracker_new_xml_file"]["tmp_name"],
                    $name,
                    $description,
                    $itemname,
                    null
                );
            } catch (TrackerIsInvalidException $exception) {
                $GLOBALS['Response']->addFeedback(Feedback::ERROR, $exception->getTranslatedMessage());
            } catch (Tracker_Exception | TrackerCreationHasFailedException $exception) {
                $GLOBALS['Response']->addFeedback(Feedback::ERROR, $exception->getMessage());
            }
        } elseif ($request->get('create_mode') == 'tv3') {
            $atid = $request->get('tracker_new_tv3');
            $user = UserManager::instance()->getCurrentUser();
            $new_tracker = $this->getTrackerFactory()->createFromTV3($user, $atid, $project, $name, $description, $itemname);
        } elseif ($request->get('create_mode') == 'migrate_from_tv3') {
            $tracker_id = $request->get('tracker_new_tv3');
            if ($this->getTV3MigrationManager()->askForMigration($project, $tracker_id, $name, $description, $itemname)) {
                $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?group_id=' . $project->group_id);
            }
        } else {
            try {
                $user = UserManager::instance()->getCurrentUser();
                $new_tracker = $this->getTrackerCreator()->duplicateTracker($project, $name, $name, $itemname, $color, $atid_template, $user);
            } catch (\Tuleap\Tracker\Creation\TrackerCreationHasFailedException $exception) {
                $GLOBALS['Response']->addFeedback(
                    Feedback::ERROR,
                    dgettext("tuleap-tracker", "Tracker creation has failed.")
                );
            } catch (\Tuleap\Tracker\TrackerIsInvalidException $exception) {
                $GLOBALS['Response']->addFeedback(
                    Feedback::ERROR,
                    $exception->getTranslatedMessage()
                );
            }
        }

        if ($new_tracker) {
            $GLOBALS['Response']->redirect(
                TRACKER_BASE_URL . '/?group_id=' . urlencode($project->group_id) . '&tracker=' . urlencode($new_tracker->id)
            );
        } else {
            $tracker_template = $this->getTrackerFactory()->getTrackerById($atid_template);
            $this->displayCreateTracker($project, $request, $name, $description, $itemname, $tracker_template);
        }
    }

    /**
     * Display tracker creation interface
     *
     * @param String $name
     * @param String $description
     * @param String $itemname
     */
    public function displayCreateTracker(
        Project $project,
        Codendi_Request $request,
        $name = '',
        $description = '',
        $itemname = '',
        ?Tracker $tracker_template = null
    ) {
        global $Language;
        $breadcrumbs = array(
            array(
                'title' => $GLOBALS['Language']->getText('plugin_tracker_index', 'create_new_tracker'),
                'url'   => TRACKER_BASE_URL . '/?group_id=' . $project->group_id . '&amp;func=create'
            )
        );
        $toolbar = [];
        $params  = [];
        $this->displayHeader($project, 'Trackers', $breadcrumbs, $toolbar, $params);

        $hp = Codendi_HTMLPurifier::instance();

        echo '<div class="alert alert-error">';
        echo dgettext('tuleap-tracker', 'This page is deprecated and will be removed soon. You should switch to the new tracker creation flow.');
        echo '<a href="' . TrackerCreationController::getRouteToTrackerCreationController($project) . '" class="btn btn-primary tracker-creation-link">
                <i class="fa fa-long-arrow-right"></i> ' . dgettext('tuleap-tracker', 'Switch to new tracker creation flow') .
            '</a>';
        echo '</div>';
        echo '<h2>' . $Language->getText('plugin_tracker_include_type', 'create_tracker') . '</h2>';

        echo '<form name="form_create" method="post" enctype="multipart/form-data" id="tracker_create_new">
          <input type="hidden" name="group_id" value="' . $project->getId() . '">
          <input type="hidden" name="func" value="docreate">

          <table>
          <tr valign="top"><td style="padding-right:2em; border-right: 1px solid #eee;">';

        echo '<p>' . $Language->getText('plugin_tracker_include_type', 'choose_creation') . '</p>';

        $create_mode = $request->get('create_mode');
        $this->displayCreateTrackerFromTemplate($create_mode, $project, $tracker_template);
        $this->displayCreateTrackerFromXML($create_mode, $project);
        $this->displayCreateTrackerFromTV3($create_mode, $project, $request->get('tracker_new_tv3'));
        $this->displayMigrateFromTV3Option($create_mode, $project, $request->get('tracker_new_tv3'));

        echo '</td><td style="padding-left:2em;">';

        echo '<p>' . $Language->getText('plugin_tracker_include_type', 'create_tracker_fill_name') . '</p>
          <p>
              <label for="newtracker_name"><b>' . $Language->getText('plugin_tracker_include_artifact', 'name') . '</b>: <font color="red">*</font></label><br />
              <input type="text" name="name" id="newtracker_name" value="' . $hp->purify($name, CODENDI_PURIFIER_CONVERT_HTML) . '" required="required" />
          </p>
          <p>
              <label for="newtracker_description"><b>' . $Language->getText('plugin_tracker_include_artifact', 'desc') . '</b>:<br />
              <textarea id="newtracker_description" name="description" rows="3" cols="50">' . $hp->purify($description, CODENDI_PURIFIER_CONVERT_HTML) . '</textarea>
          </p>
          <p>
              <label for="newtracker_itemname"><b>' . $Language->getText('plugin_tracker_include_type', 'short_name') . '</b>: <font color="red">*</font></label><br />
              <input type="text" id="newtracker_itemname" name="itemname" value="' . $hp->purify($itemname, CODENDI_PURIFIER_CONVERT_HTML) . '" required="required" /><br />
              <span style="color:#999;">' . $Language->getText('plugin_tracker_include_type', 'avoid_spaces') . '</span>
          </p>';

        echo '<div id="check_consistency_feedback"></div>';
        echo '<input type="submit" name="Create" value="' . $Language->getText('global', 'btn_create') . '" id="create_new_tracker_btn" class="btn">';

        echo '</td></tr></table></form>';

        $this->displayFooter($project);
    }

    public function displayCreateTrackerFromTemplate($requested_create_mode, Project $project, ?Tracker $tracker_template = null)
    {
        $hp = Codendi_HTMLPurifier::instance();

        $js = '';
        $trackers = $this->getTrackerFactory()->getTrackersByGroupId(100);
        foreach ($trackers as $tracker) {
            $js .= '<option value="' . $tracker->getId() . '">' . $hp->purify($tracker->getName()) . '</option>';
        }
        $js = "codendi.tracker.defaultTemplates = '" . $hp->purify($js, CODENDI_PURIFIER_JS_QUOTE) . "';";
        $GLOBALS['Response']->includeFooterJavascriptSnippet($js);

        $gf = new GroupFactory();
        $radio = $this->getCreateTrackerRadio('gallery', $requested_create_mode);
        echo '<h3><label>' . $radio . $GLOBALS['Language']->getText('plugin_tracker_include_type', 'from_tmpl') . '</label></h3>';

        echo '<div class="tracker_create_mode">';
        echo '<noscript>Project Id: <input type="text" name="group_id_template" value=""><br/>Tracker Id: <input type="text" name="atid_template" value=""></noscript>';

        echo '<table>';

        echo '<tr>';
        echo '<th align="left">' . $GLOBALS['Language']->getText('plugin_tracker_include_type', 'tmpl_src_prj') . '</th>';
        echo '<th align="left">' . $GLOBALS['Language']->getText('plugin_tracker_include_type', 'tmpl_src_trk') . '</th>';
        echo '</tr>';

        echo '<tr>';
        echo '<td valign="top">';

        $group_id_template = 100;
        $atid_template     = -1;
        if ($tracker_template) {
            $group_id_template = $tracker_template->getProject()->getID();
            $atid_template     = $tracker_template->getId();
        }
        $selectedHtml = 'selected="selected"';

        echo '<select name="group_id_template" size="15" id="tracker_new_project_list" autocomplete="off">';

        echo '<optgroup label="' . _('Project templates') . '">';
        echo '<option value="100" ' . ($group_id_template == 100 ? $selectedHtml : '') . '>' . $GLOBALS['Language']->getText('plugin_tracker_include_type', 'tmpl_src_prj_default') . '</option>';
        foreach (ProjectManager::instance()->getSiteTemplates() as $template) {
            if ((int) $template->getID() === Project::ADMIN_PROJECT_ID) {
                continue;
            }
            echo '<option value="' . (int) $template->getID() . '">' . $hp->purify($template->getPublicName()) . '</option>';
        }
        echo '</optgroup>';

        echo '<optgroup label="' . $GLOBALS['Language']->getText('plugin_tracker_include_type', 'tmpl_src_prj_my') . '">';
        $project_selected = false;
        $results = $gf->getMemberGroups();
        while ($row = db_fetch_array($results)) {
            $selected = '';
            if ($group_id_template == $row['group_id']) {
                $selected = $selectedHtml;
                $project_selected = true;
            }
            echo '<option value="' . $hp->purify($row['group_id']) . '" ' . ($group_id_template == $row['group_id'] ? $selectedHtml : '') . '>' . $hp->purify($row['group_name']) . '</option>';
        }
        echo '</optgroup>';

        $hide  = 'style="display:none;"';
        $other = '';
        if ($tracker_template && !$project_selected) {
            $hide = '';
            $other .= '<option value="' . (int) $tracker_template->getProject()->getID() . '" ' . $selectedHtml . '>';
            $other .= $hp->purify($tracker_template->getProject()->getPublicName(), CODENDI_PURIFIER_CONVERT_HTML);
            $other .= '</option>';
        }
        echo '<optgroup id="tracker_new_other" ' . $hide . ' label="' . $GLOBALS['Language']->getText('plugin_tracker_include_type', 'tmpl_src_prj_other') . '">';
        echo $other;
        echo '</optgroup>';

        echo '</select>';

        echo '<br/>' . $GLOBALS['Language']->getText('plugin_tracker_include_type', 'tmpl_src_autocomplete_desc') . '<br /><input type="text" name="tracker_new_prjname" id="tracker_new_prjname" placeholder="' . $GLOBALS['Language']->getText('plugin_tracker_include_type', 'tmpl_src_autocomplete_hint') . '" />';

        echo '</td>';

        echo '<td valign="top">';
        echo '<select name="atid_template" size="15" id="tracker_list_trackers_from_project">';
        $trackers = $this->getTrackerFactory()->getTrackersByGroupId($group_id_template);
        if (count($trackers) > 0) {
            foreach ($trackers as $tracker) {
                echo '<option value="' . $tracker->getId() . '" ' . ($atid_template == $tracker->getId() ? $selectedHtml : '') . '>' . $hp->purify($tracker->getName()) . '</option>';
            }
        } else {
            echo '<option>' . $GLOBALS['Language']->getText('plugin_tracker_include_type', 'tmpl_src_no_trk') . '</option>';
        }
        echo '</select>';

        echo '</td>';
        echo '</tr>';
        echo '</table>';

        echo '</div>';
    }

    public function displayCreateTrackerFromXML($requested_create_mode, Project $project)
    {
        $radio = $this->getCreateTrackerRadio('xml', $requested_create_mode);
        echo '<h3><label>' . $radio . $GLOBALS['Language']->getText('plugin_tracker_include_type', 'from_xml') . '</label></h3>
              <div class="tracker_create_mode">
                <p>' . $GLOBALS['Language']->getText('plugin_tracker_include_type', 'from_xml_desc') . '</p>
                <input type="file" name="tracker_new_xml_file" id="tracker_new_xml_file" />

              </div>';
    }

    private function getTrackerCreator(): TrackerCreator
    {
        return TrackerCreator::build();
    }

    private function getTrackersV3ForProject(Project $project)
    {
        if ($project->usesService('tracker')) {
            $atf         = new ArtifactTypeFactory($project);
            return $atf->getArtifactTypes();
        }

        return null;
    }

    private function displayCreateTrackerFromTV3($requested_create_mode, Project $project, $requested_template_id)
    {
        $trackers_v3 = $this->getTrackersV3ForProject($project);

        if ($trackers_v3) {
            $radio = $this->getCreateTrackerRadio('tv3', $requested_create_mode);
            echo $this->getSelectBoxForTV3($requested_template_id, $radio, $trackers_v3, $GLOBALS['Language']->getText('plugin_tracker_include_type', 'from_tv3'));
        }
    }

    private function getSelectBoxForTV3($requested_template_id, $radio, array $trackers_v3, $label)
    {
        $html    = '';
        $hp      = Codendi_HTMLPurifier::instance();
        $html   .= '<h3><label>' . $radio . $label . '</label></h3>';
        $html   .= '<div class="tracker_create_mode">';
        $checked = $requested_template_id ? '' : 'checked="checked"';

        foreach ($trackers_v3 as $tracker_v3) {
            $html .= '<p>';
            $html .= '<label>';
            if ($requested_template_id == $tracker_v3->getID()) {
                $checked = 'checked="checked"';
            }
            $html .= '<input type="radio" name="tracker_new_tv3" value="' . $tracker_v3->getID() . '" ' . $checked . ' />';
            $html .= $hp->purify(SimpleSanitizer::unsanitize($tracker_v3->getName()), CODENDI_PURIFIER_CONVERT_HTML);
            $html .= '</label>';
            $html .= '</p>';
            $checked = '';
        }
        $html .= '</div>';

        return $html;
    }

    public function getCreateTrackerRadio($create_mode, $requested_create_mode)
    {
        $checked = '';
        if (! $requested_create_mode) {
            $requested_create_mode = 'gallery';
        }
        if ($create_mode == $requested_create_mode) {
            $checked = 'checked="checked"';
        }
        return '<input type="radio" name="create_mode" value="' . $create_mode . '" ' . $checked . ' />';
    }

    /**
     * Display all trackers of project $project that $user is able to see
     *
     * @param Project $project The project
     * @param PFUser    $user    The user
     *
     * @return void
     */
    public function displayAllTrackers($project, $user)
    {
        $hp          = Codendi_HTMLPurifier::instance();
        $breadcrumbs = array();
        $html        = '';
        $trackers    = $this->getTrackerFactory()->getTrackersByGroupId($project->group_id);

        $toolbar = [];

        if (HTTPRequest::instance()->isAjax()) {
            $http_content = '';
            foreach ($trackers as $tracker) {
                if ($tracker->userCanView($user)) {
                    $http_content .= '<option value="' . $tracker->getId() . '">' . $hp->purify($tracker->getName()) . '</option>';
                }
            }
            if ($http_content) {
                echo $http_content;
            } else {
                echo '<option>' . $GLOBALS['Language']->getText('plugin_tracker_include_type', 'tmpl_src_no_trk') . '</option>';
            }
            echo $html;
        } else {
            $params = array();

            if ($this->userIsTrackerAdmin($project, $user)) {
                $this->informUserOfOngoingMigrations($project);
            }

            $this->displayHeader($project, $GLOBALS['Language']->getText('plugin_tracker', 'trackers'), $breadcrumbs, $toolbar, $params);
            $html .= '<p>';
            if (count($trackers)) {
                $html .= $GLOBALS['Language']->getText('plugin_tracker_index', 'choose_tracker');
            } else {
                $html .= $GLOBALS['Language']->getText('plugin_tracker_index', 'no_accessible_trackers_msg');
            }
            if ($this->userCanCreateTracker($project->group_id, $user)) {
                $html .= '<br /><a id="tracker_createnewlink"  data-test="new-tracker-creation" href="' . TRACKER_BASE_URL . '/' .
                    urlencode($project->getUnixNameLowerCase()) . '/new">';
                $html .= $GLOBALS['HTML']->getImage('ic/add.png', ['alt' => 'add']) . ' ';
                $html .= $GLOBALS['Language']->getText('plugin_tracker_index', 'create_new_tracker');
                $html .= '</a>';
            }
            $html .= '</p>';
            foreach ($trackers as $tracker) {
                if ($this->trackerCanBeDisplayed($tracker, $user)) {
                    $html .= '<dt>';

                    $used_in_other_services_infos = $tracker->getInformationsFromOtherServicesAboutUsage();

                    if ($tracker->userCanDeleteTracker()) {
                        if ($used_in_other_services_infos['can_be_deleted']) {
                            $html .= '<div style="float:right;">
                                    <a href="' . TRACKER_BASE_URL . '/?tracker=' . $tracker->id . '&amp;func=delete"
                                       onclick="return confirm(\'Do you want to delete this tracker?\');"
                                       title=" ' . $GLOBALS['Language']->getText('plugin_tracker', 'delete_tracker', array($hp->purify($tracker->name, CODENDI_PURIFIER_CONVERT_HTML))) . '">';
                            $html .= $GLOBALS['HTML']->getImage('ic/bin_closed.png', array('alt' => 'delete'));
                            $html .= '</a></div>';
                        } else {
                            $cannot_delete_message = $GLOBALS['Language']->getText('plugin_tracker', 'cannot_delete_tracker', array($used_in_other_services_infos['message']));
                            $html .= '<div style="float:right;" class="tracker-cant-delete">';
                            $html .= $GLOBALS['HTML']->getImage('ic/bin_closed.png', array('title' => $cannot_delete_message));
                            $html .= '</div>';
                        }
                    }
                    $html .= '<div class="tracker_homepage_info">';
                    $html .= '<a class="link-to-tracker" href="' . TRACKER_BASE_URL . '/?tracker=' . $tracker->id . '" data-test="tracker-link-' . $tracker->getItemName() . '" data-test-tracker-id="' . $tracker->getId() . '">';
                    $html .= '<i class="fa fa-circle tracker_color_info ' . $hp->purify($tracker->getColor()->getName()) . '"></i>';
                    $html .= $hp->purify($tracker->name, CODENDI_PURIFIER_CONVERT_HTML);
                    $html .= '</a>';

                    if ($tracker->userHasFullAccess()) {
                        $stats = $tracker->getStats();
                        $html .= ' <span style="font-size:0.75em">( <strong>';
                        if ($tracker->hasSemanticsStatus() && $stats['nb_total']) {
                            $html .= (int) ($stats['nb_open']) . ' ' . $GLOBALS['Language']->getText('plugin_tracker_index', 'open') . ' / ';
                        }
                        $html .= (int) ($stats['nb_total']) . ' ' . $GLOBALS['Language']->getText('plugin_tracker_index', 'total');
                        $html .= '</strong> )</span>';

                        $html .= '</dt>';
                        $html .= '<dd>' . $hp->purify($tracker->description, CODENDI_PURIFIER_CONVERT_HTML);
                        $html .= $tracker->fetchStats();
                        $html .= '</dd>';
                    } else {
                        $html .= '<dd>' . $hp->purify($tracker->description, CODENDI_PURIFIER_CONVERT_HTML);
                        $html .= '</dd>';
                    }

                    $html .= '</div>';
                }
            }
            if ($html) {
                echo '<table cellspacing="0" cellpadding="0" border="0"><tr><td><dl class="tracker_alltrackers">';
                echo $html;
                echo '</dl></td></tr></table>';
            }
            $this->displayFooter($project);
        }
    }

    public function displayDeletedTrackers()
    {
        $deleted_trackers = $this->getTrackerFactory()->getDeletedTrackers();

        $deleted_trackers_presenters = array();
        $tracker_ids_warning         = array();
        $restore_token               = new CSRFSynchronizerToken('/tracker/admin/restore.php');

        foreach ($deleted_trackers as $tracker) {
            $project             = $tracker->getProject();
            $tracker_ids_warning = array();

            if (! $project || $project->getID() === null) {
                $tracker_ids_warning[] = $tracker->getId();
                continue;
            }

            $project_id    = $project->getId();
            $project_name  = $project->getUnixName();
            $tracker_id    = $tracker->getId();
            $tracker_name  = $tracker->getName();
            $deletion_date = date('d-m-Y', $tracker->deletion_date);

            $deleted_trackers_presenters[] = new DeletedTrackerPresenter(
                $tracker_id,
                $tracker_name,
                $project_id,
                $project_name,
                $deletion_date,
                $restore_token
            );
        }

        $presenter = new DeletedTrackersListPresenter(
            $deleted_trackers_presenters,
            $tracker_ids_warning,
            count($deleted_trackers_presenters) > 0
        );

        $renderer = new AdminPageRenderer();

        $renderer->renderToPage(
            $presenter->getTemplateDir(),
            TrackerManager::DELETED_TRACKERS_TEMPLATE_NAME,
            $presenter
        );
    }

    private function trackerCanBeDisplayed(Tracker $tracker, PFUser $user)
    {
        return $tracker->userCanView($user) && ! $this->getTV3MigrationManager()->isTrackerUnderMigration($tracker);
    }

    private function userIsTrackerAdmin(Project $project, PFUser $user)
    {
        return $this->userCanCreateTracker($project->getGroupId(), $user) || $this->userCanAdminAllProjectTrackers($user);
    }

    private function informUserOfOngoingMigrations(Project $project)
    {
        if ($this->getTV3MigrationManager()->thereAreMigrationsOngoingForProject($project)) {
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_tracker_include_type', 'tv3_being_migrated'));
            $this->informUntruncatedEmailWillBeSent($project);
        }
    }

    private function informUntruncatedEmailWillBeSent(Project $project)
    {
        if ($project->getTruncatedEmailsUsage()) {
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_tracker_include_type', 'untruncated_migration_email'));
        }
    }

    protected function displayCSVImportOverview($project, $group_id, $user)
    {
        $hp = Codendi_HTMLPurifier::instance();
        $breadcrumbs = array();
        $toolbar     = array();
        $params      = array();
        $this->displayHeader($project, $GLOBALS['Language']->getText('plugin_tracker', 'trackers'), $breadcrumbs, $toolbar, $params);

        $html = '';

        $tf = TrackerFactory::instance();
        $trackers = $tf->getTrackersByGroupId($group_id);

        // Show all the fields currently available in the system
        echo '<table width="100%" border="0" cellspacing="1" cellpadding="2">';
        echo ' <tr class="boxtable">';
        echo '  <td class="boxtitle">&nbsp;</td>';
        echo '  <td class="boxtitle">';
        echo '   <div align="center"><b>' . $GLOBALS['Language']->getText('plugin_tracker_import_admin', 'art_data_import') . '</b></div>';
        echo '  </td>';
        echo '  <td class="boxtitle">';
        echo '   <div align="center"><b>' . $GLOBALS['Language']->getText('plugin_tracker_import_admin', 'import_format') . '</b></div>';
        echo '  </td>';
        echo ' </tr>';

        $cpt = 0;
        foreach ($trackers as $tracker) {
            if ($tracker->userIsAdmin($user)) {
                echo '<tr class="' . util_get_alt_row_color($cpt) . '">';
                echo ' <td><b>' . $GLOBALS['Language']->getText('plugin_tracker_import_admin', 'tracker') . ': ' . $hp->purify($tracker->getName(), CODENDI_PURIFIER_CONVERT_HTML) . '</b></td>';
                echo ' <td align="center"><a href="' . TRACKER_BASE_URL . '/?tracker=' . (int) ($tracker->getID()) . '&func=admin-csvimport">' . $GLOBALS['Language']->getText('plugin_tracker_import_admin', 'import') . '</a></td>';
                echo ' <td align="center"><a href="' . TRACKER_BASE_URL . '/?tracker=' . (int) ($tracker->getID()) . '&func=csvimport-showformat">' . $GLOBALS['Language']->getText('plugin_tracker_import_admin', 'show_format') . '</a></td>';
                echo '</tr>';
            }
        }
        echo '</table>';
        $this->displayFooter($project);
    }

    public function fetchTrackerSwitcher(PFUser $user, $separator, ?Project $include_project = null, ?Tracker $current_tracker = null)
    {
        $hp = Codendi_HTMLPurifier::instance();
        $html = '';

        //Projects/trackers
        $projects = $user->getProjects(true);
        if ($include_project) {
            $found = false;
            foreach ($projects as $data) {
                if ($data['group_id'] == $include_project->getGroupId()) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $projects[] = array(
                    'group_id'   => $include_project->getGroupId(),
                    'group_name' => $include_project->getPublicName(),
                );
            }
        }

        $html .= '<strong>';
        if ($current_tracker) {
            $html .= $hp->purify($current_tracker->getProject()->getPublicName(), CODENDI_PURIFIER_CONVERT_HTML);
        } else {
            $html .= $GLOBALS['Language']->getText('plugin_tracker', 'tracker_switcher');
        }
        $html .= '</strong>' . $separator;
        $html .= '<select id="tracker_select_tracker">';
        if (!$current_tracker) {
            $html .= '<option selected="selected">--</option>';
        }
        $factory = TrackerFactory::instance();
        foreach ($projects as $data) {
            if ($trackers = $factory->getTrackersByGroupId($data['group_id'])) {
                foreach ($trackers as $key => $v) {
                    if (! $v->userCanView($user)) {
                        unset($trackers[$key]);
                    }
                }
                if ($trackers) {
                    $html .= '<optgroup label="' . $hp->purify($data['group_name'], CODENDI_PURIFIER_CONVERT_HTML) . '">';
                    foreach ($trackers as $t) {
                        $selected = $current_tracker && $t->getId() == $current_tracker->getId() ? 'selected="selected"' : '';
                        $html .= '<option ' . $selected . ' value="' . $t->getId() . '">';
                        $html .= $hp->purify($t->getName(), CODENDI_PURIFIER_CONVERT_HTML);
                        $html .= '</option>';
                    }
                    $html .= '</optgroup>';
                }
            }
        }
        $html .= '</select>';
        return $html;
    }

    /**
     * On project creation, copy template trackers to destination project
     *
     * @param int $from_project_id
     * @param int $to_project_id
     * @param Array   $ugroup_mapping
     */
    public function duplicate($from_project_id, $to_project_id, $ugroup_mapping)
    {
        $this->getTrackerFactory()->duplicate($from_project_id, $to_project_id, $ugroup_mapping);
        $this->duplicateReferences($from_project_id);
    }

    /**
     * On project creation, copy all 'plugin_tracker_artifact' references not attached to a tracker
     *
     * @param int $from_project_id
     */
    protected function duplicateReferences($from_project_id)
    {
        // Index by shortname
        foreach ($this->getTrackerFactory()->getTrackersByGroupId($from_project_id) as $tracker) {
            $trackers[$tracker->getItemName()] = $tracker;
        }

        // Loop over references
        $refMgr     = $this->getReferenceManager();
        $references = $refMgr->getReferencesByGroupId($from_project_id);
        foreach ($references as $reference) {
            if (!isset($trackers[$reference->getKeyword()])) {
                $refMgr->createReference($reference);
            }
        }
    }

    /**
     * @return Tracker_URL
     */
    protected function getUrl()
    {
        return new Tracker_URL();
    }

    /**
     * @return TrackerFactory
     */
    protected function getTrackerFactory()
    {
        return TrackerFactory::instance();
    }

    protected function getArtifactFactory()
    {
        return Tracker_ArtifactFactory::instance();
    }

    protected function getArtifactReportFactory()
    {
        return Tracker_ReportFactory::instance();
    }

    protected function getProject($group_id)
    {
        return ProjectManager::instance()->getProject($group_id);
    }

    /**
     * @return ReferenceManager
     */
    protected function getReferenceManager()
    {
        return ReferenceManager::instance();
    }

    /**
     * Check if user has permission to create a tracker or not
     *
     * @param int  $group_id The Id of the project where the user wants to create a tracker
     * @param PFUser $user     The user to test (current user if not defined)
     *
     * @return bool true if user has persission to create trackers, false otherwise
     */
    public function userCanCreateTracker($group_id, $user = false)
    {
        if (!($user instanceof PFUser)) {
            $um = UserManager::instance();
            $user = $um->getCurrentUser();
        }
        return $user->isMember($group_id, 'A');
    }

    public function userCanAdminAllProjectTrackers($user = null)
    {
        if (! $user instanceof PFUser) {
            $um = UserManager::instance();
            $user = $um->getCurrentUser();
        }

        $permission = new TrackerAdminAllProjects();
        $forge_ugroup_permissions_manager = new User_ForgeUserGroupPermissionsManager(
            new User_ForgeUserGroupPermissionsDao()
        );

        return $forge_ugroup_permissions_manager->doesUserHavePermission($user, $permission);
    }

    public function search($request, $current_user)
    {
        if ($request->exist('tracker')) {
            $tracker_id = $request->get('tracker');
            $tracker = $this->getTrackerFactory()->getTrackerById($tracker_id);
            if ($tracker) {
                if ($tracker->userCanView($current_user)) {
                    $tracker->displaySearch($this, $request, $current_user);
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global', 'perm_denied'));
                    $GLOBALS['HTML']->redirect(TRACKER_BASE_URL . '/?group_id=' . $tracker->getGroupId());
                }
            }
        }
    }

    /**
     * Mark as deleted all trackers of a given project
     *
     * @param int $group_id The project id
     *
     * @return bool
     */
    public function deleteProjectTrackers($group_id)
    {
        $delete_status = true;
        $trackers = $this->getTrackerFactory()->getTrackersByGroupId($group_id);
        if (!empty($trackers)) {
            foreach ($trackers as $tracker) {
                if (!$this->getTrackerFactory()->markAsDeleted($tracker->getId())) {
                    $delete_status = false;
                }
            }
        }
        return $delete_status;
    }

    /**
     * Get all trackers having at least on active date reminder
     *
     * @return Array
     */
    protected function getTrackersHavingDateReminders()
    {
        $trackers = array();
        $dao = new Tracker_DateReminderDao();
        $dar = $dao->getTrackersHavingDateReminders();
        if ($dar && !$dar->isError()) {
            foreach ($dar as $row) {
                $trackers[] = $this->getTrackerFactory()->getTrackerById($row['tracker_id']);
            }
        }
        return $trackers;
    }

    /**
     * Send Date reminder
     *
     * @return Void
     */
    public function sendDateReminder()
    {
        $logger = BackendLogger::getDefaultLogger();
        $logger->debug("[TDR] Start processing date reminders");
        $trackers       = $this->getTrackersHavingDateReminders();
        foreach ($trackers as $tracker) {
            $logger->debug("[TDR] Processing date reminders for tracker " . $tracker->getProject()->getUnixName() . " / " . $tracker->getItemName() . " (id: " . $tracker->getId() . ")");
            $dateReminderManager = new Tracker_DateReminderManager($tracker);
            $dateReminderManager->process();
        }
        $logger->debug("[TDR] End processing date reminders");
    }

    private function displayMigrateFromTV3Option($requested_create_mode, Project $project, $requested_template_id)
    {
        $html        = '';
        $trackers_v3 = $this->getTrackersV3ForProject($project);
        if ($trackers_v3) {
            $html .= '<hr />';
            $html .= '<p>' . $GLOBALS['Language']->getText('plugin_tracker_include_type', 'tv3_migration_introduction') . '</p>';
            $radio = $this->getCreateTrackerRadio('migrate_from_tv3', $requested_create_mode);
            $html .= $this->getSelectBoxForTV3($requested_template_id, $radio, $trackers_v3, $GLOBALS['Language']->getText('plugin_tracker_include_type', 'migrate_from_tv3'));
        }
        echo $html;
    }

    private function getTV3MigrationManager()
    {
        return new Tracker_Migration_MigrationManager(
            new Tracker_SystemEventManager(SystemEventManager::instance()),
            $this->getTrackerFactory(),
            $this->getArtifactFactory(),
            $this->getTrackerFormElementFactory(),
            UserManager::instance(),
            ProjectManager::instance(),
            $this->getCreationDataChecker()
        );
    }

    private function getTrackerFormElementFactory()
    {
        return Tracker_FormElementFactory::instance();
    }

    private function getCreationDataChecker(): TrackerCreationDataChecker
    {
        return TrackerCreationDataChecker::build();
    }
}
