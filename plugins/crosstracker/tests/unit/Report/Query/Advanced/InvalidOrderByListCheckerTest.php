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

namespace Tuleap\CrossTracker\Report\Query\Advanced;

use LogicException;
use Tracker_FormElement_Field_List;
use Tracker_Semantic_ContributorFactory;
use Tracker_Semantic_Status;
use Tracker_Semantic_StatusFactory;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Semantic\Contributor\ContributorFieldRetriever;
use Tuleap\Tracker\Semantic\Status\StatusFieldRetriever;
use Tuleap\Tracker\Test\Builders\Fields\CheckboxFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\OpenListFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\RadioButtonFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class InvalidOrderByListCheckerTest extends TestCase
{
    protected function tearDown(): void
    {
        Tracker_Semantic_Status::clearInstances();
    }

    public function testItThrowIfUsedWithNotHandledMetadata(): void
    {
        $checker = new InvalidOrderByListChecker(
            new StatusFieldRetriever(Tracker_Semantic_StatusFactory::instance()),
            new ContributorFieldRetriever(Tracker_Semantic_ContributorFactory::instance()),
        );
        self::expectException(LogicException::class);
        $checker->metadataListIsSortable(new Metadata('title'), [TrackerTestBuilder::aTracker()->build()]);
    }

    public static function generateFields(): iterable
    {
        yield 'It allows radio button' => [RadioButtonFieldBuilder::aRadioButtonField(101)->build(), true];
        yield 'It allows selectbox' => [ListFieldBuilder::aListField(101)->build(), true];
        yield 'It rejects checkbox' => [CheckboxFieldBuilder::aCheckboxField(101)->build(), false];
        yield 'It rejects multi-selectbox' => [ListFieldBuilder::aListField(101)->withMultipleValues()->build(), false];
        yield 'It rejects open list' => [OpenListFieldBuilder::anOpenListField()->build(), false];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('generateFields')]
    public function testItAllowsSingleValueListFields(Tracker_FormElement_Field_List $list, bool $is_allowed): void
    {
        $checker = new InvalidOrderByListChecker(
            new StatusFieldRetriever(Tracker_Semantic_StatusFactory::instance()),
            new ContributorFieldRetriever(Tracker_Semantic_ContributorFactory::instance()),
        );
        $tracker = TrackerTestBuilder::aTracker()->withId(45)->build();
        Tracker_Semantic_Status::setInstance(
            new Tracker_Semantic_Status($tracker, $list),
            $tracker,
        );
        self::assertSame($is_allowed, $checker->metadataListIsSortable(new Metadata('status'), [$tracker]));
    }
}
