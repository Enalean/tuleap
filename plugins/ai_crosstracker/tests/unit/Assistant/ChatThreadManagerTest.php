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
use Tuleap\AI\Mistral\StringContent;
use Tuleap\AI\Mistral\TokenUsage;
use Tuleap\AICrossTracker\Stub\MessageRepositoryStub;
use Tuleap\AICrossTracker\Stub\MistralConnectorStub;
use Tuleap\AICrossTracker\Stub\ThreadStorageStub;
use Tuleap\CrossTracker\Widget\UserCrossTrackerWidget;
use Tuleap\DB\DatabaseUUIDFactory;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\NeverThrow\Ok;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProjectByIDFactoryStub;
use Tuleap\Tracker\Test\Stub\RetrieveMultipleTrackersStub;
use Tuleap\Tracker\Test\Stub\RetrieveUsedFieldsStub;
use Tuleap\User\CurrentUserWithLoggedInInformation;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ChatThreadManagerTest extends TestCase
{
    private DatabaseUUIDFactory $uuid_factory;

    #[Override]
    protected function setUp(): void
    {
        $this->uuid_factory = new DatabaseUUIDV7Factory();
    }

    public function testItSendsCompletionAndReturnsHelperRepresentation(): void
    {
        $assistant_payload = json_encode([
            'title'       => 'List my items',
            'tql_query'   => 'TQL QUERY',
            'explanations' => 'This query lists items in your projects.',
        ], JSON_THROW_ON_ERROR);

        $manager = new ChatThreadManager(
            $this->uuid_factory,
            new MessageRepositoryStub(),
            new ThreadStorageStub($this->uuid_factory),
            ProjectByIDFactoryStub::buildWithoutProject(),
            RetrieveMultipleTrackersStub::withoutTrackers(),
            RetrieveUsedFieldsStub::withNoFields(),
            new MistralConnectorStub()->withResponse(
                CompletionResponse::fromChoicesAndTokenUsage(
                    TokenUsage::fromValues(5, 42, 37),
                    CompletionResponseChoice::fromAssistantMessage(
                        AssistantMessage::fromStringContent(
                            new StringContent($assistant_payload)
                        )
                    )
                )
            ),
        );

        $result = $manager->handleConversation(
            CurrentUserWithLoggedInInformation::fromLoggedInUser(
                UserTestBuilder::anActiveUser()->withId(123)->build()
            ),
            UserCrossTrackerWidget::build(1, 1, 'user', 101),
            Message::buildUserMessageFromString('please list my items')
        );

        self::assertInstanceOf(Ok::class, $result);
        $payload = $result->unwrapOr(null);
        self::assertInstanceOf(\Tuleap\AICrossTracker\REST\v1\HelperRepresentation::class, $payload);
        self::assertSame('List my items', $payload->title);
        self::assertSame('TQL QUERY', $payload->tql_query);
        self::assertSame('This query lists items in your projects.', $payload->explanations);
    }
}
