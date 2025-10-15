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

use CuyZ\Valinor\Mapper\MappingError;
use CuyZ\Valinor\Mapper\Source\JsonSource;
use ForgeConfig;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Mapper\ValinorMapperBuilderFactory;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final readonly class MistralConnectorLive implements MistralConnector
{
    public function __construct(private ClientInterface $client)
    {
    }

    /**
     * @return Ok<null>|Err<Fault>
     */
    #[\Override]
    public function testConnection(): Ok|Err
    {
        try {
            if (ForgeConfig::get(self::CONFIG_API_KEY) === '') {
                return Result::err(NoKeyFault::build());
            }

            $request  = HTTPFactoryBuilder::requestFactory()
                ->createRequest('GET', 'https://api.mistral.ai/v1/models')
                ->withHeader('Authorization', sprintf('Bearer %s', (string) ForgeConfig::getSecretAsClearText(self::CONFIG_API_KEY)))
                ->withHeader('Accept', 'application/json');
            $response = $this->client->sendRequest($request);
            if ($response->getStatusCode() === 200) {
                return Result::ok(null);
            }
            if ($response->getStatusCode() === 401) {
                return Result::err(AuthenticationFailure::build());
            }
            return Result::err(Fault::fromMessage(sprintf('%s (%d)', $response->getReasonPhrase(), $response->getStatusCode())));
        } catch (ClientExceptionInterface $client_exception) {
            return Result::err(Fault::fromThrowable($client_exception));
        } catch (\Exception) {
            return Result::err(Fault::fromMessage(dgettext('tuleap-ai', 'An error occurred while trying to access to API key in configuration.')));
        }
    }

    #[\Override]
    public function sendCompletion(Completion $completion): Ok|Err
    {
        try {
            if (ForgeConfig::get(self::CONFIG_API_KEY) === '') {
                return Result::err(NoKeyFault::build());
            }

            $request  = HTTPFactoryBuilder::requestFactory()
                ->createRequest('POST', 'https://api.mistral.ai/v1/chat/completions')
                ->withHeader('Authorization', sprintf('Bearer %s', (string) ForgeConfig::getSecretAsClearText(self::CONFIG_API_KEY)))
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('Accept', 'application/json')
                ->withBody(HTTPFactoryBuilder::streamFactory()->createStream(\json_encode(
                    $completion,
                    JSON_THROW_ON_ERROR,
                )));
            $response = $this->client->sendRequest($request);
            if ($response->getStatusCode() === 401) {
                return Result::err(AuthenticationFailure::build());
            }
            if ($response->getStatusCode() !== 200) {
                return Result::err(Fault::fromMessage(sprintf('%s (%d)', $response->getReasonPhrase(), $response->getStatusCode())));
            }
            $mapper = ValinorMapperBuilderFactory::mapperBuilder()->allowSuperfluousKeys()->allowUndefinedValues()->mapper();
            return Result::ok($mapper->map(CompletionResponse::class, new JsonSource($response->getBody()->getContents())));
        } catch (ClientExceptionInterface $client_exception) {
            return Result::err(Fault::fromThrowable($client_exception));
        } catch (MappingError $error) {
            return Result::err(UnexpectedCompletionResponseFault::build($error));
        } catch (\Exception) {
            return Result::err(Fault::fromMessage(dgettext('tuleap-ai', 'An error occurred while trying to access to API key in configuration.')));
        }
    }
}
