<?php
/**
 * Copyright (c) Enalean, 2013-present. All Rights Reserved.
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

use Luracast\Restler\RestException;
use PermissionsManager;
use PFUser;
use Tracker;
use Tracker_Artifact_PriorityDao;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Artifact\PossibleParentsRetriever;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use Tracker_Report;
use Tracker_Report_InvalidRESTCriterionException as InvalidCriteriaException;
use Tracker_Report_REST;
use Tracker_ReportDao;
use Tracker_ReportFactory;
use Tracker_REST_TrackerRestBuilder;
use TrackerFactory;
use TransitionFactory;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Markdown\CommonMarkInterpreter;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\I18NRestException;
use Tuleap\REST\InvalidParameterTypeException;
use Tuleap\REST\JsonDecoder;
use Tuleap\REST\MissingMandatoryParameterException;
use Tuleap\REST\ProjectStatusVerificator;
use Tuleap\REST\QueryParameterParser;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\CachingTrackerPrivateCommentInformationRetriever;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\PermissionChecker;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentInformationRetriever;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupEnabledDao;
use Tuleap\Tracker\FormElement\Container\Fieldset\HiddenFieldsetChecker;
use Tuleap\Tracker\FormElement\Container\FieldsExtractor;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\PermissionsFunctionsWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SyntaxError;
use Tuleap\Tracker\Report\Query\Advanced\LimitSizeIsExceededException;
use Tuleap\Tracker\Report\Query\Advanced\SearchablesAreInvalidException;
use Tuleap\Tracker\Report\Query\Advanced\SearchablesDoNotExistException;
use Tuleap\Tracker\REST\Artifact\ArtifactRepresentation;
use Tuleap\Tracker\REST\Artifact\ArtifactRepresentationBuilder;
use Tuleap\Tracker\REST\Artifact\Changeset\ChangesetRepresentationBuilder;
use Tuleap\Tracker\REST\Artifact\Changeset\Comment\CommentRepresentationBuilder;
use Tuleap\Tracker\REST\Artifact\ParentArtifactRepresentation;
use Tuleap\Tracker\REST\Artifact\StatusValueRepresentation;
use Tuleap\Tracker\REST\CompleteTrackerRepresentation;
use Tuleap\Tracker\REST\FormElement\PermissionsForGroupsBuilder;
use Tuleap\Tracker\REST\FormElementRepresentationsBuilder;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;
use Tuleap\Tracker\REST\PermissionsExporter;
use Tuleap\Tracker\REST\ReportRepresentation;
use Tuleap\Tracker\REST\Tracker\PermissionsRepresentationBuilder;
use Tuleap\Tracker\REST\Tracker\UsedArtifactLinkTypeRepresentation;
use Tuleap\Tracker\REST\v1\Report\MatchingIdsOrderer;
use Tuleap\Tracker\REST\v1\Workflow\ModeUpdater;
use Tuleap\Tracker\REST\WorkflowRestBuilder;
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
use Tuleap\Tracker\Workflow\SimpleMode\TransitionReplicator;
use Tuleap\Tracker\Workflow\SimpleMode\TransitionReplicatorBuilder;
use UserManager;
use Workflow_Dao;
use WorkflowFactory;

/**
 * Wrapper for Tracker related REST methods
 */
class TrackersResource extends AuthenticatedResource
{
    public const MAX_LIMIT            = 1000;
    public const DEFAULT_LIMIT        = 100;
    public const DEFAULT_OFFSET       = 0;
    public const DEFAULT_VALUES       = null;
    public const ALL_VALUES           = 'all';
    public const DEFAULT_CRITERIA     = '';
    public const ORDER_ASC            = 'asc';
    public const ORDER_DESC           = 'desc';
    public const DEFAULT_EXPERT_QUERY = '';

