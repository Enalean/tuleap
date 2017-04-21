<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

use CrossReference;
use Http_Client;
use ReferenceInstance;
use Tuleap\Bugzilla\BugzillaLogger;

class RESTReferenceCreator
{
    /**
     * @var Http_Client
     */
    private $http_curl_client;
    /**
     * @var BugzillaLogger
     */
    private $logger;

    public function __construct(Http_Client $http_curl_client, BugzillaLogger $logger)
    {
        $this->http_curl_client = $http_curl_client;
        $this->logger           = $logger;
    }

    public function create(CrossReference $cross_reference, Reference $bugzilla)
    {
        $source_keyword = $cross_reference->getRefSourceKey();
        $source_id      = $cross_reference->getRefSourceId();
        $target_keyword = $cross_reference->getRefTargetKey();
        $target_id      = $cross_reference->getRefTargetId();

        $this->logger->info(
            "Asking reference between $source_keyword $source_id and bugzilla reference $target_keyword $target_id"
        );
        $message = "A tuleap item [$source_keyword #$source_id] references this bugzilla item. \n";
        $message .= "[$source_keyword #$source_id]: " . $this->getLinkToSource($cross_reference);

        $url                   = $bugzilla->getServer() . '/rest/bug/' . urlencode($target_id) . '/comment';
        $login                 = $bugzilla->getUsername();
        $password              = $bugzilla->getPassword();
        $are_follow_up_private = $bugzilla->getAreFollowupPrivate();

        $options = array(
            CURLOPT_URL         => $url,
            CURLOPT_POST        => true,
            CURLOPT_HEADER      => true,
            CURLOPT_FAILONERROR => true,
            CURLOPT_HTTPHEADER  => array('Content-Type: application/json'),
            CURLOPT_POSTFIELDS  => json_encode(
                array(
                    "Bugzilla_login"    => $login,
                    "Bugzilla_password" => $password,
                    "id"                => $target_id,
                    "comment"           => $message,
                    "is_private"        => $are_follow_up_private,
                    "is_markdown"       => true
                )
            )
        );
        $this->http_curl_client->addOptions($options);
        try {
            $this->http_curl_client->doRequest();
            $this->http_curl_client->close();
            $this->logger->info("Bugzilla reference added", $this->http_curl_client->getStatusCodeAndReasonPhrase());
        } catch (\Http_ClientException $ex) {
            $this->logger->error($ex->getMessage());
        }
    }

    private function getLinkToSource(CrossReference $cross_reference)
    {
        $reverse_reference_instance = new ReferenceInstance(null, null, $cross_reference->getRefTargetId());
        $reverse_reference_instance->computeGotoLink(
            $cross_reference->getRefSourceKey(),
            $cross_reference->getRefSourceId(),
            $cross_reference->getRefSourceGid()
        );

        return $reverse_reference_instance->getFullGotoLink();
    }
}
