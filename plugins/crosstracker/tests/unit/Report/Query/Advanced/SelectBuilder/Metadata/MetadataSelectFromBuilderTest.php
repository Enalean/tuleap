<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Metadata;

use LogicException;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\IProvideParametrizedSelectAndFromSQLFragments;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Metadata\Semantic\AssignedTo\AssignedToSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Metadata\Semantic\Description\DescriptionSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Metadata\Semantic\Status\StatusSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Metadata\Semantic\Title\TitleSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Metadata\Special\PrettyTitle\PrettyTitleSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Metadata\Special\ProjectName\ProjectNameSelectFromBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MetadataSelectFromBuilderTest extends TestCase
{
    private function getSelectFrom(Metadata $metadata): IProvideParametrizedSelectAndFromSQLFragments
    {
        $builder = new MetadataSelectFromBuilder(
            new TitleSelectFromBuilder(),
            new DescriptionSelectFromBuilder(),
            new StatusSelectFromBuilder(),
            new AssignedToSelectFromBuilder(),
            new ProjectNameSelectFromBuilder(),
            new PrettyTitleSelectFromBuilder(),
        );

        return $builder->getSelectFrom($metadata);
    }

    public function testItThrowsIfMetadataNotRecognized(): void
    {
        self::expectException(LogicException::class);
        $this->getSelectFrom(new Metadata('not-existing'));
    }

    public function testItReturnsSQLForTitleSemantic(): void
    {
        $result = $this->getSelectFrom(new Metadata('title'));
        self::assertNotEmpty($result->getSelect());
        self::assertNotEmpty($result->getFrom());
    }

    public function testItReturnsSQLForDescriptionSemantic(): void
    {
        $result = $this->getSelectFrom(new Metadata('description'));
        self::assertNotEmpty($result->getSelect());
        self::assertNotEmpty($result->getFrom());
    }

    public function testItReturnsSQLForStatusSemantic(): void
    {
        $result = $this->getSelectFrom(new Metadata('status'));
        self::assertNotEmpty($result->getSelect());
        self::assertNotEmpty($result->getFrom());
    }

    public function testItReturnsSQLForAssignedToSemantic(): void
    {
        $result = $this->getSelectFrom(new Metadata('assigned_to'));
        self::assertNotEmpty($result->getSelect());
        self::assertNotEmpty($result->getFrom());
    }

    public function testItReturnsSQLForSubmittedOnAlwaysThereField(): void
    {
        $result = $this->getSelectFrom(new Metadata('submitted_on'));
        self::assertNotEmpty($result->getSelect());
    }

    public function testItReturnsSQLForLastUpdateDateAlwaysThereField(): void
    {
        $result = $this->getSelectFrom(new Metadata('last_update_date'));
        self::assertNotEmpty($result->getSelect());
    }

    public function testItReturnsSQLForSubmittedByAlwaysThereField(): void
    {
        $result = $this->getSelectFrom(new Metadata('submitted_by'));
        self::assertNotEmpty($result->getSelect());
    }

    public function testItReturnsSQLForLastUpdateByAlwaysThereField(): void
    {
        $result = $this->getSelectFrom(new Metadata('last_update_by'));
        self::assertNotEmpty($result->getSelect());
    }

    public function testItReturnsSQLForArtifactIdAlwaysThereField(): void
    {
        $result = $this->getSelectFrom(new Metadata('id'));
        self::assertNotEmpty($result->getSelect());
    }

    public function testItReturnsSQLForProjectName(): void
    {
        $result = $this->getSelectFrom(new Metadata('project.name'));
        self::assertNotEmpty($result->getSelect());
        self::assertNotEmpty($result->getFrom());
    }

    public function testItReturnsSQLForTrackerName(): void
    {
        $result = $this->getSelectFrom(new Metadata('tracker.name'));
        self::assertNotEmpty($result->getSelect());
    }

    public function testItReturnsSQLForPrettyTitle(): void
    {
        $result = $this->getSelectFrom(new Metadata('pretty_title'));
        self::assertNotEmpty($result->getSelect());
        self::assertNotEmpty($result->getFrom());
    }
}
