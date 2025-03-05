<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace common\SVNCore\AccessControl;

use Tuleap\SVNCore\AccessControl\AfterLocalSVNLogin;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AfterLocalSVNLoginTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testLoginIsAllowedByDefault(): void
    {
        $user    = UserTestBuilder::buildWithDefaults();
        $project = ProjectTestBuilder::aProject()->build();
        $event   = new AfterLocalSVNLogin($user, $project);

        self::assertSame($user, $event->user);
        self::assertSame($project, $event->project);
        self::assertTrue($event->isIsLoginAllowed());
    }

    public function testRefusesLogin(): void
    {
        $event = new AfterLocalSVNLogin(UserTestBuilder::buildWithDefaults(), ProjectTestBuilder::aProject()->build());

        $expected_feedback_message = 'Some message';
        $event->refuseLogin($expected_feedback_message);

        self::assertFalse($event->isIsLoginAllowed());
        self::assertSame($expected_feedback_message, $event->getFeedbackMessage());
    }
}
