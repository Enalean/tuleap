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

namespace Tuleap\Tracker\REST\Artifact;

final class ArtifactFieldValueDateFullRepresentation extends ArtifactFieldValueRepresentation
{
    private function __construct(public readonly string $type, public readonly bool $is_time_displayed)
    {
    }

    public static function fromDatetimeInfo(int $id, string $type, string $label, ?string $value, bool $is_time_displayed): self
    {
        $representation = new self($type, $is_time_displayed);
        $representation->build($id, $label, $value);

        return $representation;
    }
}