    private UserManager $user_manager;
    private Tracker_FormElementFactory $formelement_factory;
    private Tracker_ReportFactory $report_factory;
    private PermissionsManager $permission_manager;
    private TrackerFactory $tracker_factory;
    private Tracker_ArtifactFactory $tracker_artifact_factory;
    private ReportArtifactFactory $report_artifact_factory;

    public function __construct()
    {
        $this->user_manager             = UserManager::instance();
        $this->formelement_factory      = Tracker_FormElementFactory::instance();
        $this->report_factory           = Tracker_ReportFactory::instance();
        $this->permission_manager       = PermissionsManager::instance();
        $this->tracker_factory          = TrackerFactory::instance();
        $this->tracker_artifact_factory = Tracker_ArtifactFactory::instance();
        $this->report_artifact_factory  = new ReportArtifactFactory(
            $this->tracker_artifact_factory,
            new MatchingIdsOrderer(new Tracker_Artifact_PriorityDao()),
        );
    }

    /**
     * @url OPTIONS
     */
    public function options()
    {
        Header::allowOptions();
    }

    /**
     * @url OPTIONS {id}
     *
     * @param string $id Id of the tracker
     */
    public function optionsId($id)
    {
        $this->sendAllowHeaderForTracker();
    }

    /**
     * Get tracker
     *
     * Get the definition of the given tracker
     *
     * @url GET {id}
     * @access hybrid
     * @oauth2-scope read:tracker
     *
     * @param int $id Id of the tracker
     *
     * @return CompleteTrackerRepresentation
     * @throws RestException 403
     * @throws RestException 404
     */
    public function getId($id)
    {
        $this->checkAccess();

        $builder = $this->getTrackerRepresentationBuilder();
        $user    = $this->user_manager->getCurrentUser();
        $tracker = $this->getTrackerById($user, $id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $this->user_manager->getCurrentUser(),
            $tracker->getProject()
        );

        $this->sendAllowHeaderForTracker();

        return $builder->getTrackerRepresentationInTrackerContext($user, $tracker);
    }

    /**
     * @url OPTIONS {id}/tracker_reports
     *
     * @param string $id Id of the tracker
     */
    public function optionsReports($id)
    {
        Header::allowOptionsGet();
    }

    /**
     * Get all reports of a given tracker
     *
     * All reports the user can see
     *
     * @url GET {id}/tracker_reports
     * @access hybrid
     * @oauth2-scope read:tracker
     *
     * @param int $id Id of the tracker
     * @param int $limit Number of elements displayed per page {@from path}{@min 1}{@max 1000}
     * @param int $offset Position of the first element to display {@from path}{@min 0}
     *
     * @return array {@type Tuleap\Tracker\REST\ReportRepresentation}
     * @throws RestException 403
     * @throws RestException 404
     */
    public function getReports($id, $limit = 10, $offset = self::DEFAULT_OFFSET)
    {
        $this->checkAccess();

        $user    = $this->user_manager->getCurrentUser();
        $tracker = $this->getTrackerById($user, $id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $this->user_manager->getCurrentUser(),
            $tracker->getProject()
        );

        $all_reports = $this->report_factory->getReportsByTrackerId($tracker->getId(), $user->getId());

        $nb_of_reports = count($all_reports);
        $reports       = array_slice($all_reports, $offset, $limit);

        Header::allowOptionsGet();
        Header::sendPaginationHeaders($limit, $offset, $nb_of_reports, self::MAX_LIMIT);

        return array_map(
            function (Tracker_Report $report) {
                return new ReportRepresentation($report);
            },
            $reports
        );
    }

    /**
     * @url OPTIONS {id}/artifacts
     */
    public function optionsArtifacts($id)
    {
        Header::allowOptionsGet();
    }

