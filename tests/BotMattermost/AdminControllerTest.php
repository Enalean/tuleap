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

namespace Tuleap\BotMattermost\Controller;

require_once dirname(__FILE__) . '/../bootstrap.php';

use HTTPRequest;
use Tuleap\BotMattermost\Bot\Bot;
use TuleapTestCase;

class AdminControllerTest extends TuleapTestCase
{
    private $csrf;
    private $bot_factory;
    private $event_manager;
    private $admin_controller;
    private $http_request;

    public function setUp()
    {
        parent::setUp();
        $this->csrf             = mock('CSRFSynchronizerToken');
        $this->bot_factory      = mock('Tuleap\\BotMattermost\\Bot\\BotFactory');
        $this->event_manager    = mock('EventManager');
        $this->admin_controller = new AdminController($this->csrf, $this->bot_factory, $this->event_manager);
        $this->http_request     = mock('HTTPRequest');

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

        stub($this->csrf)->check()->returns(true);
        stub($this->bot_factory)->getBotById()->returns($bot);
        stub($this->bot_factory)->deleteBotById()->returns(true);
        stub($this->event_manager)->processEvent()->returns(true);

        $this->http_request->set('bot_id', $bot->getId());
        $this->event_manager->expectCallCount('processEvent', 1);
        $this->admin_controller->deleteBot($this->http_request);
    }

}