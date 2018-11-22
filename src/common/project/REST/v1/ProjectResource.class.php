<?php
/**
 * Copyright (c) Enalean, 2013 - 2018. All Rights Reserved.
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

use Event;
use EventManager;
use Luracast\Restler\RestException;
use PaginatedWikiPagesFactory;
use PFUser;
use Project;
use Project_InvalidFullName_Exception;
use Project_InvalidShortName_Exception;
use ProjectCreator;
use ProjectManager;
use ProjectUGroup;
use ReferenceManager;
use Tuleap\Dashboard\Project\ProjectDashboardDao;
use Tuleap\Dashboard\Project\ProjectDashboardDuplicator;
use Tuleap\Dashboard\Project\ProjectDashboardRetriever;
use Tuleap\Dashboard\Widget\DashboardWidgetDao;
use Tuleap\Dashboard\Widget\DashboardWidgetRetriever;
use Tuleap\FRS\FRSPermissionCreator;
use Tuleap\FRS\FRSPermissionDao;
use Tuleap\Label\Label;
use Tuleap\Label\PaginatedCollectionsOfLabelsBuilder;
use Tuleap\Label\REST\LabelRepresentation;
use Tuleap\Project\Event\GetProjectWithTrackerAdministrationPermission;
use Tuleap\Project\HeartbeatsEntryCollection;
use Tuleap\Project\Label\LabelDao;
use Tuleap\Project\Label\LabelsCurlyCoatedRetriever;
use Tuleap\Project\PaginatedProjects;
use Tuleap\Project\ProjectStatusMapper;
use Tuleap\Project\REST\HeartbeatsRepresentation;
use Tuleap\Project\REST\ProjectRepresentation;
use Tuleap\Project\REST\UserGroupRepresentation;
use Tuleap\Project\UgroupDuplicator;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Event\ProjectGetSvn;
use Tuleap\REST\Event\ProjectOptionsSvn;
use Tuleap\REST\Header;
use Tuleap\REST\JsonDecoder;
use Tuleap\REST\ProjectAuthorization;
use Tuleap\REST\ProjectStatusVerificator;
use Tuleap\REST\ResourcesInjector;
use Tuleap\REST\v1\GitRepositoryListRepresentation;
use Tuleap\REST\v1\GitRepositoryRepresentationBase;
use Tuleap\REST\v1\MilestoneRepresentationBase;
use Tuleap\REST\v1\OrderRepresentationBase;
use Tuleap\Project\REST\v1\GetProjectsQueryChecker;
use Tuleap\REST\v1\PhpWikiPageRepresentation;
use Tuleap\Service\ServiceCreator;
use Tuleap\User\ForgeUserGroupPermission\RestProjectManagementPermission;
use Tuleap\Widget\WidgetFactory;
use UGroupBinding;
use UGroupDao;
use UGroupManager;
use UGroupUserDao;
use URLVerification;
use User_ForgeUserGroupPermissionsDao;
use User_ForgeUserGroupPermissionsManager;
use UserManager;
use Wiki;
use WikiDao;

/**
 * Wrapper for project related REST methods
 */

class ProjectResource extends AuthenticatedResource {

    const MAX_LIMIT = 50;

    /** @var LabelsCurlyCoatedRetriever */
    private $labels_retriever;

    /** @var UserManager */
    private $user_manager;

    /** @var ProjectManager */
    private $project_manager;

    /** @var UGroupManager */
    private $ugroup_manager;

    /** @var ProjectCreator*/
    private $project_creator;

    /** @var ReferenceManager */
    private $reference_manager;

    /** @var EventManager */
    private $event_manager;

    /**
     * @var UgroupDuplicator
     */
    private $ugroup_duplicator;

    /**
     * @var JsonDecoder
     */
    private $json_decoder;

    /**
     * @var User_ForgeUserGroupPermissionsManager
     */
    private $forge_ugroup_permissions_manager;

