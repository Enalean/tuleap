<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\API;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

class ClientWrapper
{
    /**
     * @var RequestFactoryInterface
     */
    private $factory;
    /**
     * @var StreamFactoryInterface
     */
    private $stream_factory;
    /**
     * @var GitlabHTTPClientFactory
     */
    private $gitlab_client_factory;

    public function __construct(
        RequestFactoryInterface $factory,
        StreamFactoryInterface $stream_factory,
        GitlabHTTPClientFactory $gitlab_client_factory,
    ) {
        $this->factory               = $factory;
        $this->stream_factory        = $stream_factory;
        $this->gitlab_client_factory = $gitlab_client_factory;
    }

    /**
     * @throws GitlabRequestException
     * @throws GitlabResponseAPIException
     */
    public function getUrl(Credentials $gitlab_credentials, string $url): ?array
    {
        $client = $this->gitlab_client_factory->buildHTTPClient($gitlab_credentials);

        $request = $this->factory->createRequest('GET', $gitlab_credentials->getGitlabServerUrl() . "/api/v4" . $url);

        try {
            $response = $client->sendRequest($request);
            if ((int) $response->getStatusCode() !== 200) {
                self::handleInvalidResponse($response);
            }
        } catch (ClientExceptionInterface $exception) {
            throw new GitlabRequestException(
                500,
                $exception->getMessage(),
                $exception
            );
        }

        $json =  json_decode($response->getBody()->getContents(), true, 512, JSON_OBJECT_AS_ARRAY);

        if ($json !== null && ! is_array($json)) {
            throw new GitlabResponseAPIException("The query is not in error but the json content is not an array. This is not expected.");
        }

        return $json;
    }

    /**
     * @throws GitlabRequestException
     * @throws GitlabResponseAPIException
     */
    public function postUrl(Credentials $gitlab_credentials, string $url, array $request_data): ?array
    {
        $client = $this->gitlab_client_factory->buildHTTPClient($gitlab_credentials);

        $request = $this->factory->createRequest('POST', $gitlab_credentials->getGitlabServerUrl() . "/api/v4" . $url)
            ->withHeader('Content-Type', 'application/json')
            ->withBody(
                $this->stream_factory->createStream(
                    json_encode($request_data)
                )
            );

        try {
            $response = $client->sendRequest($request);
            if ((int) $response->getStatusCode() !== 201) {
                self::handleInvalidResponse($response);
            }
        } catch (ClientExceptionInterface $exception) {
            throw new GitlabRequestException(
                500,
                $exception->getMessage(),
                $exception
            );
        }

        $json = json_decode($response->getBody()->getContents(), true, 512, JSON_OBJECT_AS_ARRAY);

        if ($json !== null && ! is_array($json)) {
            throw new GitlabResponseAPIException("The query is not in error but the json content is not an array. This is not expected.");
        }

        return $json;
    }

    /**
     * @throws GitlabRequestException
     */
    public function deleteUrl(Credentials $gitlab_credentials, string $url): void
    {
        $client = $this->gitlab_client_factory->buildHTTPClient($gitlab_credentials);

        $request = $this->factory
            ->createRequest(
                'DELETE',
                $gitlab_credentials->getGitlabServerUrl() . "/api/v4" . $url
            )
            ->withHeader('Content-Type', 'application/json');

        try {
            $response = $client->sendRequest($request);
            if ($response->getStatusCode() < 200 || 300 <= $response->getStatusCode()) {
                self::handleInvalidResponse($response);
            }
        } catch (ClientExceptionInterface $exception) {
            throw new GitlabRequestException(
                500,
                $exception->getMessage(),
                $exception
            );
        }
    }

    /**
     * @psalm-return never-return
     * @throws GitlabRequestException
     */
    private static function handleInvalidResponse(ResponseInterface $response): void
    {
        try {
            $json_response = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
            if (isset($json_response['message']) && is_string($json_response['message'])) {
                $reason = sprintf('%s (%s)', $json_response['message'], $response->getReasonPhrase());
            } else {
                $reason = $response->getReasonPhrase();
            }
        } catch (\JsonException $e) {
            $reason = $response->getReasonPhrase();
        }

        throw new GitlabRequestException($response->getStatusCode(), $reason);
    }
}
