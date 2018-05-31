<?php
/**
 * Copyright Enalean (c) 2013-2018. All rights reserved.
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

use Tuleap\Cardwall\Semantic\BackgroundColorSelectorPresenter;
use Tuleap\Cardwall\Semantic\CardFieldsTrackerPresenterBuilder;

class Cardwall_Semantic_CardFields extends Tracker_Semantic
{
    const NAME = 'plugin_cardwall_card_fields';
    /**
     * @var CardFieldsTrackerPresenterBuilder
     */
    private $card_fields_tracker_presenter_builder;
    /**
     * @var Codendi_HTMLPurifier
     */
    private $html_purifier;

    /** @var Tracker_FormElement_Field[] */
    private $card_fields = array();

    /** @var array
     * instances of this semantic
     */
    protected static $_instances;

    /** @var Cardwall_Semantic_Dao_CardFieldsDao */
    private $dao;

    public function __construct(Tracker $tracker) {
        parent::__construct($tracker);

        $this->html_purifier                         = Codendi_HTMLPurifier::instance();
        $this->card_fields_tracker_presenter_builder = new CardFieldsTrackerPresenterBuilder(Tracker_FormElementFactory::instance());
    }

    public function display() {
        $html   = '';
        $fields = $this->getFields();
        $html .= '<p>';
        if (!count($fields)) {
            $html .= $GLOBALS['Language']->getText('plugin_cardwall','semantic_cardFields_no_fields_defined');
        } else {
            $html .= $GLOBALS['Language']->getText('plugin_cardwall','semantic_cardFields_fields');
            $html .= '<ul>';
            foreach($fields as $field) {
                $html .= '<li><strong>' . $this->html_purifier->purify($field->getLabel(), CODENDI_PURIFIER_CONVERT_HTML) . '</strong></li>';
            }
            $html .= '</ul>';
        }
        $html .= '</p>';
        echo $html;
    }

    /**
     * @return Tracker_FormElement_Field[]
     */
    public function getFields() {
        if (! $this->card_fields) {
            $this->loadFieldsFromTracker($this->tracker);
        }

        return $this->card_fields;
    }

    /**
     * @param Tracker_FormElement_Field[] $fields
     */
    public function setFields(array $fields) {
        $this->card_fields = $fields;
    }

    public function displayAdmin(
        Tracker_SemanticManager $semantic_manager,
        TrackerManager $tracker_manager,
        Codendi_Request $request,
        PFUser $current_user
    ) {

        $semantic_manager->displaySemanticHeader($this, $tracker_manager);

        $fields = $this->getFields();

        $html = "<h3>" . dgettext('tuleap-cardwall', 'Fields') . "</h3>";
        $html .= $this->fetchFormDisplayedFieldForCard($fields);
        $html .= $this->fetchFormAddFieldToCard($fields);

        $html .= $this->fetchFormChooseBackgroundField();

        $html .= '<p><a href="'.TRACKER_BASE_URL.'/?tracker='. $this->tracker->getId() .'&amp;func=admin-semantic">&laquo; ' . $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','go_back_overview') . '</a></p>';

        echo $html;

        $semantic_manager->displaySemanticFooter($this, $tracker_manager);
    }

    /**
     * Transforms CardFields into a SimpleXMLElement
     *
     * @param SimpleXMLElement $root        the node to which the semantic is attached
     * @param array            $xml_mapping correspondance between real field ids and xml IDs
     *
     * @return void
     */
    public function exportToXml(SimpleXMLElement $root, $xml_mapping) {
        $child = $root->addChild('semantic');
        $child->addAttribute('type', $this->getShortName());
        foreach($this->getFields() as $field) {
            if (in_array($field->getId(), $xml_mapping)) {
                $child->addChild('field')->addAttribute('REF', array_search($field->getId(), $xml_mapping));
            }
        }
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

    public function process(Tracker_SemanticManager $semantic_manager, TrackerManager $tracker_manager, Codendi_Request $request, PFUser $current_user) {
        if ( $request->get('add') && (int) $request->get('field')) {
            $this->getCSRFToken()->check();
            $this->addField($request->get('field'));
        } else if ( (int) $request->get('remove') ) {
            $this->getCSRFToken()->check();
            $this->removeField($request->get('remove'));
        }
        $this->displayAdmin($semantic_manager, $tracker_manager, $request, $current_user);
    }

    private function addField($field_id) {
        $field = Tracker_FormElementFactory::instance()->getUsedFormElementById($field_id);

        if (! $field) {
            return;
        }

        $this->getDao()->add($this->tracker->getId(), $field->getId(), 'end');
    }

    private function removeField($field_id) {
        $field = Tracker_FormElementFactory::instance()->getUsedFormElementById($field_id);

        if (! $field) {
            return;
        }

        $this->getDao()->remove($this->tracker->getId(), $field->getId());
    }


    public function save() {
        $dao = $this->getDao();
        foreach ($this->card_fields as $field) {
            $dao->add($this->tracker->getId(), $field->getId(), 'end');
        }
        $this->card_fields = array();
    }

    private function getDao() {
        if (! $this->dao) {
            $this->dao = new Cardwall_Semantic_Dao_CardFieldsDao();
        }
        return $this->dao;
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
        $dao                  = $this->getDao();
        $rows                 = $dao->searchByTrackerId($tracker->getId());
        $form_element_factory = Tracker_FormElementFactory::instance();
        $this->card_fields    = array();

        foreach ($rows as $row) {
            $field = $form_element_factory->getFieldById($row['field_id']);
            if ($field) {
                $this->card_fields[$field->getId()] = $field;
            }
        }
    }

    /**
     * @return Tracker_FormElement_Field
     */
    public function instantiateFieldFromRow(array $row) {
        return Tracker_FormElementFactory::instance()->getFieldById($row['field_id']);
    }

    /**
     * @param Tracker_FormElement_Field[]
     *
     * @return String
     */
    private function fetchFormDisplayedFieldForCard(array $fields)
    {
        $html = '';
        if (! count($fields)) {
            $html .= $GLOBALS['Language']->getText('plugin_cardwall', 'semantic_cardFields_no_fields_defined');
        } else {
            $html .= $GLOBALS['Language']->getText('plugin_cardwall', 'semantic_cardFields_fields');
            $html .= '<blockquote>';
            $html .= '<table>';
            foreach ($fields as $field) {
                $html .= '<tr><td>';
                $html .= $this->html_purifier->purify($field->getLabel(), CODENDI_PURIFIER_CONVERT_HTML);
                $html .= '</td><td>';
                $html .= '<form method="post" id="tracker-semantic-removal-action" action="' . $this->html_purifier->purify($this->getUrl()) . '">';
                $html .= $this->getCSRFToken()->fetchHTMLInput();
                $html .= '<input type="hidden" name="remove" value="' . $this->html_purifier->purify($field->getId()) . '">';
                $html .= '<button type="submit" class="btn btn-link">';
                $html .= $GLOBALS['HTML']->getimage(
                    'ic/cross.png',
                    [
                        'alt' => $GLOBALS['Language']->getText('plugin_cardwall', 'semantic_cardFields_fields')
                    ]
                );
                $html .= '</button>';
                $html .= '</form>';
                $html .= '</a>';
                $html .= '</td></tr>';
            }
            $html .= '</table>';
            $html .= '</blockquote>';
        }
        return $html;
    }

    /**
     * @param $fields
     * @param $html
     *
     * @return string
     */
    private function fetchFormAddFieldToCard(array $fields)
    {
        $html    = '';
        $options = '';
        foreach ($this->tracker->getFormElements() as $formElement) {
            $options .= $formElement->fetchAddTooltip($fields);
        }
        if ($options) {
            $html .= '<form action="' . $this->getUrl() . '" method="POST">';
            $html .= $this->getCSRFToken()->fetchHTMLInput();
            $html .= '<p>' . $GLOBALS['Language']->getText('plugin_cardwall', 'semantic_card_fields_add_field');
            $html .= '<select name="field">';
            $html .= $options;
            $html .= '</select>';
            $html .= '<input type="submit" class="btn btn-primary btn-submit-semantic-card" name="add" value="' .
                $GLOBALS['Language']->getText('global', 'btn_submit') . '" />';
            $html .= '</p>';
            $html .= '</form>';
        } else {
            $html .= '<em>' . $GLOBALS['Language']->getText('plugin_cardwall', 'semantic_card_fields_no_more_field') . '</em>';
        }
        return $html;
    }

    /**
     * @return String
     */
    private function fetchFormChooseBackgroundField()
    {
        $presenter = new BackgroundColorSelectorPresenter(
            $this->card_fields_tracker_presenter_builder->getTrackerFields($this->tracker->getFormElementFields()),
            $this->getCSRFToken(),
            $this->getUrl()
        );
        $renderer  = TemplateRendererFactory::build()->getRenderer(dirname(CARDWALL_BASE_DIR) . '/templates');

        return $renderer->renderToString('semantic-card-background-selector', $presenter);
    }
}
