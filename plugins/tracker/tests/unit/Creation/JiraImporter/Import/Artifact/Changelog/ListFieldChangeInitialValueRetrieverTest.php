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

use PFUser;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_FormElement_Field_List_Bind_Static;
use Tracker_FormElement_Field_List_Bind_Users;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\ListFieldMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraUserRetriever;
use Tuleap\Tracker\XML\Importer\TrackerImporterUser;

#[DisableReturnValueGenerationForTestDoubles]
final class ListFieldChangeInitialValueRetrieverTest extends TestCase
{
    private JiraUserRetriever&MockObject $jira_user_retriever;
    private ListFieldChangeInitialValueRetriever $list_field_change_initial_value_retriever;
    private PFUser $forge_user;

    protected function setUp(): void
    {
        $this->forge_user          = UserTestBuilder::buildWithId(TrackerImporterUser::ID);
        $this->jira_user_retriever = $this->createMock(JiraUserRetriever::class);

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
                Tracker_FormElement_Field_List_Bind_Static::TYPE,
                [],
            )
        );

        self::assertSame(['id' => '10005'], $list_value);
    }

    public function testItReturnsTheUserIdFormatted(): void
    {
        $this->jira_user_retriever->method('getAssignedTuleapUser')->with('e8a6c4d54')
            ->willReturn(UserTestBuilder::buildWithId(105));

        $list_value = $this->list_field_change_initial_value_retriever->retrieveBoundValue(
            'e8a6c4d54',
            new ListFieldMapping(
                'assignee',
                'Assignee',
                null,
                'Fassignee',
                'assignee',
                'sb',
                Tracker_FormElement_Field_List_Bind_Users::TYPE,
                [],
            )
        );

        self::assertSame(['id' => '105'], $list_value);
    }

    public function testItReturnsTheUsersIdsFormattedWithoutTrackerImporterId(): void
    {
        $john_doe = UserTestBuilder::buildWithId(105);

        $this->jira_user_retriever->method('getAssignedTuleapUser')->willReturnCallback(fn(string $id) => match ($id) {
            'e8a6c4d54' => $john_doe,
            'a7e9f1b2c' => $this->forge_user,
        });

        $list_value = $this->list_field_change_initial_value_retriever->retrieveBoundValue(
            'e8a6c4d54, a7e9f1b2c',
            new ListFieldMapping(
                'multiuserpicker',
                'Multi userpicker',
                null,
                'Fmultiuserpicker',
                'multiuserpicker',
                'msb',
                Tracker_FormElement_Field_List_Bind_Users::TYPE,
                [],
            )
        );

        self::assertSame(
            [
                ['id' => '105'],
            ],
            $list_value
        );
    }

    public function testItReturnsTheUsersIdsFormattedInNewFormat(): void
    {
        $john_doe     = UserTestBuilder::buildWithId(105);
        $another_user = UserTestBuilder::buildWithId(106);

        $this->jira_user_retriever->method('getAssignedTuleapUser')->willReturnCallback(static fn(string $id) => match ($id) {
            'e8a6c4d54' => $john_doe,
            'a7e9f1b2c' => $another_user,
        });

        $list_value = $this->list_field_change_initial_value_retriever->retrieveBoundValue(
            '[e8a6c4d54, a7e9f1b2c]',
            new ListFieldMapping(
                'multiuserpicker',
                'Multi userpicker',
                null,
                'Fmultiuserpicker',
                'multiuserpicker',
                'msb',
                Tracker_FormElement_Field_List_Bind_Users::TYPE,
                [],
            )
        );

        self::assertSame(
            [
                ['id' => '105'],
                ['id' => '106'],
            ],
            $list_value
        );
    }
}
