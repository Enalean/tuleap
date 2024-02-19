<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\ProgramManagement\REST\v1;

use Luracast\Restler\RestException;
use ProjectManager;
use Tuleap\Cardwall\BackgroundColor\BackgroundColorBuilder;
use Tuleap\ProgramManagement\Adapter\ArtifactVisibleVerifier;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\Iteration\IterationContentDAO;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\Iteration\IterationsLinkedToProgramIncrementDAO;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Content\ContentDao;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Content\FeatureAdditionProcessor;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Content\FeatureRemovalProcessor;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\ProgramIncrementsDAO;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\UserCanLinkToProgramIncrementVerifier;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\Rank\FeaturesRankOrderer;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\Timebox\CrossReferenceRetriever;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\Timebox\StatusValueRetriever;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\Timebox\TimeframeValueRetriever;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\Timebox\TitleValueRetriever;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\Timebox\URIRetriever;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\Timebox\UserCanUpdateTimeboxVerifier;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\TopBacklog\ArtifactsExplicitTopBacklogDAO;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\TopBacklog\FeaturesToReorderProxy;
use Tuleap\ProgramManagement\Adapter\Program\Feature\BackgroundColorRetriever;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Content\FeatureHasPlannedUserStoriesVerifier;
use Tuleap\ProgramManagement\Adapter\Program\Feature\FeatureChecker;
use Tuleap\ProgramManagement\Adapter\Program\Feature\FeatureDAO;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Links\ArtifactsLinkedToParentDao;
use Tuleap\ProgramManagement\Adapter\Program\Plan\CanPrioritizeFeaturesDAO;
use Tuleap\ProgramManagement\Adapter\Program\Plan\PlanDao;
use Tuleap\ProgramManagement\Adapter\Program\Plan\PrioritizeFeaturesPermissionVerifier;
use Tuleap\ProgramManagement\Adapter\Program\Plan\ProgramAdapter;
use Tuleap\ProgramManagement\Adapter\Program\PlanningAdapter;
use Tuleap\ProgramManagement\Adapter\Program\ProgramDaoProject;
use Tuleap\ProgramManagement\Adapter\Team\MirroredTimeboxes\MirroredTimeboxesDao;
use Tuleap\ProgramManagement\Adapter\Team\TeamDao;
use Tuleap\ProgramManagement\Adapter\Team\VisibleTeamSearcher;
use Tuleap\ProgramManagement\Adapter\Workspace\ProjectManagerAdapter;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\Artifact\ArtifactFactoryAdapter;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\Fields\FormElementFactoryAdapter;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\Semantics\IsOpenRetriever;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\TrackerFactoryAdapter;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\TrackerOfArtifactRetriever;
use Tuleap\ProgramManagement\Adapter\Workspace\UserIsProgramAdminVerifier;
use Tuleap\ProgramManagement\Adapter\Workspace\UserManagerAdapter;
use Tuleap\ProgramManagement\Adapter\Workspace\UserProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\FeatureHasUserStoriesVerifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Feature;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links\UserStory;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationsRetriever;
use Tuleap\ProgramManagement\Domain\Program\Backlog\NotAllowedToPrioritizeException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Backlog\BacklogSearcher;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content\AddOrOrderMustBeSetException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content\ContentChange;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content\ContentModifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content\FeaturePlanner;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content\ProgramIncrementContentSearcher;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementHasNoProgramException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\UserCanPlanInProgramIncrementVerifier;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramAccessException;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Domain\Program\ProgramTrackerException;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\FeatureOfUserStoryRetriever;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\I18NRestException;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkUpdater;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkUpdaterDataFormater;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindDecoratorRetriever;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;

final class ProgramIncrementResource extends AuthenticatedResource
{
    private const MAX_LIMIT = 50;
    public const  ROUTE     = 'program_increment';

