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

use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Authentication\Scope\AuthenticationScopeBuilder;
use Tuleap\Authentication\Scope\AuthenticationScopeBuilderFromClassNames;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\BurningParrotCompatiblePageDetector;
use Tuleap\CLI\CLICommandsCollector;
use Tuleap\Event\Events\ExportXmlProject;
use Tuleap\Git\AccessRightsPresenterOptionsBuilder;
use Tuleap\Git\Account\AccountGerritController;
use Tuleap\Git\Account\PushSSHKeysController;
use Tuleap\Git\Account\ResynchronizeGroupsController;
use Tuleap\Git\BreadCrumbDropdown\GitCrumbBuilder;
use Tuleap\Git\BreadCrumbDropdown\RepositoryCrumbBuilder;
use Tuleap\Git\BreadCrumbDropdown\RepositorySettingsCrumbBuilder;
use Tuleap\Git\BreadCrumbDropdown\ServiceAdministrationCrumbBuilder;
use Tuleap\Git\CIToken\Dao as CITokenDao;
use Tuleap\Git\CIToken\Manager as CITokenManager;
use Tuleap\Git\CreateRepositoryController;
use Tuleap\Git\DefaultSettings\DefaultSettingsRouter;
use Tuleap\Git\DefaultSettings\IndexController;
use Tuleap\Git\DiskUsage\Collector;
use Tuleap\Git\DiskUsage\Retriever;
use Tuleap\Git\Gerrit\ReplicationHTTPUserAuthenticator;
use Tuleap\Git\GerritCanMigrateChecker;
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
use Tuleap\Git\Gitolite\SSHKey\SystemEvent\MigrateToTuleapSSHKeyManagement;
use Tuleap\Git\Gitolite\VersionDetector;
use Tuleap\Git\HTTP\HTTPUserAccessKeyAuthenticator;
use Tuleap\Git\Repository\GitRepositoryObjectsSizeRetriever;
use Tuleap\Git\GitViews\Header\HeaderRenderer;
use Tuleap\Git\GitXmlExporter;
use Tuleap\Git\GlobalParameterDao;
use Tuleap\Git\History\Dao as HistoryDao;
use Tuleap\Git\History\GitPhpAccessLogger;
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
use Tuleap\Git\RemoteServer\Gerrit\HttpUserValidator;
use Tuleap\Git\RemoteServer\Gerrit\Restrictor;
use Tuleap\Git\Repository\DescriptionUpdater;
use Tuleap\Git\Repository\GitPHPProjectRetriever;
use Tuleap\Git\Repository\GitRepositoryHeaderDisplayer;
use Tuleap\Git\Repository\GitRepositoryHeaderDisplayerBuilder;
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
use Tuleap\Git\RestrictedGerritServerDao;
use Tuleap\Git\SystemEvents\ParseGitolite3Logs;
use Tuleap\Git\SystemEvents\ProjectIsSuspended;
use Tuleap\Git\User\AccessKey\Scope\GitRepositoryAccessKeyScope;
use Tuleap\Git\Webhook\WebhookDao;
use Tuleap\Git\XmlUgroupRetriever;
use Tuleap\GitBundle;
use Tuleap\Glyph\GlyphLocation;
use Tuleap\Glyph\GlyphLocationsCollector;
use Tuleap\Http\HttpClientFactory;
use Tuleap\layout\HomePage\StatisticsCollectionCollector;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\ServiceUrlCollector;
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
use Tuleap\Project\HeartbeatsEntryCollection;
use Tuleap\Project\HierarchyDisplayer;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\Project\Status\ProjectSuspendedAndNotBlockedWarningCollector;
use Tuleap\Project\XML\ServiceEnableForXmlImportRetriever;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\RestrictedUsersAreHandledByPluginEvent;
use Tuleap\REST\JsonDecoder;
use Tuleap\REST\QueryParameterParser;
use Tuleap\User\AccessKey\AccessKeyDAO;
use Tuleap\User\AccessKey\AccessKeyVerifier;
use Tuleap\User\AccessKey\Scope\AccessKeyScopeBuilderCollector;
use Tuleap\User\AccessKey\Scope\AccessKeyScopeDAO;
use Tuleap\User\AccessKey\Scope\AccessKeyScopeRetriever;
use Tuleap\User\Account\AccountTabPresenterCollection;
use Tuleap\User\PasswordVerifier;

require_once 'constants.php';
require_once __DIR__ . '/../vendor/autoload.php';

/**
 * GitPlugin
 */
