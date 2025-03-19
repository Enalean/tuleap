<?php
/**
 * Copyright Enalean (c) 2013 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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
use Cardwall_CardInCellPresenterFactory;
use Cardwall_CardPresenter;
use Cardwall_FieldProviders_IProvideFieldGivenAnArtifact;
use Cardwall_MappingCollection;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CardInCellPresenterFactoryTest extends TestCase
{
    private int $field_id;
    private Cardwall_CardPresenter&MockObject $card_presenter;
    private Cardwall_FieldProviders_IProvideFieldGivenAnArtifact&MockObject $field_provider;

    protected function setUp(): void
    {
        $tracker        = TrackerTestBuilder::aTracker()->build();
        $this->field_id = 77777;
        $field          = ListFieldBuilder::aListField($this->field_id)->withMultipleValues()->build();
        $artifact       = ArtifactTestBuilder::anArtifact(145)->inTracker($tracker)->build();

        $this->card_presenter = $this->createMock(Cardwall_CardPresenter::class);
        $this->card_presenter->method('getArtifact')->willReturn($artifact);

        $this->field_provider = $this->createMock(Cardwall_FieldProviders_IProvideFieldGivenAnArtifact::class);
        $this->field_provider->method('getField')->with($tracker)->willReturn($field);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['_SESSION']);
    }

    public function testItHasACardInCellPresenterWithASemanticStatusFieldId(): void
    {
        $card_in_cell_presenter_factory = new Cardwall_CardInCellPresenterFactory($this->field_provider, new Cardwall_MappingCollection());
        $this->card_presenter->method('getSwimlineId');
        $cell_presenter = $card_in_cell_presenter_factory->getCardInCellPresenter($this->card_presenter);

        self::assertEquals($cell_presenter, new Cardwall_CardInCellPresenter($this->card_presenter, $this->field_id));
    }

    public function testItHasACardInCellPresenterWithSwimLineId(): void
    {
        $swimline_id = 112;
        $this->card_presenter->method('getSwimlineId')->willReturn($swimline_id);

        $mapping_collection = new Cardwall_MappingCollection();

        $card_in_cell_presenter_factory = new Cardwall_CardInCellPresenterFactory($this->field_provider, $mapping_collection);
        $cell_presenter                 = $card_in_cell_presenter_factory->getCardInCellPresenter($this->card_presenter);

        self::assertEquals($cell_presenter, new Cardwall_CardInCellPresenter($this->card_presenter, $this->field_id, $swimline_id));
    }

    public function testItHasACardInCellPresenterWithSwimLineValueCollection(): void
    {
        $swimline_id = 112;
        $this->card_presenter->method('getSwimlineId')->willReturn($swimline_id);

        $mapping_collection = $this->createMock(Cardwall_MappingCollection::class);
        $mapping_collection->method('getSwimLineValues')->with($this->field_id)->willReturn([123, 456]);

        $card_in_cell_presenter_factory = new Cardwall_CardInCellPresenterFactory($this->field_provider, $mapping_collection);
        $cell_presenter                 = $card_in_cell_presenter_factory->getCardInCellPresenter($this->card_presenter);

        self::assertEquals($cell_presenter, new Cardwall_CardInCellPresenter($this->card_presenter, $this->field_id, $swimline_id, [123, 456]));
    }
}
