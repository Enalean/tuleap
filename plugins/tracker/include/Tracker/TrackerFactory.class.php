<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Project\MappingRegistry;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupEnabledDao;
use Tuleap\Tracker\Artifact\RetrieveTracker;
use Tuleap\Tracker\Creation\PostCreationProcessor;
use Tuleap\Tracker\Creation\TrackerCreationDataChecker;
use Tuleap\Tracker\Creation\TrackerCreationSettings;
use Tuleap\Tracker\Creation\TrackerCreationSettingsBuilder;
use Tuleap\Tracker\DateReminder\DateReminderDao;
use Tuleap\Tracker\Notifications\ConfigNotificationAssignedToDao;
use Tuleap\Tracker\Notifications\ConfigNotificationEmailCustomSenderDao;
use Tuleap\Tracker\Notifications\GlobalNotificationDuplicationDao;
use Tuleap\Tracker\Notifications\Settings\NotificationSettingsDuplicator;
use Tuleap\Tracker\Notifications\UgroupsToNotifyDuplicationDao;
use Tuleap\Tracker\Notifications\UsersToNotifyDuplicationDao;
use Tuleap\Tracker\PromotedTrackerDao;
use Tuleap\Tracker\RetrieveTrackersByProjectIdUserCanAdministrate;
use Tuleap\Tracker\RetrieveTrackersByProjectIdUserCanView;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDao;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDuplicator;
use Tuleap\Tracker\TrackerColor;
use Tuleap\Tracker\TrackerDuplicationUserGroupMapping;
use Tuleap\Tracker\TrackerEventTrackersDuplicated;
use Tuleap\Tracker\TrackerIsInvalidException;
use Tuleap\Tracker\Webhook\WebhookDao;
use Tuleap\Tracker\Webhook\WebhookFactory;
use Tuleap\Tracker\Workflow\Trigger\Siblings\SiblingsDao;
use Tuleap\Tracker\Workflow\Trigger\Siblings\SiblingsRetriever;
use Tuleap\Tracker\Workflow\WorkflowBackendLogger;
use Tuleap\Tracker\Workflow\WorkflowRulesManagerLoopSafeGuard;

class TrackerFactory implements RetrieveTracker, RetrieveTrackersByProjectIdUserCanView, RetrieveTrackersByProjectIdUserCanAdministrate
{
    /**
     * Get the trackers required by agile dashboard
     *
     * Parameters:
     *  'project_id'        project_id
     *  'tracker_ids_list'  array containing tracker ids
     */
    public final const TRACKER_EVENT_PROJECT_CREATION_TRACKERS_REQUIRED = 'tracker_event_project_creation_trackers_required';

    public const LEGACY_SUFFIX       = '_from_tv3';
    public const TRACKER_MAPPING_KEY = 'plugin_tracker_tracker';

    /** @var array of Tracker */
    protected $trackers;

    /** @var Tracker_HierarchyFactory */
    private $hierarchy_factory;

    /**
     * A protected constructor; prevents direct creation of object
     */
    protected function __construct()
    {
        $this->trackers = [];
    }

    /**
     * Hold an instance of the class
     * @var self|null
     */
    protected static $_instance;

    /**
     * The singleton method
     *
     * @return TrackerFactory
     */
    public static function instance()
    {
        if (! isset(self::$_instance)) {
            $c               = self::class;
            self::$_instance = new $c();
        }
        return self::$_instance;
    }

    /**
     * Allows to inject a fake factory for test. DO NOT USE IT IN PRODUCTION!
     *
     */
    public static function setInstance(TrackerFactory $factory)
    {
        self::$_instance = $factory;
    }

    /**
     * Allows clear factory instance for test. DO NOT USE IT IN PRODUCTION!
     */
    public static function clearInstance()
    {
        self::$_instance = null;
    }

    public function clearCaches()
    {
        $this->trackers = [];

        self::clearInstance();
    }

    public function getTrackerById($tracker_id): ?Tracker
    {
        if (! isset($this->trackers[$tracker_id])) {
            $this->trackers[$tracker_id] = null;
            if ($row = $this->getDao()->searchById($tracker_id)->getRow()) {
                $this->getCachedInstanceFromRow($row);
            }
        }
        return $this->trackers[$tracker_id];
    }

