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

use CuyZ\Valinor\Mapper\MappingError;
use CuyZ\Valinor\Mapper\Source\JsonSource;
use Tuleap\AI\Mistral\CompletionResponse;
use Tuleap\AI\Mistral\MistralConnector;
use Tuleap\AI\Requestor\AIRequestorEntity;
use Tuleap\AI\Requestor\EndUserAIRequestor;
use Tuleap\AICrossTracker\REST\v1\HelperRepresentation;
use Tuleap\AICrossTracker\REST\v1\HelperRepresentationWithoutThreadId;
use Tuleap\Mapper\ValinorMapperBuilderFactory;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\User\CurrentUserWithLoggedInInformation;

final readonly class CompletionSender
{
    public function __construct(private MistralConnector $mistral_connector, private MessageRepository $message_repository)
    {
    }

    /**
     * @psalm-return Ok<HelperRepresentation>|Err<Fault>
     */
    public function sendMessages(CurrentUserWithLoggedInInformation $current_user_with_logged_in_information, Assistant $assistant, Thread $thread): Ok|Err
    {
        $message_repository = $this->message_repository;
        return EndUserAIRequestor::fromCurrentUser($current_user_with_logged_in_information)
            ->andThen(
                fn (AIRequestorEntity $requestor) => $this->mistral_connector->sendCompletion(
                    $requestor,
                    $assistant->getCompletion($current_user_with_logged_in_information->user, $thread->messages),
                    'crosstracker'
                )
            )
            ->andThen(
                /**
                 * @psalm-return Ok<string>|Err<Fault>
                 */
                static function (CompletionResponse $response) use ($message_repository, $thread): Ok|Err {
                    if (! isset($response->choices[0]->message->content)) {
                        return Result::err(Fault::fromMessage('No choice provided in the response'));
                    }
                    $message_repository->storeWithTokenConsumption($thread->id, $response->choices[0]->message->toGenericMessage(), $response->usage);
                    return Result::ok((string) $response->choices[0]->message->content);
                }
            )
            ->andThen(
                /**
                 * @psalm-return Ok<HelperRepresentation>|Err<Fault>
                 */
                static function (string $selected_response) use ($thread): Ok|Err {
                    $mapper = ValinorMapperBuilderFactory::mapperBuilder()->mapper();
                    try {
                        $mapped = $mapper->map(HelperRepresentationWithoutThreadId::class, new JsonSource($selected_response));
                        \assert($mapped instanceof HelperRepresentationWithoutThreadId);
                        return Result::ok(
                            new HelperRepresentation(
                                $thread->id->uuid->toString(),
                                $mapped->title,
                                $mapped->tql_query,
                                $mapped->explanations,
                            )
                        );
                    } catch (MappingError $e) {
                        return Result::err(Fault::fromThrowable($e));
                    }
                }
            );
    }
}
