<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1;

use EventManager;
use Luracast\Restler\RestException;
use PFUser;
use Project;
use Tracker_FormElementFactory;
use Tracker_REST_TrackerRestBuilder;
use Tracker_URLVerification;
use TrackerFactory;
use TransitionFactory;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\JsonDecoder;
use Tuleap\REST\ProjectAuthorization;
use Tuleap\Tracker\FormElement\Container\Fieldset\HiddenFieldsetChecker;
use Tuleap\Tracker\FormElement\Container\FieldsExtractor;
use Tuleap\Tracker\PermissionsFunctionsWrapper;
use Tuleap\Tracker\REST\FormElementRepresentationsBuilder;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;
use Tuleap\Tracker\REST\PermissionsExporter;
use Tuleap\Tracker\REST\FormElement\PermissionsForGroupsBuilder;
use Tuleap\Tracker\REST\Tracker\PermissionsRepresentationBuilder;
use Tuleap\Tracker\REST\v1\Event\GetTrackersWithCriteria;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsDao;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldDetector;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsRetriever;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsDao;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsDetector;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsRetriever;
use Tuleap\Tracker\Workflow\SimpleMode\SimpleWorkflowDao;
use Tuleap\Tracker\Workflow\SimpleMode\State\StateFactory;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionExtractor;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionRetriever;
use Workflow_Transition_ConditionFactory;

class ProjectTrackersResource extends AuthenticatedResource
{
    public const MAX_LIMIT              = 50;
    public const MINIMAL_REPRESENTATION = 'minimal';

    /**
     * Get trackers
     *
     * Get the trackers of a given project.
     *
     * Fetching reference representations can be helpful if you encounter performance issues with complex trackers.
     *
     * <br/>
     * query is optional. When filled, it is a json object with a property "is_tracker_admin" or "with_time_tracking" to filter trackers.
     * <br/>
     * <br/>
     * Example: <pre>{"is_tracker_admin": true}</pre>
     *          <pre>{"with_time_tracking": true}</pre>
     * <br/>
     * <p>
     *   <strong>/!\</strong> Please note that {"is_tracker_admin": false} is not supported and will result
     *   in a 400 Bad Request error.
     * </p>
     *
     * @url    GET {id}/trackers
     * @access hybrid
     * @oauth2-scope read:tracker
     *
     * @param int    $id             Id of the project
     * @param string $representation Whether you want to fetch full or reference only representations {@from path}{@choice full,minimal}
     * @param int    $limit          Number of elements displayed per page {@from path}
     * @param int    $offset         Position of the first element to display {@from path}
     * @param string $query          JSON object of search criteria properties {@from path}
     *
     * @return array {@type Tuleap\Tracker\REST\TrackerRepresentation}
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     */
    public function getTrackers($id, $representation = 'full', $limit = 10, $offset = 0, $query = '')
    {
        $this->checkAccess();
        $this->optionsTrackers($id);


        $project = \ProjectManager::instance()->getProject($id);
        $user    = \UserManager::instance()->getCurrentUser();

        ProjectAuthorization::userCanAccessProject($user, $project, new Tracker_URLVerification());

        if (! $this->limitValueIsAcceptable($limit)) {
            throw new RestException(406, 'Maximum value for limit exceeded');
        }

        $json_decoder                                = new JsonDecoder();
        $json_query                                  = $json_decoder->decodeAsAnArray('query', $query);
        $filter_on_tracker_administration_permission = $this->mustFilterOnTrackerAdministration($json_decoder, $query);

        if (empty($json_query) || isset($json_query["is_tracker_admin"])) {
            return $this->getTrackerRepresentations(
                $user,
                $project,
                $representation,
                $limit,
                $offset,
                $filter_on_tracker_administration_permission
            );
        }

        return $this->getTrackersWithCriteria($project, $representation, $limit, $offset, $json_query);
    }

    /**
     * @url OPTIONS {id}/trackers
     *
     * @param int $id Id of the project
     */
    public function optionsTrackers($id)
    {
        $this->sendAllowHeaders();
    }

    /**
     * @return bool
     * @throws RestException
     */
    private function mustFilterOnTrackerAdministration(JsonDecoder $json_decoder, $query)
    {
        if ($query === '') {
            return false;
        }

        if (!$json_decoder->looksLikeJson($query)) {
            throw new RestException(400, 'Query must be in Json');
        }
        $event_manager = EventManager::instance();
        $checker       = new GetTrackersQueryChecker($event_manager);
        $checker->checkQuery($json_decoder->decodeAsAnArray('query', $query));

        return true;
    }