    /**
     * Get all artifacts of a given tracker
     *
     * Get all artifacts of a given tracker the user can view
     *<br><br>
     * Notes on the query parameter
     * <ol>
     *  <li>It must be a URL-encoded JSON object</li>
     *  <li>The basic form of a property is [field_id|field_shortname] : [number|string|array(number)]
     *      <br>Example: {"1258" : "bug"} OR {"title" : "bug"}
     *  </li>
     *  <li>The complex form of a property is "field_id" : {"operator" : "operator_name", "value" : [number|string|array(number)]}
     *      <br>Example: {"title" : {"operator" : "contains", "value" : "bug"}}
     *  </li>
     *  <li>For text or number-like fields, the allowed operator is "contains". The value must be a string or number</li>
     *  <li>For list fields (like selectboxes or openlists), the allowed operator is "contains". The value(s) are bind_value_id</li>
     *  <li>For date-like fields, the allowed operators are ["="|"<"|">"|"between"]. Dates must be in ISO date format</li>
     *  <li>Full example: {"title" : "bug", "2458" : {"operator" : "between", "value", ["2014-02-25", "2014-03-25T00:00:00-05:00"]}}</li>
     * </ol>
     * <br><br>
     * Notes on the expert query parameter
     * <ol>
     *  <li>You can use: AND, OR, WITH|WITHOUT PARENT, WITH|WITHOUT CHILDREN, BETWEEN(), NOW(), IN(), NOT IN(), MYSELF() parenthesis.
     *  <li>The basic form of a property is [field_shortname] = [string]
     *      <br>Example: sprint_name='s1' AND description='desc1'
     *  </li>
     * </ol>
     *
     * @url GET {id}/artifacts
     * @access hybrid
     * @oauth2-scope read:tracker
     *
     * @param int    $id             ID of the tracker
     * @param string $values         Which fields to include in the response. Default is no field values {@from path}{@choice ,all}
     * @param int    $limit          Number of elements displayed per page {@from path}{@min 1}{@max 1000}
     * @param int    $offset         Position of the first element to display {@from path}{@min 0}
     * @param string $query          JSON object of search criteria properties {@from path}
     * @param string $expert_query   Query with AND, OR, WITH|WITHOUT PARENT, WITH|WITHOUT CHILDREN, BETWEEN(), NOW(), IN(), NOT IN(), MYSELF(), parenthesis
     *                               and Text, Integer, Float, Date, List fields
     *                               <b>Does not work with query parameter</b> {@from path}
     * @param string $order          By default the artifacts are returned by Artifact ID ASC. Set this parameter to either ASC or DESC
     *                               <b>Does not work with query and expert_query parameters</b> {@from path}{@choice asc,desc}
     *
     * @return array {@type Tuleap\Tracker\REST\Artifact\ArtifactRepresentation}
     * @throws RestException 400
     * @throws RestException 404
     * @throws RestException 403
     */
    public function getArtifacts(
        $id,
        $values = self::DEFAULT_VALUES,
        $limit = self::DEFAULT_LIMIT,
        $offset = self::DEFAULT_OFFSET,
        $query = self::DEFAULT_CRITERIA,
        $expert_query = self::DEFAULT_EXPERT_QUERY,
        $order = self::ORDER_ASC,
    ) {
        $this->checkAccess();

        $user          = $this->user_manager->getCurrentUser();
        $valid_tracker = $this->getTrackerById($user, $id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $this->user_manager->getCurrentUser(),
            $valid_tracker->getProject()
        );

        if ($query) {
            $artifacts = $this->getArtifactsMatchingFromCriteria($user, $valid_tracker, $query, $offset, $limit);
        } elseif ($expert_query) {
            $artifacts = $this->getArtifactsMatchingFromExpertQuery($user, $valid_tracker, $expert_query, $offset, $limit);
        } else {
            $reverse_order = (bool) (strtolower($order) === self::ORDER_DESC);

            $pagination  = $this->tracker_artifact_factory->getPaginatedArtifactsByTrackerId(
                $id,
                $limit,
                $offset,
                $reverse_order
            );
            $nb_matching = $pagination->getTotalSize();
            $artifacts   = $pagination->getArtifacts();
            Header::sendPaginationHeaders($limit, $offset, $nb_matching, self::MAX_LIMIT);
        }

        Header::allowOptionsGet();

        $with_all_field_values = ($values == self::ALL_VALUES);
        return $this->getListOfArtifactRepresentation(
            $user,
            $artifacts,
            $with_all_field_values
        );
    }

