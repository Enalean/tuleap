<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
use Tuleap\GitLFS\Batch\LSFBatchAPIHTTPAuthorization;
use Tuleap\GitLFS\Batch\Response\BatchSuccessfulResponseBuilder;
use Tuleap\Request\CollectRoutesEvent;

require_once __DIR__ . '/../../git/include/gitPlugin.class.php';
require_once __DIR__ . '/../vendor/autoload.php';

class gitlfsPlugin extends \Plugin // phpcs:ignore
{
    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
        bindtextdomain('tuleap-gitlfs', __DIR__.'/../site-content');
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

        return parent::getHooksAndCallbacks();
    }

    public function collectRoutesEvent(CollectRoutesEvent $event)
    {
        $event->getRouteCollector()->put('/uploads/git-lfs/objects/{oid:[a-fA-F0-9]{64}}', function () {
            return new \Tuleap\GitLFS\Transfer\Basic\LFSBasicTransferUploadController(
                $this->getLFSActionUserAccessRequestChecker(),
                new \Tuleap\GitLFS\Transfer\AuthorizedActionStore(),
                new \Tuleap\GitLFS\Transfer\Basic\LFSBasicTransferObjectSaver(
                    $this->getFilesystem(),
                    new \Tuleap\GitLFS\LFSObject\LFSObjectRetriever(new \Tuleap\GitLFS\LFSObject\LFSObjectDAO()),
                    new \Tuleap\GitLFS\LFSObject\LFSObjectPathAllocator()
                )
            );
        });
        $event->getRouteCollector()->addGroup('/git-lfs', function (FastRoute\RouteCollector $r) {
            $r->get('/objects/{oid:[a-fA-F0-9]{64}}', function () {
                return new \Tuleap\GitLFS\Transfer\Basic\LFSBasicTransferDownloadController(
                    $this->getLFSActionUserAccessRequestChecker(),
                    new \Tuleap\GitLFS\Transfer\AuthorizedActionStore(),
                    $this->getFilesystem(),
                    new \Tuleap\GitLFS\LFSObject\LFSObjectPathAllocator()
                );
            });
            $r->post('/objects/{oid:[a-fA-F0-9]{64}}/verify', function () {
                $lfs_object_dao = new \Tuleap\GitLFS\LFSObject\LFSObjectDAO();
                return new \Tuleap\GitLFS\Transfer\LFSTransferVerifyController(
                    $this->getLFSActionUserAccessRequestChecker(),
                    new \Tuleap\GitLFS\Transfer\AuthorizedActionStore(),
                    new \Tuleap\GitLFS\Transfer\LFSTransferVerifier(
                        $this->getFilesystem(),
                        new \Tuleap\GitLFS\LFSObject\LFSObjectRetriever($lfs_object_dao),
                        new \Tuleap\GitLFS\LFSObject\LFSObjectPathAllocator(),
                        $lfs_object_dao
                    )
                );
            });
        });
        $event->getRouteCollector()->get('/plugins/git-lfs/config', function () {
            return new \Tuleap\GitLFS\Admin\IndexController(
                new \Tuleap\GitLFS\Admin\AdminDao(),
                new AdminPageRenderer()
            );
        });
        $event->getRouteCollector()->post('/plugins/git-lfs/config', function () {
            return new \Tuleap\GitLFS\Admin\IndexPostController(
                new \Tuleap\GitLFS\Admin\AdminDao()
            );
        });
    }

    public function collectGitRoutesEvent(CollectGitRoutesEvent $event)
    {
        $event->getRouteCollector()->post('/{project_name}/{path:.*\.git}/info/lfs/objects/batch', function () {
            $logger               = new \WrapperLogger($this->getGitPlugin()->getLogger(), 'LFS Batch');
            $user_manager         = UserManager::instance();
            $lfs_batch_controller = new \Tuleap\GitLFS\Batch\LFSBatchController(
                $this,
                $this->getGitPlugin()->getRepositoryFactory(),
                new \Tuleap\GitLFS\Batch\LFSBatchAPIHTTPAccessControl(
                    new LSFBatchAPIHTTPAuthorization(
                        new UserTokenVerifier(
                            new UserAuthorizationDAO(),
                            new SplitTokenVerificationStringHasher(),
                            $user_manager
                        ),
                        new LFSAuthorizationTokenHeaderSerializer()
                    ),
                    $this->getGitPlugin()->getHTTPAccessControl($logger),
                    $user_manager,
                    new AccessControlVerifier(new FineGrainedRetriever(new FineGrainedDao()), new \System_Command())
                ),
                new BatchSuccessfulResponseBuilder(
                    new ActionAuthorizationTokenCreator(new SplitTokenVerificationStringHasher(), new ActionAuthorizationDAO()),
                    new LFSAuthorizationTokenHeaderSerializer(),
                    new \Tuleap\GitLFS\LFSObject\LFSObjectRetriever(new \Tuleap\GitLFS\LFSObject\LFSObjectDAO()),
                    new \Tuleap\GitLFS\Admin\AdminDao(),
                    $logger
                )
            );
            return new \Tuleap\GitLFS\LFSJSONHTTPDispatchable($lfs_batch_controller);
        });
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

    public function project_is_deleted($params) // phpcs:ignore
    {
        $this->cleanUnusedResources();
    }

    private function cleanUnusedResources()
    {
        $current_time       = new \DateTimeImmutable();
        $filesystem         = $this->getFilesystem();
        $path_allocator     = new \Tuleap\GitLFS\LFSObject\LFSObjectPathAllocator();
        $lfs_object_remover = new \Tuleap\GitLFS\LFSObject\LFSObjectRemover(
            new \Tuleap\GitLFS\LFSObject\LFSObjectDAO(),
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
        $params['plugins'][] = array(
            'label' => dgettext('tuleap-gitlfs', 'Git LFS'),
            'href'  => '/plugins/git-lfs/config'
        );
    }
}
