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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../bootstrap.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class RecipientFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $changeset;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user      = new PFUser(['user_id' => 123, 'language_id' => 'en']);
        $this->changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class)->shouldReceive('getId')->andReturns(200)->getMock();
        $this->artifact  = \Mockery::spy(\Tracker_Artifact::class)->shouldReceive('getId')->andReturns(101)->getMock();
        $this->artifact->shouldReceive('getChangeset')->with(200)->andReturns($this->changeset);
        $this->changeset->shouldReceive('getArtifact')->andReturns($this->artifact);

        $this->salt      = 'whatever';
        $this->host      = 'tuleap.example.com';

        $this->artifact_factory  = \Mockery::spy(\Tracker_ArtifactFactory::class)->shouldReceive('getArtifactById')->with(101)->andReturns($this->artifact)->getMock();
        $this->user_manager      = \Mockery::spy(\UserManager::class)->shouldReceive('getUserById')->with(123)->andReturns($this->user)->getMock();

        $this->factory = new Tracker_Artifact_MailGateway_RecipientFactory(
            $this->artifact_factory,
            $this->user_manager,
            $this->salt,
            $this->host
        );
    }

    public function testItGeneratesAMailGatewayRecipientFromEmail()
    {
        $email = '<101-5a2a341193b34695885091bbf5f75d68-123-200@tuleap.example.com>';
        $recipient = $this->factory->getFromEmail($email);

        $this->assertEquals($this->artifact, $recipient->getArtifact());
        $this->assertEquals($this->user, $recipient->getUser());
        $this->assertEquals($email, $recipient->getEmail());
    }

    public function testItThrowsAnAxceptionWhenArtifactDoesNotExist()
    {
        $email = '<000000-5a2a341193b34695885091bbf5f75d68-123-200@tuleap.example.com>';
        $this->expectException(\Tracker_Artifact_MailGateway_ArtifactDoesNotExistException::class);
        $this->factory->getFromEmail($email);
    }

    public function testItThrowsAnAxceptionWhenUserDoesNotExist()
    {
        $email = '<101-5a2a341193b34695885091bbf5f75d68-00000-200@tuleap.example.com>';
        $this->expectException(\Tracker_Artifact_MailGateway_RecipientUserDoesNotExistException::class);
        $this->factory->getFromEmail($email);
    }

    public function testItThrowsAnAxceptionWhenHashIsInvalid()
    {
        $email = '<101-invalidhash-123-200@tuleap.example.com>';
        $this->expectException(\Tracker_Artifact_MailGateway_RecipientInvalidHashException::class);
        $this->factory->getFromEmail($email);
    }

    public function testItGeneratesAMailGatewayRecipientFromUserAndArtifact()
    {
        $email = '101-5a2a341193b34695885091bbf5f75d68-123-200@tuleap.example.com';
        $recipient = $this->factory->getFromUserAndChangeset($this->user, $this->changeset);

        $this->assertEquals($this->artifact, $recipient->getArtifact());
        $this->assertEquals($this->user, $recipient->getUser());
        $this->assertEquals($email, $recipient->getEmail());
    }
}
