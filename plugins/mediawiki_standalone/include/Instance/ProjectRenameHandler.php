<?php
/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\MediawikiStandalone\Instance;

use Tuleap\MediawikiStandalone\Service\MediawikiStandaloneService;
use Tuleap\Project\ProjectByIDFactory;
use Tuleap\Queue\EnqueueTaskInterface;

final class ProjectRenameHandler
{
    public function __construct(private EnqueueTaskInterface $enqueue_task, private ProjectByIDFactory $project_factory)
    {
    }

    public function handle(int $project_id, string $new_name): void
    {
        try {
            $project = $this->project_factory->getValidProjectById($project_id);
            if (! $project->usesService(MediawikiStandaloneService::SERVICE_SHORTNAME)) {
                return;
            }
            $this->enqueue_task->enqueue(new RenameInstanceTask($project, $new_name));
        } catch (\Project_NotFoundException) {
        }
    }
}