    /**
     * @param string $shortname the shortname of the tracker we are looking for
     * @param int $project_id the id of the project from wich to retrieve the tracker
     * @return Tracker identified by shortname (null if not found)
     */
    public function getTrackerByShortnameAndProjectId($shortname, $project_id)
    {
        $row = $this->getDao()->searchByItemNameAndProjectId($shortname, $project_id)->getRow();

        if ($row) {
            return $this->getCachedInstanceFromRow($row);
        }
        return null;
    }

    /**
     * Retrieve the list of deleted trackers.
     *
     * @return array
     */
    public function getDeletedTrackers()
    {
        $pending_trackers = $this->getDao()->retrieveTrackersMarkAsDeleted();
        $deleted_trackers = [];

        if ($pending_trackers && ! $pending_trackers->isError()) {
            foreach ($pending_trackers as $pending_tracker) {
                $deleted_trackers[] = $this->getTrackerById($pending_tracker['id']);
            }
        }

        return $deleted_trackers;
    }

    /**
     * Restore a tracker from the list of deleted trackers.
     *
     * @param  int $tracker_id
     *
     * @return bool
     */
    public function restoreDeletedTracker($tracker_id)
    {
        return $this->getDao()->restoreTrackerMarkAsDeleted($tracker_id);
    }

    /**
     * @param int $group_id the project id the trackers to retrieve belong to
     *
     * @return Tracker[]
     */
    public function getTrackersByGroupId($group_id)
    {
        $trackers = [];
        foreach ($this->getDao()->searchByGroupId($group_id) as $row) {
            $tracker_id            = $row['id'];
            $trackers[$tracker_id] = $this->getCachedInstanceFromRow($row);
        }
        return $trackers;
    }

    /**
     * @return Tracker[]
     */
    public function getTrackersByProjectIdUserCanView(int|string $project_id, PFUser $user): array
    {
        $trackers = [];
        foreach ($this->getDao()->searchByGroupId($project_id) as $row) {
            $tracker_id = $row['id'];
            $tracker    = $this->getCachedInstanceFromRow($row);
            if ($tracker->userCanView($user)) {
                $trackers[$tracker_id] = $tracker;
            }
        }
        return $trackers;
    }

    /**
     * @return Tracker[]
     */
    public function getTrackersByProjectIdUserCanAdministrate(int|string $project_id, PFUser $user): array
    {
        $trackers = [];
        foreach ($this->getDao()->searchByGroupId($project_id) as $row) {
            $tracker_id = $row['id'];
            $tracker    = $this->getCachedInstanceFromRow($row);
            if ($tracker->userIsAdmin($user)) {
                $trackers[$tracker_id] = $tracker;
            }
        }

        return $trackers;
    }

    /**
     * @param Tracker $tracker
     *
     * @return Tracker[] Children trackers of the given tracker.
     */
    public function getPossibleChildren($tracker)
    {
        $project_id = $tracker->getGroupId();
        $trackers   = $this->getTrackersByGroupId($project_id);

        unset($trackers[$tracker->getId()]);
        return $trackers;
    }

    protected $dao;

    /**
     * @return TrackerDao
     */
    protected function getDao()
    {
        if (! $this->dao) {
            $this->dao = new TrackerDao();
        }
        return $this->dao;
    }

    /**
     * @param array $row Raw data (typically from the db) of the tracker
     *
     * @return Tracker
     */
    private function getCachedInstanceFromRow($row)
    {
        $tracker_id = $row['id'];
        if (! isset($this->trackers[$tracker_id])) {
            $this->trackers[$tracker_id] = $this->getInstanceFromRow($row);
        }
        return $this->trackers[$tracker_id];
    }

    /**
     * /!\ Only for tests
     */
    public function setCachedInstances($trackers)
    {
        $this->trackers = $trackers;
    }

