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

use Luracast\Restler\RestException;
use Project;
use Tracker_FormElementFactory;
use Tracker_REST_TrackerRestBuilder;
use TrackerFactory;
use TransitionFactory;
use Tuleap\Timetracking\Admin\AdminDao;
use Tuleap\Timetracking\Admin\TimetrackingUgroupDao;
use Tuleap\Timetracking\Admin\TimetrackingUgroupRetriever;
use Tuleap\Timetracking\Permissions\PermissionsRetriever;
use Tuleap\Timetracking\Time\TimeDao;
use Tuleap\Timetracking\Time\TimeRetriever;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\FormElement\Container\Fieldset\HiddenFieldsetChecker;
use Tuleap\Tracker\FormElement\Container\FieldsExtractor;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\Hierarchy\HierarchyDAO;
use Tuleap\Tracker\Hierarchy\ParentInHierarchyRetriever;
use Tuleap\Tracker\Permission\TrackersPermissionsRetriever;
use Tuleap\Tracker\PermissionsFunctionsWrapper;
use Tuleap\Tracker\REST\FormElement\PermissionsForGroupsBuilder;
use Tuleap\Tracker\REST\FormElementRepresentationsBuilder;
use Tuleap\Tracker\REST\PermissionsExporter;
use Tuleap\Tracker\REST\Tracker\PermissionsRepresentationBuilder;
use Tuleap\Tracker\REST\WorkflowRestBuilder;
use Tuleap\Tracker\Semantic\Description\CachedSemanticDescriptionFieldRetriever;
use Tuleap\Tracker\Tracker;
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
use UGroupManager;
use UserManager;

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
        $this->user_manager    = UserManager::instance();
        $ugroup_manager        = new UGroupManager();
        $permissions_retriever = new PermissionsRetriever(
            new TimetrackingUgroupRetriever(
                new TimetrackingUgroupDao(),
                $ugroup_manager
            )
        );
        $admin_dao             = new AdminDao();
        $this->time_retriever  = new TimeRetriever(
            new TimeDao(),
            $permissions_retriever,
            $admin_dao,
            \ProjectManager::instance()
        );

        $transition_retriever = new TransitionRetriever(
            new StateFactory(
                TransitionFactory::instance(),
                new SimpleWorkflowDao()
            ),
            new TransitionExtractor()
        );

        $form_element_factory   = Tracker_FormElementFactory::instance();
        $frozen_fields_detector = new FrozenFieldDetector(
            $transition_retriever,
            new FrozenFieldsRetriever(new FrozenFieldsDao(), $form_element_factory)
        );

        $permissions_functions_wrapper       = new PermissionsFunctionsWrapper();
        $tracker_factory                     = TrackerFactory::instance();
        $this->timetracking_overview_builder = new TimetrackingOverviewRepresentationsBuilder(
            $admin_dao,
            $permissions_retriever,
            $tracker_factory,
            new Tracker_REST_TrackerRestBuilder(
                $form_element_factory,
                new FormElementRepresentationsBuilder(
                    $form_element_factory,
                    new PermissionsExporter($frozen_fields_detector),
                    new HiddenFieldsetChecker(
                        new HiddenFieldsetsDetector(
                            $transition_retriever,
                            new HiddenFieldsetsRetriever(new HiddenFieldsetsDao(), $form_element_factory),
                            $form_element_factory
                        ),
                        new FieldsExtractor()
                    ),
                    new PermissionsForGroupsBuilder(
                        $ugroup_manager,
                        $frozen_fields_detector,
                        $permissions_functions_wrapper
                    ),
                    new TypePresenterFactory(new TypeDao(), new ArtifactLinksUsageDao())
                ),
                new PermissionsRepresentationBuilder($ugroup_manager, $permissions_functions_wrapper),
                new WorkflowRestBuilder(),
                static fn(Tracker $tracker) => new \Tracker_SemanticManager(CachedSemanticDescriptionFieldRetriever::instance(), $tracker),
                new ParentInHierarchyRetriever(new HierarchyDAO(), $tracker_factory),
                TrackersPermissionsRetriever::build()
            )
        );
    }

    /**
     * @param  int   $limit
     * @param  int   $offset
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
        if ($representation === 'minimal') {
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
