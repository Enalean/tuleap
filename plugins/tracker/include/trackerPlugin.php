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

use Laminas\HttpHandlerRunner\Emitter\SapiStreamEmitter;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\admin\ProjectEdit\ProjectStatusUpdate;
use Tuleap\Admin\SiteAdministrationAddOption;
use Tuleap\Admin\SiteAdministrationPluginOption;
use Tuleap\AgileDashboard\REST\v1\Milestone\MilestoneRepresentationBuilder;
use Tuleap\AgileDashboard\REST\v1\MilestoneResource;
use Tuleap\Authentication\Scope\AuthenticationScopeBuilderFromClassNames;
use Tuleap\BurningParrotCompatiblePageEvent;
use Tuleap\CLI\CLICommandsCollector;
use Tuleap\Config\ConfigClassProvider;
use Tuleap\Config\PluginWithConfigKeys;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\Dashboard\User\AtUserCreationDefaultWidgetsCreator;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Event\Events\ExportXmlProject;
use Tuleap\Event\Events\PotentialReferencesReceived;
use Tuleap\Glyph\GlyphLocation;
use Tuleap\Glyph\GlyphLocationsCollector;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\BinaryFileResponseBuilder;
use Tuleap\Http\Server\SessionWriteCloseMiddleware;
use Tuleap\Instrument\Prometheus\CollectTuleapComputedMetrics;
use Tuleap\Language\LocaleSwitcher;
use Tuleap\Layout\HomePage\StatisticsCollectionCollector;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\NewDropdown\NewDropdownProjectLinksCollector;
use Tuleap\Mail\MailFilter;
use Tuleap\Mail\MailLogger;
use Tuleap\Mail\Transport\MailTransportBuilder;
use Tuleap\Plugin\ListeningToEventClass;
use Tuleap\Project\Admin\History\GetHistoryKeyLabel;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupDisplayEvent;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupPaneCollector;
use Tuleap\Project\Admin\TemplatePresenter;
use Tuleap\Project\Event\GetProjectWithTrackerAdministrationPermission;
use Tuleap\Project\Event\GetUriFromCrossReference;
use Tuleap\Project\Event\ProjectRegistrationActivateService;
use Tuleap\Project\Event\ProjectServiceBeforeActivation;
use Tuleap\Project\Event\ProjectXMLImportPreChecksEvent;
use Tuleap\Project\HeartbeatsEntryCollection;
use Tuleap\Project\PaginatedProjects;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\Registration\RegisterProjectCreationEvent;
use Tuleap\Project\Registration\Template\DefineIssueTemplateEvent;
use Tuleap\Project\Registration\Template\IssuesTemplateDashboardDefinition;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\Project\Service\AddMissingService;
use Tuleap\Project\Service\PluginWithService;
use Tuleap\Project\Service\ServiceClassnamesCollector;
use Tuleap\Project\Service\ServiceDisabledCollector;
use Tuleap\Project\XML\Export\ArchiveInterface;
use Tuleap\Project\XML\Export\NoArchive;
use Tuleap\Project\XML\Import\ExternalFieldsExtractor;
use Tuleap\Project\XML\Import\ImportNotValidException;
use Tuleap\Project\XML\ServiceEnableForXmlImportRetriever;
use Tuleap\Queue\QueueFactory;
use Tuleap\Queue\WorkerEvent;
use Tuleap\Reference\CheckCrossReferenceValidityEvent;
use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Reference\GetReferenceEvent;
use Tuleap\Reference\Nature;
use Tuleap\Reference\NatureCollection;
use Tuleap\Request\CurrentPage;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\REST\BasicAuthentication;
use Tuleap\REST\RESTCurrentUserMiddleware;
use Tuleap\REST\TuleapRESTCORSMiddleware;
use Tuleap\REST\UserManager as RESTUserManager;
use Tuleap\Search\IndexAllPendingItemsEvent;
use Tuleap\Search\IndexedItemFoundToSearchResult;
use Tuleap\Search\ItemToIndexQueueEventBased;
use Tuleap\Service\ServiceCreator;
use Tuleap\Statistics\CSV\StatisticsServiceUsage;
use Tuleap\SystemEvent\GetSystemEventQueuesEvent;
use Tuleap\Tracker\Admin\ArtifactDeletion\ArtifactsDeletionConfig;
use Tuleap\Tracker\Admin\ArtifactDeletion\ArtifactsDeletionConfigController;
use Tuleap\Tracker\Admin\ArtifactDeletion\ArtifactsDeletionConfigDAO;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDuplicator;
use Tuleap\Tracker\Admin\ArtifactLinksUsageUpdater;
use Tuleap\Tracker\Admin\GlobalAdmin\ArtifactLinks\ArtifactLinksController;
use Tuleap\Tracker\Admin\GlobalAdmin\Trackers\CSRFSynchronizerTokenProvider;
use Tuleap\Tracker\Admin\GlobalAdmin\Trackers\MarkTrackerAsDeletedController;
use Tuleap\Tracker\Admin\GlobalAdmin\Trackers\PromoteTrackersController;
use Tuleap\Tracker\Admin\GlobalAdmin\Trackers\TrackersDisplayController;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ArtifactsDeletion\ArchiveAndDeleteArtifactTaskBuilder;
use Tuleap\Tracker\Artifact\ArtifactsDeletion\ArtifactDeletor;
use Tuleap\Tracker\Artifact\ArtifactsDeletion\ArtifactsDeletionDAO;
use Tuleap\Tracker\Artifact\ArtifactsDeletion\ArtifactsDeletionRemover;
use Tuleap\Tracker\Artifact\ArtifactsDeletion\AsynchronousArtifactsDeletionActionsRunner;
use Tuleap\Tracker\Artifact\ArtifactsDeletion\PendingArtifactRemovalDao;
use Tuleap\Tracker\Artifact\Changeset\AfterNewChangesetHandler;
use Tuleap\Tracker\Artifact\Changeset\ArtifactChangesetSaver;
use Tuleap\Tracker\Artifact\Changeset\Comment\ChangesetCommentIndexer;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentCreator;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupPermissionDao;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupPermissionInserter;
use Tuleap\Tracker\Artifact\Changeset\CommentOnlyChangesetCreator;
use Tuleap\Tracker\Artifact\Changeset\FieldsToBeSavedInSpecificOrderRetriever;
use Tuleap\Tracker\Artifact\Changeset\NewChangesetCreator;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\ActionsRunner;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\AsynchronousActionsRunner;
use Tuleap\Tracker\Artifact\Changeset\TextDiff\ChangesetsForDiffRetriever;
use Tuleap\Tracker\Artifact\Changeset\TextDiff\DiffProcessor;
use Tuleap\Tracker\Artifact\Changeset\TextDiff\TextDiffRetriever;
use Tuleap\Tracker\Artifact\ChangesetValue\ChangesetValueSaver;
use Tuleap\Tracker\Artifact\Closure\ArtifactCloser;
use Tuleap\Tracker\Artifact\Closure\ArtifactClosingReferencesHandler;
use Tuleap\Tracker\Artifact\Closure\ArtifactWasClosedCache;
use Tuleap\Tracker\Artifact\CrossReference\CrossReferenceArtifactOrganizer;
use Tuleap\Tracker\Artifact\InvertCommentsController;
use Tuleap\Tracker\Artifact\InvertDisplayChangesController;
use Tuleap\Tracker\Artifact\LatestHeartbeatsCollector;
use Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfig;
use Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfigController;
use Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfigDao;
use Tuleap\Tracker\Artifact\RecentlyVisited\RecentlyVisitedDao;
use Tuleap\Tracker\Artifact\StatusBadgeBuilder;
use Tuleap\Tracker\Colorpicker\ColorpickerMountPointPresenterBuilder;
use Tuleap\Tracker\Config\ConfigController;
use Tuleap\Tracker\Creation\DefaultTemplatesCollectionBuilder;
use Tuleap\Tracker\Creation\JiraImporter\AsynchronousJiraRunner;
use Tuleap\Tracker\Creation\JiraImporter\AsyncJiraScheduler;
use Tuleap\Tracker\Creation\JiraImporter\ClientWrapper;
use Tuleap\Tracker\Creation\JiraImporter\ClientWrapperBuilder;
use Tuleap\Tracker\Creation\JiraImporter\FromJiraTrackerCreator;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment\AttachmentCleaner;
use Tuleap\Tracker\Creation\JiraImporter\Import\ImportNotifier\CancellationOfJiraImportNotifier;
use Tuleap\Tracker\Creation\JiraImporter\Import\ImportNotifier\JiraErrorImportNotifier;
use Tuleap\Tracker\Creation\JiraImporter\Import\ImportNotifier\JiraImportNotifier;
use Tuleap\Tracker\Creation\JiraImporter\Import\ImportNotifier\JiraSuccessImportNotifier;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraTuleapUsersMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraUserOnTuleapCache;
use Tuleap\Tracker\Creation\JiraImporter\JiraProjectBuilder;
use Tuleap\Tracker\Creation\JiraImporter\JiraProjectListController;
use Tuleap\Tracker\Creation\JiraImporter\JiraRunner;
use Tuleap\Tracker\Creation\JiraImporter\JiraTrackerBuilder;
use Tuleap\Tracker\Creation\JiraImporter\JiraTrackersListController;
use Tuleap\Tracker\Creation\JiraImporter\PendingJiraImportBuilder;
use Tuleap\Tracker\Creation\JiraImporter\PendingJiraImportCleaner;
use Tuleap\Tracker\Creation\JiraImporter\PendingJiraImportDao;
use Tuleap\Tracker\Creation\TrackerCreationBreadCrumbsBuilder;
use Tuleap\Tracker\Creation\TrackerCreationController;
use Tuleap\Tracker\Creation\TrackerCreationDataChecker;
use Tuleap\Tracker\Creation\TrackerCreationPermissionChecker;
use Tuleap\Tracker\Creation\TrackerCreationPresenterBuilder;
use Tuleap\Tracker\Creation\TrackerCreationProcessorController;
use Tuleap\Tracker\Creation\TrackerCreator;
use Tuleap\Tracker\ForgeUserGroupPermission\TrackerAdminAllProjects;
use Tuleap\Tracker\FormElement\ArtifactLinkValidator;
use Tuleap\Tracker\FormElement\BurndownCacheDateRetriever;
use Tuleap\Tracker\FormElement\BurndownCalculator;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ParentLinkAction;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeConfigController;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeCreator;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDeletor;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeEditor;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeUsagePresenterFactory;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeValidator;
use Tuleap\Tracker\FormElement\Field\Burndown\BurndownFieldDao;
use Tuleap\Tracker\FormElement\Field\Computed\ComputedFieldDao;
use Tuleap\Tracker\FormElement\Field\Computed\ComputedFieldDaoCache;
use Tuleap\Tracker\FormElement\Field\FieldDao;
use Tuleap\Tracker\FormElement\Field\File\AttachmentController;
use Tuleap\Tracker\FormElement\Field\File\Upload\FileOngoingUploadDao;
use Tuleap\Tracker\FormElement\Field\File\Upload\FileUploadCleaner;
use Tuleap\Tracker\FormElement\Field\File\Upload\Tus\FileBeingUploadedInformationProvider;
use Tuleap\Tracker\FormElement\Field\File\Upload\Tus\FileDataStore;
use Tuleap\Tracker\FormElement\Field\File\Upload\Tus\FileUploadCanceler;
use Tuleap\Tracker\FormElement\Field\File\Upload\Tus\FileUploadFinisher;
use Tuleap\Tracker\FormElement\Field\File\Upload\UploadPathAllocator;
use Tuleap\Tracker\FormElement\Field\Text\TextValueValidator;
use Tuleap\Tracker\FormElement\FieldCalculator;
use Tuleap\Tracker\FormElement\SystemEvent\SystemEvent_BURNDOWN_DAILY;
use Tuleap\Tracker\FormElement\SystemEvent\SystemEvent_BURNDOWN_GENERATE;
use Tuleap\Tracker\Import\Spotter;
use Tuleap\Tracker\Legacy\Inheritor;
use Tuleap\Tracker\Migration\KeepReverseCrossReferenceDAO;
use Tuleap\Tracker\Migration\LegacyTrackerMigrationDao;
use Tuleap\Tracker\NewDropdown\TrackerInNewDropdownDao;
use Tuleap\Tracker\NewDropdown\TrackerInNewDropdownRetriever;
use Tuleap\Tracker\NewDropdown\TrackerNewDropdownLinkPresenterBuilder;
use Tuleap\Tracker\Notifications\CollectionOfUgroupToBeNotifiedPresenterBuilder;
use Tuleap\Tracker\Notifications\CollectionOfUserInvolvedInNotificationPresenterBuilder;
use Tuleap\Tracker\Notifications\GlobalNotificationsAddressesBuilder;
use Tuleap\Tracker\Notifications\GlobalNotificationSubscribersFilter;
use Tuleap\Tracker\Notifications\InvolvedNotificationDao;
use Tuleap\Tracker\Notifications\NotificationLevelExtractor;
use Tuleap\Tracker\Notifications\NotificationListBuilder;
use Tuleap\Tracker\Notifications\NotificationsForceUsageUpdater;
use Tuleap\Tracker\Notifications\NotificationsForProjectMemberCleaner;
use Tuleap\Tracker\Notifications\RecipientsManager;
use Tuleap\Tracker\Notifications\Settings\NotificationsAdminSettingsDisplayController;
use Tuleap\Tracker\Notifications\Settings\NotificationsAdminSettingsUpdateController;
use Tuleap\Tracker\Notifications\Settings\NotificationsUserSettingsDisplayController;
use Tuleap\Tracker\Notifications\Settings\NotificationsUserSettingsUpdateController;
use Tuleap\Tracker\Notifications\Settings\UserNotificationSettingsDAO;
use Tuleap\Tracker\Notifications\Settings\UserNotificationSettingsRetriever;
use Tuleap\Tracker\Notifications\TrackerForceNotificationsLevelCommand;
use Tuleap\Tracker\Notifications\UgroupsToNotifyDao;
use Tuleap\Tracker\Notifications\UgroupsToNotifyUpdater;
use Tuleap\Tracker\Notifications\UnsubscribersNotificationDAO;
use Tuleap\Tracker\Notifications\UserNotificationOnlyStatusChangeDAO;
use Tuleap\Tracker\Notifications\UsersToNotifyDao;
use Tuleap\Tracker\Permission\Fields\ByField\ByFieldController;
use Tuleap\Tracker\Permission\Fields\ByGroup\ByGroupController;
use Tuleap\Tracker\Permission\Fields\PermissionsOnFieldsUpdateController;
use Tuleap\Tracker\PermissionsPerGroup\ProjectAdminPermissionPerGroupPresenterBuilder;
use Tuleap\Tracker\ProjectDeletionEvent;
use Tuleap\Tracker\Reference\CrossReferenceValidator;
use Tuleap\Tracker\Reference\ReferenceCreator;
use Tuleap\Tracker\Report\TrackerReportConfig;
use Tuleap\Tracker\Report\TrackerReportConfigController;
use Tuleap\Tracker\Report\TrackerReportConfigDao;
use Tuleap\Tracker\REST\OAuth2\OAuth2TrackerReadScope;
use Tuleap\Tracker\Rule\FirstValidValueAccordingToDependenciesRetriever;
use Tuleap\Tracker\Search\IndexAllArtifactsProcessor;
use Tuleap\Tracker\Semantic\Status\Done\DoneValueRetriever;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneDao;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneFactory;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneValueChecker;
use Tuleap\Tracker\Semantic\Status\StatusFieldRetriever;
use Tuleap\Tracker\Semantic\Status\StatusValueRetriever;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\Service\PromotedTrackerConfiguration;
use Tuleap\Tracker\Service\ServiceActivator;
use Tuleap\Tracker\User\NotificationOnOwnActionPreference;
use Tuleap\Tracker\Webhook\Actions\WebhookCreateController;
use Tuleap\Tracker\Webhook\Actions\WebhookDeleteController;
use Tuleap\Tracker\Webhook\Actions\WebhookEditController;
use Tuleap\Tracker\Webhook\Actions\WebhookURLValidator;
use Tuleap\Tracker\Webhook\WebhookDao;
use Tuleap\Tracker\Webhook\WebhookFactory;
use Tuleap\Tracker\Widget\ProjectRendererWidgetXMLImporter;
use Tuleap\Tracker\Workflow\FirstPossibleValueInListRetriever;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldDetector;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsRetriever;
use Tuleap\Tracker\Workflow\SimpleMode\SimpleWorkflowDao;
use Tuleap\Tracker\Workflow\SimpleMode\State\StateFactory;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionExtractor;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionRetriever;
use Tuleap\Tracker\Workflow\Trigger\TriggersDao;
use Tuleap\Tracker\Workflow\ValidValuesAccordingToTransitionsRetriever;
use Tuleap\Tracker\Workflow\WorkflowMenuTabPresenterBuilder;
use Tuleap\Tracker\Workflow\WorkflowTransitionController;
use Tuleap\Tracker\Workflow\WorkflowUpdateChecker;
use Tuleap\Tracker\XML\Importer\TrackerImporterUser;
use Tuleap\Upload\FileBeingUploadedLocker;
use Tuleap\Upload\FileBeingUploadedWriter;
use Tuleap\Upload\FileUploadController;
use Tuleap\User\Account\NotificationsOnOwnActionsCollection;
use Tuleap\User\Account\NotificationsOnOwnActionsUpdate;
use Tuleap\User\History\HistoryEntryCollection;
use Tuleap\User\History\HistoryRetriever;
use Tuleap\User\OAuth2\Scope\OAuth2ScopeBuilderCollector;
use Tuleap\User\Preferences\UserPreferencesGetDefaultValue;
use Tuleap\User\User_ForgeUserGroupPermissionsFactory;
use Tuleap\Widget\Event\ConfigureAtXMLImport;
use Tuleap\Widget\Event\GetPublicAreas;

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../include/manual_autoload.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class trackerPlugin extends Plugin implements PluginWithConfigKeys, PluginWithService
{
    public final const TRACKER_EVENT_INCLUDE_CSS_FILE = 'tracker_event_include_css_file';

    public const EMAILGATEWAY_TOKEN_ARTIFACT_UPDATE      = 'forge__artifacts';
    public const EMAILGATEWAY_INSECURE_ARTIFACT_CREATION = 'forge__tracker';
    public const EMAILGATEWAY_INSECURE_ARTIFACT_UPDATE   = 'forge__artifact';
    public const SERVICE_SHORTNAME                       = 'plugin_tracker';
    public const TRUNCATED_SERVICE_NAME                  = 'Trackers';

    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
        bindtextdomain('tuleap-tracker', __DIR__ . '/../site-content');

        $this->addHook('javascript_file');
        $this->addHook('cssfile', 'cssFile');
        $this->addHook(NatureCollection::NAME);
        $this->addHook(Event::GET_ARTIFACT_REFERENCE_GROUP_ID, 'get_artifact_reference_group_id');
        $this->addHook(Event::SET_ARTIFACT_REFERENCE_GROUP_ID);
        $this->addHook(Event::BUILD_REFERENCE, 'build_reference');
        $this->addHook(Event::JAVASCRIPT, 'javascript');
        $this->addHook(Event::TOGGLE, 'toggle');
        $this->addHook(GetPublicAreas::NAME);
        $this->addHook('permission_get_name', 'permission_get_name');
        $this->addHook('permission_get_object_type', 'permission_get_object_type');
        $this->addHook('permission_get_object_name', 'permission_get_object_name');
        $this->addHook('permission_user_allowed_to_change', 'permission_user_allowed_to_change');
        $this->addHook(GetSystemEventQueuesEvent::NAME);
        $this->addHook(Event::SYSTEM_EVENT_GET_TYPES_FOR_CUSTOM_QUEUE);
        $this->addHook(Event::GET_SYSTEM_EVENT_CLASS, 'getSystemEventClass');

        $this->addHook('url_verification_instance', 'url_verification_instance');

        $this->addHook(Event::PROCCESS_SYSTEM_CHECK);
        $this->addHook(Event::SERVICES_ALLOWED_FOR_PROJECT);

        $this->addHook(\Tuleap\Widget\Event\GetWidget::NAME);
        $this->addHook(\Tuleap\Widget\Event\GetUserWidgetList::NAME);
        $this->addHook(\Tuleap\Widget\Event\GetProjectWidgetList::NAME);
        $this->addHook(AtUserCreationDefaultWidgetsCreator::DEFAULT_WIDGETS_FOR_NEW_USER);

        $this->addHook(ProjectStatusUpdate::NAME);
        $this->addHook(RegisterProjectCreationEvent::NAME);
        $this->addHook('codendi_daily_start', 'codendi_daily_start');
        $this->addHook('fill_project_history_sub_events', 'fillProjectHistorySubEvents');
        $this->addHook(Event::IMPORT_XML_PROJECT);
        $this->addHook(ProjectXMLImportPreChecksEvent::NAME);
        $this->addHook(Event::COLLECT_ERRORS_WITHOUT_IMPORTING_XML_PROJECT);
        $this->addHook(Event::USER_MANAGER_GET_USER_INSTANCE);
        $this->addHook(Event::REST_RESOURCES);
        $this->addHook(Event::REST_PROJECT_RESOURCES);
        $this->addHook(GetUriFromCrossReference::NAME);
        $this->addHook(CheckCrossReferenceValidityEvent::NAME);

        $this->addHook(Event::BACKEND_ALIAS_GET_ALIASES);
        $this->addHook(Event::GET_PROJECTID_FROM_URL);
        $this->addHook(ExportXmlProject::NAME);
        $this->addHook(GetReferenceEvent::NAME);
        $this->addHook(Event::CAN_USER_ACCESS_UGROUP_INFO);
        $this->addHook(Event::SERVICES_TRUNCATED_EMAILS);
        $this->addHook(SiteAdministrationAddOption::NAME);
        $this->addHook(BurningParrotCompatiblePageEvent::NAME);
        $this->addHook(Event::BURNING_PARROT_GET_STYLESHEETS);
        $this->addHook(PermissionPerGroupDisplayEvent::NAME);
        $this->addHook(Event::SYSTEM_EVENT_GET_TYPES_FOR_DEFAULT_QUEUE);
        $this->addHook(User_ForgeUserGroupPermissionsFactory::GET_PERMISSION_DELEGATION);

        $this->addHook('project_admin_ugroup_deletion');
        $this->addHook('project_admin_remove_user');
        $this->addHook(Event::PROJECT_ACCESS_CHANGE);
        $this->addHook(Event::SITE_ACCESS_CHANGE);

        $this->addHook(HistoryEntryCollection::NAME);
        $this->addHook(Event::USER_HISTORY_CLEAR, 'clearRecentlyVisitedArtifacts');
        $this->addHook(IndexedItemFoundToSearchResult::NAME);

        $this->addHook(ProjectCreator::PROJECT_CREATION_REMOVE_LEGACY_SERVICES);
        $this->addHook(ProjectRegistrationActivateService::NAME);

        $this->addHook(WorkerEvent::NAME);
        $this->addHook(PermissionPerGroupPaneCollector::NAME);

        $this->addHook(\Tuleap\User\UserAutocompletePostSearchEvent::NAME);

        $this->addHook(\Tuleap\Request\CollectRoutesEvent::NAME);

        $this->addHook(CLICommandsCollector::NAME);
        $this->addHook(GetProjectWithTrackerAdministrationPermission::NAME);
        $this->addHook(PotentialReferencesReceived::NAME);

        $this->addHook(CollectTuleapComputedMetrics::NAME);
        $this->addHook(ConfigureAtXMLImport::NAME);

        $this->addHook(\Tuleap\Search\IndexAllPendingItemsEvent::NAME);
        $this->addHook(\Tuleap\Search\IdentifyAllItemsToIndexEvent::NAME);
    }

    public function getHooksAndCallbacks()
    {
        if (defined('AGILEDASHBOARD_BASE_DIR')) {
            $this->addHook(AgileDashboard_XMLFullStructureExporter::AGILEDASHBOARD_EXPORT_XML);

            // REST Milestones
            $this->addHook(MilestoneRepresentationBuilder::AGILEDASHBOARD_EVENT_REST_GET_MILESTONE);
            $this->addHook(MilestoneResource::AGILEDASHBOARD_EVENT_REST_GET_BURNDOWN);
        }

        $this->addHook(Event::LIST_DELETED_TRACKERS);
        $this->addHook(TemplatePresenter::EVENT_ADDITIONAL_ADMIN_BUTTONS);

        $this->addHook(GlyphLocationsCollector::NAME);
        $this->addHook(HeartbeatsEntryCollection::NAME);
        $this->addHook(StatisticsCollectionCollector::NAME);
        $this->addHook(OAuth2ScopeBuilderCollector::NAME);
        $this->addHook(NewDropdownProjectLinksCollector::NAME);

        $this->addHook(CrossReferenceByNatureOrganizer::NAME);
        $this->addHook(DefineIssueTemplateEvent::NAME);
        $this->addHook(IssuesTemplateDashboardDefinition::NAME);

        $this->addHook(NotificationsOnOwnActionsCollection::NAME);
        $this->addHook(NotificationsOnOwnActionsUpdate::NAME);

        return parent::getHooksAndCallbacks();
    }

    public function getPluginInfo()
    {
        if (! $this->pluginInfo instanceof \trackerPluginInfo) {
            $this->pluginInfo = new trackerPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    /**
     * @see Event::PROCCESS_SYSTEM_CHECK
     */
    public function proccess_system_check(array $params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $file_manager = new Tracker_Artifact_Attachment_TemporaryFileManager(
            $this->getUserManager(),
            new Tracker_Artifact_Attachment_TemporaryFileManagerDao(),
            new System_Command(),
            ForgeConfig::get('sys_file_deletion_delay'),
            new \Tuleap\DB\DBTransactionExecutorWithConnection(\Tuleap\DB\DBFactory::getMainTuleapDBConnection())
        );

        $file_manager->purgeOldTemporaryFiles();
    }

    #[ListeningToEventClass]
    public function statisticsFrequencyLabels(\Tuleap\Statistics\FrequenciesLabels $event): void
    {
        $event->addLabel($this->getServiceShortname(), 'Opened artifacts');
    }

    public function getUriFromCrossReference(GetUriFromCrossReference $event): void
    {
        if ($event->getTargetType() === Artifact::REFERENCE_NATURE) {
            $artifact = Tracker_ArtifactFactory::instance()->getArtifactById($event->getSourceId());
            if ($artifact) {
                $event->setUri($artifact->getUri());
            }
        }
    }

    #[ListeningToEventClass]
    public function statisticsFrequenciesSamples(\Tuleap\Statistics\FrequenciesSamples $event): void
    {
        if ($event->requested_sample === $this->getServiceShortname()) {
            $event->setSample(new Tracker_Sample());
        }
    }

    public function siteAdministrationAddOption(SiteAdministrationAddOption $site_administration_add_option): void
    {
        $site_administration_add_option->addPluginOption(
            SiteAdministrationPluginOption::withShortname(
                dgettext('tuleap-tracker', 'Tracker'),
                $this->getPluginPath() . '/config.php',
                'admin-tracker'
            )
        );
    }

    public function burningParrotCompatiblePage(BurningParrotCompatiblePageEvent $event): void
    {
        if (
            strpos($_SERVER['REQUEST_URI'], $this->getPluginPath() . '/config.php') === 0 ||
            $this->isInTrackersHomepage() ||
            $this->isInDashboard() ||
            $this->isAConvertedSemanticPage()
        ) {
            $event->setIsInBurningParrotCompatiblePage();
        }
    }

    private function isAConvertedSemanticPage(): bool
    {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) !== 0) {
            return false;
        }

        parse_str($_SERVER['QUERY_STRING'], $query_string);

        if (array_keys($query_string) !== ['tracker', 'func']) {
            if (
                ! isset($query_string['semantic']) ||
                (
                    $query_string['semantic'] !== "title" &&
                    $query_string['semantic'] !== "description" &&
                    $query_string['semantic'] !== "status" &&
                    $query_string['semantic'] !== "done" &&
                    $query_string['semantic'] !== "contributor" &&
                    $query_string['semantic'] !== "timeframe" &&
                    $query_string['semantic'] !== "progress" &&
                    $query_string['semantic'] !== "initial_effort" &&
                    $query_string['semantic'] !== "tooltip"
                )
            ) {
                return false;
            }
        }

        return $query_string['func'] === 'admin-semantic';
    }

    private function isInTrackersHomepage(): bool
    {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) !== 0) {
            return false;
        }

        parse_str($_SERVER['QUERY_STRING'], $output);

        return array_keys($output) === ['group_id'];
    }

    public function cssFile()
    {
        $include_tracker_css_file = false;
        EventManager::instance()->processEvent(self::TRACKER_EVENT_INCLUDE_CSS_FILE, ['include_tracker_css_file' => &$include_tracker_css_file]);
        // Only show the stylesheet if we're actually in the tracker pages.
        // This stops styles inadvertently clashing with the main site.
        if (
            $include_tracker_css_file ||
            strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0 ||
            strpos($_SERVER['REQUEST_URI'], '/my/') === 0 ||
            strpos($_SERVER['REQUEST_URI'], '/projects/') === 0 ||
            strpos($_SERVER['REQUEST_URI'], '/widgets/') === 0
        ) {
            $style_css_url = $this->getAssets()->getFileURL('style-fp.css');
            $print_css_url = $this->getAssets()->getFileURL('print.css');

            echo '<link rel="stylesheet" type="text/css" href="' . $style_css_url . '" />';
            echo '<link rel="stylesheet" type="text/css" href="' . $print_css_url . '" media="print" />';
        }
    }

    public function burning_parrot_get_stylesheets($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $include_tracker_css_file = false;
        EventManager::instance()->processEvent(self::TRACKER_EVENT_INCLUDE_CSS_FILE, ['include_tracker_css_file' => &$include_tracker_css_file]);

        if ($include_tracker_css_file || strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            $params['stylesheets'][] = $this->getAssets()->getFileURL('tracker-bp.css');
        }
    }

    public function javascript_file($params): void//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($this->currentRequestIsForPlugin()) {
            $layout = $params['layout'];
            assert($layout instanceof \Layout);
            $legacy_asset = new \Tuleap\Layout\JavascriptAsset(
                new IncludeAssets(__DIR__ . '/../scripts/legacy/frontend-assets', '/assets/trackers/legacy'),
                'tracker.js',
            );
            // DO NOT REPLACE this `includeJavascriptFile()` with `addJavascriptAsset()`
            // The tracker artifact view has script tags in the middle of the body expecting to have access to `tuleap.tracker`
            $layout->includeJavascriptFile($legacy_asset->getFileURL());
            $layout->addJavascriptAsset(new \Tuleap\Layout\JavascriptAsset($this->getAssets(), 'modal-v2.js'));
        }
    }

    public function permissionPerGroupDisplayEvent(PermissionPerGroupDisplayEvent $event): void
    {
        $assets = new IncludeAssets(
            __DIR__ . '/../scripts/permissions-per-group/frontend-assets',
            '/assets/trackers/permissions-per-group'
        );
        $event->addJavascript(
            new \Tuleap\Layout\JavascriptAsset($assets, 'tracker-permissions-per-group.js')
        );
    }

    /**
     *This callback make SystemEvent manager knows about Tracker plugin System Events
     */
    public function getSystemEventClass($params)
    {
        switch ($params['type']) {
            case SystemEvent_TRACKER_V3_MIGRATION::NAME:
                $params['class']        = 'SystemEvent_TRACKER_V3_MIGRATION';
                $params['dependencies'] = [
                    $this->getMigrationManager(),
                ];
                break;
            case 'Tuleap\\Tracker\\FormElement\\SystemEvent\\' . SystemEvent_BURNDOWN_DAILY::NAME:
                $params['class']        = 'Tuleap\\Tracker\\FormElement\\SystemEvent\\' . SystemEvent_BURNDOWN_DAILY::NAME;
                $params['dependencies'] = [
                    new BurndownFieldDao(),
                    new FieldCalculator(new BurndownCalculator(new ComputedFieldDao())),
                    new ComputedFieldDaoCache(new ComputedFieldDao()),
                    BackendLogger::getDefaultLogger(),
                    new BurndownCacheDateRetriever(),
                ];
                break;
            case 'Tuleap\\Tracker\\FormElement\\SystemEvent\\' . SystemEvent_BURNDOWN_GENERATE::NAME:
                $params['class']        = 'Tuleap\\Tracker\\FormElement\\SystemEvent\\' . SystemEvent_BURNDOWN_GENERATE::NAME;
                $params['dependencies'] = [
                    Tracker_ArtifactFactory::instance(),
                    SemanticTimeframeBuilder::build(),
                    new BurndownFieldDao(),
                    new FieldCalculator(new BurndownCalculator(new ComputedFieldDao())),
                    new ComputedFieldDaoCache(new ComputedFieldDao()),
                    BackendLogger::getDefaultLogger(),
                    new BurndownCacheDateRetriever(),
                ];
                break;
            default:
                break;
        }
    }

    public function getServiceShortname(): string
    {
        return self::SERVICE_SHORTNAME;
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function serviceClassnamesCollector(ServiceClassnamesCollector $event): void
    {
        $event->addService($this->getServiceShortname(), ServiceTracker::class);
    }

    /**
     * @see Event::SERVICE_IS_USED
     * @param array{shortname: string, is_used: bool, group_id: int|string} $params
     */
    public function serviceIsUsed(array $params): void
    {
        // nothing to do for trackers
    }

    public function projectServiceBeforeActivation(ProjectServiceBeforeActivation $event): void
    {
        // nothing to do for trackers
    }

    public function serviceDisabledCollector(ServiceDisabledCollector $event): void
    {
        // nothing to do for trackers
    }

    public function addMissingService(AddMissingService $event): void
    {
        // nothing to do for trackers
    }

    public function javascript($params)
    {
        // TODO: Move this in ServiceTracker::displayHeader()
        include $GLOBALS['Language']->getContent('script_locale', null, 'tracker');
        echo PHP_EOL;
        echo "codendi.tracker = codendi.tracker || { };" . PHP_EOL;
        echo "codendi.tracker.base_url = '" . TRACKER_BASE_URL . "/';" . PHP_EOL;
    }

    public function toggle($params)
    {
        if ($params['id'] === 'tracker_report_query_0') {
            Toggler::togglePreference($params['user'], $params['id']);
            $params['done'] = true;
        } elseif (strpos($params['id'], 'tracker_report_query_') === 0) {
            $report_id      = (int) substr($params['id'], strlen('tracker_report_query_'));
            $report_factory = Tracker_ReportFactory::instance();
            if (($report = $report_factory->getReportById($report_id, $params['user']->getid())) && $report->userCanUpdate($params['user'])) {
                $report->toggleQueryDisplay();
                $report_factory->save($report);
            }
            $params['done'] = true;
        }
    }

    private function isLegacyTrackerV3StillUsed($legacy)
    {
        return $legacy[Service::TRACKERV3];
    }

    public function registerProjectCreationEvent(RegisterProjectCreationEvent $event): void
    {
        if ($event->shouldProjectInheritFromTemplate()) {
            $template = $event->getTemplateProject();
            $project  = $event->getJustCreatedProject();

            $tracker_manager = new TrackerManager();
            $tracker_manager->duplicate(
                new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
                (int) $template->getID(),
                (int) $project->getID(),
                $event->getMappingRegistry(),
            );

            $legacy_services = $event->getLegacyServiceUsage();

            if (
                ! $this->isRestricted() &&
                ! $this->isLegacyTrackerV3StillUsed($legacy_services) &&
                TrackerV3::instance()->available()
            ) {
                $inheritor = new Inheritor(
                    new ArtifactTypeFactory($template),
                    $this->getTrackerFactory()
                );

                $inheritor->inheritFromLegacy($this->getUserManager()->getCurrentUser(), $template, $project);
            }

            $artifact_link_types_duplicator = new ArtifactLinksUsageDuplicator(new ArtifactLinksUsageDao());
            $artifact_link_types_duplicator->duplicate($template, $project);
        }
    }

    public function permission_get_name($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (! $params['name']) {
            switch ($params['permission_type']) {
                case 'PLUGIN_TRACKER_FIELD_SUBMIT':
                    $params['name'] = dgettext('tuleap-tracker', 'Can submit tracker field');
                    break;
                case 'PLUGIN_TRACKER_FIELD_READ':
                    $params['name'] = dgettext('tuleap-tracker', 'Can read tracker field');
                    break;
                case 'PLUGIN_TRACKER_FIELD_UPDATE':
                    $params['name'] = dgettext('tuleap-tracker', 'Can update tracker field');
                    break;
                case Tracker::PERMISSION_SUBMITTER_ONLY:
                    $params['name'] = dgettext('tuleap-tracker', 'Access to tracker artifacts the user submitted');
                    break;
                case Tracker::PERMISSION_SUBMITTER:
                    $params['name'] = dgettext('tuleap-tracker', 'Access to tracker artifacts submitted by group');
                    break;
                case Tracker::PERMISSION_ASSIGNEE:
                    $params['name'] = dgettext('tuleap-tracker', 'Access to tracker artifacts assigned to group');
                    break;
                case Tracker::PERMISSION_FULL:
                    $params['name'] = dgettext('tuleap-tracker', 'Access to all tracker artifacts');
                    break;
                case Tracker::PERMISSION_ADMIN:
                    $params['name'] = dgettext('tuleap-tracker', 'Admin of the tracker');
                    break;
                case 'PLUGIN_TRACKER_ARTIFACT_ACCESS':
                    $params['name'] = dgettext('tuleap-tracker', 'Access to an artifact');
                    break;
                case 'PLUGIN_TRACKER_WORKFLOW_TRANSITION':
                    $params['name'] = dgettext('tuleap-tracker', 'Can make the transition');
                    break;
                default:
                    break;
            }
        }
    }

    public function permission_get_object_type($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $type = $this->getObjectTypeFromPermissions($params);
        if ($type != false) {
            $params['object_type'] = $type;
        }
    }

    public function getObjectTypeFromPermissions($params)
    {
        switch ($params['permission_type']) {
            case 'PLUGIN_TRACKER_FIELD_SUBMIT':
            case 'PLUGIN_TRACKER_FIELD_READ':
            case 'PLUGIN_TRACKER_FIELD_UPDATE':
                return 'field';
            case Tracker::PERMISSION_SUBMITTER_ONLY:
            case Tracker::PERMISSION_SUBMITTER:
            case Tracker::PERMISSION_ASSIGNEE:
            case Tracker::PERMISSION_FULL:
            case Tracker::PERMISSION_ADMIN:
                return 'tracker';
            case 'PLUGIN_TRACKER_ARTIFACT_ACCESS':
                return 'artifact';
            case 'PLUGIN_TRACKER_WORKFLOW_TRANSITION':
                return 'workflow transition';
        }
        return false;
    }

    public function permission_get_object_name($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (! $params['object_name']) {
            $type = $this->getObjectTypeFromPermissions($params);
            if (in_array($params['permission_type'], [Tracker::PERMISSION_ADMIN, Tracker::PERMISSION_FULL, Tracker::PERMISSION_SUBMITTER, Tracker::PERMISSION_ASSIGNEE, Tracker::PERMISSION_SUBMITTER_ONLY, 'PLUGIN_TRACKER_FIELD_SUBMIT', 'PLUGIN_TRACKER_FIELD_READ', 'PLUGIN_TRACKER_FIELD_UPDATE', 'PLUGIN_TRACKER_ARTIFACT_ACCESS'])) {
                $object_id = $params['object_id'];
                if ($type == 'tracker') {
                    $ret = (string) $object_id;
                    if ($tracker = TrackerFactory::instance()->getTrackerById($object_id)) {
                        $params['object_name'] = $tracker->getName();
                    }
                } elseif ($type == 'field') {
                    $ret = (string) $object_id;
                    if ($field = Tracker_FormElementFactory::instance()->getFormElementById($object_id)) {
                        $ret     = $field->getLabel();
                        $tracker = $field->getTracker();
                        if ($tracker !== null) {
                            $ret .= ' (' . $tracker->getName() . ')';
                        }
                    }
                    $params['object_name'] =  $ret;
                } elseif ($type == 'artifact') {
                    $ret = (string) $object_id;
                    if ($a  = Tracker_ArtifactFactory::instance()->getArtifactById($object_id)) {
                        $ret       = 'art #' . $a->getId();
                        $semantics = $a->getTracker()
                                       ->getTrackerSemanticManager()
                                       ->getSemantics();
                        if (isset($semantics['title'])) {
                            if ($field = Tracker_FormElementFactory::instance()->getFormElementById($semantics['title']->getFieldId())) {
                                $value = $a->getValue($field);
                                if ($value) {
                                    $ret .= ' - ' . $value->getText();
                                }
                            }
                        }
                    }
                    $params['object_name'] =  $ret;
                }
            }
        }
    }

    //phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore
    public $_cached_permission_user_allowed_to_change;
    public function permission_user_allowed_to_change($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (! $params['allowed']) {
            $allowed = [
                Tracker::PERMISSION_ADMIN,
                Tracker::PERMISSION_FULL,
                Tracker::PERMISSION_SUBMITTER,
                Tracker::PERMISSION_SUBMITTER_ONLY,
                Tracker::PERMISSION_ASSIGNEE,
                'PLUGIN_TRACKER_FIELD_SUBMIT',
                'PLUGIN_TRACKER_FIELD_READ',
                'PLUGIN_TRACKER_FIELD_UPDATE',
                'PLUGIN_TRACKER_ARTIFACT_ACCESS',
                'PLUGIN_TRACKER_WORKFLOW_TRANSITION',
            ];
            if (in_array($params['permission_type'], $allowed)) {
                $group_id  = $params['group_id'];
                $object_id = $params['object_id'];
                $type      = $this->getObjectTypeFromPermissions($params);
                if (! isset($this->_cached_permission_user_allowed_to_change[$type][$object_id])) {
                    switch ($type) {
                        case 'tracker':
                            if ($tracker = TrackerFactory::instance()->getTrackerById($object_id)) {
                                $this->_cached_permission_user_allowed_to_change[$type][$object_id] = $tracker->userIsAdmin();
                            }
                            break;
                        case 'field':
                            if ($field = Tracker_FormElementFactory::instance()->getFormElementById($object_id)) {
                                $this->_cached_permission_user_allowed_to_change[$type][$object_id] = $field->getTracker()->userIsAdmin();
                            }
                            break;
                        case 'artifact':
                            if ($a  = Tracker_ArtifactFactory::instance()->getArtifactById($object_id)) {
                                //TODO: manage permissions related to field "permission on artifact"
                                $this->_cached_permission_user_allowed_to_change[$type][$object_id] = $a->getTracker()->userIsAdmin();
                            }
                            break;
                        case 'workflow transition':
                            if ($transition = TransitionFactory::instance()->getTransition($object_id)) {
                                $this->_cached_permission_user_allowed_to_change[$type][$object_id] = $transition->getWorkflow()->getTracker()->userIsAdmin();
                            }
                            break;
                    }
                }
                if (isset($this->_cached_permission_user_allowed_to_change[$type][$object_id])) {
                    $params['allowed'] = $this->_cached_permission_user_allowed_to_change[$type][$object_id];
                }
            }
        }
    }

    public function getAvailableReferenceNatures(NatureCollection $natures): void
    {
        $natures->addNature(
            Artifact::REFERENCE_NATURE,
            new Nature('artifact', 'fas fa-list-ol', 'Artifact Tracker v5', true)
        );
    }

    public function get_artifact_reference_group_id($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $artifact = Tracker_ArtifactFactory::instance()->getArtifactByid($params['artifact_id']);
        if ($artifact) {
            $tracker            = $artifact->getTracker();
            $params['group_id'] = $tracker->getGroupId();
        }
    }

    public function set_artifact_reference_group_id($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $reference = $params['reference'];
        if ($this->isDefaultReferenceUrl($reference)) {
            $artifact = Tracker_ArtifactFactory::instance()->getArtifactByid($params['artifact_id']);
            if ($artifact) {
                $tracker = $artifact->getTracker();
                $reference->setGroupId($tracker->getGroupId());
            }
        }
    }

    private function isDefaultReferenceUrl(Reference $reference)
    {
        return $reference->getLink() === TRACKER_BASE_URL . '/?&aid=$1&group_id=$group_id';
    }

    public function build_reference($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $row           = $params['row'];
        $params['ref'] = new Reference(
            $params['ref_id'],
            $row['keyword'],
            $row['description'],
            $row['link'],
            $row['scope'],
            $this->getServiceShortname(),
            Artifact::REFERENCE_NATURE,
            $row['is_active'],
            $row['group_id']
        );
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function referenceGetTooltipRepresentationEvent(Tuleap\Reference\ReferenceGetTooltipRepresentationEvent $event): void
    {
        if ($event->getReference()->getServiceShortName() === self::SERVICE_SHORTNAME && $event->getReference()->getNature() === Artifact::REFERENCE_NATURE) {
            $artifact = Tracker_ArtifactFactory::instance()->getArtifactById((int) $event->getValue());
            if ($artifact && $artifact->getTracker()->isActive()) {
                $artifact->fetchTooltip($event->getUser())->apply($event->setOutput(...));
            } else {
                $event->setOutput(\Tuleap\Layout\TooltipJSON::fromHtmlBody(dgettext('tuleap-tracker', 'This artifact does not exist.')));
            }
        }
    }

    public function url_verification_instance($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $request_uri = $_SERVER['REQUEST_URI'];
        if (
            strpos($request_uri, $this->getPluginPath()) === 0 &&
            strpos($request_uri, $this->getPluginPath() . '/notifications/') !== 0 &&
            strpos($request_uri, $this->getPluginPath() . '/webhooks/') !== 0 &&
            strpos($request_uri, $this->getPluginPath() . '/workflow/') !== 0 &&
            strpos($request_uri, $this->getPluginPath() . ByGroupController::URL . '/') !== 0 &&
            strpos($request_uri, $this->getPluginPath() . ByFieldController::URL . '/') !== 0 &&
            strpos($request_uri, $this->getPluginPath() . PermissionsOnFieldsUpdateController::URL . '/') !== 0
        ) {
            $params['url_verification'] = new Tracker_URLVerification();
        }
    }

    /**
     * Hook: event raised when widget are instanciated
     *
     */
    public function widgetInstance(\Tuleap\Widget\Event\GetWidget $get_widget_event)
    {
        switch ($get_widget_event->getName()) {
            case Tracker_Widget_MyArtifacts::ID:
                $get_widget_event->setWidget(new Tracker_Widget_MyArtifacts());
                break;
            case Tracker_Widget_MyRenderer::ID:
                $get_widget_event->setWidget(new Tracker_Widget_MyRenderer());
                break;
            case Tracker_Widget_ProjectRenderer::ID:
                $get_widget_event->setWidget(new Tracker_Widget_ProjectRenderer());
                break;
        }
    }

    public function getUserWidgetList(\Tuleap\Widget\Event\GetUserWidgetList $event)
    {
        $event->addWidget(Tracker_Widget_MyArtifacts::ID);
        $event->addWidget(Tracker_Widget_MyRenderer::ID);
    }

    public function getProjectWidgetList(\Tuleap\Widget\Event\GetProjectWidgetList $event)
    {
        $event->addWidget(Tracker_Widget_ProjectRenderer::ID);
    }

    public function uninstall()
    {
        $this->removeOrphanWidgets([
            Tracker_Widget_MyArtifacts::ID,
            Tracker_Widget_MyRenderer::ID,
            Tracker_Widget_ProjectRenderer::ID,
        ]);
    }

    /** @see AtUserCreationDefaultWidgetsCreator::DEFAULT_WIDGETS_FOR_NEW_USER */
    public function default_widgets_for_new_user(array $params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['widgets'][] = Tracker_Widget_MyArtifacts::ID;
    }

    /**
     * @see Event::REST_PROJECT_RESOURCES
     */
    public function rest_project_resources(array $params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $injector = new Tracker_REST_ResourcesInjector();
        $injector->declareProjectPlanningResource($params['resources'], $params['project']);
    }

    public function service_public_areas(GetPublicAreas $event)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $project = $event->getProject();
        if ($project->usesService($this->getServiceShortname())) {
            $service = $project->getService($this->getServiceShortname());
            $tf      = TrackerFactory::instance();

            // Get the artfact type list
            $trackers = $tf->getTrackersByGroupId($project->getGroupId());

            if ($trackers) {
                $entries  = [];
                $purifier = Codendi_HTMLPurifier::instance();
                foreach ($trackers as $t) {
                    if ($t->userCanView()) {
                        $name      = $purifier->purify($t->name, CODENDI_PURIFIER_CONVERT_HTML);
                        $entries[] = '<a href="' . TRACKER_BASE_URL . '/?tracker=' . $t->id . '">' . $name . '</a>';
                    }
                }
                if ($service !== null && $entries) {
                    $area  = '';
                    $area .= '<a href="' . TRACKER_BASE_URL . '/?group_id=' . urlencode($project->getGroupId()) . '">';
                    $area .= '<i class="dashboard-widget-content-projectpublicareas ' . $purifier->purify($service->getIcon()) . '"></i>';
                    $area .= dgettext('tuleap-tracker', 'Trackers');
                    $area .= '</a>';

                    $area .= '<ul><li>' . implode('</li><li>', $entries) . '</li></ul>';

                    $event->addArea($area);
                }
            }
        }
    }

    public function project_creation_remove_legacy_services($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (! $this->isRestricted()) {
            $this->getServiceActivator()->unuseLegacyService($params);
        }
    }

    public function project_registration_activate_service(ProjectRegistrationActivateService $event)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $this->getServiceActivator()->forceUsageOfService($event->getProject(), $event->getTemplate(), $event->getLegacy());
        $this->getReferenceCreator()->insertArtifactsReferencesFromLegacy($event->getProject());
    }

    /**
     * @return ReferenceCreator
     */
    private function getReferenceCreator()
    {
        return new ReferenceCreator(
            ServiceManager::instance(),
            TrackerV3::instance(),
            new ReferenceDao()
        );
    }

    /**
     * @return ServiceActivator
     */
    private function getServiceActivator()
    {
        return new ServiceActivator(ServiceManager::instance(), TrackerV3::instance(), new ServiceCreator(new ServiceDao()));
    }

    public function projectStatusUpdate(ProjectStatusUpdate $event): void
    {
        if ($event->status === \Project::STATUS_DELETED) {
            $event_manager = EventManager::instance();
            $event_manager->processEvent(new ProjectDeletionEvent($event->project->getID()));

            $tracker_manager = new TrackerManager();
            $tracker_manager->deleteProjectTrackers((int) $event->project->getID());
        }
    }

    public function display_deleted_trackers(array &$params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $tracker_manager = new TrackerManager();
        $tracker_manager->displayDeletedTrackers();
    }

    /**
     * Process the nightly job to send reminder on artifact correponding to given criteria
     *
     * @param Array $params Hook params
     *
     * @return Void
     */
    public function codendi_daily_start($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $trackerManager = new TrackerManager();
        $logger         = BackendLogger::getDefaultLogger();
        $logger->debug("[TDR] Tuleap daily start event: launch date reminder");

        $this->getSystemEventManager()->createEvent(
            'Tuleap\\Tracker\\FormElement\\SystemEvent\\' . SystemEvent_BURNDOWN_DAILY::NAME,
            "",
            SystemEvent::PRIORITY_MEDIUM,
            SystemEvent::OWNER_APP
        );

        $this->dailyCleanup($logger);

        $trackerManager->sendDateReminder();
    }

    /**
     * Fill the list of subEvents related to tracker in the project history interface
     *
     * @param Array $params Hook params
     *
     * @return Void
     */
    public function fillProjectHistorySubEvents($params)
    {
        array_push(
            $params['subEvents']['event_others'],
            'tracker_date_reminder_add',
            'tracker_date_reminder_edit',
            'tracker_date_reminder_delete',
            'tracker_date_reminder_sent',
            Tracker_FormElement::PROJECT_HISTORY_UPDATE,
            ArtifactDeletor::PROJECT_HISTORY_ARTIFACT_DELETED,
            MarkTrackerAsDeletedController::PROJECT_HISTORY_TRACKER_DELETION_KEY,
        );
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function getHistoryKeyLabel(GetHistoryKeyLabel $event): void
    {
        if ($event->getKey() === MarkTrackerAsDeletedController::PROJECT_HISTORY_TRACKER_DELETION_KEY) {
            $event->setLabel(
                dgettext(
                    'tuleap-tracker',
                    'Tracker deleted',
                ),
            );
        }
    }

    /**
     * @param array $params
     */
    public function agiledashboard_export_xml($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $can_bypass_threshold = true;
        $user_xml_exporter    = new UserXMLExporter(
            $this->getUserManager(),
            new UserXMLExportedCollection(new XML_RNGValidator(), new XML_SimpleXMLCDATAFactory())
        );

        $user    = UserManager::instance()->getCurrentUser();
        $archive = new NoArchive();

        $this->getTrackerXmlExport($user_xml_exporter, $can_bypass_threshold)
            ->exportToXml($params['project'], $params['into_xml'], $user);
    }

    /**
     * @return TrackerXmlExport
     */
    private function getTrackerXmlExport(UserXMLExporter $user_xml_exporter, $can_bypass_threshold)
    {
        $rng_validator            = new XML_RNGValidator();
        $artifact_link_usage_dao  = new ArtifactLinksUsageDao();
        $external_field_extractor = new ExternalFieldsExtractor(EventManager::instance());

        return new TrackerXmlExport(
            $this->getTrackerFactory(),
            $this->getTrackerFactory()->getTriggerRulesManager(),
            $rng_validator,
            new Tracker_Artifact_XMLExport(
                $rng_validator,
                $this->getArtifactFactory(),
                $can_bypass_threshold,
                $user_xml_exporter,
                $external_field_extractor
            ),
            $user_xml_exporter,
            EventManager::instance(),
            new TypePresenterFactory(new TypeDao(), $artifact_link_usage_dao),
            $artifact_link_usage_dao,
            $external_field_extractor
        );
    }

    /**
     *
     * @param array $params
     * @see Event::IMPORT_XML_PROJECT
     */
    public function import_xml_project($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $import_spotter = Spotter::instance();
        $import_spotter->startImport();

        $user = UserManager::instance()->getCurrentUser();

        TrackerXmlImport::build($params['user_finder'], $params['logger'])->import(
            $params['configuration'],
            $params['project'],
            $params['xml_content'],
            $params['mappings_registery'],
            $params['extraction_path'],
            $user
        );

        $import_spotter->endImport();
    }

    /**
     * @throws ImportNotValidException
     */
    public function projectXMLImportPreChecksEvent(ProjectXMLImportPreChecksEvent $event): void
    {
        $missing_types = $this->checkTypesExistsOnPlatform($event->getXmlElement());
        if (! empty($missing_types)) {
            throw new ImportNotValidException(
                "Some natures used in trackers are not created on platform: " . implode(', ', $missing_types)
            );
        }
    }

    private function checkTypesExistsOnPlatform(SimpleXMLElement $xml): array
    {
        if (! isset($xml->trackers['use-natures'][0]) || ! $xml->trackers['use-natures'][0]) {
            return [];
        }

        if (! (array) $xml->natures) {
            return [];
        }

        $platform_types = [Tracker_FormElement_Field_ArtifactLink::TYPE_IS_CHILD];
        foreach ($this->getTypeDao()->searchAll() as $nature) {
            $platform_types[] = $nature['shortname'];
        }

        $this->addCustomTypes($platform_types);

        $missing_types = [];
        foreach ($xml->natures->nature as $type) {
            if (! in_array((string) $type, $platform_types, true)) {
                $missing_types[] = $type;
            }
        }

        return $missing_types;
    }

    private function addCustomTypes(array &$types): void
    {
        $params['types'] = &$types;
        EventManager::instance()->processEvent(
            Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink::TRACKER_ADD_SYSTEM_TYPES,
            $params
        );
    }

    private function getTypeDao()
    {
        return new TypeDao();
    }

    /**
     * @see Event::COLLECT_ERRORS_WITHOUT_IMPORTING_XML_PROJECT
     */
    public function collect_errors_without_importing_xml_project($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $tracker_xml_import = TrackerXmlImport::build($params['user_finder'], $params['logger']);
        $params['errors']   = $tracker_xml_import->collectErrorsWithoutImporting(
            $params['project'],
            $params['xml_content']
        );
    }

    public function user_manager_get_user_instance(array $params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($params['row']['user_id'] == Tracker_Workflow_WorkflowUser::ID) {
            $params['user'] = new Tracker_Workflow_WorkflowUser($params['row']);
        }
    }

    #[ListeningToEventClass]
    public function statisticsServiceUsage(StatisticsServiceUsage $event): void
    {
        $dao = $this->getArtifactDao();

        $start_date = strtotime($event->start_date);
        $end_date   = strtotime($event->end_date);

        $number_of_open_artifacts_between_two_dates   = $dao->searchSubmittedArtifactBetweenTwoDates($start_date, $end_date);
        $number_of_closed_artifacts_between_two_dates = $dao->searchClosedArtifactBetweenTwoDates($start_date, $end_date);

        $event->csv_exporter->buildDatas($number_of_open_artifacts_between_two_dates, "Trackers v5 - Opened Artifacts");
        $event->csv_exporter->buildDatas($number_of_closed_artifacts_between_two_dates, "Trackers v5 - Closed Artifacts");
    }

    /**
     * @see REST_RESOURCES
     */
    public function rest_resources($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $injector = new Tracker_REST_ResourcesInjector();
        $injector->populate($params['restler']);
    }

    public function agiledashboard_event_rest_get_milestone($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($this->buildRightVersionOfMilestonesBurndownResource($params['version'])->hasBurndown($params['user'], $params['milestone'])) {
            $milestone_representation_reference_holder                           = $params['milestone_representation_reference_holder'];
            $milestone_representation_reference_holder->milestone_representation = \Tuleap\AgileDashboard\REST\v1\MilestoneRepresentation::buildWithBurndownEnabled($milestone_representation_reference_holder->milestone_representation);
        }
    }

    public function agiledashboard_event_rest_get_burndown($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['burndown'] = $this->buildRightVersionOfMilestonesBurndownResource($params['version'])->get($params['user'], $params['milestone']);
    }

    private function buildRightVersionOfMilestonesBurndownResource($version)
    {
        $class_with_right_namespace = '\\Tuleap\\Tracker\\REST\\' . $version . '\\MilestonesBurndownResource';
        if (! class_exists($class_with_right_namespace)) {
            throw new LogicException($class_with_right_namespace . ' does not exist');
        }
        return new $class_with_right_namespace();
    }

    private function getTrackerSystemEventManager()
    {
        return new Tracker_SystemEventManager($this->getSystemEventManager());
    }

    private function getSystemEventManager()
    {
        return SystemEventManager::instance();
    }

    private function getMigrationManager(): Tracker_Migration_MigrationManager
    {
        $backend_logger = BackendLogger::getDefaultLogger(Tracker_Migration_MigrationManager::LOG_FILE);
        $mail_logger    = new Tracker_Migration_MailLogger();

        return new Tracker_Migration_MigrationManager(
            $this->getTrackerSystemEventManager(),
            $this->getTrackerFactory(),
            $this->getUserManager(),
            $this->getProjectManager(),
            $this->getTrackerChecker(),
            new LegacyTrackerMigrationDao(),
            new KeepReverseCrossReferenceDAO(),
            $mail_logger,
            new Tracker_Migration_MigrationLogger(
                $backend_logger,
                $mail_logger
            )
        );
    }

    private function getProjectManager()
    {
        return ProjectManager::instance();
    }

    private function getTrackerFactory()
    {
        return TrackerFactory::instance();
    }

    private function getUserManager()
    {
        return UserManager::instance();
    }

    private function getTrackerFormElementFactory()
    {
        return Tracker_FormElementFactory::instance();
    }

    private function getArtifactFactory()
    {
        return Tracker_ArtifactFactory::instance();
    }

    /**
     * @see Event::BACKEND_ALIAS_GET_ALIASES
     */
    public function backend_alias_get_aliases($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $config = new MailGatewayConfig(
            new MailGatewayConfigDao(),
            MailTransportBuilder::getPlatformMailConfiguration(),
        );

        $src_dir = ForgeConfig::get('codendi_dir');
        $script  = $src_dir . '/plugins/tracker/bin/emailgateway-wrapper.sh';

        $command = "sudo -u codendiadm $script";

        if ($config->isTokenBasedEmailgatewayEnabled() || $config->isInsecureEmailgatewayEnabled()) {
            $params['aliases'][] = new System_Alias(self::EMAILGATEWAY_TOKEN_ARTIFACT_UPDATE, "\"|$command\"");
        }

        if ($config->isInsecureEmailgatewayEnabled()) {
            $params['aliases'][] = new System_Alias(self::EMAILGATEWAY_INSECURE_ARTIFACT_CREATION, "\"|$command\"");
            $params['aliases'][] = new System_Alias(self::EMAILGATEWAY_INSECURE_ARTIFACT_UPDATE, "\"|$command\"");
        }
    }

    public function get_projectid_from_url($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $url = $params['url'];
        if (strpos($url, '/plugins/tracker/') === 0) {
            if (! $params['request']->get('tracker')) {
                return;
            }

            $tracker = TrackerFactory::instance()->getTrackerById($params['request']->get('tracker'));
            if ($tracker) {
                $params['project_id'] = $tracker->getGroupId();
            }
        }
    }

    public function getSystemEventQueuesEvent(GetSystemEventQueuesEvent $event): void
    {
        $event->addAvailableQueue(
            Tracker_SystemEvent_Tv3Tv5Queue::NAME,
            new Tracker_SystemEvent_Tv3Tv5Queue()
        );
    }

    /** @see Event::SYSTEM_EVENT_GET_TYPES_FOR_CUSTOM_QUEUE */
    public function system_event_get_types_for_custom_queue($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($params['queue'] === Tracker_SystemEvent_Tv3Tv5Queue::NAME) {
            $params['types'][] = SystemEvent_TRACKER_V3_MIGRATION::NAME;
        }
    }

    public function system_event_get_types_for_default_queue($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['types'][] = 'Tuleap\\Tracker\\FormElement\\SystemEvent\\' . SystemEvent_BURNDOWN_DAILY::NAME;
        $params['types'][] = 'Tuleap\\Tracker\\FormElement\\SystemEvent\\' . SystemEvent_BURNDOWN_GENERATE::NAME;
    }

    /** @see Event::SERVICES_TRUNCATED_EMAILS */
    public function services_truncated_emails(array $params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $project = $params['project'];
        if ($project->usesService($this->getServiceShortname())) {
            $params['services'][] = dgettext('tuleap-tracker', 'Trackers');
        }
    }

    public function exportXmlProject(ExportXmlProject $event): void
    {
        $tracker_id = $event->getExtraOptions('tracker_id');
        if (
            ! $tracker_id
            && ! $event->shouldExportAllData()
            && ! $event->shouldExportStructureOnly()
        ) {
            return;
        }

        $project              = $event->getProject();
        $can_bypass_threshold = $event->canBypassThreshold();
        $user_xml_exporter    = $event->getUserXMLExporter();
        $user                 = $event->getUser();

        if ($event->shouldExportAllData()) {
            $this->getTrackerXmlExport($user_xml_exporter, $can_bypass_threshold)
                ->exportToXmlFull($project, $event->getIntoXml(), $user, $event->getArchive());
        } elseif ($event->shouldExportStructureOnly()) {
            $this->getTrackerXmlExport($user_xml_exporter, $can_bypass_threshold)
                ->exportToXml($project, $event->getIntoXml(), $user);
        } elseif ($tracker_id) {
            $this->exportSingleTracker(
                $tracker_id,
                $project,
                $user_xml_exporter,
                $user,
                $can_bypass_threshold,
                $event->getArchive(),
                $event->getIntoXml()
            );
        }
    }

    private function exportSingleTracker(
        int $tracker_id,
        Project $project,
        UserXMLExporter $user_xml_exporter,
        PFUser $user,
        $can_bypass_threshold,
        ArchiveInterface $archive,
        SimpleXMLElement $into_xml,
    ) {
        $tracker = $this->getTrackerFactory()->getTrackerById($tracker_id);

        if (! $tracker) {
            throw new Exception('Tracker ID does not exist');
        }

        if ($tracker->getGroupId() != $project->getID()) {
            throw new Exception('Tracker ID does not belong to project ID');
        }

        $this->getTrackerXmlExport($user_xml_exporter, $can_bypass_threshold)
            ->exportSingleTrackerToXml($into_xml, $tracker_id, $user, $archive);
    }

    public function getReference(GetReferenceEvent $event): void
    {
        $keyword = $event->getKeyword();
        if ($this->isArtifactReferenceInMultipleTrackerServicesContext($keyword)) {
            $artifact_id       = $event->getValue();
            $reference_manager = $event->getReferenceManager();

            $tracker_reference_manager = $this->getTrackerReferenceManager($reference_manager);

            $reference = $tracker_reference_manager->getReference(
                $keyword,
                $artifact_id
            );

            if ($reference !== null) {
                $event->setReference($reference);
            }
        }
    }

    private function isArtifactReferenceInMultipleTrackerServicesContext($keyword)
    {
        return (TrackerV3::instance()->available() && ($keyword === 'art' || $keyword === 'artifact'));
    }

    /**
     * @return Tracker_ReferenceManager
     */
    private function getTrackerReferenceManager(ReferenceManager $reference_manager)
    {
        return new Tracker_ReferenceManager(
            $reference_manager,
            $this->getArtifactFactory()
        );
    }

    public function can_user_access_ugroup_info($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $project = $params['project'];
        $user    = $params['user'];

        $trackers = $this->getTrackerFactory()->getTrackersByGroupIdUserCanView($project->getID(), $user);
        foreach ($trackers as $tracker) {
            if ($tracker->hasFieldBindedToUserGroupsViewableByUser($user)) {
                $params['can_access'] = true;
                break;
            }
        }
    }

    /** @see TemplatePresenter::EVENT_ADDITIONAL_ADMIN_BUTTONS */
    public function event_additional_admin_buttons(array $params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $template = $params['template'];
        \assert($template instanceof Project);

        $is_service_used = $template->usesService($this->getServiceShortname());

        $params['buttons'][] = [
            'icon'        => 'fa-list',
            'label'       => dgettext('tuleap-tracker', 'Configure trackers'),
            'uri'         => TRACKER_BASE_URL . '/?group_id=' . (int) $template->getID(),
            'is_disabled' => ! $is_service_used,
            'title'       => ! $is_service_used ? dgettext('tuleap-tracker', 'This template does not use trackers') : '',
        ];
    }

    public function get_permission_delegation($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $permission = new TrackerAdminAllProjects();

        $params['plugins_permission'][TrackerAdminAllProjects::ID] = $permission;
    }

    public function project_admin_ugroup_deletion($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $project_id = $params['group_id'];
        $ugroup     = $params['ugroup'];

        $ugroups_to_notify_dao = new UgroupsToNotifyDao();
        $ugroups_to_notify_dao->deleteByUgroupId($project_id, $ugroup->getId());

        $private_comment_permissions_dao = new TrackerPrivateCommentUGroupPermissionDao();
        $private_comment_permissions_dao->deleteUgroupPermissionForPrivateComment($ugroup->getId());
    }

    public function project_admin_remove_user($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $project_id = $params['group_id'];
        $user_id    = $params['user_id'];

        $user_manager = UserManager::instance();

        $user    = $user_manager->getUserById($user_id);
        $project = $this->getProjectManager()->getProject($project_id);

        $cleaner = $this->getNotificationForProjectMemberCleaner();
        $cleaner->cleanNotificationsAfterUserRemoval($project, $user);
    }

    /** @see Event::PROJECT_ACCESS_CHANGE */
    public function projectAccessChange(array $params): void
    {
        $updater = $this->getUgroupToNotifyUpdater();
        $updater->updateProjectAccess($params['project_id'], $params['old_access'], $params['access']);
    }

    public function site_access_change(array $params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $updater = $this->getUgroupToNotifyUpdater();
        $updater->updateSiteAccess($params['old_value']);
    }

    /**
     * @return UgroupsToNotifyUpdater
     */
    private function getUgroupToNotifyUpdater()
    {
        return new UgroupsToNotifyUpdater($this->getUgroupToNotifyDao());
    }

    /**
     * @return NotificationsForProjectMemberCleaner
     */
    private function getNotificationForProjectMemberCleaner()
    {
        return new NotificationsForProjectMemberCleaner(
            $this->getTrackerFactory(),
            $this->getTrackerNotificationManager(),
            $this->getUserToNotifyDao()
        );
    }

    /**
     * @return UsersToNotifyDao
     */
    private function getUserToNotifyDao()
    {
        return new UsersToNotifyDao();
    }

    /**
     * @return UgroupsToNotifyDao
     */
    private function getUgroupToNotifyDao()
    {
        return new UgroupsToNotifyDao();
    }

    /**
     * @return Tracker_NotificationsManager
     */
    private function getTrackerNotificationManager()
    {
        $user_manager                   = UserManager::instance();
        $user_to_notify_dao             = $this->getUserToNotifyDao();
        $ugroup_to_notify_dao           = $this->getUgroupToNotifyDao();
        $unsubscribers_notification_dao = new UnsubscribersNotificationDAO();
        $notification_list_builder      = new NotificationListBuilder(
            new UGroupDao(),
            new CollectionOfUserInvolvedInNotificationPresenterBuilder(
                $user_to_notify_dao,
                $unsubscribers_notification_dao,
                $user_manager
            ),
            new CollectionOfUgroupToBeNotifiedPresenterBuilder($ugroup_to_notify_dao)
        );

        return new Tracker_NotificationsManager(
            $this,
            $notification_list_builder,
            $user_to_notify_dao,
            $ugroup_to_notify_dao,
            new UserNotificationSettingsDAO(),
            new GlobalNotificationsAddressesBuilder(),
            $user_manager,
            new UGroupManager(),
            new GlobalNotificationSubscribersFilter($unsubscribers_notification_dao),
            new NotificationLevelExtractor(),
            new \TrackerDao(),
            new \ProjectHistoryDao(),
            $this->getForceUsageUpdater()
        );
    }

    public function getHistoryEntryCollection(HistoryEntryCollection $collection)
    {
        $event_manager   = \EventManager::instance();
        $visit_retriever = new \Tuleap\Tracker\Artifact\RecentlyVisited\VisitRetriever(
            new RecentlyVisitedDao(),
            $this->getArtifactFactory(),
            new \Tuleap\Glyph\GlyphFinder($event_manager),
            new StatusBadgeBuilder(Tracker_Semantic_StatusFactory::instance()),
            $event_manager
        );
        $visit_retriever->getVisitHistory($collection, HistoryRetriever::MAX_LENGTH_HISTORY, $collection->getUser());
    }

    /**
     * @see Event::USER_HISTORY_CLEAR
     */
    public function clearRecentlyVisitedArtifacts(array $params)
    {
        $user = $params['user'];
        \assert($user instanceof PFUser);

        $visit_cleaner = new \Tuleap\Tracker\Artifact\RecentlyVisited\VisitCleaner(
            new RecentlyVisitedDao()
        );
        $visit_cleaner->clearVisitedArtifacts($user);
    }

    public function convertIndexedItemToSearchResult(IndexedItemFoundToSearchResult $indexed_item_convertor): void
    {
        $event_dispatcher = EventManager::instance();
        $retriever        = new \Tuleap\Tracker\Search\SearchResultRetriever(
            $this->getArtifactFactory(),
            $this->getTrackerFormElementFactory(),
            $event_dispatcher,
            new \Tuleap\Glyph\GlyphFinder($event_dispatcher),
            new StatusBadgeBuilder(\Tracker_Semantic_StatusFactory::instance()),
        );

        $retriever->retrieveSearchResult($indexed_item_convertor);
    }

    public function collectGlyphLocations(GlyphLocationsCollector $glyph_locations_collector)
    {
        $glyph_locations_collector->addLocation(
            'tuleap-tracker',
            new GlyphLocation(TRACKER_BASE_DIR . '/../glyphs')
        );
    }

    public function collectHeartbeatsEntries(HeartbeatsEntryCollection $collection): void
    {
        $collector = new LatestHeartbeatsCollector(
            $this->getArtifactDao(),
            $this->getArtifactFactory(),
            $this->getUserManager(),
            EventManager::instance()
        );
        $collector->collect($collection);
    }

    private function isInDashboard()
    {
        $current_page = new CurrentPage();

        return $current_page->isDashboard();
    }

    public function workerEvent(WorkerEvent $event): void
    {
        $user_manager        = $this->getUserManager();
        $worker_availability = new \Tuleap\Queue\WorkerAvailability();
        $logger              = $event->getLogger();

        AsynchronousJiraRunner::addListener(
            $event,
            new PendingJiraImportDao(),
            new PendingJiraImportBuilder(ProjectManager::instance(), $user_manager),
            $this->getJiraRunner($logger),
        );

        AsynchronousActionsRunner::addListener($event);

        $logger = new WrapperLogger(BackendLogger::getDefaultLogger(), self::class);

        $async_artifact_archive_runner = new AsynchronousArtifactsDeletionActionsRunner(
            new PendingArtifactRemovalDao(),
            $logger,
            $user_manager,
            new QueueFactory($logger),
            $worker_availability,
            new ArchiveAndDeleteArtifactTaskBuilder()
        );
        $async_artifact_archive_runner->addListener($event);
    }

    public function permissionPerGroupPaneCollector(PermissionPerGroupPaneCollector $event)
    {
        $service = $event->getProject()->getService($this->getServiceShortname());
        if ($service === null) {
            return;
        }

        $ugroup_manager    = new UGroupManager();
        $presenter_builder = new ProjectAdminPermissionPerGroupPresenterBuilder(
            $ugroup_manager
        );

        $request            = HTTPRequest::instance();
        $selected_ugroup_id = $event->getSelectedUGroupId();
        $presenter          = $presenter_builder->buildPresenter(
            $request->getProject(),
            $selected_ugroup_id
        );

        $template_factory      = TemplateRendererFactory::build();
        $admin_permission_pane = $template_factory
            ->getRenderer(TRACKER_TEMPLATE_DIR . '/project-admin/')
            ->renderToString(
                'project-admin-permission-per-group',
                $presenter
            );

        $rank_in_project = $service->getRank();

        $event->addPane($admin_permission_pane, $rank_in_project);
    }

    private function dailyCleanup(\Psr\Log\LoggerInterface $logger): void
    {
        $deletions_remover = new ArtifactsDeletionRemover(new ArtifactsDeletionDAO());
        $deletions_remover->deleteOutdatedArtifactsDeletions();

        (new RecentlyVisitedDao())->deleteOldVisits();

        $current_time = new \DateTimeImmutable();

        $cleaner = new FileUploadCleaner(
            $logger,
            new FileOngoingUploadDao(),
            Tracker_FormElementFactory::instance(),
            new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection())
        );
        $cleaner->deleteDanglingFilesToUpload($current_time);

        $pending_jira_cleaner = new PendingJiraImportCleaner(
            $logger,
            new PendingJiraImportDao(),
            new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
            new PendingJiraImportBuilder(ProjectManager::instance(), UserManager::instance()),
            new CancellationOfJiraImportNotifier(
                new JiraImportNotifier(
                    $this->getMailNotificationBuilder(),
                ),
                new LocaleSwitcher(),
                TemplateRendererFactory::build(),
            )
        );
        $pending_jira_cleaner->deleteDanglingPendingJiraImports($current_time);

        $jira_attachment_cleaner = new AttachmentCleaner();
        $jira_attachment_cleaner->deleteDownloadedAttachmentOlderThanOneDay($current_time);
    }

    /**
     * @see \Tuleap\User\UserAutocompletePostSearchEvent
     */
    public function userAutocompletePostSearch(\Tuleap\User\UserAutocompletePostSearchEvent $event)
    {
        $additional_information = $event->getAdditionalInformation();
        if (! isset($additional_information['tracker_id'])) {
            return;
        }
        $tracker_factory = TrackerFactory::instance();
        $tracker         = $tracker_factory->getTrackerById($additional_information['tracker_id']);
        if ($tracker === null) {
            return;
        }

        $autocompleted_user_list    = $event->getUserList();
        $autocompleted_user_id_list = [];
        foreach ($autocompleted_user_list as $autocompleted_user) {
            $autocompleted_user_id_list[] = $autocompleted_user['user_id'];
        }
        $global_notification_subscribers_filter = new GlobalNotificationSubscribersFilter(new UnsubscribersNotificationDAO());
        $autocompleted_user_id_list_filtered    = $global_notification_subscribers_filter->filterInvalidUserIDs(
            $tracker,
            $autocompleted_user_id_list
        );

        $autocompleted_user_list_filtered = [];
        foreach ($autocompleted_user_list as $autocompleted_user) {
            if (in_array($autocompleted_user['user_id'], $autocompleted_user_id_list_filtered)) {
                $autocompleted_user_list_filtered[] = $autocompleted_user;
            }
        }

        $event->setUserList($autocompleted_user_list_filtered);
    }

    public function routeLegacyController(): \Tuleap\Tracker\TrackerPluginDefaultController
    {
        return new \Tuleap\Tracker\TrackerPluginDefaultController(new TrackerManager());
    }

    public function routeGetNotifications(): NotificationsAdminSettingsDisplayController
    {
        return new NotificationsAdminSettingsDisplayController(
            $this->getTrackerFactory(),
            new TrackerManager(),
            $this->getUserManager()
        );
    }

    public function routePostNotifications(): NotificationsAdminSettingsUpdateController
    {
        return new NotificationsAdminSettingsUpdateController(
            $this->getTrackerFactory(),
            $this->getUserManager()
        );
    }

    public function routeGetNotificationsMy(): NotificationsUserSettingsDisplayController
    {
        return new NotificationsUserSettingsDisplayController(
            TemplateRendererFactory::build()->getRenderer(TRACKER_TEMPLATE_DIR . '/notifications/'),
            $this->getTrackerFactory(),
            new TrackerManager(),
            new UserNotificationSettingsRetriever(
                new Tracker_GlobalNotificationDao(),
                new UnsubscribersNotificationDAO(),
                new UserNotificationOnlyStatusChangeDAO(),
                new InvolvedNotificationDao()
            )
        );
    }

    public function routePostNotificationsMy(): NotificationsUserSettingsUpdateController
    {
        return new NotificationsUserSettingsUpdateController(
            $this->getTrackerFactory(),
            new UserNotificationSettingsDAO(),
            new ProjectHistoryDao()
        );
    }

    public function routePostWebhooksDelete(): WebhookDeleteController
    {
        return new WebhookDeleteController(
            new WebhookFactory(new WebhookDao()),
            $this->getTrackerFactory(),
            new WebhookDao()
        );
    }

    public function routePostWebhooksCreate(): WebhookCreateController
    {
        return new WebhookCreateController(
            new WebhookDao(),
            $this->getTrackerFactory(),
            new WebhookURLValidator()
        );
    }

    public function routePostWebhooksEdit(): WebhookEditController
    {
        return new WebhookEditController(
            new WebhookFactory(new WebhookDao()),
            TrackerFactory::instance(),
            new WebhookDao(),
            new WebhookURLValidator()
        );
    }

    public function routeGetWorkflowTransitions(): WorkflowTransitionController
    {
        return new WorkflowTransitionController(
            $this->getTrackerFactory(),
            new TrackerManager(),
            new WorkflowMenuTabPresenterBuilder(),
            EventManager::instance()
        );
    }

    public function routePostInvertCommentsOrder(): InvertCommentsController
    {
        return new InvertCommentsController();
    }

    public function routePostInvertDisplayChanges(): InvertDisplayChangesController
    {
        return new InvertDisplayChangesController();
    }

    public function routeConfig(): ConfigController
    {
        $nature_dao              = new TypeDao();
        $nature_validator        = new TypeValidator($nature_dao);
        $admin_page_renderer     = new AdminPageRenderer();
        $artifact_link_usage_dao = new ArtifactLinksUsageDao();
        $artifact_deletion_dao   = new ArtifactsDeletionConfigDAO();

        return new ConfigController(
            new CSRFSynchronizerToken(TRACKER_BASE_URL . '/config.php'),
            new MailGatewayConfigController(
                new MailGatewayConfig(
                    new MailGatewayConfigDao(),
                    MailTransportBuilder::getPlatformMailConfiguration(),
                ),
                new Config_LocalIncFinder(),
                EventManager::instance(),
                $admin_page_renderer
            ),
            new TypeConfigController(
                new TypeCreator(
                    $nature_dao,
                    $nature_validator
                ),
                new TypeEditor(
                    $nature_dao,
                    $nature_validator
                ),
                new TypeDeletor(
                    $nature_dao,
                    $nature_validator
                ),
                new TypePresenterFactory(
                    $nature_dao,
                    $artifact_link_usage_dao
                ),
                new TypeUsagePresenterFactory(
                    $nature_dao
                ),
                $admin_page_renderer
            ),
            new TrackerReportConfigController(
                new TrackerReportConfig(
                    new TrackerReportConfigDao()
                ),
                $admin_page_renderer
            ),
            new ArtifactsDeletionConfigController(
                $admin_page_renderer,
                new ArtifactsDeletionConfig(
                    $artifact_deletion_dao
                ),
                $artifact_deletion_dao,
                PluginManager::instance()
            )
        );
    }

    public function routeGetFieldsPermissionsByField(): DispatchableWithRequest
    {
        return new ByFieldController(TrackerFactory::instance(), TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../templates/permission'));
    }

    public function routeGetFieldsPermissionsByGroup(): DispatchableWithRequest
    {
        return new ByGroupController(TrackerFactory::instance(), TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../templates/permission'));
    }

    public function routePostFieldsPermissions(): DispatchableWithRequest
    {
        return new PermissionsOnFieldsUpdateController(TrackerFactory::instance());
    }

    public function routeJiraProjectListController(): DispatchableWithRequest
    {
        return new JiraProjectListController(
            $this->getProjectManager(),
            $this->getTrackerCreationPermissionChecker(),
            new JiraProjectBuilder(),
            new ClientWrapperBuilder(Closure::fromCallable([ClientWrapper::class, 'build'])),
            $this->getBackendLogger(),
        );
    }

    public function routeJiraTrackerListController(): DispatchableWithRequest
    {
        return new JiraTrackersListController(
            $this->getProjectManager(),
            $this->getTrackerCreationPermissionChecker(),
            new JiraTrackerBuilder(),
            new ClientWrapperBuilder(Closure::fromCallable([ClientWrapper::class, 'build'])),
        );
    }

    public function routeChangesetContentRetriever(): TextDiffRetriever
    {
        return new TextDiffRetriever(
            $this->getArtifactFactory(),
            new ChangesetsForDiffRetriever(
                Tracker_Artifact_ChangesetFactoryBuilder::build(),
                $this->getTrackerFormElementFactory()
            ),
            new DiffProcessor(new Codendi_UnifiedDiffFormatter())
        );
    }

    public function checkCrossReferenceValidityEvent(CheckCrossReferenceValidityEvent $event): void
    {
        $cross_reference_validator = new CrossReferenceValidator(Tracker_ArtifactFactory::instance());
        $cross_reference_validator->removeInvalidCrossReferences($event);
    }

    public function collectRoutesEvent(\Tuleap\Request\CollectRoutesEvent $event)
    {
        $event->getRouteCollector()->addGroup(TRACKER_BASE_URL, function (FastRoute\RouteCollector $r) {
            $r->addRoute(['GET', 'POST'], '[/[index.php]]', $this->getRouteHandler('routeLegacyController'));

            $r->post('/invert_comments_order.php', $this->getRouteHandler('routePostInvertCommentsOrder'));
            $r->post('/invert_display_changes.php', $this->getRouteHandler('routePostInvertDisplayChanges'));

            $r->addRoute(['GET', 'POST'], '/config.php', $this->getRouteHandler('routeConfig'));

            $r->get('/notifications/{id:\d+}/', $this->getRouteHandler('routeGetNotifications'));
            $r->post('/notifications/{id:\d+}/', $this->getRouteHandler('routePostNotifications'));
            $r->get('/notifications/my/{id:\d+}/', $this->getRouteHandler('routeGetNotificationsMy'));
            $r->post('/notifications/my/{id:\d+}/', $this->getRouteHandler('routePostNotificationsMy'));

            $r->get(ByFieldController::URL . '/{id:\d+}', $this->getRouteHandler('routeGetFieldsPermissionsByField'));
            $r->get(ByGroupController::URL . '/{id:\d+}', $this->getRouteHandler('routeGetFieldsPermissionsByGroup'));
            $r->post(PermissionsOnFieldsUpdateController::URL . '/{id:\d+}', $this->getRouteHandler('routePostFieldsPermissions'));

            $r->post('/webhooks/delete', $this->getRouteHandler('routePostWebhooksDelete'));
            $r->post('/webhooks/create', $this->getRouteHandler('routePostWebhooksCreate'));
            $r->post('/webhooks/edit', $this->getRouteHandler('routePostWebhooksEdit'));

            $r->get('/workflow/{tracker_id:\d+}/transitions', $this->getRouteHandler('routeGetWorkflowTransitions'));

            $r->get('/attachments/{id:\d+}-{filename}', $this->getRouteHandler('routeAttachments'));
            $r->get('/attachments/{preview:preview}/{id:\d+}-{filename}', $this->getRouteHandler('routeAttachments'));

            $r->get('/' . Tracker::GLOBAL_ADMIN_URL . '/{id:\d+}', $this->getRouteHandler('routeGlobalAdminTrackers'));
            $r->post('/' . Tracker::GLOBAL_ADMIN_URL . '/{id:\d+}/' . PromoteTrackersController::URL, $this->getRouteHandler('routeGlobalAdminPromoteTrackers'));
            $r->post('/' . MarkTrackerAsDeletedController::DELETION_URL . '/{id:\d+}', $this->getRouteHandler('routeMarkTrackerAsDeleted'));
            $r->addRoute(['GET', 'POST'], '/' . Tracker::GLOBAL_ADMIN_URL . '/{id:\d+}/' . ArtifactLinksController::URL, $this->getRouteHandler('routeGlobalAdminArtifactLinks'));

            $r->post('/{project_name:[A-z0-9-]+}/jira/project_list', $this->getRouteHandler('routeJiraProjectListController'));
            $r->post('/{project_name:[A-z0-9-]+}/jira/{jira_project_key:[A-z]+}/tracker_list', $this->getRouteHandler('routeJiraTrackerListController'));
            $r->get('/{project_name:[A-z0-9-]+}/new', $this->getRouteHandler('routeCreateNewTracker'));
            $r->get('/{project_name:[A-z0-9-]+}/new-information', $this->getRouteHandler('routeCreateNewTracker'));
            $r->post('/{project_name:[A-z0-9-]+}/new-information', $this->getRouteHandler('routeProcessNewTrackerCreation'));

            $r->get('/changeset/{changeset_id:[0-9]+}/diff/{format:html|text|strip-html}/{artifact_id:[0-9]+}/{field_id:[0-9]+}', $this->getRouteHandler('routeChangesetContentRetriever'));
        });

        $event->getRouteCollector()->addRoute(
            ['OPTIONS', 'HEAD', 'PATCH', 'DELETE', 'POST', 'PUT'],
            '/uploads/tracker/file/{id:\d+}',
            $this->getRouteHandler('routeUploads')
        );
    }

    public function routeMarkTrackerAsDeleted(): MarkTrackerAsDeletedController
    {
        return new MarkTrackerAsDeletedController(
            TrackerFactory::instance(),
            new \Tuleap\Tracker\Admin\GlobalAdmin\GlobalAdminPermissionsChecker(
                new User_ForgeUserGroupPermissionsManager(
                    new User_ForgeUserGroupPermissionsDao()
                )
            ),
            new CSRFSynchronizerTokenProvider(),
            EventManager::instance(),
            ReferenceManager::instance(),
            new FieldDao(),
            new TriggersDao(),
            new ProjectHistoryDao(),
        );
    }

    public function routeGlobalAdminPromoteTrackers(): PromoteTrackersController
    {
        return new PromoteTrackersController(
            ProjectManager::instance(),
            new \Tuleap\Tracker\Admin\GlobalAdmin\GlobalAdminPermissionsChecker(
                new User_ForgeUserGroupPermissionsManager(
                    new User_ForgeUserGroupPermissionsDao()
                )
            ),
            TrackerFactory::instance(),
            new TrackerInNewDropdownDao(),
            new CSRFSynchronizerTokenProvider(),
            new ProjectHistoryDao()
        );
    }

    public function routeGlobalAdminTrackers(): TrackersDisplayController
    {
        return new TrackersDisplayController(
            ProjectManager::instance(),
            new TrackerManager(),
            new \Tuleap\Tracker\Admin\GlobalAdmin\GlobalAdminPermissionsChecker(
                new User_ForgeUserGroupPermissionsManager(
                    new User_ForgeUserGroupPermissionsDao()
                )
            ),
            TrackerFactory::instance(),
            TemplateRendererFactory::build(),
            new TrackerInNewDropdownDao(),
            new CSRFSynchronizerTokenProvider(),
            new FieldDao(),
            new TriggersDao()
        );
    }

    public function routeGlobalAdminArtifactLinks(): ArtifactLinksController
    {
        $dao                     = new ArtifactLinksUsageDao();
        $updater                 = new ArtifactLinksUsageUpdater($dao);
        $types_presenter_factory = new TypePresenterFactory(new TypeDao(), $dao);
        $event_manager           = EventManager::instance();

        return new ArtifactLinksController(
            ProjectManager::instance(),
            new TrackerManager(),
            new \Tuleap\Tracker\Admin\GlobalAdmin\GlobalAdminPermissionsChecker(
                new User_ForgeUserGroupPermissionsManager(
                    new User_ForgeUserGroupPermissionsDao()
                )
            ),
            $dao,
            $updater,
            $types_presenter_factory,
            $event_manager
        );
    }

    public function routeUploads(): FileUploadController
    {
        $file_ongoing_upload_dao = new FileOngoingUploadDao();
        $db_connection           = DBFactory::getMainTuleapDBConnection();
        $formelement_factory     = Tracker_FormElementFactory::instance();
        $path_allocator          = new UploadPathAllocator(
            $file_ongoing_upload_dao,
            $formelement_factory
        );
        $current_user_provider   = new RESTCurrentUserMiddleware(\Tuleap\REST\UserManager::build(), new BasicAuthentication());

        return FileUploadController::build(
            new FileDataStore(
                new FileBeingUploadedInformationProvider($path_allocator, $file_ongoing_upload_dao, $current_user_provider),
                new FileBeingUploadedWriter($path_allocator, $db_connection),
                new FileBeingUploadedLocker($path_allocator),
                new FileUploadFinisher($file_ongoing_upload_dao, $formelement_factory),
                new FileUploadCanceler($path_allocator, $file_ongoing_upload_dao)
            ),
            $current_user_provider,
        );
    }

    public function routeAttachments(): AttachmentController
    {
        $file_ongoing_upload_dao = new FileOngoingUploadDao();
        $form_element_factory    = Tracker_FormElementFactory::instance();
        $path_allocator          = new UploadPathAllocator($file_ongoing_upload_dao, $form_element_factory);

        $url_verification = new URLVerification();

        $binary_file_response_builder = new BinaryFileResponseBuilder(
            HTTPFactoryBuilder::responseFactory(),
            HTTPFactoryBuilder::streamFactory()
        );
        $file_info_factory            = new Tracker_FileInfoFactory(
            new Tracker_FileInfoDao(),
            $form_element_factory,
            Tracker_ArtifactFactory::instance()
        );

        $current_user_provider = new RESTCurrentUserMiddleware(RESTUserManager::build(), new BasicAuthentication());

        return new AttachmentController(
            $url_verification,
            $file_ongoing_upload_dao,
            $form_element_factory,
            new FileBeingUploadedInformationProvider($path_allocator, $file_ongoing_upload_dao, $current_user_provider),
            $file_info_factory,
            $binary_file_response_builder,
            new SapiStreamEmitter(),
            $current_user_provider,
            new SessionWriteCloseMiddleware(),
            $current_user_provider,
            new TuleapRESTCORSMiddleware()
        );
    }

    public function collectCLICommands(CLICommandsCollector $commands_collector): void
    {
        $commands_collector->addCommand(
            TrackerForceNotificationsLevelCommand::NAME,
            function (): TrackerForceNotificationsLevelCommand {
                return new TrackerForceNotificationsLevelCommand(
                    $this->getForceUsageUpdater(),
                    ProjectManager::instance(),
                    new NotificationLevelExtractor(),
                    $this->getTrackerFactory(),
                    new TrackerDao()
                );
            }
        );
    }

    public function routeCreateNewTracker(): TrackerCreationController
    {
        return new TrackerCreationController(
            new TrackerCreationBreadCrumbsBuilder(),
            TemplateRendererFactory::build(),
            \UserManager::instance(),
            \ProjectManager::instance(),
            new TrackerCreationPresenterBuilder(
                $this->getProjectManager(),
                new TrackerDao(),
                new PendingJiraImportDao(),
                \TrackerFactory::instance(),
                new DefaultTemplatesCollectionBuilder(\EventManager::instance()),
                $this->getJiraRunner(BackendLogger::getDefaultLogger())
            ),
            $this->getTrackerCreationPermissionChecker()
        );
    }

    public function routeProcessNewTrackerCreation(): TrackerCreationProcessorController
    {
        $user_manager = UserManager::instance();

        $logger = BackendLogger::getDefaultLogger();

        $tracker_creator = TrackerCreator::build();

        return new TrackerCreationProcessorController(
            $user_manager,
            \ProjectManager::instance(),
            $tracker_creator,
            $this->getTrackerCreationPermissionChecker(),
            new DefaultTemplatesCollectionBuilder(\EventManager::instance()),
            new AsyncJiraScheduler(
                $logger,
                new KeyFactory(),
                new PendingJiraImportDao(),
                $this->getJiraRunner($logger)
            )
        );
    }

    /**
     * @return NotificationsForceUsageUpdater
     */
    private function getForceUsageUpdater()
    {
        return new NotificationsForceUsageUpdater(
            new RecipientsManager(
                Tracker_FormElementFactory::instance(),
                UserManager::instance(),
                new UnsubscribersNotificationDAO(),
                new UserNotificationSettingsRetriever(
                    new Tracker_GlobalNotificationDao(),
                    new UnsubscribersNotificationDAO(),
                    new UserNotificationOnlyStatusChangeDAO(),
                    new InvolvedNotificationDao()
                ),
                new UserNotificationOnlyStatusChangeDAO()
            ),
            new UserNotificationSettingsDAO()
        );
    }

    public function getProjectWithTrackerAdministrationPermission(GetProjectWithTrackerAdministrationPermission $event)
    {
        $user = $event->getUser();
        $dao  = new \Tuleap\Tracker\dao\ProjectDao(
            new \Tuleap\Tracker\Admin\GlobalAdmin\GlobalAdminPermissionsChecker(
                new User_ForgeUserGroupPermissionsManager(new User_ForgeUserGroupPermissionsDao())
            )
        );

        $matching_projects_rows = $dao->searchProjectsForREST($user, $event->getLimit(), $event->getOffset());
        $total_size             = $dao->foundRows();

        $project_with_tracker_administration = [];
        foreach ($matching_projects_rows as $project_row) {
            $trackers = $this->getTrackerFactory()->getTrackersByProjectIdUserCanAdministration(
                $project_row['group_id'],
                $user
            );

            if (count($trackers) > 0) {
                $project_with_tracker_administration[] = new Project($project_row);
            }
        }

        $paginated_projects = new PaginatedProjects($project_with_tracker_administration, $total_size);
        $event->setPaginatedProjects($paginated_projects);
    }

    public function statisticsCollectionCollector(StatisticsCollectionCollector $collector)
    {
        $collector->addStatistics(
            dgettext('tuleap-tracker', 'Artifacts'),
            $this->getArtifactDao()->countArtifacts(),
            $this->getArtifactDao()->countArtifactsRegisteredBefore($collector->getTimestamp())
        );
    }

    /**
     * @return Tracker_ArtifactDao
     */
    private function getArtifactDao()
    {
        return new Tracker_ArtifactDao();
    }

    /**
     * @see CollectTuleapComputedMetrics
     */
    public function collectComputedMetrics(CollectTuleapComputedMetrics $collect_tuleap_computed_metrics): void
    {
        $prometheus = $collect_tuleap_computed_metrics->getPrometheus();
        $prometheus->gaugeSet(
            'tracker_artifacts_count',
            'Number of tracker artifacts',
            $this->getArtifactDao()->countArtifacts()
        );
        $prometheus->gaugeSet(
            'tracker_artifact_changesets_count',
            'Number of tracker artifact changesets',
            (new Tracker_Artifact_ChangesetDao())->countChangesets()
        );
    }

    public function configureAtXMLImport(ConfigureAtXMLImport $event)
    {
        if ($event->getWidget()->getId() === Tracker_Widget_ProjectRenderer::ID) {
            (new ProjectRendererWidgetXMLImporter())->import($event);
        }
    }

    public function serviceEnableForXmlImportRetriever(ServiceEnableForXmlImportRetriever $event): void
    {
        $event->addServiceIfPluginIsNotRestricted($this, $this->getServiceShortname());
    }

    private function getTrackerChecker(): TrackerCreationDataChecker
    {
        return TrackerCreationDataChecker::build();
    }

    private function getAssets(): IncludeAssets
    {
        return new IncludeAssets(__DIR__ . '/../frontend-assets', '/assets/trackers');
    }

    public function collectOAuth2ScopeBuilder(OAuth2ScopeBuilderCollector $collector): void
    {
        $collector->addOAuth2ScopeBuilder(
            new AuthenticationScopeBuilderFromClassNames(
                OAuth2TrackerReadScope::class
            )
        );
    }

    private function getTrackerCreationPermissionChecker(): TrackerCreationPermissionChecker
    {
        return new TrackerCreationPermissionChecker(
            new \Tuleap\Tracker\Admin\GlobalAdmin\GlobalAdminPermissionsChecker(
                new User_ForgeUserGroupPermissionsManager(
                    new User_ForgeUserGroupPermissionsDao()
                )
            ),
        );
    }

    public function getConfigKeys(ConfigClassProvider $event): void
    {
        $event->addConfigClass(Tracker_FormElement_Field_ArtifactLink::class);
        $event->addConfigClass(\Tuleap\Tracker\Creation\JiraImporter\ClientWrapper::class);
        $event->addConfigClass(Tracker_ReportDao::class);
        $event->addConfigClass(ColorpickerMountPointPresenterBuilder::class);
        $event->addConfigClass(PromotedTrackerConfiguration::class);
    }

    private function getMailNotificationBuilder(): MailNotificationBuilder
    {
        return new MailNotificationBuilder(
            new MailBuilder(
                TemplateRendererFactory::build(),
                new MailFilter(
                    UserManager::instance(),
                    new ProjectAccessChecker(
                        new RestrictedUserCanAccessProjectVerifier(),
                        EventManager::instance()
                    ),
                    new MailLogger()
                )
            )
        );
    }

    private function getJiraSuccessImportNotifier(): JiraSuccessImportNotifier
    {
        return new JiraSuccessImportNotifier(
            new JiraImportNotifier(
                $this->getMailNotificationBuilder(),
            ),
            new LocaleSwitcher(),
            TemplateRendererFactory::build(),
        );
    }

    private function getJiraErrorImportNotifier(): JiraErrorImportNotifier
    {
        return new JiraErrorImportNotifier(
            new JiraImportNotifier(
                $this->getMailNotificationBuilder(),
            ),
            new LocaleSwitcher(),
            TemplateRendererFactory::build(),
        );
    }

    private function getJiraRunner(\Psr\Log\LoggerInterface $logger): JiraRunner
    {
        $user_manager              = $this->getUserManager();
        $forge_user                = $user_manager->getUserById(TrackerImporterUser::ID);
        $jira_user_on_tuleap_cache = new JiraUserOnTuleapCache(
            new JiraTuleapUsersMapping(),
            $forge_user
        );

        return new JiraRunner(
            $logger,
            new QueueFactory($logger),
            new \Tuleap\Queue\WorkerAvailability(),
            new KeyFactory(),
            FromJiraTrackerCreator::build($jira_user_on_tuleap_cache),
            new PendingJiraImportDao(),
            $this->getJiraSuccessImportNotifier(),
            $this->getJiraErrorImportNotifier(),
            $this->getUserManager(),
            $jira_user_on_tuleap_cache,
            new ClientWrapperBuilder(Closure::fromCallable([ClientWrapper::class, 'build'])),
        );
    }

    public function collectNewDropdownLinksForProject(NewDropdownProjectLinksCollector $collector): void
    {
        (new \Tuleap\Tracker\NewDropdown\TrackerLinksInNewDropdownCollector(
            new TrackerInNewDropdownRetriever(
                new TrackerInNewDropdownDao(),
                TrackerFactory::instance()
            ),
            new TrackerNewDropdownLinkPresenterBuilder(),
        ))->collect($collector);
    }

    public function crossReferenceByNatureOrganizer(CrossReferenceByNatureOrganizer $by_nature_organizer): void
    {
        $tracker_organizer = new CrossReferenceArtifactOrganizer(
            Tracker_ArtifactFactory::instance(),
        );

        $tracker_organizer->organizeArtifactReferences($by_nature_organizer);
    }

    public function defineIssueTemplateEvent(DefineIssueTemplateEvent $event): void
    {
        \Tuleap\Tracker\Template\IssuesTemplate::defineTemplate($event->getProject(), EventManager::instance());
    }

    public function issuesTemplateDashboardDefinition(IssuesTemplateDashboardDefinition $dashboard_definition): void
    {
        \Tuleap\Tracker\Template\IssuesTemplate::defineDashboards($dashboard_definition);
    }

    public function receivePotentialReferences(PotentialReferencesReceived $event): void
    {
        $status_semantic_factory        = \Tracker_Semantic_StatusFactory::instance();
        $form_element_factory           = \Tracker_FormElementFactory::instance();
        $artifact_factory               = \Tracker_ArtifactFactory::instance();
        $user_manager                   = \UserManager::instance();
        $event_manager                  = EventManager::instance();
        $reference_manager              = \ReferenceManager::instance();
        $db_transaction_executor        = new DBTransactionExecutorWithConnection(
            DBFactory::getMainTuleapDBConnection()
        );
        $artifact_links_usage_dao       = new ArtifactLinksUsageDao();
        $fields_retriever               = new FieldsToBeSavedInSpecificOrderRetriever($form_element_factory);
        $first_possible_value_retriever = new FirstPossibleValueInListRetriever(
            new FirstValidValueAccordingToDependenciesRetriever($form_element_factory),
            new ValidValuesAccordingToTransitionsRetriever(
                \Workflow_Transition_ConditionFactory::build()
            )
        );
        $logger                         = $this->getBackendLogger();
        $changeset_creator              = new NewChangesetCreator(
            new \Tracker_Artifact_Changeset_NewChangesetFieldsValidator(
                $form_element_factory,
                new ArtifactLinkValidator(
                    $artifact_factory,
                    new TypePresenterFactory(new TypeDao(), $artifact_links_usage_dao),
                    $artifact_links_usage_dao,
                    $event_manager,
                ),
                new WorkflowUpdateChecker(
                    new FrozenFieldDetector(
                        new TransitionRetriever(
                            new StateFactory(
                                TransitionFactory::instance(),
                                new SimpleWorkflowDao()
                            ),
                            new TransitionExtractor()
                        ),
                        FrozenFieldsRetriever::instance()
                    )
                )
            ),
            $fields_retriever,
            $event_manager,
            new \Tracker_Artifact_Changeset_ChangesetDataInitializator($form_element_factory),
            $db_transaction_executor,
            ArtifactChangesetSaver::build(),
            new ParentLinkAction($artifact_factory),
            new AfterNewChangesetHandler($artifact_factory, $fields_retriever),
            ActionsRunner::build($logger),
            new ChangesetValueSaver(),
            \WorkflowFactory::instance(),
            new CommentCreator(
                new \Tracker_Artifact_Changeset_CommentDao(),
                $reference_manager,
                new TrackerPrivateCommentUGroupPermissionInserter(
                    new TrackerPrivateCommentUGroupPermissionDao()
                ),
                new ChangesetCommentIndexer(
                    new ItemToIndexQueueEventBased($event_manager),
                    $event_manager,
                    new \Tracker_Artifact_Changeset_CommentDao(),
                ),
                new TextValueValidator(),
            )
        );

        $handler = new ArtifactClosingReferencesHandler(
            $logger,
            $reference_manager,
            $artifact_factory,
            $user_manager,
            new ArtifactWasClosedCache(),
            new ArtifactCloser(
                new StatusFieldRetriever($status_semantic_factory),
                new StatusValueRetriever($status_semantic_factory, $first_possible_value_retriever),
                new DoneValueRetriever(
                    new SemanticDoneFactory(new SemanticDoneDao(), new SemanticDoneValueChecker()),
                    $first_possible_value_retriever
                ),
                $logger,
                new CommentOnlyChangesetCreator($changeset_creator),
                $changeset_creator
            )
        );
        $handler->handlePotentialReferencesReceived($event);
    }

    public function indexAllPendingItems(IndexAllPendingItemsEvent $index_all_possible_items_event): void
    {
        $index_batch_queue = $index_all_possible_items_event->getItemToIndexBatchQueue();
        $index_batch_queue->startBatchingItemsIntoQueue(
            function (\Tuleap\Search\ItemToIndexQueue $index_queue) use ($index_all_possible_items_event): void {
                (new IndexAllArtifactsProcessor(
                    new \Tuleap\Tracker\Search\IndexArtifactDAO(),
                    static function (): Tracker_ArtifactFactory {
                        Tracker_ArtifactFactory::clearInstance();
                        return Tracker_ArtifactFactory::instance();
                    },
                    $index_queue,
                    new ChangesetCommentIndexer(
                        $index_queue,
                        EventManager::instance(),
                        new \Tracker_Artifact_Changeset_CommentDao(),
                    )
                ))->queueAllPendingArtifactsIntoIndexQueue(
                    $index_all_possible_items_event->getProcessQueueForItemCategory('artifacts')
                );
            }
        );
    }

    public function identifyAllItemsToIndex(): void
    {
        (new \Tuleap\Tracker\Search\IndexArtifactDAO())->markExistingArtifactsAsPending();
    }

    public function notificationsOnOwnActionsCollection(NotificationsOnOwnActionsCollection $notifications_on_own_actions_collection): void
    {
        $notifications_on_own_actions_collection->add(
            NotificationOnOwnActionPreference::getPresenter($notifications_on_own_actions_collection->user)
        );
    }

    public function notificationsOnOwnActionsUpdate(NotificationsOnOwnActionsUpdate $event): void
    {
        $something_changed = NotificationOnOwnActionPreference::updatePreference($event->request, $event->user);
        if ($something_changed) {
            $event->something_has_changed = true;
        }
    }

    #[ListeningToEventClass]
    public function setDefaultCommentOrderUserPreference(UserPreferencesGetDefaultValue $event): void
    {
        if ($event->value === false && str_starts_with($event->key, 'tracker_comment_invertorder_')) {
            $event->setDefaultValue('0');
        }
    }
}
