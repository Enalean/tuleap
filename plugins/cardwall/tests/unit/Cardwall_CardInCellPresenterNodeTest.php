<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

namespace Tuleap\Cardwall;

use Cardwall_CardInCellPresenter;
use Cardwall_CardInCellPresenterNode;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Cardwall_CardInCellPresenterNodeTest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    public function testItHoldsTheGivenPresenter(): void
    {
        $presenter = $this->createMock(Cardwall_CardInCellPresenter::class);
        $presenter->method('getId');
        $presenter_node = new Cardwall_CardInCellPresenterNode($presenter);
        self::assertEquals($presenter, $presenter_node->getCardInCellPresenter());
    }

    public function testItHasAnArtifact(): void
    {
        $artifact  = ArtifactTestBuilder::anArtifact(25)->build();
        $presenter = $this->createMock(Cardwall_CardInCellPresenter::class);
        $presenter->method('getId');
        $presenter->method('getArtifact')->willReturn($artifact);
        $presenter_node = new Cardwall_CardInCellPresenterNode($presenter);
        self::assertEquals($artifact, $presenter_node->getArtifact());
    }
}
