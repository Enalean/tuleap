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

namespace Tuleap\BotMattermost\Controller;

require_once __DIR__ . '/../bootstrap.php';

use BaseLanguage;
use CSRFSynchronizerToken;
use EventManager;
use HTTPRequest;
use Tuleap\BotMattermost\Bot\Bot;
use Tuleap\Layout\BaseLayout;
use Tuleap\Test\PHPUnit\TestCase;

final class AdminControllerTest extends TestCase
{
    /**
     * @var AdminController
     */
    private $admin_controller;

    private $csrf;
    private $bot_factory;
    private $event_manager;
    private $http_request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->csrf             = $this->createMock(CSRFSynchronizerToken::class);
        $this->bot_factory      = $this->createMock(\Tuleap\BotMattermost\Bot\BotFactory::class);
        $this->event_manager    = $this->createMock(EventManager::class);
        $this->admin_controller = new AdminController(
            $this->csrf,
            $this->bot_factory,
            $this->event_manager,
            $this->createMock(BaseLanguage::class)
        );

        $this->http_request = $this->createMock(HTTPRequest::class);

        HTTPRequest::setInstance($this->http_request);
    }

    protected  function tearDown(): void
    {
        HTTPRequest::clearInstance();

        parent::tearDown();
    }

    public function testDeleteBotProcessBotDeletedEvent(): void
    {
        $bot = new Bot(1, 'bot', 'webhook_url', '');

        $this->csrf->expects(self::once())->method('check')->willReturn(true);
        $this->http_request->expects(self::once())->method('get')->with('bot_id')->willReturn(1);
        $this->bot_factory->expects(self::exactly(2))->method('getBotById')->with(1)->willReturn($bot);
        $this->bot_factory->expects(self::once())->method('deleteBotById')->with(1)->willReturn(true);

        $this->event_manager
            ->expects(self::once())
            ->method('processEvent');

        $base_layout = $this->createMock(BaseLayout::class);
        $base_layout
            ->expects(self::once())
            ->method('addFeedback');
        $base_layout
            ->expects(self::once())
            ->method('redirect');

        $this->admin_controller->deleteBot(
            $this->http_request,
            $base_layout
        );
    }
}