    public function __construct() {
        $this->user_manager      = UserManager::instance();
        $this->project_manager   = ProjectManager::instance();
        $this->reference_manager = ReferenceManager::instance();
        $this->ugroup_manager    = new UGroupManager();
        $this->json_decoder      = new JsonDecoder();
        $ugroup_user_dao         = new UGroupUserDao();
        $this->event_manager     = EventManager::instance();

        $this->forge_ugroup_permissions_manager = new User_ForgeUserGroupPermissionsManager(
            new User_ForgeUserGroupPermissionsDao()
        );

        $widget_factory = new WidgetFactory(
            $this->user_manager,
            $this->forge_ugroup_permissions_manager,
            $this->event_manager
        );

        $widget_dao        = new DashboardWidgetDao($widget_factory);
        $project_dao       = new ProjectDashboardDao($widget_dao);
        $project_retriever = new ProjectDashboardRetriever($project_dao);
        $widget_retriever  = new DashboardWidgetRetriever($widget_dao);
        $duplicator        = new ProjectDashboardDuplicator(
            $project_dao,
            $project_retriever,
            $widget_dao,
            $widget_retriever,
            $widget_factory
        );

        $this->ugroup_duplicator        = new UgroupDuplicator(new UGroupDao(),
                $this->ugroup_manager,
        new UGroupBinding($ugroup_user_dao, $this->ugroup_manager),
        $ugroup_user_dao, EventManager::instance());
        $send_notifications = true;
        $force_activation   = false;
        $label_dao          = new LabelDao();

        $this->project_creator = new ProjectCreator(
            $this->project_manager,
            $this->reference_manager,
            $this->user_manager,
            $this->ugroup_duplicator,
            $send_notifications,
            new FRSPermissionCreator(new FRSPermissionDao(), new UGroupDao()),
            $duplicator,
            new ServiceCreator(),
            $label_dao,
            $force_activation
        );

        $this->labels_retriever = new LabelsCurlyCoatedRetriever(
            new PaginatedCollectionsOfLabelsBuilder(),
            $label_dao
        );
    }

    /**
     * Creates a new Project
     *
     * Creates a new project in Tuleap. doesn't support custom fields nor project categories.
     *
     * @url POST
     * @status 201
     *
     * @param string $shortname Name of the project
     * @param string $description Full description of the project
     * @param string $label A short description of the project
     * @param bool $is_public Define the visibility of the project
     * @param int $template_id Template for this project.
     *
     *
     * @return ProjectRepresentation
     * @throws 400
     * @throws 403
     * @throws 429
     */
    protected function post($shortname, $description, $label, $is_public, $template_id)
    {
        $this->checkAccess();

        $user = $this->user_manager->getCurrentUser();

        if (! $this->isUserARestProjectManager($user)) {
            throw new RestException(403, 'You are not allowed to create a project through the api');
        }

        if (! $this->project_manager->userCanCreateProject($this->user_manager->getCurrentUser())) {
            throw new RestException(429, 'Too many projects were created');
        }

        $data = [
            'project' => [
                'form_short_description' => $description,
                'is_test'                => false,
                'is_public'              => $is_public,
                'built_from_template'    => $template_id,
            ]
        ];

        try {
            $project = $this->project_creator->createFromRest($shortname, $label, $data);
        } catch (Project_InvalidShortName_Exception $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Project_InvalidFullName_Exception $exception) {
            throw new RestException(400, $exception->getMessage());
        }

        $project_representation = $this->getProjectRepresentation($project);

        return $project_representation;
    }

    /**
     * Get projects
     *
     * Get the public projects and the projects the current user is member of.
     *
     * <p>
     * If current user is site administrator, then returns all active projects.
     * <br>
     * <br>
     * ?query is optional. When filled, it is a json object with either:
     * <ul>
     *   <li>a property "shorname" to search on shortname with exact match.
     *     Example: <pre>{"shortname": "guinea-pig"}</pre>
     *   </li>
     *   <li>a property "is_member_of" to search projects the current user is member of.
     *     Example: <pre>{"is_member_of": true}</pre>
     *   </li>
     *   <li>a property "is_tracker_admin" to search projects the current user is administrator of at least one tracker.
     *     Example: <pre>{"is_tracker_admin": true}</pre>
     *   </li>
     *   <li>a property "with_status" to search projects the current user is member of with a specific status.
     *     Example: <pre>{"with_status": "deleted"}</pre>
     *   </li>
     * </ul>
     * </p>
     *
     * <p>
     *   <strong>/!\</strong> Please note that {"is_member_of": false} is not supported and will result
     *   in a 400 Bad Request error.
     * </p>
     * <p>
     *   <strong>/!\</strong> Please note that {"is_tracker_admin": false} is not supported and will result
     *   in a 400 Bad Request error.
     * </p>
     * <p>
     *   <strong>/!\</strong> Please note that querying with { "with_status" } will throw a 403 Forbidden Error
     *   if you are not member of the REST project management delegation.
     * </p>
     *
     * @url GET
     * @access hybrid
     *
     * @param int    $limit  Number of elements displayed per page
     * @param int    $offset Position of the first element to display
     * @param string $query  JSON object of search criteria properties {@from path}
     *
     * @throws 403
     * @throws 404
     * @throws 406
     *
     * @return array {@type Tuleap\Project\REST\ProjectRepresentation}
     */
    public function get($limit = 10, $offset = 0, $query = '')
    {
        $this->checkAccess();
        $this->checkLimitValueIsAcceptable($limit);

        $query = trim($query);
        $user  = $this->user_manager->getCurrentUser();

        if ($this->json_decoder->looksLikeJson($query)) {
            $paginated_projects = $this->getMyAndPublicProjectsFromExactMatch($query, $user, $offset, $limit);
        } elseif (! empty($query)) {
            throw new RestException(400, 'query parameter must be a json object or empty');
        } else {
            $paginated_projects = $this->getMyAndPublicProjects($user, $offset, $limit);
        }

        return $this->sendProjectRepresentations($paginated_projects, $limit, $offset);
    }

