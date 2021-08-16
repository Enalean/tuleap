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

namespace Tuleap\ProgramManagement\Domain\Program\Admin\Configuration;

/**
 * @psalm-immutable
 */
final class SemanticErrorPresenter
{
    public string $semantic_name;
    public string $semantic_short_name;
    /**
     * @var int[]
     */
    public array $potential_trackers_in_error;

    /**
     * @param int[]  $potential_trackers_in_error
     */
    public function __construct(string $semantic_name, string $semantic_short_name, array $potential_trackers_in_error)
    {
        $this->semantic_name               = $semantic_name;
        $this->semantic_short_name         = $semantic_short_name;
        $this->potential_trackers_in_error = $potential_trackers_in_error;
    }
}
