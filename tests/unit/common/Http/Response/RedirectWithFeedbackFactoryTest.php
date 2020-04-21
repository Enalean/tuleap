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

namespace TuleapCodingStandard\Http\Response;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Layout\Feedback\FeedbackSerializer;
use Tuleap\Layout\Feedback\NewFeedback;
use Tuleap\Test\Builders\UserTestBuilder;

final class RedirectWithFeedbackFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testCreateResponseForUser(): void
    {
        $serializer = M::mock(FeedbackSerializer::class);
        $redirector = new RedirectWithFeedbackFactory(
            HTTPFactoryBuilder::responseFactory(),
            $serializer
        );
        $user       = UserTestBuilder::anAnonymousUser()->build();
        $feedback   = new NewFeedback(\Feedback::INFO, 'Success !');
        $serializer->shouldReceive('serialize')
            ->once()
            ->with($user, $feedback);

        $response = $redirector->createResponseForUser($user, '/path/to/redirect/to', $feedback);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertEquals('/path/to/redirect/to', $response->getHeaderLine('Location'));
    }
}
