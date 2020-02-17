<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\AgileDashboard\REST\v2;

use Luracast\Restler\RestException;
use ProjectManager;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\ProjectAuthorization;
use URLVerification;
use UserManager;

final class AgileDashboardProjectResource extends AuthenticatedResource
{
    public const MAX_LIMIT = 50;

    /** @var UserManager */
    private $user_manager;

    /** @var ProjectManager */
    private $project_manager;

    public function __construct()
    {
        $this->user_manager    = UserManager::instance();
        $this->project_manager = ProjectManager::instance();
    }

    /**
     * @throws RestException 403
     * @throws RestException 404
     */
    private function getProjectForUser(int $id): \Project
    {
        $project = $this->project_manager->getProject($id);
        $user    = $this->user_manager->getCurrentUser();

        ProjectAuthorization::userCanAccessProject($user, $project, new URLVerification());
        return $project;
    }

    /**
     * Get backlog
     *
     * Get the backlog items that can be planned in a top-milestone
     *
     * @url GET {id}/backlog
     * @access hybrid
     *
     * @param int $id     Id of the project
     * @param int $limit  Number of elements displayed per page {@from path}
     * @param int $offset Position of the first element to display {@from path}
     *
     * @return BacklogRepresentation
     *
     * @throws RestException 406
     */
    public function getBacklog($id, $limit = 10, $offset = 0)
    {
        $this->checkAccess();

        $project_backlog_resource = new ProjectBacklogResource();
        $this->sendAllowHeadersForBacklog();

        return $project_backlog_resource->get(
            $this->user_manager->getCurrentUser(),
            $this->getProjectForUser($id),
            $limit,
            $offset
        );
    }

    /**
     * @url OPTIONS {id}/backlog
     *
     * @param int $id Id of the project
     */
    public function optionsBacklog($id)
    {
        $this->sendAllowHeadersForBacklog();
    }

    private function sendAllowHeadersForBacklog(): void
    {
        Header::allowOptionsGet();
    }
}
