<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Cocur\Slugify\Slugify;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\admin\PendingElements\PendingDocumentsRetriever;
use Tuleap\admin\ProjectEdit\ProjectStatusUpdate;
use Tuleap\Admin\SiteAdministrationAddOption;
use Tuleap\Admin\SiteAdministrationPluginOption;
use Tuleap\Authentication\Scope\AuthenticationScopeBuilder;
use Tuleap\Authentication\Scope\AuthenticationScopeBuilderFromClassNames;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\BurningParrotCompatiblePageDetector;
use Tuleap\CLI\CLICommandsCollector;
use Tuleap\Config\ConfigClassProvider;
use Tuleap\Config\PluginWithConfigKeys;
use Tuleap\Date\DateHelper;
use Tuleap\Date\TlpRelativeDatePresenterBuilder;
use Tuleap\Event\Events\ExportXmlProject;
use Tuleap\Git\AccessRightsPresenterOptionsBuilder;
use Tuleap\Git\Account\AccountGerritController;
use Tuleap\Git\Account\PushSSHKeysController;
use Tuleap\Git\Account\ResynchronizeGroupsController;
use Tuleap\Git\Artifact\Action\CreateBranchButtonFetcher;
use Tuleap\Git\BreadCrumbDropdown\GitCrumbBuilder;
use Tuleap\Git\BreadCrumbDropdown\RepositoryCrumbBuilder;
use Tuleap\Git\BreadCrumbDropdown\RepositorySettingsCrumbBuilder;
use Tuleap\Git\BreadCrumbDropdown\ServiceAdministrationCrumbBuilder;
use Tuleap\Git\CIBuilds\BuildStatusChangePermissionDAO;
use Tuleap\Git\CIBuilds\BuildStatusChangePermissionManager;
use Tuleap\Git\CIBuilds\CITokenDao;
use Tuleap\Git\CIBuilds\CITokenManager;
use Tuleap\Git\CreateRepositoryController;
use Tuleap\Git\DefaultBranch\DefaultBranchRetriever;
use Tuleap\Git\DefaultBranch\DefaultBranchUpdateExecutorAsGitoliteUser;
use Tuleap\Git\DefaultBranch\DefaultBranchUpdater;
use Tuleap\Git\DefaultSettings\DefaultSettingsRouter;
use Tuleap\Git\DefaultSettings\IndexController;
use Tuleap\Git\DiskUsage\Collector;
use Tuleap\Git\DiskUsage\Retriever;
use Tuleap\Git\Gerrit\ReplicationHTTPUserAuthenticator;
use Tuleap\Git\GitXMLImportDefaultBranchRetriever;
use Tuleap\Git\RemoteServer\GerritCanMigrateChecker;
use Tuleap\Git\GerritServerResourceRestrictor;
use Tuleap\Git\GitGodObjectWrapper;
use Tuleap\Git\Gitolite\Gitolite3LogParser;
use Tuleap\Git\Gitolite\GitoliteAccessURLGenerator;
use Tuleap\Git\Gitolite\GitoliteFileLogsDao;
use Tuleap\Git\Gitolite\RegenerateConfigurationCommand;
use Tuleap\Git\Gitolite\SSHKey\AuthorizedKeysFileCreator;
use Tuleap\Git\Gitolite\SSHKey\DumperFactory;
use Tuleap\Git\Gitolite\SSHKey\ManagementDetector;
use Tuleap\Git\Gitolite\SSHKey\Provider\GerritServer;
use Tuleap\Git\Gitolite\SSHKey\Provider\GitoliteAdmin;
use Tuleap\Git\Gitolite\SSHKey\Provider\User;
use Tuleap\Git\Gitolite\SSHKey\Provider\WholeInstanceKeysAggregator;
use Tuleap\Git\GitProjectRenamer;
use Tuleap\Git\GitViews\Header\HeaderRenderer;
use Tuleap\Git\GitXmlExporter;
use Tuleap\Git\GlobalParameterDao;
use Tuleap\Git\History\Dao as HistoryDao;
use Tuleap\Git\History\GitPhpAccessLogger;
use Tuleap\Git\Hook\Asynchronous\AsynchronousEventHandler;
use Tuleap\Git\Hook\Asynchronous\DefaultBranchPushParser;
use Tuleap\Git\Hook\Asynchronous\DefaultBranchPushProcessorBuilder;
use Tuleap\Git\Hook\Asynchronous\GitRepositoryRetriever;
use Tuleap\Git\Hook\PreReceive\PreReceiveAction;
use Tuleap\Git\Hook\PreReceive\PreReceiveCommand;
use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\Plugin\ListeningToEventClass;
use Tuleap\Project\Service\CollectServicesAllowedForRestrictedEvent;
use Tuleap\Project\Service\ServiceClassnamesCollector;
use Tuleap\WebAssembly\FFIWASMCaller;
use Tuleap\Git\HTTP\HTTPAccessControl;
use Tuleap\Git\LatestHeartbeatsCollector;
use Tuleap\Git\Notifications\NotificationsForProjectMemberCleaner;
use Tuleap\Git\Notifications\UgroupsToNotifyDao;
use Tuleap\Git\Notifications\UgroupToNotifyUpdater;
use Tuleap\Git\Notifications\UsersToNotifyDao;
use Tuleap\Git\Permissions\DefaultFineGrainedPermissionFactory;
use Tuleap\Git\Permissions\DefaultFineGrainedPermissionReplicator;
use Tuleap\Git\Permissions\FineGrainedDao;
use Tuleap\Git\Permissions\FineGrainedPatternValidator;
use Tuleap\Git\Permissions\FineGrainedPermissionDestructor;
use Tuleap\Git\Permissions\FineGrainedPermissionFactory;
use Tuleap\Git\Permissions\FineGrainedPermissionReplicator;
use Tuleap\Git\Permissions\FineGrainedPermissionSaver;
use Tuleap\Git\Permissions\FineGrainedPermissionSorter;
use Tuleap\Git\Permissions\FineGrainedRegexpValidator;
use Tuleap\Git\Permissions\FineGrainedRepresentationBuilder;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Git\Permissions\FineGrainedUpdater;
use Tuleap\Git\Permissions\HistoryValueFormatter;
use Tuleap\Git\Permissions\PatternValidator;
use Tuleap\Git\Permissions\PermissionChangesDetector;
use Tuleap\Git\Permissions\RegexpFineGrainedDao;
use Tuleap\Git\Permissions\RegexpFineGrainedDisabler;
use Tuleap\Git\Permissions\RegexpFineGrainedEnabler;
use Tuleap\Git\Permissions\RegexpFineGrainedRetriever;
use Tuleap\Git\Permissions\RegexpPermissionFilter;
use Tuleap\Git\Permissions\RegexpRepositoryDao;
use Tuleap\Git\Permissions\RegexpTemplateDao;
use Tuleap\Git\Permissions\TemplateFineGrainedPermissionSaver;
use Tuleap\Git\Permissions\TemplatePermissionsUpdater;
use Tuleap\Git\PermissionsPerGroup\AdminUrlBuilder;
use Tuleap\Git\PermissionsPerGroup\CollectionOfUGroupRepresentationBuilder;
use Tuleap\Git\PermissionsPerGroup\CollectionOfUgroupsFormatter;
use Tuleap\Git\PermissionsPerGroup\CollectionOfUGroupsRepresentationFormatter;
use Tuleap\Git\PermissionsPerGroup\GitJSONPermissionsRetriever;
use Tuleap\Git\PermissionsPerGroup\GitPaneSectionCollector;
use Tuleap\Git\PermissionsPerGroup\PermissionPerGroupController;
use Tuleap\Git\PermissionsPerGroup\PermissionPerGroupGitSectionBuilder;
use Tuleap\Git\PermissionsPerGroup\RepositoryFineGrainedRepresentationBuilder;
use Tuleap\Git\PermissionsPerGroup\RepositorySimpleRepresentationBuilder;
use Tuleap\Git\Reference\CommitDetailsCacheDao;
use Tuleap\Git\Reference\CommitDetailsCrossReferenceInformationBuilder;
use Tuleap\Git\Reference\CommitDetailsRetriever;
use Tuleap\Git\Reference\CommitProvider;
use Tuleap\Git\Reference\CrossReferenceGitEnhancer;
use Tuleap\Git\Reference\CrossReferenceGitOrganizer;
use Tuleap\Git\Reference\OrganizeableGitCrossReferencesAndTheContributorsCollector;
use Tuleap\Git\Reference\ReferenceAdministrationWarningsCollectorEventHandler;
use Tuleap\Git\Reference\ReferenceDao;
use Tuleap\Git\RemoteServer\Gerrit\HttpUserValidator;
use Tuleap\Git\RemoteServer\Gerrit\Restrictor;
use Tuleap\Git\Repository\DescriptionUpdater;
use Tuleap\Git\Repository\GitPHPProjectRetriever;
use Tuleap\Git\Repository\GitRepositoryHeaderDisplayer;
use Tuleap\Git\Repository\GitRepositoryHeaderDisplayerBuilder;
use Tuleap\Git\Repository\GitRepositoryObjectsSizeRetriever;
use Tuleap\Git\Repository\RepositoriesWithObjectsOverTheLimitCommand;
use Tuleap\Git\Repository\RepositoryFromRequestRetriever;
use Tuleap\Git\Repository\Settings\CITokenController;
use Tuleap\Git\Repository\Settings\CITokenRouter;
use Tuleap\Git\Repository\Settings\WebhookAddController;
use Tuleap\Git\Repository\Settings\WebhookDeleteController;
use Tuleap\Git\Repository\Settings\WebhookEditController;
use Tuleap\Git\Repository\Settings\WebhookRouter;
use Tuleap\Git\Repository\View\CommitForCurrentTreeRetriever;
use Tuleap\Git\Repository\View\FilesHeaderPresenterBuilder;
use Tuleap\Git\Repository\View\RepositoryHeaderPresenterBuilder;
use Tuleap\Git\RepositoryList\GitRepositoryListController;
use Tuleap\Git\RepositoryList\ListPresenterBuilder;
use Tuleap\Git\REST\v1\Branch\BranchNameCreatorFromArtifact;
use Tuleap\Git\RestrictedGerritServerDao;
use Tuleap\Git\SystemEvents\ParseGitolite3Logs;
use Tuleap\Git\SystemEvents\ProjectIsSuspended;
use Tuleap\Git\User\AccessKey\Scope\GitRepositoryAccessKeyScope;
use Tuleap\Git\Webhook\WebhookDao;
use Tuleap\Git\XmlUgroupRetriever;
use Tuleap\GitBundle;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Layout\HomePage\StatisticsCollectionCollector;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Mail\MailFilter;
use Tuleap\Mail\MailLogger;
use Tuleap\Project\Admin\Navigation\NavigationDropdownItemPresenter;
use Tuleap\Project\Admin\Navigation\NavigationDropdownQuickLinksCollector;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupDisplayEvent;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupPaneCollector;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupFormatter;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupRepresentationBuilder;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupRetriever;
use Tuleap\Project\Admin\ProjectUGroup\UserBecomesProjectAdmin;
use Tuleap\Project\Admin\ProjectUGroup\UserIsNoLongerProjectAdmin;
use Tuleap\Project\Admin\Reference\ReferenceAdministrationWarningsCollectorEvent;
use Tuleap\Project\Event\ProjectServiceBeforeActivation;
use Tuleap\Project\Event\ProjectUnixNameIsEditable;
use Tuleap\Project\Flags\ProjectFlagsBuilder;
use Tuleap\Project\Flags\ProjectFlagsDao;
use Tuleap\Project\HeartbeatsEntryCollection;
use Tuleap\Project\HierarchyDisplayer;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\Registration\RegisterProjectCreationEvent;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\Project\Service\AddMissingService;
use Tuleap\Project\Service\PluginWithService;
use Tuleap\Project\Service\ServiceDisabledCollector;
use Tuleap\Project\Status\ProjectSuspendedAndNotBlockedWarningCollector;
use Tuleap\Project\XML\ServiceEnableForXmlImportRetriever;
use Tuleap\Queue\WorkerEvent;
use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Reference\GetReferenceEvent;
use Tuleap\Reference\Nature;
use Tuleap\Reference\NatureCollection;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\RestrictedUsersAreHandledByPluginEvent;
use Tuleap\SystemEvent\GetSystemEventQueuesEvent;
use Tuleap\Tracker\Artifact\ActionButtons\AdditionalArtifactActionButtonsFetcher;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneUsedExternalService;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneUsedExternalServiceEvent;
use Tuleap\User\AccessKey\AccessKeyDAO;
use Tuleap\User\AccessKey\AccessKeyVerifier;
use Tuleap\User\AccessKey\Scope\AccessKeyScopeBuilderCollector;
use Tuleap\User\AccessKey\Scope\AccessKeyScopeDAO;
use Tuleap\User\AccessKey\Scope\AccessKeyScopeRetriever;
use Tuleap\User\Account\AccountTabPresenterCollection;
use Tuleap\User\PasswordVerifier;
use Tuleap\WebAssembly\WasmtimeCacheConfigurationBuilder;

