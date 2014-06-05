<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class Tracker_Artifact_MailGateway_IncomingMessage {

    /**
     * @var Tracker_Artifact_MailGatewayRecipient
     */
    private $recipient;

    /** @var string */
    private $body;

    public function __construct($body, Tracker_Artifact_MailGatewayRecipient $recipient) {
        $this->body      = $body;
        $this->recipient = $recipient;
    }

    /**
     * @return string The body of the message
     */
    public function getBody() {
        return $this->body;
    }

    /**
     * @return Tracker_Artifact_MailGatewayRecipient
     */
    public function getRecipient() {
        return $this->recipient;
    }
}
