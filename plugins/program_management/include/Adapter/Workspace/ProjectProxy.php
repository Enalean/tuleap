<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\ProgramManagement\Adapter\Workspace;

use Tuleap\ProgramManagement\Domain\ProjectReference;
use Tuleap\Project\Icons\EmojiCodepointConverter;

/**
 * @psalm-immutable
 */
final class ProjectProxy implements ProjectReference
{
    private function __construct(
        private int $project_id,
        private string $project_label,
        private string $project_shortname,
        private string $project_url,
        private string $project_icon,
    ) {
    }

    public static function buildFromProject(\Project $project): self
    {
        return new self(
            (int) $project->getID(),
            $project->getPublicName(),
            $project->getUnixNameLowerCase(),
            $project->getUrl(),
            EmojiCodepointConverter::convertStoredEmojiFormatToEmojiFormat(
                $project->getIconUnicodeCodepoint()
            )
        );
    }

    #[\Override]
    public function getId(): int
    {
        return $this->project_id;
    }

    #[\Override]
    public function getProjectLabel(): string
    {
        return $this->project_label;
    }

    #[\Override]
    public function getUrl(): string
    {
        return $this->project_url;
    }

    #[\Override]
    public function getProjectIcon(): string
    {
        return $this->project_icon;
    }

    #[\Override]
    public function getProjectShortName(): string
    {
        return $this->project_shortname;
    }
}
