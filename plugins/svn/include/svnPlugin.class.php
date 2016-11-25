<?php
/**
 * Copyright (c) Enalean, 2015-2016. All Rights Reserved.
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

require_once 'constants.php';

use Tuleap\Svn\Admin\Destructor;
use Tuleap\Svn\Admin\RestoreController;
use Tuleap\Svn\EventRepository\SystemEvent_SVN_DELETE_REPOSITORY;
use Tuleap\Svn\EventRepository\SystemEvent_SVN_RESTORE_REPOSITORY;
use Tuleap\Svn\Repository\HookDao;
use Tuleap\Svn\SvnRouter;
use Tuleap\Svn\Repository\RepositoryManager;
use Tuleap\Svn\AccessControl\AccessFileHistoryCreator;
use Tuleap\Svn\AccessControl\AccessFileHistoryFactory;
use Tuleap\Svn\AccessControl\AccessFileHistoryDao;
use Tuleap\Svn\Repository\RepositoryRegexpBuilder;
use Tuleap\Svn\Dao;
use Tuleap\Svn\SvnPermissionManager;
use Tuleap\Svn\EventRepository\SystemEvent_SVN_CREATE_REPOSITORY;
use Tuleap\Svn\Admin\MailHeaderManager;
use Tuleap\Svn\Admin\MailHeaderDao;
use Tuleap\Svn\Admin\MailNotificationDao;
use Tuleap\Svn\Admin\MailNotificationManager;
use Tuleap\Svn\Admin\ImmutableTagController;
use Tuleap\Svn\Explorer\ExplorerController;
use Tuleap\Svn\Explorer\RepositoryDisplayController;
use Tuleap\Svn\Admin\AdminController;
use Tuleap\Svn\Admin\GlobalAdminController;
use Tuleap\Svn\Admin\ImmutableTagCreator;
use Tuleap\Svn\Admin\ImmutableTagFactory;
use Tuleap\Svn\Admin\ImmutableTagDao;
use Tuleap\Svn\AccessControl\AccessControlController;
use Tuleap\Svn\Reference\Extractor;
use Tuleap\Svn\ViewVC\ViewVCProxyFactory;
use Tuleap\Svn\XMLImporter;
use Tuleap\Svn\SvnLogger;
use Tuleap\Svn\SvnAdmin;
use Tuleap\Svn\Repository\RuleName;
use Tuleap\Svn\Commit\Svnlook;
use Tuleap\Svn\ViewVC\AccessHistorySaver;
use Tuleap\Svn\ViewVC\AccessHistoryDao;
use Tuleap\Svn\Logs\QueryBuilder;
use Tuleap\ViewVCVersionChecker;
use Tuleap\Svn\Service\ServiceActivator;
/**
 * SVN plugin
 */
class SvnPlugin extends Plugin {

    const SERVICE_SHORTNAME  = 'plugin_svn';
    const SYSTEM_NATURE_NAME = 'svn_revision';

    /** @var Tuleap\Svn\Repository\RepositoryManager */
    private $repository_manager;

    /** @var Tuleap\Svn\Admin\AccessControl\AccessFileHistoryDao */
    private $accessfile_dao;

    /** @var Tuleap\Svn\Admin\AccessControl\AccessFileHistoryFactory */
    private $accessfile_factory;

    /** @var Tuleap\Svn\Admin\AccessControl\AccessFileHistoryCreator */
    private $accessfile_history_creator;

    /** @var Tuleap\Svn\Admin\MailNotificationManager */
    private $mail_notification_manager;

    /** @var UGroupManager */
    private $ugroup_manager;