    /**
     * @throws RestException 400
     */
    private function getArtifactsMatchingFromCriteria(PFUser $user, Tracker $tracker, $query, $offset, $limit)
    {
        $report = new Tracker_Report_REST(
            $user,
            $tracker,
            $this->permission_manager,
            new Tracker_ReportDao(),
            $this->formelement_factory
        );

        try {
            $report->setRESTCriteria($query);
            return $this->getArtifactsMatching($report, $offset, $limit);
        } catch (InvalidCriteriaException $e) {
            throw new RestException(400, $e->getMessage());
        }
    }

    private function getArtifactsMatchingFromExpertQuery(PFUser $user, Tracker $tracker, $query, $offset, $limit)
    {
        $report = new Tracker_Report_REST(
            $user,
            $tracker,
            $this->permission_manager,
            new Tracker_ReportDao(),
            $this->formelement_factory
        );

        $report->setIsInExpertMode(true);
        $report->setExpertQuery(stripslashes($query));

        $this->validateExpertQuery($report);

        return $this->getArtifactsMatching($report, $offset, $limit);
    }

    private function getArtifactsMatching(Tracker_Report_REST $report, $offset, $limit)
    {
        $artifact_collection = $this->report_artifact_factory->getArtifactsMatchingReport($report, $limit, $offset);
        Header::sendPaginationHeaders($limit, $offset, $artifact_collection->getTotalSize(), self::MAX_LIMIT);
        return $artifact_collection->getArtifacts();
    }

    private function validateExpertQuery(Tracker_Report_REST $report)
    {
        try {
            $report->validateExpertQuery();
        } catch (SearchablesDoNotExistException $exception) {
            throw new RestException(
                400,
                $exception->getMessage()
            );
        } catch (SearchablesAreInvalidException $exception) {
            throw new RestException(
                400,
                $exception->getMessage()
            );
        } catch (SyntaxError $exception) {
            throw new RestException(
                400,
                "Error during parsing expert query"
            );
        } catch (LimitSizeIsExceededException $exception) {
            throw new RestException(
                400,
                "The query is considered too complex to be executed by the server. Please simplify it (e.g remove comparisons) to continue."
            );
        }
    }

    /**
     * @return ArtifactRepresentation[]
     */
    private function getListOfArtifactRepresentation(PFUser $user, $artifacts, $with_all_field_values)
    {
        $builder = new ArtifactRepresentationBuilder(
            $this->formelement_factory,
            $this->tracker_artifact_factory,
            new TypeDao(),
            new ChangesetRepresentationBuilder(
                $this->user_manager,
                $this->formelement_factory,
                new CommentRepresentationBuilder(
                    CommonMarkInterpreter::build(\Codendi_HTMLPurifier::instance())
                ),
                new PermissionChecker(new CachingTrackerPrivateCommentInformationRetriever(new TrackerPrivateCommentInformationRetriever(new TrackerPrivateCommentUGroupEnabledDao())))
            )
        );

        $build_artifact_representation = function (?Artifact $artifact) use (
            $builder,
            $user,
            $with_all_field_values
        ) {
            if (! $artifact || ! $artifact->userCanView($user)) {
                return;
            }

            if ($with_all_field_values) {
                $tracker_representation = MinimalTrackerRepresentation::build($artifact->getTracker());

                return $builder->getArtifactRepresentationWithFieldValues($user, $artifact, $tracker_representation, StatusValueRepresentation::buildFromArtifact($artifact, $user));
            } else {
                return $builder->getArtifactRepresentation($user, $artifact, StatusValueRepresentation::buildFromArtifact($artifact, $user));
            }
        };

        $list_of_artifact_representation = array_map($build_artifact_representation, $artifacts);

        return array_values(array_filter($list_of_artifact_representation));
    }

