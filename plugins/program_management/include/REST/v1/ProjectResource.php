<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\Cardwall\BackgroundColor\BackgroundColorBuilder;
use Tuleap\ProgramManagement\Adapter\ArtifactVisibleVerifier;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Content\FeatureRemovalProcessor;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\ProgramIncrementRetriever;
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
use Tuleap\ProgramManagement\Adapter\Program\Backlog\TopBacklog\ProcessTopBacklogChange;
use Tuleap\ProgramManagement\Adapter\Program\Feature\BackgroundColorRetriever;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Content\FeatureHasPlannedUserStoriesVerifier;
use Tuleap\ProgramManagement\Adapter\Program\Feature\FeaturesDao;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Links\ArtifactsLinkedToParentDao;
use Tuleap\ProgramManagement\Adapter\Program\Plan\CanPrioritizeFeaturesDAO;
use Tuleap\ProgramManagement\Adapter\Program\Plan\PlanDao;
use Tuleap\ProgramManagement\Adapter\Program\Plan\PrioritizeFeaturesPermissionVerifier;
use Tuleap\ProgramManagement\Adapter\Program\Plan\ProgramAdapter;
use Tuleap\ProgramManagement\Adapter\Program\Plan\TrackerConfigurationChecker;
use Tuleap\ProgramManagement\Adapter\Program\PlanningAdapter;
use Tuleap\ProgramManagement\Adapter\Program\ProgramDaoProject;
use Tuleap\ProgramManagement\Adapter\Program\ProgramUserGroupRetriever;
use Tuleap\ProgramManagement\Adapter\Team\TeamAdapter;
use Tuleap\ProgramManagement\Adapter\Team\TeamDao;
use Tuleap\ProgramManagement\Adapter\Team\VisibleTeamSearcher;
use Tuleap\ProgramManagement\Adapter\Workspace\ProjectManagerAdapter;
use Tuleap\ProgramManagement\Adapter\Workspace\ProjectPermissionVerifier;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\Artifact\ArtifactFactoryAdapter;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\Fields\FormElementFactoryAdapter;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\Semantics\IsOpenRetriever;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\TrackerFactoryAdapter;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\TrackerOfArtifactRetriever;
use Tuleap\ProgramManagement\Adapter\Workspace\UserIsProgramAdminVerifier;
use Tuleap\ProgramManagement\Adapter\Workspace\UserManagerAdapter;
use Tuleap\ProgramManagement\Adapter\Workspace\UserProxy;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramCannotBeATeamException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\FeatureHasUserStoriesVerifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Feature;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramBacklogSearcher;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementsSearcher;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\UserCanPlanInProgramIncrementVerifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\CannotManipulateTopBacklog;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\TopBacklogChange;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\Plan\CannotPlanIntoItselfException;
use Tuleap\ProgramManagement\Domain\Program\Plan\InvalidProgramUserGroup;
use Tuleap\ProgramManagement\Domain\Program\Plan\PlanChange;
use Tuleap\ProgramManagement\Domain\Program\Plan\PlanCreator;
use Tuleap\ProgramManagement\Domain\Program\Plan\PlanIterationChange;
use Tuleap\ProgramManagement\Domain\Program\Plan\PlanProgramIncrementChange;
use Tuleap\ProgramManagement\Domain\Program\Plan\PlanTrackerException;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramAccessException;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramIncrementAndIterationCanNotBeTheSameTrackerException;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Program\ProgramIsTeamException;
use Tuleap\ProgramManagement\Domain\Program\ProgramTrackerException;
use Tuleap\ProgramManagement\Domain\Team\Creation\CreateTeam;
use Tuleap\ProgramManagement\Domain\Team\Creation\TeamCreator;
use Tuleap\ProgramManagement\Domain\Team\TeamException;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\REST\UserGroupRetriever;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\I18NRestException;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkUpdater;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkUpdaterDataFormater;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindDecoratorRetriever;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;

