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

namespace Tuleap\ScaledAgile\REST\v1;

use BackendLogger;
use Luracast\Restler\RestException;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\Cardwall\BackgroundColor\BackgroundColorBuilder;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\ScaledAgile\Adapter\Program\Backlog\ProgramIncrement\ProgramIncrementsDAO;
use Tuleap\ScaledAgile\Adapter\Program\Backlog\ProgramIncrement\ProgramIncrementsRetriever;
use Tuleap\ScaledAgile\Adapter\Program\Plan\PlanDao;
use Tuleap\ScaledAgile\Adapter\Program\Plan\PlanTrackerException;
use Tuleap\ScaledAgile\Adapter\Program\Plan\ProgramAccessException;
use Tuleap\ScaledAgile\Adapter\Program\Plan\ProgramAdapter;
use Tuleap\ScaledAgile\Adapter\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ScaledAgile\Adapter\Program\ProgramDao;
use Tuleap\ScaledAgile\Adapter\Program\Feature\BackgroundColorRetriever;
use Tuleap\ScaledAgile\Adapter\Program\Feature\FeaturesDao;
use Tuleap\ScaledAgile\Adapter\Program\Feature\FeatureElementsRetriever;
use Tuleap\ScaledAgile\Adapter\Program\Tracker\ProgramTrackerAdapter;
use Tuleap\ScaledAgile\Adapter\Program\Tracker\ProgramTrackerException;
use Tuleap\ScaledAgile\Adapter\Team\TeamAdapter;
use Tuleap\ScaledAgile\Adapter\Team\TeamDao;
use Tuleap\ScaledAgile\Adapter\Team\TeamException;
use Tuleap\ScaledAgile\Program\Backlog\Feature\RetrieveFeatures;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\ProgramIncrementBuilder;
use Tuleap\ScaledAgile\Program\Plan\CannotPlanIntoItselfException;
use Tuleap\ScaledAgile\Program\Plan\CreatePlan;
use Tuleap\ScaledAgile\Program\Plan\PlanCreator;
use Tuleap\ScaledAgile\Team\Creation\TeamCreator;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindDecoratorRetriever;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDao;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeBuilder;

final class ProjectResource extends AuthenticatedResource
{
    private const MAX_LIMIT = 50;

    /**
     * @var RetrieveFeatures
     */
    private $features_retriever;
    /**
     * @var TeamCreator
     */
    private $team_creator;
    /**
     * @var CreatePlan
     */
    private $plan_creator;
    /**
     * @var \UserManager
     */
    private $user_manager;
    /**
     * @var ProgramIncrementBuilder
     */
    private $program_increments_builder;

    public function __construct()
    {
        $this->user_manager   = \UserManager::instance();
        $plan_dao             = new PlanDao();
        $tracker_adapter      = new ProgramTrackerAdapter(\TrackerFactory::instance(), new PlanDao());
        $project_manager      = \ProjectManager::instance();
        $program_dao          = new ProgramDao();
        $explicit_backlog_dao = new ExplicitBacklogDao();
        $build_program        = new ProgramAdapter($project_manager, $program_dao);
        $this->plan_creator   = new PlanCreator($build_program, $tracker_adapter, $plan_dao);

        $team_adapter       = new TeamAdapter($project_manager, $program_dao, $explicit_backlog_dao);
        $team_dao           = new TeamDao();
        $this->team_creator = new TeamCreator($build_program, $team_adapter, $team_dao);

        $artifact_factory                 = \Tracker_ArtifactFactory::instance();
        $form_element_factory             = \Tracker_FormElementFactory::instance();
        $this->features_retriever         = new FeatureElementsRetriever(
            $build_program,
            new FeaturesDao(),
            $artifact_factory,
            $form_element_factory,
            new BackgroundColorRetriever(new BackgroundColorBuilder(new BindDecoratorRetriever()))
        );
        $this->program_increments_builder = new ProgramIncrementBuilder(
            $build_program,
            new ProgramIncrementsRetriever(
                new ProgramIncrementsDAO(),
                $artifact_factory,
                new TimeframeBuilder(
                    new SemanticTimeframeBuilder(new SemanticTimeframeDao(), $form_element_factory),
                    BackendLogger::getDefaultLogger()
                )
            )
        );
    }

