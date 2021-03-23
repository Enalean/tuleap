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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\REST\v1;

use Tuleap\Project\REST\ProjectReference;

/**
 * @psalm-immutable
 */
final class FeatureBacklogItemsRepresentation
{
    /**
     * @var int
     */
    public $id;
    /**
     * @var string
     */
    public $uri;
    /**
     * @var string
     */
    public $xref;
    /**
     * @var ?string
     */
    public $title;
    /**
     * @var bool
     */
    public $is_open;
    /**
     * @var ProjectReference
     */
    public $project;

    public function __construct(int $id, string $uri, string $xref, ?string $title, bool $is_open, ProjectReference $project)
    {
        $this->id      = $id;
        $this->uri     = $uri;
        $this->xref    = $xref;
        $this->title   = $title;
        $this->is_open = $is_open;
        $this->project = $project;
    }
}
