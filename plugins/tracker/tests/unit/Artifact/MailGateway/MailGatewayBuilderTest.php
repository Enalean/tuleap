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

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\MailGateway;

use Psr\Log\NullLogger;
use Tracker_Artifact_Changeset_IncomingMailDao;
use Tracker_Artifact_MailGateway_CitationStripper;
use Tracker_Artifact_MailGateway_IncomingMessageFactory;
use Tracker_Artifact_MailGateway_InsecureMailGateway;
use Tracker_Artifact_MailGateway_MailGatewayBuilder;
use Tracker_Artifact_MailGateway_Notifier;
use Tracker_Artifact_MailGateway_TokenMailGateway;
use Tracker_ArtifactByEmailStatus;
use Tracker_FormElementFactory;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Creation\TrackerArtifactCreator;
use Tuleap\Tracker\Test\Stub\Semantic\Description\RetrieveSemanticDescriptionFieldStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MailGatewayBuilderTest extends TestCase
{
    private Tracker_Artifact_MailGateway_MailGatewayBuilder $mailgateway_builder;
    private IncomingMail $insecure_mail;
    private IncomingMail $token_mail;

    protected function setUp(): void
    {
        parent::setUp();

        $this->insecure_mail = new IncomingMail(file_get_contents(__DIR__ . '/_fixtures/insecure-reply-comment.plain.eml'));
        $this->token_mail    = new IncomingMail(file_get_contents(__DIR__ . '/_fixtures/reply-comment.plain.eml'));

        $this->mailgateway_builder = new Tracker_Artifact_MailGateway_MailGatewayBuilder(
            $this->createMock(Tracker_Artifact_MailGateway_IncomingMessageFactory::class),
            $this->createMock(Tracker_Artifact_MailGateway_CitationStripper::class),
            $this->createMock(Tracker_Artifact_MailGateway_Notifier::class),
            $this->createMock(Tracker_Artifact_Changeset_IncomingMailDao::class),
            $this->createMock(TrackerArtifactCreator::class),
            $this->createMock(Tracker_FormElementFactory::class),
            $this->createMock(Tracker_ArtifactByEmailStatus::class),
            new NullLogger(),
            new MailGatewayFilter(),
            RetrieveSemanticDescriptionFieldStub::withNoField(),
        );
    }

    public function testItReturnsAnInsecureMailGateway(): void
    {
        $mailgateway = $this->mailgateway_builder->build($this->insecure_mail);

        self::assertInstanceOf(Tracker_Artifact_MailGateway_InsecureMailGateway::class, $mailgateway);
    }

    public function testItReturnsATokenMailGateway(): void
    {
        $mailgateway = $this->mailgateway_builder->build($this->token_mail);

        self::assertInstanceOf(Tracker_Artifact_MailGateway_TokenMailGateway::class, $mailgateway);
    }
}
