<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\REST\v1;

use Luracast\Restler\RestException;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\ProjectAuthorization;
use Tuleap\REST\ProjectStatusVerificator;
use URLVerification;

final class AgileDashboardProjectResource extends AuthenticatedResource
{
    /**
     * Get plannings
     *
     * Get the plannings of a given project
     *
     * @url    GET {id}/plannings
     * @access hybrid
     *
     * @param int $id     Id of the project
     * @param int $limit  Number of elements displayed per page {@from path}
     * @param int $offset Position of the first element to display {@from path}
     *
     * @return array {@type \Tuleap\REST\v1\PlanningRepresentationBase}
     */
    public function getPlannings($id, $limit = 10, $offset = 0)
    {
        $this->checkAccess();

        $this->sendAllowHeadersForPlanning();
        $planning_resource = new ProjectPlanningsResource();
        return $planning_resource->get(
            \UserManager::instance()->getCurrentUser(),
            $this->getProjectForUser($id),
            $limit,
            $offset
        );
    }

    /**
     * @url OPTIONS {id}/plannings
     *
     * @param int $id Id of the project
     */
    public function optionsPlannings($id): void
    {
        $this->sendAllowHeadersForPlanning();
    }

    private function sendAllowHeadersForPlanning(): void
    {
        Header::allowOptionsGet();
    }

    /**
     * Get milestones
     *
     * Get the top milestones of a given project
     *
     * <p>
     * $query parameter is optional, by default we return all milestones. If
     * query={"status":"open"} then only open milestones are returned, if
     * query={"status":"closed"} then only closed milestones are returned, if
     * query={"period":"future"} then only milestones planned are returned and if
     * query={"period":"current"} then only current milestones are returned.
     * </p>
     *
     * @url    GET {id}/milestones
     * @access hybrid
     *
     * @param int    $id     Id of the project
     * @param string $fields Set of fields to return in the result {@choice all,slim}
     * @param string $query  JSON object of search criteria properties {@from path}
     * @param int    $limit  Number of elements displayed per page {@from path}
     * @param int    $offset Position of the first element to display {@from path}
     * @param string $order  In which order milestones are fetched. Default is asc {@from path}{@choice asc,desc}
     *
     * @return array {@type MilestoneRepresentation}
     */
    public function getMilestones(
        $id,
        $fields = MilestoneRepresentation::ALL_FIELDS,
        $query = '',
        $limit = 10,
        $offset = 0,
        $order = 'asc'
    ) {
        $this->checkAccess();

        $this->sendAllowHeadersForMilestones();
        $project_milestone_resources = new ProjectMilestonesResource();
        try {
            $milestones = $project_milestone_resources->get(
                \UserManager::instance()->getCurrentUser(),
                $this->getProjectForUser($id),
                $fields,
                $query,
                $limit,
                $offset,
                $order
            );
        } catch (\Planning_NoPlanningsException $e) {
            $milestones = [];
        }

        return $milestones;
    }

    /**
     * @url OPTIONS {id}/milestones
     *
     * @param int $id The id of the project
     */
    public function optionsMilestones($id)
    {
        $this->sendAllowHeadersForMilestones();
    }

    private function sendAllowHeadersForMilestones(): void
    {
        Header::allowOptionsGet();
    }

    /**
     * Get backlog
     *
     * Get the backlog items that can be planned in a top-milestone
     *
     * @url    GET {id}/backlog
     * @access hybrid
     *
     * @param int $id     Id of the project
     * @param int $limit  Number of elements displayed per page {@from path}
     * @param int $offset Position of the first element to display {@from path}
     *
     * @return array {@type BacklogItemRepresentation}
     *
     * @throws RestException 406
     */
    public function getBacklog($id, $limit = 10, $offset = 0)
    {
        $this->checkAccess();

        $this->sendAllowHeadersForBacklog();
        $project_backlog_resource = new ProjectBacklogResource();

        try {
            return $project_backlog_resource->get(
                \UserManager::instance()->getCurrentUser(),
                $this->getProjectForUser($id),
                $limit,
                $offset
            );
        } catch (\Planning_NoPlanningsException $e) {
            return [];
        }
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

    /**
     * Set order of all backlog items
     *
     * Order all backlog items in top backlog
     *
     * @access hybrid
     * @url    PUT {id}/backlog
     *
     * @param int   $id  Id of the project
     * @param array $ids Ids of backlog items {@from body}
     *
     * @throws RestException 500
     */
    public function putBacklog($id, array $ids)
    {
        $this->checkAccess();

        $project = $this->getProjectForUser($id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt($project);

        $project_backlog_resource = new ProjectBacklogResource();
        $project_backlog_resource->put(
            \UserManager::instance()->getCurrentUser(),
            $project,
            $ids
        );
        $this->sendAllowHeadersForBacklog();
    }

    /**
     * Re-order backlog items relative to others
     *
     * Re-order backlog items in top backlog relative to each other
     * <br>
     * Order example:
     * <pre>
     * "order": {
     *   "ids" : [123, 789, 1001],
     *   "direction": "before",
     *   "compared_to": 456
     * }
     * </pre>
     *
     * <br>
     * Resulting order will be: <pre>[…, 123, 789, 1001, 456, …]</pre>
     *
     * <br>
     * Add example:
     * <pre>
     * "add": [
     *   {
     *     "id": 34
     *     "remove_from": 56
     *   },
     *   ...
     * ]
     * </pre>
     *
     * <br>
     * Remove example (only available for project using explicit backlog management):
     * <pre>
     * "Remove": [
     *   {
     *     "id": 34
     *   },
     *   ...
     * ]
     * </pre>
     *
     * <br>
     * Will remove element id 34 from milestone 56 backlog
     *
     * @url    PATCH {id}/backlog
     * @access hybrid
     *
     * @param int                                     $id    Id of the project
     * @param OrderRepresentation                     $order Order of the children {@from body}
     * @param array                                   $add   Add (move) item to the backlog {@from body} {@type BacklogAddRepresentation}
     * @param array                                   $remove   Remove item to the backlog {@from body} {@type BacklogRemoveRepresentation}
     *
     * @throws RestException 500
     * @throws RestException 409
     * @throws RestException 400
     */
    public function patchBacklog($id, ?OrderRepresentation $order = null, ?array $add = null, ?array $remove = null)
    {
        $this->checkAccess();

        $project = $this->getProjectForUser($id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt($project);

        $project_backlog_resource = new ProjectBacklogResource();
        $project_backlog_resource->patch(
            \UserManager::instance()->getCurrentUser(),
            $project,
            $order,
            $add,
            $remove
        );

        $this->sendAllowHeadersForBacklog();
    }

    private function sendAllowHeadersForBacklog(): void
    {
        Header::allowOptionsGetPutPatch();
    }

    /**
     * @throws RestException 403
     * @throws RestException 404
     */
    private function getProjectForUser(int $id): \Project
    {
        $project = \ProjectManager::instance()->getProject($id);
        $user    = \UserManager::instance()->getCurrentUser();

        ProjectAuthorization::userCanAccessProject($user, $project, new URLVerification());

        return $project;
    }
}
