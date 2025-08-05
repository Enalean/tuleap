<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace unit\Artidoc\Document\Field\User;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tuleap\Artidoc\Document\Field\ConfiguredField;
use Tuleap\Artidoc\Document\Field\User\UserFieldWithValueBuilder;
use Tuleap\Artidoc\Domain\Document\Section\Field\DisplayType;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\UserFieldWithValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\UserValue;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\AnonymousUserTestProvider;
use Tuleap\Test\Stubs\BuildDisplayNameStub;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;
use Tuleap\Test\Stubs\User\Avatar\ProvideDefaultUserAvatarUrlStub;
use Tuleap\Test\Stubs\User\Avatar\ProvideUserAvatarUrlStub;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\LastUpdateByFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\SubmittedByFieldBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class UserFieldWithValueBuilderTest extends TestCase
{
    public function testItBuildsWithUserEmailForLastUpdateBy(): void
    {
        $default_user_avatar_url = ProvideDefaultUserAvatarUrlStub::build();
        $builder                 = new UserFieldWithValueBuilder(
            RetrieveUserByIdStub::withNoUser(),
            new AnonymousUserTestProvider(),
            ProvideUserAvatarUrlStub::build(),
            $default_user_avatar_url,
            BuildDisplayNameStub::build(),
        );

        $field     = LastUpdateByFieldBuilder::aLastUpdateByField(123)->build();
        $changeset = ChangesetTestBuilder::aChangeset(52)->submittedByAnonymous('bob@example.com')->build();

        self::assertEquals(
            new UserFieldWithValue(
                $field->getLabel(),
                DisplayType::BLOCK,
                new UserValue(
                    'bob@example.com',
                    $default_user_avatar_url->getDefaultAvatarUrl(),
                ),
            ),
            $builder->buildUserFieldWithValue(new ConfiguredField($field, DisplayType::BLOCK), $changeset),
        );
    }

    public function testItBuildsWithCorrespondingUserForLastUpdateBy(): void
    {
        $bob     = UserTestBuilder::aUser()->withId(125)
            ->withUserName('bob')
            ->withRealName('Bobby')
            ->build();
        $builder = new UserFieldWithValueBuilder(
            RetrieveUserByIdStub::withUser($bob),
            new AnonymousUserTestProvider(),
            ProvideUserAvatarUrlStub::build(),
            ProvideDefaultUserAvatarUrlStub::build(),
            BuildDisplayNameStub::build(),
        );

        $field     = LastUpdateByFieldBuilder::aLastUpdateByField(123)->build();
        $changeset = ChangesetTestBuilder::aChangeset(52)->submittedBy((int) $bob->getId())->build();

        self::assertEquals(
            new UserFieldWithValue(
                $field->getLabel(),
                DisplayType::BLOCK,
                new UserValue(
                    'Bobby (bob)',
                    'avatar.png',
                ),
            ),
            $builder->buildUserFieldWithValue(new ConfiguredField($field, DisplayType::BLOCK), $changeset),
        );
    }

    public function testItBuildsWithCorrespondingUserForSubmittedBy(): void
    {
        $bob     = UserTestBuilder::aUser()->withId(125)
            ->withUserName('bob')
            ->withRealName('Bobby')
            ->build();
        $builder = new UserFieldWithValueBuilder(
            RetrieveUserByIdStub::withUser($bob),
            new AnonymousUserTestProvider(),
            ProvideUserAvatarUrlStub::build(),
            ProvideDefaultUserAvatarUrlStub::build(),
            BuildDisplayNameStub::build(),
        );

        $field     = SubmittedByFieldBuilder::aSubmittedByField(123)->build();
        $changeset = ChangesetTestBuilder::aChangeset(52)
            ->ofArtifact(ArtifactTestBuilder::anArtifact(9875)->submittedBy($bob)->build())
            ->build();

        self::assertEquals(
            new UserFieldWithValue(
                $field->getLabel(),
                DisplayType::BLOCK,
                new UserValue(
                    'Bobby (bob)',
                    'avatar.png',
                ),
            ),
            $builder->buildUserFieldWithValue(new ConfiguredField($field, DisplayType::BLOCK), $changeset),
        );
    }
}