require_once 'constants.php';
require_once __DIR__ . '/../vendor/autoload.php';

class GitPlugin extends Plugin implements PluginWithConfigKeys, PluginWithService //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    public const LOG_IDENTIFIER = 'git_syslog';

    /**
     *
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var Git_UserAccountManager
     */
    private $user_account_manager;

    /**
     * Service short_name as it appears in 'service' table
     *
     * Should be transfered in 'ServiceGit' class when we introduce it
     */
    public const SERVICE_SHORTNAME = 'plugin_git';

    public const SYSTEM_NATURE_NAME = 'git_revision';

    private static $FREQUENCIES_GIT_READ = 'git';

    public function __construct($id)
    {
        parent::__construct($id);
        bindtextdomain('tuleap-git', __DIR__ . '/../site-content');
        bindtextdomain('gitphp', __DIR__ . '/../site-content-gitphp');

        $this->setScope(Plugin::SCOPE_PROJECT);
        $this->addHook(SiteAdministrationAddOption::NAME);
        $this->addHook('cssfile', 'cssFile');
        $this->addHook('javascript_file', 'jsFile');
        $this->addHook(Event::JAVASCRIPT, 'javascript');
        $this->addHook(Event::GET_SYSTEM_EVENT_CLASS, 'getSystemEventClass');
        $this->addHook(Event::GET_PLUGINS_AVAILABLE_KEYWORDS_REFERENCES, 'getReferenceKeywords');
        $this->addHook(NatureCollection::NAME);
        $this->addHook(GetReferenceEvent::NAME);
        $this->addHook('SystemEvent_PROJECT_IS_PRIVATE', 'changeProjectRepositoriesAccess');
        $this->addHook('SystemEvent_PROJECT_RENAME', 'systemEventProjectRename');
        $this->addHook(ProjectStatusUpdate::NAME);
        $this->addHook('file_exists_in_data_dir', 'file_exists_in_data_dir');

        // Stats plugin
        $this->addHook('plugin_statistics_disk_usage_collect_project', 'plugin_statistics_disk_usage_collect_project');
        $this->addHook('plugin_statistics_disk_usage_service_label', 'plugin_statistics_disk_usage_service_label');
        $this->addHook('plugin_statistics_color', 'plugin_statistics_color');

        $this->addHook(Event::DUMP_SSH_KEYS);
        $this->addHook(Event::EDIT_SSH_KEYS);
        $this->addHook(Event::PROCCESS_SYSTEM_CHECK);
        $this->addHook(Event::SYSTEM_EVENT_GET_TYPES_FOR_DEFAULT_QUEUE);
        $this->addHook(GetSystemEventQueuesEvent::NAME);
        $this->addHook(Event::SYSTEM_EVENT_GET_TYPES_FOR_CUSTOM_QUEUE);

        $this->addHook('permission_get_name', 'permission_get_name');
        $this->addHook('permission_get_object_type', 'permission_get_object_type');
        $this->addHook('permission_get_object_name', 'permission_get_object_name');
        $this->addHook('permission_user_allowed_to_change', 'permission_user_allowed_to_change');

        $this->addHook('statistics_collector', 'statistics_collector');

        $this->addHook('collect_ci_triggers', 'collect_ci_triggers');
        $this->addHook('save_ci_triggers', 'save_ci_triggers');
        $this->addHook('update_ci_triggers', 'update_ci_triggers');
        $this->addHook('delete_ci_triggers', 'delete_ci_triggers');

        $this->addHook('logs_daily', 'logsDaily');
        $this->addHook(\Tuleap\Widget\Event\GetWidget::NAME);
        $this->addHook(\Tuleap\Widget\Event\GetUserWidgetList::NAME);
        $this->addHook(\Tuleap\Widget\Event\GetProjectWidgetList::NAME);

        $this->addHook('SystemEvent_USER_RENAME', 'systemevent_user_rename');

        // User Group membership modification
        $this->addHook('project_admin_add_user');
        $this->addHook('project_admin_ugroup_add_user');
        $this->addHook('project_admin_remove_user');
        $this->addHook('project_admin_ugroup_remove_user');
        $this->addHook('project_admin_ugroup_deletion');
        $this->addHook('project_admin_remove_user_from_project_ugroups');
        $this->addHook('project_admin_ugroup_creation');
        $this->addHook('project_admin_parent_project_modification');
        $this->addHook(Event::UGROUP_MANAGER_UPDATE_UGROUP_BINDING_ADD);
        $this->addHook(Event::UGROUP_MANAGER_UPDATE_UGROUP_BINDING_REMOVE);

        // Project hierarchy modification
        $this->addHook(Event::PROJECT_SET_PARENT_PROJECT, 'project_admin_parent_project_modification');
        $this->addHook(Event::PROJECT_UNSET_PARENT_PROJECT, 'project_admin_parent_project_modification');

        $this->addHook(RegisterProjectCreationEvent::NAME);
        $this->addHook(RestrictedUsersAreHandledByPluginEvent::NAME);
        $this->addHook(Event::PROJECT_ACCESS_CHANGE);
        $this->addHook(Event::SITE_ACCESS_CHANGE);

        $this->addHook('fill_project_history_sub_events');
        $this->addHook(Event::POST_SYSTEM_EVENTS_ACTIONS);

        $this->addHook(Event::REST_RESOURCES);
        $this->addHook(Event::REST_PROJECT_RESOURCES);

        $this->addHook(ExportXmlProject::NAME);
        $this->addHook(Event::IMPORT_XML_PROJECT, 'importXmlProject');

        // Gerrit user suspension
        if (defined('LDAP_DAILY_SYNCHRO_UPDATE_USER')) {
            $this->addHook(LDAP_DAILY_SYNCHRO_UPDATE_USER);
        }

        $this->addHook(Event::SERVICES_TRUNCATED_EMAILS);

        $this->addHook('codendi_daily_start');

        $this->addHook(PermissionPerGroupDisplayEvent::NAME);

        $this->addHook(\Tuleap\Request\CollectRoutesEvent::NAME);
        $this->addHook(ReferenceAdministrationWarningsCollectorEvent::NAME);
    }

    public function getHooksAndCallbacks()
    {
        $this->addHook(HeartbeatsEntryCollection::NAME);
        $this->addHook(HierarchyDisplayer::NAME);
        $this->addHook(NavigationDropdownQuickLinksCollector::NAME);
        $this->addHook(UserBecomesProjectAdmin::NAME);
        $this->addHook(UserIsNoLongerProjectAdmin::NAME);
        $this->addHook(PermissionPerGroupPaneCollector::NAME);
        $this->addHook(ProjectSuspendedAndNotBlockedWarningCollector::NAME);
        $this->addHook(ProjectUnixNameIsEditable::NAME);
        $this->addHook(StatisticsCollectionCollector::NAME);
        $this->addHook(CLICommandsCollector::NAME);
        $this->addHook(AccessKeyScopeBuilderCollector::NAME);
        $this->addHook(AccountTabPresenterCollection::NAME);
        $this->addHook(PendingDocumentsRetriever::NAME);
        $this->addHook(WorkerEvent::NAME);

        if (defined('TRACKER_BASE_URL')) {
            $this->addHook(AdditionalArtifactActionButtonsFetcher::NAME);
            $this->addHook(SemanticDoneUsedExternalServiceEvent::NAME);
        }

        $this->addHook(CrossReferenceByNatureOrganizer::NAME);

        return parent::getHooksAndCallbacks();
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function serviceClassnamesCollector(ServiceClassnamesCollector $event): void
    {
        $event->addService($this->getServiceShortname(), \Tuleap\Git\GitService::class);
    }

    /**
     * @see Event::SERVICE_IS_USED
     * @param array{shortname: string, is_used: bool, group_id: int|string} $params
     */
    public function serviceIsUsed(array $params): void
    {
        // nothing to do for git
    }

    public function projectServiceBeforeActivation(ProjectServiceBeforeActivation $event): void
    {
        // nothing to do for git
    }

    public function serviceDisabledCollector(ServiceDisabledCollector $event): void
    {
        // nothing to do for git
    }

    public function addMissingService(AddMissingService $event): void
    {
        // nothing to do for git
    }

    public function exportXmlProject(ExportXmlProject $event): void
    {
        if (! $event->shouldExportAllData()) {
            return;
        }

        $this->getGitExporter($event->getProject())->exportToXml(
            $event->getIntoXml(),
            $event->getArchive(),
            $event->getTemporaryDumpPathOnFilesystem()
        );
    }

    private function getGitExporter(Project $project): GitXmlExporter
    {
        $user_manager = UserManager::instance();
        return new GitXmlExporter(
            $project,
            $this->getGitPermissionsManager(),
            $this->getUGroupManager(),
            $this->getRepositoryFactory(),
            $this->getLogger(),
            new GitBundle(new System_Command(), $this->getLogger()),
            $this->getGitLogDao(),
            $user_manager,
            new UserXMLExporter(
                $user_manager,
                new UserXMLExportedCollection(new XML_RNGValidator(), new XML_SimpleXMLCDATAFactory())
            ),
            EventManager::instance(),
            $this->getGitDao(),
            new DefaultBranchRetriever(),
        );
    }

    public function getServiceShortname(): string
    {
        return self::SERVICE_SHORTNAME;
    }

    public function siteAdministrationAddOption(SiteAdministrationAddOption $site_administration_add_option): void
    {
        $site_administration_add_option->addPluginOption(
            SiteAdministrationPluginOption::build(dgettext('tuleap-git', 'Git'), GIT_SITE_ADMIN_BASE_URL)
        );
    }

    public function getPluginInfo()
    {
        if (! $this->pluginInfo instanceof \GitPluginInfo) {
            $this->pluginInfo = new GitPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    #[ListeningToEventClass]
    public function statisticsFrequenciesLabels(\Tuleap\Statistics\FrequenciesLabels $event): void
    {
        $event->addLabel(self::$FREQUENCIES_GIT_READ, 'Git read access');
    }

    #[ListeningToEventClass]
    public function statisticsFrequenciesSamples(\Tuleap\Statistics\FrequenciesSamples $event): void
    {
        if ($event->requested_sample === self::$FREQUENCIES_GIT_READ) {
            $event->setSample(new Tuleap\Git\Statistics\FrequenciesSample());
        }
    }

    /**
     * Returns the configuration defined for given variable name
     *
     * @param String $key
     *
     * @return Mixed
     */
    public function getConfigurationParameter($key)
    {
        return $this->getPluginInfo()->getPropertyValueForName($key);
    }

    public function getConfigKeys(ConfigClassProvider $event): void
    {
        $event->addConfigClass(PreReceiveCommand::class);
        $event->addConfigClass(GitoliteAccessURLGenerator::class);
    }

    public function cssFile($params)
    {
        // Only show the stylesheet if we're actually in the Git pages.
        // This stops styles inadvertently clashing with the main site.
        if (
            strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0 ||
            strpos($_SERVER['REQUEST_URI'], '/widgets/') === 0
        ) {
            echo '<link rel="stylesheet" type="text/css" href="' . $this->getLegacyAssets()->getFileURL('default.css') . '" />';
        }
    }

    public function jsFile($params)
    {
        // Only show the javascript if we're actually in the Git pages.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            $layout = $params['layout'];
            assert($layout instanceof \Tuleap\Layout\BaseLayout);
            $layout->addJavascriptAsset(new \Tuleap\Layout\JavascriptAsset($this->getLegacyAssets(), 'git.js'));
        }
    }

    public function permissionPerGroupDisplayEvent(PermissionPerGroupDisplayEvent $event): void
    {
        $event->addJavascript(
            new \Tuleap\Layout\JavascriptViteAsset(
                new \Tuleap\Layout\IncludeViteAssets(
                    __DIR__ . '/../scripts/permissions-per-group/frontend-assets',
                    '/assets/git/permissions-per-group'
                ),
                'src/index.ts'
            )
        );
    }

    public function javascript($params)
    {
        include $GLOBALS['Language']->getContent('script_locale', null, 'git');
    }

    public function system_event_get_types_for_default_queue(array &$params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['types'] = array_merge($params['types'], $this->getGitSystemEventManager()->getTypesForDefaultQueue());
    }

    public function getSystemEventQueuesEvent(GetSystemEventQueuesEvent $event): void
    {
        $event->addAvailableQueue(
            Git_SystemEventQueue::NAME,
            new Git_SystemEventQueue($this->getLogger())
        );
    }

    /** @see Event::SYSTEM_EVENT_GET_TYPES_FOR_CUSTOM_QUEUE */
    public function system_event_get_types_for_custom_queue(array &$params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($params['queue'] == Git_SystemEventQueue::NAME) {
            $params['types'] = array_merge(
                $params['types'],
                $this->getGitSystemEventManager()->getTypes()
            );
        }
    }

    /**
     *This callback make SystemEvent manager knows about git plugin System Events
     */
    public function getSystemEventClass($params)
    {
        switch ($params['type']) {
            case SystemEvent_GIT_REPO_UPDATE::NAME:
                $params['class']        = 'SystemEvent_GIT_REPO_UPDATE';
                $params['dependencies'] = [
                    $this->getRepositoryFactory(),
                    new DefaultBranchUpdateExecutorAsGitoliteUser(),
                ];
                break;
            case SystemEvent_GIT_REPO_DELETE::NAME:
                $params['class']        = 'SystemEvent_GIT_REPO_DELETE';
                $params['dependencies'] = [
                    $this->getRepositoryFactory(),
                    $this->getLogger(),
                    $this->getUgroupsToNotifyDao(),
                    $this->getUsersToNotifyDao(),
                    EventManager::instance(),
                ];
                break;
            case SystemEvent_GIT_GERRIT_MIGRATION::NAME:
                $params['class']        = 'SystemEvent_GIT_GERRIT_MIGRATION';
                $params['dependencies'] = [
                    $this->getGitDao(),
                    $this->getRepositoryFactory(),
                    $this->getGerritServerFactory(),
                    $this->getLogger(),
                    $this->getProjectCreator(),
                    $this->getGitRepositoryUrlManager(),
                    UserManager::instance(),
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
                    ),
                ];
                break;
            case SystemEvent_GIT_REPO_FORK::NAME:
                $params['class']        = 'SystemEvent_GIT_REPO_FORK';
                $params['dependencies'] = [
                    $this->getRepositoryFactory(),
                ];
                break;
            case SystemEvent_GIT_GERRIT_ADMIN_KEY_DUMP::NAME:
                $params['class']        = 'SystemEvent_GIT_GERRIT_ADMIN_KEY_DUMP';
                $params['dependencies'] = [
                    $this->getGerritServerFactory(),
                    $this->getSSHKeyDumper(),
                ];
                break;
            case SystemEvent_GIT_GERRIT_PROJECT_DELETE::NAME:
                $params['class']        = 'SystemEvent_GIT_GERRIT_PROJECT_DELETE';
                $params['dependencies'] = [
                    $this->getRepositoryFactory(),
                    $this->getGerritServerFactory(),
                    $this->getGerritDriverFactory(),
                ];
                break;
            case SystemEvent_GIT_GERRIT_PROJECT_READONLY::NAME:
                $params['class']        = 'SystemEvent_GIT_GERRIT_PROJECT_READONLY';
                $params['dependencies'] = [
                    $this->getRepositoryFactory(),
                    $this->getGerritServerFactory(),
                    $this->getGerritDriverFactory(),
                ];
                break;
            case SystemEvent_GIT_USER_RENAME::NAME:
                $params['class']        = 'SystemEvent_GIT_USER_RENAME';
                $params['dependencies'] = [
                    $this->getSSHKeyDumper(),
                    UserManager::instance(),
                ];
                break;
            case SystemEvent_GIT_EDIT_SSH_KEYS::NAME:
                $params['class']        = 'SystemEvent_GIT_EDIT_SSH_KEYS';
                $params['dependencies'] = [
                    UserManager::instance(),
                    $this->getSSHKeyDumper(),
                    $this->getUserAccountManager(),
                    $this->getLogger(),
                ];
                break;
            case SystemEvent_GIT_DUMP_ALL_SSH_KEYS::NAME:
                $params['class']        = 'SystemEvent_GIT_DUMP_ALL_SSH_KEYS';
                $params['dependencies'] = [
                    $this->getSSHKeyMassDumper(),
                    $this->getLogger(),
                ];
                break;
            case SystemEvent_GIT_REPO_RESTORE::NAME:
                $params['class']        = 'SystemEvent_GIT_REPO_RESTORE';
                $params['dependencies'] = [
                    $this->getRepositoryFactory(),
                ];
                break;
            case SystemEvent_GIT_PROJECTS_UPDATE::NAME:
                $params['class']        = 'SystemEvent_GIT_PROJECTS_UPDATE';
                $params['dependencies'] = [
                    $this->getLogger(),
                    $this->getProjectManager(),
                    $this->getGitoliteDriver(),
                ];
                break;
            case SystemEvent_GIT_REGENERATE_GITOLITE_CONFIG::NAME:
                $params['class']        = 'SystemEvent_GIT_REGENERATE_GITOLITE_CONFIG';
                $params['dependencies'] = [
                    $this->getGitoliteDriver(),
                    $this->getProjectManager(),
                ];
                break;
            case ProjectIsSuspended::NAME:
                $params['class']        = ProjectIsSuspended::class;
                $params['dependencies'] = [
                    $this->getGitoliteDriver(),
                    $this->getProjectManager(),
                ];
                break;
            case ParseGitolite3Logs::NAME:
                $params['class']        = '\\Tuleap\\Git\\SystemEvents\\ParseGitolite3Logs';
                $params['dependencies'] = [
                    $this->getGitolite3Parser(),
                ];
                break;
            default:
                break;
        }
    }

    private function getTemplateFactory()
    {
        return new Git_Driver_Gerrit_Template_TemplateFactory(new Git_Driver_Gerrit_Template_TemplateDao());
    }

    public function getReferenceKeywords($params)
    {
        $params['keywords'][] = Git::REFERENCE_KEYWORD;
        $params['keywords'][] = Git::TAG_REFERENCE_KEYWORD;
    }

    public function getAvailableReferenceNatures(NatureCollection $natures): void
    {
        $natures->addNature(
            Git::REFERENCE_NATURE,
            new Nature(
                Git::REFERENCE_KEYWORD,
                'fas fa-tlp-versioning-git',
                dgettext('tuleap-git', 'Git commit'),
                true
            )
        );
        $natures->addNature(
            Git::TAG_REFERENCE_NATURE,
            new Nature(
                Git::TAG_REFERENCE_KEYWORD,
                'fas fa-tlp-versioning-git',
                dgettext('tuleap-git', 'Git tag'),
                true
            )
        );
    }

    public function getReference(GetReferenceEvent $event): void
    {
        $reference             = null;
        $git_reference_manager = new Git_ReferenceManager(
            $this->getRepositoryFactory(),
            $event->getReferenceManager(),
            new ReferenceDao()
        );

        if ($event->getKeyword() == Git::REFERENCE_KEYWORD) {
            if ($event->getProject()) {
                $reference = $git_reference_manager->getCommitReference(
                    $event->getProject(),
                    $event->getKeyword(),
                    $event->getValue()
                );
            }
        } elseif ($event->getKeyword() == Git::TAG_REFERENCE_KEYWORD) {
            if ($event->getProject()) {
                $reference = $git_reference_manager->getTagReference(
                    $event->getProject(),
                    $event->getKeyword(),
                    $event->getValue()
                );
            }
        }

        if ($reference !== null) {
            $event->setReference($reference);
        }
    }

    public function referenceAdministrationWarningsCollectorEvent(
        ReferenceAdministrationWarningsCollectorEvent $event,
    ): void {
        (new ReferenceAdministrationWarningsCollectorEventHandler())
            ->handle($event);
    }

    public function changeProjectRepositoriesAccess($params)
    {
        $groupId   = $params[0];
        $isPrivate = $params[1];
        $dao       = new GitDao();
        $factory   = $this->getRepositoryFactory();
        GitActions::changeProjectRepositoriesAccess($groupId, $isPrivate, $dao, $factory);
    }

    public function systemEventProjectRename($params)
    {
        $project_renamer = new GitProjectRenamer($this->getBackendGitolite(), $this->getGitDao());
        $project_renamer->renameProject($params['project'], $params['new_name']);
    }

    public function file_exists_in_data_dir($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['result'] = $this->isNameAvailable($params['new_name'], $params['error']);
    }

    private function isNameAvailable($newName, &$error)
    {
        $backend_gitolite = $this->getBackendGitolite();

        if (! $backend_gitolite->isNameAvailable($newName)) {
            $error = dgettext('tuleap-git', 'A file already exists with this name under gitroot');
            return false;
        }

        return true;
    }

    public function getBackendGitolite()
    {
        return new Git_Backend_Gitolite(
            $this->getGitoliteDriver(),
            new GitoliteAccessURLGenerator($this->getPluginInfo()),
            new DefaultBranchUpdateExecutorAsGitoliteUser(),
            $this->getLogger()
        );
    }

    protected function getChainOfRouters()
    {
        $repository_retriever = new RepositoryFromRequestRetriever(
            $this->getRepositoryFactory(),
            $this->getGitPermissionsManager()
        );

        $webhook_router = $this->getWebhookRouter($repository_retriever);
        $final_link     = new GitGodObjectWrapper($this->getGitController());

        $webhook_router
            ->chain($this->getCreateRepositoryController())
            ->chain($this->getCITokenRouter($repository_retriever))
            ->chain($this->getPermissionsPerGroupController())
            ->chain($this->getDefaultSettingsRouter())
            ->chain($final_link);

        return $webhook_router;
    }

    private function getDefaultSettingsRouter()
    {
        return new DefaultSettingsRouter(
            new IndexController(
                $this->getAccessRightsPresenterOptionsBuilder(),
                $this->getGitPermissionsManager(),
                $this->getFineGrainedRetriever(),
                $this->getDefaultFineGrainedPermissionFactory(),
                $this->getFineGrainedRepresentationBuilder(),
                $this->getRegexpFineGrainedRetriever(),
                $this->getHeaderRenderer(),
                EventManager::instance()
            )
        );
    }

    private function getCreateRepositoryController()
    {
        return new CreateRepositoryController(
            $this->getGitRepositoryUrlManager(),
            $this->getRepositoryCreator()
        );
    }

    private function getCITokenRouter($repository_retriever)
    {
        return new CITokenRouter(
            new CITokenController(
                $repository_retriever,
                $this->getCITokenManager(),
                new BuildStatusChangePermissionManager(
                    new BuildStatusChangePermissionDAO()
                )
            )
        );
    }

    private function getWebhookRouter($repository_retriever)
    {
        $dao = new WebhookDao();

        return new WebhookRouter(
            new WebhookAddController($repository_retriever, $dao),
            new WebhookEditController($repository_retriever, $dao),
            new WebhookDeleteController($repository_retriever, $dao)
        );
    }

    public function getAdminRouter()
    {
        $project_manager             = ProjectManager::instance();
        $gerrit_ressource_restrictor = new GerritServerResourceRestrictor(new RestrictedGerritServerDao());

        return new Git_AdminRouter(
            $this->getGerritServerFactory(),
            new CSRFSynchronizerToken(GIT_SITE_ADMIN_BASE_URL),
            $project_manager,
            $this->getGitSystemEventManager(),
            $this->getRegexpFineGrainedRetriever(),
            $this->getRegexpFineGrainedEnabler(),
            $this->getAdminPageRenderer(),
            $this->getRegexpFineGrainedDisabler(),
            $gerrit_ressource_restrictor,
            new Restrictor(
                $this->getGerritServerFactory(),
                $gerrit_ressource_restrictor,
                $project_manager
            ),
            $this->getBigObjectAuthorizationManager(),
            new \Tuleap\Layout\IncludeViteAssets(
                __DIR__ . '/../scripts/siteadmin/frontend-assets/',
                '/assets/git/siteadmin'
            ),
        );
    }

    private function getRegexpFineGrainedEnabler()
    {
        return new RegexpFineGrainedEnabler(
            $this->getRegexpFineGrainedDao(),
            $this->getRegexpRepositoryDao(),
            $this->getRegexpTemplateDao()
        );
    }

    public function getRegexpFineGrainedRetriever()
    {
        return new RegexpFineGrainedRetriever(
            $this->getRegexpFineGrainedDao(),
            $this->getRegexpRepositoryDao(),
            $this->getRegexpTemplateDao()
        );
    }

    private function getRegexpTemplateDao()
    {
        return new RegexpTemplateDao();
    }

    private function getRegexpFineGrainedDao()
    {
        return new RegexpFineGrainedDao();
    }

    private function getRegexpRepositoryDao()
    {
        return new RegexpRepositoryDao();
    }

    private function getAdminPageRenderer()
    {
        return new AdminPageRenderer();
    }

    /**
     * Hook to collect Git disk size usage per project
     *
     * @param array $params
     */
    public function plugin_statistics_disk_usage_collect_project($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $start          = microtime(true);
        $row            = $params['project_row'];
        $disk_usage_dao = $params['DiskUsageManager']->_getDao();
        $retriever      = new Retriever($disk_usage_dao);
        $collector      = new Collector($params['DiskUsageManager'], $this->getGitLogDao(), $retriever);
        $project        = $params['project'];

        $disk_usage_dao->addGroup(
            $row['group_id'],
            self::SERVICE_SHORTNAME,
            $collector->collectForGitoliteRepositories($project),
            $params['collect_date']->getTimestamp()
        );

        $end  = microtime(true);
        $time = $end - $start;

        if (! isset($params['time_to_collect'][self::SERVICE_SHORTNAME])) {
            $params['time_to_collect'][self::SERVICE_SHORTNAME] = 0;
        }

        $params['time_to_collect'][self::SERVICE_SHORTNAME] += $time;
    }

    /**
     * Hook to list docman in the list of serices managed by disk stats
     *
     * @param array $params
     */
    public function plugin_statistics_disk_usage_service_label($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['services'][self::SERVICE_SHORTNAME] = 'Git';
    }

    /**
     * Hook to choose the color of the plugin in the graph
     *
     * @param array $params
     */
    public function plugin_statistics_color($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($params['service'] == self::SERVICE_SHORTNAME) {
            $params['color'] = 'palegreen';
        }
    }

    /**
     * Function called when a user is removed from a project
     * If a user is removed from a project wich having a private git repository, the
     * user should be removed from notification.
     *
     * @param array $params
     *
     * @return void
     */
    private function projectRemoveUserFromNotification($params)
    {
        $project_id = $params['group_id'];
        $user_id    = $params['user_id'];

        $user_manager = UserManager::instance();

        $user    = $user_manager->getUserById($user_id);
        $project = $this->getProjectManager()->getProject($project_id);

        $cleaner = new NotificationsForProjectMemberCleaner(
            $this->getRepositoryFactory(),
            new Git_PostReceiveMailManager(),
            $this->getUsersToNotifyDao()
        );
        $cleaner->cleanNotificationsAfterUserRemoval($project, $user);
    }

    /**
     *
     * @see Event::EDIT_SSH_KEYS
     * @param array $params
     */
    public function edit_ssh_keys(array $params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $this->getGitSystemEventManager()->queueEditSSHKey($params['user_id'], $params['original_keys']);
    }

    /**
     * Hook. Call by backend when SSH keys are modified
     *
     * @param array $params Should contain two entries:
     *     'user' => PFUser,
     *     'original_keys' => string of concatenated ssh keys
     */
    public function dump_ssh_keys(array $params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $this->getGitSystemEventManager()->queueDumpAllSSHKeys();
    }

    /**
     * @return Git_UserAccountManager
     */
    private function getUserAccountManager()
    {
        if (! $this->user_account_manager) {
            $this->user_account_manager = new Git_UserAccountManager(
                $this->getGerritDriverFactory(),
                $this->getGerritServerFactory()
            );
        }

        return $this->user_account_manager;
    }

    public function setUserAccountManager(Git_UserAccountManager $manager)
    {
        $this->user_account_manager = $manager;
    }

    public function permission_get_name($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (! $params['name']) {
            switch ($params['permission_type']) {
                case 'PLUGIN_GIT_READ':
                    $params['name'] = dgettext('tuleap-git', 'Read');
                    break;
                case 'PLUGIN_GIT_WRITE':
                    $params['name'] = dgettext('tuleap-git', 'Write');
                    break;
                case 'PLUGIN_GIT_WPLUS':
                    $params['name'] = dgettext('tuleap-git', 'Rewind');
                    break;
                default:
                    break;
            }
        }
    }

    public function permission_get_object_type($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (! $params['object_type']) {
            if (in_array($params['permission_type'], ['PLUGIN_GIT_READ', 'PLUGIN_GIT_WRITE', 'PLUGIN_GIT_WPLUS'])) {
                $params['object_type'] = 'git_repository';
            }
        }
    }

    public function permission_get_object_name($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (! $params['object_name']) {
            if (in_array($params['permission_type'], ['PLUGIN_GIT_READ', 'PLUGIN_GIT_WRITE', 'PLUGIN_GIT_WPLUS'])) {
                $repository = new GitRepository();
                $repository->setId($params['object_id']);
                try {
                    $repository->load();
                    $params['object_name'] = $repository->getName();
                } catch (Exception $e) {
                    // do nothing
                }
            }
        }
    }

    public $_cached_permission_user_allowed_to_change; //phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore
    public function permission_user_allowed_to_change($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (! $params['allowed']) {
            $user    = $this->getCurrentUser();
            $project = $this->getProjectManager()->getProject($params['group_id']);

            if ($this->getGitPermissionsManager()->userIsGitAdmin($user, $project)) {
                $this->_cached_permission_user_allowed_to_change = true;
            }

            if (! $this->_cached_permission_user_allowed_to_change) {
                if (in_array($params['permission_type'], ['PLUGIN_GIT_READ', 'PLUGIN_GIT_WRITE', 'PLUGIN_GIT_WPLUS'])) {
                    $repository = new GitRepository();
                    $repository->setId($params['object_id']);
                    try {
                        $repository->load();
                        //Only project admin can update perms of project repositories
                        //Only repo owner can update perms of personal repositories
                        $this->_cached_permission_user_allowed_to_change = $repository->belongsTo($user) || $this->getPermissionsManager()->userIsGitAdmin($user, $project);
                    } catch (Exception $e) {
                        // do nothing
                    }
                }
            }
            $params['allowed'] = $this->_cached_permission_user_allowed_to_change;
        }
    }

    public function proccess_system_check($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $gitgc           = new Git_GitoliteHousekeeping_GitoliteHousekeepingGitGc(
            new Git_GitoliteHousekeeping_GitoliteHousekeepingDao(),
            $params['logger'],
            $this->getGitoliteAdminPath()
        );
        $gitolite_driver = $this->getGitoliteDriver();

        $system_check = new Git_SystemCheck(
            $gitgc,
            $gitolite_driver,
            new PluginConfigChecker($params['logger']),
            $this
        );

        $system_check->process();
    }

    public function getGitoliteDriver()
    {
        return new Git_GitoliteDriver(
            $this->getLogger(),
            $this->getGitRepositoryUrlManager(),
            $this->getGitDao(),
            $this,
            $this->getBigObjectAuthorizationManager(),
            null,
            null,
            null,
            null,
            $this->getProjectManager(),
        );
    }

    public function projectStatusUpdate(ProjectStatusUpdate $event): void
    {
        match ($event->status) {
            Project::STATUS_ACTIVE    => $this->getGitSystemEventManager()->queueRegenerateGitoliteConfig($event->project->getID()),
            Project::STATUS_SUSPENDED => $this->getGitSystemEventManager()->queueProjectIsSuspended($event->project->getID()),
            Project::STATUS_DELETED   => $this->getRepositoryManager()->deleteProjectRepositories($event->project),
        };
    }

    /**
     * Display git backend statistics in CSV format
     *
     * @param Array $params parameters of the event
     *
     * @return void
     */
    public function statistics_collector($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (! empty($params['formatter'])) {
            $formatter = $params['formatter'];
            echo $this->getBackendGitolite()->getBackendStatistics($formatter);
        }
    }

    /**
     * Add ci trigger information for Git service
     *
     * @param Array $params Hook parms
     *
     * @return Void
     */
    public function collect_ci_triggers($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $ci       = new Git_Ci();
        $triggers = $ci->retrieveTriggers($params);
        if ($triggers) {
            $params['services'][] = $triggers;
        }
    }

    /**
     * Save ci trigger for Git service
     *
     * @param Array $params Hook parms
     *
     * @return Void
     */
    public function save_ci_triggers($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (isset($params['job_id']) && ! empty($params['job_id']) && isset($params['request']) && ! empty($params['request'])) {
            if ($params['request']->get('hudson_use_plugin_git_trigger_checkbox')) {
                $repositoryId = $params['request']->get('hudson_use_plugin_git_trigger');
                if ($repositoryId) {
                    $vRepoId = new Valid_UInt('hudson_use_plugin_git_trigger');
                    $vRepoId->required();
                    if ($params['request']->valid($vRepoId)) {
                        $ci = new Git_Ci();
                        if (! $ci->saveTrigger($params['job_id'], $repositoryId)) {
                            $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-git', 'Git trigger not saved'));
                        }
                    } else {
                        $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-git', 'Bad repository id'));
                    }
                }
            }
        }
    }

    /**
     * Update ci trigger for Git service
     *
     * @param Array $params Hook parms
     *
     * @return Void
     */
    public function update_ci_triggers($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (isset($params['request']) && ! empty($params['request'])) {
            $jobId        = $params['request']->get('job_id');
            $repositoryId = $params['request']->get('hudson_use_plugin_git_trigger');
            if ($jobId) {
                $vJobId = new Valid_UInt('job_id');
                $vJobId->required();
                if ($params['request']->valid($vJobId)) {
                    $ci      = new Git_Ci();
                    $vRepoId = new Valid_UInt('hudson_use_plugin_git_trigger');
                    $vRepoId->required();
                    if ($params['request']->valid($vRepoId)) {
                        if (! $ci->saveTrigger($jobId, $repositoryId)) {
                            $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-git', 'Git trigger not saved'));
                        }
                    } else {
                        if (! $ci->deleteTrigger($jobId)) {
                            $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-git', 'Git trigger not deleted'));
                        }
                    }
                } else {
                    $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-git', 'Bad repository id'));
                }
            }
        }
    }

    /**
     * Delete ci trigger for Git service
     *
     * @param Array $params Hook parms
     *
     * @return Void
     */
    public function delete_ci_triggers($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (isset($params['job_id']) && ! empty($params['job_id'])) {
            $ci = new Git_Ci();
            if (! $ci->deleteTrigger($params['job_id'])) {
                $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-git', 'Git trigger not deleted'));
            }
        }
    }

    /**
     * Add log access for git pushs
     *
     * @param Array $params parameters of the event
     *
     * @return Void
     */
    public function logsDaily($params)
    {
        $pm      = ProjectManager::instance();
        $project = $pm->getProject($params['group_id']);
        if ($project->usesService(self::SERVICE_SHORTNAME)) {
            $controler = $this->getGitController();
            $controler->logsDaily($params);
        }
    }

    /**
     * Instanciate the corresponding widget
     *
     * @param \Tuleap\Widget\Event\GetWidget $get_widget_event Name and instance of the widget
     *
     * @return Void
     */
    public function widgetInstance(\Tuleap\Widget\Event\GetWidget $get_widget_event)
    {
        switch ($get_widget_event->getName()) {
            case 'plugin_git_user_pushes':
                $get_widget_event->setWidget(new Git_Widget_UserPushes($this->getPluginPath()));
                break;
            case 'plugin_git_project_pushes':
                $get_widget_event->setWidget(new Git_Widget_ProjectPushes($this->getPluginPath()));
                break;
            default:
                break;
        }
    }

    public function project_admin_remove_user_from_project_ugroups($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        foreach ($params['ugroups'] as $ugroup_id) {
            $this->project_admin_ugroup_remove_user(
                [
                    'group_id'  => $params['group_id'],
                    'user_id'   => $params['user_id'],
                    'ugroup_id' => $ugroup_id,
                ]
            );
        }
    }

    public function userIsNoLongerProjectAdmin(UserIsNoLongerProjectAdmin $event)
    {
        $this->project_admin_ugroup_remove_user(
            [
                'user_id'   => $event->getUser()->getId(),
                'group_id'  => $event->getProject()->getID(),
                'ugroup_id' => ProjectUGroup::PROJECT_ADMIN,
            ]
        );
    }

    public function userBecomesProjectAdmin(UserBecomesProjectAdmin $event)
    {
        $this->project_admin_ugroup_add_user(
            [
                'user_id'   => $event->getUser()->getId(),
                'group_id'  => $event->getProject()->getID(),
                'ugroup_id' => ProjectUGroup::PROJECT_ADMIN,
            ]
        );
    }

    public function project_admin_ugroup_deletion($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $ugroup     = $params['ugroup'];
        $users      = $ugroup->getMembers();
        $project_id = $params['group_id'];

        foreach ($users as $user) {
            $calling = [
                'group_id' => $project_id,
                'user_id'  => $user->getId(),
                'ugroup'   => $ugroup,
            ];
            $this->project_admin_ugroup_remove_user($calling);
        }

        $this->getFineGrainedUpdater()->deleteUgroupPermissions($ugroup, $project_id);
        $this->getUgroupsToNotifyDao()->deleteByUgroupId($project_id, $ugroup->getId());

        $this->getGitSystemEventManager()->queueProjectsConfigurationUpdate([$project_id]);
    }

    public function project_admin_add_user($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['ugroup_id'] = ProjectUGroup::PROJECT_MEMBERS;
        $this->project_admin_ugroup_add_user($params);
    }

    public function project_admin_remove_user($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['ugroup_id'] = ProjectUGroup::PROJECT_MEMBERS;
        $this->project_admin_ugroup_remove_user($params);
        $this->projectRemoveUserFromNotification($params);
    }

    public function project_admin_ugroup_add_user($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $this->getGerritMembershipManager()->addUserToGroup(
            $this->getUserFromParams($params),
            $this->getUGroupFromParams($params)
        );
    }

    public function project_admin_ugroup_remove_user($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $this->getGerritMembershipManager()->removeUserFromGroup(
            $this->getUserFromParams($params),
            $this->getUGroupFromParams($params)
        );
    }

    public function project_admin_ugroup_creation($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $this->getGerritMembershipManager()->createGroupOnProjectsServers(
            $this->getUGroupFromParams($params)
        );
    }

    public function project_admin_parent_project_modification($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        try {
            $project        = ProjectManager::instance()->getProject($params['group_id']);
            $gerrit_servers = $this->getGerritServerFactory()->getServersForProject($project);

            $this->getGerritUmbrellaProjectManager()->recursivelyCreateUmbrellaProjects($gerrit_servers, $project);
        } catch (Git_Driver_Gerrit_Exception $exception) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, sprintf(dgettext('tuleap-git', 'An error occured while trying to access Gerrit server, maybe the server is down, please check with administrators: %1$s'), $exception->getMessage()));
        } catch (Exception $exception) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $exception->getMessage());
        }
    }

    public function ugroup_manager_update_ugroup_binding_add($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $this->getGerritMembershipManager()->addUGroupBinding(
            $params['ugroup'],
            $params['source']
        );
    }

    public function ugroup_manager_update_ugroup_binding_remove($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $this->getGerritMembershipManager()->removeUGroupBinding(
            $params['ugroup']
        );
    }

    private function getUserFromParams(array $params)
    {
        return UserManager::instance()->getUserById($params['user_id']);
    }

    private function getUGroupFromParams(array $params)
    {
        if (isset($params['ugroup'])) {
            return $params['ugroup'];
        } else {
            $project = ProjectManager::instance()->getProject($params['group_id']);
            return $this->getUGroupManager()->getUGroup($project, $params['ugroup_id']);
        }
    }

    public function getUserWidgetList(\Tuleap\Widget\Event\GetUserWidgetList $event)
    {
        $event->addWidget('plugin_git_user_pushes');
    }

    public function getProjectWidgetList(\Tuleap\Widget\Event\GetProjectWidgetList $event)
    {
        $request = HTTPRequest::instance();
        $groupId = $request->get('group_id');
        $pm      = ProjectManager::instance();
        $project = $pm->getProject($groupId);
        if ($project->usesService(self::SERVICE_SHORTNAME)) {
            $event->addWidget('plugin_git_project_pushes');
        }
    }

    public function uninstall()
    {
        $this->removeOrphanWidgets(['plugin_git_user_pushes', 'plugin_git_project_pushes']);
    }

    private function getProjectCreator()
    {
        $tmp_dir = ForgeConfig::get('tmp_dir') . '/gerrit_' . bin2hex(random_bytes(7));
        return new Git_Driver_Gerrit_ProjectCreator(
            $tmp_dir,
            $this->getGerritDriverFactory(),
            $this->getGerritUserFinder(),
            $this->getUGroupManager(),
            $this->getGerritMembershipManager(),
            $this->getGerritUmbrellaProjectManager(),
            $this->getTemplateFactory(),
            $this->getTemplateProcessor(),
            new Git_Exec($tmp_dir)
        );
    }

    private function getTemplateProcessor()
    {
        return new Git_Driver_Gerrit_Template_TemplateProcessor();
    }

    private function getGerritUmbrellaProjectManager()
    {
        return new Git_Driver_Gerrit_UmbrellaProjectManager(
            $this->getUGroupManager(),
            $this->getProjectManager(),
            $this->getGerritMembershipManager(),
            $this->getGerritDriverFactory()
        );
    }

    private function getProjectManager()
    {
        return ProjectManager::instance();
    }

    private function getGerritUserFinder()
    {
        return new Git_Driver_Gerrit_UserFinder(PermissionsManager::instance());
    }

    private function getProjectCreatorStatus()
    {
        $dao = new Git_Driver_Gerrit_ProjectCreatorStatusDao();

        return new Git_Driver_Gerrit_ProjectCreatorStatus($dao);
    }

    private function getDescriptionUpdater()
    {
        return new DescriptionUpdater(new ProjectHistoryDao());
    }

    private function getGitController()
    {
        $gerrit_server_factory = $this->getGerritServerFactory();
        $git_dao               = $this->getGitDao();

        return new Git(
            $this,
            $this->getGerritServerFactory(),
            $this->getGerritDriverFactory(),
            $this->getRepositoryManager(),
            $this->getGitSystemEventManager(),
            new Git_Driver_Gerrit_UserAccountManager($this->getGerritDriverFactory(), $gerrit_server_factory),
            $this->getRepositoryFactory(),
            UserManager::instance(),
            ProjectManager::instance(),
            HTTPRequest::instance(),
            $this->getProjectCreator(),
            new Git_Driver_Gerrit_Template_TemplateFactory(new Git_Driver_Gerrit_Template_TemplateDao()),
            $this->getGitPermissionsManager(),
            $this->getGitRepositoryUrlManager(),
            $this->getLogger(),
            $this->getProjectCreatorStatus(),
            new GerritCanMigrateChecker(EventManager::instance(), $gerrit_server_factory),
            $this->getFineGrainedUpdater(),
            $this->getFineGrainedFactory(),
            $this->getFineGrainedRetriever(),
            $this->getFineGrainedPermissionSaver(),
            $this->getDefaultFineGrainedPermissionFactory(),
            $this->getFineGrainedPermissionDestructor(),
            $this->getFineGrainedRepresentationBuilder(),
            $this->getHistoryValueFormatter(),
            $this->getPermissionChangesDetector(),
            $this->getTemplatePermissionsUpdater(),
            new ProjectHistoryDao(),
            new DefaultBranchUpdater(new DefaultBranchUpdateExecutorAsGitoliteUser()),
            $this->getDescriptionUpdater(),
            $this->getRegexpFineGrainedRetriever(),
            $this->getRegexpFineGrainedEnabler(),
            $this->getRegexpFineGrainedDisabler(),
            $this->getRegexpPermissionFilter(),
            new UsersToNotifyDao(),
            $this->getUgroupsToNotifyDao(),
            new UGroupManager(),
            $this->getHeaderRenderer(),
            $git_dao,
            $git_dao,
        );
    }

    /**
     * @return UgroupsToNotifyDao
     */
    private function getUgroupsToNotifyDao()
    {
        return new UgroupsToNotifyDao();
    }

    /**
     * @return UsersToNotifyDao
     */
    private function getUsersToNotifyDao()
    {
        return new UsersToNotifyDao();
    }

    private function getRegexpPermissionFilter()
    {
        return new RegexpPermissionFilter(
            $this->getFineGrainedFactory(),
            $this->getPatternValidator(),
            $this->getFineGrainedPermissionDestructor(),
            $this->getDefaultFineGrainedPermissionFactory()
        );
    }

    private function getRegexpFineGrainedDisabler()
    {
        return new RegexpFineGrainedDisabler(
            new RegexpRepositoryDao(),
            new RegexpFineGrainedDao(),
            new RegexpTemplateDao()
        );
    }

    private function getTemplatePermissionsUpdater()
    {
        return new TemplatePermissionsUpdater(
            $this->getPermissionsManager(),
            new ProjectHistoryDao(),
            $this->getHistoryValueFormatter(),
            $this->getFineGrainedRetriever(),
            $this->getDefaultFineGrainedPermissionFactory(),
            $this->getFineGrainedUpdater(),
            $this->getTemplateFineGrainedPermissionSaver(),
            $this->getPermissionChangesDetector(),
            $this->getRegexpFineGrainedEnabler(),
            $this->getRegexpFineGrainedRetriever(),
            $this->getRegexpPermissionFilter(),
            $this->getRegexpFineGrainedDisabler()
        );
    }

    private function getPermissionChangesDetector()
    {
        return new PermissionChangesDetector(
            $this->getGitPermissionsManager(),
            $this->getFineGrainedRetriever()
        );
    }

    private function getFineGrainedPermissionReplicator()
    {
        $dao             = $this->getFineGrainedDao();
        $default_factory = $this->getDefaultFineGrainedPermissionFactory();
        $saver           = $this->getFineGrainedPermissionSaver();
        $factory         = $this->getFineGrainedFactory();

        return new FineGrainedPermissionReplicator(
            $dao,
            $default_factory,
            $saver,
            $factory,
            $this->getRegexpFineGrainedEnabler(),
            $this->getRegexpFineGrainedRetriever(),
            $this->getPatternValidator()
        );
    }

    private function getFineGrainedRepresentationBuilder()
    {
        $option_builder = $this->getAccessRightsPresenterOptionsBuilder();

        return new FineGrainedRepresentationBuilder($option_builder);
    }

    private function getFineGrainedDao()
    {
        return new FineGrainedDao();
    }

    private function getFineGrainedPermissionDestructor()
    {
        $dao = $this->getFineGrainedDao();
        return new FineGrainedPermissionDestructor($dao);
    }

    private function getTemplateFineGrainedPermissionSaver()
    {
        $dao = $this->getFineGrainedDao();
        return new TemplateFineGrainedPermissionSaver($dao);
    }

    private function getDefaultFineGrainedPermissionFactory()
    {
        $dao = $this->getFineGrainedDao();
        return new DefaultFineGrainedPermissionFactory(
            $dao,
            $this->getUGroupManager(),
            new PermissionsNormalizer(),
            $this->getPermissionsManager(),
            $this->getPatternValidator(),
            $this->getFineGrainedPermissionSorter(),
            $this->getRegexpFineGrainedRetriever()
        );
    }

    private function getFineGrainedPermissionSorter()
    {
        return new FineGrainedPermissionSorter();
    }

    /**
     * @return FineGrainedPermissionSaver
     */
    private function getFineGrainedPermissionSaver()
    {
        $dao = $this->getFineGrainedDao();
        return new FineGrainedPermissionSaver($dao);
    }

    /**
     * @return FineGrainedUpdater
     */
    private function getFineGrainedUpdater()
    {
        $dao = $this->getFineGrainedDao();
        return new FineGrainedUpdater($dao);
    }

    /**
     * @return FineGrainedRetriever
     */
    public function getFineGrainedRetriever()
    {
        $dao = $this->getFineGrainedDao();
        return new FineGrainedRetriever($dao);
    }

    /**
     * @return FineGrainedPermissionFactory
     */
    public function getFineGrainedFactory()
    {
        $dao = $this->getFineGrainedDao();
        return new FineGrainedPermissionFactory(
            $dao,
            $this->getUGroupManager(),
            new PermissionsNormalizer(),
            $this->getPermissionsManager(),
            $this->getPatternValidator(),
            $this->getFineGrainedPermissionSorter(),
            new XmlUgroupRetriever(
                $this->getLogger(),
                $this->getUGroupManager()
            )
        );
    }

    /**
     * @return FineGrainedPermissionFactory
     */
    private function getFineGrainedFactoryWithLogger(\Psr\Log\LoggerInterface $logger)
    {
        $dao = $this->getFineGrainedDao();
        return new FineGrainedPermissionFactory(
            $dao,
            $this->getUGroupManager(),
            new PermissionsNormalizer(),
            $this->getPermissionsManager(),
            $this->getPatternValidator(),
            $this->getFineGrainedPermissionSorter(),
            new XmlUgroupRetriever(
                $logger,
                $this->getUGroupManager()
            )
        );
    }

    private function getPatternValidator()
    {
        return new PatternValidator(
            $this->getFineGrainedPatternValidator(),
            $this->getRegexpPatternValidator(),
            $this->getRegexpFineGrainedRetriever()
        );
    }

    private function getFineGrainedPatternValidator()
    {
        return new FineGrainedPatternValidator();
    }

    private function getRegexpPatternValidator()
    {
        return new FineGrainedRegexpValidator();
    }

    private function getHistoryValueFormatter()
    {
        return new HistoryValueFormatter(
            $this->getPermissionsManager(),
            $this->getUGroupManager(),
            $this->getFineGrainedRetriever(),
            $this->getDefaultFineGrainedPermissionFactory(),
            $this->getFineGrainedFactory()
        );
    }

    public function getGitSystemEventManager()
    {
        return new Git_SystemEventManager(SystemEventManager::instance());
    }

    /**
     * @return GitRepositoryManager
     */
    private function getRepositoryManager()
    {
        return new GitRepositoryManager(
            $this->getRepositoryFactory(),
            $this->getGitSystemEventManager(),
            $this->getGitDao(),
            $this->getConfigurationParameter('git_backup_dir'),
            $this->getFineGrainedPermissionReplicator(),
            new ProjectHistoryDao(),
            $this->getHistoryValueFormatter(),
            EventManager::instance()
        );
    }

    public function getRepositoryFactory(): GitRepositoryFactory
    {
        return new GitRepositoryFactory($this->getGitDao(), ProjectManager::instance());
    }

    /**
     * @protected for testing purpose
     * @return GitDao
     */
    protected function getGitDao()
    {
        return new GitDao();
    }

    /**
     * @return Git_Driver_Gerrit_GerritDriverFactory
     */
    private function getGerritDriverFactory()
    {
        return new Git_Driver_Gerrit_GerritDriverFactory(
            new \Tuleap\Git\Driver\GerritHTTPClientFactory(HttpClientFactory::createClient()),
            \Tuleap\Http\HTTPFactoryBuilder::requestFactory(),
            \Tuleap\Http\HTTPFactoryBuilder::streamFactory(),
            $this->getLogger()
        );
    }

    protected function getPermissionsManager()
    {
        return PermissionsManager::instance();
    }

    protected function getGitPermissionsManager()
    {
        return new GitPermissionsManager(
            new Git_PermissionsDao(),
            $this->getGitSystemEventManager(),
            $this->getFineGrainedDao(),
            $this->getFineGrainedRetriever()
        );
    }

    /**
     *
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        if (! $this->logger) {
            $this->logger = \BackendLogger::getDefaultLogger(self::LOG_IDENTIFIER);
        }

        return $this->logger;
    }

    public function setLogger(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    private function getGerritMembershipManager()
    {
        return new Git_Driver_Gerrit_MembershipManager(
            new Git_Driver_Gerrit_MembershipDao(),
            $this->getGerritDriverFactory(),
            new Git_Driver_Gerrit_UserAccountManager($this->getGerritDriverFactory(), $this->getGerritServerFactory()),
            $this->getGerritServerFactory(),
            $this->getLogger(),
            $this->getUGroupManager(),
            $this->getProjectManager()
        );
    }

    protected function getGerritServerFactory()
    {
        return new Git_RemoteServer_GerritServerFactory(
            new Git_RemoteServer_Dao(),
            $this->getGitDao(),
            $this->getGitSystemEventManager(),
            $this->getProjectManager()
        );
    }

    private function getGitoliteAdminPath()
    {
        return ForgeConfig::get('sys_data_dir') . '/gitolite/admin';
    }

    private function getUGroupManager()
    {
        return new UGroupManager();
    }

    /**
     * @see Event::USER_RENAME
     */
    public function systemevent_user_rename($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $this->getGitSystemEventManager()->queueUserRenameUpdate($params['old_user_name'], $params['user']);
    }

    public function registerProjectCreationEvent(RegisterProjectCreationEvent $event): void
    {
        $template_project     = $event->getTemplateProject();
        $just_created_project = $event->getJustCreatedProject();
        $ugroup_mapping       = $event->getMappingRegistry()->getUgroupMapping();

        $this->getPermissionsManager()->duplicateWithStaticMapping(
            (int) $template_project->getID(),
            (int) $just_created_project->getID(),
            [Git::PERM_ADMIN, Git::DEFAULT_PERM_READ, Git::DEFAULT_PERM_WRITE, Git::DEFAULT_PERM_WPLUS],
            $ugroup_mapping,
        );

        $this->getDefaultFineGrainedPermissionReplicator()->replicate(
            $template_project,
            $just_created_project->getID(),
            $ugroup_mapping,
        );
    }

    private function getDefaultFineGrainedPermissionReplicator()
    {
        return new DefaultFineGrainedPermissionReplicator(
            new FineGrainedDao(),
            $this->getDefaultFineGrainedPermissionFactory(),
            $this->getTemplateFineGrainedPermissionSaver(),
            $this->getRegexpFineGrainedEnabler(),
            $this->getRegexpFineGrainedRetriever(),
            $this->getPatternValidator()
        );
    }

    /**
     * @return bool true if friendly URLs have been activated
     */
    public function areFriendlyUrlsActivated()
    {
        return (bool) $this->getConfigurationParameter('git_use_friendly_urls');
    }

    /**
     * @return Git_GitRepositoryUrlManager
     */
    private function getGitRepositoryUrlManager()
    {
        return new Git_GitRepositoryUrlManager($this);
    }

    /**
     * @return \Tuleap\Git\Gitolite\SSHKey\Dumper
     */
    private function getSSHKeyDumper()
    {
        $factory = $this->getSSHKeyDumperFactory();
        return $factory->buildDumper();
    }

    /**
     * @return \Tuleap\Git\Gitolite\SSHKey\MassDumper
     */
    private function getSSHKeyMassDumper()
    {
        $factory = $this->getSSHKeyDumperFactory();
        return $factory->buildMassDumper();
    }

    /**
     * @return DumperFactory
     */
    private function getSSHKeyDumperFactory()
    {
        $user_manager = UserManager::instance();

        $whole_instance_keys = new WholeInstanceKeysAggregator(
            new GitoliteAdmin(),
            new GerritServer(new Git_RemoteServer_Dao()),
            new User($user_manager)
        );

        $gitolite_admin_path = $this->getGitoliteAdminPath();
        $git_exec            = new Git_Exec($gitolite_admin_path);

        $system_command = new System_Command();

        return new DumperFactory(
            $this->getManagementDetector(),
            new AuthorizedKeysFileCreator($whole_instance_keys, $system_command),
            $system_command,
            $git_exec,
            $gitolite_admin_path,
            $user_manager,
            $this->getLogger()
        );
    }

    /**
     * @return ManagementDetector
     */
    private function getManagementDetector()
    {
        return new ManagementDetector(
            new GlobalParameterDao()
        );
    }

    public function fill_project_history_sub_events($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        array_push(
            $params['subEvents']['event_others'],
            'git_repo_create',
            'git_repo_delete',
            'git_repo_update',
            'git_repo_to_gerrit',
            'git_create_template',
            'git_delete_template',
            'git_disconnect_gerrit_delete',
            'git_disconnect_gerrit_read_only',
            'git_admin_groups',
            'git_fork_repositories'
        );
    }

    /**
     * @see Event::POST_EVENTS_ACTIONS
     */
    public function post_system_events_actions($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (! $this->pluginIsConcerned($params)) {
            return;
        }

        $this->getLogger()->info('Processing git post system events actions');

        $executed_events_ids = $params['executed_events_ids'];

        $this->getGitoliteDriver()->commit('Modifications from events ' . implode(',', $executed_events_ids));
        $this->getGitoliteDriver()->push();
    }

    private function pluginIsConcerned($params)
    {
        return $params['queue_name'] == "git"
            && is_array($params['executed_events_ids'])
            && count($params['executed_events_ids']) > 0;
    }

    /**
     * @see Event::REST_PROJECT_RESOURCES
     */
    public function rest_project_resources(array $params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $injector = new Git_REST_ResourcesInjector();
        $injector->declareProjectPlanningResource($params['resources'], $params['project']);
    }

    /**
     * @see REST_RESOURCES
     */
    public function rest_resources($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $injector = new Git_REST_ResourcesInjector();
        $injector->populate($params['restler']);
    }

    /**
     * @return PFUser
     */
    private function getCurrentUser()
    {
        return UserManager::instance()->getCurrentUser();
    }

    public function pendingDocumentsRetriever(PendingDocumentsRetriever $documents_retriever): void
    {
        $group_id              =  $documents_retriever->getProject()->getID();
        $archived_repositories = $this->getRepositoryManager()->getRepositoriesForRestoreByProjectId($group_id);
        $tab_content           = '';

        $user = $documents_retriever->getUser();

        $tab_content .= '<section class="tlp-pane">
        <div class="tlp-pane-container">
            <div class="tlp-pane-header">
                <h1 class="tlp-pane-title">' . dgettext('tuleap-git', 'Deleted git repositories') . '</h1>
            </div>
            <section class="tlp-pane-section">
                <table class="tlp-table">
                    <thead>
                        <tr>
                            <th>' . dgettext('tuleap-git', 'Repository name') . '</th>
                            <th>' . dgettext('tuleap-git', 'Creation date') . '</th>
                            <th>' . dgettext('tuleap-git', 'Creator') . '</th>
                            <th>' . dgettext('tuleap-git', 'Deletion date') . '</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>';
        if (count($archived_repositories)) {
            $html_purifier = Codendi_HTMLPurifier::instance();
            foreach ($archived_repositories as $archived_repository) {
                $creation_date = new DateTime($archived_repository->getCreationDate());
                $deletion_date = new DateTime($archived_repository->getDeletionDate());
                $tab_content  .= '<tr>';
                $tab_content  .= '<td>' . $html_purifier->purify($archived_repository->getName()) . '</td>';
                $tab_content  .= '<td>' . DateHelper::relativeDateInlineContext((int) $creation_date->getTimestamp(), $user) . '</td>';
                $tab_content  .= '<td>' . $html_purifier->purify($archived_repository->getCreator()->getUserName()) . '</td>';
                $tab_content  .= '<td>' . DateHelper::relativeDateInlineContext((int) $deletion_date->getTimestamp(), $user)  . '</td>';
                $tab_content  .= '<td class="tlp-table-cell-actions">
                                    <form method="post" action="/plugins/git/"
                                    onsubmit="return confirm(\'' . $html_purifier->purify(dgettext('tuleap-git', 'Confirm restore of this Git repository'), CODENDI_PURIFIER_JS_QUOTE) . '\')">
                                        ' . $documents_retriever->getToken()->fetchHTMLInput() . '
                                        <input type="hidden" name="action" value="restore">
                                        <input type="hidden" name="group_id" value="' . $html_purifier->purify($group_id) . '">
                                        <input type="hidden" name="repo_id" value="' . $html_purifier->purify($archived_repository->getId()) . '">
                                        <button class="tlp-table-cell-actions-button tlp-button-small tlp-button-primary tlp-button-outline">
                                            <i class="fas fa-redo tlp-button-icon"></i> ' . $html_purifier->purify(dgettext('tuleap-git', 'Restore')) . '
                                        </button>
                                    </form>
                                 </td>';
                $tab_content  .= '</tr>';
            }
        } else {
            $tab_content .= '<tr>
                <td class="tlp-table-cell-empty" colspan="5">
                    ' . dgettext('tuleap-git', 'No restorable git repositories found') . '
                </td>
            </tr>';
        }
        $tab_content .= '</tbody>
                    </table>
                </section>
            </div>
        </section>';

        $documents_retriever->addPurifiedHTML($tab_content);
    }

    public function restrictedUsersAreHandledByPluginEvent(RestrictedUsersAreHandledByPluginEvent $event)
    {
        if (strpos($event->getUri(), $this->getPluginPath()) === 0) {
            $event->setPluginHandleRestricted();
        }
    }

    #[ListeningToEventClass]
    public function handleServiceAllowedForRestricted(CollectServicesAllowedForRestrictedEvent $event): void
    {
        $event->addServiceShortname($this->getServiceShortname());
    }

    /**
     * @see Event::PROJECT_ACCESS_CHANGE
     */
    public function projectAccessChange(array $params): void
    {
        $project = ProjectManager::instance()->getProject($params['project_id']);

        $this->getGitPermissionsManager()->updateProjectAccess($project, $params['old_access'], $params['access']);

        $updater = $this->getUgroupToNotifyUpdater();
        $updater->updateProjectAccess($project, $params['old_access'], $params['access']);
    }

    /**
     * @see Event::SITE_ACCESS_CHANGE
     * @param array $params
     */
    public function site_access_change(array $params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $this->getGitPermissionsManager()->updateSiteAccess($params['old_value'], $params['new_value']);

        $updater = $this->getUgroupToNotifyUpdater();
        $updater->updateSiteAccess($params['old_value']);
    }

    /**
     * @return UgroupToNotifyUpdater
     */
    private function getUgroupToNotifyUpdater()
    {
        return new UgroupToNotifyUpdater($this->getUgroupsToNotifyDao());
    }

    /**
     * @param PFUser user
     */
    public function ldap_daily_synchro_update_user(PFUser $user)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($user->getStatus() == PFUser::STATUS_SUSPENDED) {
            $factory             = $this->getGerritServerFactory();
            $gerrit_servers      = $factory->getServers();
            $gerritDriverFactory = $this->getGerritDriverFactory();
            foreach ($gerrit_servers as $server) {
                $gerritDriver = $gerritDriverFactory->getDriver($server);
                $gerritDriver->setUserAccountInactive($server, $user);
            }
        }
    }

    /** @see Event::SERVICES_TRUNCATED_EMAILS */
    public function services_truncated_emails(array $params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $project = $params['project'];
        if ($project->usesService($this->getServiceShortname())) {
            $params['services'][] = dgettext('tuleap-git', 'Git');
        }
    }

    /**
     *
     * @param array $params
     * @see Event::IMPORT_XML_PROJECT
     */
    public function importXmlProject($params)
    {
        $logger  = new WrapperLogger($params['logger'], "GitXmlImporter");
        $git_dao = new GitDao();

        $importer = new GitXmlImporter(
            $logger,
            $this->getRepositoryManager(),
            $this->getRepositoryFactory(),
            $this->getBackendGitolite(),
            $this->getGitSystemEventManager(),
            PermissionsManager::instance(),
            EventManager::instance(),
            $this->getFineGrainedUpdater(),
            $this->getRegexpFineGrainedRetriever(),
            $this->getRegexpFineGrainedEnabler(),
            $this->getFineGrainedFactoryWithLogger($logger),
            $this->getFineGrainedPermissionSaver(),
            new XmlUgroupRetriever(
                $logger,
                $this->getUGroupManager()
            ),
            $git_dao,
            new XMLImportHelper(UserManager::instance()),
            $git_dao,
            new GitXMLImportDefaultBranchRetriever(),
            new DefaultBranchUpdateExecutorAsGitoliteUser(),
        );

        $importer->import(
            $params['configuration'],
            $params['project'],
            UserManager::instance()->getCurrentUser(),
            $params['xml_content'],
            $params['extraction_path']
        );
    }

    /**
     * @return GitPhpAccessLogger
     */
    protected function getGitPhpAccessLogger()
    {
        $dao = new HistoryDao();

        return new GitPhpAccessLogger($dao);
    }

    public function codendi_daily_start()//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        SystemEventManager::instance()->createEvent(
            ParseGitolite3Logs::NAME,
            null,
            SystemEvent::PRIORITY_LOW,
            SystemEvent::OWNER_ROOT,
            \Tuleap\Git\SystemEvents\ParseGitolite3Logs::class
        );

        $this->getRepositoryManager()->purgeArchivedRepositories($this->getLogger());
    }

    private function getGitolite3Parser()
    {
        return new Gitolite3LogParser(
            $this->getLogger(),
            new HttpUserValidator(),
            new HistoryDao(),
            $this->getRepositoryFactory(),
            UserManager::instance(),
            new GitoliteFileLogsDao(),
            $this->getUserDao()
        );
    }

    public function collectHeartbeatsEntries(HeartbeatsEntryCollection $collection): void
    {
        $collector = new LatestHeartbeatsCollector(
            $this->getRepositoryFactory(),
            $this->getGitLogDao(),
            $this->getGitRepositoryUrlManager(),
            UserManager::instance()
        );
        $collector->collect($collection);
    }

    public function projectCanDisplayHierarchy(HierarchyDisplayer $hierarchy_displayer)
    {
        $shaker = new GerritCanMigrateChecker(EventManager::instance(), $this->getGerritServerFactory());

        if ($shaker->canMigrate($hierarchy_displayer->getProject())) {
            $hierarchy_displayer->projectCanDisplayHierarchy();
        }
    }

    /**
     * @return CITokenManager
     */
    private function getCITokenManager()
    {
        return new CITokenManager(new CITokenDao());
    }

    public function collectProjectAdminNavigationPermissionDropdownQuickLinks(
        NavigationDropdownQuickLinksCollector $quick_links_collector,
    ) {
        $project = $quick_links_collector->getProject();

        if (! $project->usesService(self::SERVICE_SHORTNAME)) {
            return;
        }

        $quick_links_collector->addQuickLink(
            new NavigationDropdownItemPresenter(
                dgettext('tuleap-git', 'Git'),
                $this->getPluginPath() . '/?' . http_build_query(
                    [
                        'group_id' => $project->getID(),
                        'action'   => 'admin-git-admins',
                    ]
                )
            )
        );
    }

    public function permissionPerGroupPaneCollector(PermissionPerGroupPaneCollector $event)
    {
        if (! $event->getProject()->usesService(self::SERVICE_SHORTNAME)) {
            return;
        }

        $ugroup_manager          = $this->getUGroupManager();
        $formatter               = new PermissionPerGroupUGroupFormatter($ugroup_manager);
        $collection_formatter    = new CollectionOfUgroupsFormatter($formatter, $ugroup_manager);
        $service_section_builder = new PermissionPerGroupGitSectionBuilder(
            new PermissionPerGroupUGroupRetriever(PermissionsManager::instance()),
            $collection_formatter,
            $ugroup_manager
        );

        $sections_collector = new GitPaneSectionCollector(
            $service_section_builder,
            $this->getUGroupManager()
        );

        $sections_collector->collectSections($event);
    }

    public function projectSuspendedAndNotBlockedWarningCollector(ProjectSuspendedAndNotBlockedWarningCollector $event)
    {
        if (! $event->getProject()->usesService(self::SERVICE_SHORTNAME)) {
            return;
        }

        $gerrit_server_factory = $this->getGerritServerFactory();
        if ($gerrit_server_factory->hasRemotesSetUp()) {
            $event->addWarning(dgettext('tuleap-git', 'Gerrit'));
        }
    }

    public function projectUnixNameIsEditable(ProjectUnixNameIsEditable $event): void
    {
        $gerrit_server_factory = $this->getGerritServerFactory();
        $is_editable           = empty($gerrit_server_factory->getServersForProject($event->getProject()));
        $event->setIsEditable($is_editable);
        if (! $is_editable) {
            $event->setMessage(dgettext('tuleap-git', " At least a git repository is migrated to gerrit"));
        }
    }

    private function getJSONRepositoriesRetriever()
    {
        $ugroup_representation_builder = new PermissionPerGroupUGroupRepresentationBuilder($this->getUGroupManager());
        $ugroup_builder                = new CollectionOfUGroupRepresentationBuilder(
            $ugroup_representation_builder
        );
        $admin_url_builder             = new AdminUrlBuilder();
        $simple_builder                = new RepositorySimpleRepresentationBuilder(
            $this->getGitPermissionsManager(),
            $ugroup_builder,
            $admin_url_builder
        );
        $fine_grained_builder          = new RepositoryFineGrainedRepresentationBuilder(
            $this->getGitPermissionsManager(),
            $ugroup_builder,
            new CollectionOfUGroupsRepresentationFormatter($ugroup_representation_builder),
            $this->getFineGrainedFactory(),
            $admin_url_builder
        );

        return new GitJSONPermissionsRetriever(
            new \Tuleap\Git\PermissionsPerGroup\RepositoriesPermissionRepresentationBuilder(
                $fine_grained_builder,
                $simple_builder,
                $this->getRepositoryFactory(),
                $this->getFineGrainedRetriever()
            )
        );
    }

    private function getPermissionsPerGroupController()
    {
        return new PermissionPerGroupController($this->getJSONRepositoriesRetriever());
    }

    /**
     * @return UserDao
     */
    protected function getUserDao()
    {
        return new UserDao();
    }

    /**
     * @return HTTPAccessControl
     */
    public function getHTTPAccessControl(\Psr\Log\LoggerInterface $logger)
    {
        $password_handler = \PasswordHandlerFactory::getPasswordHandler();
        return new HTTPAccessControl(
            $logger,
            new ForgeAccess(),
            new \User_LoginManager(
                \EventManager::instance(),
                \UserManager::instance(),
                \UserManager::instance(),
                new PasswordVerifier($password_handler),
                new \User_PasswordExpirationChecker(),
                $password_handler
            ),
            new ReplicationHTTPUserAuthenticator(
                $password_handler,
                $this->getGerritServerFactory(),
                new HttpUserValidator()
            ),
            new \Tuleap\User\AccessKey\HTTPBasicAuth\HTTPBasicAuthUserAccessKeyAuthenticator(
                new \Tuleap\Authentication\SplitToken\PrefixedSplitTokenSerializer(new \Tuleap\User\AccessKey\PrefixAccessKey()),
                new AccessKeyVerifier(
                    new AccessKeyDAO(),
                    new SplitTokenVerificationStringHasher(),
                    \UserManager::instance(),
                    new AccessKeyScopeRetriever(
                        new AccessKeyScopeDAO(),
                        $this->buildAccessKeyScopeBuilder()
                    )
                ),
                GitRepositoryAccessKeyScope::fromItself(),
                $logger
            ),
            $this->getPermissionsManager(),
            $this->getUserDao(),
            new \Tuleap\Git\HTTP\GitHTTPAskBasicAuthenticationChallenge(),
        );
    }

    public function routeGetAccountGerrit(): DispatchableWithRequest
    {
        return new AccountGerritController(
            EventManager::instance(),
            TemplateRendererFactory::build(),
            $this->getGerritServerFactory(),
        );
    }

    public function routePostAccountGerritSSH(): DispatchableWithRequest
    {
        return new PushSSHKeysController(
            AccountGerritController::getCSRFToken(),
            $this->getUserAccountManager(),
            $this->getGerritServerFactory(),
            $this->getLogger(),
        );
    }

    public function routePostAccountGerritGroups(): DispatchableWithRequest
    {
        return new ResynchronizeGroupsController(
            AccountGerritController::getCSRFToken(),
            $this->getGerritServerFactory(),
            $this->getGerritMembershipManager(),
        );
    }

    public function routeGetGit()
    {
        return new GitRepositoryListController(
            $this->getProjectManager(),
            new ListPresenterBuilder(
                $this->getGitPermissionsManager(),
                $this->getGitDao(),
                UserManager::instance(),
                EventManager::instance(),
                new ProjectFlagsBuilder(new ProjectFlagsDao()),
            ),
            new \Tuleap\Layout\JavascriptViteAsset(
                new \Tuleap\Layout\IncludeViteAssets(
                    __DIR__ . '/../scripts/repositories-list/frontend-assets/',
                    '/assets/git/repositories-list'
                ),
                'src/index.ts'
            ),
            EventManager::instance(),
        );
    }

    public function routeGetLegacyURLForRepository()
    {
        return new \Tuleap\Git\GitLegacyURLRedirectController(
            $this->getProjectManager(),
            $this->getRepositoryFactory()
        );
    }

    public function routeGetPostSmartHTTP()
    {
        $logger = new \WrapperLogger($this->getLogger(), 'http');
        return new \Tuleap\Git\HTTP\HTTPController(
            $logger,
            $this->getProjectManager(),
            $this->getRepositoryFactory(),
            $this->getHTTPAccessControl($logger)
        );
    }

    public function routeGetPostRepositoryView()
    {
        return new \Tuleap\Git\GitRepositoryBrowserController(
            $this->getRepositoryFactory(),
            $this->getProjectManager(),
            $this->getGitPhpAccessLogger(),
            $this->getGitRepositoryHeaderDisplayer(),
            new FilesHeaderPresenterBuilder(
                new GitPHPProjectRetriever(),
                new CommitForCurrentTreeRetriever(),
                $this->getGitRepositoryUrlManager()
            ),
            EventManager::instance()
        );
    }

    public function routeGetPostLegacyController()
    {
        return new \Tuleap\Git\GitPluginDefaultController(
            $this->getChainOfRouters(),
            EventManager::instance()
        );
    }

    public function collectRoutesEvent(\Tuleap\Request\CollectRoutesEvent $event)
    {
        $event->getRouteCollector()->addRoute(['GET', 'POST'], GIT_SITE_ADMIN_BASE_URL . '[/]', $this->getRouteHandler('getAdminRouter'));

        $event->getRouteCollector()->addGroup(GIT_BASE_URL, function (FastRoute\RouteCollector $r) {
            EventManager::instance()->processEvent(new \Tuleap\Git\CollectGitRoutesEvent($r));

            $r->get('/account/gerrit', $this->getRouteHandler('routeGetAccountGerrit'));
            $r->post('/account/gerrit/ssh', $this->getRouteHandler('routePostAccountGerritSSH'));
            $r->post('/account/gerrit/groups', $this->getRouteHandler('routePostAccountGerritGroups'));

            $r->get('/index.php/{project_id:\d+}/view/{repository_id:\d+}/[{args}]', $this->getRouteHandler('routeGetLegacyURLForRepository'));

            $r->addRoute(['GET', 'POST'], '/{project_name}/{path:.*\.git|.*}/{smart_http:HEAD|info/refs\??.*|git-upload-pack|git-receive-pack|objects/info[^/]+|objects/[0-9a-f]{2}/[0-9a-f]{38}|pack/pack-[0-9a-f]{40}\.pack|pack/pack-[0-9a-f]{40}\.idx}', $this->getRouteHandler('routeGetPostSmartHTTP'));

            $r->get('/{project_name:[A-z0-9-]+}[/]', $this->getRouteHandler('routeGetGit'));

            $r->addRoute(['GET', 'POST'], '/{project_name}/{path:.*}', $this->getRouteHandler('routeGetPostRepositoryView'));
            $r->addRoute(['GET', 'POST'], '/{path:.*}', $this->getRouteHandler('routeGetPostLegacyController'));
        });
    }

    /**
     * protected for testing purpose
     * @return GitRepositoryHeaderDisplayer
     */
    protected function getGitRepositoryHeaderDisplayer()
    {
        $selected_tab = RepositoryHeaderPresenterBuilder::TAB_FILES;

        $gitphp_actions_displayed_in_commits_tab = ['shortlog', 'commit', 'commitdiff', 'blobdiff', 'search'];
        if (in_array(HTTPRequest::instance()->get('a'), $gitphp_actions_displayed_in_commits_tab, true)) {
            $selected_tab = RepositoryHeaderPresenterBuilder::TAB_COMMITS;
        }

        $header_displayed_builder = new GitRepositoryHeaderDisplayerBuilder();
        return $header_displayed_builder->build($selected_tab);
    }

    public function getLegacyAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../scripts/legacy/frontend-assets',
            '/assets/git/legacy'
        );
    }

    /**
     * @return \Tuleap\Git\Repository\RepositoryCreator
     */
    private function getRepositoryCreator()
    {
        return new \Tuleap\Git\Repository\RepositoryCreator(
            $this->getRepositoryFactory(),
            $this->getBackendGitolite(),
            $this->getRepositoryManager(),
            $this->getGitPermissionsManager(),
            $this->getFineGrainedPermissionReplicator(),
            new ProjectHistoryDao(),
            $this->getHistoryValueFormatter(),
            $this->getCITokenManager(),
            EventManager::instance()
        );
    }

    /**
     * @return HeaderRenderer
     */
    public function getHeaderRenderer()
    {
        $service_crumb_builder        = new GitCrumbBuilder($this->getGitPermissionsManager(), $this->getPluginPath());
        $settings_crumb_builder       = new RepositorySettingsCrumbBuilder($this->getPluginPath());
        $administration_crumb_builder = new ServiceAdministrationCrumbBuilder($this->getPluginPath());

        $repository_crumb_builder = new RepositoryCrumbBuilder(
            $this->getGitRepositoryUrlManager(),
            $this->getGitPermissionsManager(),
            $this->getPluginPath()
        );

        return new HeaderRenderer(
            EventManager::instance(),
            $service_crumb_builder,
            $administration_crumb_builder,
            $repository_crumb_builder,
            $settings_crumb_builder
        );
    }

    /**
     * @return AccessRightsPresenterOptionsBuilder
     */
    private function getAccessRightsPresenterOptionsBuilder()
    {
        $user_group_factory  = new User_ForgeUserGroupFactory(new UserGroupDao());
        $permissions_manager = $this->getPermissionsManager();
        $option_builder      = new AccessRightsPresenterOptionsBuilder($user_group_factory, $permissions_manager);

        return $option_builder;
    }

    /**
     * @return ThemeManager
     */
    protected function getThemeManager()
    {
        return new ThemeManager(
            new BurningParrotCompatiblePageDetector(
                new Tuleap\Request\CurrentPage(),
                new \User_ForgeUserGroupPermissionsManager(
                    new \User_ForgeUserGroupPermissionsDao()
                )
            )
        );
    }

    public function statisticsCollectionCollector(StatisticsCollectionCollector $collector)
    {
        $collector->addStatistics(
            dgettext('tuleap-git', 'Git push'),
            $this->getGitLogDao()->countGitPush(),
            $this->getGitLogDao()->countGitPushAfter($collector->getTimestamp())
        );
    }

    /**
     * @return Git_LogDao
     */
    private function getGitLogDao()
    {
        return new Git_LogDao();
    }

    private function getBigObjectAuthorizationManager()
    {
        return new \Tuleap\Git\BigObjectAuthorization\BigObjectAuthorizationManager(
            new \Tuleap\Git\BigObjectAuthorization\BigObjectAuthorizationDao(),
            $this->getProjectManager()
        );
    }

    public function collectCLICommands(CLICommandsCollector $commands_collector): void
    {
        $commands_collector->addCommand(
            RegenerateConfigurationCommand::NAME,
            function (): RegenerateConfigurationCommand {
                return new RegenerateConfigurationCommand(
                    ProjectManager::instance(),
                    $this->getGitSystemEventManager()
                );
            }
        );
        $commands_collector->addCommand(
            RepositoriesWithObjectsOverTheLimitCommand::NAME,
            function (): RepositoriesWithObjectsOverTheLimitCommand {
                return new RepositoriesWithObjectsOverTheLimitCommand(
                    $this->getRepositoryFactory(),
                    new GitRepositoryObjectsSizeRetriever()
                );
            }
        );
        $commands_collector->addCommand(
            PreReceiveCommand::NAME,
            function (): PreReceiveCommand {
                $mapper      = \Tuleap\Mapper\ValinorMapperBuilderFactory::mapperBuilder()->mapper();
                $wasm_caller = new FFIWASMCaller(new WasmtimeCacheConfigurationBuilder(), $mapper, Prometheus::instance(), 'git_plugin');

                return new PreReceiveCommand(
                    new PreReceiveAction(
                        $this->getRepositoryFactory(),
                        $wasm_caller,
                        $mapper,
                        $this->getLogger(),
                        EventManager::instance(),
                    )
                );
            }
        );
    }

    public function collectAccessKeyScopeBuilder(AccessKeyScopeBuilderCollector $collector): void
    {
        $collector->addAccessKeyScopeBuilder($this->buildAccessKeyScopeBuilder());
    }

    private function buildAccessKeyScopeBuilder(): AuthenticationScopeBuilder
    {
        return new AuthenticationScopeBuilderFromClassNames(
            GitRepositoryAccessKeyScope::class
        );
    }

    public function serviceEnableForXmlImportRetriever(ServiceEnableForXmlImportRetriever $event): void
    {
        $event->addServiceIfPluginIsNotRestricted($this, $this->getServiceShortname());
    }

    public function accountTabPresenterCollection(AccountTabPresenterCollection $collection): void
    {
        (new \Tuleap\Git\Account\AccountTabsBuilder($this->getGerritServerFactory()))->addTabs($collection);
    }

    public function crossReferenceByNatureOrganizer(CrossReferenceByNatureOrganizer $organizer): void
    {
        $git_organizer = new CrossReferenceGitOrganizer(
            new OrganizeableGitCrossReferencesAndTheContributorsCollector(
                new CommitDetailsCrossReferenceInformationBuilder(
                    ProjectManager::instance(),
                    new Git_ReferenceManager(
                        $this->getRepositoryFactory(),
                        ReferenceManager::instance(),
                        new ReferenceDao()
                    ),
                    new CommitProvider(),
                    new CommitDetailsRetriever(
                        new CommitDetailsCacheDao(),
                    ),
                ),
                UserManager::instance(),
            ),
            new CrossReferenceGitEnhancer(
                UserHelper::instance(),
                new TlpRelativeDatePresenterBuilder(),
            ),
        );

        $git_organizer->organizeGitReferences($organizer);
    }

    public function additionalArtifactActionButtonsFetcher(AdditionalArtifactActionButtonsFetcher $event): void
    {
        $button_fetcher = new CreateBranchButtonFetcher(
            $this->getRepositoryFactory(),
            new BranchNameCreatorFromArtifact(
                new Slugify()
            ),
            new \Tuleap\Layout\JavascriptViteAsset(
                new \Tuleap\Layout\IncludeViteAssets(
                    __DIR__ . '/../scripts/artifact-create-branch-action/frontend-assets/',
                    '/assets/git/artifact-create-branch-action'
                ),
                'src/index.ts'
            ),
            new \Tuleap\Git\PullRequestEndpointsAvailableChecker(EventManager::instance()),
        );

        $button_action = $button_fetcher->getActionButton(
            $event->getArtifact(),
            $event->getUser()
        );

        if ($button_action === null) {
            return;
        }

        $event->addAction($button_action);
    }

    public function semanticDoneUsedExternalServiceEvent(SemanticDoneUsedExternalServiceEvent $event): void
    {
        $project = $event->getTracker()->getProject();
        if (! $project->usesService(self::SERVICE_SHORTNAME)) {
            return;
        }

        $event->setExternalServicesDescriptions(
            new SemanticDoneUsedExternalService(
                dgettext('tuleap-git', 'Git'),
                dgettext('tuleap-git', 'close artifacts'),
            )
        );
    }

    public function workerEvent(WorkerEvent $event): void
    {
        $logger        = $this->getLogger();
        $event_manager = \EventManager::instance();

        $handler = new AsynchronousEventHandler(
            $logger,
            new DefaultBranchPushParser(
                \UserManager::instance(),
                new GitRepositoryRetriever(
                    new \GitRepositoryFactory(new GitDao(), ProjectManager::instance()),
                )
            ),
            new DefaultBranchPushProcessorBuilder(),
            $event_manager
        );
        $handler->handle($event);
    }

    private function getAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../frontend-assets',
            '/assets/git'
        );
    }
}