    /**
     * Get content of a program increment
     *
     * In a program increment get all the elements planned in team and linked to a program increment
     *
     * @url    GET {id}/content
     * @access hybrid
     *
     * @param int $id     Id of the program
     * @param int $limit  Number of elements displayed per page {@min 0} {@max 50}
     * @param int $offset Position of the first element to display {@min 0}
     *
     * @return FeatureRepresentation[]
     *
     * @throws RestException 401
     * @throws RestException 400
     */
    public function getContent(int $id, int $limit = self::MAX_LIMIT, int $offset = 0): array
    {
        $user_manager                   = \UserManager::instance();
        $user_retriever                 = new UserManagerAdapter($user_manager);
        $artifact_factory               = \Tracker_ArtifactFactory::instance();
        $artifact_retriever             = new ArtifactFactoryAdapter($artifact_factory);
        $tracker_retriever              = new TrackerFactoryAdapter(\TrackerFactory::instance());
        $artifacts_linked_to_parent_dao = new ArtifactsLinkedToParentDao();
        $visibility_verifier            = new ArtifactVisibleVerifier($artifact_factory, $user_retriever);

        $program_increment_content_retriever = new ProgramIncrementContentSearcher(
            new ProgramIncrementsDAO(),
            $visibility_verifier,
            new ContentDao(),
            $visibility_verifier,
            new TitleValueRetriever($artifact_retriever),
            new URIRetriever($artifact_retriever),
            new CrossReferenceRetriever($artifact_retriever),
            new TrackerOfArtifactRetriever($artifact_retriever),
            new BackgroundColorRetriever(
                new BackgroundColorBuilder(new BindDecoratorRetriever()),
                $artifact_retriever,
                $user_retriever
            ),
            new FeatureHasPlannedUserStoriesVerifier(
                $artifacts_linked_to_parent_dao,
                new PlanningAdapter(\PlanningFactory::build(), $user_retriever),
                $artifacts_linked_to_parent_dao
            ),
            new FeatureHasUserStoriesVerifier($artifacts_linked_to_parent_dao, $visibility_verifier),
            new IsOpenRetriever($artifact_retriever)
        );

        $user = $user_manager->getCurrentUser();
        try {
            $features = $program_increment_content_retriever->retrieveProgramIncrementContent(
                $id,
                UserProxy::buildFromPFUser($user)
            );

            $representations = array_map(
                static fn(Feature $feature) => FeatureRepresentation::fromFeature($tracker_retriever, $feature),
                $features
            );

            Header::sendPaginationHeaders($limit, $offset, count($representations), self::MAX_LIMIT);

            return array_slice($representations, $offset, $limit);
        } catch (ProgramIncrementNotFoundException $e) {
            throw new I18NRestException(404, $e->getI18NExceptionMessage());
        }
    }

    /**
     * Change the program increment's contents
     *
     * Plan elements in the program increment.
     *
     * <br>
     * Add example
     * <pre>
     * {
     *   "add": [
     *     { "id": 34 }
     *   ],
     *   "order": { "ids": [ 34 ], "compared_to": 35, "direction": "before" }
     * }
     * </pre>
     * <br>
     * The feature with id 34 is planned (added to the contents) of the Program Increment. It is also ordered before feature with id 35.
     * <code>order</code> is not mandatory.
     *
     * @url    PATCH {id}/content
     * @access protected
     *
     * @param int                                        $id ID of the program increment
     * @param ProgramIncrementContentPatchRepresentation $patch_representation {@from body}
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     */
    public function patchContent(int $id, ProgramIncrementContentPatchRepresentation $patch_representation): void
    {
        $user_manager            = \UserManager::instance();
        $user_retriever          = new UserManagerAdapter($user_manager);
        $artifact_factory        = \Tracker_ArtifactFactory::instance();
        $program_increments_dao  = new ProgramIncrementsDAO();
        $plan_dao                = new PlanDao();
        $program_dao             = new ProgramDaoProject();
        $artifact_retriever      = new ArtifactFactoryAdapter($artifact_factory);
        $tracker_factory         = \TrackerFactory::instance();
        $tracker_retriever       = new TrackerFactoryAdapter($tracker_factory);
        $form_element_factory    = \Tracker_FormElementFactory::instance();
        $field_retriever         = new FormElementFactoryAdapter($tracker_retriever, $form_element_factory);
        $project_manager_adapter = new ProjectManagerAdapter(ProjectManager::instance(), $user_retriever);
        $program_adapter         = ProgramAdapter::instance();
        $visibility_verifier     = new ArtifactVisibleVerifier($artifact_factory, $user_retriever);

        $artifact_link_updater = new ArtifactLinkUpdater(
            \Tracker_Artifact_PriorityManager::build(),
            new ArtifactLinkUpdaterDataFormater()
        );

        $artifacts_linked_to_parent_dao = new ArtifactsLinkedToParentDao();
        $user_story_linked_verifier     = new FeatureHasPlannedUserStoriesVerifier(
            $artifacts_linked_to_parent_dao,
            new PlanningAdapter(\PlanningFactory::build(), $user_retriever),
            $artifacts_linked_to_parent_dao
        );

        $project_access_checker = new ProjectAccessChecker(
            new RestrictedUserCanAccessProjectVerifier(),
            \EventManager::instance()
        );

        $modifier = new ContentModifier(
            new PrioritizeFeaturesPermissionVerifier(
                $project_manager_adapter,
                $project_access_checker,
                new CanPrioritizeFeaturesDAO(),
                $user_retriever,
                new UserIsProgramAdminVerifier($user_retriever)
            ),
            $program_increments_dao,
            $visibility_verifier,
            $plan_dao,
            new FeaturePlanner(
                $user_story_linked_verifier,
                new FeatureRemovalProcessor(
                    $program_increments_dao,
                    $artifact_factory,
                    $artifact_link_updater,
                    $user_retriever
                ),
                new ArtifactsExplicitTopBacklogDAO(),
                new FeatureAdditionProcessor($artifact_retriever, $artifact_link_updater, $user_retriever)
            ),
            new FeaturesRankOrderer(\Tracker_Artifact_PriorityManager::build()),
            new FeatureDAO(),
            new UserCanPlanInProgramIncrementVerifier(
                new UserCanUpdateTimeboxVerifier($artifact_retriever, $user_retriever),
                $program_increments_dao,
                new UserCanLinkToProgramIncrementVerifier($user_retriever, $field_retriever),
                $program_dao,
                $program_adapter,
                new VisibleTeamSearcher(
                    $program_dao,
                    $user_retriever,
                    $project_manager_adapter,
                    $project_access_checker,
                    new TeamDao()
                ),
            ),
            $visibility_verifier,
            $program_dao,
            $program_adapter,
        );

        $user = $user_manager->getCurrentUser();

        try {
            $potential_feature_id_to_add = $patch_representation->add[0]->id ?? null;

            $modifier->modifyContent(
                $id,
                ContentChange::fromFeatureAdditionAndReorder(
                    $potential_feature_id_to_add,
                    FeaturesToReorderProxy::buildFromRESTRepresentation($patch_representation->order)
                ),
                UserProxy::buildFromPFUser($user)
            );
        } catch (ProgramTrackerException | ProgramIncrementNotFoundException | ProgramIncrementHasNoProgramException | ProjectIsNotAProgramException $e) {
            throw new I18NRestException(404, $e->getI18NExceptionMessage());
        } catch (ProgramAccessException | NotAllowedToPrioritizeException $e) {
            throw new I18NRestException(403, $e->getI18NExceptionMessage());
        } catch (FeatureException | AddOrOrderMustBeSetException $e) {
            throw new I18NRestException(400, $e->getI18NExceptionMessage());
        }
    }

