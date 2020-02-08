<?php
/**
 * Copyright Enalean (c) 2018-Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Timetracking\REST\v1;

use EventManager;
use Luracast\Restler\RestException;
use Project;
use Tracker_FormElementFactory;
use Tracker_REST_TrackerRestBuilder;
use TrackerFactory;
use TransitionFactory;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Timetracking\Admin\AdminDao;
use Tuleap\Timetracking\Admin\TimetrackingUgroupDao;
use Tuleap\Timetracking\Admin\TimetrackingUgroupRetriever;
use Tuleap\Timetracking\Permissions\PermissionsRetriever;
use Tuleap\Timetracking\Time\TimeDao;
use Tuleap\Timetracking\Time\TimeRetriever;
use Tuleap\Tracker\FormElement\Container\Fieldset\HiddenFieldsetChecker;
use Tuleap\Tracker\FormElement\Container\FieldsExtractor;
use Tuleap\Tracker\PermissionsFunctionsWrapper;
use Tuleap\Tracker\REST\FormElementRepresentationsBuilder;
use Tuleap\Tracker\REST\PermissionsExporter;
use Tuleap\Tracker\REST\FormElement\PermissionsForGroupsBuilder;
use Tuleap\Tracker\REST\Tracker\PermissionsRepresentationBuilder;
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
use UserManager;
use Workflow_Transition_ConditionFactory;

class ProjectResource
{
    public const TIMETRACKING_CRITERION = 'with_time_tracking';

    /** @var UserManager */
    private $user_manager;

    /**
     * @var TimeRetriever
     */
    private $time_retriever;

    /**
     * @var TimetrackingOverviewRepresentationsBuilder
     */
    private $timetracking_overview_builder;

    public function __construct()
    {
        $this->user_manager          = UserManager::instance();
        $this->permissions_retriever = new PermissionsRetriever(
            new TimetrackingUgroupRetriever(
                new TimetrackingUgroupDao()
            )
        );
        $this->time_retriever        = new TimeRetriever(
            new TimeDao(),
            $this->permissions_retriever,
            new AdminDao(),
            \ProjectManager::instance()
        );

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

        $this->timetracking_overview_builder = new TimetrackingOverviewRepresentationsBuilder(
            new AdminDao(),
            $this->permissions_retriever,
            TrackerFactory::instance(),
            new Tracker_REST_TrackerRestBuilder(
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
            )
        );
    }

    /**
     * @param  int   $limit
     * @param  int   $offset
     * @param  array $query
     *
     * @return Project[]
     *
     * @throws RestException
     * @throws \Rest_Exception_InvalidTokenException
     * @throws \User_PasswordExpiredException
     * @throws \User_StatusInvalidException
     */
    public function getProjects($limit, $offset, array $query)
    {
        $this->checkQuery($query);
        $current_user = $this->user_manager->getCurrentUser();

        return $this->time_retriever->getProjectsWithTimetracking($current_user, $limit, $offset);
    }

    /**
     * @param array   $query
     * @param String  $representation
     * @param int     $limit
     * @param int     $offset
     *
     * @return array
     *
     * @throws RestException
     * @throws RestException 400
     * @throws \Rest_Exception_InvalidTokenException
     * @throws \User_PasswordExpiredException
     * @throws \User_StatusInvalidException
     */
    public function getTrackers($query, $representation, Project $project, $limit, $offset)
    {
        $this->checkQuery($query);
        $current_user = $this->user_manager->getCurrentUser();
        if ($representation === "minimal") {
            return $this->timetracking_overview_builder->getTrackersMinimalRepresentationsWithTimetracking(
                $current_user,
                $project,
                $limit,
                $offset
            );
        }

        return $this->timetracking_overview_builder->getTrackersFullRepresentationsWithTimetracking(
            $current_user,
            $project,
            $limit,
            $offset
        );
    }

    /**
     * @throws RestException
     */
    private function checkQuery(array $query)
    {
        if ($query[self::TIMETRACKING_CRITERION] === false) {
            throw new RestException(
                400,
                "Searching projects where timetracking is not enabled is not supported. Use 'with_timetracking': true"
            );
        }
    }
}
