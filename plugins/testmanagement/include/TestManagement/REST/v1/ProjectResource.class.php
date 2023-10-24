<?php
/**
 * Copyright (c) Enalean, 2014-present. All Rights Reserved.
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

namespace Tuleap\TestManagement\REST\v1;

use Luracast\Restler\RestException;
use PFUser;
use ProjectManager;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use Tracker_ReportFactory;
use Tracker_Semantic_StatusFactory;
use Tracker_URLVerification;
use TrackerFactory;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\Markdown\CommonMarkInterpreter;
use Tuleap\REST\Header;
use Tuleap\REST\ProjectAuthorization;
use Tuleap\REST\ProjectStatusVerificator;
use Tuleap\TestManagement\ArtifactDao;
use Tuleap\TestManagement\ArtifactFactory;
use Tuleap\TestManagement\Campaign\CampaignDao;
use Tuleap\TestManagement\Campaign\CampaignRetriever;
use Tuleap\TestManagement\Campaign\TestExecutionTestStatusDAO;
use Tuleap\TestManagement\Config;
use Tuleap\TestManagement\ConfigConformanceValidator;
use Tuleap\TestManagement\Dao;
use Tuleap\TestManagement\MalformedQueryParameterException;
use Tuleap\TestManagement\QueryToCriterionConverter;
use Tuleap\TestManagement\REST\v1\DefinitionRepresentations\DefinitionRepresentationBuilder;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\CachingTrackerPrivateCommentInformationRetriever;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\PermissionChecker;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentInformationRetriever;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupEnabledDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\REST\Artifact\ArtifactRepresentationBuilder;
use Tuleap\Tracker\REST\Artifact\Changeset\ChangesetRepresentationBuilder;
use Tuleap\Tracker\REST\Artifact\Changeset\Comment\CommentRepresentationBuilder;
use Tuleap\Tracker\Rule\FirstValidValueAccordingToDependenciesRetriever;
use Tuleap\Tracker\Semantic\Status\StatusValueRetriever;
use Tuleap\Tracker\Workflow\FirstPossibleValueInListRetriever;
use Tuleap\Tracker\Workflow\ValidValuesAccordingToTransitionsRetriever;
use UserManager;
use Workflow_Transition_ConditionFactory;

class ProjectResource
{
    public const MAX_LIMIT = 1000;

    /** @var PFUser */
    private $user;

    /** @var Config */
    private $config;

    /** @var ProjectManager */
    private $project_manager;

    /** @var TrackerFactory */
    private $tracker_factory;

    /** @var ArtifactFactory */
    private $testmanagement_artifact_factory;

    /** @var DefinitionRepresentationBuilder */
    private $definition_representation_builder;

    /** @var QueryToCriterionConverter */
    private $query_to_criterion_converter;

    /** @var CampaignRepresentationBuilder */
    private $campaign_representation_builder;

    public function __construct()
    {
        $this->config          = new Config(new Dao(), TrackerFactory::instance());
        $conformance_validator = new ConfigConformanceValidator($this->config);
        $this->project_manager = ProjectManager::instance();
        $this->tracker_factory = TrackerFactory::instance();
        $artifact_dao          = new ArtifactDao();
        $artifact_factory      = Tracker_ArtifactFactory::instance();

        $this->testmanagement_artifact_factory = new ArtifactFactory(
            $this->config,
            $artifact_factory,
            $artifact_dao
        );
        $tracker_factory                       = TrackerFactory::instance();
        $tracker_form_element_factory          = Tracker_FormElementFactory::instance();
        $this->user                            = UserManager::instance()->getCurrentUser();

        $retriever = new RequirementRetriever($artifact_factory, $artifact_dao, $this->config);

        $purifier                                =  \Codendi_HTMLPurifier::instance();
        $this->definition_representation_builder = new DefinitionRepresentationBuilder(
            $tracker_form_element_factory,
            $conformance_validator,
            $retriever,
            $purifier,
            CommonMarkInterpreter::build($purifier),
            new ArtifactRepresentationBuilder(
                Tracker_FormElementFactory::instance(),
                Tracker_ArtifactFactory::instance(),
                new TypeDao(),
                new ChangesetRepresentationBuilder(
                    UserManager::instance(),
                    Tracker_FormElementFactory::instance(),
                    new CommentRepresentationBuilder(
                        CommonMarkInterpreter::build(\Codendi_HTMLPurifier::instance())
                    ),
                    new PermissionChecker(new CachingTrackerPrivateCommentInformationRetriever(new TrackerPrivateCommentInformationRetriever(new TrackerPrivateCommentUGroupEnabledDao())))
                )
            ),
            \Tracker_Artifact_PriorityManager::build(),
        );

        $campaign_retriever = new CampaignRetriever($artifact_factory, new CampaignDao(), new KeyFactory());

        $this->campaign_representation_builder = new CampaignRepresentationBuilder(
            $tracker_factory,
            $tracker_form_element_factory,
            $this->testmanagement_artifact_factory,
            $campaign_retriever,
            new Config(new Dao(), $tracker_factory),
            new TestExecutionTestStatusDAO(),
            new StatusValueRetriever(
                new Tracker_Semantic_StatusFactory(),
                new FirstPossibleValueInListRetriever(
                    new FirstValidValueAccordingToDependenciesRetriever(
                        $tracker_form_element_factory
                    ),
                    new ValidValuesAccordingToTransitionsRetriever(
                        Workflow_Transition_ConditionFactory::build()
                    )
                )
            )
        );

        $this->query_to_criterion_converter = new QueryToCriterionConverter();
    }

    /**
     * @url OPTIONS {id}/testmanagement_campaigns
     *
     */
    public function optionsCampaigns(int $id): void
    {
        Header::allowOptionsGet();
    }

    /**
     * Get campaigns
     *
     * Get testing campaigns for a given project
     *
     * @url GET {id}/testmanagement_campaigns
     *
     * @param int    $id Id of the project
     * @param string $query JSON object of search criteria properties {@from path}
     * @param int    $limit Number of elements displayed per page {@from path}
     * @param int    $offset Position of the first element to display {@from path}
     *
     * @return array
     *
     * @access hybrid
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     */
    public function getCampaigns($id, $query = null, $limit = 10, $offset = 0)
    {
        $this->optionsCampaigns($id);

        try {
            $status_criterion = $this->query_to_criterion_converter->convertStatus($query);
        } catch (MalformedQueryParameterException $exception) {
            throw new RestException(400, $exception->getMessage());
        }

        try {
            $milestone_criterion = $this->query_to_criterion_converter->convertMilestone($query);
        } catch (MalformedQueryParameterException $exception) {
            throw new RestException(400, $exception->getMessage());
        }

        $project = $this->getProject($id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $this->user,
            $project
        );

        $campaign_tracker_id = $this->config->getCampaignTrackerId($project);
        if (! $campaign_tracker_id) {
            throw new RestException(400, 'The campaign tracker id is not well configured');
        }

        $campaign_tracker = $this->tracker_factory->getTrackerById($campaign_tracker_id);
        if (! $campaign_tracker) {
            throw new RestException(404, 'The campaign tracker does not exist');
        }

        if (! $campaign_tracker->userCanView($this->user)) {
            throw new RestException(403, 'Access denied to campaign tracker');
        }

        $paginated_campaigns_representations = $this->campaign_representation_builder
            ->getPaginatedCampaignsRepresentations($this->user, $campaign_tracker_id, $status_criterion, $milestone_criterion, $limit, $offset);

        $this->sendAllowHeaderForProjectCampaigns();
        $this->sendPaginationHeaders($limit, $offset, $paginated_campaigns_representations->getTotalSize());

        return $paginated_campaigns_representations->getCampaignsRepresentations();
    }

    /**
     * @url OPTIONS {id}/testmanagement_definitions
     *
     */
    public function optionsDefinitions(int $id): void
    {
        Header::allowOptionsGet();
    }

    /**
     * Get test definitions
     *
     * Get all test projects for a given project
     *
     * @url GET {id}/testmanagement_definitions
     *
     * @param int $id Id of the project
     * @param int $limit Number of elements displayed per page {@from path}
     * @param int $offset Position of the first element to display {@from path}
     * @param int $report_id Id of the report from which to get the definitions {@from path}
     *
     * @return array {DefinitionRepresentation}
     *
     * @throws RestException 403
     */
    protected function getDefinitions($id, $limit = 10, $offset = 0, $report_id = null)
    {
        $this->optionsDefinitions($id);

        $project = $this->getProject($id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $this->user,
            $project
        );

        $tracker_id = $this->config->getTestDefinitionTrackerId($project);
        if (! $tracker_id) {
            throw new RestException(400, 'The test definition tracker id is not well configured');
        }
        $tracker = $this->tracker_factory->getTrackerById($tracker_id);
        if (! $tracker) {
            throw new RestException(400, 'The test definition tracker id is not well configured');
        }

        if (! $tracker->userCanView($this->user)) {
            throw new RestException(403, 'Access denied to the test definition tracker');
        }

        if (isset($report_id)) {
            $result = $this->getDefinitionsSliceFromReport($report_id, $limit, $offset);
        } else {
            $result = $this->getDefinitionsSliceFromTracker($tracker_id, $limit, $offset);
        }

        $this->sendPaginationHeaders($limit, $offset, $result['total']);

        return $result['definitions'];
    }

    /** @return \Tracker_Report */
    private function getReportById(PFUser $user, int $id)
    {
        $store_in_session = false;
        $report           = Tracker_ReportFactory::instance()->getReportById(
            $id,
            $user->getId(),
            $store_in_session
        );

        if (! $report) {
            throw new RestException(404, 'The given report id does not correspond to any existing report');
        }

        $tracker = $report->getTracker();
        if (! $tracker->userCanView($user)) {
            throw new RestException(403, 'You are not allowed to access the requested report');
        }

        ProjectAuthorization::userCanAccessProject($user, $tracker->getProject(), new Tracker_URLVerification());

        return $report;
    }

    /**
     * @return array {Tracker_Artifact}
     */
    private function getDefinitionsSliceFromReport(int $report_id, int $limit, int $offset)
    {
        $report          = $this->getReportById($this->user, $report_id);
        $matching_ids    = $report->getMatchingIds();
        $artifacts       = [];
        $artifacts_count = 0;

        if (isset($matching_ids['id']) && ! empty($matching_ids['id'])) {
            $matching_artifact_ids = explode(',', $matching_ids['id']);
            $artifacts_count       = count($matching_artifact_ids);
            $slice_matching_ids    = array_slice($matching_artifact_ids, $offset, $limit);
            $artifacts             = $this->testmanagement_artifact_factory
                ->getArtifactsByIdListUserCanView($this->user, $slice_matching_ids);
        }

        return [
            'definitions' => $this->getDefinitionRepresentationsFromArtifactsList($artifacts),
            'total'       => $artifacts_count,
        ];
    }

    /**
     * @return array {Tracker_Artifact}
     */
    private function getDefinitionsSliceFromTracker(int $tracker_id, int $limit, int $offset)
    {
        $paginated_artifacts =
            $this->testmanagement_artifact_factory
            ->getPaginatedArtifactsByTrackerIdUserCanView(
                $this->user,
                $tracker_id,
                null,
                $limit,
                $offset,
                false
            );

        $artifacts       = $paginated_artifacts->getArtifacts();
        $artifacts_count = $paginated_artifacts->getTotalSize();

        return [
            'definitions' => $this->getDefinitionRepresentationsFromArtifactsList($artifacts),
            'total'       => $artifacts_count,
        ];
    }

    /** @return array {DefinitionRepresentation} */
    private function getDefinitionRepresentationsFromArtifactsList(array $artifacts)
    {
        $definition_representations = [];

        foreach ($artifacts as $artifact) {
            $definition_representation =
                $this->definition_representation_builder
                ->getMinimalRepresentation($this->user, $artifact);

            if ($definition_representation) {
                $definition_representations[] = $definition_representation;
            }
        }

        return $definition_representations;
    }

    private function getProject(int $id): \Project
    {
        $project = $this->project_manager->getProject($id);
        if ($project->isError()) {
            throw new RestException(404, 'Project not found');
        }

        return $project;
    }

    private function sendPaginationHeaders(int $limit, int $offset, int $size): void
    {
        Header::sendPaginationHeaders($limit, $offset, $size, self::MAX_LIMIT);
    }

    private function sendAllowHeaderForProjectCampaigns(): void
    {
        Header::allowOptionsGet();
    }
}
