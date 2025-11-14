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

final readonly class RegisterProjectCreationEvent implements Dispatchable
{
    public const string NAME = 'registerProjectCreationEvent';

    public function __construct(
        private Project $just_created_project,
        private Project $template_project,
        private MappingRegistry $mapping_registry,
        private \PFUser $project_administrator,
        private bool $should_project_inherit_from_template,
    ) {
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

    public function shouldProjectInheritFromTemplate(): bool
    {
        return $this->should_project_inherit_from_template;
    }
}
