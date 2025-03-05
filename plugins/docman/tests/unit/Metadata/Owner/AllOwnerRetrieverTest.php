<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Docman\Metadata\Owner;

use Project;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\BuildDisplayNameStub;
use Tuleap\Test\Stubs\ProvideUserFromRowStub;
use Tuleap\Test\Stubs\User\Avatar\ProvideUserAvatarUrlStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AllOwnerRetrieverTest extends TestCase
{
    public function testItReturnsAnEmptyArrayIfThereIsNoDocumentOwner(): void
    {
        $owner_retriever = new AllOwnerRetriever(
            new class implements OwnerData {
                public function getDocumentOwnerOfProjectForAutocomplete(Project $project, string $name_to_search): ?array
                {
                    return [];
                }
            },
            ProvideUserFromRowStub::build(),
            BuildDisplayNameStub::build(),
            ProvideUserAvatarUrlStub::build(),
        );

        self::assertEquals([], $owner_retriever->retrieveProjectDocumentOwnersForAutocomplete(ProjectTestBuilder::aProject()->build(), 'igo'));
    }

    public function testItReturnsTheDocumentOwner(): void
    {
        $owner_retriever = new AllOwnerRetriever(
            new class implements OwnerData {
                public function getDocumentOwnerOfProjectForAutocomplete(Project $project, string $name_to_search): ?array
                {
                    return [
                        [
                            'user_id' => 101,
                            'user_name' => 'knopel',
                            'realname' => 'Leslie Knope',
                            'has_custom_avatar' => 'false',
                        ],
                        [
                            'user_id' => 102,
                            'user_name' => 'swansonr',
                            'realname' => 'Ron Swanson',
                            'has_custom_avatar' => 'false',
                        ],
                    ];
                }
            },
            ProvideUserFromRowStub::build(),
            BuildDisplayNameStub::build(),
            ProvideUserAvatarUrlStub::build(),
        );

        $owner_1 = UserTestBuilder::aUser()
            ->withId(101)
            ->withUserName('knopel')
            ->withRealName('Leslie Knope')
            ->withAvatarUrl('https:///users/knopel/avatar.png')
            ->build();

        $owner_2 = UserTestBuilder::aUser()
            ->withId(102)
            ->withUserName('swansonr')
            ->withRealName('Ron Swanson')
            ->withAvatarUrl('https:///users/swansonr/avatar.png')
            ->build();

        $expected_owners = [
            OwnerRepresentationForAutocomplete::buildForSelect2AutocompleteFromOwner($owner_1, BuildDisplayNameStub::build(), ProvideUserAvatarUrlStub::build()),
            OwnerRepresentationForAutocomplete::buildForSelect2AutocompleteFromOwner($owner_2, BuildDisplayNameStub::build(), ProvideUserAvatarUrlStub::build()),
        ];

        $owners = $owner_retriever->retrieveProjectDocumentOwnersForAutocomplete(ProjectTestBuilder::aProject()->build(), '');
        self::assertEquals($expected_owners, $owners);
    }
}
