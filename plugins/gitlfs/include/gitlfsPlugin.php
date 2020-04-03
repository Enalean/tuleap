<?php
/**
 * Copyright (c) Enalean, 2018-2019. All Rights Reserved.
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

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\CLI\Events\GetWhitelistedKeys;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Git\CollectGitRoutesEvent;
use Tuleap\Git\Permissions\AccessControlVerifier;
use Tuleap\Git\Permissions\FineGrainedDao;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Git\PostInitGitRepositoryWithDataEvent;
use Tuleap\GitLFS\Authorization\Action\ActionAuthorizationDAO;
use Tuleap\GitLFS\Authorization\Action\ActionAuthorizationRemover;
use Tuleap\GitLFS\Authorization\Action\ActionAuthorizationTokenCreator;
use Tuleap\GitLFS\Authorization\Action\ActionAuthorizationVerifier;
use Tuleap\GitLFS\Authorization\LFSAuthorizationTokenHeaderSerializer;
use Tuleap\GitLFS\Authorization\User\UserAuthorizationDAO;
use Tuleap\GitLFS\Authorization\User\UserAuthorizationRemover;
use Tuleap\GitLFS\Authorization\User\UserTokenVerifier;
use Tuleap\GitLFS\HTTP\LSFAPIHTTPAuthorization;
use Tuleap\GitLFS\Batch\Response\BatchSuccessfulResponseBuilder;
use Tuleap\Git\GitPHP\Events\DisplayFileContentInGitView;
use Tuleap\GitLFS\Download\FileDownloaderController;
use Tuleap\GitLFS\GitPHPDisplay\Detector;
use Tuleap\GitLFS\LFSJSONHTTPDispatchable;
use Tuleap\GitLFS\LFSObject\LFSObjectDAO;
use Tuleap\GitLFS\LFSObject\LFSObjectPathAllocator;
use Tuleap\GitLFS\LFSObject\LFSObjectRetriever;
use Tuleap\GitLFS\Statistics\Retriever;
use Tuleap\Project\Quota\ProjectQuotaChecker;
use Tuleap\PullRequest\Events\PullRequestDiffRepresentationBuild;
use Tuleap\Request\CollectRoutesEvent;

require_once __DIR__ . '/../../git/include/gitPlugin.php';
require_once __DIR__ . '/../vendor/autoload.php';

class gitlfsPlugin extends \Plugin // phpcs:ignore
{
    public const SERVICE_SHORTNAME = "tuleap-gitlfs";

    public const SERVICE_LABEL = "Git LFS";

    public const DISPLAY_CONFIG_KEY = 'git_lfs_display_config';
    public const MAX_FILE_SIZE_KEY  = 'git_lfs_max_file_size';

    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
        bindtextdomain('tuleap-gitlfs', __DIR__ . '/../site-content');
    }

    public function getPluginInfo()
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new \Tuleap\GitLFS\Plugin\PluginInfo($this);
        }

        return $this->pluginInfo;
    }

    public function getDependencies()
    {
        return ['git'];
    }

    public function getHooksAndCallbacks()
    {
        $this->addHook(CollectRoutesEvent::NAME);
        $this->addHook(CollectGitRoutesEvent::NAME);
        $this->addHook(\Tuleap\Git\PostInitGitRepositoryWithDataEvent::NAME);
        $this->addHook('codendi_daily_start', 'dailyCleanup');
        $this->addHook('project_is_deleted');
        $this->addHook('site_admin_option_hook');
        $this->addHook('plugin_statistics_disk_usage_collect_project');
        $this->addHook('plugin_statistics_disk_usage_service_label');
        $this->addHook('plugin_statistics_color');
        $this->addHook(DisplayFileContentInGitView::NAME);
        $this->addHook(GetWhitelistedKeys::NAME);
        if (file_exists(__DIR__ . '/../../pullrequest/include/pullrequestPlugin.php')) {
            require_once __DIR__ . '/../../pullrequest/include/pullrequestPlugin.php';
            $this->addHook(PullRequestDiffRepresentationBuild::NAME);
        }
        if (file_exists(__DIR__ . '/../../statistics/include/statisticsPlugin.php')) {
            require_once __DIR__ . '/../../statistics/include/statisticsPlugin.php';
            $this->addHook(\Tuleap\Statistics\Events\StatisticsRefreshDiskUsage::NAME);
        }

        return parent::getHooksAndCallbacks();
    }

    public function routePutUploadsGitLFSObjects(): \Tuleap\GitLFS\Transfer\Basic\LFSBasicTransferUploadController
    {
        return new \Tuleap\GitLFS\Transfer\Basic\LFSBasicTransferUploadController(
            $this->getLFSActionUserAccessRequestChecker(),
            new \Tuleap\GitLFS\Transfer\Basic\LFSBasicTransferObjectSaver(
                $this->getFilesystem(),
                DBFactory::getMainTuleapDBConnection(),
                new LFSObjectRetriever(new \Tuleap\GitLFS\LFSObject\LFSObjectDAO()),
                new LFSObjectPathAllocator(),
                \Tuleap\Instrument\Prometheus\Prometheus::instance()
            )
        );
    }

    public function routeGetGitLFSObjects(): \Tuleap\GitLFS\Transfer\Basic\LFSBasicTransferDownloadController
    {
        return new \Tuleap\GitLFS\Transfer\Basic\LFSBasicTransferDownloadController(
            $this->getLFSActionUserAccessRequestChecker(),
            $this->getFilesystem(),
            new LFSObjectPathAllocator(),
            \Tuleap\Instrument\Prometheus\Prometheus::instance()
        );
    }

    public function routePostGitLFSObjects(): \Tuleap\GitLFS\Transfer\LFSTransferVerifyController
    {
        $lfs_object_dao = new \Tuleap\GitLFS\LFSObject\LFSObjectDAO();
        return new \Tuleap\GitLFS\Transfer\LFSTransferVerifyController(
            $this->getLFSActionUserAccessRequestChecker(),
            new \Tuleap\GitLFS\Transfer\LFSTransferVerifier(
                $this->getFilesystem(),
                new LFSObjectRetriever($lfs_object_dao),
                new LFSObjectPathAllocator(),
                $lfs_object_dao,
                new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection())
            )
        );
    }

    public function routeGetGitLFSConfig(): \Tuleap\GitLFS\Admin\IndexController
    {
        return new \Tuleap\GitLFS\Admin\IndexController(
            new \Tuleap\GitLFS\Admin\AdminDao(),
            new AdminPageRenderer()
        );
    }

    public function routePostGitLFSConfig(): \Tuleap\GitLFS\Admin\IndexPostController
    {
        return new \Tuleap\GitLFS\Admin\IndexPostController(
            new \Tuleap\GitLFS\Admin\AdminDao()
        );
    }

    public function routeGetGitLFSRepositoryObjects(): FileDownloaderController
    {
        return new FileDownloaderController(
            new GitRepositoryFactory(
                new GitDao(),
                ProjectManager::instance()
            ),
            new LFSObjectRetriever(
                new LFSObjectDAO()
            ),
            new LFSObjectPathAllocator(),
            $this->getFilesystem(),
            \Tuleap\Instrument\Prometheus\Prometheus::instance()
        );
    }

    public function collectRoutesEvent(CollectRoutesEvent $event)
    {
        $event->getRouteCollector()->put('/uploads/git-lfs/objects/{oid:[a-fA-F0-9]{64}}', $this->getRouteHandler('routePutUploadsGitLFSObjects'));
        $event->getRouteCollector()->addGroup('/git-lfs', function (FastRoute\RouteCollector $r) {
            $r->get('/objects/{oid:[a-fA-F0-9]{64}}', $this->getRouteHandler('routeGetGitLFSObjects'));
            $r->post('/objects/{oid:[a-fA-F0-9]{64}}/verify', $this->getRouteHandler('routePostGitLFSObjects'));
        });
        $event->getRouteCollector()->get('/plugins/git-lfs/config', $this->getRouteHandler('routeGetGitLFSConfig'));
        $event->getRouteCollector()->post('/plugins/git-lfs/config', $this->getRouteHandler('routePostGitLFSConfig'));
        $event->getRouteCollector()->get('/plugins/git-lfs/{repo_id:[0-9]+}/objects/{oid:[a-fA-F0-9]{64}}', $this->getRouteHandler('routeGetGitLFSRepositoryObjects'));
    }

    public function routePostGitLFSLocks(): LFSJSONHTTPDispatchable
    {
        $logger              = new \WrapperLogger($this->getGitPlugin()->getLogger(), 'LFS Lock');
        $lfs_lock_controller = new \Tuleap\GitLFS\Lock\Controller\LFSLockCreateController(
            $this,
            $this->getGitRepositoryFactory(),
            $this->getLFSAPIHTTPAccessControl(),
            new \Tuleap\GitLFS\Lock\Response\LockResponseBuilder(),
            new \Tuleap\GitLFS\Lock\LockCreator(
                new \Tuleap\GitLFS\Lock\LockDao()
            ),
            new \Tuleap\GitLFS\Lock\LockRetriever(
                new \Tuleap\GitLFS\Lock\LockDao(),
                $this->getUserManager()
            ),
            $this->getUserRetriever($logger),
            \Tuleap\Instrument\Prometheus\Prometheus::instance()
        );
        return new LFSJSONHTTPDispatchable($lfs_lock_controller);
    }

    public function routeGetGitLFSLocks(): LFSJSONHTTPDispatchable
    {
        $logger              = new \WrapperLogger($this->getGitPlugin()->getLogger(), 'LFS Lock');
        $lfs_lock_controller = new \Tuleap\GitLFS\Lock\Controller\LFSLockListController(
            $this,
            $this->getGitRepositoryFactory(),
            $this->getLFSAPIHTTPAccessControl(),
            new \Tuleap\GitLFS\Lock\Response\LockResponseBuilder(),
            new \Tuleap\GitLFS\Lock\LockRetriever(
                new \Tuleap\GitLFS\Lock\LockDao(),
                $this->getUserManager()
            ),
            $this->getUserRetriever($logger)
        );
        return new LFSJSONHTTPDispatchable($lfs_lock_controller);
    }

    public function routePostGitLFSLocksVerify(): LFSJSONHTTPDispatchable
    {
        $logger              = new \WrapperLogger($this->getGitPlugin()->getLogger(), 'LFS Lock');
        $lfs_lock_controller = new \Tuleap\GitLFS\Lock\Controller\LFSLockVerifyController(
            $this,
            $this->getGitRepositoryFactory(),
            $this->getLFSAPIHTTPAccessControl(),
            new \Tuleap\GitLFS\Lock\Response\LockResponseBuilder(),
            new \Tuleap\GitLFS\Lock\LockRetriever(
                new \Tuleap\GitLFS\Lock\LockDao(),
                $this->getUserManager()
            ),
            $this->getUserRetriever($logger)
        );
        return new LFSJSONHTTPDispatchable($lfs_lock_controller);
    }

    public function routePostGitLFSLocksUnlock(): LFSJSONHTTPDispatchable
    {
        $logger              = new \WrapperLogger($this->getGitPlugin()->getLogger(), 'LFS Lock');
        $lfs_lock_controller = new \Tuleap\GitLFS\Lock\Controller\LFSLockDeleteController(
            $this,
            $this->getGitRepositoryFactory(),
            $this->getLFSAPIHTTPAccessControl(),
            new \Tuleap\GitLFS\Lock\Response\LockResponseBuilder(),
            new \Tuleap\GitLFS\Lock\LockDestructor(
                new \Tuleap\GitLFS\Lock\LockDao()
            ),
            new \Tuleap\GitLFS\Lock\LockRetriever(
                new \Tuleap\GitLFS\Lock\LockDao(),
                $this->getUserManager()
            ),
            $this->getUserRetriever($logger),
            \Tuleap\Instrument\Prometheus\Prometheus::instance()
        );
        return new LFSJSONHTTPDispatchable($lfs_lock_controller);
    }

    public function routePostGitLFSObjectsBatch(): LFSJSONHTTPDispatchable
    {
        $logger               = new \WrapperLogger($this->getGitPlugin()->getLogger(), 'LFS Batch');
        $lfs_batch_controller = new \Tuleap\GitLFS\Batch\LFSBatchController(
            $this,
            $this->getGitPlugin()->getRepositoryFactory(),
            $this->getLFSAPIHTTPAccessControl(),
            new BatchSuccessfulResponseBuilder(
                new ActionAuthorizationTokenCreator(new SplitTokenVerificationStringHasher(), new ActionAuthorizationDAO()),
                new LFSAuthorizationTokenHeaderSerializer(),
                new LFSObjectRetriever(new \Tuleap\GitLFS\LFSObject\LFSObjectDAO()),
                new \Tuleap\GitLFS\Admin\AdminDao(),
                new ProjectQuotaChecker(EventManager::instance()),
                $logger,
                \Tuleap\Instrument\Prometheus\Prometheus::instance()
            ),
            $this->getUserRetriever($logger)
        );
        return new LFSJSONHTTPDispatchable($lfs_batch_controller);
    }

    public function collectGitRoutesEvent(CollectGitRoutesEvent $event)
    {
        $event->getRouteCollector()->post('/{project_name}/{path:.*\.git}/info/lfs/locks', $this->getRouteHandler('routePostGitLFSLocks'));
        $event->getRouteCollector()->get('/{project_name}/{path:.*\.git}/info/lfs/locks', $this->getRouteHandler('routeGetGitLFSLocks'));
        $event->getRouteCollector()->post('/{project_name}/{path:.*\.git}/info/lfs/locks/verify', $this->getRouteHandler('routePostGitLFSLocksVerify'));
        $event->getRouteCollector()->post('/{project_name}/{path:.*\.git}/info/lfs/locks/{lock_id}/unlock', $this->getRouteHandler('routePostGitLFSLocksUnlock'));

        $event->getRouteCollector()->post('/{project_name}/{path:.*\.git}/info/lfs/objects/batch', $this->getRouteHandler('routePostGitLFSObjectsBatch'));
    }

    /**
     * @return GitPlugin
     */
    private function getGitPlugin()
    {
        $git_plugin = PluginManager::instance()->getPluginByName('git');
        if ($git_plugin === null) {
            throw new RuntimeException('Git Plugin can not be found but the Git LFS is enabled');
        }
        return $git_plugin;
    }

    /**
     * @return \Tuleap\GitLFS\Transfer\LFSActionUserAccessHTTPRequestChecker
     */
    private function getLFSActionUserAccessRequestChecker()
    {
        return new \Tuleap\GitLFS\Transfer\LFSActionUserAccessHTTPRequestChecker(
            $this,
            new LFSAuthorizationTokenHeaderSerializer(),
            new ActionAuthorizationVerifier(
                new ActionAuthorizationDAO(),
                new SplitTokenVerificationStringHasher(),
                new GitRepositoryFactory(new GitDao(), ProjectManager::instance())
            )
        );
    }

    /**
     * @return League\Flysystem\FilesystemInterface
     */
    private function getFilesystem()
    {
        return new Filesystem(new Local(ForgeConfig::get('sys_data_dir') . '/git-lfs/'));
    }

    public function postInitGitRepositoryWithDataEvent(PostInitGitRepositoryWithDataEvent $event)
    {
        $parent_repository = $event->getRepository()->getParent();
        if ($parent_repository === null) {
            return;
        }
        (new \Tuleap\GitLFS\LFSObject\LFSObjectDAO())->duplicateObjectReferences(
            $event->getRepository()->getId(),
            $parent_repository->getId()
        );
    }

    public function dailyCleanup()
    {
        $this->cleanUnusedResources();
    }

    public function plugin_statistics_disk_usage_service_label($params) // phpcs:ignore
    {
        $params['services'][self::SERVICE_SHORTNAME] = self::SERVICE_LABEL;
    }

    public function plugin_statistics_disk_usage_collect_project($params) // phpcs:ignore
    {
        $this->getStatisticsCollector($params['DiskUsageManager'])
            ->proceedToDiskUsageCollection($params, $params['collect_date']);
    }

    public function plugin_statistics_color($params) // phpcs:ignore
    {
        if ($params['service'] == self::SERVICE_SHORTNAME) {
            $params['color'] = 'chartreuse4';
        }
    }

    private function getStatisticsCollector(Statistics_DiskUsageManager $disk_usage_manager)
    {
        return new \Tuleap\GitLFS\Statistics\Collector(
            $disk_usage_manager->_getDao(),
            new Retriever(
                new \Tuleap\GitLFS\Statistics\LFSStatisticsDAO()
            )
        );
    }

    private function getStatisticsRetriever(): Retriever
    {
        return new Retriever(
            new \Tuleap\GitLFS\Statistics\LFSStatisticsDAO()
        );
    }

    public function project_is_deleted($params) // phpcs:ignore
    {
        $this->cleanUnusedResources();
    }

    private function cleanUnusedResources()
    {
        $current_time       = new \DateTimeImmutable();
        $filesystem         = $this->getFilesystem();
        $path_allocator     = new LFSObjectPathAllocator();
        $lfs_object_remover = new \Tuleap\GitLFS\LFSObject\LFSObjectRemover(
            new \Tuleap\GitLFS\LFSObject\LFSObjectDAO(),
            new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
            $filesystem,
            $path_allocator
        );
        $lfs_object_remover->removeDanglingObjects();
        $action_authorization_remover = new ActionAuthorizationRemover(
            new ActionAuthorizationDAO(),
            $filesystem,
            $path_allocator
        );
        $action_authorization_remover->deleteExpired($current_time);
        $user_authorization_remover = new UserAuthorizationRemover(new UserAuthorizationDAO());
        $user_authorization_remover->deleteExpired($current_time);
    }

    public function site_admin_option_hook($params) //phpcs:ignore
    {
        $config_should_be_displayed = \ForgeConfig::get(\gitlfsPlugin::DISPLAY_CONFIG_KEY, true);
        if ($config_should_be_displayed) {
            $params['plugins'][] = array(
                'label' => dgettext('tuleap-gitlfs', 'Git LFS'),
                'href'  => '/plugins/git-lfs/config'
            );
        }
    }

    public function displayFileContentInGitView(DisplayFileContentInGitView $event)
    {
        $detector = new Detector();

        if ($detector->isFileALFSFile($event->getBlob()->GetData())) {
            $event->setFileIsInSpecialFormat();

            $download_url_builder = new \Tuleap\GitLFS\GitPHPDisplay\DownloadURLBuilder();

            $event->setSpecialDownloadUrl($download_url_builder->buildDownloadURL(
                $event->getRepository(),
                UserManager::instance()->getCurrentUser(),
                $event->getBlob()->GetData()
            ));
        }
    }

    public function pullRequestDiffRepresentationBuild(PullRequestDiffRepresentationBuild $event)
    {
        $detector = new Detector();

        if (
            ($event->getObjectSrc() !== null && $detector->isFileALFSFile($event->getObjectSrc())) ||
            $detector->isFileALFSFile($event->getObjectDest())
        ) {
            $event->setSpecialFormat('git-lfs');
            return;
        }
    }

    public function getWhitelistedKeys(GetWhitelistedKeys $event)
    {
        $event->addPluginsKeys(self::DISPLAY_CONFIG_KEY);
        $event->addPluginsKeys(self::MAX_FILE_SIZE_KEY);
    }

    public function statisticsRefreshDiskUsage(\Tuleap\Statistics\Events\StatisticsRefreshDiskUsage $event): void
    {
        $event->addUsageForService(
            self::SERVICE_SHORTNAME,
            $this->getStatisticsRetriever()->getProjectDiskUsage(
                $this->getProjectManager()->getProject($event->getProjectId()),
                new DateTimeImmutable()
            )
        );
    }

    private function getLFSAPIHTTPAccessControl()
    {
        return new \Tuleap\GitLFS\HTTP\LFSAPIHTTPAccessControl(
            new AccessControlVerifier(new FineGrainedRetriever(new FineGrainedDao()), new \System_Command())
        );
    }

    private function getUserRetriever(\Psr\Log\LoggerInterface $logger): \Tuleap\GitLFS\HTTP\UserRetriever
    {
        return new \Tuleap\GitLFS\HTTP\UserRetriever(
            $this->getLFSAPIHTTPAuthorization(),
            $this->getGitPlugin()->getHTTPAccessControl($logger),
            $this->getUserManager()
        );
    }

    private function getLFSAPIHTTPAuthorization()
    {
        return new LSFAPIHTTPAuthorization(
            new UserTokenVerifier(
                new UserAuthorizationDAO(),
                new SplitTokenVerificationStringHasher(),
                $this->getUserManager()
            ),
            new LFSAuthorizationTokenHeaderSerializer()
        );
    }

    private function getUserManager()
    {
        return UserManager::instance();
    }

    private function getProjectManager(): ProjectManager
    {
        return ProjectManager::instance();
    }

    private function getGitRepositoryFactory()
    {
        return $this->getGitPlugin()->getRepositoryFactory();
    }
}
