<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Project\ProjectBackground;

/**
 * @psalm-immutable
 */
class ProjectBackgroundAdministrationPresenter
{
    /**
     * @var ProjectBackground[]
     */
    public $backgrounds;
    /**
     * @var int
     */
    public $project_id;

    public function __construct(array $backgrounds, int $project_id)
    {
        $this->project_id  = $project_id;
        $this->backgrounds = $backgrounds;
        usort($this->backgrounds, static function (ProjectBackground $a, ProjectBackground $b) {
            $identifier_a = $a->identifier;
            $identifier_b = $b->identifier;
            // Workaround for Psalm when the static analysis is done only on a part of the codebase
            assert(is_string($identifier_a) && is_string($identifier_b));
            return strnatcasecmp($identifier_a, $identifier_b);
        });
    }
}
