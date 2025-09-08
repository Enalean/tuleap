<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Mail\Transport;

use ForgeConfig;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\RawMessage;

final readonly class InvalidDefinedTransport implements MailerInterface
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    #[\Override]
    public function send(RawMessage $message, ?Envelope $envelope = null): void
    {
        // We don't send any mail if the transport if not defined to either "sendmail" or "smtp"
        // We only log that there is an issue

        $this->logger->error(
            sprintf(
                "No mail has been sent. The value '%s' of '%s' is not recognized. It must be either '%s' or '%s'",
                ForgeConfig::get(MailTransportBuilder::TRANSPORT_CONFIG_KEY),
                MailTransportBuilder::TRANSPORT_CONFIG_KEY,
                MailTransportBuilder::EMAIL_TRANSPORT_SENDMAIL_VALUE,
                MailTransportBuilder::EMAIL_TRANSPORT_SMTP_VALUE,
            )
        );
    }
}
