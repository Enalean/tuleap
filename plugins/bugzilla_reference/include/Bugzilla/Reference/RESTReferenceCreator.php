<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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
 */

namespace Tuleap\Bugzilla\Reference;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Tuleap\Reference\CrossReference;
use Tuleap\Reference\GotoLink;

class RESTReferenceCreator
{
    /**
     * @var ClientInterface
     */
    private $http_client;
    /**
     * @var RequestFactoryInterface
     */
    private $request_factory;
    /**
     * @var StreamFactoryInterface
     */
    private $stream_factory;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        ClientInterface $http_client,
        RequestFactoryInterface $request_factory,
        StreamFactoryInterface $stream_factory,
        LoggerInterface $logger,
    ) {
        $this->http_client     = $http_client;
        $this->request_factory = $request_factory;
        $this->stream_factory  = $stream_factory;
        $this->logger          = $logger;
    }

    public function create(CrossReference $cross_reference, Reference $bugzilla): void
    {
        $source_keyword = $cross_reference->getRefSourceKey();
        $source_id      = $cross_reference->getRefSourceId();
        $target_keyword = $cross_reference->getRefTargetKey();
        $target_id      = $cross_reference->getRefTargetId();

        $this->logger->info(
            "Asking reference between $source_keyword $source_id and bugzilla reference $target_keyword $target_id"
        );
        $message  = "A tuleap item [$source_keyword #$source_id] references this bugzilla item. \n";
        $message .= "[$source_keyword #$source_id]: " . $this->getLinkToSource($cross_reference);

        $base_url              = $this->getBaseUrl($bugzilla);
        $url                   = $base_url . '/rest/bug/' . urlencode((string) $target_id) . '/comment';
        $login                 = $bugzilla->getUsername();
        $api_key               = $bugzilla->getAPIKey();
        $are_follow_up_private = $bugzilla->getAreFollowupPrivate();

        $request = $this->request_factory->createRequest('POST', $url)
            ->withHeader('Content-Type', 'application/json')
            ->withBody(
                $this->stream_factory->createStream(
                    json_encode(
                        [
                            'Bugzilla_login'   => $login,
                            'Bugzilla_api_key' => $api_key->getString(),
                            'id'               => $target_id,
                            'comment'          => $message,
                            'is_private'       => $are_follow_up_private,
                            'is_markdown'      => true,
                        ]
                    )
                )
            );

        try {
            $response = $this->http_client->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            $this->logger->error('Could not send HTTP request to Bugzilla server ' . $url, $e);
            return;
        }

        if ($response->getStatusCode() === 401) {
            $message = dgettext(
                'tuleap-bugzilla_reference',
                'Cannot create Bugzilla link. Please check your credentials.'
            );
            if ($are_follow_up_private) {
                $message .= ' ' . dgettext(
                    'tuleap-bugzilla_reference',
                    'Please check that you can add private comments in Bugzilla.'
                );
            }

            $this->logger->error($message);
            return;
        }

        $response_status_code   = $response->getStatusCode();
        $response_reason_phrase = $response->getReasonPhrase();
        if ($response_status_code >= 400) {
            $this->logger->error(
                'Request has not been processed correctly by the Bugzilla server: #'
                . $response_status_code . ':' . $response_reason_phrase
            );
            return;
        }

        $this->logger->info('Bugzilla reference added: #' . $response_status_code . ':' . $response_reason_phrase);
    }

    private function getLinkToSource(CrossReference $cross_reference)
    {
        $link = GotoLink::fromComponents(
            $cross_reference->getRefSourceKey(),
            (string) $cross_reference->getRefSourceId(),
            $cross_reference->getRefSourceGid()
        );
        return $link->getFullGotoLink();
    }

    private function getBaseUrl(Reference $bugzilla)
    {
        if ($bugzilla->getRestUrl()) {
            return $bugzilla->getRestUrl();
        }

        return $bugzilla->getServer();
    }
}