    /**
     * @url OPTIONS {id}/parent_artifacts
     */
    public function optionsParentArtifacts($id)
    {
        Header::allowOptionsGet();
    }

    /**
     * Get all possible parent artifacts for a given tracker
     *
     * Given a tracker, get all open artifacts of its parent tracker ordered by their artifact id
     * in decreasing order.
     * If the given tracker doesn't have a parent, it throws an error.
     *
     * @url GET {id}/parent_artifacts
     * @access hybrid
     * @oauth2-scope read:tracker
     *
     * @param int $id
     * @param int $limit Number of elements displayed per page {@from path}{@min 1}{@max 1000}
     * @param int $offset Position of the first element to display {@from path}{@min 0}
     *
     * @return array {@type ParentArtifactRepresentation}
     * @throws RestException 403
     * @throws RestException 404
     */
    public function getParentArtifacts($id, $limit = self::DEFAULT_LIMIT, $offset = self::DEFAULT_OFFSET)
    {
        $this->checkAccess();

        $user    = $this->user_manager->getCurrentUser();
        $tracker = $this->getTrackerById($user, $id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $this->user_manager->getCurrentUser(),
            $tracker->getProject()
        );

        $possible_parents_getr = new PossibleParentsRetriever($this->tracker_artifact_factory, \EventManager::instance());
        $possible_parents      = $possible_parents_getr->getPossibleArtifactParents($tracker, $user, $limit, $offset, false);
        $pagination            = $possible_parents->getPossibleParents();
        if (! $pagination || ! $possible_parents->isSelectorDisplayed()) {
            Header::sendPaginationHeaders($limit, $offset, 0, self::MAX_LIMIT);
            return [];
        }
        $nb_matching = $pagination->getTotalSize();
        Header::sendPaginationHeaders($limit, $offset, $nb_matching, self::MAX_LIMIT);
        return array_map([$this, 'getParentArtifactRepresentation'], array_values($pagination->getArtifacts()));
    }

    private function getParentArtifactRepresentation(Artifact $artifact): ParentArtifactRepresentation
    {
        $user                  = $this->user_manager->getCurrentUser();
        $status_representation = null;
        if ($artifact->getStatus()) {
            $status_representation = StatusValueRepresentation::buildFromArtifact($artifact, $user);
        }
        return ParentArtifactRepresentation::build($artifact, $status_representation);
    }

    /**
     * @return Tracker
     * @throws RestException
     */
    private function getTrackerById(\PFUser $user, $id)
    {
        $tracker = $this->tracker_factory->getTrackerById($id);
        if ($tracker) {
            $permissions_checker = new TrackerPermissionsChecker(new \URLVerification());
            $permissions_checker->checkRead($user, $tracker);
            return $tracker;
        }
        throw new RestException(404);
    }

