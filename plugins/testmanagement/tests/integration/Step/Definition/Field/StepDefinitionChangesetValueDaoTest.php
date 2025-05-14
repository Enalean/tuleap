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

namespace Tuleap\TestManagement\Step\Definition\Field;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tuleap\DB\DBFactory;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;
use Tuleap\TestManagement\Step\Step;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class StepDefinitionChangesetValueDaoTest extends TestIntegrationTestCase
{
    public function testItCreatesNoneChangesetValue(): void
    {
        $db              = DBFactory::getMainTuleapDBConnection()->getDB();
        $tracker_builder = new TrackerDatabaseBuilder($db);
        $tracker_builder->buildArtifact(53);
        $tracker_builder->buildArtifact(53);

        $dao = new StepDefinitionChangesetValueDao();
        $ids = $dao->createNoneChangesetValue(53, 125);
        self::assertIsArray($ids);
        $ids = array_unique($ids);
        self::assertCount(2, $ids);

        foreach ($ids as $changeset_value_id) {
            self::assertSame([], $dao->searchById($changeset_value_id));
        }
    }

    public function testItCanInsertSomeStepsAndRetrieveThemAndDeleteThem(): void
    {
        $dao = new StepDefinitionChangesetValueDao();

        self::assertSame([], $dao->searchById(15));

        $steps = [
            new Step(1, '1 + 1', 'commonmark', '2', 'text', 1),
            new Step(2, '2 * 2', 'commonmark', '4', 'text', 2),
        ];
        self::assertTrue($dao->create(15, $steps));

        $retrieved = $dao->searchById(15);
        self::assertCount(2, $retrieved);
        foreach ($retrieved as $i => $step) {
            assert(isset($steps[$i]));
            self::assertSameStep($steps[$i], $step);
        }

        $dao->delete(15);
        self::assertSame([], $dao->searchById(15));
    }

    public function testItCanKeepValueFromChangesetValueToChangesetValue(): void
    {
        $dao = new StepDefinitionChangesetValueDao();

        $steps = [
            new Step(1, '1 + 1', 'commonmark', '2', 'text', 1),
            new Step(2, '2 * 2', 'commonmark', '4', 'text', 2),
        ];
        self::assertTrue($dao->create(15, $steps));
        $dao->keep(15, 16);

        $retrieved = $dao->searchById(16);
        foreach ($retrieved as $i => $step) {
            assert(isset($steps[$i]));
            self::assertSameStep($steps[$i], $step);
        }
    }

    private static function assertSameStep(Step $expected, Step $retrieved): void
    {
        self::assertSame($expected->getDescription(), $retrieved->getDescription());
        self::assertSame($expected->getDescriptionFormat(), $retrieved->getDescriptionFormat());
        self::assertSame($expected->getExpectedResults(), $retrieved->getExpectedResults());
        self::assertSame($expected->getExpectedResultsFormat(), $retrieved->getExpectedResultsFormat());
        self::assertSame($expected->getRank(), $retrieved->getRank());
    }
}