    /**
     * @param array the row identifing a tracker
     * @return Tracker
     */
    public function getInstanceFromRow($row)
    {
        return new Tracker(
            $row['id'],
            $row['group_id'],
            $row['name'],
            $row['description'],
            $row['item_name'],
            $row['allow_copy'],
            $row['submit_instructions'],
            $row['browse_instructions'],
            $row['status'],
            $row['deletion_date'],
            $row['instantiate_for_new_projects'],
            $row['log_priority_changes'],
            $row['notifications_level'],
            TrackerColor::fromName($row['color']),
            $row['enable_emailgateway']
        );
    }

    /**
     * @return Tracker_FormElementFactory
     */
    protected function getFormElementFactory()
    {
        return Tracker_FormElementFactory::instance();
    }

    /**
     * @return Tracker_RuleFactory
     */
    protected function getRuleFactory()
    {
        return Tracker_RuleFactory::instance();
    }

    /**
     * @return ReferenceManager
     */
    protected function getReferenceManager()
    {
        return ReferenceManager::instance();
    }

    /**
     * @return ProjectManager
     */
    protected function getProjectManager()
    {
        return ProjectManager::instance();
    }

    /**
     * Mark the tracker as deleted
     */
    public function markAsDeleted($tracker_id)
    {
        return $this->getDao()->markAsDeleted($tracker_id);
    }

    /**
     * Check if the name of the tracker is already used in the project
     * @param string $name the name of the tracker we are looking for
     * @param int $group_id th ID of the group
     * @return bool
     */
    public function isNameExists($name, $group_id)
    {
        $tracker_dao = $this->getDao();
        $dar         = $tracker_dao->searchByGroupId($group_id);
        while ($row = $dar->getRow()) {
            if ($name == $row['name']) {
                return true;
            }
        }
        return false;
    }

   /**
    * @param int $group_id the ID of the group
    */
    public function isShortNameExists(string $shortname, $group_id): bool
    {
        $checker = $this->getTrackerChecker();
        return $checker->doesShortNameExists($shortname, (int) $group_id);
    }

    /**
     * @return array <string>
     */
    public function collectTrackersNameInErrorOnMandatoryCreationInfo(array $trackers, $project_id): array
    {
        $invalid_trackers_name = [];

        $checker = $this->getTrackerChecker();

        foreach ($trackers as $tracker) {
            if (! $checker->areMandatoryCreationInformationValid($tracker->getName(), $tracker->getItemName(), (int) $project_id)) {
                $invalid_trackers_name[] = $tracker->getName();
            }
        }

        return $invalid_trackers_name;
    }