final class ProjectResource extends AuthenticatedResource
{
    private const MAX_LIMIT = 50;

    private CreateTeam $team_creator;
    private \UserManager $user_manager;
    private ProgramIncrementsSearcher $program_increments_builder;
    private BuildProgram $build_program;
    private UserManagerAdapter $user_manager_adapter;
    private PrioritizeFeaturesPermissionVerifier $features_permission_verifier;
    private FeatureHasPlannedUserStoriesVerifier $user_story_linked_verifier;

    public function __construct()
    {
        $this->user_manager          = \UserManager::instance();
        $team_dao                    = new TeamDao();
        $project_manager             = \ProjectManager::instance();
        $program_dao                 = new ProgramDaoProject();
        $explicit_backlog_dao        = new ExplicitBacklogDao();
        $this->user_manager_adapter  = new UserManagerAdapter($this->user_manager);
        $project_retriever           = new ProjectManagerAdapter($project_manager, $this->user_manager_adapter);
        $project_permission_verifier = new ProjectPermissionVerifier($this->user_manager_adapter);
        $artifact_factory            = \Tracker_ArtifactFactory::instance();
        $artifact_retriever          = new ArtifactFactoryAdapter($artifact_factory);
        $tracker_factory             = \TrackerFactory::instance();
        $tracker_retriever           = new TrackerFactoryAdapter($tracker_factory);
        $form_element_factory        = \Tracker_FormElementFactory::instance();
        $field_retriever             = new FormElementFactoryAdapter($tracker_retriever, $form_element_factory);
        $project_manager_adapter     = new ProjectManagerAdapter($project_manager, $this->user_manager_adapter);

        $project_access_checker = new ProjectAccessChecker(
            new RestrictedUserCanAccessProjectVerifier(),
            \EventManager::instance()
        );
        $this->build_program    = ProgramAdapter::instance();

        $team_adapter       = new TeamAdapter(
            $project_manager_adapter,
            $program_dao,
            $explicit_backlog_dao,
            $this->user_manager_adapter
        );
        $this->team_creator = new TeamCreator(
            $project_retriever,
            $team_dao,
            $project_permission_verifier,
            $team_adapter,
            $team_dao
        );

        $artifacts_linked_to_parent_dao   = new ArtifactsLinkedToParentDao();
        $this->user_story_linked_verifier = new FeatureHasPlannedUserStoriesVerifier(
            $artifacts_linked_to_parent_dao,
            new PlanningAdapter(\PlanningFactory::build(), $this->user_manager_adapter),
            $artifacts_linked_to_parent_dao
        );

        $this->features_permission_verifier = new PrioritizeFeaturesPermissionVerifier(
            $project_manager_adapter,
            $project_access_checker,
            new CanPrioritizeFeaturesDAO(),
            $this->user_manager_adapter,
            new UserIsProgramAdminVerifier($this->user_manager_adapter)
        );

        $program_increments_dao = new ProgramIncrementsDAO();
        $update_verifier        = new UserCanUpdateTimeboxVerifier($artifact_retriever, $this->user_manager_adapter);

        $this->program_increments_builder = new ProgramIncrementsSearcher(
            $this->build_program,
            $program_increments_dao,
            $program_increments_dao,
            new ArtifactVisibleVerifier($artifact_factory, $this->user_manager_adapter),
            new ProgramIncrementRetriever(
                new StatusValueRetriever($artifact_retriever, $this->user_manager_adapter),
                new TitleValueRetriever($artifact_retriever),
                new TimeframeValueRetriever(
                    $artifact_retriever,
                    $this->user_manager_adapter,
                    SemanticTimeframeBuilder::build(),
                    \Tuleap\ProgramManagement\ProgramManagementLogger::getLogger(),
                ),
                new URIRetriever($artifact_retriever),
                new CrossReferenceRetriever($artifact_retriever),
                $update_verifier,
                new UserCanPlanInProgramIncrementVerifier(
                    $update_verifier,
                    $program_increments_dao,
                    new UserCanLinkToProgramIncrementVerifier($this->user_manager_adapter, $field_retriever),
                    $program_dao,
                    $this->build_program,
                    new VisibleTeamSearcher(
                        $program_dao,
                        $this->user_manager_adapter,
                        $project_manager_adapter,
                        $project_access_checker,
                        $team_dao
                    ),
                )
            )
        );
    }