    private function sendProjectRepresentations(PaginatedProjects $paginated_projects, $limit, $offset)
    {
        $project_representations = array();
        foreach($paginated_projects->getProjects() as $project) {
            $project_representations[] = $this->getProjectRepresentation($project);
        }

        $this->sendAllowHeadersForProject();
        $this->sendPaginationHeaders($limit, $offset, $paginated_projects->getTotalSize());

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
     * @return Tuleap\Project\PaginatedProjects
     */
    private function getMyAndPublicProjects(PFUser $user, $offset, $limit) {
        return $this->project_manager->getMyAndPublicProjectsForREST($user, $offset, $limit);
    }

    /**
     * @param string $query
     * @param PFUser $user
     * @param int    $offset
     * @param int    $limit
     *
     * @return PaginatedProjects
     *
     * @throws RestException
     * @throws \Tuleap\REST\Exceptions\InvalidJsonException
     */
    private function getMyAndPublicProjectsFromExactMatch($query, PFUser $user, $offset, $limit)
    {
        $json_query = $this->json_decoder->decodeAsAnArray('query', $query);
        $checker    = new GetProjectsQueryChecker();
        $checker->checkQuery($json_query, $this->isUserARestProjectManager($user));

        if (isset($json_query['shortname'])) {
            return $this->project_manager->getMyAndPublicProjectsForRESTByShortname(
                $json_query['shortname'],
                $user,
                $offset,
                $limit
            );
        } elseif (isset($json_query['is_tracker_admin'])) {
            $event = new GetProjectWithTrackerAdministrationPermission($user, $limit, $offset);
            $this->event_manager->processEvent($event);

            return $event->getPaginatedProjects();
        } else if (isset($json_query['with_status'])) {
            $with_status = $json_query['with_status'];
            return $this->project_manager->getProjectsWithStatusForREST(
                ProjectStatusMapper::getProjectStatusFlagFromStatusLabel($with_status),
                $offset,
                $limit
            );
        } else {
            return $this->project_manager->getMyProjectsForREST(
                $user,
                $offset,
                $limit
            );
        }
    }

    /**
     * Get project
     *
     * Get the definition of a given project
     *
     * @url GET {id}
     * @access hybrid
     *
     * @param int $id Id of the project
     *
     *
     * @throws 403
     * @throws 404
     *
     * @return ProjectRepresentation
     */
    public function getId($id) {
        $this->checkAccess();

        $this->sendAllowHeadersForProject();

        $user = $this->user_manager->getCurrentUser();

        if ($this->isUserDelegatedRestProjectManager($user)) {
            return $this->getProjectRepresentation(
                $this->getProjectForRestProjectManager($id)
            );
        }

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
     * @throws 403
     * @throws 404
     *
     * @return Project
     */
    private function getProjectForRestProjectManager($project_id) {
        $project = $this->project_manager->getProject($project_id);

        if ($project->isError()) {
            throw new RestException(404, "Project does not exist");
        }

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
     * @return ProjectRepresentation
     */
    private function getProjectRepresentation(Project $project) {
        $resources = array();
        $this->event_manager->processEvent(
            Event::REST_PROJECT_RESOURCES,
            array(
                'version'   => 'v1',
                'project'   => $project,
                'resources' => &$resources
            )
        );

        $resources_injector = new ResourcesInjector();
        $resources_injector->declareProjectResources($resources, $project);

        $informations = array();
        $this->event_manager->processEvent(
            Event::REST_PROJECT_ADDITIONAL_INFORMATIONS,
            array(
                'project' => $project,
                'informations' => &$informations
            )
        );

        $project_representation = new ProjectRepresentation();
        $project_representation->build(
            $project,
            $this->user_manager->getCurrentUser(),
            $resources,
            $informations
        );

        return $project_representation;
    }

    /**
     * Get plannings
     *
     * Get the plannings of a given project
     *
     * @url GET {id}/plannings
     * @access hybrid
     *
     * @param int $id     Id of the project
     * @param int $limit  Number of elements displayed per page {@from path}
     * @param int $offset Position of the first element to display {@from path}
     *
     * @return array {@type Tuleap\REST\v1\PlanningRepresentationBase}
     */
    public function getPlannings($id, $limit = 10, $offset = 0) {
        $this->checkAccess();

        $this->checkAgileEndpointsAvailable();

        $plannings = $this->plannings($id, $limit, $offset, Event::REST_GET_PROJECT_PLANNINGS);
        $this->sendAllowHeadersForPlanning();

        return $plannings;
    }

    /**
     * @url OPTIONS {id}/plannings
     *
     * @param int $id Id of the project
     */
    public function optionsPlannings($id) {
        $this->checkAgileEndpointsAvailable();
        $this->sendAllowHeadersForPlanning();
    }

    /**
     * Get heartbeats
     *
     * Get the latest activities of a given project
     *
     * @url GET {id}/heartbeats
     * @access hybrid
     *
     * @param int $id     Id of the project
     *
     * @return HeartbeatsRepresentation {@type HeartbeatsRepresentation}
     */
    public function getHeartbeats($id) {
        $this->checkAccess();

        $project = $this->getProjectForUser($id);
        $user    = $this->user_manager->getCurrentUser();
        $event   = new HeartbeatsEntryCollection($project, $user);
        $this->event_manager->processEvent($event);

        $heartbeats = new HeartbeatsRepresentation();
        $heartbeats->build($event);

        $this->sendAllowHeadersForHeartBeat();

        return $heartbeats;
    }

    /**
     * @url OPTIONS {id}/heartbeats
     *
     * @param int $id Id of the project
     */
    public function optionsHeartbeats($id) {
        $this->sendAllowHeadersForHeartBeat();
    }

    /**
     * Get labels
     *
     * Get labels used by the project
     *
     * <p><code>query</code> parameter allows you to search for a particular label with wildcard</p>
     *
     * @url GET {id}/labels
     * @access hybrid
     *
     * @param int $id Id of the project
     * @param string $query Search particular label, if not used, returns all project labels
     * @param int $limit  Number of elements displayed per page {@from path} {@min 1} {@max 50}
     * @param int $offset Position of the first element to display {@from path} {@min 0}
     *
     * @return array {@type LabelRepresentation}
     */
    public function getLabels($id, $query = '', $limit = self::MAX_LIMIT, $offset = 0) {
        $this->checkAccess();

        $project = $this->getProjectForUser($id);
        $collection = $this->labels_retriever->getPaginatedMatchingLabelsForProject($project, $query, $limit, $offset);
        $labels_representation = array_map(
            function (Label $label) {
                $representation = new LabelRepresentation();
                $representation->build($label);

                return $representation;
            },
            $collection->getLabels()
        );

        $this->sendAllowHeadersForLabels();
        Header::sendPaginationHeaders($limit, $offset, $collection->getTotalSize(), self::MAX_LIMIT);

        return array(
            'labels' => $labels_representation
        );
    }

    /**
     * @url OPTIONS {id}/labels
     *
     * @param int $id Id of the project
     */
    public function optionsLabels($id) {
        $this->sendAllowHeadersForLabels();
    }

    private function plannings($id, $limit, $offset, $event) {
        $project = $this->getProjectForUser($id);
        $result  = array();

        $this->event_manager->processEvent(
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
     * <p>
     * $query parameter is optional, by default we return all milestones. If
     * query={"status":"open"} then only open milestones are returned and if
     * query={"status":"closed"} then only closed milestones are returned.
     * </p>
     *
     * @url GET {id}/milestones
     * @access hybrid
     *
     * @param int    $id     Id of the project
     * @param string $fields Set of fields to return in the result {@choice all,slim}
     * @param string $query  JSON object of search criteria properties {@from path}
     * @param int    $limit  Number of elements displayed per page {@from path}
     * @param int    $offset Position of the first element to display {@from path}
     * @param string $order  In which order milestones are fetched. Default is asc {@from path}{@choice asc,desc}
     *
     * @return array {@type Tuleap\REST\v1\MilestoneRepresentationBase}
     */
    public function getMilestones(
        $id,
        $fields = MilestoneRepresentationBase::ALL_FIELDS,
        $query = '',
        $limit = 10,
        $offset = 0,
        $order = 'asc'
    ) {
        $this->checkAccess();

        $this->checkAgileEndpointsAvailable();

        try {
            $milestones = $this->milestones($id, $fields, $query, $limit, $offset, $order, Event::REST_GET_PROJECT_MILESTONES);
        } catch (\Planning_NoPlanningsException $e) {
            $milestones = array();
        }

        $this->sendAllowHeadersForMilestones();

        return $milestones;
    }

    /**
     * @url OPTIONS {id}/milestones
     *
     * @param int $id The id of the project
     */
    public function optionsMilestones($id) {
        $this->checkAgileEndpointsAvailable();
        $this->sendAllowHeadersForMilestones();
    }

    private function milestones($id, $representation_type, $query, $limit, $offset, $order, $event) {
        $project = $this->getProjectForUser($id);
        $result  = array();

        $this->event_manager->processEvent(
            $event,
            array(
                'version'             => 'v1',
                'project'             => $project,
                'representation_type' => $representation_type,
                'query'               => $query,
                'limit'               => $limit,
                'offset'              => $offset,
                'order'               => $order,
                'result'              => &$result,
            )
        );

        return $result;
    }

    /**
     * Get trackers
     *
     * Get the trackers of a given project.
     *
     * Fetching reference representations can be helpful if you encounter performance issues with complex trackers.
     *
     * <br/>
     * query is optional. When filled, it is a json object with a property "is_tracker_admin" to filter trackers.
     * <br/>
     * <br/>
     * Example: <pre>{"is_tracker_admin": true}</pre>
     * <br/>
     * <p>
     *   <strong>/!\</strong> Please note that {"is_tracker_admin": false} is not supported and will result
     *   in a 400 Bad Request error.
     * </p>
     *
     * @url GET {id}/trackers
     * @access hybrid
     *
     * @param int $id     Id of the project
     * @param string $representation Whether you want to fetch full or reference only representations {@from path}{@choice full,minimal}
     * @param int $limit  Number of elements displayed per page {@from path}
     * @param int $offset Position of the first element to display {@from path}
     * @param string $query JSON object of search criteria properties {@from path}
     *
     * @return array {@type Tuleap\Tracker\REST\TrackerRepresentation}
     */
    public function getTrackers($id, $representation = 'full', $limit = 10, $offset = 0, $query = '')
    {
        $this->checkAccess();

        $trackers = $this->getRepresentationsForTrackers(
            $id,
            $representation,
            $limit,
            $offset,
            $query
        );

        $this->sendAllowHeadersForTracker();

        return $trackers;
    }

    /**
     * @url OPTIONS {id}/trackers
     *
     * @param int $id Id of the project
     */
    public function optionsTrackers($id) {
        $this->sendAllowHeadersForTracker();
    }

    private function getRepresentationsForTrackers($id, $representation, $limit, $offset, $query)
    {
        $project = $this->getProjectWithoutAuthorisation($id);
        $result  = array();

        $this->event_manager->processEvent(
            Event::REST_GET_PROJECT_TRACKERS,
            array(
                'version'        => 'v1',
                'project'        => $project,
                'representation' => $representation,
                'limit'          => $limit,
                'query'          => $query,
                'offset'         => $offset,
                'result'         => &$result,
            )
        );

        return $result;
    }

    /**
     * Get FRS packages
     *
     * Get the list of packages in the project
     *
     * @url GET {id}/frs_packages
     * @access hybrid
     *
     * @param int $id     Id of the project
     * @param int $limit  Number of elements displayed per page {@from path}
     * @param int $offset Position of the first element to display {@from path}
     *
     * @return array {@type Tuleap\REST\v1\FRSPackageRepresentationBase}
     *
     * @throws 406
     */
    public function getFRSPackages($id, $limit = 10, $offset = 0) {
        $this->checkAccess();
        $this->checkLimitValueIsAcceptable($limit);
        $this->checkFRSEndpointsAvailable();

        $project    = $this->getProjectForUser($id);
        $result     = array();
        $total_size = 0;

        $this->event_manager->processEvent(
            Event::REST_GET_PROJECT_FRS_PACKAGES,
            array(
                'project'      => $project,
                'current_user' => $this->user_manager->getCurrentUser(),
                'limit'        => $limit,
                'offset'       => $offset,
                'result'       => &$result,
                'total_size'   => &$total_size
            )
        );

        $this->sendAllowHeadersForFRSPackages();
        $this->sendPaginationHeaders($limit, $offset, $total_size);

        return $result;
    }

    /**
     * @url OPTIONS {id}/frs_packages
     *
     * @param int $id Id of the project
     */
    public function optionsFRSPackages($id) {
        $this->checkFRSEndpointsAvailable();
        $this->sendAllowHeadersForFRSPackages();
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
     * @return array {@type Tuleap\REST\v1\BacklogItemRepresentationBase}
     *
     * @throws 406
     */
    public function getBacklog($id, $limit = 10, $offset = 0) {
        $this->checkAccess();

        $this->checkAgileEndpointsAvailable();

        try {
        $backlog_items = $this->backlogItems($id, $limit, $offset, Event::REST_GET_PROJECT_BACKLOG);
        } catch (\Planning_NoPlanningsException $e) {
            $backlog_items = array();
        }

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
     * @access hybrid
     * @url PUT {id}/backlog
     *
     * @param int $id    Id of the project
     * @param array $ids Ids of backlog items {@from body}
     *
     * @throws 500
     */
    public function putBacklog($id, array $ids) {
        $this->checkAccess();

        $this->checkAgileEndpointsAvailable();

        $project = $this->getProjectForUser($id);
        $result  = array();

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt($project);

        $this->event_manager->processEvent(
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
     * @access hybrid
     *
     * @param int                                     $id    Id of the project
     * @param \Tuleap\REST\v1\OrderRepresentationBase $order Order of the children {@from body}
     * @param array                                   $add   Add (move) item to the backlog {@from body}
     *
     * @throws 500
     * @throws 409
     * @throws 400
     */
    public function patchBacklog($id, OrderRepresentationBase $order = null, array $add = null) {
        $this->checkAccess();

        $this->checkAgileEndpointsAvailable();

        $project = $this->getProjectForUser($id);
        $result  = array();

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt($project);

        $this->event_manager->processEvent(
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

    /**
     * Project partial update
     *
     * Partial update of a Project
     * <br/>
     *
     * <br/>
     * This partial update allows a REST project manager to toggle the status of a given project from active to suspended and conversely.
     * <br/>
     *
     * <br/>
     * Example:
     * <pre>
     * {
     *   "status": "suspended"
     * }
     * </pre>
     *
     * @url PATCH {id}
     * @access hybrid
     * @status 200
     *
     * @param int $id Id of the project
     * @param PATCHProjectRepresentation $patch_resource {@from body} {@type Tuleap\Project\REST\v1\PATCHProjectRepresentation}
     *
     * @throws 400
     * @throws 401
     * @throws 403
     * @throws 404
     */
    public function patchProject($id, PATCHProjectRepresentation $patch_resource)
    {
        $this->checkAccess();

        $user = $this->user_manager->getCurrentUser();

        if (! $this->isUserARestProjectManager($user)) {
            throw new RestException(403, 'You are not allowed to change the status of a project');
        }

        if ($id > 0 && $id <= Project::ADMIN_PROJECT_ID) {
            throw new RestException(403, 'You are not allowed to change the status of a system project.');
        }

        $project = $this->getProjectForRestProjectManager($id);

        $this->project_manager->updateStatus(
            $project,
            ProjectStatusMapper::getProjectStatusFlagFromStatusLabel($patch_resource->status)
        );

        $this->sendAllowHeadersForProject();
    }

    private function backlogItems($id, $limit, $offset, $event) {
        $project = $this->getProjectForUser($id);
        $result  = array();

        $this->event_manager->processEvent(
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
     * @url OPTIONS {id}/user_groups
     *
     * @param int $id Id of the project
     */
    public function optionsUserGroups($id) {
        $this->sendAllowHeadersForUserGroups();
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

        $excluded_ugroups_ids = array(
            ProjectUGroup::NONE,
            ProjectUGroup::ANONYMOUS,
            ProjectUGroup::REGISTERED,
            ProjectUGroup::AUTHENTICATED
        );

        $ugroups     = $this->ugroup_manager->getUGroups($project, $excluded_ugroups_ids);
        $user_groups = $this->getUserGroupsRepresentations($ugroups, $id);

        $this->sendAllowHeadersForUserGroups();

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

        $this->event_manager->processEvent(
            Event::REST_PROJECT_OPTIONS_GIT,
            array(
                'activated' => &$activated
            )
        );

        if ($activated) {
            $this->sendAllowHeadersForGit();
        } else {
            throw new RestException(404, 'Git plugin not activated');
        }
    }

    /**
     * Get git
     *
     * Get info about project Git repositories. Repositories are returned ordered by last_push date, if there are no push
     * yet, it's the creation date of the repository that is taken into account.
     * <br>
     * The total number of repositories returned by 'x-pagination-size' header corresponds to ALL repositories, including
     * those you cannot view so you might retrieve a lower number of repositories than 'x-pagination-size'.
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
     * You can use <code>query</code> parameter in order to filter results. Currently you can only filter on scope or
     * owner_id. By default, all repositories are returned.
     * <br>
     * { "scope": "project" } will return only project repositories.
     * <br>
     * { "scope": "individual" } will return only forked repositories.
     * <br>
     * { "owner_id": 123 } will return all repositories created by user with id 123.
     * <br>
     * { "scope": "individual", "owner_id": 123 } will return all repositories forked by user with id 123.
     *
     * @url    GET {id}/git
     * @access hybrid
     *
     * @param int    $id     Id of the project
     * @param int    $limit  Number of elements displayed per page {@from path}
     * @param int    $offset Position of the first element to display {@from path}
     * @param string $fields Whether you want to fetch permissions or just repository info {@from path}{@choice basic,all}
     * @param string $query  Filter repositories {@from path}
     *
     * @return GitRepositoryListRepresentation
     *
     * @throws RestException
     */
    public function getGit(
        $id,
        $limit = 10,
        $offset = 0,
        $fields = GitRepositoryRepresentationBase::FIELDS_BASIC,
        $query = ''
    ) {
        $this->checkAccess();

        $project                = $this->getProjectForUser($id);
        $result                 = new GitRepositoryListRepresentation();
        $total_git_repositories = 0;

        $this->event_manager->processEvent(
            Event::REST_PROJECT_GET_GIT,
            array(
                'version'        => 'v1',
                'project'        => $project,
                'result'         => &$result,
                'limit'          => $limit,
                'offset'         => $offset,
                'fields'         => $fields,
                'query'          => $query,
                'total_git_repo' => &$total_git_repositories
            )
        );

        if ($result->repositories !== null) {
            $this->sendAllowHeadersForGit();
            $this->sendPaginationHeaders($limit, $offset, $total_git_repositories);
            return $result;
        } else {
            throw new RestException(404, 'Git plugin not activated');
        }

    }

    /**
     * @url OPTIONS {id}/svn
     *
     * @param int $id Id of the project
     *
     * @throws 404
     */
    public function optionsSvn($id) {
        $event = new ProjectOptionsSvn();

        $this->event_manager->processEvent($event);

        if ($event->isPluginActivated()) {
            $this->sendAllowHeadersForSvn();
        } else {
            throw new RestException(404, 'SVN plugin not activated');
        }
    }

    /**
     * Get svn
     *
     * Get info about project SVN repositories
     *
     * <br>
     * <pre>
     * "repositories": [{<br>
     *   &nbsp;"id" : 90,<br>
     *   &nbsp;"project": {...},<br>
     *   &nbsp;"uri": "svn/90",<br>
     *   &nbsp;"name": "repo",<br>
     *  }<br>
     * ...<br>
     * ]
     * </pre>
     *
     * <br/>
     * <br/>
     * ?query must be a json object to search on name with exact match: {"name": "repository01"}
     *
     * @url GET {id}/svn
     * @access hybrid
     *
     * @param int $id        Id of the project
     * @param string $query  Optional search string in json format {@from query}
     * @param int $limit     Number of elements displayed per page {@from path}
     * @param int $offset    Position of the first element to display {@from path}
     *
     * @return array {@type Tuleap\REST\v1\SvnRepositoryRepresentationBase}
     *
     * @throws 404
     */
    public function getSvn($id, $query = '', $limit = 10, $offset = 0) {
        $this->checkAccess();

        $project                = $this->getProjectForUser($id);
        $repository_name_filter = $this->getRepositoryNameFromQuery($query);

        $event = new ProjectGetSvn($project, $repository_name_filter, 'v1', $limit, $offset);

        $this->event_manager->processEvent($event);

        if (! $event->isPluginActivated()) {
            throw new RestException(404, 'SVN plugin not activated');
        }

        $this->sendAllowHeadersForSvn();
        $this->sendPaginationHeaders($limit, $offset, $event->getTotalRepositories());

        return array('repositories' => $event->getRepositoriesRepresentations());
    }

    private function getRepositoryNameFromQuery($query)
    {
        if ($query === '') {
            return '';
        }

        if ($query && ! $this->json_decoder->looksLikeJson($query)) {
            throw new RestException(400, 'Query must be in Json');
        }

        $json_query = $this->json_decoder->decodeAsAnArray('query', $query);
        if (! isset($json_query['name'])) {
            throw new RestException(400, 'You can only search on "name"');
        }

        return $json_query['name'];
    }

    /**
     * @url OPTIONS {id}/phpwiki
     *
     * @param int $id Id of the project
     */
    public function optionsWiki($id) {
        $this->sendAllowHeadersForWiki();
    }

    /**
     * Get PhpWiki pages
     *
     * Get info about project non empty PhpWiki pages.
     *
     * @url GET {id}/phpwiki
     *
     * @access hybrid
     *
     * @param int $id          Id of the project
     * @param int $limit       Number of elements displayed per page {@from path}
     * @param int $offset      Position of the first element to display {@from path}
     * @param string $pagename Part of the pagename or the full pagename to search {@from path}
     *
     * @return array {@type Tuleap\REST\v1\PhpWikiPageRepresentation}
     */
    public function getPhpWiki($id, $limit = 10, $offset = 0, $pagename = '') {
        $this->checkAccess();
        $this->getProjectForUser($id);

        $current_user = UserManager::instance()->getCurrentUser();

        $this->userCanAccessPhpWikiService($current_user, $id);

        $wiki_pages = array(
            'pages' => array()
        );

        $wiki_pages_factory = new PaginatedWikiPagesFactory(new WikiDao());
        $all_pages          = $wiki_pages_factory->getPaginatedUserPages(
            $current_user,
            $id,
            $limit,
            $offset,
            $pagename
        );

        foreach ($all_pages->getPages() as $page) {
            $representation = new PhpWikiPageRepresentation();
            $representation->build($page);

            $wiki_pages['pages'][] = $representation;
        }

        $this->sendAllowHeadersForWiki();
        $this->sendPaginationHeaders($limit, $offset, $all_pages->getTotalSize());

        return $wiki_pages;
    }

    private function userCanAccessPhpWikiService(PFUser $user, $project_id)
    {
        $wiki_service = new Wiki($project_id);

        if (! $wiki_service->isAutorized($user->getId())) {
            throw new RestException(403, 'You are not allowed to access to PhpWiki service');
        }
    }

    private function checkAgileEndpointsAvailable() {
        $available = false;

        $this->event_manager->processEvent(
            Event::REST_PROJECT_AGILE_ENDPOINTS,
            array(
                'available' => &$available
            )
        );

        if ($available === false) {
            throw new RestException(404, 'AgileDashboard plugin not activated');
        }
    }

    private function checkFRSEndpointsAvailable() {
        $available = false;

        $this->event_manager->processEvent(
            Event::REST_PROJECT_FRS_ENDPOINTS,
            array(
                'available' => &$available
            )
        );

        if ($available === false) {
            throw new RestException(404, 'FRS plugin not activated');
        }
    }

    private function sendAllowHeadersForProject() {
        Header::allowOptionsGetPostPatch();
    }

    private function sendAllowHeadersForBacklog() {
        Header::allowOptionsGetPutPatch();
    }

    private function sendAllowHeadersForSvn()
    {
        Header::allowOptionsGet();
    }

    private function sendAllowHeadersForPlanning()
    {
        Header::allowOptionsGet();
    }

    private function sendAllowHeadersForMilestones()
    {
        Header::allowOptionsGet();
    }

    private function sendAllowHeadersForTracker()
    {
        Header::allowOptionsGet();
    }

    private function sendAllowHeadersForLabels()
    {
        Header::allowOptionsGet();
    }

    private function sendAllowHeadersForUserGroups()
    {
        Header::allowOptionsGet();
    }

    private function sendAllowHeadersForWiki()
    {
        Header::allowOptionsGet();
    }

    private function sendAllowHeadersForHeartBeat()
    {
        Header::allowOptionsGet();
    }

    private function sendAllowHeadersForGit()
    {
        Header::allowOptionsGet();
    }

    private function sendPaginationHeaders($limit, $offset, $size) {
        Header::sendPaginationHeaders($limit, $offset, $size, self::MAX_LIMIT);
    }

    private function sendAllowHeadersForFRSPackages()
    {
        Header::allowOptionsGet();
    }

    private function checkLimitValueIsAcceptable($limit)
    {
        if (!$this->limitValueIsAcceptable($limit)) {
            throw new RestException(406, 'Maximum value for limit exceeded');
        }
    }

    private function isUserDelegatedRestProjectManager(PFUser $user)
    {
        return $this->forge_ugroup_permissions_manager->doesUserHavePermission($user, new RestProjectManagementPermission());
    }

    /**
     * @param PFUser $user
     * @return bool
     */
    private function isUserARestProjectManager(PFUser $user)
    {
        return $user->isSuperUser() || $this->isUserDelegatedRestProjectManager($user);
    }
}
