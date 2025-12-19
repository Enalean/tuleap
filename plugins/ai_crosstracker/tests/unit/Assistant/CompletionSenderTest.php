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
use Tuleap\AI\Mistral\AssistantMessage;
use Tuleap\AI\Mistral\CompletionResponse;
use Tuleap\AI\Mistral\CompletionResponseChoice;
use Tuleap\AI\Mistral\Message;
use Tuleap\AI\Mistral\Role;
use Tuleap\AI\Mistral\StringContent;
use Tuleap\AI\Mistral\TokenUsage;
use Tuleap\AICrossTracker\REST\v1\HelperRepresentation;
use Tuleap\AICrossTracker\Stub\AssistantStub;
use Tuleap\AICrossTracker\Stub\MessageRepositoryStub;
use Tuleap\AICrossTracker\Stub\MistralConnectorStub;
use Tuleap\DB\DatabaseUUIDFactory;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\NeverThrow\Ok;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\User\CurrentUserWithLoggedInInformation;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CompletionSenderTest extends TestCase
{
    private DatabaseUUIDFactory $uuid_factory;

    #[Override]
    protected function setUp(): void
    {
        $this->uuid_factory = new DatabaseUUIDV7Factory();
    }

    public function testReceiveAEndUserMessageSendItToMistralAndGiveTheResponseBack(): void
    {
        $json = json_encode(['title' => 'foo', 'tql_query' => 'SELECT ...', 'explanations' => 'bar']);
        assert($json !== false);
        $completion_response = CompletionResponse::fromChoicesAndTokenUsage(
            TokenUsage::fromFakeValues(),
            CompletionResponseChoice::fromAssistantMessage(
                AssistantMessage::fromStringContent(
                    new StringContent($json)
                )
            )
        );

        $completion_sender = new CompletionSender(
            new MistralConnectorStub()->withResponse($completion_response),
            new MessageRepositoryStub(),
        );

        $result = $completion_sender->sendMessages(
            CurrentUserWithLoggedInInformation::fromLoggedInUser(UserTestBuilder::anActiveUser()->build()),
            new AssistantStub(),
            new Thread(
                ThreadID::fromUUID($this->uuid_factory->buildUUIDFromBytesData($this->uuid_factory->buildUUIDBytes())),
                new Message(Role::USER, new StringContent('some content'))
            ),
        );

        self::assertInstanceOf(Ok::class, $result);
        self::assertInstanceOf(HelperRepresentation::class, $result->value);
        self::assertEquals('foo', $result->value->title);
        self::assertEquals('SELECT ...', $result->value->tql_query);
        self::assertEquals('bar', $result->value->explanations);
    }

    public function testItSendsMessageToLLMWithPrePrompt(): void
    {
        $mistral_stub = new MistralConnectorStub()->withResponse(
            CompletionResponse::fromChoicesAndTokenUsage(
                TokenUsage::fromFakeValues(),
                CompletionResponseChoice::fromAssistantMessage(
                    AssistantMessage::fromFakeContent()
                )
            )
        );

        $completion_sender = new CompletionSender(
            $mistral_stub,
            new MessageRepositoryStub(),
        );

        $completion_sender->sendMessages(
            CurrentUserWithLoggedInInformation::fromLoggedInUser(UserTestBuilder::anActiveUser()->build()),
            new AssistantStub(),
            new Thread(
                ThreadID::fromUUID($this->uuid_factory->buildUUIDFromBytesData($this->uuid_factory->buildUUIDBytes())),
                new Message(Role::USER, new StringContent('some content'))
            ),
        );

        self::assertCount(2, $mistral_stub->query->messages);
        self::assertEquals(Role::SYSTEM, $mistral_stub->query->messages[0]->role);
        self::assertEquals(Role::USER, $mistral_stub->query->messages[1]->role);
        self::assertEquals('some content', $mistral_stub->query->messages[1]->content);
    }

    public function testItSendThreadOfMessagesToLLMWithPrePrompt(): void
    {
        $mistral_stub = new MistralConnectorStub()->withResponse(
            CompletionResponse::fromChoicesAndTokenUsage(
                TokenUsage::fromFakeValues(),
                CompletionResponseChoice::fromAssistantMessage(
                    AssistantMessage::fromFakeContent()
                )
            )
        );

        $completion_sender = new CompletionSender(
            $mistral_stub,
            new MessageRepositoryStub(),
        );

        $completion_sender->sendMessages(
            CurrentUserWithLoggedInInformation::fromLoggedInUser(UserTestBuilder::anActiveUser()->build()),
            new AssistantStub(),
            new Thread(
                ThreadID::fromUUID($this->uuid_factory->buildUUIDFromBytesData($this->uuid_factory->buildUUIDBytes())),
                new Message(Role::USER, new StringContent('some content')),
                new Message(Role::ASSISTANT, new StringContent('some answer')),
                new Message(Role::USER, new StringContent('some question')),
            ),
        );

        self::assertCount(4, $mistral_stub->query->messages);
        self::assertEquals(Role::SYSTEM, $mistral_stub->query->messages[0]->role);

        self::assertEquals(Role::USER, $mistral_stub->query->messages[1]->role);
        self::assertEquals('some content', $mistral_stub->query->messages[1]->content);

        self::assertEquals(Role::ASSISTANT, $mistral_stub->query->messages[2]->role);
        self::assertEquals('some answer', $mistral_stub->query->messages[2]->content);

        self::assertEquals(Role::USER, $mistral_stub->query->messages[3]->role);
        self::assertEquals('some question', $mistral_stub->query->messages[3]->content);
    }

    public function testItStoresLLMResponse(): void
    {
        $thread_id = ThreadID::fromUUID($this->uuid_factory->buildUUIDFromBytesData($this->uuid_factory->buildUUIDBytes()));

        $json = json_encode(['title' => 'foo', 'tql_query' => 'SELECT ...', 'explanations' => 'bar']);
        assert($json !== false);
        $completion_response = CompletionResponse::fromChoicesAndTokenUsage(
            TokenUsage::fromValues(1500, 2000, 500),
            CompletionResponseChoice::fromAssistantMessage(
                AssistantMessage::fromStringContent(
                    new StringContent($json)
                )
            )
        );

        $message_repository = new MessageRepositoryStub();

        $completion_sender = new CompletionSender(
            new MistralConnectorStub()->withResponse($completion_response),
            $message_repository,
        );

        $completion_sender->sendMessages(
            CurrentUserWithLoggedInInformation::fromLoggedInUser(UserTestBuilder::anActiveUser()->build()),
            new AssistantStub(),
            new Thread(
                $thread_id,
                new Message(Role::USER, new StringContent('some content')),
                new Message(Role::ASSISTANT, new StringContent('some answer')),
                new Message(Role::USER, new StringContent('some question')),
            ),
        );

        $new_messages = $message_repository->fetch($thread_id);

        self::assertCount(1, $new_messages);
        self::assertEquals(Role::ASSISTANT, $new_messages[0]->role);
        self::assertEquals($json, $new_messages[0]->content);

        self::assertEquals(1500, $message_repository->token_usage->prompt_tokens);
        self::assertEquals(500, $message_repository->token_usage->completion_tokens);
        self::assertEquals(2000, $message_repository->token_usage->total_tokens);
    }
}
