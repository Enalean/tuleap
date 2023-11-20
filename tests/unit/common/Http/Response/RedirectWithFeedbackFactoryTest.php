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

namespace Tuleap\Http\Response;

use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Layout\Feedback\FeedbackSerializer;
use Tuleap\Layout\Feedback\NewFeedback;
use Tuleap\Test\Builders\UserTestBuilder;

final class RedirectWithFeedbackFactoryTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testCreateResponseForUser(): void
    {
        $serializer = $this->createMock(FeedbackSerializer::class);
        $redirector = new RedirectWithFeedbackFactory(
            HTTPFactoryBuilder::responseFactory(),
            $serializer
        );
        $user       = UserTestBuilder::anAnonymousUser()->build();
        $feedback   = new NewFeedback(\Feedback::INFO, 'Success !');
        $serializer->expects(self::once())
            ->method('serialize')
            ->with($user, $feedback);

        $response = $redirector->createResponseForUser($user, '/path/to/redirect/to', $feedback);
        self::assertSame(302, $response->getStatusCode());
        self::assertEquals('/path/to/redirect/to', $response->getHeaderLine('Location'));
    }
}
