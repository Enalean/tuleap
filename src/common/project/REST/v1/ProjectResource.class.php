<?php
/**
 * Copyright (c) Enalean, 2013 - 2014. All Rights Reserved.
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

use Tuleap\Project\REST\ProjectRepresentation;
use Tuleap\Project\REST\UserGroupRepresentation;
use Tuleap\REST\v1\GitRepositoryRepresentationBase;
use Tuleap\REST\v1\OrderRepresentationBase;
use Tuleap\REST\ProjectAuthorization;
use Tuleap\REST\Header;
use Tuleap\REST\ResourcesInjector;
use ProjectManager;
use UserManager;
use PFUser;
use Project;
use EventManager;
use Event;
use ProjectUGroup;
use UGroupManager;
use URLVerification;
use Luracast\Restler\RestException;

/**
 * Wrapper for project related REST methods
 */

class ProjectResource {

    const MAX_LIMIT = 50;

    /** @var UserManager */
    private $user_manager;

    /** @var ProjectManager */
    private $project_manager;

    /** @var UGroupManager */
    private $ugroup_manager;

    public function __construct() {
        $this->user_manager    = UserManager::instance();
        $this->project_manager = ProjectManager::instance();
        $this->ugroup_manager  = new UGroupManager();
    }

    /**
     * Get projects
     *
     * Get the projects list
     *
     * @url GET
     *
     * @param int $limit  Number of elements displayed per page
     * @param int $offset Position of the first element to display
     *
     * @access protected
     *
     * @throws 403
     * @throws 404
     * @throws 406
     *
     * @return array {@type Tuleap\Project\REST\ProjectRepresentation}
     */
    public function get($limit = 10, $offset = 0) {
        $user = $this->user_manager->getCurrentUser();

        if (! $user) {
             throw new RestException(403, 'You don\'t have the permissions');
        }
        if (! $this->limitValueIsAcceptable($limit)) {
             throw new RestException(406, 'Maximum value for limit exceeded');
        }

        $project_representations = array();
        $projects                = $this->getMyAndPublicProjects($user, $offset, $limit);

        foreach($projects as $project) {
            $project_representations[] = $this->getProjectRepresentation($project);
        }

        $this->sendAllowHeadersForProject();
        $this->sendPaginationHeaders($limit, $offset, $this->countMyAndPublicProjects($user));

        return $project_representations;
    }

    /**
     * @url OPTIONS
     */
    public function options() {
        $this->sendAllowHeadersForProject();
    }

    /**
     * Get projects which I am member of, public projects (if I'm not a member of
     * a project but I'm in a static group of this project, this one will not be
     * retrieve)
     *
     * @return Project[]
     */
    private function getMyAndPublicProjects(PFUser $user, $offset, $limit) {
        return $this->project_manager->getMyAndPublicProjectsForREST($user, $offset, $limit);
    }

    /**
     * Count projects which I am member of, public projects (if I'm not a member of
     * a project but I'm in a static group of this project, this one will not be
     * retrieve)
     *
     * @return int
     */
    private function countMyAndPublicProjects(PFUser $user) {
        return $this->project_manager->countMyAndPublicProjectsForREST($user);
    }

    /**
     * Get project
     *
     * Get the definition of a given project
     *
     * @url GET {id}
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
    public function getId($id) {
        $this->sendAllowHeadersForProject();
        return $this->getProjectRepresentation($this->getProjectForUser($id));
    }

    /**
     * @url OPTIONS {id}
     *
     * @param int $id Id of the project
     *
     * @throws 403
     * @throws 404
     */
    public function optionsId($id) {
        $this->sendAllowHeadersForProject();
    }

    /**
     * @throws 403
     * @throws 404
     *
     * @return Project
     */
    private function getProjectForUser($id) {
        $project = $this->project_manager->getProject($id);
        $user    = $this->user_manager->getCurrentUser();

        ProjectAuthorization::userCanAccessProject($user, $project, new URLVerification());
        return $project;
    }

