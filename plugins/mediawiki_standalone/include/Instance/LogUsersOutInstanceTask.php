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
    private function __construct(private ?int $project_id)
    {
    }

    public static function logsOutUserOnAllInstances(): self
    {
        return new self(null);
    }

    public static function logsOutUserOfAProjectFromItsID(int $project_id, ProjectByIDFactory $project_factory): ?self
    {
        try {
            $project = $project_factory->getValidProjectById($project_id);
        } catch (\Project_NotFoundException $e) {
            return null;
        }
        if (! $project->usesService(MediawikiStandaloneService::SERVICE_SHORTNAME)) {
            return null;
        }
        return new self($project_id);
    }

    public function getTopic(): string
    {
        return LogUsersOutInstance::TOPIC;
    }

    public function getPayload(): array
    {
        return ['project_id' => $this->project_id];
    }

    public function getPreEnqueueMessage(): string
    {
        if ($this->project_id === null) {
            return 'Log-out users of all MediaWiki instances';
        }
        return 'Log-out users of MediaWiki instance #' . $this->project_id;
    }
}
