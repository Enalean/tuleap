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

namespace Tuleap\Artidoc\REST\v1;

/**
 * @psalm-immutable
 */
final class PUTConfigurationRepresentation
{
    /**
     * @var int[] Selected trackers for the document {@required true} {@min 1} {@max 1} {@type int}
     * @psalm-var array{0:int} $selected_tracker_ids
     */
    public array $selected_tracker_ids;

    /**
     * @var ConfiguredFieldRepresentation[] Selected artifact fields for the document {@type \Tuleap\Artidoc\REST\v1\ConfiguredFieldRepresentation} {@required true}
     * @psalm-var ConfiguredFieldRepresentation[]
     */
    public array $fields;

    /**
     * @param int[] $selected_tracker_ids
     * @param ConfiguredFieldRepresentation[] $fields
     */
    public function __construct(array $selected_tracker_ids, array $fields)
    {
        $this->selected_tracker_ids = $selected_tracker_ids;
        $this->fields               = $fields;
    }
}
