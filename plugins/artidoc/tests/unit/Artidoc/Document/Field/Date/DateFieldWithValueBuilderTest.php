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

namespace Tuleap\Artidoc\Document\Field\Date;

use DateTimeImmutable;
use Override;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_Date;
use Tracker_FormElement_Field_Date;
use Tuleap\Artidoc\Document\Field\ConfiguredField;
use Tuleap\Artidoc\Domain\Document\Section\Field\DisplayType;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\DateFieldWithValue;
use Tuleap\Option\Option;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueDateTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\DateFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\LastUpdateDateFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\SubmittedOnFieldBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class DateFieldWithValueBuilderTest extends TestCase
{
    private const int CHANGESET_TIMESTAMP = 1234567890;
    private const int ARTIFACT_TIMESTAMP  = 9876543210;

    private Tracker_Artifact_Changeset $changeset;

    #[Override]
    protected function setUp(): void
    {
        $this->changeset = ChangesetTestBuilder::aChangeset(85)
            ->submittedOn(self::CHANGESET_TIMESTAMP)
            ->ofArtifact(ArtifactTestBuilder::anArtifact(98657)->withSubmissionTimestamp(self::ARTIFACT_TIMESTAMP)->build())
            ->build();
    }

    private function buildDateFieldWithValue(
        Tracker_FormElement_Field_Date $field,
        Tracker_Artifact_Changeset $changeset,
        ?Tracker_Artifact_ChangesetValue_Date $value,
    ): DateFieldWithValue {
        $builder = new DateFieldWithValueBuilder(UserTestBuilder::anActiveUser()->withTimezone('Europe/Paris')->build());

        return $builder->buildDateFieldWithValue(new ConfiguredField($field, DisplayType::BLOCK), $changeset, $value);
    }

    public function testItBuildsDateField(): void
    {
        $field     = DateFieldBuilder::aDateField(123)->build();
        $timestamp = 1753660800;
        $value     = ChangesetValueDateTestBuilder::aValue(544, $this->changeset, $field)
            ->withTimestamp($timestamp)
            ->build();

        self::assertEquals(
            new DateFieldWithValue(
                $field->getLabel(),
                DisplayType::BLOCK,
                Option::fromValue(DateTimeImmutable::createFromTimestamp($timestamp)),
                false,
            ),
            $this->buildDateFieldWithValue($field, $this->changeset, $value),
        );
    }

    public function testItBuildsDateTimeField(): void
    {
        $field     = DateFieldBuilder::aDateField(123)->withTime()->build();
        $timestamp = 1753694986;
        $value     = ChangesetValueDateTestBuilder::aValue(544, $this->changeset, $field)
            ->withTimestamp($timestamp)
            ->build();

        self::assertEquals(
            new DateFieldWithValue(
                $field->getLabel(),
                DisplayType::BLOCK,
                Option::fromValue(DateTimeImmutable::createFromTimestamp($timestamp)),
                true,
            ),
            $this->buildDateFieldWithValue($field, $this->changeset, $value),
        );
    }

    public function testItBuildsLastUpdateDateField(): void
    {
        $field = LastUpdateDateFieldBuilder::aLastUpdateDateField(123)->build();
        $value = ChangesetValueDateTestBuilder::aValue(544, $this->changeset, $field)->build();

        self::assertEquals(
            new DateFieldWithValue(
                $field->getLabel(),
                DisplayType::BLOCK,
                Option::fromValue(DateTimeImmutable::createFromTimestamp(self::CHANGESET_TIMESTAMP)),
                true,
            ),
            $this->buildDateFieldWithValue($field, $this->changeset, $value),
        );
    }

    public function testItBuildsSubmittedOnField(): void
    {
        $field = SubmittedOnFieldBuilder::aSubmittedOnField(123)->build();
        $value = ChangesetValueDateTestBuilder::aValue(544, $this->changeset, $field)->build();

        self::assertEquals(
            new DateFieldWithValue(
                $field->getLabel(),
                DisplayType::BLOCK,
                Option::fromValue(DateTimeImmutable::createFromTimestamp(self::ARTIFACT_TIMESTAMP)),
                true,
            ),
            $this->buildDateFieldWithValue($field, $this->changeset, $value),
        );
    }
}