    /**
     * @url OPTIONS {id}/content
     *
     * @param int $id Id of the project
     */
    public function optionsContent(int $id): void
    {
        Header::allowOptionsGetPatch();
    }

    /**
     * Get iterations linked to a program increment
     *
     * In a program increment get all its iterations
     *
     * @url GET {id}/iterations
     * @access hybrid
     *
     * @param int $id Id of the program increment
     * @param int $limit Number of elements displayed per page {@min 0} {@max 50}
     * @param int $offset Position of the first element to display {@min 0}
     *
     * @return IterationRepresentation[]
     *
     * @throws RestException 401
     * @throws RestException 400
     */
    public function getIterations(int $id, int $limit = self::MAX_LIMIT, int $offset = 0): array
    {
        $user_manager       = \UserManager::instance();
        $user_retriever     = new UserManagerAdapter($user_manager);
        $artifact_factory   = \Tracker_ArtifactFactory::instance();
        $artifact_retriever = new ArtifactFactoryAdapter($artifact_factory);

        $iteration_retriever = new IterationsRetriever(
            new ProgramIncrementsDAO(),
            new ArtifactVisibleVerifier($artifact_factory, $user_retriever),
            new IterationsLinkedToProgramIncrementDAO(),
            new StatusValueRetriever($artifact_retriever, $user_retriever),
            new TitleValueRetriever($artifact_retriever),
            new TimeframeValueRetriever(
                $artifact_retriever,
                $user_retriever,
                SemanticTimeframeBuilder::build(),
                \Tuleap\ProgramManagement\ProgramManagementLogger::getLogger(),
            ),
            new URIRetriever($artifact_retriever),
            new CrossReferenceRetriever($artifact_retriever),
            new UserCanUpdateTimeboxVerifier($artifact_retriever, $user_retriever),
        );
        $user                = $user_manager->getCurrentUser();
        try {
            $iterations = $iteration_retriever->retrieveIterations(
                $id,
                UserProxy::buildFromPFUser($user)
            );

            $representations = [];
            foreach ($iterations as $iteration) {
                $representations[] = IterationRepresentation::buildFromIteration($iteration);
            }

            Header::sendPaginationHeaders($limit, $offset, count($representations), self::MAX_LIMIT);

            return array_slice($representations, $offset, $limit);
        } catch (ProgramIncrementNotFoundException $e) {
            throw new I18NRestException(404, $e->getI18NExceptionMessage());
        }
    }

