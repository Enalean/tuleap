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

namespace Tuleap\Tracker\Artifact\XMLImport;

use Tuleap\Tracker\Action\FieldMapping;

/**
 * @psalm-immutable
 */
final class MoveImportConfig
{
    private function __construct(public array $field_mapping, public bool $is_ducktyping_move)
    {
    }

    public static function buildForRegularImport(): self
    {
        return new self([], false);
    }

    /**
     * @param FieldMapping[] $field_mapping
     */
    public static function buildForMoveArtifact(bool $is_ducktyping_move, array $field_mapping): self
    {
        return new self($field_mapping, $is_ducktyping_move);
    }
}
