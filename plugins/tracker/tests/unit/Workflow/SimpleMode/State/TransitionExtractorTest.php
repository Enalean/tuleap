<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Workflow\SimpleMode\State;

use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;
use Tuleap\Tracker\Workflow\Transition\NoTransitionForStateException;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TransitionExtractorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private TransitionExtractor $extractor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->extractor = new TransitionExtractor();
    }

    public function testExtractsFirstTransitionNotFromNewFromStateObject(): void
    {
        $transition_from_new   = $this->createMock(\Transition::class);
        $transition_from_value = $this->createMock(\Transition::class);

        $transition_from_new->method('getIdFrom')->willReturn('');
        $transition_from_value->method('getIdFrom')->willReturn('210');

        $state = new State(1, [$transition_from_new, $transition_from_value]);

        self::assertSame(
            $transition_from_value,
            $this->extractor->extractReferenceTransitionFromState($state)
        );
    }

    public function testExtractsTransitionFromNewFromStateObjectIfThisTransitionIsTheOnlyOne(): void
    {
        $transition_from_new = $this->createMock(\Transition::class);
        $transition_from_new->method('getIdFrom')->willReturn('');

        $state = new State(1, [1238 => $transition_from_new]);

        self::assertSame(
            $transition_from_new,
            $this->extractor->extractReferenceTransitionFromState($state)
        );
    }

    public function testThrowsAnExceptionIfNoTransition(): void
    {
        $state = new State(1, []);

        $this->expectException(NoTransitionForStateException::class);

        $this->extractor->extractReferenceTransitionFromState($state);
    }

    public function testRetrievesSiblingsTransitionsInState(): void
    {
        $value_01 = ListStaticValueBuilder::aStaticValue('value 1')->withId(101)->build();
        $value_02 = ListStaticValueBuilder::aStaticValue('value 2')->withId(102)->build();
        $value_03 = ListStaticValueBuilder::aStaticValue('value 3')->withId(103)->build();

        $transition_01 = new \Transition(1, 1, $value_01, $value_02);
        $transition_02 = new \Transition(2, 1, $value_01, $value_03);

        $state = new State(1, [$transition_01, $transition_02]);

        self::assertSame(
            [$transition_02],
            $this->extractor->extractSiblingTransitionsFromState($state, $transition_01)
        );
    }

    public function testReturnsEmptyArrayIfNoSiblingsTransitionsInState(): void
    {
        $value_01 = ListStaticValueBuilder::aStaticValue('value 1')->withId(101)->build();
        $value_02 = ListStaticValueBuilder::aStaticValue('value 2')->withId(102)->build();

        $transition_01 = new \Transition(1, 1, $value_01, $value_02);

        $state = new State(1, [$transition_01]);

        self::assertSame(
            [],
            $this->extractor->extractSiblingTransitionsFromState($state, $transition_01)
        );
    }
}
