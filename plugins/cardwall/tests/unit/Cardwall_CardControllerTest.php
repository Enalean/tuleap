<?php
/**
 * Copyright Enalean (c) 2013-Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
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

use Cardwall_CardController;
use Cardwall_CardFields;
use Cardwall_CardInCellPresenter;
use Cardwall_CardPresenter;
use Cardwall_OnTop_Config_TrackerMapping;
use Cardwall_SingleCard;
use Cardwall_UserPreferences_UserPreferencesDisplayUser;
use Tracker_Artifact_Changeset_Null;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\Float\FloatField;
use Tuleap\Tracker\FormElement\Field\List\SelectboxField;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Cardwall_CardControllerTest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotPascalCase
{
    use GlobalResponseMock;

    public function testItReturnsJson(): void
    {
        $artifact_id    = 55;
        $artifact_title = 'bla';
        $cross_ref      = 'task #22';
        $edit_url       = 'edit';
        $accent_color   = 'rgb(12,12,12)';
        $swimline_id    = 215;
        $drop_into      = ['5', '7'];

        $artifact    = ArtifactTestBuilder::anArtifact($artifact_id)
            ->withChangesets(new Tracker_Artifact_Changeset_Null())
            ->build();
        $card_fields = $this->createMock(Cardwall_CardFields::class);

        $field1 = $this->createMock(FloatField::class);
        $field2 = $this->createMock(SelectboxField::class);
        $field3 = $this->createMock(SelectboxField::class);

        $field1->method('getJsonValue')->willReturn(5.1);
        $field2->method('getJsonValue')->willReturn([101, 201]);
        $field3->method('getJsonValue')->willReturn(236);

        $field1->method('fetchCardValue')->willReturn('5.1');
        $field2->method('fetchCardValue')->willReturn('<a href');
        $field3->method('fetchCardValue')->willReturn('<span>Decorator</span>');

        $field1->method('getName')->willReturn(Tracker::REMAINING_EFFORT_FIELD_NAME);
        $field2->method('getName')->willReturn('assigned_to');
        $field3->method('getName')->willReturn('impediment');

        $card_presenter = $this->createMock(Cardwall_CardPresenter::class);
        $card_presenter->method('getTitle')->willReturn($artifact_title);
        $card_presenter->method('getXRef')->willReturn($cross_ref);
        $card_presenter->method('getEditUrl')->willReturn($edit_url);
        $card_presenter->method('getAccentColor')->willReturn($accent_color);
        $card_presenter->method('getSwimlineId')->willReturn($swimline_id);

        $card_in_cell_presenter = $this->createMock(Cardwall_CardInCellPresenter::class);
        $card_in_cell_presenter->method('getCardPresenter')->willReturn($card_presenter);
        $card_in_cell_presenter->method('getDropIntoIds')->willReturn($drop_into);
        $card_in_cell_presenter->method('getArtifact')->willReturn($artifact);

        $card_fields->method('getFields')->willReturn([$field1, $field2, $field3]);

        $single_card = new Cardwall_SingleCard(
            $card_in_cell_presenter,
            $card_fields,
            $this->createMock(Cardwall_UserPreferences_UserPreferencesDisplayUser::class),
            1111,
            $this->createMock(Cardwall_OnTop_Config_TrackerMapping::class),
        );

        $request         = HTTPRequestBuilder::get()->withUser(UserTestBuilder::buildWithDefaults())->build();
        $card_controller = new Cardwall_CardController($request, $single_card);

        $expected = [
            $artifact_id => [
                'title'        => $artifact_title,
                'xref'         => $cross_ref,
                'edit_url'     => $edit_url,
                'accent_color' => $accent_color,
                'column_id'    => 1111,
                'drop_into'    => $drop_into,
                'fields'       => [
                    'remaining_effort' => 5.1,
                    'assigned_to'      => [101, 201],
                    'impediment'       => 236,
                ],
                'html_fields'  => [
                    'remaining_effort' => '5.1',
                    'assigned_to'      => '<a href',
                    'impediment'       => '<span>Decorator</span>',
                ],
            ],
        ];

        $GLOBALS['Response']->expects($this->once())->method('sendJSON')->with($expected);

        $card_controller->getCard();
    }
}
