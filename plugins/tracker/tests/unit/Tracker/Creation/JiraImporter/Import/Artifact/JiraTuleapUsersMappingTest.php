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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Comment\JiraUser;
use Tuleap\Tracker\XML\Importer\TrackerImporterUser;

final class JiraTuleapUsersMappingTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var JiraTuleapUsersMapping
     */
    private $mapping;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\PFUser
     */
    private $tuleap_user;

    protected function setUp(): void
    {
        $this->mapping     = new JiraTuleapUsersMapping();
        $this->tuleap_user = \Mockery::mock(\PFUser::class);
    }

    public function testItStoresIdentifiedUsersInTheListOfIdentifiedUsers(): void
    {
        $jira_user = new JiraUser([
            'displayName' => 'Jeannot',
            'accountId' => 'e8a4sd5d6',
            'emailAddress' => 'john.doe@example.com'
        ]);

        $this->tuleap_user->shouldReceive('getId')->andReturn(105);
        $this->tuleap_user->shouldReceive('getRealName')->andReturn('John Doe');
        $this->tuleap_user->shouldReceive('getUserName')->andReturn('jdoe');
        $this->tuleap_user->shouldReceive('getPublicProfileUrl')->andReturn('/users/jdoe');

        $this->mapping->addUserMapping($jira_user, $this->tuleap_user);

        $identified_users = $this->mapping->getIdentifiedUsers();
        $this->assertEquals(1, count($identified_users));
        $this->assertSame(
            [
                'jira_display_name'       => 'Jeannot',
                'tuleap_user_real_name'   => 'John Doe',
                'tuleap_user_profile_url' => 'https:///users/jdoe',
                'tuleap_user_username'    => 'jdoe'
            ],
            $identified_users[0]
        );
    }

    public function testItStoresUsersInTheListOfNotMatchingEmailAddresses(): void
    {
        $jira_user = new JiraUser([
            'displayName' => 'Jeannot',
            'accountId' => 'e8a4sd5d6',
            'emailAddress' => 'john.doe@example.com'
        ]);

        $this->tuleap_user->shouldReceive('getId')->andReturn(TrackerImporterUser::ID);
        $this->mapping->addUserMapping($jira_user, $this->tuleap_user);

        $not_matching_email_address_users = $this->mapping->getUserEmailsNotMatching();
        $this->assertEquals(1, count($not_matching_email_address_users));
        $this->assertSame(
            ['jira_display_name' => 'Jeannot'],
            $not_matching_email_address_users[0]
        );
    }

    public function testItStoresUnknownUsers(): void
    {
        $jira_user = new JiraUser([
            'displayName' => 'Jeannot',
            'accountId' => 'e8a4sd5d6',
        ]);

        $this->tuleap_user->shouldReceive('getId')->andReturn(TrackerImporterUser::ID);
        $this->mapping->addUserMapping($jira_user, $this->tuleap_user);

        $unknown_users = $this->mapping->getUnknownUsers();
        $this->assertEquals(1, count($unknown_users));
        $this->assertSame(
            ['jira_display_name' => 'Jeannot'],
            $unknown_users[0]
        );
    }
}