    /**
     * @url OPTIONS {id}/scaled_agile_plan
     *
     * @param int $id Id of the project
     */
    public function options(int $id): void
    {
        Header::allowOptionsPut();
    }

    /**
     * Define Scaled agile program plan
     *
     * Define the program increment and the tracker plannable inside
     *
     * @url    PUT {id}/scaled_agile_plan
     *
     * @param int                                  $id Id of the program project
     * @param ProjectResourcePutPlanRepresentation $representation {@from body}
     *
     *
     * @throws RestException 404
     * @throws RestException 400
     */
    protected function putPlan(int $id, ProjectResourcePutPlanRepresentation $representation): void
    {
        $user = $this->user_manager->getCurrentUser();
        try {
            $this->plan_creator->create(
                $user,
                $id,
                $representation->program_increment_tracker_id,
                $representation->plannable_tracker_ids
            );
        } catch (ProjectIsNotAProgramException | CannotPlanIntoItselfException | PlanTrackerException | ProgramTrackerException $e) {
            throw new RestException(400, $e->getMessage());
        } catch (ProgramAccessException $e) {
            throw new RestException(404, $e->getMessage());
        }
    }

    /**
     * Define Scaled agile team projects
     *
     * Define the team project of a program
     *
     * @url    PUT {id}/scaled_agile_teams
     *
     * @param int                                   $id Id of the program project
     * @param ProjectResourcePutTeamsRepresentation $representation {@from body}
     *
     *
     * @throws RestException 404
     * @throws RestException 400
     */
    protected function putTeam(int $id, ProjectResourcePutTeamsRepresentation $representation): void
    {
        $user = $this->user_manager->getCurrentUser();
        try {
            $this->team_creator->create(
                $user,
                $id,
                $representation->team_ids
            );
        } catch (TeamException $e) {
            throw new RestException(400, $e->getMessage());
        } catch (ProgramAccessException $e) {
            throw new RestException(404, $e->getMessage());
        }
    }

    /**
     * Get program backlog
     *
     * Get the to be planned elements of a program
     *
     * @url GET {id}/scaled_agile_backlog
     * @access hybrid
     *
     * @param int $id Id of the program
     * @param int $limit Number of elements displayed per page {@min 0} {@max 50}
     * @param int $offset Position of the first element to display {@min 0}
     *
     * @return FeatureRepresentation[]
     *
     * @throws RestException 401
     * @throws RestException 400
     */
    public function getBacklog(int $id, int $limit = self::MAX_LIMIT, int $offset = 0): array
    {
        $user = $this->user_manager->getCurrentUser();
        try {
            $elements = $this->features_retriever->retrieveFeaturesToBePlanned($id, $user);

            Header::sendPaginationHeaders($limit, $offset, count($elements), self::MAX_LIMIT);

            return array_slice($elements, $offset, $limit);
        } catch (\Tuleap\ScaledAgile\Adapter\Program\Plan\ProgramAccessException $e) {
            throw new RestException(404, $e->getMessage());
        } catch (\Tuleap\ScaledAgile\Adapter\Program\Plan\ProjectIsNotAProgramException $e) {
            throw new RestException(400, $e->getMessage());
        }
    }

    /**
     * @url OPTIONS {id}/scaled_agile_backlog
     *
     * @param int $id Id of the project
     */
    public function optionsBacklog(int $id): void
    {
        Header::allowOptionsGet();
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
     * @url GET {id}/program_increments
     * @access hybrid
     *
     * @param int $id ID of the program
     * @param int $limit Number of elements displayed per page {@min 1} {@max 50}
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
            $program_increments = $this->program_increments_builder->buildOpenProgramIncrements($id, $user);
        } catch (\Tuleap\ScaledAgile\Adapter\Program\Plan\ProgramAccessException $e) {
            throw new RestException(404, $e->getMessage());
        } catch (\Tuleap\ScaledAgile\Adapter\Program\Plan\ProjectIsNotAProgramException $e) {
            throw new RestException(400, $e->getMessage());
        }

        Header::sendPaginationHeaders($limit, $offset, count($program_increments), self::MAX_LIMIT);

        $representations = [];
        foreach (array_slice($program_increments, $offset, $limit) as $program_increment) {
            $representations[] = ProgramIncrementRepresentation::fromProgramIncrement($program_increment);
        }
        return $representations;
    }
}
