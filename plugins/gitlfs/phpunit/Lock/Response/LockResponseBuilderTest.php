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

    public function setUp(): void
    {
        $this->lock_response_builder = new LockResponseBuilder();
    }

    public function testSuccessfulResponseIsCorrect()
    {
        $user = \Mockery::mock(PFUser::class);

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

    public function testListLocksResponseIsCorrect()
    {
        $user_1 = \Mockery::mock(PFUser::class);
        $user_1->shouldReceive('getRealName')->andReturn('Mick Jagger');

        $user_2 = \Mockery::mock(PFUser::class);
        $user_2->shouldReceive('getRealName')->andReturn('Jean Bono');

        $lock_1 = new Lock(
            1,
            'test/FileTest1.png',
            $user_1,
            1547486947
        );

        $lock_2 = new Lock(
            2,
            'test/FileTest2.png',
            $user_2,
            1547662695
        );

        $locks = array($lock_1, $lock_2);

        $response            = $this->lock_response_builder->buildSuccessfulLockList($locks);
        $serialized_response = json_decode(json_encode($response));

        $this->assertCount(2, $serialized_response->locks);

        $serialized_lock_1 = $serialized_response->locks[0];
        $this->assertSame('Mick Jagger', $serialized_lock_1->owner->name);
        $this->assertSame('test/FileTest1.png', $serialized_lock_1->path);
        $this->assertSame('1', $serialized_lock_1->id);
        $this->assertSame('2019-01-14T18:29:07+01:00', $serialized_lock_1->locked_at);

        $serialized_lock_2 = $serialized_response->locks[1];
        $this->assertSame('Jean Bono', $serialized_lock_2->owner->name);
        $this->assertSame('test/FileTest2.png', $serialized_lock_2->path);
        $this->assertSame('2', $serialized_lock_2->id);
        $this->assertSame('2019-01-16T19:18:15+01:00', $serialized_lock_2->locked_at);
    }

    public function testVerifyLocksResponseIsCorrect()
    {
        $our_user = \Mockery::mock(PFUser::class);
        $our_user->shouldReceive('getRealName')->andReturn('Mick Jagger');

        $their_user = \Mockery::mock(PFUser::class);
        $their_user->shouldReceive('getRealName')->andReturn('Jean Bono');

        $our_lock = new Lock(
            1,
            'test/FileTest1.png',
            $our_user,
            1547486947
        );

        $their_lock = new Lock(
            2,
            'test/FileTest2.png',
            $their_user,
            1547662695
        );

        $ours   = array($our_lock);
        $theirs = array($their_lock);

        $response            = $this->lock_response_builder->buildSuccessfulLockVerify($ours, $theirs);
        $serialized_response = json_decode(json_encode($response));

        $this->assertCount(1, $serialized_response->ours);
        $this->assertCount(1, $serialized_response->theirs);

        $our_serialized_lock = $serialized_response->ours[0];
        $this->assertSame('Mick Jagger', $our_serialized_lock->owner->name);
        $this->assertSame('test/FileTest1.png', $our_serialized_lock->path);
        $this->assertSame('1', $our_serialized_lock->id);
        $this->assertSame('2019-01-14T18:29:07+01:00', $our_serialized_lock->locked_at);

        $their_serialized_lock = $serialized_response->theirs[0];
        $this->assertSame('Jean Bono', $their_serialized_lock->owner->name);
        $this->assertSame('test/FileTest2.png', $their_serialized_lock->path);
        $this->assertSame('2', $their_serialized_lock->id);
        $this->assertSame('2019-01-16T19:18:15+01:00', $their_serialized_lock->locked_at);
    }

    public function testErrorResponseIsCorrect()
    {
        $message             = "this is a test error message";
        $response            = $this->lock_response_builder->buildErrorResponse($message);
        $serialized_response = json_decode(json_encode($response));

        $this->assertSame($message, $serialized_response->message);
    }

    public function testDeleteLockResponseIsCorrect()
    {
        $user = \Mockery::mock(PFUser::class);

        $user->shouldReceive('getRealName')->andReturn('Mick Jagger');

        $lock = new Lock(
            1,
            'test/FileTest.png',
            $user,
            1547486947
        );

        $response            = $this->lock_response_builder->buildSuccessfulLockDestruction($lock);
        $serialized_response = json_decode(json_encode($response));

        $this->assertSame('Mick Jagger', $serialized_response->lock->owner->name);
        $this->assertSame('test/FileTest.png', $serialized_response->lock->path);
        $this->assertSame('1', $serialized_response->lock->id);
        $this->assertSame('2019-01-14T18:29:07+01:00', $serialized_response->lock->locked_at);
    }

    public function testLockConflictResponseIsCorrect()
    {
        $user = \Mockery::mock(PFUser::class);
        $user->shouldReceive('getRealName')->andReturn('Mick Jagger');

        $lock = new Lock(
            1,
            'test/FileTest.png',
            $user,
            1547486947
        );

        $response            = $this->lock_response_builder->buildLockConflictErrorResponse($lock);
        $serialized_response = json_decode(json_encode($response));

        $this->assertSame('Mick Jagger', $serialized_response->lock->owner->name);
        $this->assertSame('test/FileTest.png', $serialized_response->lock->path);
        $this->assertSame('1', $serialized_response->lock->id);
        $this->assertSame('2019-01-14T18:29:07+01:00', $serialized_response->lock->locked_at);
        $this->assertSame('already created lock', $serialized_response->message);
    }
}
