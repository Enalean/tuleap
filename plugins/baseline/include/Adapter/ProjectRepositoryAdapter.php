<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Baseline\Adapter;

use PFUser;
use Project;
use Project_AccessException;
use ProjectManager;
use Tuleap\Baseline\Domain\ProjectRepository;
use URLVerification;

class ProjectRepositoryAdapter implements ProjectRepository
{
    /** @var ProjectManager */
    private $project_manager;

    /** @var URLVerification */
    private $url_verification;

    public function __construct(ProjectManager $project_manager, URLVerification $url_verification)
    {
        $this->project_manager  = $project_manager;
        $this->url_verification = $url_verification;
    }

    public function findById(PFUser $current_user, int $id): ?Project
    {
        $project = $this->project_manager->getProject($id);
        if ($project === null) {
            return null;
        }
        try {
            $this->url_verification->userCanAccessProject($current_user, $project);
            return $project;
        } catch (Project_AccessException $e) {
            return null;
        }
    }
}
