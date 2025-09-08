<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Project\Registration\Template\Upload;

use Tuleap\Queue\QueueTask;

final readonly class ProjectCreationFromArchiveTask implements QueueTask
{
    public function __construct(private int $project_id, private string $filename, private int $user_id)
    {
    }

    #[\Override]
    public function getTopic(): string
    {
        return ExtractArchiveAndCreateProject::TOPIC;
    }

    #[\Override]
    public function getPayload(): array
    {
        return ['project_id' => $this->project_id, 'filename' => $this->filename, 'user_id' => $this->user_id];
    }

    #[\Override]
    public function getPreEnqueueMessage(): string
    {
        return "Create project #{$this->project_id} from archive {$this->filename} for user #{$this->user_id}";
    }
}