    private function limitValueIsAcceptable($limit)
    {
        return $limit <= self::MAX_LIMIT;
    }

    private function sendPaginationHeaders($limit, $offset, $size)
    {
        header('X-PAGINATION-LIMIT: ' . $limit);
        header('X-PAGINATION-OFFSET: ' . $offset);
        header('X-PAGINATION-SIZE: ' . $size);
        header('X-PAGINATION-LIMIT-MAX: ' . self::MAX_LIMIT);
    }

    private function sendAllowHeaders()
    {
        Header::allowOptionsGet();
    }

    /**
     * @param         $limit
     * @param         $offset
     * @param         $json_query
     * @return MinimalTrackerRepresentation[]
     * @throws RestException
     */
    private function getTrackersWithCriteria(Project $project, $representation, $limit, $offset, $json_query)
    {
        $event_manager = EventManager::instance();
        $get_projects  = new GetTrackersWithCriteria($json_query, $limit, $offset, $project, $representation);
        $event_manager->processEvent($get_projects);
        $all_trackers = $get_projects->getTrackersWithCriteria();

        $this->sendAllowHeaders();
        $this->sendPaginationHeaders($limit, $offset, $get_projects->getTotalTrackers());

        return $all_trackers;
    }

    /**
     * @param String  $representation
     * @param int     $limit
     * @param int     $offset
     * @param         $filter_on_tracker_administration_permission
     * @return array
     */
    private function getTrackerRepresentations(
        PFUser $user,
        Project $project,
        $representation,
        $limit,
        $offset,
        $filter_on_tracker_administration_permission
    ) {
        $all_trackers            = TrackerFactory::instance()->getTrackersByGroupIdUserCanView(
            $project->getId(),
            $user
        );
        $trackers                = array_slice($all_trackers, $offset, $limit);

        $transition_retriever = new TransitionRetriever(
            new StateFactory(
                new TransitionFactory(
                    Workflow_Transition_ConditionFactory::build(),
                    EventManager::instance(),
                    new DBTransactionExecutorWithConnection(
                        DBFactory::getMainTuleapDBConnection()
                    )
                ),
                new SimpleWorkflowDao()
            ),
            new TransitionExtractor()
        );

        $frozen_fields_detector = new FrozenFieldDetector(
            $transition_retriever,
            new FrozenFieldsRetriever(
                new FrozenFieldsDao(),
                Tracker_FormElementFactory::instance()
            )
        );

        $builder = new Tracker_REST_TrackerRestBuilder(
            Tracker_FormElementFactory::instance(),
            new FormElementRepresentationsBuilder(
                Tracker_FormElementFactory::instance(),
                new PermissionsExporter(
                    $frozen_fields_detector
                ),
                new HiddenFieldsetChecker(
                    new HiddenFieldsetsDetector(
                        $transition_retriever,
                        new HiddenFieldsetsRetriever(
                            new HiddenFieldsetsDao(),
                            Tracker_FormElementFactory::instance()
                        ),
                        Tracker_FormElementFactory::instance()
                    ),
                    new FieldsExtractor()
                ),
                new PermissionsForGroupsBuilder(
                    new \UGroupManager(),
                    $frozen_fields_detector,
                    new PermissionsFunctionsWrapper()
                )
            ),
            new PermissionsRepresentationBuilder(
                new \UGroupManager(),
                new PermissionsFunctionsWrapper()
            )
        );
        $tracker_representations = [];

        foreach ($trackers as $tracker) {
            if ($filter_on_tracker_administration_permission && ! $tracker->userIsAdmin($user)) {
                continue;
            }
            if ($representation === self::MINIMAL_REPRESENTATION) {
                $tracker_minimal_representation = new MinimalTrackerRepresentation();
                $tracker_minimal_representation->build($tracker);
                $tracker_representations[] = $tracker_minimal_representation;
            } else {
                $tracker_representations[] = $builder->getTrackerRepresentationInTrackerContext($user, $tracker);
            }
        }

        $this->sendAllowHeaders();
        $this->sendPaginationHeaders($limit, $offset, count($all_trackers));

        return $tracker_representations;
    }
}
