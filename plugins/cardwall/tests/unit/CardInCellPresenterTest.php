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
use Cardwall_CardPresenter;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CardInCellPresenterTest extends TestCase
{
    private const int CARD_FIELD_ID = 9999;
    private const int CARD_ID       = 56789;

    private Artifact $artifact;
    private Cardwall_CardInCellPresenter $presenter;
    private Cardwall_CardPresenter&MockObject $card_presenter;

    #[\Override]
    protected function setUp(): void
    {
        $swimline_field_values = [100, 221];
        $swimline_id           = 3;
        $this->artifact        = ArtifactTestBuilder::anArtifact(475)->build();
        $this->card_presenter  = $this->createMock(Cardwall_CardPresenter::class);
        $this->card_presenter->method('getArtifact')->willReturn($this->artifact);
        $this->card_presenter->method('getId')->willReturn(self::CARD_ID);
        $this->presenter = new Cardwall_CardInCellPresenter($this->card_presenter, self::CARD_FIELD_ID, $swimline_id, $swimline_field_values);
    }

    #[\Override]
    protected function tearDown(): void
    {
        unset($GLOBALS['_SESSION']);
    }

    public function testItHasColumnDropInto(): void
    {
        $drop_into = 'drop-into-3-100 drop-into-3-221';
        self::assertEquals($drop_into, $this->presenter->getDropIntoClass());
    }

    public function testItHasCardFieldId(): void
    {
        self::assertEquals(self::CARD_FIELD_ID, $this->presenter->getCardFieldId());
    }

    public function testItHasACardPresenter(): void
    {
        self::assertEquals($this->card_presenter, $this->presenter->getCardPresenter());
    }

    public function testItHasAnArtifact(): void
    {
        self::assertEquals($this->artifact, $this->presenter->getArtifact());
    }

    public function testItHasAnId(): void
    {
        self::assertEquals(self::CARD_ID, $this->presenter->getId());
    }
}
