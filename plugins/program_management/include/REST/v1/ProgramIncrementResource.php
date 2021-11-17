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

use BackendLogger;
use Luracast\Restler\RestException;
use ProjectManager;
use Tuleap\Cardwall\BackgroundColor\BackgroundColorBuilder;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\ProgramManagement\Adapter\ArtifactVisibleVerifier;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\Iteration\IterationsLinkedToProgramIncrementDAO;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Content\FeatureAdditionProcessor;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Content\FeatureRemovalProcessor;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\ProgramIncrementsDAO;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\UserCanLinkToProgramIncrementVerifier;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\Rank\FeaturesRankOrderer;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\Timebox\CrossReferenceRetriever;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\Timebox\StatusValueRetriever;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\Timebox\TimeframeValueRetriever;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\Timebox\TitleValueRetriever;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\Timebox\UriRetriever;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\Timebox\UserCanUpdateTimeboxVerifier;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\TopBacklog\ArtifactsExplicitTopBacklogDAO;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\TopBacklog\FeaturesToReorderProxy;
use Tuleap\ProgramManagement\Adapter\Program\Feature\BackgroundColorRetriever;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Content\ContentDao;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Content\FeatureContentRetriever;
use Tuleap\ProgramManagement\Adapter\Program\Feature\FeatureDAO;
use Tuleap\ProgramManagement\Adapter\Program\Feature\FeatureRepresentationBuilder;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Links\ArtifactsLinkedToParentDao;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Links\UserStoryLinkedToFeatureVerifier;
use Tuleap\ProgramManagement\Adapter\Program\Feature\VerifyIsVisibleFeatureAdapter;
use Tuleap\ProgramManagement\Adapter\Program\Plan\CanPrioritizeFeaturesDAO;
use Tuleap\ProgramManagement\Adapter\Program\Plan\PlanDao;
use Tuleap\ProgramManagement\Adapter\Program\Plan\PrioritizeFeaturesPermissionVerifier;
use Tuleap\ProgramManagement\Adapter\Program\Plan\ProgramAdapter;
use Tuleap\ProgramManagement\Adapter\Program\PlanningAdapter;
use Tuleap\ProgramManagement\Adapter\Program\ProgramDao;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\Artifact\ArtifactFactoryAdapter;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\Fields\FormElementFactoryAdapter;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\TrackerFactoryAdapter;
use Tuleap\ProgramManagement\Adapter\Workspace\UserManagerAdapter;
use Tuleap\ProgramManagement\Adapter\Workspace\UserProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationsRetriever;
use Tuleap\ProgramManagement\Domain\Program\Backlog\NotAllowedToPrioritizeException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content\AddOrOrderMustBeSetException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content\ContentChange;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content\ContentModifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content\FeaturePlanner;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementHasNoProgramException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\UserCanPlanInProgramIncrementVerifier;
use Tuleap\ProgramManagement\Domain\Program\Plan\PlanTrackerException;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramAccessException;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Domain\Program\ProgramTrackerException;
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
    public function getBacklog(int $id, int $limit = self::MAX_LIMIT, int $offset = 0): array
    {
        $user_manager       = \UserManager::instance();
        $user_retriever     = new UserManagerAdapter($user_manager);
        $artifact_factory   = \Tracker_ArtifactFactory::instance();
        $program_dao        = new ProgramDao();
        $artifact_retriever = new ArtifactFactoryAdapter($artifact_factory);

        $artifacts_linked_to_parent_dao = new ArtifactsLinkedToParentDao();
        $user_story_linked_verifier     = new UserStoryLinkedToFeatureVerifier(
            $artifacts_linked_to_parent_dao,
            new PlanningAdapter(\PlanningFactory::build(), $user_retriever),
            $artifact_factory,
            $user_retriever,
            $artifacts_linked_to_parent_dao,
            $artifacts_linked_to_parent_dao
        );

        $program_increment_content_retriever = new FeatureContentRetriever(
            new ProgramIncrementsDAO(),
            new ContentDao(),
            new FeatureRepresentationBuilder(
                $artifact_retriever,
                \Tracker_FormElementFactory::instance(),
                new BackgroundColorRetriever(
                    new BackgroundColorBuilder(new BindDecoratorRetriever()),
                    $artifact_retriever,
                    $user_retriever
                ),
                new VerifyIsVisibleFeatureAdapter($artifact_factory, $user_retriever),
                $user_story_linked_verifier,
                $user_retriever
            ),
            new ArtifactVisibleVerifier($artifact_factory, $user_retriever),
            $program_dao,
            new ProgramAdapter(
                ProjectManager::instance(),
                new ProjectAccessChecker(new RestrictedUserCanAccessProjectVerifier(), \EventManager::instance()),
                $program_dao,
                $user_retriever
            )
        );

        $user = $user_manager->getCurrentUser();
        try {
            $elements = $program_increment_content_retriever->retrieveProgramIncrementContent(
                $id,
                UserProxy::buildFromPFUser($user)
            );

            Header::sendPaginationHeaders($limit, $offset, count($elements), self::MAX_LIMIT);

            return array_slice($elements, $offset, $limit);
        } catch (ProgramIncrementNotFoundException | ProgramIncrementHasNoProgramException | PlanTrackerException | ProgramTrackerException | ProgramAccessException $e) {
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
        $user_manager           = \UserManager::instance();
        $user_retriever         = new UserManagerAdapter($user_manager);
        $artifact_factory       = \Tracker_ArtifactFactory::instance();
        $program_increments_dao = new ProgramIncrementsDAO();
        $plan_dao               = new PlanDao();
        $program_dao            = new ProgramDao();
        $artifact_retriever     = new ArtifactFactoryAdapter($artifact_factory);
        $tracker_factory        = \TrackerFactory::instance();
        $tracker_retriever      = new TrackerFactoryAdapter($tracker_factory);
        $form_element_factory   = \Tracker_FormElementFactory::instance();
        $field_retriever        = new FormElementFactoryAdapter($tracker_retriever, $form_element_factory);

        $artifact_link_updater = new ArtifactLinkUpdater(
            \Tracker_Artifact_PriorityManager::build(),
            new ArtifactLinkUpdaterDataFormater()
        );

        $artifacts_linked_to_parent_dao = new ArtifactsLinkedToParentDao();
        $user_story_linked_verifier     = new UserStoryLinkedToFeatureVerifier(
            $artifacts_linked_to_parent_dao,
            new PlanningAdapter(\PlanningFactory::build(), $user_retriever),
            $artifact_factory,
            $user_retriever,
            $artifacts_linked_to_parent_dao,
            $artifacts_linked_to_parent_dao
        );

        $modifier             = new ContentModifier(
            new PrioritizeFeaturesPermissionVerifier(
                \ProjectManager::instance(),
                new ProjectAccessChecker(
                    new RestrictedUserCanAccessProjectVerifier(),
                    \EventManager::instance()
                ),
                new CanPrioritizeFeaturesDAO(),
                $user_retriever
            ),
            $program_increments_dao,
            new VerifyIsVisibleFeatureAdapter($artifact_factory, $user_retriever),
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
                new UserCanLinkToProgramIncrementVerifier($user_retriever, $field_retriever)
            ),
            new ArtifactVisibleVerifier($artifact_factory, $user_retriever),
            $program_dao,
            new ProgramAdapter(
                ProjectManager::instance(),
                new ProjectAccessChecker(new RestrictedUserCanAccessProjectVerifier(), \EventManager::instance()),
                $program_dao,
                $user_retriever
            )
        );
        $transaction_executor = new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection());

        $user = $user_manager->getCurrentUser();

        try {
            $potential_feature_id_to_add = $patch_representation->add[0]->id ?? null;

            $transaction_executor->execute(
                function () use ($modifier, $potential_feature_id_to_add, $patch_representation, $user, $id) {
                    $modifier->modifyContent(
                        $id,
                        ContentChange::fromFeatureAdditionAndReorder(
                            $potential_feature_id_to_add,
                            FeaturesToReorderProxy::buildFromRESTRepresentation($patch_representation->order)
                        ),
                        UserProxy::buildFromPFUser($user)
                    );
                }
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
                BackendLogger::getDefaultLogger(),
            ),
            new UriRetriever($artifact_retriever),
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
}