    /**
     * Partial update of a tracker.
     *
     * Only tracker administrators are allowed to patch trackers.
     *
     * To set the field transitions are based on:
     * <pre>
     * {
     *   "workflow": {
     *     "set_transitions_rules": {
     *       "field_id": 1234
     *     }
     *   }
     * }
     * </pre>
     *
     * Enable or disable transition rules:
     * <pre>
     * {
     *   "workflow": {
     *     "set_transitions_rules": {
     *       "is_used": true
     *     }
     *   }
     * }
     * </pre>
     *
     * To remove the field transitions are based on (in order to set new field):
     * <pre>
     * {
     *   "workflow": {
     *     "delete_transitions_rules": true
     *   }
     * }
     * </pre>
     * (*) This will remove all associated transitions and rules.<br />
     * <br />
     * /!\ "set_transitions_rules" and "delete_transitions_rules" cannot be used at the same time.
     *
     * <br/>
     * Deactivate legacy transitions:
     * <pre>
     * {
     *   "workflow": {
     *       "is_legacy": false
     *   }
     * }
     * </pre>
     * <br/>
     * /!\ A workflow cannot be switched from standard to legacy.
     *
     * <br/>
     * Switch a workflow from simple to advanced mode:
     * <pre>
     * {
     *   "workflow": {
     *       "is_advanced": true
     *   }
     * }
     * </pre>
     * <br/>
     *
     * Switch a workflow from advanced to simple mode:
     * <pre>
     * {
     *   "workflow": {
     *       "is_advanced": false
     *   }
     * }
     * </pre>
     * <br/>
     *
     * @url PATCH {id}
     * @access protected
     *
     * @param int    $id    Id of the tracker.
     * @param string $query JSON object of search criteria properties {@from query}
     *
     * @return CompleteTrackerRepresentation
     *
     * @throws I18NRestException 500
     * @throws I18NRestException 400
     * @throws I18NRestException 404
     * @throws RestException 401
     * @throws RestException 403
     */
    public function patchWorkflow($id, $query = '')
    {
        $this->checkAccess();

        $tracker_id   = $id;
        $user         = $this->user_manager->getCurrentUser();
        $tracker      = $this->getTrackerById($user, $tracker_id);
        $json_decoder = $this->getJsonDecoder();

        $this->sendAllowHeaderForTracker();

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt($tracker->getProject());
        (new TrackerPermissionsChecker(new \URLVerification()))->checkUpdateWorkflow($user, $tracker);

        $parameterParser = new QueryParameterParser($json_decoder);

        try {
            $workflow_query = $parameterParser->getObject($query, 'workflow');

            $this->processWorkflowTransitionPatchQuery($workflow_query, $tracker);
            $this->getWorkflowFactory()->clearTrackerWorkflowFromCache($tracker);
            $tracker->workflow = null;

            $builder = $this->getTrackerRepresentationBuilder();
            return $builder->getTrackerRepresentationInTrackerContext($user, $tracker);
        } catch (InvalidParameterTypeException $e) {
            throw new I18NRestException(400, dgettext('tuleap-tracker', 'Please provide a valid query.'));
        } catch (MissingMandatoryParameterException $e) {
            throw new I18NRestException(400, dgettext('tuleap-tracker', 'Please provide a valid query.'));
        }
    }

    /**
     * @param array $workflow_query
     *
     * @throws I18NRestException 500
     * @throws I18NRestException 400
     * @throws I18NRestException 404
     */
    private function processWorkflowTransitionPatchQuery(array $workflow_query, Tracker $tracker)
    {
        if (isset($workflow_query['set_transitions_rules']) && isset($workflow_query['delete_transitions_rules'])) {
            throw new I18NRestException(400, dgettext('tuleap-tracker', 'Please provide a valid query.'));
        }

        if (isset($workflow_query['delete_transitions_rules']) && $workflow_query['delete_transitions_rules'] === true) {
            return $this->deleteTransitionsRules(
                $tracker
            );
        }

        if (isset($workflow_query['set_transitions_rules']) && count($workflow_query['set_transitions_rules']) > 0) {
            return $this->setTransitionsRules(
                $workflow_query['set_transitions_rules'],
                $tracker
            );
        }

        if (isset($workflow_query['is_legacy']) && $workflow_query['is_legacy'] === false) {
            return $this->deactivateLegacyTransitions($tracker);
        }

        if (isset($workflow_query['is_advanced'])) {
            $workflow_mode_updater = $this->getModeUpdater();
            $transaction_executor  = new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection());

            $transaction_executor->execute(
                function () use ($workflow_mode_updater, $tracker, $workflow_query) {
                    if ($workflow_query['is_advanced'] === true) {
                        $workflow_mode_updater->switchWorkflowToAdvancedMode($tracker);
                    } else {
                        $workflow_mode_updater->switchWorkflowToSimpleMode($tracker);
                    }
                }
            );
            return;
        }

