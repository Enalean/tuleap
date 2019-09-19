<?php
/**
 * Copyright Enalean (c) 2013. All rights reserved.
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

require_once dirname(__FILE__) .'/bootstrap.php';
require_once dirname(__FILE__) .'/../../agiledashboard/include/Planning/PlanningFactory.class.php';

class Cardwall_CardControllerTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();
        ForgeConfig::store();
        ForgeConfig::set('codendi_dir', dirname(__FILE__).'/../../..');
    }

    public function tearDown()
    {
        ForgeConfig::restore();
        parent::tearDown();
    }

    public function itReturnsJson()
    {
        $artifact_id    = 55;
        $artifact_title = 'bla';
        $cross_ref      = 'task #22';
        $edit_url       = 'edit';
        $accent_color   = 'rgb(12,12,12)';
        $swimline_id    = 215;
        $drop_into      = array('5', '7');

        $artifact    = aMockArtifact()->withId($artifact_id)->withlastChangeset(Mockery::spy(Tracker_Artifact_Changeset::class))->build();
        $card_fields = mock('Cardwall_CardFields');

        $field1 = mock('Tracker_FormElement_Field_Float');
        $field2 = mock('Tracker_FormElement_Field_Selectbox');
        $field3 = mock('Tracker_FormElement_Field_Selectbox');

        stub($field1)->getJsonValue()->returns(5.1);
        stub($field2)->getJsonValue()->returns(array(101, 201));
        stub($field3)->getJsonValue()->returns(236);

        stub($field1)->fetchCardValue()->returns("5.1");
        stub($field2)->fetchCardValue()->returns('<a href');
        stub($field3)->fetchCardValue()->returns('<span>Decorator</span>');

        stub($field1)->getName()->returns(Tracker::REMAINING_EFFORT_FIELD_NAME);
        stub($field2)->getName()->returns(Tracker::ASSIGNED_TO_FIELD_NAME);
        stub($field3)->getName()->returns(Tracker::IMPEDIMENT_FIELD_NAME);

        $card_presenter = mock('Cardwall_CardPresenter');
        stub($card_presenter)->getTitle()->returns($artifact_title);
        stub($card_presenter)->getXRef()->returns($cross_ref);
        stub($card_presenter)->getEditUrl()->returns($edit_url);
        stub($card_presenter)->getAccentColor()->returns($accent_color);
        stub($card_presenter)->getSwimlineId()->returns($swimline_id);

        $card_in_cell_presenter = mock('Cardwall_CardInCellPresenter');
        stub($card_in_cell_presenter)->getCardPresenter()->returns($card_presenter);
        stub($card_in_cell_presenter)->getDropIntoIds()->returns($drop_into);
        stub($card_in_cell_presenter)->getArtifact()->returns($artifact);

        stub($card_fields)->getFields()->returns(array($field1, $field2, $field3));

        $single_card = new Cardwall_SingleCard($card_in_cell_presenter, $card_fields, mock('Cardwall_UserPreferences_UserPreferencesDisplayUser'), 1111, mock('Cardwall_OnTop_Config_TrackerMapping'));

        $card_controller = new Cardwall_CardController(
            aRequest()->withUser(mock('PFUser'))->build(),
            $single_card
        );

        $expected = array(
            $artifact_id => array(
                'title'        => $artifact_title,
                'xref'         => $cross_ref,
                'edit_url'     => $edit_url,
                'accent_color' => $accent_color,
                'column_id'    => 1111,
                'drop_into'    => $drop_into,
                'fields'       => array(
                    'remaining_effort' => 5.1,
                    'assigned_to'      => array(101, 201),
                    'impediment'       => 236
                ),
                'html_fields'  => array(
                    'remaining_effort' => '5.1',
                    'assigned_to'      => '<a href',
                    'impediment'       => '<span>Decorator</span>'
                ),
            )
        );

        expect($GLOBALS['Response'])->sendJSON($expected)->once();

        $card_controller->getCard();
    }
}
