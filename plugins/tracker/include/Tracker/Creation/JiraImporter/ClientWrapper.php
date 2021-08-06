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

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Client\JiraHTTPClientBuilder;

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
    /**
     * @var ?string
     */
    private $debug_directory;
    /**
     * @var ?string
     */
    private $log_file;

    public function __construct(ClientInterface $client, RequestFactoryInterface $factory, string $base_url)
    {
        $this->client   = $client;
        $this->factory  = $factory;
        $this->base_url = $base_url;
    }

    public function setDebugDirectory(string $debug_directory): void
    {
        $this->debug_directory = $debug_directory;
        $this->log_file        = $this->debug_directory . '/manifest.log';
        if (file_exists($this->log_file)) {
            unlink($this->log_file);
        }
    }

    public static function build(JiraCredentials $jira_credentials): self
    {
        $client = JiraHTTPClientBuilder::buildHTTPClientFromCredentials(
            $jira_credentials
        );

        $request_factory = HTTPFactoryBuilder::requestFactory();

        return new self($client, $request_factory, $jira_credentials->getJiraUrl());
    }

    /**
     * @throws JiraConnectionException|\JsonException
     */
    public function getUrl(string $url): ?array
    {
        $request_url         = $this->base_url . $url;
        $response_debug_path = null;
        $sha1                = null;

        if ($this->debug_directory && $this->log_file) {
            $sha1                = sha1($request_url);
            $response_debug_path = $this->debug_directory . '/' . $sha1;
            file_put_contents($this->log_file, $request_url . ' ' . $sha1, FILE_APPEND);
        }
        $request = $this->factory->createRequest('GET', $request_url);

        try {
            $response = $this->client->sendRequest($request);
            if ($this->debug_directory && $sha1 && $this->log_file) {
                file_put_contents($this->log_file, ' ' . $response->getStatusCode() . PHP_EOL, FILE_APPEND);
            }
            if ($response->getStatusCode() !== 200) {
                throw JiraConnectionException::responseIsNotOk($request, $response, $response_debug_path);
            }
        } catch (ClientExceptionInterface $e) {
            throw JiraConnectionException::connectionToServerFailed((int) $e->getCode(), $e->getMessage(), $request);
        }

        $body_contents = $response->getBody()->getContents();
        if ($response_debug_path) {
            file_put_contents($response_debug_path, "Headers: " . PHP_EOL, FILE_APPEND);
            foreach ($response->getHeaders() as $header_name => $header_values) {
                file_put_contents($response_debug_path, "$header_name: " . implode(', ', $header_values) . PHP_EOL, FILE_APPEND);
            }
            file_put_contents($response_debug_path, PHP_EOL . "Body content: " . PHP_EOL, FILE_APPEND);
            file_put_contents($response_debug_path, $body_contents, FILE_APPEND);
        }

        return json_decode($body_contents, true, 512, JSON_OBJECT_AS_ARRAY & JSON_THROW_ON_ERROR);
    }
}
