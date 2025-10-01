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

use Tuleap\Queue\QueueTask;

/**
 * @psalm-immutable
 */
final class CreateInstanceTask implements QueueTask
{
    private readonly int $project_id;
    private readonly string $short_language_code;

    public function __construct(\Project $project, InitializationLanguageCodeProvider $language_code_provider)
    {
        $this->project_id          = (int) $project->getID();
        $this->short_language_code = $language_code_provider->getLanguageCode();
    }

    #[\Override]
    public function getTopic(): string
    {
        return CreateInstance::TOPIC;
    }

    #[\Override]
    public function getPayload(): array
    {
        return ['project_id' => $this->project_id, 'language_code' => $this->short_language_code];
    }

    #[\Override]
    public function getPreEnqueueMessage(): string
    {
        return 'Create MediaWiki instance #' . $this->project_id;
    }
}
