<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All rights reserved
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

use Codendi_HTMLPurifier;
use CSRFSynchronizerToken;
use Exception;
use Feedback;
use ForgeConfig;
use HTTPRequest;
use PFUser;
use TemplateRendererFactory;
use Tuleap\Dashboard\AssetsIncluder;
use Tuleap\Dashboard\NameDashboardAlreadyExistsException;
use Tuleap\Dashboard\NameDashboardDoesNotExistException;
use Tuleap\Dashboard\Widget\DashboardWidgetPresenterBuilder;
use Tuleap\Dashboard\Widget\DashboardWidgetRetriever;
use Tuleap\Dashboard\Widget\OwnerInfo;
use Tuleap\Dashboard\Widget\WidgetMinimizor;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\FooterConfiguration;
use Tuleap\Layout\HeaderConfigurationBuilder;

class UserDashboardController
{
    public const DASHBOARD_TYPE        = 'user';
    public const LEGACY_DASHBOARD_TYPE = 'u';

    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf;
    /**
     * @var UserDashboardRetriever
     */
    private $retriever;
    /**
     * @var UserDashboardSaver
     */
    private $saver;
    /**
     * @var UserDashboardDeletor
     */
    private $deletor;
    /**
     * @var UserDashboardUpdator
     */
    private $updator;
    /**
     * @var DashboardWidgetRetriever
     */
    private $widget_retriever;
    /**
     * @var DashboardWidgetPresenterBuilder
     */
    private $widget_presenter_builder;
    /**
     * @var WidgetDeletor
     */
    private $widget_deletor;
    /**
     * @var WidgetMinimizor
     */
    private $widget_minimizor;
    /**
     * @var AssetsIncluder
     */
    private $assets_includer;

    public function __construct(
        CSRFSynchronizerToken $csrf,
        UserDashboardRetriever $retriever,
        UserDashboardSaver $saver,
        UserDashboardDeletor $deletor,
        UserDashboardUpdator $updator,
        DashboardWidgetRetriever $widget_retriever,
        DashboardWidgetPresenterBuilder $widget_presenter_builder,
        WidgetDeletor $widget_deletor,
        WidgetMinimizor $widget_minimizor,
        AssetsIncluder $assets_includer,
        private FirstTimerPresenterBuilder $first_timer_presenter_builder,
    ) {
        $this->csrf                     = $csrf;
        $this->retriever                = $retriever;
        $this->saver                    = $saver;
        $this->deletor                  = $deletor;
        $this->updator                  = $updator;
        $this->widget_retriever         = $widget_retriever;
        $this->widget_presenter_builder = $widget_presenter_builder;
        $this->widget_deletor           = $widget_deletor;
        $this->widget_minimizor         = $widget_minimizor;
        $this->assets_includer          = $assets_includer;
    }

