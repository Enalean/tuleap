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

namespace Tuleap\Project\Registration;

use Project;
use Tuleap\Event\Dispatchable;
use Tuleap\Project\MappingRegistry;

class RegisterProjectCreationEvent implements Dispatchable
{
    public const NAME = 'registerProjectCreationEvent';

    /**
     * @var Project
     */
    private $just_created_project;
    /**
     * @var Project
     */
    private $template_project;
    /**
     * @var MappingRegistry
     */
    private $mapping_registry;
    /**
     * @var \PFUser
     */
    private $project_administrator;
    /**
     * @var array
     */
    private $legacy_service_usage;
    /**
     * @var bool
     */
    private $should_project_inherit_from_template;

    public function __construct(
        Project $just_created_project,
        Project $template_project,
        MappingRegistry $mapping_registry,
        \PFUser $project_administrator,
        array $legacy_service_usage,
        bool $should_project_inherit_from_template,
    ) {
        $this->just_created_project                 = $just_created_project;
        $this->template_project                     = $template_project;
        $this->mapping_registry                     = $mapping_registry;
        $this->project_administrator                = $project_administrator;
        $this->legacy_service_usage                 = $legacy_service_usage;
        $this->should_project_inherit_from_template = $should_project_inherit_from_template;
    }

    public function getJustCreatedProject(): Project
    {
        return $this->just_created_project;
    }

    public function getTemplateProject(): Project
    {
        return $this->template_project;
    }

    public function getMappingRegistry(): MappingRegistry
    {
        return $this->mapping_registry;
    }

    public function getProjectAdministrator(): \PFUser
    {
        return $this->project_administrator;
    }

    public function getLegacyServiceUsage(): array
    {
        return $this->legacy_service_usage;
    }

    public function shouldProjectInheritFromTemplate(): bool
    {
        return $this->should_project_inherit_from_template;
    }
}
