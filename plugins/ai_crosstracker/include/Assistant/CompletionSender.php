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
use Tuleap\AI\Mistral\Message;
use Tuleap\AI\Mistral\MistralConnector;
use Tuleap\AI\Requestor\AIRequestorEntity;
use Tuleap\AI\Requestor\EndUserAIRequestor;
use Tuleap\AICrossTracker\REST\v1\HelperRepresentation;
use Tuleap\AICrossTracker\REST\v1\MessageRepresentation;
use Tuleap\Mapper\ValinorMapperBuilderFactory;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\User\CurrentUserWithLoggedInInformation;

final readonly class CompletionSender
{
    public function __construct(private MistralConnector $mistral_connector)
    {
    }

    /**
     * @psalm-return Ok<HelperRepresentation>|Err<Fault>
     */
    public function sendMessages(CurrentUserWithLoggedInInformation $current_user_with_logged_in_information, Assistant $assistant, MessageRepresentation ...$message_representation): Ok|Err
    {
        $user_messages = array_map(static fn (MessageRepresentation $message): Message => $message->toMistralMessage(), $message_representation);

        return EndUserAIRequestor::fromCurrentUser($current_user_with_logged_in_information)
            ->andThen(
                fn (AIRequestorEntity $requestor) => $this->mistral_connector->sendCompletion(
                    $requestor,
                    $assistant->getCompletion($current_user_with_logged_in_information->user, $user_messages),
                    'crosstracker'
                )
            )
            ->andThen(
            /**
             * @psalm-return Ok<string>|Err<Fault>
             */
                static function (CompletionResponse $response): Ok|Err {
                    if (! isset($response->choices[0]->message->content)) {
                        return Result::err(Fault::fromMessage('No choice provided in the response'));
                    }
                    return Result::ok((string) $response->choices[0]->message->content);
                }
            )
            ->andThen(
                /**
                 * @psalm-return Ok<HelperRepresentation>|Err<Fault>
                 */
                static function (string $selected_response): Ok|Err {
                    $mapper = ValinorMapperBuilderFactory::mapperBuilder()->mapper();
                    try {
                        return Result::ok($mapper->map(HelperRepresentation::class, new JsonSource($selected_response)));
                    } catch (MappingError $e) {
                        return Result::err(Fault::fromThrowable($e));
                    }
                }
            );
    }
}