    /**
     * @return array{tracker: Tracker, field_mapping: list<array{from: int, to: int, values: array, workflow: bool}>, report_mapping: array}|null
     * @throws TrackerIsInvalidException
     */
    public function create($project_id, MappingRegistry $mapping_registry, $id_template, $name, $description, $itemname, ?string $color, array|false $ugroup_mapping = false)
    {
        $this->getTrackerChecker()->checkAtProjectCreation((int) $project_id, $name, $itemname);
        $template_tracker = $this->getTrackerChecker()->checkAndRetrieveTrackerTemplate((int) $id_template);

        //Ask to dao to duplicate the tracker
        $id = $this->getDao()->duplicate($id_template, $project_id, $name, $description, $itemname, $color);
        if (! $id) {
            return null;
        }

        // Duplicate Form Elements
        $field_mapping = Tracker_FormElementFactory::instance()->duplicate($id_template, $id);

        $duplication_user_group_mapping = TrackerDuplicationUserGroupMapping::fromMapping($ugroup_mapping, $template_tracker, $project_id);

        // Duplicate workflow
        foreach ($field_mapping as $mapping) {
            if ($mapping['workflow']) {
                WorkflowFactory::instance()->duplicate(
                    $id_template,
                    $id,
                    $mapping['from'],
                    $mapping['to'],
                    $mapping['values'],
                    $field_mapping,
                    $duplication_user_group_mapping,
                );
            }
        }
        // Duplicate Reports
        $report_mapping = Tracker_ReportFactory::instance()->duplicate(
            $id_template,
            $id,
            $field_mapping,
            $mapping_registry
        );

        // Duplicate Semantics
        Tracker_SemanticFactory::instance()->duplicate(
            (int) $id_template,
            $id,
            $field_mapping
        );

        // Duplicate Canned Responses
        Tracker_CannedResponseFactory::instance()->duplicate($id_template, $id);
        //Duplicate field dependencies
        $this->getRuleFactory()->duplicate($id_template, $id, $field_mapping);

        $notification_settings_duplicator = new NotificationSettingsDuplicator(
            new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
            new GlobalNotificationDuplicationDao(),
            new UsersToNotifyDuplicationDao(),
            new UgroupsToNotifyDuplicationDao(),
            new ConfigNotificationAssignedToDao(),
            new ConfigNotificationEmailCustomSenderDao(),
            new DateReminderDao(),
        );
        $notification_settings_duplicator->duplicate((int) $id_template, $id, $duplication_user_group_mapping, $field_mapping);

        $tracker = $this->getTrackerById($id);

        // Process event that tracker is created
        $em          = EventManager::instance();
        $pref_params = [
            'atid_source' => $id_template,
            'atid_dest'   => $id,
        ];
        $em->processEvent('Tracker_created', $pref_params);
        //Duplicate Permissions
        $this->duplicatePermissions($id_template, $id, $field_mapping, $duplication_user_group_mapping);

        $source_tracker = $this->getTrackerById($id_template);
        if ($tracker === null || $source_tracker === null) {
            throw new RuntimeException('Tracker does not exist');
        }
        $this->duplicateWebhooks($source_tracker, $tracker);

        $builder = new TrackerCreationSettingsBuilder(new PromotedTrackerDao(), new TrackerPrivateCommentUGroupEnabledDao());
        $this->postCreateActions($tracker, $builder->build($source_tracker));

        return [
            'tracker'        => $tracker,
            'field_mapping'  => $field_mapping,
            'report_mapping' => $report_mapping,
        ];
    }

    private function duplicateWebhooks(Tracker $source_tracker, Tracker $tracker)
    {
        $this->getWebhookFactory()->duplicateWebhookFromSourceTracker($source_tracker, $tracker);
    }

    /**
     * @return WebhookFactory
     */
    private function getWebhookFactory()
    {
        return new WebhookFactory(new WebhookDao());
    }

   /**
    * @param int $id_template the id of the duplicated tracker
    * @param int $id          the id of the new tracker
    * @param array $field_mapping
    */
    public function duplicatePermissions($id_template, $id, $field_mapping, TrackerDuplicationUserGroupMapping $duplication_user_group_mapping): void
    {
        $pm                      = PermissionsManager::instance();
        $permission_type_tracker = [Tracker::PERMISSION_ADMIN, Tracker::PERMISSION_SUBMITTER, Tracker::PERMISSION_SUBMITTER_ONLY, Tracker::PERMISSION_ASSIGNEE, Tracker::PERMISSION_FULL, Tracker::PERMISSION_NONE];
        //Duplicate tracker permissions
        $pm->duplicatePermissions($id_template, $id, $permission_type_tracker, $duplication_user_group_mapping);

        $permission_type_field = ['PLUGIN_TRACKER_FIELD_SUBMIT', 'PLUGIN_TRACKER_FIELD_READ', 'PLUGIN_TRACKER_FIELD_UPDATE', 'PLUGIN_TRACKER_NONE'];
        //Duplicate fields permissions
        foreach ($field_mapping as $f) {
            $from = $f['from'];
            $to   = $f['to'];
            $pm->duplicatePermissions($from, $to, $permission_type_field, $duplication_user_group_mapping);
        }
    }

    protected function postCreateActions(Tracker $tracker, TrackerCreationSettings $settings): void
    {
        $processor = PostCreationProcessor::build();
        $processor->postCreationProcess($tracker, $settings);
    }