        throw new I18NRestException(400, dgettext('tuleap-tracker', 'Please provide a valid query.'));
    }

    private function getModeUpdater(): ModeUpdater
    {
        return new ModeUpdater(
            new Workflow_Dao(),
            $this->getTransitionReplicator(),
            new FrozenFieldsDao(),
            new HiddenFieldsetsDao(),
            new StateFactory(
                TransitionFactory::instance(),
                new SimpleWorkflowDao()
            ),
            new TransitionExtractor()
        );
    }

    private function getTransitionReplicator(): TransitionReplicator
    {
        return TransitionReplicatorBuilder::build();
    }

    private function deactivateLegacyTransitions(Tracker $tracker): void
    {
        $workflow_id = $tracker->getWorkflow()->getId();

        (new \Workflow_Dao())->removeWorkflowLegacyState((int) $workflow_id);
    }

    /**
     * @param array $set_transitions_rules_query
     *
     * @return int Created workflow id
     * @throws I18NRestException 500
     * @throws I18NRestException 400
     * @throws I18NRestException 404
     */
    private function setTransitionsRules(array $set_transitions_rules_query, Tracker $tracker)
    {
        if (isset($set_transitions_rules_query['field_id'])) {
            if (! is_int($set_transitions_rules_query['field_id'])) {
                throw new I18NRestException(400, dgettext('tuleap-tracker', 'Please provide a valid query.'));
            }
            $field_id = $set_transitions_rules_query['field_id'];
            return $this->updateWorkflowTransitionFieldId($tracker, $field_id);
        }

        if (isset($set_transitions_rules_query['is_used'])) {
            if (! is_bool($set_transitions_rules_query['is_used'])) {
                throw new I18NRestException(400, dgettext('tuleap-tracker', 'Please provide a valid query.'));
            }
            $is_used = $set_transitions_rules_query['is_used'];
            return $this->updateWorkflowTransitionActivation($tracker, $is_used);
        }
    }

    /**
     * @throws RestException
     */
    private function deleteTransitionsRules(Tracker $tracker)
    {
        $tracker_id       = $tracker->getId();
        $workflow_factory = $this->getWorkflowFactory();
        $workflow         = $workflow_factory->getWorkflowByTrackerId($tracker_id);
        if (! $workflow) {
            throw new I18NRestException(400, dgettext('tuleap-tracker', "This tracker has no workflow."));
        }

        if (! $workflow_factory->deleteWorkflow($workflow->getId())) {
            throw new I18NRestException(500, dgettext('tuleap-tracker', "An error has occurred, the workflow couldn't be reset."));
        }
    }

    /**
     * @return int New workflow id
     * @throws I18NRestException
     */
    private function updateWorkflowTransitionFieldId(Tracker $tracker, $field_id)
    {
        $workflow_factory = $this->getWorkflowFactory();
        if ($workflow_factory->getWorkflowByTrackerId($tracker->getId()) != null) {
            throw new I18NRestException(400, dgettext('tuleap-tracker', 'A workflow already exists on the given tracker.'));
        }

        $field = $this->formelement_factory->getFieldById($field_id);
        if (! $field) {
            throw new I18NRestException(404, dgettext('tuleap-tracker', 'Field not found.'));
        }

        $new_workflow_id = $workflow_factory->create($tracker->getId(), $field->getId());
        if (! $new_workflow_id) {
            throw new I18NRestException(500, dgettext('tuleap-tracker', "An error has occurred, the workflow couldn't be created."));
        }

        return $new_workflow_id;
    }

    /**
     * @return int Updated workflow id
     * @throws I18NRestException 500
     */
    private function updateWorkflowTransitionActivation(Tracker $tracker, $is_used)
    {
        $workflow_factory = $this->getWorkflowFactory();
        $workflow         = $workflow_factory->getWorkflowByTrackerId($tracker->getId());
        if (! $workflow) {
            throw new I18NRestException(400, dgettext('tuleap-tracker', "No workflow defined on the given tracker."));
        }

        $workflow_id = $workflow_factory->updateActivation($workflow->getId(), $is_used);
        if (! $workflow_id) {
            throw new I18NRestException(500, dgettext('tuleap-tracker', "An error has occurred, the workflow couldn't be updated."));
        }

        return $workflow_id;
    }

    /**
     * @return WorkflowFactory
     */
    private function getWorkflowFactory()
    {
        return WorkflowFactory::instance();
    }

    private function getTrackerRepresentationBuilder(): Tracker_REST_TrackerRestBuilder
    {
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
                Tracker_FormElementFactory::instance()
            )
        );

        return new Tracker_REST_TrackerRestBuilder(
            $this->formelement_factory,
            new FormElementRepresentationsBuilder(
                $this->formelement_factory,
                new PermissionsExporter(
                    $frozen_fields_detector
                ),
                new HiddenFieldsetChecker(
                    new HiddenFieldsetsDetector(
                        $transition_retriever,
                        new HiddenFieldsetsRetriever(
                            new HiddenFieldsetsDao(),
                            $this->formelement_factory
                        ),
                        Tracker_FormElementFactory::instance()
                    ),
                    new FieldsExtractor()
                ),
                new PermissionsForGroupsBuilder(
                    new \UGroupManager(),
                    $frozen_fields_detector,
                    new PermissionsFunctionsWrapper()
                ),
                new TypePresenterFactory(
                    new TypeDao(),
                    new ArtifactLinksUsageDao()
                )
            ),
            new PermissionsRepresentationBuilder(
                new \UGroupManager(),
                new PermissionsFunctionsWrapper()
            ),
            new WorkflowRestBuilder()
        );
    }

    private function getParentTracker(\PFUser $user, Tracker $tracker)
    {
        $parent = $tracker->getParent();

        if (! $parent) {
            throw new RestException(404, 'This tracker has no parent tracker');
        }

        if ($parent->isDeleted()) {
            throw new RestException(404, "This tracker's parent is deleted");
        }

        if (! $parent->userCanView($user)) {
            throw new RestException(403);
        }
        return $parent;
    }

    private function sendAllowHeaderForTracker()
    {
        Header::allowOptionsGetPatch();
    }

    private function getJsonDecoder()
    {
        return new JsonDecoder();
    }

    /**
     * @url GET {id}/used_artifact_links
     * @access hybrid
     * @oauth2-scope read:tracker
     */
    public function optionsUserArtifactLinkTypes(int $id): void
    {
        Header::allowOptionsGet();
    }

    /**
     * Get all currently used artifact link types in a tracker
     *
     * @url GET {id}/used_artifact_links
     * @access hybrid
     * @oauth2-scope read:tracker
     *
     * @param int $id Id of the tracker
     * @param int $limit Number of elements displayed per page {@from path}{@min 1}{@max 1000}
     * @param int $offset Position of the first element to display {@from path}{@min 0}
     *
     * @return array {@type UsedArtifactLinkTypeRepresentation}
     * @psalm-return UsedArtifactLinkTypeRepresentation[]
     * @throws RestException 403
     * @throws RestException 404
     */
    public function getUserArtifactLinkTypes(int $id, int $limit = 10, int $offset = self::DEFAULT_OFFSET): array
    {
        $this->checkAccess();

        $user    = $this->user_manager->getCurrentUser();
        $tracker = $this->getTrackerById($user, $id);

        $factory = new TypePresenterFactory(new TypeDao(), new ArtifactLinksUsageDao());

        $type_presenters = $factory->getAllCurrentlyUsedTypePresentersByTracker($tracker);

        Header::sendPaginationHeaders($limit, $offset, count($type_presenters), self::MAX_LIMIT);

        $representations = [];

        foreach (array_slice($type_presenters, $offset, $limit) as $type_presenter) {
            $representations[] = UsedArtifactLinkTypeRepresentation::fromTypePresenter($type_presenter);
        }

        return $representations;
    }
}
