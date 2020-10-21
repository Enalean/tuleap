<?php
/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

use Tuleap\Git\Events\GetExternalUsedServiceEvent;
use Tuleap\Git\Permissions\FineGrainedDao;
use Tuleap\Git\Permissions\FineGrainedRetriever;

require_once __DIR__ . '/../vendor/autoload.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class gitlabPlugin extends Plugin
{
    public const SERVICE_NAME = "gitlab";

    public function __construct(?int $id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
        bindtextdomain('tuleap-gitlab', __DIR__ . '/../site-content');
    }


    public function getPluginInfo(): PluginInfo
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new \Tuleap\Gitlab\Plugin\PluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function getHooksAndCallbacks(): Collection
    {
        $this->addHook(GetExternalUsedServiceEvent::NAME);
        return parent::getHooksAndCallbacks();
    }

    public function getDependencies(): array
    {
        return ['git'];
    }

    public function getExternalUsedServiceEvent(GetExternalUsedServiceEvent $event): void
    {
        $project = $event->getProject();
        $is_gitlab_used  = $this->isAllowed((int) $project->getGroupId());

        if (! $is_gitlab_used || ! $this->getGitPermissionsManager()->userIsGitAdmin($event->getUser(), $project)) {
            return;
        }

        $event->addUsedServiceName(self::SERVICE_NAME);
    }

    private function getGitPermissionsManager(): GitPermissionsManager
    {
        $git_system_event_manager = new Git_SystemEventManager(
            SystemEventManager::instance(),
            new GitRepositoryFactory(
                new GitDao(),
                ProjectManager::instance()
            )
        );

        $fine_grained_dao       = new FineGrainedDao();
        $fine_grained_retriever = new FineGrainedRetriever($fine_grained_dao);

        return new GitPermissionsManager(
            new Git_PermissionsDao(),
            $git_system_event_manager,
            $fine_grained_dao,
            $fine_grained_retriever
        );
    }
}
