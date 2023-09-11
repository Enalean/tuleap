<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\BacklogPlugin;

use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\ProgramManagement\Adapter\Workspace\RetrieveFullProject;
use Tuleap\ProgramManagement\Domain\Workspace\ProjectIdentifier;
use Tuleap\ProgramManagement\ProgramService;
use Tuleap\Tracker\Events\SplitBacklogFeatureFlagEvent;

final class BacklogServiceBlocker implements \Tuleap\ProgramManagement\Domain\Workspace\ProgramBlocksBacklogServiceIfNeeded
{
    public function __construct(
        private readonly RetrieveFullProject $project_retriever,
        private readonly EventDispatcherInterface $event_dispatcher,
    ) {
    }

    public function shouldBacklogServiceBeBlocked(ProjectIdentifier $project_identifier): bool
    {
        $project = $this->project_retriever->getProject($project_identifier->getId());
        $event   = $this->event_dispatcher->dispatch(new SplitBacklogFeatureFlagEvent($project));
        if (! $event->isSplitFeatureFlagEnabled()) {
            return false;
        }
        return $project->usesService(ProgramService::SERVICE_SHORTNAME);
    }
}
