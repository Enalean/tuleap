<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\MailGateway;

use Tracker_Artifact_IncomingMessageInsecureBuilder;
use Tracker_Artifact_IncomingMessageTokenBuilder;
use Tracker_Artifact_MailGateway_IncomingMessageFactory;
use Tracker_Artifact_MailGateway_Recipient;
use Tracker_Artifact_MailGateway_RecipientFactory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

final class Tracker_Artifact_MailGateway_IncomingMessageFactoryTest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    public function testCorrespondingRecipientIsReturned(): void
    {
        $tracker_config = $this->createMock(MailGatewayConfig::class);
        $tracker_config->method('isTokenBasedEmailgatewayEnabled')->willReturn(true);

        $artifact  = ArtifactTestBuilder::anArtifact(26)->build();
        $user      = UserTestBuilder::buildWithDefaults();
        $recipient = new Tracker_Artifact_MailGateway_Recipient($user, $artifact, '1661-08c450c149f11d38955ad983a9b3b857-107-1234@crampons.cro.example.com');

        $recipient_factory = $this->createMock(Tracker_Artifact_MailGateway_RecipientFactory::class);
        $recipient_factory->method('getFromEmail')
            ->with('<1661-08c450c149f11d38955ad983a9b3b857-107-1234@crampons.cro.example.com>')
            ->willReturn($recipient);
        $incoming_message_token_builder = new Tracker_Artifact_IncomingMessageTokenBuilder($recipient_factory);

        $incoming_message_factory = new Tracker_Artifact_MailGateway_IncomingMessageFactory(
            $tracker_config,
            $incoming_message_token_builder,
            $this->createMock(Tracker_Artifact_IncomingMessageInsecureBuilder::class)
        );

        $incoming_mail = $this->createMock(IncomingMail::class);
        $incoming_mail->method('getHeaderValue')
            ->with('references')
            ->willReturn(
                '<CACbijxsMSUpnKPi3Kq5k9rA=7RZ=ntXT_ayYW0Z2UP9qC=7=jA@mail.gmail.com> <1661-08c450c149f11d38955ad983a9b3b857-107-1234@crampons.cro.example.com>'
            );
        $incoming_mail->method('getSubject')->willReturn('Subject');
        $incoming_mail->method('getBodyText')->willReturn('Text');

        $incoming_message = $incoming_message_factory->build($incoming_mail);

        self::assertSame($artifact, $incoming_message->getArtifact());
        self::assertSame($user, $incoming_message->getUser());
    }
}
