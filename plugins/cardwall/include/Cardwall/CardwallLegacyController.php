<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Cardwall;

use Cardwall_CardController;
use Cardwall_CardFields;
use Cardwall_OnTop_ConfigFactory;
use Cardwall_SingleCardBuilder;
use Cardwall_UserPreferences_UserPreferencesController;
use Feedback;
use HTTPRequest;
use PlanningFactory;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use Tuleap\Cardwall\AccentColor\AccentColorBuilder;
use Tuleap\Cardwall\BackgroundColor\BackgroundColorBuilder;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindDecoratorRetriever;

class CardwallLegacyController implements DispatchableWithRequest
{

    /**
     * @var Cardwall_OnTop_ConfigFactory
     */
    private $config_factory;

    public function __construct(Cardwall_OnTop_ConfigFactory $config_factory)
    {
        $this->config_factory = $config_factory;
    }

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param array       $variables
     * @throws NotFoundException
     * @throws ForbiddenException
     * @return void
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        switch ($request->get('action')) {
            case 'toggle_user_autostack_column':
                $display_preferences_controller = new Cardwall_UserPreferences_UserPreferencesController($request);
                $display_preferences_controller->toggleAutostack();
                break;

            case 'toggle_user_display_avatar':
                $display_preferences_controller = new Cardwall_UserPreferences_UserPreferencesController($request);
                $display_preferences_controller->toggleUserDisplay();
                break;

            case 'get-card':
                $bind_decorator_retriever = new BindDecoratorRetriever();
                try {
                    $single_card_builder = new Cardwall_SingleCardBuilder(
                        $this->config_factory,
                        new Cardwall_CardFields(
                            Tracker_FormElementFactory::instance()
                        ),
                        Tracker_ArtifactFactory::instance(),
                        PlanningFactory::build(),
                        new BackgroundColorBuilder($bind_decorator_retriever),
                        new AccentColorBuilder(Tracker_FormElementFactory::instance(), $bind_decorator_retriever)
                    );
                    $controller = new Cardwall_CardController(
                        $request,
                        $single_card_builder->getSingleCard(
                            $request->getCurrentUser(),
                            $request->getValidated('id', 'uint', 0),
                            $request->getValidated('planning_id', 'uint', 0)
                        )
                    );
                    $controller->getCard();
                } catch (\Exception $exception) {
                    $GLOBALS['Response']->addFeedback(Feedback::ERROR, $exception->getMessage());
                    $GLOBALS['Response']->sendStatusCode(400);
                }
                break;
        }
    }
}
