<?php
/**
 * Copyright (c) Enalean, 2014-2017. All Rights Reserved.
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

namespace Tuleap\Project\REST\v2;

use Luracast\Restler\RestException;
use ProjectManager;
use UserManager;
use Project;
use EventManager;
use Event;
use Tuleap\REST\Header;
use Tuleap\REST\ProjectAuthorization;
use Tuleap\REST\AuthenticatedResource;
use URLVerification;

/**
 * Wrapper for project related REST methods
 */

class ProjectResource extends AuthenticatedResource
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
     *
     * @return Project
     */
    private function getProjectForUser($id)
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
     * @return array {@type Tuleap\REST\v2\BacklogRepresentationBase}
     *
     * @throws RestException 406
     */
    public function getBacklog($id, $limit = 10, $offset = 0)
    {
        $this->checkAccess();

        $this->checkAgileEndpointsAvailable();

        $backlog_items = $this->backlogItems($id, $limit, $offset, Event::REST_GET_PROJECT_BACKLOG);
        $this->sendAllowHeadersForBacklog();

        return $backlog_items;
    }

    /**
     * @url OPTIONS {id}/backlog
     *
     * @param int $id Id of the project
     */
    public function optionsBacklog($id)
    {
        $this->checkAgileEndpointsAvailable();
        $this->sendAllowHeadersForBacklog();
    }


    private function backlogItems($id, $limit, $offset, $event)
    {
        $project = $this->getProjectForUser($id);
        $result  = array();

        EventManager::instance()->processEvent(
            $event,
            array(
                'version' => 'v2',
                'project' => $project,
                'limit'   => $limit,
                'offset'  => $offset,
                'result'  => &$result,
            )
        );

        return $result;
    }

    private function sendAllowHeadersForBacklog()
    {
        Header::allowOptionsGet();
    }

    private function sendPaginationHeaders($limit, $offset, $size)
    {
        Header::sendPaginationHeaders($limit, $offset, $size, self::MAX_LIMIT);
    }

    private function checkAgileEndpointsAvailable()
    {
        $available = false;

        EventManager::instance()->processEvent(
            Event::REST_PROJECT_AGILE_ENDPOINTS,
            array(
                'available' => &$available
            )
        );

        if ($available === false) {
            throw new RestException(404, 'AgileDashboard plugin not activated');
        }
    }
}
