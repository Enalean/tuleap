<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\User;

use ColinODell\PsrTestLogger\TestLogger;
use PFUser;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Stub\Creation\JiraImporter\JiraClientStub;
use Tuleap\Tracker\XML\Importer\TrackerImporterUser;
use UserManager;

#[DisableReturnValueGenerationForTestDoubles]
final class JiraAuthorRetrieverTest extends TestCase
{
    private UserManager&MockObject $user_manager;
    private TestLogger $logger;
    private JiraUserRetriever $retriever;
    private PFUser $forge_user;

    protected function setUp(): void
    {
        $this->forge_user = UserTestBuilder::aUser()->withRealName('Tracker Importer (forge__tracker_importer_user)')->build();

        $this->user_manager = $this->createMock(UserManager::class);
        $this->logger       = new TestLogger();
        $this->retriever    = new JiraUserRetriever(
            $this->logger,
            $this->user_manager,
            new JiraUserOnTuleapCache(new JiraTuleapUsersMapping(), $this->forge_user),
            new JiraUserInfoQuerier(JiraClientStub::aJiraClient(), $this->logger),
            $this->forge_user
        );
    }

    protected function tearDown(): void
    {
        self::assertTrue($this->logger->hasDebugRecords());
    }

    public function testItReturnsTheTuleapUserIfEmailAddressMatchesOnce(): void
    {
        $tuleap_user = UserTestBuilder::aUser()->withRealName('John Doe')->build();
        $this->user_manager->method('getAndEventuallyCreateUserByEmail')->with('johndoe@example.com')->willReturn([$tuleap_user]);

        $submitter = $this->retriever->retrieveUserFromAPIData([
            'accountId'    => '5e8dss456a2d45f3',
            'displayName'  => 'John Doe',
            'emailAddress' => 'johndoe@example.com',
        ]);

        self::assertEquals('John Doe', $submitter->getRealName());
    }

    public function testItReturnsForgeUserWhenMoreThanOneEmailAddressMatchesOnTuleapSide(): void
    {
        $tuleap_user = UserTestBuilder::aUser()->withRealName('John Doe')->build();
        $this->user_manager->method('getAndEventuallyCreateUserByEmail')
            ->with('johndoe@example.com')
            ->willReturn([
                $tuleap_user,
                UserTestBuilder::buildWithDefaults(),
                UserTestBuilder::buildWithDefaults(),
            ]);

        $submitter = $this->retriever->retrieveUserFromAPIData([
            'accountId'    => '5e8dss456a2d45f3',
            'displayName'  => 'John Doe',
            'emailAddress' => 'johndoe@example.com',
        ]);

        self::assertEquals('Tracker Importer (forge__tracker_importer_user)', $submitter->getRealName());
    }

    public function testItReturnsForgeUserWhenNoEmailAddressMatchesOnTuleapSide(): void
    {
        $this->user_manager->method('getAndEventuallyCreateUserByEmail')->with('johndoe@example.com')->willReturn([]);

        $submitter = $this->retriever->retrieveUserFromAPIData([
            'accountId'    => '5e8dss456a2d45f3',
            'displayName'  => 'John Doe',
            'emailAddress' => 'johndoe@example.com',
        ]);

        self::assertEquals('Tracker Importer (forge__tracker_importer_user)', $submitter->getRealName());
    }

    public function testItReturnsForgeUserUserDoesNotShareHisEmailAddress(): void
    {
        $submitter = $this->retriever->retrieveUserFromAPIData([
            'accountId'   => '5e8dss456a2d45f3',
            'displayName' => 'John Doe',
        ]);

        self::assertEquals('Tracker Importer (forge__tracker_importer_user)', $submitter->getRealName());
    }

    public function testItDoesNotCallUserManagerWhenUserExistsInCache(): void
    {
        $this->user_manager->expects($this->never())->method('getAndEventuallyCreateUserByEmail');
        $this->forge_user->setId(TrackerImporterUser::ID);

        $submitter = $this->retriever->retrieveUserFromAPIData([
            'accountId'   => '5e8dss456a2d45f3',
            'displayName' => 'John Doe',
        ]);

        self::assertEquals('Tracker Importer (forge__tracker_importer_user)', $submitter->getRealName());
    }
}
