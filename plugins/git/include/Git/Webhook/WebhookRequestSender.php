<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Git\Webhook;

use Tuleap\User\REST\MinimalUserRepresentation;
use GitRepository;
use Http_Client;
use Http_ClientException;
use PFUser;
use Logger;

class WebhookRequestSender
{

    /**
     * @var WebhookResponseReceiver
     */
    private $receiver;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var HttpClient
     */
    private $http_client;

    /**
     * @var WebhookFactory
     */
    private $factory;

    public function __construct(
        WebhookResponseReceiver $receiver,
        WebhookFactory $factory,
        Http_Client $http_client,
        Logger $logger
    ) {
        $this->factory     = $factory;
        $this->http_client = $http_client;
        $this->logger      = $logger;
        $this->receiver    = $receiver;
    }

    public function sendRequests(GitRepository $repository, PFUser $user, $oldrev, $newrev, $refname)
    {
        $web_hooks = $this->factory->getWebhooksForRepository($repository);
        foreach ($web_hooks as $web_hook) {
            $this->logger->info("Processing webhook at ". $web_hook->getUrl() ." for repository #" . $repository->getId());
            $this->buildRequest($repository, $user, $web_hook, $oldrev, $newrev, $refname);

            try {
                $this->http_client->doRequest();
                $this->receiver->receive($web_hook, $this->http_client->getStatusCodeAndReasonPhrase());
            } catch (Http_ClientException $e) {
                $this->receiver->receiveError($web_hook, $e->getMessage());
            }
        }
    }

    private function buildRequest(
        GitRepository $repository,
        PFUser $user,
        Webhook $web_hook,
        $oldrev,
        $newrev,
        $refname
    ) {
        $options = array(
            CURLOPT_URL             => $web_hook->getUrl(),
            CURLOPT_SSL_VERIFYPEER  => true,
            CURLOPT_POST            => true,
            CURLOPT_HEADER          => true,
            CURLOPT_FAILONERROR     => false,
            CURLOPT_POSTFIELDS      => $this->getRequestBody($repository, $user, $oldrev, $newrev, $refname)
        );

        $this->http_client->addOptions($options);
    }

    private function getRequestBody(GitRepository $repository, PFUser $user, $oldrev, $newrev, $refname)
    {
        $repository_representation = array(
            "id"        => $repository->getId(),
            "name"      => $repository->getName(),
            "full_name" => $repository->getFullName(),
        );

        $pusher_representation = array(
            "name"  => $user->getUserName(),
            "email" => $user->getEmail(),
        );

        $sender_representation = new MinimalUserRepresentation();
        $sender_representation->build($user);

        $body = array(
            "ref"        => $refname,
            "after"      => $newrev,
            "before"     => $oldrev,
            "repository" => $repository_representation,
            "pusher"     => $pusher_representation,
            "sender"     => $sender_representation,
        );

        return http_build_query(array(
            'payload' => json_encode($body)
        ));
    }
}
