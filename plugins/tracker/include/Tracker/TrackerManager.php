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

use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Event\Events\ProjectProviderEvent;
use Tuleap\Layout\HeaderConfiguration;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupRepresentationBuilder;
use Tuleap\Project\MappingRegistry;
use Tuleap\Tracker\Admin\GlobalAdmin\GlobalAdminPermissionsChecker;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Creation\JiraImporter\PendingJiraImportDao;
use Tuleap\Tracker\Creation\TrackerCreationDataChecker;
use Tuleap\Tracker\DateReminder\DateReminderDao;
use Tuleap\Tracker\Migration\KeepReverseCrossReferenceDAO;
use Tuleap\Tracker\Migration\LegacyTrackerMigrationDao;
use Tuleap\Tracker\PermissionsPerGroup\TrackerPermissionPerGroupJSONRetriever;
use Tuleap\Tracker\PermissionsPerGroup\TrackerPermissionPerGroupPermissionRepresentationBuilder;
use Tuleap\Tracker\PermissionsPerGroup\TrackerPermissionPerGroupRepresentationBuilder;
use Tuleap\Tracker\ServiceHomepage\HomepagePresenterBuilder;
use Tuleap\Tracker\ServiceHomepage\HomepageRenderer;
use Tuleap\Tracker\Tracker;
use Tuleap\Tracker\TrackerDeletion\DeletedTrackerDao;
use Tuleap\Tracker\TrackerDeletion\TrackerRestorer;

class TrackerManager implements Tracker_IFetchTrackerSwitcher //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
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

        header('HTTP/1.0 404 Not Found');
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
                            case 'restore-tracker':
                                if ($global_admin_permissions_checker->doesUserHaveTrackerGlobalAdminRightsOnProject($project, $user)) {
                                    $restorer = new TrackerRestorer($this->getTrackerFactory(), new DeletedTrackerDao());
                                    $token    = new CSRFSynchronizerToken('/tracker/admin/restore.php');
                                    $token->check();
                                    $restorer->restoreTracker($request, $GLOBALS['Response']);
                                } else {
                                    $this->redirectToTrackerHomepage($group_id);
                                }
                                break;

                            case 'permissions-per-group':
                                if (! $request->getCurrentUser()->isAdmin($request->getProject()->getID())) {
                                    $GLOBALS['Response']->send400JSONErrors(
                                        [
                                            'error' => [
                                                'message' => dgettext(
                                                    'tuleap-tracker',
                                                    "You don't have permissions to see user groups."
                                                ),
                                            ],
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

    #[\Override]
    public function displayHeader(Project $project, string $title, array $breadcrumbs, HeaderConfiguration|array $params): void
    {
        $breadcrumbs = array_merge(
            [
                $this->getServiceTrackerBreadcrumb($project),
            ],
            $breadcrumbs
        );

        $service = $project->getService(trackerPlugin::SERVICE_SHORTNAME);
        if ($service) {
            $service->displayHeader($title, $breadcrumbs, [], $params);
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
                    'data-test' => 'tracker-administration',
                ],
            ];
        }

        return $service_tracker_breadcrumb;
    }

    private function getCurrentUser()
    {
        return UserManager::instance()->getCurrentUser();
    }

    #[\Override]
    public function displayFooter(Project $project): void
    {
        if ($service = $project->getService('plugin_tracker')) {
            $service->displayFooter();
        }
    }

    /**
     * Display all trackers of $project that $user is able to see
     */
    public function displayAllTrackers(\Project $project, \PFUser $user): void
    {
        $migration_manager = $this->getTV3MigrationManager();
        $renderer          = new HomepageRenderer(
            new HomepagePresenterBuilder(
                $this->getTrackerFactory(),
                $migration_manager,
            ),
            new \Tuleap\Tracker\Admin\GlobalAdmin\GlobalAdminPermissionsChecker(
                new User_ForgeUserGroupPermissionsManager(
                    new User_ForgeUserGroupPermissionsDao()
                )
            ),
            new \Tuleap\Tracker\Creation\OngoingCreationFeedbackNotifier(
                $migration_manager,
                new PendingJiraImportDao()
            ),
            TemplateRendererFactory::build()
        );


        $GLOBALS['HTML']->addJavascriptAsset(
            new JavascriptViteAsset(
                new IncludeViteAssets(
                    __DIR__ . '/../../scripts/service-homepage/frontend-assets',
                    '/assets/trackers/service-homepage'
                ),
                'src/main.ts'
            )
        );
        $title       = dgettext('tuleap-tracker', 'Trackers');
        $breadcrumbs = [];
        $this->displayHeader(
            $project,
            $title,
            $breadcrumbs,
            \Tuleap\Layout\HeaderConfigurationBuilder::get($title)
                ->inProject($project, trackerPlugin::SERVICE_SHORTNAME)
                ->build()
        );
        echo $renderer->renderToString($project, $user);
        $this->displayFooter($project);
    }

    #[\Override]
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
            $project = $project_manager->getProject($data['group_id']);

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
    public function duplicate(PFUser $user, DBTransactionExecutor $transaction_executor, \Project $from_project, \Project $to_project, MappingRegistry $mapping_registry): void
    {
        $this->getTrackerFactory()->duplicate($user, $transaction_executor, $from_project, $to_project, $mapping_registry);
        $this->duplicateReferences((int) $from_project->getID());
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
        $logger->debug('[TDR] Start processing date reminders');
        $trackers = $this->getTrackersHavingDateReminders();
        foreach ($trackers as $tracker) {
            $logger->debug('[TDR] Processing date reminders for tracker ' . $tracker->getProject()->getUnixName() . ' / ' . $tracker->getItemName() . ' (id: ' . $tracker->getId() . ')');
            $dateReminderManager = new Tracker_DateReminderManager($tracker);
            $dateReminderManager->process();
        }
        $logger->debug('[TDR] End processing date reminders');
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
