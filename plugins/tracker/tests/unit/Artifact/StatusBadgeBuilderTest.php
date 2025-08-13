<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact;

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class StatusBadgeBuilderTest extends TestCase
{
    public function testEmptyArrayWhenSemanticStatusIsNotDefined(): void
    {
        $semantic_status = $this->createMock(\Tuleap\Tracker\Semantic\Status\TrackerSemanticStatus::class);
        $semantic_status->method('getField')->willReturn(null);

        $status_factory = $this->createMock(\Tuleap\Tracker\Semantic\Status\TrackerSemanticStatusFactory::class);
        $status_factory->method('getByTracker')->willReturn($semantic_status);

        $builder = new StatusBadgeBuilder($status_factory);

        $artifact = \Tuleap\Tracker\Test\Builders\ArtifactTestBuilder::anArtifact(101)->build();

        $badges = $builder->buildBadgesFromArtifactStatus(
            $artifact,
            UserTestBuilder::anActiveUser()->build(),
            static fn(string $label, ?string $color) => new \Tuleap\Search\SearchResultEntryBadge($label, $color)
        );

        self::assertEquals([], $badges);
    }

    public function testEmptyArrayWhenSemanticStatusFieldIsNotReadableByUser(): void
    {
        $field = $this->createMock(\Tuleap\Tracker\FormElement\Field\List\SelectboxField::class);
        $field->method('userCanRead')->willReturn(false);

        $semantic_status = $this->createMock(\Tuleap\Tracker\Semantic\Status\TrackerSemanticStatus::class);
        $semantic_status->method('getField')->willReturn($field);

        $status_factory = $this->createMock(\Tuleap\Tracker\Semantic\Status\TrackerSemanticStatusFactory::class);
        $status_factory->method('getByTracker')->willReturn($semantic_status);

        $builder = new StatusBadgeBuilder($status_factory);

        $artifact = \Tuleap\Tracker\Test\Builders\ArtifactTestBuilder::anArtifact(101)->build();

        $badges = $builder->buildBadgesFromArtifactStatus(
            $artifact,
            UserTestBuilder::anActiveUser()->build(),
            static fn(string $label, ?string $color) => new \Tuleap\Search\SearchResultEntryBadge($label, $color)
        );

        self::assertEquals([], $badges);
    }

    public function testEmptyArrayWhenSemanticStatusIsDefinedOnUsers(): void
    {
        $field = $this->createMock(\Tuleap\Tracker\FormElement\Field\List\SelectboxField::class);
        $field->method('userCanRead')->willReturn(true);

        $semantic_status = $this->createMock(\Tuleap\Tracker\Semantic\Status\TrackerSemanticStatus::class);
        $semantic_status->method('getField')->willReturn($field);
        $semantic_status->method('isFieldBoundToStaticValues')->willReturn(false);

        $status_factory = $this->createMock(\Tuleap\Tracker\Semantic\Status\TrackerSemanticStatusFactory::class);
        $status_factory->method('getByTracker')->willReturn($semantic_status);

        $builder = new StatusBadgeBuilder($status_factory);

        $artifact = \Tuleap\Tracker\Test\Builders\ArtifactTestBuilder::anArtifact(101)->build();

        $badges = $builder->buildBadgesFromArtifactStatus(
            $artifact,
            UserTestBuilder::anActiveUser()->build(),
            static fn(string $label, ?string $color) => new \Tuleap\Search\SearchResultEntryBadge($label, $color)
        );

        self::assertEquals([], $badges);
    }

    public function testEmptyArrayWhenArtifactHasNoValueForTheField(): void
    {
        $field = $this->createMock(\Tuleap\Tracker\FormElement\Field\List\SelectboxField::class);
        $field->method('userCanRead')->willReturn(true);
        $field->method('getId')->willReturn(1100);
        $field->method('getDecorators')->willReturn([]);

        $semantic_status = $this->createMock(\Tuleap\Tracker\Semantic\Status\TrackerSemanticStatus::class);
        $semantic_status->method('getField')->willReturn($field);
        $semantic_status->method('isFieldBoundToStaticValues')->willReturn(true);

        $status_factory = $this->createMock(\Tuleap\Tracker\Semantic\Status\TrackerSemanticStatusFactory::class);
        $status_factory->method('getByTracker')->willReturn($semantic_status);

        $builder = new StatusBadgeBuilder($status_factory);

        $artifact       = \Tuleap\Tracker\Test\Builders\ArtifactTestBuilder::anArtifact(101)->build();
        $last_changeset = \Tuleap\Tracker\Test\Builders\ChangesetTestBuilder::aChangeset(1001)
            ->ofArtifact($artifact)
            ->build();
        $last_changeset->setFieldValue(
            $field,
            new \Tracker_Artifact_ChangesetValue_List(1010, $last_changeset, $field, true, [])
        );
        $artifact->setChangesets([$last_changeset]);

        $badges = $builder->buildBadgesFromArtifactStatus(
            $artifact,
            UserTestBuilder::anActiveUser()->build(),
            static fn(string $label, ?string $color) => new \Tuleap\Search\SearchResultEntryBadge($label, $color)
        );

        self::assertEquals([], $badges);
    }

    public function testBuildBadgeForEachStatusValue(): void
    {
        $field = $this->createMock(\Tuleap\Tracker\FormElement\Field\List\SelectboxField::class);
        $field->method('userCanRead')->willReturn(true);
        $field->method('getId')->willReturn(1100);
        $field->method('getDecorators')->willReturn([]);

        $semantic_status = $this->createMock(\Tuleap\Tracker\Semantic\Status\TrackerSemanticStatus::class);
        $semantic_status->method('getField')->willReturn($field);
        $semantic_status->method('isFieldBoundToStaticValues')->willReturn(true);

        $status_factory = $this->createMock(\Tuleap\Tracker\Semantic\Status\TrackerSemanticStatusFactory::class);
        $status_factory->method('getByTracker')->willReturn($semantic_status);

        $builder = new StatusBadgeBuilder($status_factory);

        $artifact       = \Tuleap\Tracker\Test\Builders\ArtifactTestBuilder::anArtifact(101)->build();
        $last_changeset = \Tuleap\Tracker\Test\Builders\ChangesetTestBuilder::aChangeset(1001)
            ->ofArtifact($artifact)
            ->build();
        $list_values    = [
            ListStaticValueBuilder::aStaticValue('On going')->build(),
            ListStaticValueBuilder::aStaticValue('Other')->build(),
        ];
        $last_changeset->setFieldValue(
            $field,
            new \Tracker_Artifact_ChangesetValue_List(
                1010,
                $last_changeset,
                $field,
                true,
                $list_values
            )
        );
        $artifact->setChangesets([$last_changeset]);

        $badges = $builder->buildBadgesFromArtifactStatus(
            $artifact,
            UserTestBuilder::anActiveUser()->build(),
            static fn(string $label, ?string $color) => new \Tuleap\Search\SearchResultEntryBadge($label, $color)
        );

        self::assertCount(2, $badges);
        self::assertEquals('On going', $badges[0]->label);
        self::assertNull($badges[0]->color);
        self::assertEquals('Other', $badges[1]->label);
        self::assertNull($badges[1]->color);
    }

    public function testBuildBadgeWithTLPColorForEachStatusValue(): void
    {
        $field = $this->createMock(\Tuleap\Tracker\FormElement\Field\List\SelectboxField::class);
        $field->method('userCanRead')->willReturn(true);
        $field->method('getId')->willReturn(1100);
        $field->method('getDecorators')->willReturn([
            2001 => new \Tracker_FormElement_Field_List_BindDecorator(1100, 2001, 255, 0, 0, ''),
            2002 => new \Tracker_FormElement_Field_List_BindDecorator(1100, 2002, null, null, null, 'fiesta-red'),
        ]);

        $semantic_status = $this->createMock(\Tuleap\Tracker\Semantic\Status\TrackerSemanticStatus::class);
        $semantic_status->method('getField')->willReturn($field);
        $semantic_status->method('isFieldBoundToStaticValues')->willReturn(true);

        $status_factory = $this->createMock(\Tuleap\Tracker\Semantic\Status\TrackerSemanticStatusFactory::class);
        $status_factory->method('getByTracker')->willReturn($semantic_status);

        $builder = new StatusBadgeBuilder($status_factory);

        $artifact       = \Tuleap\Tracker\Test\Builders\ArtifactTestBuilder::anArtifact(101)->build();
        $last_changeset = \Tuleap\Tracker\Test\Builders\ChangesetTestBuilder::aChangeset(1001)
            ->ofArtifact($artifact)
            ->build();
        $list_values    = [
            ListStaticValueBuilder::aStaticValue('Value with legacy color')->withId(2001)->build(),
            ListStaticValueBuilder::aStaticValue('Value with TLP color')->withId(2002)->build(),
        ];
        $last_changeset->setFieldValue(
            $field,
            new \Tracker_Artifact_ChangesetValue_List(
                1010,
                $last_changeset,
                $field,
                true,
                $list_values
            )
        );
        $artifact->setChangesets([$last_changeset]);

        $badges = $builder->buildBadgesFromArtifactStatus(
            $artifact,
            UserTestBuilder::anActiveUser()->build(),
            static fn(string $label, ?string $color) => new \Tuleap\Search\SearchResultEntryBadge($label, $color)
        );

        self::assertCount(2, $badges);
        self::assertEquals('Value with legacy color', $badges[0]->label);
        self::assertNull($badges[0]->color);
        self::assertEquals('Value with TLP color', $badges[1]->label);
        self::assertEquals('fiesta-red', $badges[1]->color);
    }
}
