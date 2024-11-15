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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\ListFieldMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraUserRetriever;
use Tuleap\Tracker\XML\Importer\TrackerImporterUser;

class ListFieldChangeInitialValueRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|JiraUserRetriever
     */
    private $jira_user_retriever;

    /**
     * @var ListFieldChangeInitialValueRetriever
     */
    private $list_field_change_initial_value_retriever;

    /**
     * @var \PFUser
     */
    private $forge_user;

    protected function setUp(): void
    {
        $this->forge_user = \Mockery::mock(\PFUser::class);
        $this->forge_user->shouldReceive('getId')->andReturn(TrackerImporterUser::ID);

        $this->jira_user_retriever                       = \Mockery::mock(JiraUserRetriever::class);
        $this->list_field_change_initial_value_retriever = new ListFieldChangeInitialValueRetriever(
            new CreationStateListValueFormatter(),
            $this->jira_user_retriever
        );
    }

    public function testItReturnsTheStaticListValueFormatted(): void
    {
        $list_value = $this->list_field_change_initial_value_retriever->retrieveBoundValue(
            '10005',
            new ListFieldMapping(
                'status',
                'Status',
                null,
                'Fstatus',
                'status',
                'sb',
                \Tracker_FormElement_Field_List_Bind_Static::TYPE,
                [],
            )
        );

        $this->assertSame(['id' => '10005'], $list_value);
    }

    public function testItReturnsTheUserIdFormatted(): void
    {
        $john_doe = \Mockery::mock(\PFUser::class);
        $john_doe->shouldReceive('getid')->andReturn(105);
        $this->jira_user_retriever->shouldReceive('getAssignedTuleapUser')
            ->with('e8a6c4d54')
            ->andReturn($john_doe);

        $list_value = $this->list_field_change_initial_value_retriever->retrieveBoundValue(
            'e8a6c4d54',
            new ListFieldMapping(
                'assignee',
                'Assignee',
                null,
                'Fassignee',
                'assignee',
                'sb',
                \Tracker_FormElement_Field_List_Bind_Users::TYPE,
                [],
            )
        );

        $this->assertSame(['id' => '105'], $list_value);
    }

    public function testItReturnsTheUsersIdsFormattedWithoutTrackerImporterId(): void
    {
        $john_doe = \Mockery::mock(\PFUser::class);
        $john_doe->shouldReceive('getId')->andReturn(105);

        $this->jira_user_retriever->shouldReceive('getAssignedTuleapUser')
            ->with('e8a6c4d54')
            ->andReturn($john_doe);

        $this->jira_user_retriever->shouldReceive('getAssignedTuleapUser')
            ->with('a7e9f1b2c')
            ->andReturn($this->forge_user);

        $list_value = $this->list_field_change_initial_value_retriever->retrieveBoundValue(
            'e8a6c4d54, a7e9f1b2c',
            new ListFieldMapping(
                'multiuserpicker',
                'Multi userpicker',
                null,
                'Fmultiuserpicker',
                'multiuserpicker',
                'msb',
                \Tracker_FormElement_Field_List_Bind_Users::TYPE,
                [],
            )
        );

        $this->assertSame(
            [
                ['id' => '105'],
            ],
            $list_value
        );
    }

    public function testItReturnsTheUsersIdsFormattedInNewFormat(): void
    {
        $john_doe = \Mockery::mock(\PFUser::class);
        $john_doe->shouldReceive('getId')->andReturn(105);

        $another_user = \Mockery::mock(\PFUser::class);
        $another_user->shouldReceive('getId')->andReturn(106);

        $this->jira_user_retriever->shouldReceive('getAssignedTuleapUser')
            ->with('e8a6c4d54')
            ->andReturn($john_doe);

        $this->jira_user_retriever->shouldReceive('getAssignedTuleapUser')
            ->with('a7e9f1b2c')
            ->andReturn($another_user);

        $list_value = $this->list_field_change_initial_value_retriever->retrieveBoundValue(
            '[e8a6c4d54, a7e9f1b2c]',
            new ListFieldMapping(
                'multiuserpicker',
                'Multi userpicker',
                null,
                'Fmultiuserpicker',
                'multiuserpicker',
                'msb',
                \Tracker_FormElement_Field_List_Bind_Users::TYPE,
                [],
            )
        );

        $this->assertSame(
            [
                ['id' => '105'],
                ['id' => '106'],
            ],
            $list_value
        );
    }
}
