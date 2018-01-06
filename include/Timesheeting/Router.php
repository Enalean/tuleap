<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

namespace Tuleap\Timesheeting;

use Codendi_Request;
use CSRFSynchronizerToken;
use Feedback;
use PermissionsNormalizer;
use PFUser;
use ProjectHistoryDao;
use Tracker;
use Tracker_Artifact;
use Tracker_ArtifactFactory;
use TrackerFactory;
use TrackerManager;
use Tuleap\Timesheeting\Admin\AdminController;
use Tuleap\Timesheeting\Admin\TimesheetingEnabler;
use Tuleap\Timesheeting\Admin\TimesheetingUgroupRetriever;
use Tuleap\Timesheeting\Admin\TimesheetingUgroupSaver;
use Tuleap\Timesheeting\Permissions\PermissionsRetriever;
use Tuleap\Timesheeting\Time\TimeUpdater;
use User_ForgeUserGroupFactory;
use Tuleap\Timesheeting\Time\TimeController;

class Router
{
    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    /**
     * @var TrackerManager
     */
    private $tracker_manager;

    /**
     * @var TimesheetingEnabler
     */
    private $timesheeting_enabler;

    /**
     * @var User_ForgeUserGroupFactory
     */
    private $user_forge_user_group_factory;

    /**
     * @var PermissionsNormalizer
     */
    private $permissions_normalizer;

    /**
     * @var TimesheetingUgroupSaver
     */
    private $timesheeting_ugroup_saver;

    /**
     * @var TimesheetingUgroupRetriever
     */
    private $timesheeting_ugroup_retriever;

    /**
     * @var ProjectHistoryDao
     */
    private $project_history_dao;

    /**
     * @var TimeController
     */
    private $controller;

    /**
     * @var PermissionsRetriever
     */
    private $permissions_retriever;

    /**
     * @var TimeUpdater
     */
    private $time_updater;

    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;

    public function __construct(
        TrackerFactory $tracker_factory,
        TrackerManager $tracker_manager,
        Tracker_ArtifactFactory $artifact_factory,
        TimesheetingEnabler $timesheeting_enabler,
        User_ForgeUserGroupFactory $user_forge_user_group_factory,
        PermissionsNormalizer $permissions_normalizer,
        TimesheetingUgroupSaver $timesheeting_ugroup_saver,
        TimesheetingUgroupRetriever $timesheeting_ugroup_retriever,
        ProjectHistoryDao $project_history_dao,
        PermissionsRetriever $permissions_retriever,
        TimeUpdater $time_updater
    ) {
        $this->tracker_factory               = $tracker_factory;
        $this->tracker_manager               = $tracker_manager;
        $this->timesheeting_enabler          = $timesheeting_enabler;
        $this->user_forge_user_group_factory = $user_forge_user_group_factory;
        $this->permissions_normalizer        = $permissions_normalizer;
        $this->timesheeting_ugroup_saver     = $timesheeting_ugroup_saver;
        $this->timesheeting_ugroup_retriever = $timesheeting_ugroup_retriever;
        $this->project_history_dao           = $project_history_dao;
        $this->permissions_retriever         = $permissions_retriever;
        $this->time_updater                  = $time_updater;
        $this->artifact_factory              = $artifact_factory;
    }

    public function route(Codendi_Request $request)
    {
        $user       = $request->getCurrentUser();
        $action     = $request->get('action');

        switch ($action) {
            case "admin-timesheeting":
                $tracker          = $this->getTrackerFromRequest($request);
                $admin_controller = $this->getAdminController($user, $tracker);

                $admin_controller->displayAdminForm($tracker);

                break;
            case "edit-timesheeting":
                $tracker          = $this->getTrackerFromRequest($request);
                $admin_controller = $this->getAdminController($user, $tracker);

                $admin_controller->editTimesheetingAdminSettings($tracker, $request);

                $this->redirectToTimesheetingAdminPage($tracker);
                break;
            case "add-time":
                $artifact = $this->getArtifactFromRequest($request);
                $this->getTimeController()->addTimeForUser($request, $user, $artifact);

                break;
            default:
                $this->redirectToTuleapHomepage();

                break;
        }
    }

    /**
     * @return Tracker
     */
    private function getTrackerFromRequest(Codendi_Request $request)
    {
        $tracker_id = $request->get('tracker');
        $tracker    = $this->tracker_factory->getTrackerById($tracker_id);

        if (! $tracker) {
            $this->redirectToTuleapHomepage();
        }

        return $tracker;
    }

    /**
     * @return Tracker_Artifact
     */
    private function getArtifactFromRequest(Codendi_Request $request)
    {
        $artifact_id = $request->get('artifact');
        $artifact    = $this->artifact_factory->getArtifactById($artifact_id);

        if (! $artifact) {
            $this->redirectToTuleapHomepage();
        }

        return $artifact;
    }

    /**
     * @return AdminController
     */
    private function getAdminController(PFUser $user, Tracker $tracker)
    {
        if (! $tracker->userIsAdmin($user)) {
            $this->redirectToTrackerHomepage($tracker->getId());
        }

        return new AdminController(
            $this->tracker_manager,
            $this->timesheeting_enabler,
            new CSRFSynchronizerToken($tracker->getAdministrationUrl()),
            $this->user_forge_user_group_factory,
            $this->permissions_normalizer,
            $this->timesheeting_ugroup_saver,
            $this->timesheeting_ugroup_retriever,
            $this->project_history_dao
        );
    }

    /**
     * @return TimeController
     */
    private function getTimeController()
    {
        return new TimeController(
            $this->permissions_retriever,
            $this->time_updater
        );
    }

    private function redirectToTrackerHomepage($tracker_id)
    {
        $GLOBALS['Response']->addFeedback(
            Feedback::ERROR,
            dgettext('tuleap-timesheeting', "Access denied. You don't have permissions to perform this action.")
        );

        $url = TRACKER_BASE_URL . '/?' . http_build_query(array(
                'tracker' => $tracker_id
        ));

        $GLOBALS['Response']->redirect($url);
    }

    private function redirectToTimesheetingAdminPage(Tracker $tracker)
    {
        $url = TIMESHEETING_BASE_URL . '/?' . http_build_query(array(
                'tracker' => $tracker->getId(),
                'action' => 'admin-timesheeting'
        ));

        $GLOBALS['Response']->redirect($url);

    }

    private function redirectToTuleapHomepage()
    {
        $GLOBALS['Response']->addFeedback(
            Feedback::ERROR,
            dgettext('tuleap-timesheeting', 'The request is not valid.')
        );

        $GLOBALS['Response']->redirect('/');
    }
}
