<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\GitLFS\Lock\Response;

use Tuleap\GitLFS\Lock\Lock;
use Tuleap\Test\Builders\UserTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class LockResponseBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private LockResponseBuilder $lock_response_builder;

    public function setUp(): void
    {
        $this->lock_response_builder = new LockResponseBuilder();
    }

    public function testSuccessfulResponseIsCorrect(): void
    {
        $user = UserTestBuilder::aUser()->withRealName('Mick Jagger')->build();

        $lock = new Lock(
            1,
            'test/FileTest.png',
            $user,
            1547486947
        );

        $response            = $this->lock_response_builder->buildSuccessfulLockCreation($lock);
        $serialized_response = json_decode(json_encode($response));

        self::assertSame('Mick Jagger', $serialized_response->lock->owner->name);
        self::assertSame('test/FileTest.png', $serialized_response->lock->path);
        self::assertSame('1', $serialized_response->lock->id);
        self::assertSame('2019-01-14T18:29:07+01:00', $serialized_response->lock->locked_at);
    }

    public function testListLocksResponseIsCorrect(): void
    {
        $user_1 = UserTestBuilder::aUser()->withRealName('Mick Jagger')->build();

        $user_2 = UserTestBuilder::aUser()->withRealName('Jean Bono')->build();

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

        $locks = [$lock_1, $lock_2];

        $response            = $this->lock_response_builder->buildSuccessfulLockList($locks);
        $serialized_response = json_decode(json_encode($response));

        $this->assertCount(2, $serialized_response->locks);

        $serialized_lock_1 = $serialized_response->locks[0];
        self::assertSame('Mick Jagger', $serialized_lock_1->owner->name);
        self::assertSame('test/FileTest1.png', $serialized_lock_1->path);
        self::assertSame('1', $serialized_lock_1->id);
        self::assertSame('2019-01-14T18:29:07+01:00', $serialized_lock_1->locked_at);

        $serialized_lock_2 = $serialized_response->locks[1];
        self::assertSame('Jean Bono', $serialized_lock_2->owner->name);
        self::assertSame('test/FileTest2.png', $serialized_lock_2->path);
        self::assertSame('2', $serialized_lock_2->id);
        self::assertSame('2019-01-16T19:18:15+01:00', $serialized_lock_2->locked_at);
    }

    public function testVerifyLocksResponseIsCorrect(): void
    {
        $our_user = UserTestBuilder::aUser()->withRealName('Mick Jagger')->build();

        $their_user = UserTestBuilder::aUser()->withRealName('Jean Bono')->build();

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

        $ours   = [$our_lock];
        $theirs = [$their_lock];

        $response            = $this->lock_response_builder->buildSuccessfulLockVerify($ours, $theirs);
        $serialized_response = json_decode(json_encode($response));

        $this->assertCount(1, $serialized_response->ours);
        $this->assertCount(1, $serialized_response->theirs);

        $our_serialized_lock = $serialized_response->ours[0];
        self::assertSame('Mick Jagger', $our_serialized_lock->owner->name);
        self::assertSame('test/FileTest1.png', $our_serialized_lock->path);
        self::assertSame('1', $our_serialized_lock->id);
        self::assertSame('2019-01-14T18:29:07+01:00', $our_serialized_lock->locked_at);

        $their_serialized_lock = $serialized_response->theirs[0];
        self::assertSame('Jean Bono', $their_serialized_lock->owner->name);
        self::assertSame('test/FileTest2.png', $their_serialized_lock->path);
        self::assertSame('2', $their_serialized_lock->id);
        self::assertSame('2019-01-16T19:18:15+01:00', $their_serialized_lock->locked_at);
    }

    public function testErrorResponseIsCorrect(): void
    {
        $message             = 'this is a test error message';
        $response            = $this->lock_response_builder->buildErrorResponse($message);
        $serialized_response = json_decode(json_encode($response));

        self::assertSame($message, $serialized_response->message);
    }

    public function testDeleteLockResponseIsCorrect(): void
    {
        $user = UserTestBuilder::aUser()->withRealName('Mick Jagger')->build();

        $lock = new Lock(
            1,
            'test/FileTest.png',
            $user,
            1547486947
        );

        $response            = $this->lock_response_builder->buildSuccessfulLockDestruction($lock);
        $serialized_response = json_decode(json_encode($response));

        self::assertSame('Mick Jagger', $serialized_response->lock->owner->name);
        self::assertSame('test/FileTest.png', $serialized_response->lock->path);
        self::assertSame('1', $serialized_response->lock->id);
        self::assertSame('2019-01-14T18:29:07+01:00', $serialized_response->lock->locked_at);
    }

    public function testLockConflictResponseIsCorrect(): void
    {
        $user = UserTestBuilder::aUser()->withRealName('Mick Jagger')->build();

        $lock = new Lock(
            1,
            'test/FileTest.png',
            $user,
            1547486947
        );

        $response            = $this->lock_response_builder->buildLockConflictErrorResponse($lock);
        $serialized_response = json_decode(json_encode($response));

        self::assertSame('Mick Jagger', $serialized_response->lock->owner->name);
        self::assertSame('test/FileTest.png', $serialized_response->lock->path);
        self::assertSame('1', $serialized_response->lock->id);
        self::assertSame('2019-01-14T18:29:07+01:00', $serialized_response->lock->locked_at);
        self::assertSame('already created lock', $serialized_response->message);
    }
}
