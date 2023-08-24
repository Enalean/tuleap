<?php
/**
 * Copyright (c) Enalean, 2023-present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Test\Builders;

use Project;
use Service;
use Tuleap\Project\Service\ProjectDefinedService;

final class ServiceBuilder
{
    private bool $is_defined_by_project;
    private string $short_name = 'custom_service';
    private string $label      = 'Custom Service';

    private function __construct(private readonly Project $project, bool $is_defined_by_project)
    {
        $this->is_defined_by_project = $is_defined_by_project;
    }

    /**
     * @throws \ServiceNotAllowedForProjectException
     */
    public static function buildLegacyAdminService(Project $project): Service
    {
        return self::aSystemService($project)
            ->withShortName(Service::ADMIN)
            ->build();
    }

    /**
     * @throws \ServiceNotAllowedForProjectException
     */
    public static function buildLegacySummaryService(Project $project): Service
    {
        return self::aSystemService($project)
            ->withShortName(Service::SUMMARY)
            ->build();
    }

    public static function aProjectDefinedService(Project $project): self
    {
        return new self($project, true);
    }

    public static function aSystemService(Project $project): self
    {
        return new self($project, false);
    }

    public function withShortName(string $short_name): self
    {
        $this->short_name = $short_name;
        return $this;
    }

    public function withLabel(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    /**
     * @throws \ServiceNotAllowedForProjectException
     */
    public function build(): Service
    {
        $parameters = [
            'service_id'    => 102,
            'group_id'      => (int) $this->project->getID(),
            'label'         => $this->label,
            'description'   => 'mudding homework',
            'short_name'    => $this->short_name,
            'link'          => null,
            'is_active'     => 1,
            'is_used'       => 1,
            'rank'          => 140,
            'is_in_iframe'  => 0,
            'is_in_new_tab' => false,
            'icon'          => '',
        ];
        if ($this->is_defined_by_project) {
            $parameters['scope'] = Service::SCOPE_PROJECT;
            return new ProjectDefinedService($this->project, $parameters);
        }
        $parameters['scope'] = Service::SCOPE_SYSTEM;
        return new class ($this->project, $parameters) extends Service {
            public function getIconName(): string
            {
                return 'fa-solid fa-play';
            }
        };
    }
}
