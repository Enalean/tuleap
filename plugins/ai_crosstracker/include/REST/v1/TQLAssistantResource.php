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

namespace Tuleap\AICrossTracker\REST\v1;

use Luracast\Restler\RestException;
use Tuleap\AI\Mistral\ChunkContent;
use Tuleap\AI\Mistral\Completion;
use Tuleap\AI\Mistral\CompletionResponse;
use Tuleap\AI\Mistral\Message;
use Tuleap\AI\Mistral\MistralConnectorLive;
use Tuleap\AI\Mistral\Model;
use Tuleap\AI\Mistral\Role;
use Tuleap\AI\Mistral\StringContent;
use Tuleap\AI\Mistral\TextChunk;
use Tuleap\Http\HttpClientFactory;
use Tuleap\NeverThrow\Fault;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;

final class TQLAssistantResource extends AuthenticatedResource
{
    public const string ROUTE = 'crosstracker_assistant';

    /**
     * @url OPTIONS
     */
    public function optionsHelper(): void
    {
        Header::allowOptionsPost();
    }

    /**
     * (EXPERIMENTAL) Get help on TQL
     *
     * @url    POST
     * @access hybrid
     *
     * @status 200
     * @throws RestException
     */
    public function post(): HelperRepresentation
    {
        $this->checkAccess();

        $tql_doc = file_get_contents(__DIR__ . '/tql.html');
        if ($tql_doc === false) {
            throw new RestException(500, 'TQL doc error');
        }

        $completion        = new Completion(
            Model::SMALL_LATEST,
            new Message(
                Role::SYSTEM,
                new ChunkContent(
                    new TextChunk('You are an assistant that helps to generate TQL queries for users. TQL is a pseudo programming language, described in this documentation.'),
                    new TextChunk('### TQL documentation' . PHP_EOL . $tql_doc),
                ),
            ),
            new Message(
                Role::USER,
                new StringContent('je veux toutes les exigences ouvertes non couvertes par des tests')
            ),
        );
        $mistral_connector = new MistralConnectorLive(HttpClientFactory::createClientWithCustomTimeout(30));
        return $mistral_connector->sendCompletion($completion)->match(
            static fn (CompletionResponse $response) => new HelperRepresentation((string) $response->choices[0]->message->content),
            static fn (Fault $fault) => throw new RestException(400, (string) $fault)
        );
    }
}