    /**
     * Duplicate all trackers from a project to another one
     *
     * Duplicate among others:
     * - the tracker's definition
     * - the hierarchy
     * - the shared fields
     * - etc.
     */
    public function duplicate(DBTransactionExecutor $transaction_executor, int $from_project_id, int $to_project_id, MappingRegistry $mapping_registry): void
    {
        $tracker_ids_list = [];
        $params           = ['project_id' => $from_project_id, 'tracker_ids_list' => &$tracker_ids_list];
        EventManager::instance()->processEvent(self::TRACKER_EVENT_PROJECT_CREATION_TRACKERS_REQUIRED, $params);
        $tracker_ids_list = array_unique($tracker_ids_list);

        [$trackers_from_template, $tracker_mapping, $field_mapping, $report_mapping] = $transaction_executor->execute(
            /**
             * @return array{0: Tracker[], 1: array, 2: list<array{from: int, to: int, values: array, workflow: bool}>, 3: array}
             */
            function () use ($from_project_id, $tracker_ids_list, $mapping_registry, $to_project_id) {
                $tracker_mapping = [];
                /** @var list<array{from: int, to: int, values: array, workflow: bool}> $field_mapping */
                $field_mapping          = [];
                $report_mapping         = [];
                $trackers_from_template = [];

                foreach ($this->getTrackersByGroupId($from_project_id) as $tracker) {
                    if ($tracker->mustBeInstantiatedForNewProjects() || in_array($tracker->getId(), $tracker_ids_list)) {
                        $trackers_from_template[]                           = $tracker;
                        [$tracker_mapping, $field_mapping, $report_mapping] = $this->duplicateTracker(
                            $tracker_mapping,
                            $field_mapping,
                            $report_mapping,
                            $tracker,
                            $mapping_registry,
                            $to_project_id,
                            $mapping_registry->getUgroupMapping()
                        );
                        /*
                         * @todo
                         * Unless there is some odd dependency on the last tracker meeting
                         * the requirement of the if() condition then there should be a break here.
                         */
                    }
                }

                return [
                    $trackers_from_template,
                    $tracker_mapping,
                    $field_mapping,
                    $report_mapping,
                ];
            }
        );

        if (! empty($tracker_mapping)) {
            $mapping_registry->setCustomMapping(self::TRACKER_MAPPING_KEY, $tracker_mapping);

            $hierarchy_factory = $this->getHierarchyFactory();
            $hierarchy_factory->duplicate($tracker_mapping);

            $trigger_rules_manager = $this->getTriggerRulesManager();
            $trigger_rules_manager->duplicate($trackers_from_template, $field_mapping);

            $timeframe_duplicator = $this->getSemanticTimeframeDuplicator();
            $timeframe_duplicator->duplicateSemanticTimeframeForAllTrackers($field_mapping, $tracker_mapping);
        }
        $shared_factory = $this->getFormElementFactory();
        $shared_factory->fixOriginalFieldIdsAfterDuplication($to_project_id, $from_project_id, $field_mapping);

        EventManager::instance()->processEvent(new TrackerEventTrackersDuplicated(
            $tracker_mapping,
            $field_mapping,
            $report_mapping,
            $to_project_id,
            $mapping_registry->getUgroupMapping(),
            $from_project_id,
            $mapping_registry,
        ));
    }

    /**
     * @return Tracker_Workflow_Trigger_RulesManager
     */
    public function getTriggerRulesManager()
    {
        $trigger_rule_dao        = new Tracker_Workflow_Trigger_RulesDao();
        $workflow_backend_logger = new WorkflowBackendLogger(BackendLogger::getDefaultLogger(), ForgeConfig::get('sys_logger_level'));
        $rules_processor         = new Tracker_Workflow_Trigger_RulesProcessor(
            new Tracker_Workflow_WorkflowUser(),
            new SiblingsRetriever(
                new SiblingsDao(),
                Tracker_ArtifactFactory::instance()
            ),
            $workflow_backend_logger
        );

        return new Tracker_Workflow_Trigger_RulesManager(
            $trigger_rule_dao,
            $this->getFormElementFactory(),
            $rules_processor,
            $workflow_backend_logger,
            new Tracker_Workflow_Trigger_RulesBuilderFactory($this->getFormElementFactory()),
            new WorkflowRulesManagerLoopSafeGuard($workflow_backend_logger)
        );
    }

