<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\Tracker\Artifact\Creation\TrackerArtifactCreator;
use Tuleap\Tracker\Artifact\MailGateway\IncomingMail;

require_once __DIR__ . '/../../../bootstrap.php';

class MailGatewayBuilderTest extends \Tuleap\Test\PHPUnit\TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    use MockeryPHPUnitIntegration;

    /** @var Tracker_Artifact_MailGateway_MailGatewayBuilder */
    private $mailgateway_builder;

    private $insecure_mail;
    private $token_mail;

    protected function setUp(): void
    {
        parent::setUp();

        $this->insecure_mail = new IncomingMail(file_get_contents(__DIR__ . '/_fixtures/insecure-reply-comment.plain.eml'));
        $this->token_mail    = new IncomingMail(file_get_contents(__DIR__ . '/_fixtures/reply-comment.plain.eml'));

        $incoming_message_factory = \Mockery::spy(\Tracker_Artifact_MailGateway_IncomingMessageFactory::class);
        $artifact_creator         = \Mockery::spy(TrackerArtifactCreator::class);
        $tracker_artifactbyemail  = \Mockery::spy(\Tracker_ArtifactByEmailStatus::class);
        $logger                   = \Mockery::spy(\Psr\Log\LoggerInterface::class);
        $notifier                 = \Mockery::spy(\Tracker_Artifact_MailGateway_Notifier::class);
        $incoming_mail_dao        = \Mockery::spy(\Tracker_Artifact_Changeset_IncomingMailDao::class);
        $citation_stripper        = \Mockery::spy(\Tracker_Artifact_MailGateway_CitationStripper::class);

        $this->mailgateway_builder = new Tracker_Artifact_MailGateway_MailGatewayBuilder(
            $incoming_message_factory,
            $citation_stripper,
            $notifier,
            $incoming_mail_dao,
            $artifact_creator,
            \Mockery::spy(\Tracker_FormElementFactory::class),
            $tracker_artifactbyemail,
            $logger,
            \Mockery::spy(\Tuleap\Tracker\Artifact\MailGateway\MailGatewayFilter::class)
        );
    }

    public function testItReturnsAnInsecureMailGateway(): void
    {
        $mailgateway = $this->mailgateway_builder->build($this->insecure_mail);

        $this->assertInstanceOf(Tracker_Artifact_MailGateway_InsecureMailGateway::class, $mailgateway);
    }

    public function testItReturnsATokenMailGateway(): void
    {
        $mailgateway = $this->mailgateway_builder->build($this->token_mail);

        $this->assertInstanceOf(Tracker_Artifact_MailGateway_TokenMailGateway::class, $mailgateway);
    }
}
