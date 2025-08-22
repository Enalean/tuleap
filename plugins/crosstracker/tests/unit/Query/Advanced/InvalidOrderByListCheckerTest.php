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

namespace Tuleap\CrossTracker\Query\Advanced;

use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\ListField;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Semantic\Contributor\ContributorFieldRetriever;
use Tuleap\Tracker\Semantic\Contributor\TrackerSemanticContributorFactory;
use Tuleap\Tracker\Test\Builders\Fields\CheckboxFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\MultiSelectboxFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\OpenListFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\RadioButtonFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\SelectboxFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveSemanticStatusFieldStub;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class InvalidOrderByListCheckerTest extends TestCase
{
    private RetrieveSemanticStatusFieldStub $status_field_retriever;

    #[\Override]
    protected function setUp(): void
    {
        $this->status_field_retriever = RetrieveSemanticStatusFieldStub::build();
    }

    private function getChecker(): InvalidOrderByListChecker
    {
        return new InvalidOrderByListChecker(
            $this->status_field_retriever,
            new ContributorFieldRetriever(TrackerSemanticContributorFactory::instance()),
        );
    }

    public function testItThrowIfUsedWithNotHandledMetadata(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->withId(45)->build();

        $this->expectException(LogicException::class);
        $this->getChecker()->metadataListIsSortable(new Metadata('title'), [$tracker]);
    }

    public static function generateFields(): iterable
    {
        $tracker = TrackerTestBuilder::aTracker()->withId(903)->build();

        yield 'It allows radio button' => [
            RadioButtonFieldBuilder::aRadioButtonField(101)->inTracker($tracker)->build(),
            $tracker,
            true,
        ];
        yield 'It allows selectbox' => [
            SelectboxFieldBuilder::aSelectboxField(101)->inTracker($tracker)->build(),
            $tracker,
            true,
        ];
        yield 'It rejects checkbox' => [
            CheckboxFieldBuilder::aCheckboxField(101)->inTracker($tracker)->build(),
            $tracker,
            false,
        ];
        yield 'It rejects multi-selectbox' => [
            MultiSelectboxFieldBuilder::aMultiSelectboxField(101)->inTracker($tracker)->build(),
            $tracker,
            false,
        ];
        yield 'It rejects open list' => [
            OpenListFieldBuilder::anOpenListField()->withTracker($tracker)->build(),
            $tracker,
            false,
        ];
    }

    #[DataProvider('generateFields')]
    public function testItAllowsSingleValueListFields(ListField $list, Tracker $tracker, bool $is_allowed): void
    {
        $this->status_field_retriever->withField($list);

        self::assertSame(
            $is_allowed,
            $this->getChecker()->metadataListIsSortable(new Metadata('status'), [$tracker])
        );
    }
}
