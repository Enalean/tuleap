<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Dashboard\Widget;

use HTTPRequest;
use Tuleap\Dashboard\Widget\Add\AddWidgetController;
use Tuleap\Option\Option;
use Tuleap\Widget\WidgetFactory;
use Widget;

class Router
{
    /**
     * @var PreferencesController
     */
    private $preferences_controller;
    /**
     * @var AddWidgetController
     */
    private $add_widget_controller;
    /**
     * @var WidgetFactory
     */
    private $widget_factory;

    public function __construct(
        PreferencesController $preferences_controller,
        AddWidgetController $add_widget_controller,
        WidgetFactory $widget_factory,
    ) {
        $this->preferences_controller = $preferences_controller;
        $this->add_widget_controller  = $add_widget_controller;
        $this->widget_factory         = $widget_factory;
    }

    public function route(HTTPRequest $request): void
    {
        $action = $request->get('action');

        switch ($action) {
            case 'get-add-modal-content':
                $this->rejectIfRequestDoesNotAppearToBeFetched($request);
                $this->add_widget_controller->display($request);
                return;
            case 'get-edit-modal-content':
                $this->rejectIfRequestDoesNotAppearToBeFetched($request);
                $this->preferences_controller->display($request);
                return;
            case 'add-widget':
                if (! $request->isPost()) {
                    $this->rejectMalformedRequest();
                }
                $this->add_widget_controller->create($request);
                return;
            case 'edit-widget':
                if (! $request->isPost()) {
                    $this->rejectMalformedRequest();
                }
                $this->preferences_controller->update($request);
                return;
            case 'ajax':
                $this->getWidgetFromUrl($request)
                    ->match(
                        function (Widget $widget) use ($request): void {
                            $param       = $request->get('name');
                            $param_keys  = array_keys($param);
                            $name        = array_pop($param_keys);
                            $instance_id = (int) $param[$name];

                            if ($widget->isAjax()) {
                                $this->rejectIfRequestDoesNotAppearToBeFetched($request);
                                $widget->loadContent($instance_id);
                                echo $widget->getContent();
                            }
                        },
                        fn(): never => $this->rejectMalformedRequest()
                    );
                return;
            case 'rss':
                $this->getWidgetFromUrl($request)
                    ->match(
                        function (Widget $widget) use ($request): void {
                            $this->rejectIfRequestDoesNotAppearToBeFetched($request);
                            $widget->displayRss();
                        },
                        fn(): never => $this->rejectMalformedRequest()
                    );
                return;
        }
        $this->rejectMalformedRequest();
    }

    private function rejectIfRequestDoesNotAppearToBeFetched(HTTPRequest $request): void
    {
        $sec_fetch_dest = $request->getFromServer('HTTP_SEC_FETCH_DEST');
        $sec_fetch_mode = $request->getFromServer('HTTP_SEC_FETCH_MODE');

        if ($sec_fetch_dest === false || $sec_fetch_mode === false) {
            return;
        }

        if ($sec_fetch_dest === 'empty' && $sec_fetch_mode === 'cors') {
            return;
        }

        $GLOBALS['Response']->sendStatusCode(400);
        echo 'Direct accesses to this page are not expected';
        exit;
    }

    private function rejectMalformedRequest(): never
    {
        $GLOBALS['Response']->sendStatusCode(404);
        echo 'Request appears to be malformed';
        exit;
    }

    /**
     * @return Option<Widget>
     */
    private function getWidgetFromUrl(HTTPRequest $request): Option
    {
        $param = $request->get('name');
        if (! is_array($param)) {
            return Option::nothing(Widget::class);
        }
        $param_keys = array_keys($param);
        $name       = array_pop($param_keys);

        return Option::fromNullable($this->widget_factory->getInstanceByWidgetName($name));
    }
}
