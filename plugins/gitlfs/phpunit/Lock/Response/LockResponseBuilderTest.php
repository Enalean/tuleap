<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\GitLFS\Lock\Response;

use GitRepository;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tuleap\GitLFS\Lock\Lock;

class LockResponseBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var LockResponseBuilder
     */
    private $lock_response_builder;

    public function setUp()
    {
        $this->lock_response_builder = new LockResponseBuilder();
    }

    public function testSuccessfulResponseIsCorrect()
    {
        $user       = \Mockery::mock(PFUser::class);

        $user->shouldReceive('getRealName')->andReturn('Mick Jagger');

        $lock = new Lock(
            1,
            'test/FileTest.png',
            $user,
            1547486947
        );

        $response            = $this->lock_response_builder->buildSuccessfulLockCreation($lock);
        $serialized_response = json_decode(json_encode($response));

        $this->assertSame('Mick Jagger', $serialized_response->lock->owner->name);
        $this->assertSame('test/FileTest.png', $serialized_response->lock->path);
        $this->assertSame('1', $serialized_response->lock->id);
        $this->assertSame('2019-01-14T18:29:07+01:00', $serialized_response->lock->locked_at);
    }
}
