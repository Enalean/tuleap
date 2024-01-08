<?php
/**
 * Copyright (c) Enalean, 2012—present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2004-2010. All rights reserved
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

use Tuleap\ForgeConfigSandbox;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
final class SystemEvent_USER_RENAME_Test extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    /**
     * Rename user 142 'mickey' in 'tazmani'
     */
    public function testRenameOps(): void
    {
        $now = (new DateTimeImmutable())->getTimestamp();

        $evt = $this->getMockBuilder(\SystemEvent_USER_RENAME::class)
            ->setConstructorArgs(
                [
                    '1',
                    SystemEvent::TYPE_USER_RENAME,
                    SystemEvent::OWNER_ROOT,
                    '142' . SystemEvent::PARAMETER_SEPARATOR . 'tazmani',
                    SystemEvent::PRIORITY_HIGH,
                    SystemEvent::STATUS_RUNNING,
                    $now,
                    $now,
                    $now,
                    '',
                ]
            )
            ->onlyMethods([
                'getUser',
                'getBackend',
                'updateDB',
                'done',
                'getProject',
            ])
            ->getMock();

        // The user
        $user = \Tuleap\Test\Builders\UserTestBuilder::aUser()->withId(142)->withUserName('mickey')->withProjects([133])->build();
        $evt->method('getUser')->with('142')->willReturn($user);

        // The project
        $evt->method('getProject')->with(133)->willReturn(\Tuleap\Test\Builders\ProjectTestBuilder::aProject()->withId(133)->build());

        // System
        $backendSystem = $this->createMock(\BackendSystem::class);

        // DB
        $evt->method('updateDB')->willReturn(true);

        $evt->method('getBackend')->willReturnMap([
            ['System', $backendSystem],
        ]);

        // Expect everything went OK
        $evt->expects(self::once())->method('done');

        // Launch the event
        self::assertTrue($evt->process());
    }
}
