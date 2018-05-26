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

namespace Tuleap\BotMattermost\Controller;

require_once __DIR__ . '/../bootstrap.php';

use BaseLanguage;
use CSRFSynchronizerToken;
use EventManager;
use HTTPRequest;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\BotMattermost\Bot\Bot;
use Tuleap\Theme\BurningParrot\BurningParrotTheme;

class AdminControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var AdminController
     */
    private $admin_controller;

    private $csrf;
    private $bot_factory;
    private $event_manager;
    private $http_request;

    public function setUp()
    {
        parent::setUp();

        $this->csrf                 = \Mockery::spy(CSRFSynchronizerToken::class);
        $this->bot_factory          = \Mockery::spy(\Tuleap\BotMattermost\Bot\BotFactory::class);
        $this->event_manager        = \Mockery::spy(EventManager::class);
        $this->burning_parrot_theme = \Mockery::spy(BurningParrotTheme::class);
        $this->language             = \Mockery::spy(BaseLanguage::class);
        $this->admin_controller     = new AdminController(
            $this->csrf,
            $this->bot_factory,
            $this->event_manager,
            $this->burning_parrot_theme,
            $this->language
        );

        $this->http_request     = \Mockery::spy(HTTPRequest::class);

        HTTPRequest::setInstance($this->http_request);
    }

    public function tearDown()
    {
        HTTPRequest::clearInstance();

        parent::tearDown();
    }

    public function testDeleteBotProcessBotDeletedEvent()
    {
        $bot = new Bot(1, 'bot', 'webhook_url', '');

        $this->csrf->allows()->check()->andReturns(true);
        $this->http_request->allows()->get('bot_id')->andReturns(1);
        $this->bot_factory->allows()->getBotById(1)->andReturns($bot);
        $this->bot_factory->allows()->deleteBotById(1)->andReturns(true);

        $this_event_manager_processEvent = $this->event_manager->shouldReceive('processEvent');
        $this_event_manager_processEvent->times(1);

        $this->admin_controller->deleteBot($this->http_request);
    }
}
