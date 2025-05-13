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
        $ids = array_unique($dao->createNoneChangesetValue(53, 125));
        self::assertCount(2, $ids);

        foreach ($ids as $changeset_value_id) {
            $dar = $dao->searchById($changeset_value_id);
            self::assertSame(0, $dar->rowCount());
        }
    }

    public function testItCanInsertSomeStepsAndRetrieveThem(): void
    {
        $dao = new StepDefinitionChangesetValueDao();

        self::assertCount(0, $dao->searchById(15));

        $steps = [
            new Step(1, '1 + 1', 'commonmark', '2', 'text', 1),
            new Step(2, '2 * 2', 'commonmark', '4', 'text', 2),
        ];
        self::assertTrue($dao->create(15, $steps));

        $retrieved = $dao->searchById(15);
        self::assertNotNull($retrieved);
        self::assertCount(2, $retrieved);
        foreach ($retrieved as $i => $row) {
            self::assertSameStep($steps[$i], $row);
        }
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
        self::assertNotNull($retrieved);
        foreach ($retrieved as $i => $row) {
            self::assertSameStep($steps[$i], $row);
        }
    }

    /**
     * @param array{
     *      id: int,
     *      description: string,
     *      description_format: string,
     *      expected_results: string,
     *      expected_results_format: string,
     *  } $retrieved
     */
    private static function assertSameStep(Step $expected, array $retrieved): void
    {
        self::assertSame($expected->getDescription(), $retrieved['description']);
        self::assertSame($expected->getDescriptionFormat(), $retrieved['description_format']);
        self::assertSame($expected->getExpectedResults(), $retrieved['expected_results']);
        self::assertSame($expected->getExpectedResultsFormat(), $retrieved['expected_results_format']);
    }
}
