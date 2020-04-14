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

namespace Tuleap\Timetracking;

use Codendi_Request;
use CSRFSynchronizerToken;
use Feedback;
use PFUser;
use Tracker;
use Tracker_Artifact;
use Tracker_ArtifactFactory;
use TrackerFactory;
use Tuleap\Timetracking\Admin\AdminController;
use Tuleap\Timetracking\Exceptions\TimeTrackingBadTimeFormatException;
use Tuleap\Timetracking\Exceptions\TimeTrackingBadDateFormatException;
use Tuleap\Timetracking\Exceptions\TimeTrackingMissingTimeException;
use Tuleap\Timetracking\Exceptions\TimeTrackingNotAllowedToAddException;
use Tuleap\Timetracking\Exceptions\TimeTrackingNotAllowedToDeleteException;
use Tuleap\Timetracking\Exceptions\TimeTrackingNotAllowedToEditException;
use Tuleap\Timetracking\Exceptions\TimeTrackingNotBelongToUserException;
use Tuleap\Timetracking\Exceptions\TimeTrackingNoTimeException;
use Tuleap\Timetracking\Time\TimeController;

class Router
{
    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;

    /**
     * @var AdminController
     */
    private $admin_controller;

    /**
     * @var TimeController
     */
    private $time_controller;

    public function __construct(
        TrackerFactory $tracker_factory,
        Tracker_ArtifactFactory $artifact_factory,
        AdminController $admin_controller,
        TimeController $time_controller
    ) {
        $this->tracker_factory  = $tracker_factory;
        $this->artifact_factory = $artifact_factory;
        $this->admin_controller = $admin_controller;
        $this->time_controller  = $time_controller;
    }

