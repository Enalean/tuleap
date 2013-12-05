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
use \Tuleap\Project\REST\ProjectRepresentation;
use \Tuleap\REST\Header;

/**
 * Wrapper for project related REST methods
 */
class ProjectResource {

    /**
     * Get project
     *
     * Get the definition of a given project
     *
     * @param int $id Id of the project
     *
     * @access protected
     *
     * @throws 403
     * @throws 404
     *
     * @return Tuleap\Project\REST\ProjectRepresentation
     */
    public function get($id) {
        $project = $this->getProject($id);

        $resources = array();
        EventManager::instance()->processEvent(
            Event::REST_PROJECT_RESOURCES,
            array(
                'version'   => 'v1',
                'project'   => $project,
                'resources' => &$resources
            )
        );
        $this->sendAllowHeadersForProject();

        $project_representation = new ProjectRepresentation();
        $project_representation->build($project, $resources);

        return $project_representation;
    }

    /**
     * @url OPTIONS {id}
     *
     * @param int $id Id of the project
     *
     * @access protected
     *
     * @throws 403
     * @throws 404
     */
    public function optionsId($id) {
        $this->getProject($id);
        $this->sendAllowHeadersForProject();
    }

    /**
     * @url OPTIONS
     */
    public function options() {
        Header::allowOptions();
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
     * Get plannings
     *
     * Get the plannings of a given project
     *
     * @url GET {id}/plannings
     *
     * @param int $id     Id of the project
     * @param int $limit  Number of elements displayed per page {@from path}
     * @param int $offset Position of the first element to display {@from path}
     *
     * @return array {@type Tuleap\AgileDashboard\REST\v1\PlanningRepresentation}
     */
    protected function getPlannings($id, $limit = 10, $offset = 0) {
        $plannings = $this->plannings($id, $limit, $offset, Event::REST_GET_PROJECT_PLANNINGS);
        $this->sendAllowHeadersForProject();

        return $plannings;
    }

    /**
     * @url OPTIONS {id}/plannings
     *
     * @param int $id Id of the project
     */
    protected function optionsPlannings($id) {
        $this->plannings($id, 10, 0, Event::REST_OPTIONS_PROJECT_PLANNINGS);
        $this->sendAllowHeadersForProject();
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

    /**
     * Get milestones
     *
     * Get the top milestones of a given project
     *
     * @url GET {id}/milestones
     *
     * @param int $id     Id of the project
     * @param int $limit  Number of elements displayed per page {@from path}
     * @param int $offset Position of the first element to display {@from path}
     *
     * @return array {@type Tuleap\AgileDashboard\REST\v1\MilestoneRepresentation}
     */
    protected function getMilestones($id, $limit = 10, $offset = 0) {
        $milestones = $this->milestones($id, $limit, $offset, Event::REST_GET_PROJECT_MILESTONES);
        $this->sendAllowHeadersForProject();

        return $milestones;
    }

    /**
     * @url OPTIONS {id}/milestones
     *
     * @param int $id The id of the project
     */
    protected function optionsMilestones($id) {
        $this->milestones($id, 10, 0, Event::REST_OPTIONS_PROJECT_MILESTONES);
        $this->sendAllowHeadersForProject();
    }

    private function milestones($id, $limit, $offset, $event) {
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

    /**
     * Get trackers
     *
     * Get the trackers of a given project
     *
     * @url GET {id}/trackers
     *
     * @param int $id     Id of the project
     * @param int $limit  Number of elements displayed per page {@from path}
     * @param int $offset Position of the first element to display {@from path}
     *
     * @return array {@type Tuleap\Tracker\REST\TrackerRepresentation}
     */
    protected function getTrackers($id, $limit = 10, $offset = 0) {
        $trackers = $this->trackers($id, $limit, $offset, Event::REST_GET_PROJECT_TRACKERS);
        $this->sendAllowHeadersForProject();

        return $trackers;
    }

    /**
     * @url OPTIONS {id}/trackers
     *
     * @param int $id Id of the project
     */
    protected function optionsTrackers($id) {
        $this->trackers($id, 10, 0, Event::REST_OPTIONS_PROJECT_TRACKERS);
        $this->sendAllowHeadersForProject();
    }

    private function trackers($id, $limit, $offset, $event) {
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

    /**
     * Get backlog
     *
     * Get the backlog items that can be planned in a top-milestone
     *
     * @url GET {id}/backlog
     *
     * @param int $id     Id of the project
     * @param int $limit  Number of elements displayed per page {@from path}
     * @param int $offset Position of the first element to display {@from path}
     *
     * @return array {@type Tuleap\AgileDashboard\REST\v1\BacklogItemRepresentation}
     *
     * @throws 406
     */
    protected function getBacklog($id, $limit = 10, $offset = 0) {
        $backlog_items = $this->backlogItems($id, $limit, $offset, Event::REST_GET_PROJECT_BACKLOG);
        $this->sendAllowHeadersForBacklog();

        return $backlog_items;
    }

    /**
     * @url OPTIONS {id}/backlog
     *
     * @param int $id Id of the project
     */
    protected function optionsBacklog($id) {
        $this->backlogItems($id, 10, 0, Event::REST_OPTIONS_PROJECT_BACKLOG);
        $this->sendAllowHeadersForBacklog();
    }

    /**
     * Order backlog items
     *
     * Order backlog items in top backlog
     *
     * @url PUT {id}/backlog
     *
     * @param int $id    Id of the project
     * @param array $ids Ids of backlog items {@from body}
     *
     * @throws 500
     */
    protected function putBacklog($id, array $ids) {
        $project = $this->getProject($id);
        $result  = array();

        EventManager::instance()->processEvent(
            Event::REST_PUT_PROJECT_BACKLOG,
            array(
                'version' => 'v1',
                'project' => $project,
                'ids'     => $ids,
                'result'  => &$result,
            )
        );

        $this->sendAllowHeadersForBacklog();
    }

    private function backlogItems($id, $limit, $offset, $event) {
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

    private function sendAllowHeadersForProject() {
        Header::allowOptionsGet();
    }

    private function sendAllowHeadersForBacklog() {
        Header::allowOptionsGetPut();
    }
}
