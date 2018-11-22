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

use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Git\CollectGitRoutesEvent;
use Tuleap\Git\Permissions\AccessControlVerifier;
use Tuleap\Git\Permissions\FineGrainedDao;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\GitLFS\Authorization\Action\ActionAuthorizationDAO;
use Tuleap\GitLFS\Authorization\Action\ActionAuthorizationTokenCreator;
use Tuleap\GitLFS\Authorization\Action\ActionAuthorizationTokenHeaderSerializer;
use Tuleap\GitLFS\Authorization\Action\ActionAuthorizationVerifier;
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

        return parent::getHooksAndCallbacks();
    }

    public function collectRoutesEvent(CollectRoutesEvent $event)
    {
        $event->getRouteCollector()->addGroup('/git-lfs', function (FastRoute\RouteCollector $r) {
            $r->put('/objects/{oid:[a-fA-F0-9]{64}}', function () {
                return new \Tuleap\GitLFS\Transfer\Basic\LFSBasicTransferUploadController();
            });
        });
    }

    public function collectGitRoutesEvent(CollectGitRoutesEvent $event)
    {
        $event->getRouteCollector()->post('/{project_name}/{path:.*\.git}/info/lfs/objects/batch', function () {
            $logger               = new \WrapperLogger($this->getGitPlugin()->getLogger(), 'LFS Batch');
            $lfs_batch_controller = new \Tuleap\GitLFS\Batch\LFSBatchController(
                $this,
                $this->getGitPlugin()->getRepositoryFactory(),
                new \Tuleap\GitLFS\Batch\LFSBatchAPIHTTPAccessControl(
                    $this->getGitPlugin()->getHTTPAccessControl($logger),
                    \UserManager::instance(),
                    new AccessControlVerifier(new FineGrainedRetriever(new FineGrainedDao()), new \System_Command())
                ),
                new BatchSuccessfulResponseBuilder(
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
}