    /**
     * @param list<array{from: int, to: int, values: array, workflow: bool}> $field_mapping
     *
     * @return array{0: array, 1: list<array{from: int, to: int, values: array, workflow: bool}>, 2: array}
     */
    private function duplicateTracker(
        array $tracker_mapping,
        array $field_mapping,
        array $report_mapping,
        Tracker $tracker,
        MappingRegistry $mapping_registry,
        $to_project_id,
        $ugroup_mapping,
    ) {
        $tracker_and_field_and_report_mapping = $this->create(
            $to_project_id,
            $mapping_registry,
            $tracker->getId(),
            $tracker->getName(),
            $tracker->getDescription(),
            $tracker->getItemName(),
            $tracker->getColor()->getName(),
            $ugroup_mapping
        );

        if ($tracker_and_field_and_report_mapping !== null) {
            $tracker_mapping[$tracker->getId()] = $tracker_and_field_and_report_mapping['tracker']->getId();
            $field_mapping                      = array_merge($field_mapping, $tracker_and_field_and_report_mapping['field_mapping']);
            $report_mapping                     = $report_mapping + $tracker_and_field_and_report_mapping['report_mapping'];
        } else {
            $GLOBALS['Response']->addFeedback('warning', sprintf(dgettext('tuleap-tracker', 'Tracker %1$s not duplicated'), $tracker->getName()));
        }

        return [
            $tracker_mapping,
            $field_mapping,
            $report_mapping,
        ];
    }

    /**
     * /!\ Only for tests
     */
    public function setHierarchyFactory(Tracker_HierarchyFactory $hierarchy_factory)
    {
        $this->hierarchy_factory = $hierarchy_factory;
    }

    /**
     * @return Tracker_HierarchyFactory
     */
    public function getHierarchyFactory()
    {
        if (! $this->hierarchy_factory) {
            $this->hierarchy_factory = Tracker_HierarchyFactory::instance();
        }
        return $this->hierarchy_factory;
    }

    /**
     * @return Hierarchy
     */
    public function getHierarchy(array $tracker_ids)
    {
        return $this->getHierarchyFactory()->getHierarchy($tracker_ids);
    }

    /**
     * Saves the default permission of a tracker in the db
     *
     * @param int $tracker_id the id of the tracker
     * @return bool
     */
    public function saveTrackerDefaultPermission($tracker_id)
    {
        $pm = PermissionsManager::instance();
        if (! $pm->addPermission(Tracker::PERMISSION_FULL, $tracker_id, ProjectUGroup::ANONYMOUS)) {
            return false;
        }
        return true;
    }

    public function saveObject(Tracker $tracker, TrackerCreationSettings $settings): int
    {
        // create tracker
        $transaction_executor = $this->getTransactionExecutor();

        return $transaction_executor->execute(
            function () use ($tracker, $settings) {
                $tracker_id = $this->getDao()->create(
                    $tracker->group_id,
                    $tracker->name,
                    $tracker->description,
                    $tracker->item_name,
                    $tracker->allow_copy,
                    $tracker->submit_instructions,
                    $tracker->browse_instructions,
                    '',
                    '',
                    $tracker->instantiate_for_new_projects,
                    $tracker->log_priority_changes,
                    $tracker->getNotificationsLevel(),
                    $tracker->getColor()->getName(),
                    $tracker->isEmailgatewayEnabled()
                );
                if ($tracker_id) {
                    $trackerDB = $this->getTrackerById($tracker_id);
                    if ($trackerDB === null) {
                        throw new RuntimeException('Tracker does not exist');
                    }
                    //create cannedResponses
                    $response_factory = $tracker->getCannedResponseFactory();
                    foreach ($tracker->cannedResponses as $response) {
                        $response_factory->saveObject($tracker_id, $response);
                    }
                    //create formElements
                    foreach ($tracker->formElements as $formElement) {
                        // these fields have no parent
                        Tracker_FormElementFactory::instance()->saveObject($trackerDB, $formElement, 0, true, true);
                    }
                    //create report
                    foreach ($tracker->reports as $report) {
                        $id = Tracker_ReportFactory::instance()->saveObject($tracker_id, $report);
                        $report->setId($id);
                    }
                    //create semantics
                    if (isset($tracker->semantics)) {
                        foreach ($tracker->semantics as $semantic) {
                            Tracker_SemanticFactory::instance()->saveObject($semantic, $trackerDB);
                        }
                    }
                    //create rules
                    if (isset($tracker->rules)) {
                        $this->getRuleFactory()->saveObject($tracker->rules, $trackerDB);
                    }
                    //create workflow
                    if (isset($tracker->workflow)) {
                        WorkflowFactory::instance()->saveObject($tracker->workflow, $trackerDB);
                    }

                    if (count($tracker->webhooks) > 0) {
                        $this->getWebhookFactory()->saveWebhooks($tracker->webhooks, $tracker_id);
                    }

                    //tracker permissions
                    if ($tracker->permissionsAreCached()) {
                        $pm = PermissionsManager::instance();
                        foreach ($tracker->getPermissionsByUgroupId() as $ugroup => $permissions) {
                            foreach ($permissions as $permission) {
                                $pm->addPermission($permission, $tracker_id, $ugroup);
                            }
                        }
                    } else {
                        $this->saveTrackerDefaultPermission($tracker_id);
                    }

                    $this->postCreateActions($trackerDB, $settings);
                }

                return (int) $tracker_id;
            }
        );
    }

