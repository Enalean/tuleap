<?php
/**
* Copyright Enalean (c) 2013. All rights reserved.
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

class Cardwall_Semantic_CardFields extends Tracker_Semantic {
    const NAME = 'card_fields';

    /** @var array */
    private $card_fields = array();

    /** @var array
     * instances of this semantic
     */
    protected static $_instances;

    public function __construct(Tracker $tracker) {
        parent::__construct($tracker);
    }

    public function display() {
        $html   = '';
        $html_purifier     = Codendi_HTMLPurifier::instance();
        $fields = $this->getFields();
        $html .= '<p>';
        if (!count($fields)) {
            $html .= $GLOBALS['Language']->getText('plugin_cardwall','semantic_cardFields_no_fields_defined');
        } else {
            $html .= $GLOBALS['Language']->getText('plugin_cardwall','semantic_cardFields_fields');
            $html .= '<ul>';
            foreach($fields as $field) {
                $html .=  '<li><strong>'. $html_purifier->purify($field->getLabel(), CODENDI_PURIFIER_CONVERT_HTML) .'</strong></li>';
            }
            $html .= '</ul>';
        }
        $html .= '</p>';
        echo $html;
    }

    private function getFields() {
        if (! $this->card_fields) {
            $this->loadFieldsFromTracker($this->tracker);
        }

        return $this->card_fields;
    }

    public function displayAdmin(Tracker_SemanticManager $sm, TrackerManager $tracker_manager, Codendi_Request $request, PFUser $current_user) {
    }

    public function exportToXml(SimpleXMLElement $root, $xmlMapping) {
    }

    public function getDescription() {
        return $GLOBALS['Language']->getText('plugin_cardwall','semantic_cardFields_description');
    }

    public function getLabel() {
        return $GLOBALS['Language']->getText('plugin_cardwall','semantic_cardFields_label');
    }

    public function getShortName() {
        return self::NAME;
    }

    public function isUsedInSemantics($field) {
        $card_fields = $this->getFields();

        foreach ($card_fields as $card_field) {
            if ($card_field->getId() == $field->getId()) {
                return true;
            }
        }

        return false;
    }

    public function process(Tracker_SemanticManager $sm, TrackerManager $tracker_manager, Codendi_Request $request, PFUser $current_user) {

    }

    public function save() {

    }

    /**
     * Load an instance of a Cardwall_Semantic_CardFields
     *
     * @param Tracker $tracker
     * @return Cardwall_Semantic_CardFields
     */
    public static function load(Tracker $tracker) {
        if (!isset(self::$_instances[$tracker->getId()])) {
            self::$_instances[$tracker->getId()] = new Cardwall_Semantic_CardFields($tracker);
        }
        return self::$_instances[$tracker->getId()];
    }

    private function loadFieldsFromTracker(Tracker $tracker) {
        $dao      = new Cardwall_Semantic_Dao_CardFieldsDao();

        $this->card_fields = $dao->searchByTrackerId($tracker->getId())->instanciateWith(array($this, 'instantiateFieldFromRow'));
    }

    /**
     * @return Tracker_FormElement_Field
     */
    public function instantiateFieldFromRow(array $row) {
        return Tracker_FormElementFactory::instance()->getFieldById($row['field_id']);
    }
}
?>
