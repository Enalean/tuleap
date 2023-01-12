<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\User\Account\Register;

use Tuleap\Layout\BaseLayout;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\User\Account\RegistrationGuardEvent;

class ProcessRegisterFormControllerTest extends TestCase
{
    public function testPasswordIsNeededByDefault(): void
    {
        $form_processor = IProcessRegisterFormStub::buildSelf();

        $event_manager = new class extends \EventManager
        {
            public function processEvent($event_name, $params = [])
            {
            }
        };

        $controller = new ProcessRegisterFormController(
            $form_processor,
            EventDispatcherStub::withIdentityCallback(),
            $event_manager,
        );
        $controller->process(
            HTTPRequestBuilder::get()->build(),
            $this->createMock(BaseLayout::class),
            [],
        );

        self::assertTrue($form_processor->hasBeenProcessed());
        self::assertFalse($form_processor->isAdmin());
        self::assertTrue($form_processor->isPasswordNeeded());
    }

    public function testWhenPasswordIsNotNeeded(): void
    {
        $form_processor = IProcessRegisterFormStub::buildSelf();

        $event_manager = new class extends \EventManager
        {
            public function processEvent($event_name, $params = [])
            {
                if ($event_name === 'before_register') {
                    $params['is_password_needed'] = false;
                }
            }
        };

        $controller = new ProcessRegisterFormController(
            $form_processor,
            EventDispatcherStub::withIdentityCallback(),
            $event_manager,
        );
        $controller->process(
            HTTPRequestBuilder::get()->build(),
            $this->createMock(BaseLayout::class),
            [],
        );

        self::assertTrue($form_processor->hasBeenProcessed());
        self::assertFalse($form_processor->isAdmin());
        self::assertFalse($form_processor->isPasswordNeeded());
    }

    public function testRejectWhenRegistrationIsNotPossible(): void
    {
        $this->expectException(ForbiddenException::class);

        $form_processor = IProcessRegisterFormStub::buildSelf();

        $event_manager = new class extends \EventManager
        {
            public function processEvent($event_name, $params = [])
            {
            }
        };

        $controller = new ProcessRegisterFormController(
            $form_processor,
            EventDispatcherStub::withCallback(
                static function (RegistrationGuardEvent $event) {
                    $event->disableRegistration();

                    return $event;
                }
            ),
            $event_manager,
        );
        $controller->process(
            HTTPRequestBuilder::get()->build(),
            $this->createMock(BaseLayout::class),
            [],
        );

        self::assertFalse($form_processor->hasBeenProcessed());
    }
}