    /**
     * @url OPTIONS {id}/program_plan
     *
     * @param int $id Id of the project
     */
    public function options(int $id): void
    {
        Header::allowOptionsPut();
    }

    /**
     * Define a program plan
     *
     * Define which tracker is program increment and which trackers can be planned in it.
     * It also lets you define which user groups have permission to plan in program increments.
     * <br/>
     * <strong>"program_increment_label"</strong> and <strong>"program_increment_sub_label"</strong> are optional.
     * They will be used to set a custom label for Program Increment in the UI.
     * <br/>
     * The following values are used by default:
     * <pre>
     * {<br/>
     * &nbsp;"program_increment_label": "Program Increments",<br/>
     * &nbsp;"program_increment_sub_label": "program increment"<br/>
     * }
     * </pre>
     *
     * <br/>
     * <strong>"iteration"</strong> is optional. It permits to configure iteration tracker.
     * <br/>
     * Example:
     * <pre>
     * {<br/>
     * &nbsp;"iteration_tracker_id": 115<br/>
     * }
     * </pre>
     * <br/>
     *
     * <strong>"iteration_label"</strong> and <strong>"iteration_sub_label"</strong> are optional. They permit to set a custom label for iterations.
     * <br/>
     * The following values are used by default:
     * <pre>
     * {<br/>
     * &nbsp;"iteration_label": "Iterations"<br/>
     * &nbsp;"iteration_sub_label": "iteration"<br/>
     * }
     * </pre>
     * <br/>
     *
     * @url    PUT {id}/program_plan
     *
     * @param int $id                                              Id of the program project
     * @param ProjectResourcePutPlanRepresentation $representation {@from body}
     *
     *
     * @throws RestException 404
     * @throws RestException 400
     */
    protected function putPlan(int $id, ProjectResourcePutPlanRepresentation $representation): void
    {
        $user            = $this->user_manager->getCurrentUser();
        $user_identifier = UserProxy::buildFromPFUser($user);

        $tracker_retriever = new TrackerFactoryAdapter(\TrackerFactory::instance());
        $tracker_checker   = new TrackerConfigurationChecker($tracker_retriever);

        $plan_creator = new PlanCreator(
            $tracker_checker,
            $tracker_checker,
            $tracker_checker,
            new ProgramUserGroupRetriever(new UserGroupRetriever(new \UGroupManager())),
            new PlanDao(),
            new ProjectManagerAdapter(\ProjectManager::instance(), $this->user_manager_adapter),
            new TeamDao(),
            new ProjectPermissionVerifier($this->user_manager_adapter)
        );

        $plan_program_increment_change = new PlanProgramIncrementChange(
            $representation->program_increment_tracker_id,
            $representation->program_increment_label,
            $representation->program_increment_sub_label
        );
        $plan_iteration_change         = null;
        if ($representation->iteration) {
            $plan_iteration_change = new PlanIterationChange(
                $representation->iteration->iteration_tracker_id,
                $representation->iteration->iteration_label,
                $representation->iteration->iteration_sub_label
            );
        }
        try {
            $plan_change = PlanChange::fromProgramIncrementAndRaw(
                $plan_program_increment_change,
                $user_identifier,
                $id,
                $representation->plannable_tracker_ids,
                $representation->permissions->can_prioritize_features,
                $plan_iteration_change
            );
            $plan_creator->create($plan_change);
        } catch (CannotPlanIntoItselfException | PlanTrackerException | ProgramTrackerException | InvalidProgramUserGroup | ProgramIncrementAndIterationCanNotBeTheSameTrackerException $e) {
            throw new I18NRestException(400, $e->getI18NExceptionMessage());
        } catch (ProgramCannotBeATeamException | ProgramAccessException $e) {
            throw new I18NRestException(404, $e->getI18NExceptionMessage());
        }
    }

