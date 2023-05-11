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
use Http\Message\Authentication\Bearer;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Log\LoggerInterface;
use Tuleap\Config\ConfigKey;
use Tuleap\Config\ConfigKeyCategory;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment\Attachment;

#[ConfigKeyCategory('Import from Jira')]
abstract class ClientWrapper implements JiraClient
{
    #[ConfigKey('Jira importer will record all request being made and all responses sent back by the client in this directory')]
    public const CONFIG_KEY_DEBUG_DIRECTORY = 'tracker_jira_debug_directory';

    #[ConfigKey('Jira importer will always use basic auth with given credentials')]
    public const CONFIG_KEY_FORCE_BASIC_AUTH = 'tracker_jira_force_basic_auth';

    /**
     * According to [1] the v3 API is only available on Jira Cloud with no forseen implementation on Jira Server (on prem)
     * So we stick to v2 because this code should run against on prem instances.
     *
     * [1] https://community.atlassian.com/t5/Jira-questions/When-will-Jira-Server-support-REST-API-v3/qaq-p/1303614
     */
    public const JIRA_CORE_BASE_URL = '/rest/api/2';

    public const DEBUG_MARKER_BODY = 'Body content:';

    private const DEPLOYMENT_TYPE_CLOUD = 'Cloud';
    private const DEFAULT_TIMEOUT       = 30;

    private ?string $debug_directory = null;
    private ?string $log_file        = null;

    final public function __construct(
        private readonly ClientInterface $client,
        private readonly RequestFactoryInterface $factory,
        private readonly string $base_url,
    ) {
    }

    final public function setDebugDirectory(string $debug_directory): void
    {
        $this->debug_directory = $debug_directory;
        $this->log_file        = $this->debug_directory . '/manifest.log';
        if (file_exists($this->log_file)) {
            unlink($this->log_file);
        }
    }

    /**
     * @throws \JsonException
     */
    final public static function build(JiraCredentials $jira_credentials, LoggerInterface $logger): self
    {
        $request_factory = HTTPFactoryBuilder::requestFactory();

        $client = self::getClientDependingOnServer($jira_credentials, $request_factory, $logger);

        if (\ForgeConfig::get(self::CONFIG_KEY_DEBUG_DIRECTORY) && is_dir(\ForgeConfig::get(self::CONFIG_KEY_DEBUG_DIRECTORY))) {
            $logger->debug("Set Jira client in debug mode");
            $client->setDebugDirectory(\ForgeConfig::get(self::CONFIG_KEY_DEBUG_DIRECTORY));
        }
        return $client;
    }

    private static function getClientDependingOnServer(
        JiraCredentials $jira_credentials,
        RequestFactoryInterface $request_factory,
        LoggerInterface $logger,
    ): JiraServer9Client|JiraCloudClient|JiraServer7and8Client {
        $client_without_authentication = HttpClientFactory::createClientWithCustomTimeout(self::DEFAULT_TIMEOUT);
        $server_info_uri               = $jira_credentials->getJiraUrl() . self::JIRA_CORE_BASE_URL . '/serverInfo';
        $logger->debug("Do we talk to JiraCloud or JiraServer ?");
        $logger->debug("GET $server_info_uri");
        $server_info_response = $client_without_authentication->sendRequest($request_factory->createRequest('GET', $server_info_uri));
        $logger->debug("Response: " . $server_info_response->getStatusCode());
        $server_info = json_decode($server_info_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        if (isset($server_info['deploymentType']) && $server_info['deploymentType'] === self::DEPLOYMENT_TYPE_CLOUD) {
            $logger->info("Instantiate JiraCloudClient");
            return new JiraCloudClient(
                self::getClientWithBasicAuth($jira_credentials),
                $request_factory,
                $jira_credentials->getJiraUrl(),
            );
        }

        $logger->info("Instantiate JiraServerClient");

        $jira_server_major_version = self::getJiraServerMajorVersion($server_info);
        if ($jira_server_major_version < 9) {
            return new JiraServer7and8Client(
                self::getClientWithBearerAuth($jira_credentials),
                $request_factory,
                $jira_credentials->getJiraUrl(),
            );
        }

        return new JiraServer9Client(
            self::getClientWithBearerAuth($jira_credentials),
            $request_factory,
            $jira_credentials->getJiraUrl(),
        );
    }

    private static function getJiraServerMajorVersion(array $server_info): int
    {
        if (
            isset($server_info['versionNumbers']) &&
            is_array($server_info['versionNumbers']) &&
            isset($server_info['versionNumbers'][0])
        ) {
            return $server_info['versionNumbers'][0];
        }

        return 0;
    }

    private static function getClientWithBearerAuth(JiraCredentials $jira_credentials): ClientInterface
    {
        if (\ForgeConfig::get(self::CONFIG_KEY_FORCE_BASIC_AUTH) === '1') {
            return self::getClientWithBasicAuth($jira_credentials);
        }
        return HttpClientFactory::createClientWithCustomTimeout(
            self::DEFAULT_TIMEOUT,
            new AuthenticationPlugin(
                new Bearer($jira_credentials->getJiraToken()->getString()),
            ),
        );
    }

    private static function getClientWithBasicAuth(JiraCredentials $jira_credentials): ClientInterface
    {
        return HttpClientFactory::createClientWithCustomTimeout(
            self::DEFAULT_TIMEOUT,
            new AuthenticationPlugin(
                new BasicAuth(
                    $jira_credentials->getJiraUsername(),
                    $jira_credentials->getJiraToken()->getString(),
                )
            ),
        );
    }

    /**
     * @throws JiraConnectionException|\JsonException
     */
    final public function getUrl(string $url): ?array
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
            foreach ($response->getHeaders() as $header_name => $header_values) {
                file_put_contents($response_debug_path . '_headers', "$header_name: " . implode(', ', $header_values) . PHP_EOL, FILE_APPEND);
            }
            file_put_contents($response_debug_path, $body_contents);
        }

        return json_decode($body_contents, true, 512, JSON_OBJECT_AS_ARRAY & JSON_THROW_ON_ERROR);
    }

    final public function getAttachmentContents(Attachment $attachment): string
    {
        $request  = $this->factory->createRequest('GET', $attachment->getContentUrl());
        $response = $this->client->sendRequest($request);
        return $response->getBody()->getContents();
    }

    abstract public function isJiraCloud(): bool;

    abstract public function isJiraServer9(): bool;
}
