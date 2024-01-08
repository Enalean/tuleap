<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\SVNCore\Event;

use Psr\EventDispatcher\EventDispatcherInterface;

final class UpdateProjectAccessFileSystemEvent extends \SystemEvent
{
    public const NAME = 'UPDATE_SVN_ACCESS_FILE';

    /**
     * @var \ProjectManager
     */
    private $project_manager;
    /**
     * @var EventDispatcherInterface
     */
    private $event_dispatcher;

    private function getProjectIdFromParameters(): int
    {
        $parameters = $this->getParametersAsArray();

        return (int) $parameters[0];
    }

    public function verbalizeParameters($with_link): string
    {
        $project_id = $this->getProjectIdFromParameters();

        return 'Project: ' . $this->verbalizeProjectId($project_id, $with_link);
    }

    public function injectDependencies(\ProjectManager $project_manager, EventDispatcherInterface $event_dispatcher): void
    {
        $this->project_manager  = $project_manager;
        $this->event_dispatcher = $event_dispatcher;
    }

    public function process(): void
    {
        $project_id = $this->getProjectIdFromParameters();
        $project    = $this->project_manager->getProject($project_id);

        if ($project === null) {
            $this->error('Project does not exist');
            return;
        }

        $this->event_dispatcher->dispatch(
            new UpdateProjectAccessFilesEvent($project)
        );
        $this->done();
    }
}
