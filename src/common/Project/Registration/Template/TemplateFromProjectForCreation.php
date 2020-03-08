<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Project\Registration\Template;

use PFUser;
use Project;
use Project_OneStepCreation_OneStepCreationRequest;
use ProjectManager;
use Tuleap\Project\REST\v1\ProjectPostRepresentation;

class TemplateFromProjectForCreation
{
    /**
     * @var Project
     * @psalm-readonly
     */
    private $project;

    private function __construct(Project $project)
    {
        $this->project = $project;
    }

    /**
     * @throws InsufficientPermissionToUseProjectAsTemplateException
     * @throws ProjectIDTemplateNotProvidedException
     * @throws ProjectTemplateIDInvalidException
     * @throws ProjectTemplateNotActiveException
     */
    private static function fromData(ProjectManager $project_manager, PFUser $user_requesting_creation, ?int $project_id): self
    {
        if ($project_id === null) {
            throw new ProjectIDTemplateNotProvidedException();
        }

        $project = $project_manager->getProject($project_id);

        if ($project->isError()) {
            throw new ProjectTemplateIDInvalidException($project_id);
        }

        if (! $project->isActive() && ! $project->isTemplate()) {
            throw new ProjectTemplateNotActiveException($project);
        }

        if (! $project->isTemplate() && ! $user_requesting_creation->isAdmin($project->getID())) {
            throw new InsufficientPermissionToUseProjectAsTemplateException($project, $user_requesting_creation);
        }

        return new self($project);
    }

    /**
     * @throws InsufficientPermissionToUseProjectAsTemplateException
     * @throws ProjectIDTemplateNotProvidedException
     * @throws ProjectTemplateIDInvalidException
     * @throws ProjectTemplateNotActiveException
     */
    public static function fromRegisterCreationRequest(
        Project_OneStepCreation_OneStepCreationRequest $request,
        ProjectManager $project_manager
    ): self {
        $template_id = $request->getTemplateId();
        if ($template_id !== null) {
            $template_id = (int) $template_id;
        }
        return self::fromData($project_manager, $request->getCurrentUser(), $template_id);
    }

    /**
     * @throws InsufficientPermissionToUseProjectAsTemplateException
     * @throws ProjectIDTemplateNotProvidedException
     * @throws ProjectTemplateIDInvalidException
     * @throws ProjectTemplateNotActiveException
     */
    public static function fromRESTRepresentation(
        ProjectPostRepresentation $representation,
        PFUser $user_requesting_creation,
        ProjectManager $project_manager
    ): self {
        return self::fromData($project_manager, $user_requesting_creation, $representation->template_id);
    }

    /**
     * @throws InsufficientPermissionToUseProjectAsTemplateException
     * @throws ProjectIDTemplateNotProvidedException
     * @throws ProjectTemplateIDInvalidException
     * @throws ProjectTemplateNotActiveException
     */
    public static function fromSOAPServer(
        int $project_id,
        PFUser $user_requesting_creation,
        ProjectManager $project_manager
    ): self {
        return self::fromData($project_manager, $user_requesting_creation, $project_id);
    }

    public static function fromGlobalProjectAdminTemplate(): self
    {
        return new self(new Project(['group_id' => Project::ADMIN_PROJECT_ID, 'status' => Project::STATUS_SYSTEM]));
    }


    public function getProject(): Project
    {
        return $this->project;
    }
}
