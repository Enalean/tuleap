<?php
/**
 * Copyright (c) Enalean, 2017. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\Dashboard\User;

use CSRFSynchronizerToken;
use Exception;
use Feedback;
use ForgeConfig;
use HttpRequest;
use TemplateRendererFactory;

class Controller
{
    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf;
    /**
     * @var Retriever
     */
    private $retriever;
    /**
     * @var Saver
     */
    private $saver;
    private $title;
    /**
     * @var Deletor
     */
    private $deletor;

    public function __construct(
        CSRFSynchronizerToken $csrf,
        $title,
        Retriever $retriever,
        Saver $saver,
        Deletor $deletor
    ) {
        $this->csrf      = $csrf;
        $this->title     = $title;
        $this->retriever = $retriever;
        $this->saver     = $saver;
        $this->deletor   = $deletor;
    }

    /**
     * @param HTTPRequest $request
     */
    public function display(HTTPRequest $request)
    {
        $current_user    = $request->getCurrentUser();
        $dashboard_id    = $request->get('dashboard_id');
        $user_dashboards = $this->retriever->getAllUserDashboards($current_user);

        if ($dashboard_id && ! $this->doesDashboardIdExist($dashboard_id, $user_dashboards)) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                sprintf(
                    dgettext(
                        'tuleap-core',
                        "The dashboard '%s' doesn't exist."
                    ),
                    $dashboard_id
                )
            );
        }

        $user_dashboards_presenter = $this->getUserDashboardsPresenter($dashboard_id, $user_dashboards);

        $GLOBALS['Response']->header(array('title' => $this->title));
        $renderer = TemplateRendererFactory::build()->getRenderer(
            ForgeConfig::get('tuleap_dir') . '/src/templates/dashboard'
        );
        $renderer->renderToPage(
            'my',
            new MyPresenter(
                $this->csrf,
                new UserPresenter(
                    $current_user->getRealName(),
                    $current_user->getUnixName()
                ),
                $user_dashboards_presenter
            )
        );

        $GLOBALS['Response']->includeFooterJavascriptFile('/scripts/dashboards/dashboard-modals.js');
        $GLOBALS['Response']->footer(array());
    }

    /**
     * @param HttpRequest $request
     * @return integer|null
     */
    public function createDashboard(HTTPRequest $request)
    {
        $this->csrf->check();

        $dashboard_id = null;
        $current_user = $request->getCurrentUser();
        $name         = $request->get('dashboard-name');

        try {
            $dashboard_id = $this->saver->save($current_user, $name);
            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                dgettext(
                    'tuleap-core',
                    'Dashboard has been successfully created.'
                )
            );
        } catch (NameDashboardAlreadyExistsException $exception) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                sprintf(
                    dgettext(
                        'tuleap-core',
                        'The dashboard "%s" already exists.'
                    ),
                    $name
                )
            );
        } catch (NameDashboardDoesNotExistException $exception) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext(
                    'tuleap-core',
                    'The name is missing for creating a dashboard.'
                )
            );
        } catch (Exception $exception) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext(
                    'tuleap-core',
                    'Dashboard creation failed.'
                )
            );
        }

        return $dashboard_id;
    }

    /**
     * @param $dashboard_id
     * @param array $user_dashboards
     * @return DashboardPresenter[]
     */
    private function getUserDashboardsPresenter($dashboard_id, array $user_dashboards)
    {
        $user_dashboards_presenter = array();

        foreach ($user_dashboards as $index => $dashboard) {
            if (! $dashboard_id && $index === 0) {
                $is_active = true;
            } else {
                $is_active = $dashboard->getId() === $dashboard_id;
            }

            $user_dashboards_presenter[] = new DashboardPresenter($dashboard, $is_active);
        }

        return $user_dashboards_presenter;
    }

    /**
     * @param $dashboard_id
     * @param $user_dashboards
     * @return bool
     */
    private function doesDashboardIdExist($dashboard_id, array $user_dashboards)
    {
        foreach ($user_dashboards as $dashboard) {
            if ($dashboard_id === $dashboard->getId()) {
                return true;
            }
        }

        return false;
    }

    public function deleteDashboard(HTTPRequest $request)
    {
        $this->csrf->check();

        $current_user = $request->getCurrentUser();
        $dashboard_id = $request->get('dashboard-id');

        try {
            $this->deletor->delete($current_user, $dashboard_id);
            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                dgettext(
                    'tuleap-core',
                    'Dashboard has been successfully deleted.'
                )
            );
        } catch (Exception $exception) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext(
                    'tuleap-core',
                    'Cannot delete the requested dashboard.'
                )
            );
        }

        $this->redirectToDefaultDashboard();
    }

    private function redirectToDefaultDashboard()
    {
        $GLOBALS['Response']->redirect('/my/');
    }
}
