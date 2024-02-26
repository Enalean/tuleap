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

use Exception;
use PFUser;
use Project;
use ProjectManager;
use Tuleap\Project\REST\v1\ProjectPostRepresentation;
use URLVerification;

class TemplateFromProjectForCreation
{
    private function __construct(private readonly Project $project)
    {
    }

    /**
     * @throws InsufficientPermissionToUseProjectAsTemplateException
     * @throws ProjectIDTemplateNotProvidedException
     * @throws ProjectTemplateIDInvalidException
     * @throws ProjectTemplateNotActiveException
     * @throws InsufficientPermissionToUseCompanyTemplateException
     */
    private static function fromData(
        ProjectManager $project_manager,
        PFUser $user_requesting_creation,
        ?int $project_id,
        URLVerification $url_verification,
    ): self {
        if ($project_id === null) {
            throw new ProjectIDTemplateNotProvidedException();
        }

        $project = $project_manager->getProject($project_id);

        if ($project->isError()) {
            throw new ProjectTemplateIDInvalidException($project_id);
        }

        if (! self::doesProjectStatusAllowUsageAsTemplate($project)) {
            throw new ProjectTemplateNotActiveException($project);
        }

        self::checkIfUserHasPermissionsToUseTemplate($project, $url_verification, $user_requesting_creation);

        return new self($project);
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
        ProjectManager $project_manager,
        URLVerification $url_verification,
    ): self {
        return self::fromData(
            $project_manager,
            $user_requesting_creation,
            $representation->template_id,
            $url_verification
        );
    }

    public static function fromGlobalProjectAdminTemplate(): self
    {
        return new self(new Project(['group_id' => Project::DEFAULT_TEMPLATE_PROJECT_ID, 'status' => Project::STATUS_SYSTEM]));
    }

    private static function doesProjectStatusAllowUsageAsTemplate(Project $project): bool
    {
        return $project->isActive() || $project->isSystem();
    }

    /**
     * @throws InsufficientPermissionToUseCompanyTemplateException
     * @throws InsufficientPermissionToUseProjectAsTemplateException
     */
    private static function checkIfUserHasPermissionsToUseTemplate(
        Project $project,
        URLVerification $url_verification,
        PFUser $user_requesting_creation,
    ): void {
        if ($project->isTemplate()) {
            try {
                $url_verification->userCanAccessProject($user_requesting_creation, $project);
            } catch (Exception $exception) {
                throw new InsufficientPermissionToUseCompanyTemplateException($project);
            }
        } elseif (! $user_requesting_creation->isAdmin($project->getID())) {
            throw new InsufficientPermissionToUseProjectAsTemplateException($project, $user_requesting_creation);
        }
    }

    public function getProject(): Project
    {
        return $this->project;
    }
}
