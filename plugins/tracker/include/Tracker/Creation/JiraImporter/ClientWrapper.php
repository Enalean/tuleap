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

class ClientWrapper implements JiraClient
{
    /**
     * According to [1] the v3 API is only available on Jira Cloud with no forseen implementation on Jira Server (on prem)
     * So we stick to v2 because this code should run against on prem instances.
     *
     * [1] https://community.atlassian.com/t5/Jira-questions/When-will-Jira-Server-support-REST-API-v3/qaq-p/1303614
     */
    public const JIRA_CORE_BASE_URL = '/rest/api/2';

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
        $this->base_url = $base_url;
    }

    public static function build(JiraCredentials $jira_credentials): self
    {
        $client = HttpClientFactory::createClient(
            new AuthenticationPlugin(
                new BasicAuth($jira_credentials->getJiraUsername(), $jira_credentials->getJiraToken()->getString())
            )
        );

        $request_factory = HTTPFactoryBuilder::requestFactory();

        return new self($client, $request_factory, $jira_credentials->getJiraUrl());
    }

    /**
     * @throws JiraConnectionException|\JsonException
     */
    public function getUrl(string $url): ?array
    {
        $request = $this->factory->createRequest('GET', $this->base_url . $url);

        try {
            $response = $this->client->sendRequest($request);
            if ((int) $response->getStatusCode() !== 200) {
                throw JiraConnectionException::responseIsNotOk($request, $response);
            }
        } catch (ClientExceptionInterface $e) {
            throw JiraConnectionException::connectionToServerFailed((int) $e->getCode(), $e->getMessage(), $request);
        }

        return json_decode($response->getBody()->getContents(), true, 512, JSON_OBJECT_AS_ARRAY & JSON_THROW_ON_ERROR);
    }
}
