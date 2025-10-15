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

namespace Tuleap\AI\Mistral;

use ForgeConfig;
use Http\Message\RequestMatcher\CallbackRequestMatcher;
use Http\Message\RequestMatcher\RequestMatcher;
use Http\Mock\Client;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Psr\Http\Message\RequestInterface;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Ok;
use Tuleap\Test\PHPUnit\TestCase;

#[DisableReturnValueGenerationForTestDoubles]
final class MistralConnectorLiveTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testNoAPIKeyIsReportedAsAnError(): void
    {
        ForgeConfig::set(MistralConnector::CONFIG_API_KEY, '');
        $client    = new Client();
        $connector = new MistralConnectorLive($client);

        $result = $connector->testConnection();
        self::assertInstanceOf(Err::class, $result);
        self::assertInstanceOf(NoKeyFault::class, $result->error);
    }

    public function testUnauthorizedKeyIsReportedAsAnError(): void
    {
        $this->setEncryptedValue(MistralConnector::CONFIG_API_KEY, 'whatever');
        $client    = new Client();
        $connector = new MistralConnectorLive($client);

        $client->on(
            new RequestMatcher('^/v1/models$', null, 'GET'),
            function () {
                return HTTPFactoryBuilder::responseFactory()->createResponse(401);
            }
        );

        $result = $connector->testConnection();
        self::assertInstanceOf(Err::class, $result);
        self::assertStringContainsString('authentication failure', (string) $result->error);
    }

    public function testAuthorizedKeyIsASuccess(): void
    {
        $this->setEncryptedValue(MistralConnector::CONFIG_API_KEY, 'whatever');
        $client    = new Client();
        $connector = new MistralConnectorLive($client);

        $client->on(
            new RequestMatcher('^/v1/models$', null, 'GET'),
            function () {
                return HTTPFactoryBuilder::responseFactory()->createResponse(200);
            }
        );

        $result = $connector->testConnection();
        self::assertInstanceOf(Ok::class, $result);
    }

    public function testCompletionExpectedPayload(): void
    {
        $this->setEncryptedValue(MistralConnector::CONFIG_API_KEY, 'whatever');
        $client    = new Client();
        $connector = new MistralConnectorLive($client);

        $client->on(
            new CallbackRequestMatcher(
                function (RequestInterface $request): bool {
                    return $request->getMethod() === 'POST' &&
                        $request->getUri()->getPath() === '/v1/chat/completions' &&
                        $request->getBody()->getContents() === '{"model":"mistral-medium-2508","messages":[{"role":"system","content":[{"type":"text","text":"some def"},{"type":"text","text":"another def"}]},{"role":"user","content":"question"}]}';
                }
            ),
            function () {
                return HTTPFactoryBuilder::responseFactory()->createResponse(200)
                    ->withBody(
                        HTTPFactoryBuilder::streamFactory()->createStream((string) file_get_contents(__DIR__ . '/payload_1.json'))
                    );
            }
        );

        $result = $connector->sendCompletion(
            new Completion(
                Model::MEDIUM_2508,
                new Message(
                    Role::SYSTEM,
                    new ChunkContent(
                        new TextChunk('some def'),
                        new TextChunk('another def')
                    ),
                ),
                new Message(
                    Role::USER,
                    new StringContent('question'),
                ),
            )
        );

        self::assertInstanceOf(Ok::class, $result);
    }

    public function testCompletionWithWorkingPayload(): void
    {
        $this->setEncryptedValue(MistralConnector::CONFIG_API_KEY, 'whatever');
        $client    = new Client();
        $connector = new MistralConnectorLive($client);

        $client->on(
            new RequestMatcher('^/v1/chat/completions$', null, 'POST'),
            function () {
                return HTTPFactoryBuilder::responseFactory()->createResponse(200)
                    ->withBody(
                        HTTPFactoryBuilder::streamFactory()->createStream((string) file_get_contents(__DIR__ . '/payload_1.json'))
                    );
            }
        );

        $result = $connector->sendCompletion(new Completion(Model::MEDIUM_2508));

        self::assertInstanceOf(Ok::class, $result);
        self::assertInstanceOf(StringContent::class, $result->value->choices[0]->message->content);
        self::assertStringContainsString('toutes les exigences (user stories) ouvertes', (string) $result->value->choices[0]->message->content);
    }

    public function testCompletionWithInvalidKey(): void
    {
        $this->setEncryptedValue(MistralConnector::CONFIG_API_KEY, 'whatever');
        $client    = new Client();
        $connector = new MistralConnectorLive($client);

        $client->on(
            new RequestMatcher('^/v1/chat/completions$', null, 'POST'),
            function () {
                return HTTPFactoryBuilder::responseFactory()->createResponse(401);
            }
        );

        $result = $connector->sendCompletion(new Completion(Model::MEDIUM_2508));

        self::assertInstanceOf(Err::class, $result);
        self::assertStringContainsString('authentication failure', (string) $result->error);
    }

    public function testCompletionReturnsAnErrorCode(): void
    {
        $this->setEncryptedValue(MistralConnector::CONFIG_API_KEY, 'whatever');
        $client    = new Client();
        $connector = new MistralConnectorLive($client);

        $client->on(
            new RequestMatcher('^/v1/chat/completions$', null, 'POST'),
            function () {
                return HTTPFactoryBuilder::responseFactory()->createResponse(422);
            }
        );

        $result = $connector->sendCompletion(new Completion(Model::MEDIUM_2508));

        self::assertInstanceOf(Err::class, $result);
    }

    public function testCompletionHasAnUnexpectedJson(): void
    {
        $this->setEncryptedValue(MistralConnector::CONFIG_API_KEY, 'whatever');
        $client    = new Client();
        $connector = new MistralConnectorLive($client);

        $client->on(
            new RequestMatcher('^/v1/chat/completions$', null, 'POST'),
            function () {
                return HTTPFactoryBuilder::responseFactory()
                    ->createResponse(200)
                    ->withBody(
                        HTTPFactoryBuilder::streamFactory()->createStream('{"foo": "bar"}')
                    );
            }
        );

        $result = $connector->sendCompletion(new Completion(Model::MEDIUM_2508));

        self::assertInstanceOf(Err::class, $result);
        self::assertInstanceOf(UnexpectedCompletionResponseFault::class, $result->error);
    }
}
