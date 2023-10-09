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
use Tuleap\date\RelativeDatesAssetsRetriever;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Event\Events\ProjectProviderEvent;
use Tuleap\Layout\HeaderConfiguration;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupRepresentationBuilder;
use Tuleap\Project\MappingRegistry;
use Tuleap\Tracker\Admin\GlobalAdmin\GlobalAdminPermissionsChecker;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Creation\JiraImporter\PendingJiraImportDao;
use Tuleap\Tracker\Creation\TrackerCreationController;
use Tuleap\Tracker\Creation\TrackerCreationDataChecker;
use Tuleap\Tracker\DateReminder\DateReminderDao;
use Tuleap\Tracker\Migration\KeepReverseCrossReferenceDAO;
use Tuleap\Tracker\Migration\LegacyTrackerMigrationDao;
use Tuleap\Tracker\PermissionsPerGroup\TrackerPermissionPerGroupJSONRetriever;
use Tuleap\Tracker\PermissionsPerGroup\TrackerPermissionPerGroupPermissionRepresentationBuilder;
use Tuleap\Tracker\PermissionsPerGroup\TrackerPermissionPerGroupRepresentationBuilder;

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
        if (! $request->isAjax()) {
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
            throw new Tracker_CannotAccessTrackerException(dgettext('tuleap-tracker', 'This tracker does not exist.'));
        }
        if (! $tracker->userCanView($user)) {
            if ($user->isAnonymous()) {
                $url_redirect = new URLRedirect(EventManager::instance());

                throw new Tracker_CannotAccessTrackerException(
                    sprintf(dgettext('tuleap-tracker', 'You don\'t have the permissions to view this tracker. Given that you are anonymous, please try to <a href="%1$s">login</a>.'), $url_redirect->buildReturnToLogin($_SERVER))
                );
            } else {
                throw new Tracker_CannotAccessTrackerException(dgettext('tuleap-tracker', 'You don\'t have the permissions to view this tracker.'));
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
        if ($object instanceof Artifact) {
            $artifact = $object;
            if ((int) $request->get('aid')) {
                if ($artifact->userCanView($user)) {
                    $artifact->process($this, $request, $user);
                } else {
                    if ($user->isAnonymous()) {
                        $url_redirect = new URLRedirect(EventManager::instance());

                        $GLOBALS['Response']->addFeedback(
                            'error',
                            sprintf(dgettext('tuleap-tracker', 'You don\'t have the permissions to view this artifact. Given that you are anonymous, please try to <a href="%1$s">login</a>.'), $url_redirect->buildReturnToLogin($_SERVER)),
                            CODENDI_PURIFIER_LIGHT
                        );
                    } else {
                        $GLOBALS['Response']->addFeedback(
                            'error',
                            dgettext('tuleap-tracker', 'You don\'t have the permissions to view this artifact.')
                        );
                    }

                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?tracker=' . $artifact->getTrackerId());
                }
            } elseif ($request->get('func') == 'new-artifact-link') {
                echo '<html>';
                echo '<head>';
                $GLOBALS['HTML']->displayStylesheetElements([]);
                $GLOBALS['HTML']->displayJavascriptElements([]);
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
                $this->redirectToTrackerHomepage($object->getTracker()->getProject()->getID());
            } else {
                $GLOBALS['Response']->send401UnauthorizedHeader();
            }
        } catch (Tracker_NoMachingResourceException $e) {
            $global_admin_permissions_checker = new GlobalAdminPermissionsChecker(
                new User_ForgeUserGroupPermissionsManager(
                    new User_ForgeUserGroupPermissionsDao()
                )
            );
            //show, admin all trackers
            if ((int) $request->get('group_id')) {
                $group_id = (int) $request->get('group_id');
                if ($project = $this->getProject($group_id)) {
                    if ($this->checkServiceEnabled($project, $request)) {
                        switch ($request->get('func')) {
                            case 'docreate':
                                if ($global_admin_permissions_checker->doesUserHaveTrackerGlobalAdminRightsOnProject($project, $user)) {
                                      $this->doCreateTracker($project, $request);
                                } else {
                                    $this->redirectToTrackerHomepage($group_id);
                                }
                                break;
                            case 'create':
                                if ($global_admin_permissions_checker->doesUserHaveTrackerGlobalAdminRightsOnProject($project, $user)) {
                                    $this->displayCreateTracker($project, $request);
                                } else {
                                    $this->redirectToTrackerHomepage($group_id);
                                }
                                break;
                            case 'restore-tracker':
                                if ($global_admin_permissions_checker->doesUserHaveTrackerGlobalAdminRightsOnProject($project, $user)) {
                                    $tracker_id = $request->get('tracker_id');
                                    $group_id   = $request->get('group_id');
                                    $token      = new CSRFSynchronizerToken('/tracker/admin/restore.php');
                                    $token->check();
                                    $tracker = $this->getTrackerFactory()->getTrackerById($tracker_id);
                                    if ($tracker === null) {
                                        throw new RuntimeException('Tracker does not exist');
                                    }
                                    $tracker_name = $tracker->getName();
                                    $this->restoreDeletedTracker($tracker_id);
                                    $GLOBALS['Response']->addFeedback('info', sprintf(dgettext('tuleap-tracker', 'The tracker \'%1$s\' has been properly restored'), $tracker_name));
                                    $GLOBALS['Response']->redirect('/tracker/admin/restore.php');
                                } else {
                                    $this->redirectToTrackerHomepage($group_id);
                                }
                                break;

                            case 'permissions-per-group':
                                if (! $request->getCurrentUser()->isAdmin($request->getProject()->getID())) {
                                    $GLOBALS['Response']->send400JSONErrors(
                                        [
                                            'error' => dgettext(
                                                "tuleap-tracker",
                                                "You don't have permissions to see user groups."
                                            ),
                                        ]
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
            dgettext('tuleap-tracker', 'Access denied. You don\'t have permissions to perform this action.')
        );

        $url = $this->getTrackerHomepageURL($project_id);

        $GLOBALS['Response']->redirect($url);
    }

    private function getTrackerHomepageURL($project_id)
    {
        return TRACKER_BASE_URL . '/?' . http_build_query([
            'group_id' => $project_id,
        ]);
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

    public function displayHeader($project, $title, $breadcrumbs, $toolbar, HeaderConfiguration|array $params): void
    {
        $breadcrumbs = array_merge(
            [
                $this->getServiceTrackerBreadcrumb($project),
            ],
            $breadcrumbs
        );

        if ($service = $project->getService('plugin_tracker')) {
            $service->displayHeader($title, $breadcrumbs, $toolbar, $params);
        }
    }

    private function getServiceTrackerBreadcrumb(Project $project)
    {
        $service_tracker_breadcrumb = [
            'title'     => dgettext('tuleap-tracker', 'Trackers'),
            'url'       => TRACKER_BASE_URL . '/?group_id=' . $project->getID(),
        ];

        if ($this->getCurrentUser()->isAdmin($project->getID())) {
            $service_tracker_breadcrumb['sub_items'] = [
                [
                    'title'     => $GLOBALS['Language']->getText('global', 'Administration'),
                    'url'       => Tracker::getTrackerGlobalAdministrationURL($project),
                    'data-test' => "tracker-administration",
                ],
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

        if ($request->get('create_mode') === 'tv3') {
            $atid        = $request->get('tracker_new_tv3');
            $user        = UserManager::instance()->getCurrentUser();
            $new_tracker = $this->getTrackerFactory()->createFromTV3($user, $atid, $project, $name, $description, $itemname);
        } elseif ($request->get('create_mode') === 'migrate_from_tv3') {
            $tracker_id = $request->get('tracker_new_tv3');
            if ($this->getTV3MigrationManager()->askForMigration($project, $tracker_id, $name, $description, $itemname, false)) {
                $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?group_id=' . $project->group_id);
            }
        } elseif ($request->get('create_mode') === 'migrate_from_tv3_with_ids') {
            $tracker_id = $request->get('tracker_new_tv3');
            if ($this->getTV3MigrationManager()->askForMigration($project, $tracker_id, $name, $description, $itemname, true)) {
                $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?group_id=' . $project->group_id);
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
        ?Tracker $tracker_template = null,
    ) {
        global $Language;

        $route_to_new_ui = TrackerCreationController::getRouteToTrackerCreationController($project);

        $trackers_v3 = $this->getTrackersV3ForProject($project);
        if (empty($trackers_v3)) {
            $GLOBALS['HTML']->redirect($route_to_new_ui);
            return;
        }

        $breadcrumbs = [
            [
                'title' => dgettext('tuleap-tracker', 'Create a New Tracker'),
                'url'   => TRACKER_BASE_URL . '/?group_id=' . $project->group_id . '&amp;func=create',
            ],
        ];
        $title       = 'Trackers';
        $this->displayHeader(
            $project,
            $title,
            $breadcrumbs,
            [],
            \Tuleap\Layout\HeaderConfigurationBuilder::get($title)
                ->inProject($project, trackerPlugin::SERVICE_SHORTNAME)
                ->build()
        );

        $hp = Codendi_HTMLPurifier::instance();

        echo '<div class="alert alert-error">';
        echo dgettext('tuleap-tracker', 'This page is deprecated and will be removed soon. You should switch to the new tracker creation flow.');
        echo '<a href="' . $route_to_new_ui . '" class="btn btn-primary tracker-creation-link">
                <i class="fas fa-long-arrow-alt-right"></i> ' . dgettext('tuleap-tracker', 'Switch to new tracker creation flow') .
            '</a>';
        echo '</div>';
        echo '<h2>' . dgettext('tuleap-tracker', 'Create a new tracker') . '</h2>';

        echo '<form name="form_create" method="post" enctype="multipart/form-data" id="tracker_create_new">
          <input type="hidden" name="group_id" value="' . $hp->purify($project->getId()) . '">
          <input type="hidden" name="func" value="docreate">

          <table>
          <tr valign="top"><td style="padding-right:2em; border-right: 1px solid #eee;">';

        echo '<p>' . dgettext('tuleap-tracker', 'First, select a template you want to start from') . '</p>';

        $create_mode = $request->get('create_mode');
        $this->displayCreateTrackerFromTV3($create_mode, $project, $request->get('tracker_new_tv3'));
        $this->displayMigrateFromTV3Option($create_mode, $project, $request->get('tracker_new_tv3'));

        echo '</td><td style="padding-left:2em;">';

        echo '<p>' . dgettext('tuleap-tracker', 'Then, fill the name, description and short name of this new tracker:') . '</p>
          <p>
              <label for="newtracker_name"><b>' . dgettext('tuleap-tracker', 'Name') . '</b>: <font color="red">*</font></label><br />
              <input type="text" name="name" id="newtracker_name" value="' . $hp->purify($name, CODENDI_PURIFIER_CONVERT_HTML) . '" required="required" />
          </p>
          <p>
              <label for="newtracker_description"><b>' . dgettext('tuleap-tracker', 'Description') . '</b>:<br />
              <textarea id="newtracker_description" name="description" rows="3" cols="50">' . $hp->purify($description, CODENDI_PURIFIER_CONVERT_HTML) . '</textarea>
          </p>
          <p>
              <label for="newtracker_itemname"><b>' . dgettext('tuleap-tracker', 'Short name') . '</b>: <font color="red">*</font></label><br />
              <input type="text" id="newtracker_itemname" name="itemname" value="' . $hp->purify($itemname, CODENDI_PURIFIER_CONVERT_HTML) . '" required="required" /><br />
              <span style="color:#999;">' . dgettext('tuleap-tracker', 'Please avoid spaces and punctuation in short names') . '</span>
          </p>';

        echo '<div id="check_consistency_feedback"></div>';
        echo '<input type="submit" name="Create" value="' . $Language->getText('global', 'btn_create') . '" id="create_new_tracker_btn" class="btn">';

        echo '</td></tr></table></form>';

        $this->displayFooter($project);
    }

    private function getTrackersV3ForProject(Project $project)
    {
        if ($project->usesService('tracker')) {
            $atf = new ArtifactTypeFactory($project);
            return $atf->getArtifactTypes();
        }

        return null;
    }

    private function displayCreateTrackerFromTV3($requested_create_mode, Project $project, $requested_template_id)
    {
        $trackers_v3 = $this->getTrackersV3ForProject($project);

        if ($trackers_v3) {
            $radio = $this->getCreateTrackerRadio('tv3', $requested_create_mode);
            echo $this->getSelectBoxForTV3(
                $requested_template_id,
                [
                    [
                        'button' => $radio,
                        'label'  => dgettext('tuleap-tracker', 'From a Tracker v3'),
                    ],
                ],
                $trackers_v3
            );
        }
    }

    /**
     * @param list<array{button: string, label: string}> $radio_buttons
     */
    private function getSelectBoxForTV3($requested_template_id, array $radio_buttons, array $trackers_v3): string
    {
        $html = '';
        $hp   = Codendi_HTMLPurifier::instance();
        foreach ($radio_buttons as $radio) {
            $html .= '<h3><label>' . $radio['button'] . $radio['label'] . '</label></h3>';
        }
        $html   .= '<br>';
        $html   .= '<div class="tracker_create_mode">';
        $checked = $requested_template_id ? '' : 'checked="checked"';

        foreach ($trackers_v3 as $tracker_v3) {
            $html .= '<p>';
            $html .= '<label>';
            if ($requested_template_id == $tracker_v3->getID()) {
                $checked = 'checked="checked"';
            }
            $html   .= '<input type="radio" name="tracker_new_tv3" value="' . $tracker_v3->getID() . '" ' . $checked . ' />';
            $html   .= $hp->purify(SimpleSanitizer::unsanitize($tracker_v3->getName()), CODENDI_PURIFIER_CONVERT_HTML);
            $html   .= '</label>';
            $html   .= '</p>';
            $checked = '';
        }
        $html .= '</div>';

        return $html;
    }

    public function getCreateTrackerRadio($create_mode, $requested_create_mode): string
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
        $breadcrumbs = [];
        $html        = '';
        $trackers    = $this->getTrackerFactory()->getTrackersByGroupId($project->getID());

        $permissions_checker = new \Tuleap\Tracker\Admin\GlobalAdmin\GlobalAdminPermissionsChecker(
            new User_ForgeUserGroupPermissionsManager(
                new User_ForgeUserGroupPermissionsDao()
            )
        );
        $is_tracker_admin    = $permissions_checker->doesUserHaveTrackerGlobalAdminRightsOnProject($project, $user);
        if ($is_tracker_admin) {
            $this->informUserOfOngoingMigrations($project);
        }

        $title = dgettext('tuleap-tracker', 'Trackers');
        $this->displayHeader(
            $project,
            $title,
            $breadcrumbs,
            [],
            \Tuleap\Layout\HeaderConfigurationBuilder::get($title)
                ->inProject($project, trackerPlugin::SERVICE_SHORTNAME)
                ->build()
        );
        $html .= '<h1 class="trackers-homepage-title">' . dgettext('tuleap-tracker', 'Trackers');

        if ($is_tracker_admin) {
            $html .= '<a id="tracker_createnewlink" class="tlp-button-primary" data-test="new-tracker-creation" href="' . TRACKER_BASE_URL . '/' .
                urlencode($project->getUnixNameLowerCase()) . '/new">';
            $html .= '<i class="fa fa-plus tlp-button-icon"></i>';
            $html .= dgettext('tuleap-tracker', 'New tracker');
            $html .= '</a></h1>';
        } else {
            $html .= '</h1>';
        }

        $html .= '<div class="trackers-homepage">';

        if (! count($trackers)) {
            $html .= '<p>' . dgettext('tuleap-tracker', 'No trackers have been set up, or you are not allowed to view them.') . '</p>';
        }

        $GLOBALS['HTML']->includeFooterJavascriptFile(RelativeDatesAssetsRetriever::retrieveAssetsUrl());

        $include_assets = new IncludeAssets(
            __DIR__ . '/../../frontend-assets',
            '/assets/trackers'
        );

        $GLOBALS['HTML']->includeFooterJavascriptFile($include_assets->getFileURL('tracker-homepage.js'));

        foreach ($trackers as $tracker) {
            if ($this->trackerCanBeDisplayed($tracker, $user)) {
                $html .= '<a href="' . TRACKER_BASE_URL . '/?tracker=' . $hp->purify(urlencode($tracker->id)) . '" data-test="tracker-link-' . $hp->purify($tracker->getItemName()) . '" ';
                $html .= 'data-test-tracker-id="' . $hp->purify($tracker->getId()) . '" data-tracker-id="' . $hp->purify($tracker->id) . '" ';
                $html .= 'class="tlp-card tlp-card-selectable trackers-homepage-tracker ' . $hp->purify($tracker->getColor()->getName()) . '">';

                $html .= '<span class="trackers-homepage-tracker-title-container">';
                $html .= '<span class="trackers-homepage-tracker-title">' . $hp->purify($tracker->name, CODENDI_PURIFIER_CONVERT_HTML) . '</span>';

                $stats = null;
                if ($tracker->userHasFullAccess()) {
                    $stats = $tracker->getStats();
                }

                if ($stats !== null) {
                    $html .= '<span class="trackers-homepage-tracker-spacer"></span>';
                    $html .= '<span class="trackers-homepage-tracker-badge tlp-badge-' . $hp->purify($tracker->getColor()->getName()) . ' tlp-badge-outline">';
                    if ($tracker->hasSemanticsStatus() && $stats->getNbOpenArtifacts() > 0) {
                        $html .= $stats->getNbOpenArtifacts() . ' ' . dgettext('tuleap-tracker', 'open') . ' / ';
                    }
                    $html .= $stats->getNbTotalArtifacts() . ' ' . dgettext('tuleap-tracker', 'total');
                    $html .= '</span>';
                }

                $html .= '</span>';

                $html .= '<span class="trackers-homepage-tracker-description">' . $hp->purify($tracker->description, CODENDI_PURIFIER_CONVERT_HTML) . '</span>';
                $html .= '</a>';

                $html .= $tracker->fetchStatsTooltip($user);
            }
        }

        $html .= '</div>';

        if ($html) {
            echo $html;
        }

        $this->displayFooter($project);
    }

    public function displayDeletedTrackers()
    {
        $deleted_trackers = $this->getTrackerFactory()->getDeletedTrackers();

        $deleted_trackers_presenters = [];
        $tracker_ids_warning         = [];
        $restore_token               = new CSRFSynchronizerToken('/tracker/admin/restore.php');

        foreach ($deleted_trackers as $tracker) {
            $project             = $tracker->getProject();
            $tracker_ids_warning = [];

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
            self::DELETED_TRACKERS_TEMPLATE_NAME,
            $presenter
        );
    }

    private function trackerCanBeDisplayed(Tracker $tracker, PFUser $user)
    {
        return $tracker->userCanView($user) && ! $this->getTV3MigrationManager()->isTrackerUnderMigration($tracker);
    }

    private function informUserOfOngoingMigrations(Project $project)
    {
        $feedback_notifier = new \Tuleap\Tracker\Creation\OngoingCreationFeedbackNotifier(
            $this->getTV3MigrationManager(),
            $this->getPendingJiraCreationDao()
        );
        $feedback_notifier->informUserOfOngoingMigrations($project, $GLOBALS['Response']);
    }

    private function getPendingJiraCreationDao(): PendingJiraImportDao
    {
        return new PendingJiraImportDao();
    }

    public function fetchTrackerSwitcher(PFUser $user, $separator, ?Project $include_project = null, ?Tracker $current_tracker = null)
    {
        $hp   = Codendi_HTMLPurifier::instance();
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
            if (! $found) {
                $projects[] = [
                    'group_id'   => $include_project->getGroupId(),
                    'group_name' => $include_project->getPublicName(),
                ];
            }
        }

        $html .= '<strong>';
        if ($current_tracker) {
            $html .= $hp->purify($current_tracker->getProject()->getPublicName(), CODENDI_PURIFIER_CONVERT_HTML);
        } else {
            $html .= dgettext('tuleap-tracker', 'Please select a tracker');
        }
        $html .= '</strong>' . $separator;
        $html .= '<span class="tracker-selector"><select id="tracker_select_tracker">';
        if (! $current_tracker) {
            $html .= '<option selected="selected">--</option>';
        }
        $factory         = TrackerFactory::instance();
        $project_manager = ProjectManager::instance();

        foreach ($projects as $data) {
            $project = $project_manager->getProject($data["group_id"]);

            if (! $project->usesService(trackerPlugin::SERVICE_SHORTNAME)) {
                continue;
            }

            $trackers = $factory->getTrackersByGroupId((int) $project->getID());
            if ($trackers) {
                foreach ($trackers as $key => $v) {
                    if (! $v->userCanView($user)) {
                        unset($trackers[$key]);
                    }
                }
                if ($trackers) {
                    $html .= '<optgroup label="' . $hp->purify($project->getPublicName(), CODENDI_PURIFIER_CONVERT_HTML) . '">';
                    foreach ($trackers as $t) {
                        $selected = $current_tracker && $t->getId() == $current_tracker->getId() ? 'selected="selected"' : '';
                        $html    .= '<option ' . $selected . ' value="' . $t->getId() . '">';
                        $html    .= $hp->purify($t->getName(), CODENDI_PURIFIER_CONVERT_HTML);
                        $html    .= '</option>';
                    }
                    $html .= '</optgroup>';
                }
            }
        }
        $html .= '</select></span>';
        return $html;
    }

    /**
     * On project creation, copy template trackers to destination project
     */
    public function duplicate(DBTransactionExecutor $transaction_executor, int $from_project_id, int $to_project_id, MappingRegistry $mapping_registry): void
    {
        $this->getTrackerFactory()->duplicate($transaction_executor, $from_project_id, $to_project_id, $mapping_registry);
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
            if (! isset($trackers[$reference->getKeyword()])) {
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
     * Mark as deleted all trackers of a given project
     *
     * @param int $group_id The project id
     *
     * @return bool
     */
    public function deleteProjectTrackers($group_id)
    {
        $delete_status = true;
        $trackers      = $this->getTrackerFactory()->getTrackersByGroupId($group_id);
        if (! empty($trackers)) {
            foreach ($trackers as $tracker) {
                if (! $this->getTrackerFactory()->markAsDeleted($tracker->getId())) {
                    $delete_status = false;
                }
            }
        }
        return $delete_status;
    }

    /**
     * Get all trackers having at least on active date reminder
     */
    protected function getTrackersHavingDateReminders(): array
    {
        $trackers = [];
        $dao      = new DateReminderDao();
        foreach ($dao->getTrackersHavingDateReminders() as $tracker_id) {
            $trackers[] = $this->getTrackerFactory()->getTrackerById($tracker_id);
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
        $trackers = $this->getTrackersHavingDateReminders();
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
            $html      .= '<hr />';
            $html      .= '<p>' . dgettext('tuleap-tracker', 'Or you can migrate a Tracker v3...') . '</p>';
            $radio_test = $this->getCreateTrackerRadio('migrate_from_tv3', $requested_create_mode);
            $radio      = $this->getCreateTrackerRadio('migrate_from_tv3_with_ids', $requested_create_mode);
            $html      .= $this->getSelectBoxForTV3(
                $requested_template_id,
                [
                    [
                        'button' => $radio_test,
                        'label'  => dgettext('tuleap-tracker', 'Migrate a Tracker v3 content for test'),
                    ],
                    [
                        'button' => $radio,
                        'label'  => dgettext('tuleap-tracker', 'Migrate a Tracker v3 content keeping original ids'),
                    ],
                ],
                $trackers_v3,
            );
        }
        echo $html;
    }

    private function getTV3MigrationManager(): Tracker_Migration_MigrationManager
    {
        $backend_logger = BackendLogger::getDefaultLogger(Tracker_Migration_MigrationManager::LOG_FILE);
        $mail_logger    = new Tracker_Migration_MailLogger();

        return new Tracker_Migration_MigrationManager(
            new Tracker_SystemEventManager(SystemEventManager::instance()),
            $this->getTrackerFactory(),
            UserManager::instance(),
            ProjectManager::instance(),
            $this->getCreationDataChecker(),
            new LegacyTrackerMigrationDao(),
            new KeepReverseCrossReferenceDAO(),
            $mail_logger,
            new Tracker_Migration_MigrationLogger(
                $backend_logger,
                $mail_logger
            )
        );
    }

    private function getCreationDataChecker(): TrackerCreationDataChecker
    {
        return TrackerCreationDataChecker::build();
    }
}
