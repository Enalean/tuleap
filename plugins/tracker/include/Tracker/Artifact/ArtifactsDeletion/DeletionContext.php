<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\ArtifactsDeletion;

/**
 * @psalm-immutable
 */
final class DeletionContext
{
    public const MOVE_TYPE = 'move';

    private function __construct(private readonly bool $is_in_move_context, private readonly int $source_project_id, private readonly int $destination_project_id)
    {
    }

    public static function moveContext(int $source_project_id, int $destination_project_id): self
    {
        return new self(true, $source_project_id, $destination_project_id);
    }

    public static function regularDeletion(int $project_id): self
    {
        return new self(false, $project_id, $project_id);
    }

    public function isAnArtifactMove(): bool
    {
        return $this->is_in_move_context;
    }

    public function getType(): string
    {
        if ($this->is_in_move_context) {
            return self::MOVE_TYPE;
        }

        return "regular";
    }

    public function getSourceProjectId(): int
    {
        return $this->source_project_id;
    }

    public function getDestinationProjectId(): int
    {
        return $this->destination_project_id;
    }
}
