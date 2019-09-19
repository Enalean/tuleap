<?php
/**
 * Copyright (c) Enalean, 2015-2018. All Rights Reserved.
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

use Tuleap\Tracker\Artifact\MailGateway\IncomingMail;
use Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfig;

class Tracker_Artifact_MailGateway_IncomingMessageFactory
{
    /** @var MailGatewayConfig */
    private $tracker_config;

    /** @var Tracker_Artifact_IncomingMessageTokenBuilder */
    private $incoming_message_token_builder;

    /** @var Tracker_Artifact_IncomingMessageInsecureBuilder */
    private $incoming_message_insecure_builder;

    public function __construct(
        MailGatewayConfig $tracker_config,
        Tracker_Artifact_IncomingMessageTokenBuilder $incoming_message_token_builder,
        Tracker_Artifact_IncomingMessageInsecureBuilder $incoming_message_insecure_builder
    ) {
        $this->tracker_config                    = $tracker_config;
        $this->incoming_message_token_builder    = $incoming_message_token_builder;
        $this->incoming_message_insecure_builder = $incoming_message_insecure_builder;
    }

    /**
     * @return Tracker_Artifact_MailGateway_IncomingMessage
     */
    public function build(IncomingMail $incoming_mail)
    {
        $incoming_message = null;

        if ($this->tracker_config->isTokenBasedEmailgatewayEnabled()) {
            $incoming_message = $this->incoming_message_token_builder->build($incoming_mail);
        } elseif ($this->tracker_config->isInsecureEmailgatewayEnabled()) {
            $incoming_message = $this->buildIncomingMessageInInsecureMode($incoming_mail);
        }

        return $incoming_message;
    }

    /**
     * @return Tracker_Artifact_MailGateway_IncomingMessage
     */
    private function buildIncomingMessageInInsecureMode(IncomingMail $incoming_mail)
    {
        if ($this->isATokenMail($incoming_mail)) {
            return $this->incoming_message_token_builder->build($incoming_mail);
        }
        return $this->incoming_message_insecure_builder->build($incoming_mail);
    }

    /**
     * @return bool
     */
    private function isATokenMail(IncomingMail $mail)
    {
        foreach ($mail->getTo() as $address) {
            if (strpos($address, trackerPlugin::EMAILGATEWAY_TOKEN_ARTIFACT_UPDATE) === 0) {
                return true;
            }
        }
        return false;
    }
}
