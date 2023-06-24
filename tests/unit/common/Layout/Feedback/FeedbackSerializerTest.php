<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Layout\Feedback;

use Tuleap\Test\Builders\UserTestBuilder;

final class FeedbackSerializerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testSerialize(): void
    {
        $user = UserTestBuilder::aUser()->build();
        $user->setSessionId(12);
        $dao                 = $this->createMock(\FeedbackDao::class);
        $feedback_serializer = new FeedbackSerializer($dao);
        $dao->expects(self::once())
            ->method('create')
            ->with(
                12,
                [
                    ['level' => \Feedback::ERROR, 'msg' => 'An error has occurred', 'purify' => CODENDI_PURIFIER_CONVERT_HTML],
                ]
            );

        $feedback_serializer->serialize($user, new NewFeedback(\Feedback::ERROR, 'An error has occurred'));
    }
}
