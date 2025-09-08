<?php
/**
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
 */

declare(strict_types=1);

namespace Tuleap\MediawikiStandalone\Instance;

use Tuleap\MediawikiStandalone\Service\MediawikiStandaloneService;
use Tuleap\Project\ProjectByIDFactory;
use Tuleap\Queue\QueueTask;

/**
 * @psalm-immutable
 */
final class LogUsersOutInstanceTask implements QueueTask
{
    private function __construct(private ?int $project_id, private ?int $user_id)
    {
    }

    public static function logsOutUserOnAllInstances(): self
    {
        return new self(null, null);
    }

    public static function logsOutUserOfAProjectFromItsID(int $project_id, ProjectByIDFactory $project_factory): ?self
    {
        try {
            $project = $project_factory->getValidProjectById($project_id);
        } catch (\Project_NotFoundException $e) {
            return null;
        }

        return self::logsOutUserOfAProject($project);
    }

    public static function logsOutUserOfAProject(\Project $project): ?self
    {
        if (! $project->usesService(MediawikiStandaloneService::SERVICE_SHORTNAME)) {
            return null;
        }
        return new self((int) $project->getID(), null);
    }

    public static function logsSpecificUserOutOfAProjectFromItsID(int $project_id, ProjectByIDFactory $project_factory, int $user_id): ?self
    {
        $task = self::logsOutUserOfAProjectFromItsID($project_id, $project_factory);
        if ($task === null) {
            return null;
        }
        $task          = clone $task;
        $task->user_id = $user_id;

        return $task;
    }

    public static function logsSpecificUserOutOfAllProjects(int $user_id): self
    {
        return new self(null, $user_id);
    }

    #[\Override]
    public function getTopic(): string
    {
        return LogUsersOutInstance::TOPIC;
    }

    #[\Override]
    public function getPayload(): array
    {
        return ['project_id' => $this->project_id, 'user_id' => $this->user_id];
    }

    #[\Override]
    public function getPreEnqueueMessage(): string
    {
        $log_out_phrase = 'Log-out users';
        if ($this->user_id !== null) {
            $log_out_phrase = 'Log-out user #' . $this->user_id;
        }

        if ($this->project_id === null) {
            return $log_out_phrase . ' of all MediaWiki instances';
        }
        return $log_out_phrase . ' of MediaWiki instance #' . $this->project_id;
    }
}
