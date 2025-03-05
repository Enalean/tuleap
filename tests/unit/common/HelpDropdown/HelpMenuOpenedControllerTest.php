<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\HelpDropdown;

use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class HelpMenuOpenedControllerTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&\UserManager
     */
    private $user_manager;
    private Prometheus $prometheus;
    private HelpMenuOpenedController $controller;

    protected function setUp(): void
    {
        $this->user_manager = $this->createStub(\UserManager::class);
        $this->prometheus   = Prometheus::getInMemory();
        $this->controller   = new HelpMenuOpenedController($this->user_manager, $this->prometheus, HTTPFactoryBuilder::responseFactory(), new SapiEmitter());
    }

    public function testDoesAllExpectedActions(): void
    {
        $current_user = $this->createMock(\PFUser::class);
        $this->user_manager->method('getCurrentUser')->willReturn($current_user);

        $current_user->expects(self::atLeastOnce())->method('setPreference')->with('has_release_note_been_seen', '1');

        $response = $this->controller->handle(new NullServerRequest());

        self::assertEquals(204, $response->getStatusCode());
        self::assertStringContainsString('help_menu_opened_total 1', $this->prometheus->renderText());
    }
}
