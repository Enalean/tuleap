<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\Tracker\Creation\JiraImporter;

use Http\Client\Common\Plugin\AuthenticationPlugin;
use Http\Message\Authentication\BasicAuth;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\HTTPFactoryBuilder;

class ClientWrapper
{
    /**
     * @var ClientInterface
     */
    private $client;
    /**
     * @var RequestFactoryInterface
     */
    private $factory;
    /**
     * @var string
     */
    private $base_url;

    public function __construct(ClientInterface $client, RequestFactoryInterface $factory, string $base_url)
    {
        $this->client   = $client;
        $this->factory  = $factory;
        $this->base_url = $base_url . "/rest/api/latest/";
    }

    public static function build(string $base_url, string $jira_username, string $jira_token): self
    {
        $client = HttpClientFactory::createClient(
            new AuthenticationPlugin(
                new BasicAuth($jira_username, $jira_token)
            )
        );

        $request_factory = HTTPFactoryBuilder::requestFactory();

        return new self($client, $request_factory, $base_url);
    }

    /**
     * @throws JiraConnectionException
     */
    public function getUrl(string $url): ?array
    {
        $request = $this->factory->createRequest('GET', $this->base_url . $url);

        try {
            $response = $this->client->sendRequest($request);
            if ((int) $response->getStatusCode() !== 200) {
                throw JiraConnectionException::connectionToServerFailed(
                    (int) $response->getStatusCode(),
                    $response->getReasonPhrase()
                );
            }
        } catch (ClientExceptionInterface $e) {
            throw JiraConnectionException::connectionToServerFailed((int) $e->getCode(), $e->getMessage());
        }

        return json_decode($response->getBody()->getContents(), true, 512, JSON_OBJECT_AS_ARRAY);
    }
}
