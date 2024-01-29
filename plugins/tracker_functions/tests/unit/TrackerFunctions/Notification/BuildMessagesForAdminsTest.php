<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\TrackerFunctions\Notification;

use PHPUnit\Framework\MockObject\MockObject;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_MailGateway_Recipient;
use Tracker_Artifact_MailGateway_RecipientFactory;
use Tuleap\GlobalLanguageMock;
use Tuleap\NeverThrow\Result;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\Builders\TemplateRendererFactoryBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfig;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;

final class BuildMessagesForAdminsTest extends TestCase
{
    use TemporaryTestDirectory;
    use GlobalLanguageMock;

    private BuildMessagesForAdmins $build_messages_for_admins;
    private MailGatewayConfig&MockObject $mail_gateway_config;
    private Tracker_Artifact_MailGateway_RecipientFactory&MockObject $recipient_factory;
    private Tracker_Artifact_Changeset $changeset;
    private Artifact $artifact;

    protected function setUp(): void
    {
        $this->mail_gateway_config       = $this->createMock(MailGatewayConfig::class);
        $this->recipient_factory         = $this->createMock(Tracker_Artifact_MailGateway_RecipientFactory::class);
        $this->build_messages_for_admins = new BuildMessagesForAdmins(
            TemplateRendererFactoryBuilder::get()->withPath($this->getTmpDir())->build(),
        );
        $this->artifact                  = ArtifactTestBuilder::anArtifact(1)->build();
        $this->changeset                 = ChangesetTestBuilder::aChangeset('1')
            ->ofArtifact($this->artifact)
            ->build();
    }

    public function testItReturnsNoMessagesIfThereIsNoRecipient(): void
    {
        $result = $this->build_messages_for_admins->buildMessagesForAdmins([], $this->changeset);

        self::assertTrue(Result::isOk($result));
        self::assertEmpty($result->value);
    }

    public function testItBuildMessageForAnAdmin(): void
    {
        $this->mail_gateway_config->method('isTokenBasedEmailgatewayEnabled')->willReturn(true);
        $this->recipient_factory->method('getFromUserAndChangeset')->willReturn(new Tracker_Artifact_MailGateway_Recipient(
            UserTestBuilder::buildWithDefaults(),
            $this->artifact,
            'example2@example.com'
        ));

        $GLOBALS['Language']->method('getText')->willReturn('c');
        $result = $this->build_messages_for_admins->buildMessagesForAdmins([
            UserTestBuilder::anActiveUser()
                ->withEmail('example@example.com')
                ->build(),
        ], $this->changeset);

        self::assertTrue(Result::isOk($result));
        $messages = $result->value;
        self::assertCount(1, $messages);
        $message = $messages[0];
        self::assertEquals(['example@example.com'], $message->recipients);
    }
}
