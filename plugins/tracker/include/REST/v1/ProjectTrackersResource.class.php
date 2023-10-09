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
use Project;
use Tracker_FormElementFactory;
use Tracker_REST_TrackerRestBuilder;
use Tracker_Semantic_TitleFactory;
use Tracker_URLVerification;
use TrackerFactory;
use TransitionFactory;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\JsonDecoder;
use Tuleap\REST\ProjectAuthorization;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\FormElement\Container\Fieldset\HiddenFieldsetChecker;
use Tuleap\Tracker\FormElement\Container\FieldsExtractor;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\Permission\SubmissionPermissionVerifier;
use Tuleap\Tracker\PermissionsFunctionsWrapper;
use Tuleap\Tracker\REST\CompleteTrackerRepresentation;
use Tuleap\Tracker\REST\FaultMapper;
use Tuleap\Tracker\REST\FormElement\PermissionsForGroupsBuilder;
use Tuleap\Tracker\REST\FormElementRepresentationsBuilder;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;
use Tuleap\Tracker\REST\PermissionsExporter;
use Tuleap\Tracker\REST\Tracker\PermissionsRepresentationBuilder;
use Tuleap\Tracker\REST\v1\Event\GetTrackersWithCriteria;
use Tuleap\Tracker\REST\WorkflowRestBuilder;
use Tuleap\Tracker\Semantic\ArtifactCannotBeCreatedReasonsGetter;
use Tuleap\Tracker\Semantic\CollectionOfCreationSemanticToCheck;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldDetector;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsDao;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsRetriever;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsDao;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsDetector;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsRetriever;
use Tuleap\Tracker\Workflow\SimpleMode\SimpleWorkflowDao;
use Tuleap\Tracker\Workflow\SimpleMode\State\StateFactory;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionExtractor;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionRetriever;

class ProjectTrackersResource extends AuthenticatedResource
{
    public const MAX_LIMIT = 50;

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
     *   ⚠ Please note that {"is_tracker_admin": false} is not supported and will result
     *   in a 400 Bad Request error.
     * </p>
     *
     * <p>
     *   ⚠ Notes about <code>with_creation_semantic_check</code> key:
     * <ul>
     *   <li> Supported semantic:
     *        <ul>
     *           <li> Title </li>
     *        </ul>
     * </li>
     *   <li> It's only available with the <strong>minimal</strong> representation, full representation will return 400 Bad Request error</li>
     *   <li> The <code>cannot_create_reasons</code> key in the result which contains the list of all reasons that prevent you from creating an artifact </li>
     * </ul>
     * </p>
     *
     * @url    GET {id}/trackers
     * @access hybrid
     * @oauth2-scope read:tracker
     *
     * @param int $id Id of the project
     * @param CompleteTrackerRepresentation::FULL_REPRESENTATION|MinimalTrackerRepresentation::MINIMAL_REPRESENTATION $representation Whether you want to fetch full or reference only representations {@from path}{@choice full,minimal}
     * @param int $limit Number of elements displayed per page {@from path} {@min 0} {@max 50}
     * @param int $offset Position of the first element to display {@from path} {@min 0}
     * @param string $query JSON object of search criteria properties {@from path}
     * @param array  $with_creation_semantic_check Include the list of reasons why an artifact cannot be created with only the given semantics {@from path} {@type string} {@fix true}
     *
     * @return array {@type Tuleap\Tracker\REST\TrackerRepresentation}
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     */
    public function getTrackers(int $id, string $representation = CompleteTrackerRepresentation::FULL_REPRESENTATION, int $limit = 10, int $offset = 0, string $query = '', array $with_creation_semantic_check = [])
    {
        $this->checkAccess();
        $this->optionsTrackers($id);


        $project = \ProjectManager::instance()->getProject($id);
        $user    = \UserManager::instance()->getCurrentUser();

        ProjectAuthorization::userCanAccessProject($user, $project, new Tracker_URLVerification());

        $json_decoder                                = new JsonDecoder();
        $json_query                                  = $json_decoder->decodeAsAnArray('query', $query);
        $filter_on_tracker_administration_permission = $this->mustFilterOnTrackerAdministration($json_decoder, $query);

        $form_element_factory = Tracker_FormElementFactory::instance();

        $tracker_permission_wrapper =  new PermissionsFunctionsWrapper();

        $transition_retriever = new TransitionRetriever(
            new StateFactory(
                TransitionFactory::instance(),
                new SimpleWorkflowDao()
            ),
            new TransitionExtractor()
        );

        $frozen_fields_detector = new FrozenFieldDetector(
            $transition_retriever,
            new FrozenFieldsRetriever(
                new FrozenFieldsDao(),
                $form_element_factory
            )
        );

        $builder = new Tracker_REST_TrackerRestBuilder(
            $form_element_factory,
            new FormElementRepresentationsBuilder(
                $form_element_factory,
                new PermissionsExporter(
                    $frozen_fields_detector
                ),
                new HiddenFieldsetChecker(
                    new HiddenFieldsetsDetector(
                        $transition_retriever,
                        new HiddenFieldsetsRetriever(
                            new HiddenFieldsetsDao(),
                            $form_element_factory
                        ),
                        $form_element_factory
                    ),
                    new FieldsExtractor()
                ),
                new PermissionsForGroupsBuilder(
                    new \UGroupManager(),
                    $frozen_fields_detector,
                    $tracker_permission_wrapper
                ),
                new TypePresenterFactory(
                    new TypeDao(),
                    new ArtifactLinksUsageDao()
                )
            ),
            new PermissionsRepresentationBuilder(
                new \UGroupManager(),
                $tracker_permission_wrapper
            ),
            new WorkflowRestBuilder()
        );

        $cannot_create_reasons = new ArtifactCannotBeCreatedReasonsGetter(
            SubmissionPermissionVerifier::instance(),
            $form_element_factory,
            Tracker_Semantic_TitleFactory::instance()
        );

        $semantics_to_check = CollectionOfCreationSemanticToCheck::fromREST($with_creation_semantic_check)->match(
            fn(CollectionOfCreationSemanticToCheck $valid_semantic_to_check) => $valid_semantic_to_check,
            FaultMapper::mapToRestException(...)
        );

        if (empty($json_query) || isset($json_query["is_tracker_admin"])) {
            $representation_builder     = new TrackerRepresentationBuilder($builder, $cannot_create_reasons);
            $project_trackers_retriever = new ProjectTrackersRetriever(
                TrackerFactory::instance(),
                TrackerFactory::instance()
            );

            $project_trackers = $project_trackers_retriever->getFilteredProjectTrackers(
                $project,
                $user,
                $filter_on_tracker_administration_permission
            );

            $tracker_representations = $representation_builder->buildTrackerRepresentations(
                $user,
                $project_trackers,
                $representation,
                $limit,
                $offset,
                $semantics_to_check
            );

            $this->sendAllowHeaders();
            $this->sendPaginationHeaders($limit, $offset, count($project_trackers));

            return $tracker_representations;
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

        if (! $json_decoder->looksLikeJson($query)) {
            throw new RestException(400, 'Query must be in Json');
        }
        $event_manager = EventManager::instance();
        $checker       = new GetTrackersQueryChecker($event_manager);
        $checker->checkQuery($json_decoder->decodeAsAnArray('query', $query));

        return true;
    }

    private function sendPaginationHeaders(int $limit, int $offset, int $size): void
    {
        Header::sendPaginationHeaders($limit, $offset, $size, self::MAX_LIMIT);
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
}