    /**
     * Create a tracker v5 from a tracker v3
     *
     * @param PFUser         $user           the user who requested the creation
     * @param int            $atid           the id of the tracker v3
     * @param Project        $project        the Id of the project to create the tracker
     * @param string         $name           the name of the tracker (label)
     * @param string         $description    the description of the tracker
     * @param string         $itemname       the short name of the tracker
     *
     * @throws Tracker_Exception_Migration_GetTv3Exception
     *
     * @return Tracker
     */
    public function createFromTV3(PFUser $user, $atid, Project $project, $name, $description, $itemname)
    {
        $tv3 = new ArtifactType($project, $atid);
        if ($tv3->isError()) {
            throw new Tracker_Exception_Migration_GetTv3Exception($tv3->getErrorMessage());
        }
        // Check if this tracker is valid (not deleted)
        if (! $tv3->isValid()) {
            throw new Tracker_Exception_Migration_GetTv3Exception($GLOBALS['Language']->getText('tracker_add', 'invalid'));
        }
        //Check if the user can view the artifact
        if (! $tv3->userCanView($user->getId())) {
            throw new Tracker_Exception_Migration_GetTv3Exception($GLOBALS['Language']->getText('include_exit', 'no_perm'));
        }

        return $this->createTracker($name, $description, $itemname, $project, $tv3);
    }

    public function createFromTV3LegacyService(PFUser $user, ArtifactType $tracker_v3, Project $project)
    {
        $name        = $tracker_v3->getName();
        $description = $tracker_v3->getDescription();
        $itemname    = $tracker_v3->getItemName();

        if ($this->isNameExists($name, $project->getID())) {
            $name = $name . self::LEGACY_SUFFIX;
        }

        if ($this->isShortNameExists($itemname, $project->getID())) {
            $itemname = $itemname . self::LEGACY_SUFFIX;
        }

        return $this->createTracker($name, $description, $itemname, $project, $tracker_v3);
    }

    private function createTracker($name, $description, $itemname, Project $project, ArtifactType $tv3)
    {
        $tracker = null;
        try {
            $this->getTrackerChecker()->checkAtProjectCreation((int) $project->getId(), $name, $itemname);
            $migration_v3 = new Tracker_Migration_V3($this);
            $tracker      = $migration_v3->createTV5FromTV3($project, $name, $description, $itemname, $tv3);

            $settings = new TrackerCreationSettings(false, true);
            $this->postCreateActions($tracker, $settings);

            return $tracker;
        } catch (TrackerIsInvalidException $exception) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $exception->getTranslatedMessage());
        }
    }

    protected function getTrackerChecker(): TrackerCreationDataChecker
    {
        return TrackerCreationDataChecker::build();
    }

    protected function getTransactionExecutor(): DBTransactionExecutorWithConnection
    {
        return new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection());
    }

    /**
     * protected for testing purpose
     */
    protected function getSemanticTimeframeDuplicator(): SemanticTimeframeDuplicator
    {
        return new SemanticTimeframeDuplicator(new SemanticTimeframeDao());
    }
}
