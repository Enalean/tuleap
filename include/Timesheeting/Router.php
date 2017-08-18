<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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
use Feedback;
use TemplateRendererFactory;
use TrackerFactory;

class Router
{
    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    public function __construct(TrackerFactory $tracker_factory)
    {
        $this->tracker_factory = $tracker_factory;
    }

    public function route(Codendi_Request $request)
    {
        $tracker_id = $request->get('tracker');
        $tracker    = $this->tracker_factory->getTrackerById($tracker_id);

        if (! $tracker) {
            $this->redirectToTuleapHomepage();
        }

        $user   = $request->getCurrentUser();
        $action = $request->get('action');

        switch ($action) {
            case "admin-timesheeting":
                if (! $tracker->userIsAdmin($user)) {
                    $this->redirectToTrackerHomepage($tracker_id);
                }

                $renderer  = TemplateRendererFactory::build()->getRenderer(TIMESHEETING_TEMPLATE_DIR);
                $presenter = new AdminPresenter();

                $renderer->renderToPage(
                    'tracker-admin',
                    $presenter
                );

                break;
            default:
                $this->redirectToTrackerAdminPage($tracker_id);

                break;
        }
    }

    private function redirectToTuleapHomepage()
    {
        $GLOBALS['Response']->addFeedback(
            Feedback::ERROR,
            dgettext('tuleap-timesheeting', 'The request is not valid.')
        );

        $GLOBALS['Response']->redirect('/');
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

    private function redirectToTrackerAdminPage($tracker_id)
    {
        $GLOBALS['Response']->addFeedback(
            Feedback::ERROR,
            dgettext('tuleap-timesheeting', 'The request is not valid.')
        );

        $url = TRACKER_BASE_URL . '/?' . http_build_query(array(
                'tracker' => $tracker_id,
                'func' => 'admin'
            ));

        $GLOBALS['Response']->redirect($url);
    }
}