    /** @var PermissionsManager */
    private $permissions_manager;


    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(Plugin::SCOPE_PROJECT);
        $this->addHook(Event::SERVICE_ICON);
        $this->addHook(Event::SERVICE_CLASSNAMES);
        $this->addHook(Event::SERVICES_ALLOWED_FOR_PROJECT);
        $this->addHook(Event::SYSTEM_EVENT_GET_TYPES_FOR_DEFAULT_QUEUE);
        $this->addHook(Event::GET_SYSTEM_EVENT_CLASS);
        $this->addHook(Event::GET_SVN_LIST_REPOSITORIES_SQL_FRAGMENTS);
        $this->addHook(Event::UGROUP_MODIFY);
        $this->addHook(Event::MEMBERSHIP_CREATE);
        $this->addHook(Event::MEMBERSHIP_DELETE);
        $this->addHook(Event::IMPORT_XML_PROJECT);
        $this->addHook('cssfile');
        $this->addHook('javascript_file');
        $this->addHook('codendi_daily_start');
        $this->addHook('show_pending_documents');
        $this->addHook('project_is_deleted');
        $this->addHook('logs_daily');
        $this->addHook('statistics_collector');
        $this->addHook('plugin_statistics_service_usage');

        $this->addHook(Event::GET_REFERENCE);
        $this->addHook(Event::SVN_REPOSITORY_CREATED);