    public function route(Codendi_Request $request)
    {
        $user   = $request->getCurrentUser();
        $action = $request->get('action');

        try {
            switch ($action) {
                case "admin-timetracking":
                    $tracker = $this->getTrackerFromRequest($request, $user);

                    $this->admin_controller->displayAdminForm($tracker);

                    break;
                case "edit-timetracking":
                    $tracker = $this->getTrackerFromRequest($request, $user);

                    $this->admin_controller->editTimetrackingAdminSettings($tracker, $request);

                    $this->redirectToTimetrackingAdminPage($tracker);

                    break;
                case "add-time":
                    $artifact = $this->getArtifactFromRequest($request, $user);

                    $this->time_controller->addTimeForUser($request, $user, $artifact, $this->getCSRFForArtifact($artifact));

                    $GLOBALS['Response']->addFeedback(
                        Feedback::INFO,
                        dgettext('tuleap-timetracking', "Time successfully added.")
                    );

                    $this->redirectToArtifactViewInTimetrackingPane($artifact);

                    break;
                case "delete-time":
                    $artifact = $this->getArtifactFromRequest($request, $user);

                    $this->getCSRFForArtifact($artifact);

                    $this->time_controller->deleteTimeForUser($request, $user, $artifact, $this->getCSRFForArtifact($artifact));

                    $GLOBALS['Response']->addFeedback(
                        Feedback::INFO,
                        dgettext('tuleap-timetracking', "Time successfully deleted.")
                    );

                    $this->redirectToArtifactViewInTimetrackingPane($artifact);

                    break;
                case "edit-time":
                    $artifact = $this->getArtifactFromRequest($request, $user);

                    $this->getCSRFForArtifact($artifact);

                    $this->time_controller->editTimeForUser($request, $user, $artifact, $this->getCSRFForArtifact($artifact));

                    $GLOBALS['Response']->addFeedback(
                        Feedback::INFO,
                        dgettext('tuleap-timetracking', "Time successfully updated.")
                    );

                    $this->redirectToArtifactViewInTimetrackingPane($artifact);

                    break;
                default:
                    $this->redirectToTuleapHomepage();

                    break;
            }
        } catch (TimeTrackingMissingTimeException $e) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $e->getMessage()
            );
            $this->redirectToArtifactViewInTimetrackingPane($artifact);
        } catch (TimeTrackingNotAllowedToAddException $e) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $e->getMessage()
            );
            $this->redirectToArtifactView($artifact);
        } catch (TimeTrackingNotAllowedToEditException $e) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $e->getMessage()
            );
            $this->redirectToArtifactView($artifact);
        } catch (TimeTrackingNotAllowedToDeleteException $e) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $e->getMessage()
            );
            $this->redirectToArtifactView($artifact);
        } catch (TimeTrackingNoTimeException $e) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $e->getMessage()
            );
            $this->redirectToArtifactViewInTimetrackingPane($artifact);
        } catch (TimeTrackingNotBelongToUserException $e) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $e->getMessage()
            );
            $this->redirectToArtifactViewInTimetrackingPane($artifact);
        } catch (TimeTrackingBadTimeFormatException $e) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $e->getMessage()
            );
            $this->redirectToArtifactViewInTimetrackingPane($artifact);
        } catch (TimeTrackingBadDateFormatException $e) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $e->getMessage()
            );
            $this->redirectToArtifactViewInTimetrackingPane($artifact);
        }
    }

    private function redirectToArtifactViewInTimetrackingPane(Tracker_Artifact $artifact)
    {
        $url = TRACKER_BASE_URL . '/?' . http_build_query(array(
                'aid'  => $artifact->getId(),
                'view' => 'timetracking'
            ));

        $GLOBALS['Response']->redirect($url);
    }

    private function redirectToArtifactView(Tracker_Artifact $artifact)
    {
        $url = TRACKER_BASE_URL . '/?' . http_build_query(array(
                'aid'  => $artifact->getId()
            ));

        $GLOBALS['Response']->redirect($url);
    }

    /**
     * @return Tracker
     */
    private function getTrackerFromRequest(Codendi_Request $request, PFUser $user)
    {
        $tracker_id = $request->get('tracker');
        $tracker    = $this->tracker_factory->getTrackerById($tracker_id);

        if (! $tracker) {
            $this->redirectToTuleapHomepage();
        }

        if (! $tracker->userIsAdmin($user)) {
            $this->redirectToTrackerHomepage($tracker_id);
        }

        return $tracker;
    }

    /**
     * @return Tracker_Artifact
     */
    private function getArtifactFromRequest(Codendi_Request $request, PFUser $user)
    {
        $artifact_id = $request->get('artifact');
        $artifact    = $this->artifact_factory->getArtifactById($artifact_id);

        if (! $artifact || ! $artifact->userCanView($user)) {
            $this->redirectToTuleapHomepage();
        }

        return $artifact;
    }

    private function redirectToTrackerHomepage($tracker_id)
    {
        $GLOBALS['Response']->addFeedback(
            Feedback::ERROR,
            dgettext('tuleap-timetracking', "Access denied. You don't have permissions to perform this action.")
        );

        $url = TRACKER_BASE_URL . '/?' . http_build_query(array(
                'tracker' => $tracker_id
        ));

        $GLOBALS['Response']->redirect($url);
    }

    private function redirectToTimetrackingAdminPage(Tracker $tracker)
    {
        $url = TIMETRACKING_BASE_URL . '/?' . http_build_query(array(
                'tracker' => $tracker->getId(),
                'action' => 'admin-timetracking'
        ));

        $GLOBALS['Response']->redirect($url);
    }

    /**
     * @psalm-return never-return
     */
    private function redirectToTuleapHomepage()
    {
        $GLOBALS['Response']->addFeedback(
            Feedback::ERROR,
            dgettext('tuleap-timetracking', 'The request is not valid.')
        );

        $GLOBALS['Response']->redirect('/');
        exit;
    }

    /**
     * @return CSRFSynchronizerToken
     */
    private function getCSRFForArtifact(Tracker_Artifact $artifact)
    {
        return new CSRFSynchronizerToken($artifact->getUri());
    }
}
