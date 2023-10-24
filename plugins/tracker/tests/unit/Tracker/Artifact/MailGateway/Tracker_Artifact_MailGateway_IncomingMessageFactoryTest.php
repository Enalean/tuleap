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

use Tuleap\Tracker\Artifact\Artifact;

require_once __DIR__ . '/../../../bootstrap.php';

class Tracker_Artifact_MailGateway_IncomingMessageFactoryTest extends \PHPUnit\Framework\TestCase // phpcs:ignore
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testCorrespondingRecipientIsReturned()
    {
        $tracker_config = Mockery::mock(\Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfig::class);
        $tracker_config->shouldReceive('isTokenBasedEmailgatewayEnabled')->andReturns(true);

        $recipient = Mockery::mock(Tracker_Artifact_MailGateway_Recipient::class);
        $artifact  = Mockery::mock(Artifact::class);
        $user      = Mockery::mock(PFUser::class);

        $artifact->shouldReceive('getTracker')->andReturns(Mockery::mock(Tracker::class));
        $recipient->shouldReceive('getArtifact')->andReturns($artifact);
        $recipient->shouldReceive('getUser')->andReturns($user);

        $recipient_factory = Mockery::mock(Tracker_Artifact_MailGateway_RecipientFactory::class);
        $recipient_factory->shouldReceive('getFromEmail')
            ->with('<1661-08c450c149f11d38955ad983a9b3b857-107-1234@crampons.cro.example.com>')
            ->andReturns($recipient);
        $incoming_message_token_builder = new Tracker_Artifact_IncomingMessageTokenBuilder($recipient_factory);

        $incoming_message_factory = new Tracker_Artifact_MailGateway_IncomingMessageFactory(
            $tracker_config,
            $incoming_message_token_builder,
            Mockery::mock(Tracker_Artifact_IncomingMessageInsecureBuilder::class)
        );

        $incoming_mail = Mockery::mock(\Tuleap\Tracker\Artifact\MailGateway\IncomingMail::class);
        $incoming_mail->shouldReceive('getHeaderValue')
            ->with('references')
            ->andReturns(
                '<CACbijxsMSUpnKPi3Kq5k9rA=7RZ=ntXT_ayYW0Z2UP9qC=7=jA@mail.gmail.com> <1661-08c450c149f11d38955ad983a9b3b857-107-1234@crampons.cro.example.com>'
            );
        $incoming_mail->shouldReceive('getSubject')->andReturns('Subject');
        $incoming_mail->shouldReceive('getBodyText')->andReturns('Text');

        $incoming_message = $incoming_message_factory->build($incoming_mail);

        $this->assertSame($artifact, $incoming_message->getArtifact());
        $this->assertSame($user, $incoming_message->getUser());
    }
}
