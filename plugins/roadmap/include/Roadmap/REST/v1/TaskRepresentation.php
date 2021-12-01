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

namespace Tuleap\Roadmap\REST\v1;

use Tuleap\Project\REST\ProjectReference;
use Tuleap\REST\JsonCast;

/**
 * @psalm-immutable
 */
final class TaskRepresentation
{
    public int $id;
    public string $xref;
    public string $html_url;
    public string $title;
    public string $color_name;
    public ?float $progress;
    public string $progress_error_message;
    public ?string $start;
    public ?string $end;
    /**
     * @var array<string, int[]>
     */
    public array $dependencies;
    public string $subtasks_uri;
    public ProjectReference $project;
    public bool $are_dates_implied;
    public bool $is_open;

    public string $time_period_error_message;

    /**
     * @param DependenciesByNature[] $dependencies
     */
    public function __construct(
        int $id,
        string $xref,
        string $html_url,
        string $title,
        string $color_name,
        ?float $progress,
        string $progress_error_message,
        ?\DateTimeImmutable $start,
        ?\DateTimeImmutable $end,
        bool $are_dates_implied,
        bool $is_open,
        string $time_period_error_message,
        array $dependencies,
        ProjectReference $project,
    ) {
        $this->id         = $id;
        $this->xref       = $xref;
        $this->html_url   = $html_url;
        $this->title      = $title;
        $this->color_name = $color_name;
        $this->progress   = $progress;
        $this->start      = JsonCast::fromDateTimeToDate($start);
        $this->end        = JsonCast::fromDateTimeToDate($end);
        $this->project    = $project;
        $this->is_open    = $is_open;

        $this->are_dates_implied         = $are_dates_implied;
        $this->time_period_error_message = $time_period_error_message;
        $this->progress_error_message    = $progress_error_message;
        $this->subtasks_uri              = TasksResource::ROUTE . '/' . $id . '/subtasks';

        $this->dependencies = [];
        foreach ($dependencies as $dep) {
            $this->dependencies[$dep->nature] = $dep->artifact_ids;
        }
    }
}
