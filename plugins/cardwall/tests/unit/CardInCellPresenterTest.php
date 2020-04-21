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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
final class CardInCellPresenterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const CARD_FIELD_ID = 9999;
    private const CARD_ID       = 56789;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact
     */
    private $artifact;
    /**
     * @var Cardwall_CardInCellPresenter
     */
    private $presenter;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Cardwall_CardPresenter
     */
    private $card_presenter;

    protected function setUp(): void
    {
        parent::setUp();
        $swimline_field_values   = array(100, 221);
        $swimline_id             = 3;
        $this->artifact          = \Mockery::mock(\Tracker_Artifact::class);
        $this->card_presenter    = \Mockery::mock(\Cardwall_CardPresenter::class)->shouldReceive('getArtifact')->andReturns($this->artifact)->getMock();
        $this->card_presenter->shouldReceive('getId')->andReturns(self::CARD_ID);
        $this->presenter         = new Cardwall_CardInCellPresenter($this->card_presenter, self::CARD_FIELD_ID, $swimline_id, $swimline_field_values);
    }

    public function testItHasColumnDropInto(): void
    {
        $drop_into               = 'drop-into-3-100 drop-into-3-221';
        $this->assertEquals($drop_into, $this->presenter->getDropIntoClass());
    }

    public function testItHasCardFieldId(): void
    {
        $this->assertEquals(self::CARD_FIELD_ID, $this->presenter->getCardFieldId());
    }

    public function testItHasACardPresenter(): void
    {
        $this->assertEquals($this->card_presenter, $this->presenter->getCardPresenter());
    }

    public function testItHasAnArtifact(): void
    {
        $this->assertEquals($this->artifact, $this->presenter->getArtifact());
    }

    public function testItHasAnId(): void
    {
        $this->assertEquals(self::CARD_ID, $this->presenter->getId());
    }
}
