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

namespace Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Metadata;

use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\IProvideParametrizedSelectAndFromSQLFragments;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Metadata\Semantic\AssignedTo\AssignedToSelectFromBuilder;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Metadata\Semantic\Description\DescriptionSelectFromBuilder;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Metadata\Semantic\Status\StatusSelectFromBuilder;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Metadata\Semantic\Title\TitleSelectFromBuilder;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Metadata\Special\LinkType\ForwardLinkTypeSelectFromBuilder;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Metadata\Special\PrettyTitle\PrettyTitleSelectFromBuilder;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Metadata\Special\ProjectName\ProjectNameSelectFromBuilder;
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
            new ForwardLinkTypeSelectFromBuilder(),
        );

        return $builder->getSelectFrom($metadata);
    }

    public function testItThrowsIfMetadataNotRecognized(): void
    {
        self::expectException(LogicException::class);
        $this->getSelectFrom(new Metadata('not-existing'));
    }

    #[DataProvider('metadataForSelectAndFromStatement')]
    public function testItReturnsSQLForSelectAndFromSemantic(string $metadata_name): void
    {
        $result = $this->getSelectFrom(new Metadata($metadata_name));
        self::assertNotEmpty($result->getSelect());
        self::assertNotEmpty($result->getFrom());
    }

    #[DataProvider('metadataForSelectStatement')]
    public function testItReturnsSQLForSelectOnlySemantic(string $metadata_name): void
    {
        $result = $this->getSelectFrom(new Metadata($metadata_name));
        self::assertNotEmpty($result->getSelect());
    }

    public function testItReturnsSEmptyForLinkType(): void
    {
        $result = $this->getSelectFrom(new Metadata('link_type'));
        self::assertEmpty($result->getSelect());
    }

    public static function metadataForSelectStatement(): array
    {
        return ['submitted_on' => ['submitted_on'], 'last_update_date' => ['last_update_date'], 'submitted_by' => ['submitted_by'], 'last_update_by' => ['last_update_by'], 'id' => ['id'], 'tracker.name' => ['tracker.name']];
    }

    public static function metadataForSelectAndFromStatement(): array
    {
        return ['title' => ['title'], 'description' => ['description'], 'status' => ['status'], 'assigned_to' => ['assigned_to'], 'project.name' => ['project.name'], 'pretty_title' => ['pretty_title']];
    }
}
