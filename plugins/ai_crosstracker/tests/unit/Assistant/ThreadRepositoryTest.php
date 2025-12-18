<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\AICrossTracker\Assistant;

use Override;
use Tuleap\AI\Mistral\Message;
use Tuleap\AI\Mistral\Role;
use Tuleap\AI\Mistral\StringContent;
use Tuleap\AICrossTracker\Stub\MessageRepositoryStub;
use Tuleap\AICrossTracker\Stub\ThreadStorageStub;
use Tuleap\CrossTracker\Widget\ProjectCrossTrackerWidget;
use Tuleap\DB\DatabaseUUIDFactory;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ThreadRepositoryTest extends TestCase
{
    private DatabaseUUIDFactory $uuid_factory;

    #[Override]
    protected function setUp(): void
    {
        $this->uuid_factory = new DatabaseUUIDV7Factory();
    }

    public function testItCreatesAnewThread(): void
    {
        $thread_repository = new ThreadRepository(
            new MessageRepositoryStub(),
            new ThreadStorageStub($this->uuid_factory),
            $this->uuid_factory,
        );

        $thread = $thread_repository->fetchThread(
            ProjectCrossTrackerWidget::build(111, 1, 'foo', 123),
            UserTestBuilder::anActiveUser()->build(),
            null,
            Message::buildUserMessageFromString('some question'),
        )->unwrapOr(null);

        self::assertInstanceOf(Thread::class, $thread);
        self::assertCount(1, $thread->messages);

        self::assertEquals(Role::USER, $thread->messages[0]->role);
        self::assertEquals('some question', $thread->messages[0]->content);
    }

    public function testAThreadOfMessageWithPreviousExchanges(): void
    {
        $uuid      = '019b2bf2-3cb7-736a-8979-a16b0297cc1d';
        $thread_id = ThreadID::fromUUID($this->uuid_factory->buildUUIDFromHexadecimalString($uuid)->unwrapOr(null));

        $thread_repository = new ThreadRepository(
            new MessageRepositoryStub(
                new Thread(
                    $thread_id,
                    new Message(Role::USER, new StringContent('some content')),
                    new Message(Role::ASSISTANT, new StringContent('some answer')),
                ),
            ),
            new ThreadStorageStub($this->uuid_factory),
            $this->uuid_factory,
        );

        $thread = $thread_repository->fetchThread(
            ProjectCrossTrackerWidget::build(345, 1, 'foo', 123),
            UserTestBuilder::buildWithId(123),
            $uuid,
            Message::buildUserMessageFromString('some question'),
        )->unwrapOr(null);

        self::assertInstanceOf(Thread::class, $thread);
        self::assertCount(3, $thread->messages);

        self::assertEquals(Role::USER, $thread->messages[0]->role);
        self::assertEquals('some content', $thread->messages[0]->content);

        self::assertEquals(Role::ASSISTANT, $thread->messages[1]->role);
        self::assertEquals('some answer', $thread->messages[1]->content);

        self::assertEquals(Role::USER, $thread->messages[2]->role);
        self::assertEquals('some question', $thread->messages[2]->content);
    }

    public function testDistinguishDifferentThreadsOfMessages(): void
    {
        $uuid_1   = '019b2bf2-3cb7-736a-8979-a16b0297cc1d';
        $thread_1 = ThreadID::fromUUID($this->uuid_factory->buildUUIDFromHexadecimalString($uuid_1)->unwrapOr(null));
        $uuid_2   = '019b2bf2-3cb7-736a-8979-a16b0297cc2d';
        $thread_2 = ThreadID::fromUUID($this->uuid_factory->buildUUIDFromHexadecimalString($uuid_2)->unwrapOr(null));

        $thread_repository = new ThreadRepository(
            new MessageRepositoryStub(
                new Thread(
                    $thread_1,
                    new Message(Role::USER, new StringContent('some content 1')),
                    new Message(Role::ASSISTANT, new StringContent('some answer 1')),
                ),
                new Thread(
                    $thread_2,
                    new Message(Role::USER, new StringContent('some content 2')),
                    new Message(Role::ASSISTANT, new StringContent('some answer 2')),
                ),
            ),
            new ThreadStorageStub($this->uuid_factory),
            $this->uuid_factory,
        );

        $thread = $thread_repository->fetchThread(
            ProjectCrossTrackerWidget::build(345, 1, 'foo', 123),
            UserTestBuilder::buildWithId(123),
            $uuid_2,
            Message::buildUserMessageFromString('some question 2'),
        )->unwrapOr(null);

        self::assertInstanceOf(Thread::class, $thread);
        self::assertCount(3, $thread->messages);

        self::assertEquals(Role::USER, $thread->messages[0]->role);
        self::assertEquals('some content 2', $thread->messages[0]->content);

        self::assertEquals(Role::ASSISTANT, $thread->messages[1]->role);
        self::assertEquals('some answer 2', $thread->messages[1]->content);

        self::assertEquals(Role::USER, $thread->messages[2]->role);
        self::assertEquals('some question 2', $thread->messages[2]->content);
    }

    public function testItStoresMessagesForNewThreads(): void
    {
        $message_repository = new MessageRepositoryStub();
        $thread_storage     = new ThreadStorageStub($this->uuid_factory);

        $thread_repository = new ThreadRepository(
            $message_repository,
            $thread_storage,
            $this->uuid_factory,
        );

        $user = UserTestBuilder::anActiveUser()->withId(123)->build();

        $thread = $thread_repository->fetchThread(
            ProjectCrossTrackerWidget::build(345, 1, 'foo', 123),
            $user,
            null,
            Message::buildUserMessageFromString('some question'),
        )->unwrapOr(null);

        $stored_messages = $message_repository->fetch($thread->id);
        self::assertCount(1, $stored_messages);
        self::assertEquals(Role::USER, $stored_messages[0]->role);
        self::assertEquals('some question', $stored_messages[0]->content);

        self::assertEquals(345, $thread_storage->widget_id);
        self::assertEquals($user, $thread_storage->user);
    }

    public function testItStoresNewMessagesForExistingThreads(): void
    {
        $uuid      = '019b2bf2-3cb7-736a-8979-a16b0297cc1d';
        $thread_id = ThreadID::fromUUID($this->uuid_factory->buildUUIDFromHexadecimalString($uuid)->unwrapOr(null));

        $message_repository =  new MessageRepositoryStub(
            new Thread(
                $thread_id,
                new Message(Role::USER, new StringContent('some content')),
                new Message(Role::ASSISTANT, new StringContent('some answer')),
            ),
        );

        $thread_repository = new ThreadRepository(
            $message_repository,
            new ThreadStorageStub($this->uuid_factory),
            $this->uuid_factory,
        );

        $thread_repository->fetchThread(
            ProjectCrossTrackerWidget::build(345, 1, 'foo', 123),
            UserTestBuilder::buildWithId(123),
            $uuid,
            Message::buildUserMessageFromString('some question'),
        );

        $stored_messages = $message_repository->fetch($thread_id);
        self::assertCount(3, $stored_messages);

        self::assertEquals(Role::USER, $stored_messages[0]->role);
        self::assertEquals('some content', $stored_messages[0]->content);

        self::assertEquals(Role::ASSISTANT, $stored_messages[1]->role);
        self::assertEquals('some answer', $stored_messages[1]->content);

        self::assertEquals(Role::USER, $stored_messages[2]->role);
        self::assertEquals('some question', $stored_messages[2]->content);
    }

    public function testAThreadIdMustBeAttachedToAWidgetAndAUser(): void
    {
        $uuid      = '019b2bf2-3cb7-736a-8979-a16b0297cc1d';
        $thread_id = ThreadID::fromUUID($this->uuid_factory->buildUUIDFromHexadecimalString($uuid)->unwrapOr(null));

        $thread_repository = new ThreadRepository(
            new MessageRepositoryStub(
                new Thread(
                    $thread_id,
                    new Message(Role::USER, new StringContent('some content')),
                    new Message(Role::ASSISTANT, new StringContent('some answer')),
                ),
            ),
            new ThreadStorageStub($this->uuid_factory)->withoutExistingThread(),
            $this->uuid_factory,
        );

        $result = $thread_repository->fetchThread(
            ProjectCrossTrackerWidget::build(345, 1, 'foo', 123),
            UserTestBuilder::buildWithId(123),
            $uuid,
            Message::buildUserMessageFromString('some question'),
        );


        self::assertTrue($result->isNothing());
    }
}