    /**
     * Define team projects of a program
     *
     * @url    PUT {id}/program_teams
     *
     * @param int $id                                               Id of the program project
     * @param ProjectResourcePutTeamsRepresentation $representation {@from body}
     *
     *
     * @throws RestException 404
     * @throws RestException 400
     */
    protected function putTeam(int $id, ProjectResourcePutTeamsRepresentation $representation): void
    {
        $user            = $this->user_manager->getCurrentUser();
        $user_identifier = UserProxy::buildFromPFUser($user);
        try {
            $this->team_creator->create(
                $user_identifier,
                $id,
                $representation->team_ids
            );
        } catch (TeamException | ProjectIsNotAProgramException | ProgramIsTeamException $e) {
            throw new I18NRestException(400, $e->getI18NExceptionMessage());
        } catch (ProgramAccessException $e) {
            throw new I18NRestException(404, $e->getI18NExceptionMessage());
        }
    }

    /**
     * Get program backlog
     *
     * Get the to be planned elements of a program
     *
     * @url    GET {id}/program_backlog
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
        $user_manager                   = \UserManager::instance();
        $user_retriever                 = new UserManagerAdapter($user_manager);
        $artifact_factory               = \Tracker_ArtifactFactory::instance();
        $artifact_retriever             = new ArtifactFactoryAdapter($artifact_factory);
        $tracker_retriever              = new TrackerFactoryAdapter(\TrackerFactory::instance());
        $artifacts_linked_to_parent_dao = new ArtifactsLinkedToParentDao();
        $visibility_verifier            = new ArtifactVisibleVerifier($artifact_factory, $user_retriever);

        $program_backlog_searcher = new ProgramBacklogSearcher(
            ProgramAdapter::instance(),
            new FeaturesDao(),
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
            $features = $program_backlog_searcher->retrieveFeaturesToBePlanned($id, UserProxy::buildFromPFUser($user));

            $representations = array_map(
                static fn(Feature $feature) => FeatureRepresentation::fromFeature($tracker_retriever, $feature),
                $features
            );

            Header::sendPaginationHeaders($limit, $offset, count($representations), self::MAX_LIMIT);

            return array_slice($representations, $offset, $limit);
        } catch (ProgramAccessException $e) {
            throw new I18NRestException(404, $e->getI18NExceptionMessage());
        } catch (ProjectIsNotAProgramException $e) {
            throw new I18NRestException(400, $e->getI18NExceptionMessage());
        }
    }

    /**
     * Manipulate the program backlog
     *
     * Add or remove elements from program backlog
     *
     * <br>
     * Add example:
     * <pre>
     * {
     *   "add": [
     *     {
     *       "id": 34,
     *     }
     *   ],
     *   "remove_from_program_increment_to_add_to_the_backlog": true
     * }
     * </pre>
     * <br>
     * The feature with id 34 is removed from Program Increments linked and is added in program backlog.
     * <br>
     * <code>remove_from_program_increment_to_add_to_the_backlog</code> is not mandatory.
     * If it's not set and the feature is linked to program increments, then feature will not be added to program backlog.
     *
     * <br><br>
     *
     * Reorder example:
     *
     * <pre>
     * "order":{
     *   "ids":[
     *     123
     *   ],
     *   "direction":"before",
     *   "compared_to":456
     * },
     * </pre>
     * The feature with id 123 is moved before feature with id 456. <code>direction</code> can be "after" or "before".
     * <code>"order"</code> is not mandatory.
     *
     * @url PATCH {id}/program_backlog
     *
     * @param int $id                                                  ID of the program
     * @param BacklogPatchRepresentation $backlog_patch_representation {@from body}
     *
     * @throws RestException 401
     * @throws RestException 400
     */
    protected function patchBacklog(int $id, BacklogPatchRepresentation $backlog_patch_representation): void
    {
        $feature_ids_to_remove = [];
        foreach ($backlog_patch_representation->remove as $feature_to_remove) {
            $feature_ids_to_remove[] = $feature_to_remove->id;
        }
        $feature_ids_to_add = [];
        foreach ($backlog_patch_representation->add as $feature_to_add) {
            $feature_ids_to_add[] = $feature_to_add->id;
        }

        $artifact_factory    = \Tracker_ArtifactFactory::instance();
        $priority_manager    = \Tracker_Artifact_PriorityManager::build();
        $top_backlog_updater = new ProcessTopBacklogChange(
            $this->features_permission_verifier,
            new ArtifactsExplicitTopBacklogDAO(),
            new FeaturesRankOrderer(\Tracker_Artifact_PriorityManager::build()),
            $this->user_story_linked_verifier,
            new ArtifactVisibleVerifier($artifact_factory, $this->user_manager_adapter),
            new FeatureRemovalProcessor(
                new ProgramIncrementsDAO(),
                $artifact_factory,
                new ArtifactLinkUpdater($priority_manager, new ArtifactLinkUpdaterDataFormater()),
                $this->user_manager_adapter
            )
        );

        try {
            $user_identifier = UserProxy::buildFromPFUser($this->user_manager->getCurrentUser());
            $program         = ProgramIdentifier::fromId($this->build_program, $id, $user_identifier, null);
            $top_backlog_updater->processTopBacklogChangeForAProgram(
                $program,
                new TopBacklogChange(
                    $feature_ids_to_add,
                    $feature_ids_to_remove,
                    $backlog_patch_representation->remove_from_program_increment_to_add_to_the_backlog,
                    FeaturesToReorderProxy::buildFromRESTRepresentation($backlog_patch_representation->order)
                ),
                $user_identifier,
                null
            );
        } catch (ProgramAccessException | CannotManipulateTopBacklog $e) {
            throw new I18NRestException(404, $e->getI18NExceptionMessage());
        } catch (ProjectIsNotAProgramException $e) {
            throw new I18NRestException(403, $e->getI18NExceptionMessage());
        } catch (FeatureException $e) {
            throw new I18NRestException(400, $e->getI18NExceptionMessage());
        }
    }

