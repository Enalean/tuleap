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

class WebhookResponseReceiver
{

    /**
     * @var WebhookDao
     */
    private $dao;

    public function __construct(WebhookDao $dao)
    {
        $this->dao = $dao;
    }

    public function receive(Webhook $webhook, $raw_response)
    {
        $this->dao->addLog($webhook->getId(), $this->getStatusCodeAndReasonPhrase($raw_response));
    }

    public function receiveError(Webhook $webhook, $error)
    {
        $this->dao->addLog($webhook->getId(), $error);
    }

    /**
     * status-line = HTTP-version SP status-code SP reason-phrase CRLF
     * @see https://tools.ietf.org/html/rfc7230#section-3.1.2
     */
    private function getStatusCodeAndReasonPhrase($raw_response)
    {
        $response_lines       = explode(PHP_EOL, $raw_response);
        $status_line          = $response_lines[0];
        $status_code_position = strpos($status_line, ' ') + 1;

        return substr($status_line, $status_code_position);
    }
}