class GitPlugin extends Plugin //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
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
        $this->addHook('site_admin_option_hook', 'site_admin_option_hook', false);
        $this->addHook('cssfile', 'cssFile', false);
        $this->addHook('javascript_file', 'jsFile', false);
        $this->addHook(Event::JAVASCRIPT, 'javascript', false);
        $this->addHook(Event::GET_SYSTEM_EVENT_CLASS, 'getSystemEventClass', false);
        $this->addHook(Event::GET_PLUGINS_AVAILABLE_KEYWORDS_REFERENCES, 'getReferenceKeywords', false);
        $this->addHook(Event::GET_AVAILABLE_REFERENCE_NATURE, 'getReferenceNatures', false);
        $this->addHook(Event::GET_REFERENCE);
        $this->addHook('SystemEvent_PROJECT_IS_PRIVATE', 'changeProjectRepositoriesAccess', false);
        $this->addHook('SystemEvent_PROJECT_RENAME', 'systemEventProjectRename', false);
        $this->addHook('project_is_deleted');
        $this->addHook('project_is_suspended');
        $this->addHook('project_is_active');
        $this->addHook('file_exists_in_data_dir', 'file_exists_in_data_dir', false);
        $this->addHook(Event::SERVICE_CLASSNAMES);
        $this->addHook(Event::SERVICES_ALLOWED_FOR_PROJECT);

        // Stats plugin
        $this->addHook('plugin_statistics_disk_usage_collect_project', 'plugin_statistics_disk_usage_collect_project', false);
        $this->addHook('plugin_statistics_disk_usage_service_label', 'plugin_statistics_disk_usage_service_label', false);
        $this->addHook('plugin_statistics_color', 'plugin_statistics_color', false);

        $this->addHook(Event::DUMP_SSH_KEYS);
        $this->addHook(Event::EDIT_SSH_KEYS);
        $this->addHook(Event::PROCCESS_SYSTEM_CHECK);
        $this->addHook(Event::SYSTEM_EVENT_GET_TYPES_FOR_DEFAULT_QUEUE);
        $this->addHook(Event::SYSTEM_EVENT_GET_CUSTOM_QUEUES);
        $this->addHook(Event::SYSTEM_EVENT_GET_TYPES_FOR_CUSTOM_QUEUE);

        $this->addHook('permission_get_name', 'permission_get_name', false);
        $this->addHook('permission_get_object_type', 'permission_get_object_type', false);
        $this->addHook('permission_get_object_name', 'permission_get_object_name', false);
        $this->addHook('permission_user_allowed_to_change', 'permission_user_allowed_to_change', false);

        $this->addHook('statistics_collector', 'statistics_collector', false);

        $this->addHook('collect_ci_triggers', 'collect_ci_triggers', false);
        $this->addHook('save_ci_triggers', 'save_ci_triggers', false);
        $this->addHook('update_ci_triggers', 'update_ci_triggers', false);
        $this->addHook('delete_ci_triggers', 'delete_ci_triggers', false);

        $this->addHook('logs_daily', 'logsDaily', false);
        $this->addHook(\Tuleap\Widget\Event\GetWidget::NAME);
        $this->addHook(\Tuleap\Widget\Event\GetUserWidgetList::NAME);
        $this->addHook(\Tuleap\Widget\Event\GetProjectWidgetList::NAME);
        $this->addHook('show_pending_documents', 'showArchivedRepositories', false);

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

        $this->addHook(Event::REGISTER_PROJECT_CREATION);
        $this->addHook(RestrictedUsersAreHandledByPluginEvent::NAME);
        $this->addHook(Event::GET_SERVICES_ALLOWED_FOR_RESTRICTED);
        $this->addHook(Event::PROJECT_ACCESS_CHANGE);
        $this->addHook(Event::SITE_ACCESS_CHANGE);

        $this->addHook('fill_project_history_sub_events');
        $this->addHook(Event::POST_SYSTEM_EVENTS_ACTIONS);

        $this->addHook(Event::REST_RESOURCES);
        $this->addHook(Event::REST_PROJECT_RESOURCES);
        $this->addHook(Event::REST_PROJECT_GET_GIT);
        $this->addHook(Event::REST_PROJECT_OPTIONS_GIT);

        $this->addHook(ExportXmlProject::NAME);
        $this->addHook(Event::IMPORT_XML_PROJECT, 'importXmlProject', false);

        // Gerrit user suspension
        if (defined('LDAP_DAILY_SYNCHRO_UPDATE_USER')) {
            $this->addHook(LDAP_DAILY_SYNCHRO_UPDATE_USER);
        }

        $this->addHook(Event::SERVICES_TRUNCATED_EMAILS);

        $this->addHook('codendi_daily_start');

        $this->addHook(PermissionPerGroupDisplayEvent::NAME);

        $this->addHook(\Tuleap\Request\CollectRoutesEvent::NAME);
    }

    public function getHooksAndCallbacks()
    {
        $this->addHook(GlyphLocationsCollector::NAME);
        $this->addHook(HeartbeatsEntryCollection::NAME);
        $this->addHook(HierarchyDisplayer::NAME);
        $this->addHook(NavigationDropdownQuickLinksCollector::NAME);
        $this->addHook(UserBecomesProjectAdmin::NAME);
        $this->addHook(UserIsNoLongerProjectAdmin::NAME);
        $this->addHook(PermissionPerGroupPaneCollector::NAME);
        $this->addHook(ServiceUrlCollector::NAME);
        $this->addHook(ProjectSuspendedAndNotBlockedWarningCollector::NAME);
        $this->addHook(StatisticsCollectionCollector::NAME);
        $this->addHook(CLICommandsCollector::NAME);
        $this->addHook(AccessKeyScopeBuilderCollector::NAME);
        $this->addHook(ServiceEnableForXmlImportRetriever::NAME);
        $this->addHook(AccountTabPresenterCollection::NAME);

        if (defined('STATISTICS_BASE_DIR')) {
            $this->addHook(Statistics_Event::FREQUENCE_STAT_ENTRIES);
            $this->addHook(Statistics_Event::FREQUENCE_STAT_SAMPLE);
        }

        return parent::getHooksAndCallbacks();
    }

    public function serviceClassnames(array $params)
    {
        $params['classnames'][$this->getServiceShortname()] = \Tuleap\Git\GitService::class;
    }

    public function exportXmlProject(ExportXmlProject $event): void
    {
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
            EventManager::instance()
        );
    }

    public function getServiceShortname()
    {
        return self::SERVICE_SHORTNAME;
    }

    public function site_admin_option_hook($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['plugins'][] = array(
            'label' => dgettext('tuleap-git', 'Git'),
            'href'  => GIT_SITE_ADMIN_BASE_URL
        );
    }

    public function getPluginInfo()
    {
        if (!is_a($this->pluginInfo, 'GitPluginInfo')) {
            $this->pluginInfo = new GitPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    /**
     * @see Statistics_Event::FREQUENCE_STAT_ENTRIES
     */
    public function plugin_statistics_frequence_stat_entries($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['entries'][self::$FREQUENCIES_GIT_READ] = 'Git read access';
    }

    /**
     * @see Statistics_Event::FREQUENCE_STAT_SAMPLE
     */
    public function plugin_statistics_frequence_stat_sample($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($params['character'] === self::$FREQUENCIES_GIT_READ) {
            $params['sample'] = new Tuleap\Git\Statistics\FrequenciesSample();
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

    public function cssFile($params)
    {
        // Only show the stylesheet if we're actually in the Git pages.
        // This stops styles inadvertently clashing with the main site.
        if (
            strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0 ||
            strpos($_SERVER['REQUEST_URI'], '/widgets/') === 0
        ) {
            echo '<link rel="stylesheet" type="text/css" href="' . $this->getIncludeAssets()->getFileURL('default.css') . '" />';
        }
    }

    public function jsFile($params)
    {
        // Only show the javascript if we're actually in the Git pages.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            echo '<script type="text/javascript" src="' . $this->getIncludeAssets()->getFileURL('git.js') . '"></script>';
        }
    }

    public function permissionPerGroupDisplayEvent(PermissionPerGroupDisplayEvent $event)
    {
        $event->addJavascript($this->getIncludeAssets()->getFileURL('permission-per-group.js'));
    }

    public function javascript($params)
    {
        include $GLOBALS['Language']->getContent('script_locale', null, 'git');
    }

    public function system_event_get_types_for_default_queue(array &$params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['types'] = array_merge($params['types'], $this->getGitSystemEventManager()->getTypesForDefaultQueue());
    }

    /** @see Event::SYSTEM_EVENT_GET_CUSTOM_QUEUES */
    public function system_event_get_custom_queues(array &$params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['queues'][Git_SystemEventQueue::NAME] = new Git_SystemEventQueue($this->getLogger());
        $params['queues'][Git_Mirror_MirrorSystemEventQueue::NAME] = new Git_Mirror_MirrorSystemEventQueue($this->getLogger());
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

        if ($params['queue'] == Git_Mirror_MirrorSystemEventQueue::NAME) {
            $params['types'] = array_merge(
                $params['types'],
                $this->getGitSystemEventManager()->getGrokMirrorTypes()
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
                $params['class'] = 'SystemEvent_GIT_REPO_UPDATE';
                $params['dependencies'] = array(
                    $this->getRepositoryFactory(),
                    $this->getGitSystemEventManager()
                );
                break;
            case SystemEvent_GIT_REPO_DELETE::NAME:
                $params['class'] = 'SystemEvent_GIT_REPO_DELETE';
                $params['dependencies'] = array(
                    $this->getRepositoryFactory(),
                    $this->getLogger(),
                    $this->getGitSystemEventManager(),
                    $this->getUgroupsToNotifyDao(),
                    $this->getUsersToNotifyDao(),
                    EventManager::instance()
                );
                break;
            case SystemEvent_GIT_LEGACY_REPO_DELETE::NAME:
                $params['class'] = 'SystemEvent_GIT_LEGACY_REPO_DELETE';
                $params['dependencies'] = array(
                    $this->getRepositoryFactory(),
                    $this->getManifestManager(),
                    $this->getLogger(),
                );
                break;
            case SystemEvent_GIT_LEGACY_REPO_ACCESS::NAME:
                $params['class'] = 'SystemEvent_GIT_LEGACY_REPO_ACCESS';
                break;
            case SystemEvent_GIT_GERRIT_MIGRATION::NAME:
                $params['class'] = 'SystemEvent_GIT_GERRIT_MIGRATION';
                $params['dependencies'] = array(
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
                                PermissionsOverrider_PermissionsOverriderManager::instance(),
                                new RestrictedUserCanAccessProjectVerifier(),
                                EventManager::instance()
                            ),
                            new MailLogger()
                        )
                    ),
                );
                break;
            case SystemEvent_GIT_REPO_FORK::NAME:
                $params['class'] = 'SystemEvent_GIT_REPO_FORK';
                $params['dependencies'] = array(
                    $this->getRepositoryFactory()
                );
                break;
            case SystemEvent_GIT_GERRIT_ADMIN_KEY_DUMP::NAME:
                $params['class'] = 'SystemEvent_GIT_GERRIT_ADMIN_KEY_DUMP';
                $params['dependencies'] = array(
                    $this->getGerritServerFactory(),
                    $this->getSSHKeyDumper(),
                );
                break;
            case SystemEvent_GIT_GERRIT_PROJECT_DELETE::NAME:
                $params['class'] = 'SystemEvent_GIT_GERRIT_PROJECT_DELETE';
                $params['dependencies'] = array(
                    $this->getRepositoryFactory(),
                    $this->getGerritServerFactory(),
                    $this->getGerritDriverFactory()
                );
                break;
            case SystemEvent_GIT_GERRIT_PROJECT_READONLY::NAME:
                $params['class'] = 'SystemEvent_GIT_GERRIT_PROJECT_READONLY';
                $params['dependencies'] = array(
                    $this->getRepositoryFactory(),
                    $this->getGerritServerFactory(),
                    $this->getGerritDriverFactory()
                );
                break;
            case SystemEvent_GIT_USER_RENAME::NAME:
                $params['class'] = 'SystemEvent_GIT_USER_RENAME';
                $params['dependencies'] = array(
                    $this->getSSHKeyDumper(),
                    UserManager::instance()
                );
                break;
            case SystemEvent_GIT_GROKMIRROR_MANIFEST_UPDATE::NAME:
                $params['class'] = 'SystemEvent_GIT_GROKMIRROR_MANIFEST_UPDATE';
                $params['dependencies'] = array(
                    $this->getRepositoryFactory(),
                    $this->getManifestManager(),
                );
                break;
            case SystemEvent_GIT_GROKMIRROR_MANIFEST_UPDATE_FOLLOWING_A_GIT_PUSH::NAME:
                $params['class'] = 'SystemEvent_GIT_GROKMIRROR_MANIFEST_UPDATE_FOLLOWING_A_GIT_PUSH';
                $params['dependencies'] = array(
                    $this->getRepositoryFactory(),
                    $this->getManifestManager(),
                );
                break;
            case SystemEvent_GIT_GROKMIRROR_MANIFEST_CHECK::NAME:
                $params['class'] = 'SystemEvent_GIT_GROKMIRROR_MANIFEST_CHECK';
                $params['dependencies'] = array(
                    $this->getManifestManager(),
                );
                break;
            case SystemEvent_GIT_GROKMIRROR_MANIFEST_REPODELETE::NAME:
                $params['class'] = 'SystemEvent_GIT_GROKMIRROR_MANIFEST_REPODELETE';
                $params['dependencies'] = array(
                    $this->getManifestManager(),
                );
                break;
            case SystemEvent_GIT_EDIT_SSH_KEYS::NAME:
                $params['class'] = 'SystemEvent_GIT_EDIT_SSH_KEYS';
                $params['dependencies'] = array(
                    UserManager::instance(),
                    $this->getSSHKeyDumper(),
                    $this->getUserAccountManager(),
                    $this->getGitSystemEventManager(),
                    $this->getLogger()
                );
                break;
            case SystemEvent_GIT_DUMP_ALL_SSH_KEYS::NAME:
                $params['class'] = 'SystemEvent_GIT_DUMP_ALL_SSH_KEYS';
                $params['dependencies'] = array(
                    $this->getSSHKeyMassDumper(),
                    $this->getLogger()
                );
                break;
            case SystemEvent_GIT_REPO_RESTORE::NAME:
                $params['class'] = 'SystemEvent_GIT_REPO_RESTORE';
                $params['dependencies'] = array(
                    $this->getRepositoryFactory(),
                    $this->getGitSystemEventManager()
                );
                break;
            case SystemEvent_GIT_PROJECTS_UPDATE::NAME:
                $params['class'] = 'SystemEvent_GIT_PROJECTS_UPDATE';
                $params['dependencies'] = array(
                    $this->getLogger(),
                    $this->getGitSystemEventManager(),
                    $this->getProjectManager(),
                    $this->getGitoliteDriver(),
                );
                break;
            case SystemEvent_GIT_DUMP_ALL_MIRRORED_REPOSITORIES::NAME:
                $params['class'] = 'SystemEvent_GIT_DUMP_ALL_MIRRORED_REPOSITORIES';
                $params['dependencies'] = array(
                    $this->getGitoliteDriver()
                );
                break;
            case SystemEvent_GIT_UPDATE_MIRROR::NAME:
                $params['class'] = 'SystemEvent_GIT_UPDATE_MIRROR';
                $params['dependencies'] = array(
                    $this->getGitoliteDriver()
                );
                break;
            case SystemEvent_GIT_DELETE_MIRROR::NAME:
                $params['class'] = 'SystemEvent_GIT_DELETE_MIRROR';
                $params['dependencies'] = array(
                    $this->getGitoliteDriver()
                );
                break;
            case SystemEvent_GIT_REGENERATE_GITOLITE_CONFIG::NAME:
                $params['class'] = 'SystemEvent_GIT_REGENERATE_GITOLITE_CONFIG';
                $params['dependencies'] = array(
                    $this->getGitoliteDriver(),
                    $this->getProjectManager()
                );
                break;
            case ProjectIsSuspended::NAME:
                $params['class'] = ProjectIsSuspended::class;
                $params['dependencies'] = array(
                    $this->getGitoliteDriver(),
                    $this->getProjectManager()
                );
                break;
            case ParseGitolite3Logs::NAME:
                $params['class'] = '\\Tuleap\\Git\\SystemEvents\\ParseGitolite3Logs';
                $params['dependencies'] = array(
                    $this->getGitolite3Parser()
                );
                break;
            case MigrateToTuleapSSHKeyManagement::NAME:
                $params['class'] = 'Tuleap\\Git\\Gitolite\\SSHKey\\SystemEvent\\MigrateToTuleapSSHKeyManagement';
                $params['dependencies'] = array(
                    new GlobalParameterDao(),
                    new System_Command()
                );
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
    }

    public function getReferenceNatures($params)
    {
        $params['natures'] = array_merge(
            $params['natures'],
            array(
                Git::REFERENCE_NATURE => array(
                    'keyword' => Git::REFERENCE_KEYWORD,
                    'label'   => dgettext('tuleap-git', 'Git commit')
                )
            )
        );
    }

    public function get_reference($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($params['keyword'] == Git::REFERENCE_KEYWORD) {
            $reference = false;
            if ($params['project']) {
                $git_reference_manager = new Git_ReferenceManager(
                    $this->getRepositoryFactory(),
                    $params['reference_manager']
                );
                $reference = $git_reference_manager->getReference(
                    $params['project'],
                    $params['keyword'],
                    $params['value']
                );
            }
            $params['reference'] = $reference;
        }
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
        GitActions::renameProject($params['project'], $params['new_name']);
    }

    public function file_exists_in_data_dir($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['result'] = $this->isNameAvailable($params['new_name'], $params['error']);
    }

    private function isNameAvailable($newName, &$error)
    {
        $backend_gitolite = $this->getBackendGitolite();
        $backend_gitshell = Backend::instance('Git', 'GitBackend', array($this->getGitRepositoryUrlManager()));

        if (! $backend_gitolite->isNameAvailable($newName) && ! $backend_gitshell->isNameAvailable($newName)) {
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
                $this->getMirrorDataMapper(),
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
            new CITokenController($repository_retriever, $this->getCITokenManager())
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
            $this->getMirrorDataMapper(),
            new Git_MirrorResourceRestrictor(
                new Git_RestrictedMirrorDao(),
                $this->getMirrorDataMapper(),
                $this->getGitSystemEventManager(),
                new ProjectHistoryDao()
            ),
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
            $this->getManagementDetector(),
            $this->getBigObjectAuthorizationManager(),
            $this->getIncludeAssets(),
            new VersionDetector()
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

    public function getMirrorDataMapper(): Git_Mirror_MirrorDataMapper
    {
        return new Git_Mirror_MirrorDataMapper(
            new Git_Mirror_MirrorDao(),
            UserManager::instance(),
            new GitRepositoryFactory(
                new GitDao(),
                ProjectManager::instance()
            ),
            $this->getProjectManager(),
            $this->getGitSystemEventManager(),
            new Git_Gitolite_GitoliteRCReader(new VersionDetector()),
            new DefaultProjectMirrorDao()
        );
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
        $user_id = $params['user_id'];

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
     *
     * @param PFUser $user
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
        if (!$params['name']) {
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
        if (!$params['object_type']) {
            if (in_array($params['permission_type'], array('PLUGIN_GIT_READ', 'PLUGIN_GIT_WRITE', 'PLUGIN_GIT_WPLUS'))) {
                $params['object_type'] = 'git_repository';
            }
        }
    }
    public function permission_get_object_name($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (!$params['object_name']) {
            if (in_array($params['permission_type'], array('PLUGIN_GIT_READ', 'PLUGIN_GIT_WRITE', 'PLUGIN_GIT_WPLUS'))) {
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
        if (!$params['allowed']) {
            $user = $this->getCurrentUser();
            $project = $this->getProjectManager()->getProject($params['group_id']);

            if ($this->getGitPermissionsManager()->userIsGitAdmin($user, $project)) {
                $this->_cached_permission_user_allowed_to_change = true;
            }

            if (! $this->_cached_permission_user_allowed_to_change) {
                if (in_array($params['permission_type'], array('PLUGIN_GIT_READ', 'PLUGIN_GIT_WRITE', 'PLUGIN_GIT_WPLUS'))) {
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
        $gitgc = new Git_GitoliteHousekeeping_GitoliteHousekeepingGitGc(
            new Git_GitoliteHousekeeping_GitoliteHousekeepingDao(),
            $params['logger'],
            $this->getGitoliteAdminPath()
        );
        $gitolite_driver  = $this->getGitoliteDriver();

        $system_check = new Git_SystemCheck(
            $gitgc,
            $gitolite_driver,
            $this->getGitSystemEventManager(),
            new PluginConfigChecker($params['logger']),
            $this
        );

        $system_check->process();
    }

    public function getGitoliteDriver()
    {
        return new Git_GitoliteDriver(
            $this->getLogger(),
            $this->getGitSystemEventManager(),
            $this->getGitRepositoryUrlManager(),
            $this->getGitDao(),
            new Git_Mirror_MirrorDao(),
            $this,
            null,
            null,
            null,
            null,
            $this->getProjectManager(),
            $this->getMirrorDataMapper(),
            $this->getBigObjectAuthorizationManager(),
            new VersionDetector()
        );
    }

    /**
     * When project is deleted all its git repositories are archived and marked as deleted
     *
     * @param Array $params Parameters contining project id
     *
     * @return void
     */
    public function project_is_deleted($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (!empty($params['group_id'])) {
            $project = ProjectManager::instance()->getProject($params['group_id']);
            if ($project) {
                $repository_manager = $this->getRepositoryManager();
                $repository_manager->deleteProjectRepositories($project);
            }
        }
    }

    public function project_is_active(array $params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (! empty($params['group_id'])) {
            $this->getGitSystemEventManager()->queueRegenerateGitoliteConfig($params['group_id']);
        }
    }

    public function project_is_suspended(array $params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (! empty($params['group_id'])) {
            $this->getGitSystemEventManager()->queueProjectIsSuspended($params['group_id']);
        }
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
        if (!empty($params['formatter'])) {
            include_once('GitBackend.class.php');
            $formatter  = $params['formatter'];
            $gitBackend = Backend::instance('Git', 'GitBackend', array($this->getGitRepositoryUrlManager()));
            echo $gitBackend->getBackendStatistics($formatter);
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
        $ci = new Git_Ci();
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
        if (isset($params['job_id']) && !empty($params['job_id']) && isset($params['request']) && !empty($params['request'])) {
            if ($params['request']->get('hudson_use_plugin_git_trigger_checkbox')) {
                $repositoryId = $params['request']->get('hudson_use_plugin_git_trigger');
                if ($repositoryId) {
                    $vRepoId = new Valid_UInt('hudson_use_plugin_git_trigger');
                    $vRepoId->required();
                    if ($params['request']->valid($vRepoId)) {
                        $ci = new Git_Ci();
                        if (!$ci->saveTrigger($params['job_id'], $repositoryId)) {
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
        if (isset($params['request']) && !empty($params['request'])) {
            $jobId        = $params['request']->get('job_id');
            $repositoryId = $params['request']->get('hudson_use_plugin_git_trigger');
            if ($jobId) {
                $vJobId = new Valid_UInt('job_id');
                $vJobId->required();
                if ($params['request']->valid($vJobId)) {
                    $ci = new Git_Ci();
                    $vRepoId = new Valid_UInt('hudson_use_plugin_git_trigger');
                    $vRepoId->required();
                    if ($params['request']->valid($vRepoId)) {
                        if (!$ci->saveTrigger($jobId, $repositoryId)) {
                            $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-git', 'Git trigger not saved'));
                        }
                    } else {
                        if (!$ci->deleteTrigger($jobId)) {
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
        if (isset($params['job_id']) && !empty($params['job_id'])) {
            $ci = new Git_Ci();
            if (!$ci->deleteTrigger($params['job_id'])) {
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
        if ($project->usesService(GitPlugin::SERVICE_SHORTNAME)) {
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
                array(
                    'group_id'  => $params['group_id'],
                    'user_id'   => $params['user_id'],
                    'ugroup_id' => $ugroup_id,
                )
            );
        }
    }

    public function userIsNoLongerProjectAdmin(UserIsNoLongerProjectAdmin $event)
    {
        $this->project_admin_ugroup_remove_user(
            array(
                'user_id'   => $event->getUser()->getId(),
                'group_id'  => $event->getProject()->getID(),
                'ugroup_id' => ProjectUGroup::PROJECT_ADMIN
            )
        );
    }

    public function userBecomesProjectAdmin(UserBecomesProjectAdmin $event)
    {
        $this->project_admin_ugroup_add_user(
            array(
                'user_id'   => $event->getUser()->getId(),
                'group_id'  => $event->getProject()->getID(),
                'ugroup_id' => ProjectUGroup::PROJECT_ADMIN
            )
        );
    }

    public function project_admin_ugroup_deletion($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $ugroup     = $params['ugroup'];
        $users      = $ugroup->getMembers();
        $project_id = $params['group_id'];

        foreach ($users as $user) {
            $calling = array(
                'group_id' => $project_id,
                'user_id'  => $user->getId(),
                'ugroup'   => $ugroup
            );
            $this->project_admin_ugroup_remove_user($calling);
        }

        $this->getFineGrainedUpdater()->deleteUgroupPermissions($ugroup, $project_id);
        $this->getUgroupsToNotifyDao()->deleteByUgroupId($project_id, $ugroup->getId());

        $this->getGitSystemEventManager()->queueProjectsConfigurationUpdate(array($project_id));
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
        if ($project->usesService(GitPlugin::SERVICE_SHORTNAME)) {
            $event->addWidget('plugin_git_project_pushes');
        }
    }

    public function uninstall()
    {
        $this->removeOrphanWidgets(array('plugin_git_user_pushes', 'plugin_git_project_pushes'));
    }

    private function getProjectCreator()
    {
        $tmp_dir = ForgeConfig::get('tmp_dir') . '/gerrit_' . uniqid();
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
            $this->getMirrorDataMapper(),
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
            $this->getDescriptionUpdater(),
            $this->getGitPhpAccessLogger(),
            $this->getRegexpFineGrainedRetriever(),
            $this->getRegexpFineGrainedEnabler(),
            $this->getRegexpFineGrainedDisabler(),
            $this->getRegexpPermissionFilter(),
            new UsersToNotifyDao(),
            $this->getUgroupsToNotifyDao(),
            new UGroupManager(),
            $this->getHeaderRenderer()
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
        return new Git_SystemEventManager(SystemEventManager::instance(), $this->getRepositoryFactory());
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
            new GitRepositoryMirrorUpdater($this->getMirrorDataMapper(), new ProjectHistoryDao()),
            $this->getMirrorDataMapper(),
            $this->getFineGrainedPermissionReplicator(),
            new ProjectHistoryDao(),
            $this->getHistoryValueFormatter(),
            EventManager::instance()
        );
    }

    public function getRepositoryFactory()
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
        if (!$this->logger) {
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
        return $GLOBALS['sys_data_dir'] . '/gitolite/admin';
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

    public function register_project_creation($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $this->getPermissionsManager()->duplicateWithStaticMapping(
            $params['template_id'],
            $params['group_id'],
            array(Git::PERM_ADMIN, Git::DEFAULT_PERM_READ, Git::DEFAULT_PERM_WRITE, Git::DEFAULT_PERM_WPLUS),
            $params['ugroupsMapping']
        );

        $this->getDefaultFineGrainedPermissionReplicator()->replicate(
            $this->getProjectManager()->getProject($params['template_id']),
            $params['group_id'],
            $params['ugroupsMapping']
        );

        $this->getMirrorDataMapper()->duplicate($params['template_id'], $params['group_id']);
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
        return new Git_GitRepositoryUrlManager($this, new \Tuleap\InstanceBaseURLBuilder());
    }

    /**
     * @return Git_Mirror_ManifestManager
     */
    public function getManifestManager()
    {
        return new Git_Mirror_ManifestManager(
            $this->getMirrorDataMapper(),
            new Git_Mirror_ManifestFileGenerator(
                $this->getLogger(),
                ForgeConfig::get('sys_data_dir') . '/gitolite/grokmirror'
            )
        );
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
            new VersionDetector(),
            new GlobalParameterDao(),
            SystemEventManager::instance()
        );
    }

    public function fill_project_history_sub_events($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        array_push(
            $params['subEvents']['event_others'],
            'git_repo_create',
            'git_repo_delete',
            'git_repo_update',
            'git_repo_mirroring_update',
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

    public function getRESTRepositoryRepresentationBuilder($version)
    {
        $class  = "Tuleap\\Git\\REST\\" . $version . "\\RepositoryRepresentationBuilder";
        if (! class_exists($class)) {
            throw new LogicException("$class cannot be found");
        }
        return new $class(
            $this->getGitPermissionsManager(),
            $this->getGerritServerFactory(),
            $this->getGitLogDao(),
            EventManager::instance(),
            $this->getGitRepositoryUrlManager()
        );
    }

    public function rest_project_get_git($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $class            = "Tuleap\\Git\\REST\\" . $params['version'] . "\\ProjectResource";
        if (! class_exists($class)) {
            throw new LogicException("$class cannot be found");
        }
        $project          = $params['project'];
        $project_resource = new $class(
            $this->getRepositoryFactory(),
            $this->getRESTRepositoryRepresentationBuilder($params['version']),
            new QueryParameterParser(new JsonDecoder())
        );

        $params['result']->repositories = $project_resource->getGit(
            $project,
            $this->getCurrentUser(),
            $params['limit'],
            $params['offset'],
            $params['fields'],
            $params['query'],
            $params['order_by'],
            $params['total_git_repo']
        );
    }

    public function rest_project_options_git($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['activated'] = true;
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

    /**
     * Hook to list archived repositories for restore in site admin page
     *
     * @param array $params
     */
    public function showArchivedRepositories($params)
    {
        $group_id              = $params['group_id'];
        $archived_repositories = $this->getRepositoryManager()->getRepositoriesForRestoreByProjectId($group_id);
        $tab_content           = '';

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
                $tab_content .= '<tr>';
                $tab_content .= '<td>' . $html_purifier->purify($archived_repository->getName()) . '</td>';
                $tab_content .= '<td>' . $html_purifier->purify($archived_repository->getCreationDate()) . '</td>';
                $tab_content .= '<td>' . $html_purifier->purify($archived_repository->getCreator()->getName()) . '</td>';
                $tab_content .= '<td>' . $html_purifier->purify($archived_repository->getDeletionDate()) . '</td>';
                $tab_content .= '<td class="tlp-table-cell-actions">
                                    <form method="post" action="/plugins/git/"
                                    onsubmit="return confirm(\'' . $html_purifier->purify(dgettext('tuleap-git', 'Confirm restore of this Git repository'), CODENDI_PURIFIER_JS_QUOTE) . '\')">
                                        ' . $params['csrf_token']->fetchHTMLInput() . '
                                        <input type="hidden" name="action" value="restore">
                                        <input type="hidden" name="group_id" value="' . $html_purifier->purify($group_id) . '">
                                        <input type="hidden" name="repo_id" value="' . $html_purifier->purify($archived_repository->getId()) . '">
                                        <button class="tlp-table-cell-actions-button tlp-button-small tlp-button-primary tlp-button-outline">
                                            <i class="fa fa-repeat tlp-button-icon"></i> ' . $html_purifier->purify(dgettext('tuleap-git', 'Restore')) . '
                                        </button>
                                    </form>
                                 </td>';
                $tab_content .= '</tr>';
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
        $params['html'][] = $tab_content;
    }

    public function restrictedUsersAreHandledByPluginEvent(RestrictedUsersAreHandledByPluginEvent $event)
    {
        if (strpos($event->getUri(), $this->getPluginPath()) === 0) {
            $event->setPluginHandleRestricted();
        }
    }

    public function get_services_allowed_for_restricted($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['allowed_services'][] = $this->getServiceShortname();
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
            $factory = $this->getGerritServerFactory();
            $gerrit_servers = $factory->getServers();
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
        $logger = new WrapperLogger($params['logger'], "GitXmlImporter");

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
            new GitDao(),
            new XMLImportHelper(UserManager::instance())
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
        $detector = new VersionDetector();
        if ($detector->isGitolite3()) {
            SystemEventManager::instance()->createEvent(
                ParseGitolite3Logs::NAME,
                null,
                SystemEvent::PRIORITY_LOW,
                SystemEvent::OWNER_ROOT,
                '\\Tuleap\\Git\\SystemEvents\\ParseGitolite3Logs'
            );
        }

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

    public function collectGlyphLocations(GlyphLocationsCollector $glyph_locations_collector)
    {
        $glyph_locations_collector->addLocation(
            'tuleap-git',
            new GlyphLocation(GIT_BASE_DIR . '/../glyphs')
        );
    }

    public function collect_heartbeats_entries(HeartbeatsEntryCollection $collection)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $collector = new LatestHeartbeatsCollector(
            $this->getRepositoryFactory(),
            $this->getGitLogDao(),
            $this->getGitRepositoryUrlManager(),
            new \Tuleap\Glyph\GlyphFinder(EventManager::instance()),
            UserManager::instance(),
            UserHelper::instance()
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
        NavigationDropdownQuickLinksCollector $quick_links_collector
    ) {
        $project = $quick_links_collector->getProject();

        if (! $project->usesService(self::SERVICE_SHORTNAME)) {
            return;
        }

        $quick_links_collector->addQuickLink(
            new NavigationDropdownItemPresenter(
                dgettext('tuleap-git', 'Git'),
                $this->getPluginPath() . '/?' . http_build_query(
                    array(
                        'group_id' => $project->getID(),
                        'action'   => 'admin-git-admins'
                    )
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

    private function getJSONRepositoriesRetriever()
    {
        $ugroup_representation_builder = new PermissionPerGroupUGroupRepresentationBuilder($this->getUGroupManager());
        $ugroup_builder = new CollectionOfUGroupRepresentationBuilder(
            $ugroup_representation_builder
        );
        $admin_url_builder = new AdminUrlBuilder();
        $simple_builder = new RepositorySimpleRepresentationBuilder(
            $this->getGitPermissionsManager(),
            $ugroup_builder,
            $admin_url_builder
        );
        $fine_grained_builder = new RepositoryFineGrainedRepresentationBuilder(
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
            new ForgeAccess(PermissionsOverrider_PermissionsOverriderManager::instance()),
            new \User_LoginManager(
                \EventManager::instance(),
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
            new HTTPUserAccessKeyAuthenticator(
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
                EventManager::instance()
            ),
            $this->getIncludeAssets(),
            EventManager::instance()
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
            $this->getMirrorDataMapper(),
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

    public function getIncludeAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../../../src/www/assets/git',
            "/assets/git"
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
            $this->getMirrorDataMapper(),
            $this->getRepositoryManager(),
            $this->getGitPermissionsManager(),
            $this->getFineGrainedPermissionReplicator(),
            new ProjectHistoryDao(),
            $this->getHistoryValueFormatter(),
            $this->getCITokenManager(),
            EventManager::instance()
        );
    }

    public function serviceUrlCollector(ServiceUrlCollector $collector)
    {
        if ($collector->getServiceShortname() === $this->getServiceShortname()) {
            $collector->setUrl(GIT_BASE_URL . "/" . urlencode($collector->getProject()->getUnixNameLowerCase()));
        }
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
        return  new \Tuleap\Git\BigObjectAuthorization\BigObjectAuthorizationManager(
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
}