    /**
     * @url OPTIONS {id}/iterations
     *
     * @param int $id Id of the program increment
     */
    public function optionsIterations(int $id): void
    {
        Header::allowOptionsGet();
    }

    /**
     * Get the backlog of the program increment
     *
     * It returns all user stories (from Team projects) that are children of
     * features planned in the program increment and that are _not_ also planned in any
     * of the teams' iterations. Contrary to the "content" route, it returns user stories, not features.
     * Features are in the Program project, user stories are children of features and are in Team projects.
     *
     * @url    GET {id}/backlog
     * @access hybrid
     *
     * @param int $id     Identifier of the program increment
     * @param int $limit  Number of elements displayed per page {@min 0} {@max 50}
     * @param int $offset Position of the first element to display {@min 0}
     *
     * @throws I18NRestException
     */
    public function getBacklog(int $id, int $limit = self::MAX_LIMIT, int $offset = 0): array
    {
        $user_manager        = \UserManager::instance();
        $user_retriever      = new UserManagerAdapter($user_manager);
        $tracker_retriever   = new TrackerFactoryAdapter(\TrackerFactory::instance());
        $artifact_factory    = \Tracker_ArtifactFactory::instance();
        $artifact_retriever  = new ArtifactFactoryAdapter($artifact_factory);
        $visibility_verifier = new ArtifactVisibleVerifier($artifact_factory, $user_retriever);

        $artifacts_linked_to_parent_dao = new ArtifactsLinkedToParentDao();
        $title_retriever                = new TitleValueRetriever($artifact_retriever);
        $uri_retriever                  = new URIRetriever($artifact_retriever);
        $background_color_retriever     = new BackgroundColorRetriever(
            new BackgroundColorBuilder(new BindDecoratorRetriever()),
            $artifact_retriever,
            $user_retriever
        );
        $cross_reference_retriever      = new CrossReferenceRetriever($artifact_retriever);
        $story_tracker_retriever        = new TrackerOfArtifactRetriever($artifact_retriever);
        $open_verifier                  = new IsOpenRetriever($artifact_retriever);
        $backlog_searcher               = new BacklogSearcher(
            new ProgramIncrementsDAO(),
            $visibility_verifier,
            new ContentDao(),
            $visibility_verifier,
            $artifacts_linked_to_parent_dao,
            $visibility_verifier,
            new IterationsLinkedToProgramIncrementDAO(),
            new MirroredTimeboxesDao(),
            new IterationContentDAO(),
            $title_retriever,
            $uri_retriever,
            $cross_reference_retriever,
            $open_verifier,
            $background_color_retriever,
            $story_tracker_retriever,
            new FeatureOfUserStoryRetriever(
                $title_retriever,
                $uri_retriever,
                $cross_reference_retriever,
                new FeatureHasPlannedUserStoriesVerifier(
                    $artifacts_linked_to_parent_dao,
                    new PlanningAdapter(\PlanningFactory::build(), $user_retriever),
                    $artifacts_linked_to_parent_dao
                ),
                new FeatureChecker(new PlanDao(), $visibility_verifier),
                $background_color_retriever,
                $story_tracker_retriever,
                $artifacts_linked_to_parent_dao,
                new FeatureHasUserStoriesVerifier($artifacts_linked_to_parent_dao, $visibility_verifier),
                $open_verifier
            )
        );

        $current_user = $user_manager->getCurrentUser();

        try {
            $user_identifier = UserProxy::buildFromPFUser($current_user);
            $user_stories    = $backlog_searcher->searchUnplannedUserStories($id, $user_identifier);

            $representations = array_map(
                static fn(UserStory $user_story) => UserStoryWithParentRepresentation::build($tracker_retriever, $user_story),
                $user_stories
            );

            Header::sendPaginationHeaders($limit, $offset, count($representations), self::MAX_LIMIT);

            return array_slice($representations, $offset, $limit);
        } catch (ProgramIncrementNotFoundException $e) {
            throw new I18NRestException(404, $e->getI18NExceptionMessage());
        }
    }

    /**
     * @url OPTIONS {id}/backlog
     *
     * @param int $id Id of the program increment
     */
    public function optionsBacklog(int $id): void
    {
        Header::allowOptionsGet();
    }
}
