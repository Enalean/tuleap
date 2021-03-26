<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\REST\v1;

use Tuleap\Project\REST\ProjectReference;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;

/**
 * @psalm-immutable
 */
final class UserStoryRepresentation extends ElementRepresentation
{
    /**
     * @var bool
     */
    public $is_open;
    /**
     * @var ProjectReference
     */
    public $project;
    /**
     * @var MinimalTrackerRepresentation
     */
    public $tracker;
    /**
     * @var string
     */
    public $background_color;

    public function __construct(
        int $id,
        string $uri,
        string $xref,
        ?string $title,
        bool $is_open,
        ProjectReference $project,
        MinimalTrackerRepresentation $tracker,
        string $background_color
    ) {
        parent::__construct($id, $uri, $xref, $title);
        $this->is_open          = $is_open;
        $this->project          = $project;
        $this->tracker          = $tracker;
        $this->background_color = $background_color;
    }
}
