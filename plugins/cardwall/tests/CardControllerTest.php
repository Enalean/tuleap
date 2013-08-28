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

class Cardwall_CardControllerTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        Config::store();
        Config::set('codendi_dir', dirname(__FILE__).'/../../..');
    }

    public function tearDown() {
        Config::restore();
        parent::tearDown();
    }

    public function itReturnsJson() {
        $artifact_id    = 55;
        $artifact_title = 'bla';
        $cross_ref      = 'task #22';
        $edit_url       = 'edit';
        $accent_color   = 'rgb(12,12,12)';
        $swimline_id    = 215;
        $drop_into      = array('drop-into-1234-5', 'drop-into-1234-7');

        $column1 = new Cardwall_Column(7922, 'New', 'white', 'black');
        $column2 = new Cardwall_Column(1111, 'Verified', 'white', 'black');
        $columns = new Cardwall_OnTop_Config_ColumnCollection(array($column1, $column2));

        $artifact = aMockArtifact()->withId($artifact_id)->build();
        $config = mock('Cardwall_OnTop_Config');
        $field_retriever = mock('Cardwall_FieldProviders_IProvideFieldGivenAnArtifact');
        $card_fields = mock('Cardwall_CardFields');

        $card_controller = partial_mock(
            'Cardwall_CardController',
            array('getCardInCellPresenter'),
            array(
                mock('Codendi_Request'),
                $artifact,
                $card_fields,
                mock('Cardwall_UserPreferences_UserPreferencesDisplayUser'),
                $config,
                $field_retriever,
                mock('Cardwall_CardInCellPresenterFactory'),
                $columns
            )
        );

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
        stub($card_in_cell_presenter)->getDropIntoClasses()->returns($drop_into);

        stub($card_controller)->getCardInCellPresenter()->returns($card_in_cell_presenter);

        stub($config)->isInColumn($artifact, $field_retriever, $column2)->returns(true);
        stub($card_fields)->getFields()->returns(array($field1, $field2, $field3));

        $expected = array(
            $artifact_id => array(
                'title'        => $artifact_title,
                'xref'         => $cross_ref,
                'edit_url'     => $edit_url,
                'accent_color' => $accent_color,
                'swimline_id'  => $swimline_id,
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

?>