    /**
     * @url OPTIONS {id}/program_backlog
     *
     * @param int $id Id of the project
     */
    public function optionsBacklog(int $id): void
    {
        Header::allowOptionsGetPatch();
    }

    /**
     * @url OPTIONS {id}/program_increments
     *
     * @param int $id ID of the program
     */
    public function optionsProgramIncrements(int $id): void
    {
        Header::allowOptionsGet();
    }

    /**
     * Get program increments
     *
     * @url    GET {id}/program_increments
     * @access hybrid
     *
     * @param int $id     ID of the program
     * @param int $limit  Number of elements displayed per page {@min 1} {@max 50}
     * @param int $offset Position of the first element to display {@min 0}
     *
     * @return ProgramIncrementRepresentation[]
     *
     * @throws RestException 401
     * @throws RestException 400
     */
    public function getProgramIncrements(int $id, int $limit = self::MAX_LIMIT, int $offset = 0): array
    {
        $user = $this->user_manager->getCurrentUser();
        try {
            $program_increments = $this->program_increments_builder->searchOpenProgramIncrements(
                $id,
                UserProxy::buildFromPFUser($user)
            );
        } catch (ProgramAccessException $e) {
            throw new I18NRestException(404, $e->getI18NExceptionMessage());
        } catch (ProjectIsNotAProgramException $e) {
            throw new RestException(400, $e->getI18NExceptionMessage());
        }

        Header::sendPaginationHeaders($limit, $offset, count($program_increments), self::MAX_LIMIT);

        $representations = [];
        foreach (array_slice($program_increments, $offset, $limit) as $program_increment) {
            $representations[] = ProgramIncrementRepresentation::fromProgramIncrement($program_increment);
        }

        return $representations;
    }
}