    public function display(HTTPRequest $request)
    {
        $current_user    = $request->getCurrentUser();
        $dashboard_id    = $request->get('dashboard_id');
        $user_dashboards = $this->retriever->getAllUserDashboards($current_user);

        $layout = $GLOBALS['Response'];
        assert($layout instanceof BaseLayout);

        if ($dashboard_id && ! $this->doesDashboardIdExist($dashboard_id, $user_dashboards)) {
            $layout->addFeedback(
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

        $user_dashboards_presenter = $this->getUserDashboardsPresenter($current_user, $dashboard_id, $user_dashboards);

        $this->assets_includer->includeAssets($user_dashboards_presenter);

        $first_timer_presenter = $this->first_timer_presenter_builder->buildPresenter($current_user);
        if ($first_timer_presenter) {
            $layout->addJavascriptAsset($first_timer_presenter->javascript_assets);
        }

        $title    = $this->getPageTitle($user_dashboards_presenter, $current_user);
        $purifier = Codendi_HTMLPurifier::instance();
        $layout->header(
            HeaderConfigurationBuilder::get($purifier->purify($title))
            ->withBodyClass(['body-user-dashboard', 'reduce-help-button'])
            ->build()
        );
        $renderer = TemplateRendererFactory::build()->getRenderer(
            ForgeConfig::get('tuleap_dir') . '/src/templates/dashboard'
        );
        $renderer->renderToPage(
            'my',
            new MyPresenter(
                $this->csrf,
                '/my/',
                new UserPresenter($current_user),
                $user_dashboards_presenter,
                $first_timer_presenter,
            )
        );
        $layout->footer(FooterConfiguration::withoutContent());
    }

    /**
     * @return int|null
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
            $this->redirectToDashboard($dashboard_id);
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

        $this->redirectToDefaultDashboard();
    }

    /**
     * @param $dashboard_id
     * @param array $user_dashboards
     * @return UserDashboardPresenter[]
     */
    private function getUserDashboardsPresenter(PFUser $user, $dashboard_id, array $user_dashboards)
    {
        $user_dashboards_presenter = [];

        foreach ($user_dashboards as $index => $dashboard) {
            if (! $dashboard_id && $index === 0) {
                $is_active = true;
            } else {
                $is_active = $dashboard->getId() === $dashboard_id;
            }

            $widgets_presenter = [];
            if ($is_active) {
                $widgets_lines = $this->widget_retriever->getAllWidgets($dashboard->getId(), self::DASHBOARD_TYPE);
                if ($widgets_lines) {
                    $widgets_presenter = $this->widget_presenter_builder->getWidgetsPresenter(
                        $dashboard,
                        OwnerInfo::createForUser($user),
                        $widgets_lines,
                        true,
                        $user,
                    );
                }
            }

            $user_dashboards_presenter[] = new UserDashboardPresenter($dashboard, $is_active, $widgets_presenter);
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

    private function redirectToDashboard($dashboard_id)
    {
        $GLOBALS['Response']->redirect('/my/?dashboard_id=' . urlencode($dashboard_id));
    }

    public function editDashboard(HTTPRequest $request)
    {
        $this->csrf->check();

        $current_user   = $request->getCurrentUser();
        $dashboard_id   = $request->get('dashboard-id');
        $dashboard_name = $request->get('dashboard-name');

        try {
            $this->updator->update($current_user, $dashboard_id, $dashboard_name);
            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                dgettext(
                    'tuleap-core',
                    'Dashboard has been successfully updated.'
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
                    $dashboard_name
                )
            );
        } catch (NameDashboardDoesNotExistException $exception) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext(
                    'tuleap-core',
                    'The name is missing for editing the dashboard.'
                )
            );
        } catch (Exception $exception) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext(
                    'tuleap-core',
                    'Cannot update the requested dashboard.'
                )
            );
        }

        $this->redirectToDashboard($dashboard_id);
    }

    public function deleteWidget(HTTPRequest $request)
    {
        $this->csrf->check();

        $current_user = $request->getCurrentUser();
        $dashboard_id = $request->get('dashboard-id');
        $widget_id    = $request->get('widget-id');

        try {
            $this->widget_deletor->delete($current_user, $dashboard_id, self::DASHBOARD_TYPE, $widget_id);
            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                dgettext(
                    'tuleap-core',
                    'Widget has been successfully deleted.'
                )
            );
        } catch (Exception $exception) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext(
                    'tuleap-core',
                    'Cannot delete the widget.'
                )
            );
        }

        $this->redirectToDashboard($dashboard_id);
    }

    public function minimizeWidget(HTTPRequest $request)
    {
        $this->csrf->check();

        $current_user = $request->getCurrentUser();
        $dashboard_id = $request->get('dashboard-id');
        $widget_id    = $request->get('widget-id');

        $this->widget_minimizor->minimize($current_user, (int) $widget_id);

        $this->redirectToDashboard($dashboard_id);
    }

    public function maximizeWidget(HTTPRequest $request)
    {
        $this->csrf->check();

        $current_user = $request->getCurrentUser();
        $dashboard_id = $request->get('dashboard-id');
        $widget_id    = $request->get('widget-id');

        $this->widget_minimizor->maximize($current_user, (int) $widget_id);

        $this->redirectToDashboard($dashboard_id);
    }

    /**
     * @return string
     */
    private function getPageTitle(array $user_dashboards_presenter, PFUser $current_user)
    {
        $title = '';
        foreach ($user_dashboards_presenter as $presenter) {
            if ($presenter->is_active) {
                $title = $presenter->name . ' - ';
            }
        }
        $title .= $GLOBALS['Language']->getText(
            'my_index',
            'title',
            $current_user->getRealName() . ' (' . $current_user->getUserName() . ')'
        );

        return $title;
    }
}
