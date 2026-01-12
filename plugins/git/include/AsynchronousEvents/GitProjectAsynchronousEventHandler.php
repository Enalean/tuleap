<?php
/**
 * Copyright (c) Enalean, 2026-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Git\AsynchronousEvents;

use CuyZ\Valinor\Mapper\Source\Source;
use CuyZ\Valinor\MapperBuilder;
use Git_GitoliteDriver;
use Tuleap\Project\ProjectByIDFactory;
use Tuleap\Queue\WorkerEvent;

final readonly class GitProjectAsynchronousEventHandler
{
    public function __construct(
        private MapperBuilder $mapper_builder,
        private ProjectByIDFactory $project_retriever,
        private Git_GitoliteDriver $gitolite_driver,
    ) {
    }

    public function handle(WorkerEvent $event): void
    {
        if ($event->getEventName() !== RefreshGitoliteProjectConfigurationTask::TOPIC) {
            return;
        }

        $mapper = $this->mapper_builder->mapper();

        $this->handleGitoliteProjectConfigurationUpdate(
            $mapper->map(RefreshGitoliteProjectConfigurationTask::class, Source::array($event->getPayload()))
        );
    }

    private function handleGitoliteProjectConfigurationUpdate(RefreshGitoliteProjectConfigurationTask $task): void
    {
        $project = $this->project_retriever->getProjectById($task->project_id);
        $this->gitolite_driver->dumpProjectRepoConf($project);
    }
}