    /**
     * Used when the resource manages its own special access permissions
     * e.g. trackers
     *
     * @return Project
     */
    private function getProjectWithoutAuthorisation($id) {
        return $this->project_manager->getProject($id);
    }

    /**
     * Get a ProjectRepresentation
     *
     * @param Project $project
     * @return Tuleap\Project\REST\ProjectRepresentation
     */
    private function getProjectRepresentation(Project $project) {
        $resources = array();
        EventManager::instance()->processEvent(
            Event::REST_PROJECT_RESOURCES,
            array(
                'version'   => 'v1',
                'project'   => $project,
                'resources' => &$resources
            )
        );

        $resources_injector = new ResourcesInjector();
        $resources_injector->declareProjectUserGroupResource($resources, $project);

        $project_representation = new ProjectRepresentation();
        $project_representation->build($project, $resources);

        return $project_representation;
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
     * @return array {@type Tuleap\REST\v1\PlanningRepresentationBase}
     */
    protected function getPlannings($id, $limit = 10, $offset = 0) {
        $this->checkAgileEndpointsAvailable();

        $plannings = $this->plannings($id, $limit, $offset, Event::REST_GET_PROJECT_PLANNINGS);
        $this->sendAllowHeadersForProject();

        return $plannings;
    }

    /**
     * @url OPTIONS {id}/plannings
     *
     * @param int $id Id of the project
     */
    public function optionsPlannings($id) {
        $this->checkAgileEndpointsAvailable();
        $this->sendAllowHeadersForProject();
    }

    private function plannings($id, $limit, $offset, $event) {
        $project = $this->getProjectForUser($id);
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
     * @param int    $id     Id of the project
     * @param int    $limit  Number of elements displayed per page {@from path}
     * @param int    $offset Position of the first element to display {@from path}
     * @param string $order  In which order milestones are fetched. Default is asc {@from path}{@choice asc,desc}
     *
     * @return array {@type Tuleap\REST\v1\MilestoneRepresentationBase}
     */
    protected function getMilestones($id, $limit = 10, $offset = 0, $order = 'asc') {
        $this->checkAgileEndpointsAvailable();

        $milestones = $this->milestones($id, $limit, $offset, $order, Event::REST_GET_PROJECT_MILESTONES);
        $this->sendAllowHeadersForProject();

        return $milestones;
    }

    /**
     * @url OPTIONS {id}/milestones
     *
     * @param int $id The id of the project
     */
    public function optionsMilestones($id) {
        $this->checkAgileEndpointsAvailable();
        $this->sendAllowHeadersForProject();
    }

    private function milestones($id, $limit, $offset, $order, $event) {
        $project = $this->getProjectForUser($id);
        $result  = array();

        EventManager::instance()->processEvent(
            $event,
            array(
                'version' => 'v1',
                'project' => $project,
                'limit'   => $limit,
                'offset'  => $offset,
                'order'   => $order,
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
        $trackers = $this->getRepresentationsForTrackers($id, $limit, $offset, Event::REST_GET_PROJECT_TRACKERS);
        $this->sendAllowHeadersForProject();

        return $trackers;
    }

    /**
     * @url OPTIONS {id}/trackers
     *
     * @param int $id Id of the project
     */
    public function optionsTrackers($id) {
        $this->sendAllowHeadersForProject();
    }

    private function getRepresentationsForTrackers($id, $limit, $offset, $event) {
        $project = $this->getProjectWithoutAuthorisation($id);
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
     * @return array {@type Tuleap\REST\v1\BacklogItemRepresentationBase}
     *
     * @throws 406
     */
    protected function getBacklog($id, $limit = 10, $offset = 0) {
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
    public function optionsBacklog($id) {
        $this->checkAgileEndpointsAvailable();
        $this->sendAllowHeadersForBacklog();
    }

    /**
     * Set order of all backlog items
     *
     * Order all backlog items in top backlog
     *
     * @url PUT {id}/backlog
     *
     * @param int $id    Id of the project
     * @param array $ids Ids of backlog items {@from body}
     *
     * @throws 500
     */
    protected function putBacklog($id, array $ids) {
        $this->checkAgileEndpointsAvailable();

        $project = $this->getProjectForUser($id);
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
     * Will remove element id 34 from milestone 56 backlog
     *
     * @url PATCH {id}/backlog
     *
     * @param int                                     $id    Id of the Backlog Item
     * @param \Tuleap\REST\v1\OrderRepresentationBase $order Order of the children {@from body}
     * @param array                                   $add   Add (move) item to the backlog {@from body}
     *
     * @throws 500
     * @throws 409
     * @throws 400
     */
    protected function patchBacklog($id, OrderRepresentationBase $order, array $add = null) {
        $this->checkAgileEndpointsAvailable();

        $order->checkFormat($order);
        $project = $this->getProjectForUser($id);
        $result  = array();

        EventManager::instance()->processEvent(
            Event::REST_PATCH_PROJECT_BACKLOG,
            array(
                'version' => 'v1',
                'project' => $project,
                'order'   => $order,
                'add'     => $add,
                'result'  => &$result,
            )
        );

        $this->sendAllowHeadersForBacklog();
    }

    private function backlogItems($id, $limit, $offset, $event) {
        $project = $this->getProjectForUser($id);
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

    private function limitValueIsAcceptable($limit) {
        return $limit <= self::MAX_LIMIT;
    }

    /**
     * Get user_groups
     *
     * Get the user_groups of a given project
     *
     * @url GET {id}/user_groups
     *
     * @param int $id Id of the project
     *
     * @return array {@type Tuleap\Project\REST\v1\UserGroupRepresentation}
     */
    protected function getUserGroups($id) {
        $project = $this->getProjectForUser($id);
        $this->userCanSeeUserGroups($id);

        $excluded_ugroups_ids = array(ProjectUGroup::NONE, ProjectUGroup::ANONYMOUS, ProjectUGroup::REGISTERED);
        $ugroups              = $this->ugroup_manager->getUGroups($project, $excluded_ugroups_ids);
        $user_groups          = $this->getUserGroupsRepresentations($ugroups, $id);

        $this->sendAllowHeadersForProject();

        return $user_groups;
    }

    private function getUserGroupsRepresentations(array $ugroups, $project_id) {
        $user_groups = array();

        foreach ($ugroups as $ugroup) {
            $representation = new UserGroupRepresentation();
            $representation->build($project_id, $ugroup);
            $user_groups[] = $representation;
        }

        return $user_groups;
    }

    /**
     * @throws 403
     * @throws 404
     *
     * @return boolean
     */
    private function userCanSeeUserGroups($project_id) {
        $project      = $this->project_manager->getProject($project_id);
        $user         = $this->user_manager->getCurrentUser();
        ProjectAuthorization::userCanAccessProjectAndIsProjectAdmin($user, $project);

        return true;
    }

    /**
     * @url OPTIONS {id}/git
     *
     * @param int $id Id of the project
     *
     * @throws 404
     */
    public function optionsGit($id) {
        $activated = false;

        EventManager::instance()->processEvent(
            Event::REST_PROJECT_OPTIONS_GIT,
            array(
                'activated' => &$activated
            )
        );

        if ($activated) {
            $this->sendAllowHeadersForProject();
        } else {
            throw new RestException(404, 'Git plugin not activated');
        }
    }

    /**
     * Get git
     *
     * Get info about project Git repositories
     * <br>
     * <br>
     * With fields = 'basic', permissions is always set as <strong>NULL</strong>
     * <br>
     * <br>
     * Basic example:
     * <br>
     * <br>
     * <pre>
     * "repositories": [{<br>
     *   &nbsp;"id" : 90,<br>
     *   &nbsp;"uri": "git/90",<br>
     *   &nbsp;"name": "repo",<br>
     *   &nbsp;"path": "project/repo.git",<br>
     *   &nbsp;"description": "-- Default description --",<br>
     *   &nbsp;"permissions": null<br>
     *  }<br>
     * ...<br>
     * ]
     * </pre>
     * <br>
     *
     * <br>
     * All example:
     * <br>
     * <br>
     * <pre>
     * "repositories": [{<br>
     *   &nbsp;"id" : 90,<br>
     *   &nbsp;"uri": "git/90",<br>
     *   &nbsp;"name": "repo",<br>
     *   &nbsp;"path": "project/repo.git",<br>
     *   &nbsp;"description": "-- Default description --",<br>
     *   &nbsp;"permissions": {<br>
     *   &nbsp;   "read": [<br>
     *   &nbsp;     &nbsp;{<br>
     *   &nbsp;     &nbsp;  "id": "116_2",<br>
     *   &nbsp;     &nbsp;  "uri": "user_groups/116_2",<br>
     *   &nbsp;     &nbsp;  "label": "registered_users",<br>
     *   &nbsp;     &nbsp;  "users_uri": "user_groups/116_2/users"<br>
     *   &nbsp;     &nbsp;}<br>
     *   &nbsp;   ],<br>
     *   &nbsp;   "write": [<br>
     *   &nbsp;     &nbsp;{<br>
     *   &nbsp;     &nbsp;  "id": "116_3",<br>
     *   &nbsp;     &nbsp;  "uri": "user_groups/116_3",<br>
     *   &nbsp;     &nbsp;  "label": "project_members",<br>
     *   &nbsp;     &nbsp;  "users_uri": "user_groups/116_3/users"<br>
     *   &nbsp;     &nbsp;}<br>
     *   &nbsp;   ]<br>
     *   &nbsp;   "rewind": [<br>
     *   &nbsp;     &nbsp;{<br>
     *   &nbsp;     &nbsp;  "id": "116_122",<br>
     *   &nbsp;     &nbsp;  "uri": "user_groups/116_122",<br>
     *   &nbsp;     &nbsp;  "label": "admins",<br>
     *   &nbsp;     &nbsp;  "users_uri": "user_groups/116_122/users"<br>
     *   &nbsp;     &nbsp;}<br>
     *   &nbsp;   ],<br>
     *   &nbsp;}<br>
     *  }<br>
     * ...<br>
     * ]
     * </pre>
     * <br>
     *
     * @url GET {id}/git
     *
     * @param int $id        Id of the project
     * @param int $limit     Number of elements displayed per page {@from path}
     * @param int $offset    Position of the first element to display {@from path}
     * @param string $fields Whether you want to fetch permissions or just repository info {@from path}{@choice basic,all}
     *
     * @return array {@type Tuleap\REST\v1\GitRepositoryRepresentationBase}
     *
     * @throws 404
     */
    protected function getGit($id, $limit = 10, $offset = 0, $fields = GitRepositoryRepresentationBase::FIELDS_BASIC) {
        $project                = $this->getProjectForUser($id);
        $result                 = array();
        $total_git_repositories = 0;

        EventManager::instance()->processEvent(
            Event::REST_PROJECT_GET_GIT,
            array(
                'version'        => 'v1',
                'project'        => $project,
                'result'         => &$result,
                'limit'          => $limit,
                'offset'         => $offset,
                'fields'         => $fields,
                'total_git_repo' => &$total_git_repositories
            )
        );

        if (count($result) > 0) {
            $this->sendAllowHeadersForProject();
            $this->sendPaginationHeaders($limit, $offset, $total_git_repositories);
            return $result;
        } else {
            throw new RestException(404, 'Git plugin not activated');
        }

    }

    private function checkAgileEndpointsAvailable() {
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

    private function sendAllowHeadersForProject() {
        Header::allowOptionsGet();
    }

    private function sendAllowHeadersForBacklog() {
        Header::allowOptionsGetPutPatch();
    }

    private function sendPaginationHeaders($limit, $offset, $size) {
        Header::sendPaginationHeaders($limit, $offset, $size, self::MAX_LIMIT);
    }
}
