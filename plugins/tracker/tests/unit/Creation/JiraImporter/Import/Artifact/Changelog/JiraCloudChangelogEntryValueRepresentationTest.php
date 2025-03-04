<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog;

use Tuleap\Tracker\Creation\JiraImporter\Import\User\AnonymousJiraUser;
use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertInstanceOf;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class JiraCloudChangelogEntryValueRepresentationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItBuildsARepresentationFromAPIResponse(): void
    {
        $response = [
            'id'      => '10057',
            'created' => '2020-03-25T14:10:10.823+0100',
            'items'   => [
                [
                    'fieldId'    => 'field01',
                    'from'       => null,
                    'fromString' => 'string01',
                    'to'         => null,
                    'toString'   => 'string02',
                ],
            ],
            'author' => [
                'accountId' => 'e8a7dbae5',
                'displayName' => 'John Doe',
                'emailAddress' => 'john.doe@example.com',
            ],
        ];

        $representation = JiraCloudChangelogEntryValueRepresentation::buildFromAPIResponse($response);

        self::assertSame(10057, $representation->getId());
        self::assertSame(1585141810, $representation->getCreated()->getTimestamp());
        $this->assertCount(1, $representation->getItemRepresentations());
        $this->assertEquals($representation->getChangelogOwner()->getDisplayName(), 'John Doe');
    }

    public function testItThrowsAnExcpetionIfAPIResponseIsNotWellFormed(): void
    {
        $response = [
            'items' => [
                [
                    'fieldId'    => 'field01',
                    'from'       => null,
                    'fromString' => 'string01',
                ],
            ],
        ];

        $this->expectException(ChangelogAPIResponseNotWellFormedException::class);

        JiraCloudChangelogEntryValueRepresentation::buildFromAPIResponse($response);

        $response = [
            'id' => '10057',
        ];

        $this->expectException(ChangelogAPIResponseNotWellFormedException::class);

        JiraCloudChangelogEntryValueRepresentation::buildFromAPIResponse($response);

        $response = [
            'id' => '10057',
            'items' => [
                [
                    'fieldId'    => 'field01',
                    'from'       => null,
                    'fromString' => 'string01',
                ],
            ],
            'author' => [
                'accountId' => 'e8a7dbae5',
                'displayName' => 'John Doe',
                'emailAddress' => 'john.doe@example.com',
            ],
        ];

        $this->expectException(ChangelogAPIResponseNotWellFormedException::class);

        JiraCloudChangelogEntryValueRepresentation::buildFromAPIResponse($response);
    }

    public function testItBuildsARepresentationFromAPIResponseWithChangeMadeByAnonymous(): void
    {
        $response = [
            'id'      => '10057',
            'created' => '2020-03-25T14:10:10.823+0100',
            'items'   => [
                [
                    'fieldId'    => 'field01',
                    'from'       => null,
                    'fromString' => 'string01',
                    'to'         => null,
                    'toString'   => 'string02',
                ],
            ],
        ];

        $representation = JiraCloudChangelogEntryValueRepresentation::buildFromAPIResponse($response);

        assertEquals(10057, $representation->getId());
        assertEquals(1585141810, $representation->getCreated()->getTimestamp());
        assertCount(1, $representation->getItemRepresentations());
        assertInstanceOf(AnonymousJiraUser::class, $representation->getChangelogOwner());
    }
}
