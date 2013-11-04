<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

namespace Tuleap\Project\REST\v1;

use ProjectManager;
use UserManager;
use EventManager;
use Event;
use URLVerification;
use \Luracast\Restler\RestException;
use \Tuleap\Project\REST\ProjectInfoRepresentation;

/**
 * Wrapper for project related REST methods
 */
class ProjectResource {

    const ROUTE = 'projects';

    /**
     * Method to handle GET /projects/:id
     *
     * Get the project identified by its id
     *
     * @param int $id The id of the project
     *
     * @access protected
     *
     * @throws 403
     * @throws 404
     *
     * @return ProjectInfoRepresentation
     */
    public function get($id) {
        $project = $this->getProject($id);

        return new ProjectInfoRepresentation($project);
    }

    /**
     * @url OPTIONS {id}
     *
     * @param int $id The id of the project
     *
     * @access protected
     *
     * @throws 403
     * @throws 404
     */
    public function optionsId($id) {
        $this->getProject($id);

        header('Allow: GET, OPTIONS');
    }

    /**
     * @url OPTIONS
     */
    public function options() {
        header('Allow: GET, OPTIONS');
    }

    /**
     * @throws 403
     * @throws 404
     *
     * @return Project
     */
    private function getProject($id) {
        try {
            $project          = ProjectManager::instance()->getProject($id);
            $user             = UserManager::instance()->getCurrentUser();
            $url_verification = new URLVerification();
            $url_verification->userCanAccessProject($user, $project);
            return $project;
        } catch (\Project_AccessProjectNotFoundException $exception) {
            throw new RestException(404);
        } catch (\Project_AccessException $exception) {
            throw new RestException(403, $exception->getMessage());
        }
    }

    /**
     * @url GET {id}/plannings
     *
     * @param int $id     The id of the project
     * @param int $limit  The number of element displayed per page {@from path}
     * @param int $offset The id of the first element to display {@from path}
     *
     * @return array of ProjectPlanningResource
     */
    public function getPlannings($id, $limit = 10, $offset = 0) {
        return $this->plannings($id, $limit, $offset, Event::REST_GET_PROJECT_PLANNINGS);
    }

    /**
     * @url OPTIONS {id}/plannings
     *
     * @param int $id The id of the project
     */
    public function optionsPlannings($id) {
        return $this->plannings($id, 10, 0, Event::REST_OPTIONS_PROJECT_PLANNINGS);
    }

    private function plannings($id, $limit, $offset, $event) {
        $project = $this->getProject($id);
        $result  = array();

        EventManager::instance()->processEvent(
            $event,
            array(
                'version' => 'v1',
                'project' => $project,
                'limit'   => $limit,
                'offset'  => $offset,
                'result'  => &$result,
            )
        );

        return $result;
    }
}
