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

namespace Tuleap\Artidoc\Document\Field\Permissions;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use ProjectUGroup;
use Tracker_Artifact_ChangesetValue_PermissionsOnArtifact;
use Tuleap\Artidoc\Document\Field\ConfiguredField;
use Tuleap\Artidoc\Domain\Document\Section\Field\DisplayType;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\PermissionsOnArtifactFieldWithValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\UserGroupValue;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\PermissionsOnArtifact\PermissionsOnArtifactField;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValuePermissionsOnArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\PermissionsOnArtifactFieldBuilder;
use Tuleap\User\UserGroup\NameTranslator;

#[DisableReturnValueGenerationForTestDoubles]
final class PermissionsOnArtifactFieldWithValueBuilderTest extends TestCase
{
    use GlobalLanguageMock;

    private function buildPermissionsOnArtifactFieldWithValue(
        PermissionsOnArtifactField $field,
        ?Tracker_Artifact_ChangesetValue_PermissionsOnArtifact $value,
    ): PermissionsOnArtifactFieldWithValue {
        return new PermissionsOnArtifactFieldWithValueBuilder()->buildPermissionsOnArtifactFieldWithValue(
            new ConfiguredField($field, DisplayType::BLOCK),
            $value,
        );
    }

    public function testItReturnsEmptyWhenChangesetValueIsNull(): void
    {
        $field = PermissionsOnArtifactFieldBuilder::aPermissionsOnArtifactField(12)->build();

        self::assertEquals(
            new PermissionsOnArtifactFieldWithValue(
                $field->getLabel(),
                DisplayType::BLOCK,
                [],
            ),
            $this->buildPermissionsOnArtifactFieldWithValue($field, null)
        );
    }

    public function testItReturnsEmptyWhenFieldNotUsed(): void
    {
        $field = PermissionsOnArtifactFieldBuilder::aPermissionsOnArtifactField(12)->build();
        $value = ChangesetValuePermissionsOnArtifactTestBuilder::aListOfPermissions(54, ChangesetTestBuilder::aChangeset(85)->build(), $field)
            ->withAllowedUserGroups([
                152                            => 'Custom group',
                ProjectUGroup::PROJECT_MEMBERS => NameTranslator::PROJECT_MEMBERS,
            ])
            ->thatIsNotUsed()->build();

        self::assertEquals(
            new PermissionsOnArtifactFieldWithValue(
                $field->getLabel(),
                DisplayType::BLOCK,
                [],
            ),
            $this->buildPermissionsOnArtifactFieldWithValue($field, $value)
        );
    }

    public function testItReturnsTranslatedUserGroups(): void
    {
        $field = PermissionsOnArtifactFieldBuilder::aPermissionsOnArtifactField(12)->build();
        $value = ChangesetValuePermissionsOnArtifactTestBuilder::aListOfPermissions(54, ChangesetTestBuilder::aChangeset(85)->build(), $field)
            ->withAllowedUserGroups([
                152                            => 'Custom group',
                ProjectUGroup::PROJECT_MEMBERS => NameTranslator::PROJECT_MEMBERS,
            ])
            ->build();

        $GLOBALS['Language']->method('getText')->with(self::isString(), 'ugroup_project_members')->willReturn('Project Members');

        self::assertEquals(
            new PermissionsOnArtifactFieldWithValue(
                $field->getLabel(),
                DisplayType::BLOCK,
                [
                    new UserGroupValue('Custom group'),
                    new UserGroupValue('Project Members'),
                ],
            ),
            $this->buildPermissionsOnArtifactFieldWithValue($field, $value)
        );
    }
}
