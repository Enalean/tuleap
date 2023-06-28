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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalResponseMock;
use Tuleap\Tracker\Artifact\Artifact;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class Cardwall_CardControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;
    use GlobalResponseMock;

    protected function setUp(): void
    {
        parent::setUp();
        ForgeConfig::set('codendi_dir', __DIR__ . '/../../../..');
    }

    public function testItReturnsJson(): void
    {
        $artifact_id    = 55;
        $artifact_title = 'bla';
        $cross_ref      = 'task #22';
        $edit_url       = 'edit';
        $accent_color   = 'rgb(12,12,12)';
        $swimline_id    = 215;
        $drop_into      = ['5', '7'];

        $artifact = Mockery::mock(Artifact::class);
        $artifact->shouldReceive('getId')->andReturn($artifact_id);
        $artifact->shouldReceive('getLastChangeset')->andReturn(Mockery::spy(Tracker_Artifact_Changeset::class));
        $card_fields = \Mockery::spy(\Cardwall_CardFields::class);

        $field1 = \Mockery::spy(\Tracker_FormElement_Field_Float::class);
        $field2 = \Mockery::spy(\Tracker_FormElement_Field_Selectbox::class);
        $field3 = \Mockery::spy(\Tracker_FormElement_Field_Selectbox::class);

        $field1->shouldReceive('getJsonValue')->andReturns(5.1);
        $field2->shouldReceive('getJsonValue')->andReturns([101, 201]);
        $field3->shouldReceive('getJsonValue')->andReturns(236);

        $field1->shouldReceive('fetchCardValue')->andReturns("5.1");
        $field2->shouldReceive('fetchCardValue')->andReturns('<a href');
        $field3->shouldReceive('fetchCardValue')->andReturns('<span>Decorator</span>');

        $field1->shouldReceive('getName')->andReturns(Tracker::REMAINING_EFFORT_FIELD_NAME);
        $field2->shouldReceive('getName')->andReturns('assigned_to');
        $field3->shouldReceive('getName')->andReturns('impediment');

        $card_presenter = \Mockery::spy(\Cardwall_CardPresenter::class);
        $card_presenter->shouldReceive('getTitle')->andReturns($artifact_title);
        $card_presenter->shouldReceive('getXRef')->andReturns($cross_ref);
        $card_presenter->shouldReceive('getEditUrl')->andReturns($edit_url);
        $card_presenter->shouldReceive('getAccentColor')->andReturns($accent_color);
        $card_presenter->shouldReceive('getSwimlineId')->andReturns($swimline_id);

        $card_in_cell_presenter = \Mockery::spy(\Cardwall_CardInCellPresenter::class);
        $card_in_cell_presenter->shouldReceive('getCardPresenter')->andReturns($card_presenter);
        $card_in_cell_presenter->shouldReceive('getDropIntoIds')->andReturns($drop_into);
        $card_in_cell_presenter->shouldReceive('getArtifact')->andReturns($artifact);

        $card_fields->shouldReceive('getFields')->andReturns([$field1, $field2, $field3]);

        $single_card = new Cardwall_SingleCard($card_in_cell_presenter, $card_fields, \Mockery::spy(\Cardwall_UserPreferences_UserPreferencesDisplayUser::class), 1111, \Mockery::spy(\Cardwall_OnTop_Config_TrackerMapping::class));

        $request = Mockery::mock(Codendi_Request::class);
        $request->shouldReceive('getCurrentUser')->andReturn(\Mockery::spy(\PFUser::class));
        $card_controller = new Cardwall_CardController(
            $request,
            $single_card
        );

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

        $GLOBALS['Response']->expects(self::once())->method('sendJSON')->with($expected);

        $card_controller->getCard();
    }
}