        $this->addHook(ProjectCreator::PROJECT_CREATION_REMOVE_LEGACY_SERVICES);
    }

    public function getPluginInfo() {
        if (!is_a($this->pluginInfo, 'SvnPluginInfo')) {
            $this->pluginInfo = new SvnPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function getServiceShortname() {
        return self::SERVICE_SHORTNAME;
    }

    public function getTypes() {
        return array(
            SystemEvent_SVN_CREATE_REPOSITORY::NAME,
            SystemEvent_SVN_DELETE_REPOSITORY::NAME,
            SystemEvent_SVN_RESTORE_REPOSITORY::NAME
        );
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


    /** @see Event::UGROUP_MODIFY */
    public function ugroup_modify(array $params) {
        $project         = $params['project'];
        $new_ugroup_name = $params['new_ugroup_name'];
        $old_ugroup_name = $params['old_ugroup_name'];

        $this->updateAllAccessFileOfProject($project, $params['new_ugroup_name'], $params['old_ugroup_name']);
    }

    /** @see Event::MEMBERSHIP_CREATE */
    public function membership_create(array $params) {
        $project         = $params['project'];
        $new_ugroup_name = null;
        $old_ugroup_name = null;

        $this->updateAllAccessFileOfProject($project, $new_ugroup_name, $old_ugroup_name);
    }

    /** @see Event::MEMBERSHIP_DELETE */
    public function membership_delete(array $params) {
        $project         = $params['project'];
        $new_ugroup_name = null;
        $old_ugroup_name = null;

        $this->updateAllAccessFileOfProject($project, $new_ugroup_name, $old_ugroup_name);
    }

    private function updateAllAccessFileOfProject(Project $project, $new_ugroup_name, $old_ugroup_name) {
        $list_repositories = $this->getRepositoryManager()->getRepositoriesInProject($project);
        foreach ($list_repositories as $repository) {
            $this->getBackendSVN()->updateSVNAccessForRepository(
                $project,
                $repository->getSystemPath(),
                $new_ugroup_name,
                $old_ugroup_name,
                $repository->getFullName()
            );
        }
    }

    public function get_svn_list_repositories_sql_fragments(array $params) {
        $dao = new Dao();
        $params['sql_fragments'][] = $dao->getListRepositoriesSqlFragment();
    }

    public function system_event_get_types_for_default_queue($params) {
        $params['types'][] = 'Tuleap\\Svn\\EventRepository\\'.SystemEvent_SVN_CREATE_REPOSITORY::NAME;
        $params['types'][] = 'Tuleap\\Svn\\EventRepository\\'.SystemEvent_SVN_DELETE_REPOSITORY::NAME;
        $params['types'][] = 'Tuleap\\Svn\\EventRepository\\'.SystemEvent_SVN_RESTORE_REPOSITORY::NAME;
    }

    public function get_system_event_class($params)
    {
        switch ($params['type']) {
            case 'SVN_CREATE_REPOSITORY':
                include_once dirname(__FILE__).'/events/SystemEvent_SVN_CREATE_REPOSITORY.class.php';
                $params['class'] = 'SystemEvent_SVN_CREATE_REPOSITORY';
                $params['dependencies'] = array(
                    $this->getBackendSVN()
                );
                break;
            case 'SVN_DELETE_REPOSITORY':
                include_once dirname(__FILE__).'/events/SystemEvent_SVN_DELETE_REPOSITORY.class.php';
                $params['class'] = 'SystemEvent_SVN_DELETE_REPOSITORY';
                $params['dependencies'] = array(
                    $this->getRepositoryManager(),
                    ProjectManager::instance()
                );
                break;
        }
    }

    /** @return Tuleap\Svn\Repository\RepositoryManager */
    private function getRepositoryManager()
    {
        if (empty($this->repository_manager)) {
            $this->repository_manager = new RepositoryManager(
                new Dao(),
                ProjectManager::instance(),
                new SvnAdmin(new System_Command(), new SvnLogger()),
                new SvnLogger(),
                new System_Command(),
                new Destructor(
                    new Dao(),
                    new SvnLogger()
                ),
                new HookDao(),
                EventManager::instance(),
                Backend::instance(Backend::SVN),
                new AccessFileHistoryFactory(new AccessFileHistoryDao()),
                SystemEventManager::instance()
            );
        }

        return $this->repository_manager;
    }

    /** @return Tuleap\Svn\Admin\AccessControl\AccessFileHistoryDao */
    private function getAccessFileHistoryDao(){
        if(empty($this->accessfile_dao)){
            $this->accessfile_dao = new AccessFileHistoryDao();
        }
        return $this->accessfile_dao;
    }

    /** @return Tuleap\Svn\Admin\AccessControl\AccessFileHistoryFactory */
    private function getAccessFileHistoryFactory(){
        if(empty($this->accessfile_factory)){
            $this->accessfile_factory = new AccessFileHistoryFactory($this->getAccessFileHistoryDao());
        }
        return $this->accessfile_factory;
    }

    /** @return Tuleap\Svn\Admin\AccessControl\AccessFileHistoryCreator */
    private function getAccessFileHistoryCreator() {
        if(empty($this->accessfile_history_manager)) {
            $this->accessfile_history_creator = new AccessFileHistoryCreator(
                $this->getAccessFileHistoryDao(), $this->getAccessFileHistoryFactory());
        }
        return $this->accessfile_history_creator;
    }

    /** @return Tuleap\Svn\Admin\MailNotificationManager */
    private function getMailNotificationManager() {
        if (empty($this->mail_notification_manager)) {
            $this->mail_notification_manager = new MailNotificationManager(
                new MailNotificationDao(CodendiDataAccess::instance(), new RepositoryRegexpBuilder())
            );
        }
        return $this->mail_notification_manager;
    }

    /**
     * @return UGroupManager
     */
    private function getUGroupManager()
    {
        if (empty($this->ugroup_manager)) {
            $this->ugroup_manager = new UGroupManager();
        }
        return $this->ugroup_manager;
    }

    /**
     * @return PermissionsManager
     */
    private function getPermissionsManager()
    {
        if (empty($this->permissions_manager)) {
            $this->permissions_manager = new SvnPermissionManager($this->getForgeUserGroupFactory(), PermissionsManager::instance());
        }
        return $this->permissions_manager;
    }

    private function getForgeUserGroupFactory()
    {
        return new User_ForgeUserGroupFactory(new UserGroupDao());
    }

    /**
     * @return ViewVCVersionChecker
     */
    private function getViewVCVersionChecker()
    {
        return new ViewVCVersionChecker();
    }


    public function process(HTTPRequest $request)
    {
        $project = $request->getProject();
        if (! $project->getID()) {
            $project = $this->getProjectFromViewVcURL($request);
        }

        $project_id = $project->getId();
        if (! PluginManager::instance()->isPluginAllowedForProject($this, $project_id)) {
            $GLOBALS['Response']->addFeedback(
                'error', $GLOBALS['Language']->getText(
                'plugin_svn_manage_repository', 'plugin_not_activated'
            )
            );
            $GLOBALS['Response']->redirect('/projects/' . $project->getUnixNameMixedCase() . '/');
        } else {
            $this->getRouter()->route($request);
        }
    }

    private function getProjectFromViewVcURL(HTTPRequest $request)
    {
        $svn_root          = $request->get('root');
        $project_shortname = substr($svn_root, 0, strpos($svn_root, '/'));
        $project           = ProjectManager::instance()->getValidProjectByShortNameOrId($project_shortname);

        return $project;
    }

    public function cssFile($params) {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            $viewvc_version_checker = $this->getViewVCVersionChecker();
            if ($viewvc_version_checker->isTuleapViewVCInstalled()) {
                echo '<link rel="stylesheet" type="text/css" href="/viewvc-static/styles.css" />';
            }
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />';
        }
    }

    public function javascript_file() {
        // Only show the javascript if we're actually in the svn pages.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            echo '<script type="text/javascript" src="'.$this->getPluginPath().'/scripts/svn.js"></script>';
        }
    }

    public function service_icon($params) {
        $params['list_of_icon_unicodes'][$this->getServiceShortname()] = '\e804';
    }

    public function service_classnames(array $params) {
        $params['classnames'][$this->getServiceShortname()] = 'Tuleap\Svn\ServiceSvn';
    }

    /**
     *
     * @param array $params
     * @see Event::IMPORT_XML_PROJECT
     */
    public function import_xml_project($params) {
        $xml = $params['xml_content'];
        $extraction_path = $params['extraction_path'];
        $project = $params['project'];
        $logger = $params['logger'];

        $svn = new XMLImporter(Backend::instance(), $xml, $extraction_path);
        $svn->import(
            $params['configuration'],
            $logger,
            $project,
            $this->getRepositoryManager(),
            SystemEventManager::instance(),
            $this->getAccessFileHistoryCreator(),
            $this->getMailNotificationManager(),
            new RuleName($project, new Dao())
        );
    }

    private function getRouter()
    {
        $repository_manager  = $this->getRepositoryManager();
        $ugroup_manager      = $this->getUGroupManager();
        $permissions_manager = $this->getPermissionsManager();

        return new SvnRouter(
            $repository_manager,
            $ugroup_manager,
            $permissions_manager,
            new AccessControlController(
                $repository_manager,
                $this->getAccessFileHistoryFactory(),
                $this->getAccessFileHistoryCreator()
            ),
            new AdminController(
                new MailHeaderManager(new MailHeaderDao()),
                $repository_manager,
                $this->getMailNotificationManager(),
                new SvnLogger()
            ),
            new ExplorerController(
                $repository_manager,
                $permissions_manager
            ),
            new RepositoryDisplayController(
                $repository_manager,
                ProjectManager::instance(),
                $permissions_manager,
                new AccessHistorySaver(new AccessHistoryDao()),
                new ViewVCProxyFactory($this->getViewVCVersionChecker()),
                EventManager::instance()
            ),
            new ImmutableTagController(
                $repository_manager,
                new Svnlook(new System_Command()),
                new ImmutableTagCreator(new ImmutableTagDao()),
                new ImmutableTagFactory(new ImmutableTagDao())
            ),
            new GlobalAdminController(
                $this->getForgeUserGroupFactory(),
                $permissions_manager
            ),
            new RestoreController($this->getRepositoryManager())
        );
    }

    /** @return BackendSVN */
    private function getBackendSVN() {
        return Backend::instance(Backend::SVN);
    }

    public function get_reference($params) {
        $keyword = $params['keyword'];

        if ($this->isReferenceASubversionReference($keyword)) {
            $project = $params['project'];
            $value   = $params['value'];

            $extractor = $this->getReferenceExtractor();
            $reference = $extractor->getReference($project, $keyword, $value);

            if ($reference) {
                $params['reference'] = $reference;
            }
        }

    }

    private function getReferenceExtractor() {
        return new Extractor($this->getRepositoryManager());
    }

    private function isReferenceASubversionReference($keyword) {
        $dao    = new ReferenceDao();
        $result = $dao->searchSystemReferenceByNatureAndKeyword($keyword, self::SYSTEM_NATURE_NAME);

        if (! $result || $result->rowCount() < 1) {
            return false;
        }

        return true;
    }

    public function svn_repository_created($params)
    {
        $backend           = Backend::instance();
        $svn_plugin_folder = ForgeConfig::get('sys_data_dir') .'/svn_plugin/';
        $project_id        = $params['project_id'];

        $backend->chown($svn_plugin_folder, $backend->getHTTPUser());
        $backend->chgrp($svn_plugin_folder, $backend->getHTTPUser());

        $svn_project_folder = $svn_plugin_folder . $project_id;

        $backend->chown($svn_project_folder, $backend->getHTTPUser());
        $backend->chgrp($svn_project_folder, $backend->getHTTPUser());
    }

    public function project_is_deleted($params)
    {
        if (! empty($params['group_id'])) {
            $project = ProjectManager::instance()->getProject($params['group_id']);
            if ($project) {
                $this->getRepositoryManager()->deleteProjectRepositories($project);
            }
        }
    }

    public function codendi_daily_start()
    {
        $this->getRepositoryManager()->purgeArchivedRepositories();
    }

    public function show_pending_documents($params)
    {
        $project_id            = $params['group_id'];
        $project               = ProjectManager::instance()->getProject($project_id);
        $archived_repositories = $this->getRepositoryManager()->getRestorableRepositoriesByProject($project);

        $restore_controller = new RestoreController($this->getRepositoryManager());
        $tab_content        = $restore_controller->displayRestorableRepositories($archived_repositories, $project_id);
        $params['html'][]   = $tab_content;
    }

    public function logs_daily($params)
    {
        $project_manager = ProjectManager::instance();
        $project         = $project_manager->getProject($params['group_id']);
        if ($project->usesService(self::SERVICE_SHORTNAME)) {
            $builder = new QueryBuilder();
            $query  = $builder->buildQuery($project, $params['span'], $params['who']);

             $params['logs'][] = array(
                'sql'   => $query,
                'field' => $GLOBALS['Language']->getText('plugin_svn', 'logsdaily_field'),
                'title' => $GLOBALS['Language']->getText('plugin_svn', 'logsdaily_title')
            );
        }
    }

    public function statistics_collector(array $params)
    {
        if (! empty($params['formatter']))
        {
            $statistic_dao       = new \Tuleap\Svn\Statistic\SCMUsageDao();
            $statistic_collector = new \Tuleap\Svn\Statistic\SCMUsageCollector($statistic_dao);

            echo $statistic_collector->collect($params['formatter']);
        }
    }

    public function plugin_statistics_service_usage(array $params)
    {
        $statistic_dao       = new \Tuleap\Svn\Statistic\ServiceUsageDao();
        $statistic_collector = new \Tuleap\Svn\Statistic\ServiceUsageCollector($statistic_dao);
        $statistic_collector->collect($params['csv_exporter'], $params['start_date'], $params['end_date']);
    }

    public function project_creation_remove_legacy_services($params)
    {
        if (! $this->isRestricted()) {
            $activator = new ServiceActivator(ServiceManager::instance());
            $activator->unuseLegacyService($params);
        }
    }
}
