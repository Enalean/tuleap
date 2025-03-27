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

use PFUser;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\XML\Importer\TrackerImporterUser;

#[DisableReturnValueGenerationForTestDoubles]
final class JiraTuleapUsersMappingTest extends TestCase
{
    private JiraTuleapUsersMapping $mapping;
    private PFUser $tuleap_user;

    protected function setUp(): void
    {
        $this->mapping     = new JiraTuleapUsersMapping();
        $this->tuleap_user = UserTestBuilder::aUser()->withId(105)->withRealName('John Doe')->withUserName('jdoe')->build();
    }

    public function testItStoresIdentifiedUsersInTheListOfIdentifiedUsers(): void
    {
        $jira_user = new ActiveJiraCloudUser([
            'displayName'  => 'Jeannot',
            'accountId'    => 'e8a4sd5d6',
            'emailAddress' => 'john.doe@example.com',
        ]);

        $this->mapping->addUserMapping($jira_user, $this->tuleap_user);

        $identified_users = $this->mapping->getIdentifiedUsers();
        self::assertEquals(1, count($identified_users));
        self::assertSame(
            [
                'jira_display_name'       => 'Jeannot',
                'tuleap_user_real_name'   => 'John Doe',
                'tuleap_user_profile_url' => 'https:///users/jdoe',
                'tuleap_user_username'    => 'jdoe',
            ],
            $identified_users[0]
        );
    }

    public function testItStoresUsersInTheListOfNotMatchingEmailAddresses(): void
    {
        $jira_user = new ActiveJiraCloudUser([
            'displayName'  => 'Jeannot',
            'accountId'    => 'e8a4sd5d6',
            'emailAddress' => 'john.doe@example.com',
        ]);

        $this->tuleap_user->setId(TrackerImporterUser::ID);
        $this->mapping->addUserMapping($jira_user, $this->tuleap_user);

        $not_matching_email_address_users = $this->mapping->getUserEmailsNotMatching();
        self::assertEquals(1, count($not_matching_email_address_users));
        self::assertSame(
            ['jira_display_name' => 'Jeannot'],
            $not_matching_email_address_users[0]
        );
    }

    public function testItStoresUnknownUsers(): void
    {
        $jira_user = new ActiveJiraCloudUser([
            'displayName' => 'Jeannot',
            'accountId'   => 'e8a4sd5d6',
        ]);

        $this->tuleap_user->setId(TrackerImporterUser::ID);
        $this->mapping->addUserMapping($jira_user, $this->tuleap_user);

        $unknown_users = $this->mapping->getUnknownUsers();
        self::assertEquals(1, count($unknown_users));
        self::assertSame(
            ['jira_display_name' => 'Jeannot'],
            $unknown_users[0]
        );
    }
}
