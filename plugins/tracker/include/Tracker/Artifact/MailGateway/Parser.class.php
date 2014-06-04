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

require_once 'Mail/mimeDecode.php';

class Tracker_Artifact_MailGateway_Parser {

    /**
     * @var Tracker_Artifact_MailGatewayRecipientFactory
     */
    private $recipient_factory;

    public function __construct(Tracker_Artifact_MailGatewayRecipientFactory $recipient_factory) {
        $this->recipient_factory = $recipient_factory;
    }

    public function parse($input) {
        $decoder = new Mail_mimeDecode($input, "\r\n");

        $structure = $decoder->decode(
            array(
                'include_bodies' => true,
                'decode_bodies'  => true
            )
        );

        return new Tracker_Artifact_MailGateway_IncomingMessage(
            $this->getBody($structure),
            $this->getRecipient($structure)
        );
    }

    private function getBody(stdClass $structure) {
        if (isset($structure->body)) {
            return $structure->body;
        }

        return $structure->parts[0]->body;
    }

    private function getRecipient(stdClass $structure) {
        preg_match(Tracker_Artifact_MailGatewayRecipientFactory::EMAIL_PATTERN, $structure->headers['references'], $matches);

        return $this->recipient_factory->getFromEmail($matches[0]);
    }
}
