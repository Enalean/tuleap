<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

use PFUser;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_MailGateway_ArtifactDoesNotExistException;
use Tracker_Artifact_MailGateway_RecipientFactory;
use Tracker_Artifact_MailGateway_RecipientInvalidHashException;
use Tracker_Artifact_MailGateway_RecipientUserDoesNotExistException;
use Tracker_ArtifactFactory;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use UserManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class RecipientFactoryTest extends TestCase
{
    private Tracker_Artifact_Changeset $changeset;
    private PFUser $user;
    private Artifact $artifact;
    private Tracker_Artifact_MailGateway_RecipientFactory $factory;

    protected function setUp(): void
    {
        $this->user                = new PFUser(['user_id' => 123, 'language_id' => 'en']);
        $this->changeset           = ChangesetTestBuilder::aChangeset(200)->build();
        $this->artifact            = ArtifactTestBuilder::anArtifact(101)->withChangesets($this->changeset)->build();
        $this->changeset->artifact = $this->artifact;

        $artifact_factory = $this->createMock(Tracker_ArtifactFactory::class);
        $artifact_factory->method('getArtifactById')->willReturnCallback(fn(int $id) => match ($id) {
            101     => $this->artifact,
            default => null,
        });
        $user_manager = $this->createMock(UserManager::class);
        $user_manager->method('getUserById')->willReturnCallback(fn(int $id) => match ($id) {
            123     => $this->user,
            default => null,
        });

        $this->factory = new Tracker_Artifact_MailGateway_RecipientFactory(
            $artifact_factory,
            $user_manager,
            'whatever',
            'tuleap.example.com'
        );
    }

    public function testItGeneratesAMailGatewayRecipientFromEmail(): void
    {
        $email     = '<101-5a2a341193b34695885091bbf5f75d68-123-200@tuleap.example.com>';
        $recipient = $this->factory->getFromEmail($email);

        self::assertEquals($this->artifact, $recipient->getArtifact());
        self::assertEquals($this->user, $recipient->getUser());
        self::assertEquals($email, $recipient->getEmail());
    }

    public function testItThrowsAnAxceptionWhenArtifactDoesNotExist(): void
    {
        $email = '<000000-5a2a341193b34695885091bbf5f75d68-123-200@tuleap.example.com>';
        $this->expectException(Tracker_Artifact_MailGateway_ArtifactDoesNotExistException::class);
        $this->factory->getFromEmail($email);
    }

    public function testItThrowsAnAxceptionWhenUserDoesNotExist(): void
    {
        $email = '<101-5a2a341193b34695885091bbf5f75d68-00000-200@tuleap.example.com>';
        $this->expectException(Tracker_Artifact_MailGateway_RecipientUserDoesNotExistException::class);
        $this->factory->getFromEmail($email);
    }

    public function testItThrowsAnAxceptionWhenHashIsInvalid(): void
    {
        $email = '<101-invalidhash-123-200@tuleap.example.com>';
        $this->expectException(Tracker_Artifact_MailGateway_RecipientInvalidHashException::class);
        $this->factory->getFromEmail($email);
    }

    public function testItGeneratesAMailGatewayRecipientFromUserAndArtifact(): void
    {
        $email     = '101-5a2a341193b34695885091bbf5f75d68-123-200@tuleap.example.com';
        $recipient = $this->factory->getFromUserAndChangeset($this->user, $this->changeset);

        self::assertEquals($this->artifact, $recipient->getArtifact());
        self::assertEquals($this->user, $recipient->getUser());
        self::assertEquals($email, $recipient->getEmail());
    }
}
