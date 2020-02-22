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

use Tuleap\Cardwall\Semantic\BackgroundColorDao;
use Tuleap\Cardwall\Semantic\BackgroundColorFieldRetriever;
use Tuleap\Cardwall\Semantic\BackgroundColorFieldSaver;
use Tuleap\Cardwall\Semantic\BackgroundColorPresenterBuilder;
use Tuleap\Cardwall\Semantic\BackgroundColorSemanticFieldNotFoundException;
use Tuleap\Cardwall\Semantic\CardFieldsPresenterBuilder;
use Tuleap\Cardwall\Semantic\CardFieldXmlExporter;
use Tuleap\Cardwall\Semantic\CardsPreviewBuilder;
use Tuleap\Cardwall\Semantic\FieldUsedInSemanticObjectChecker;
use Tuleap\Cardwall\Semantic\SemanticCardPresenter;
use Tuleap\Cardwall\Semantic\SingleCardPreviewDetailsBuilder;

class Cardwall_Semantic_CardFields extends Tracker_Semantic
{
    public const NAME                  = 'plugin_cardwall_card_fields';
    public const BACKGROUND_COLOR_NAME = 'plugin_cardwell_card_fields_background_color';
    /**
     * @var Tracker_FormElement_Field|null
     */
    private $background_color_field = null;

    /**
     * @var FieldUsedInSemanticObjectChecker
     */
    private $semantic_field_checker;
    /**
     * @var BackgroundColorFieldSaver
     */
    private $background_field_saver;

    /**
     * @var CardFieldsPresenterBuilder
     */
    private $field_builder;
    /**
     * @var BackgroundColorPresenterBuilder
     */
    private $background_color_presenter_builder;
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
    /**
     * @var CardFieldXmlExporter
     */
    private $xml_exporter;

    /** @var BackgroundColorFieldRetriever */
    private $background_field_retriever;
    /**
     * @var BackgroundColorDao
     */
    private $background_color_dao;
    /**
     * @var CardsPreviewBuilder
     */
    private $cards_preview_builder;

    public function __construct(
        Tracker $tracker,
        FieldUsedInSemanticObjectChecker $field_used_in_semantic_object_checker,
        BackgroundColorPresenterBuilder $background_color_presenter_builder,
        BackgroundColorFieldSaver $background_color_field_saver,
        CardFieldsPresenterBuilder $field_builder,
        BackgroundColorFieldRetriever $background_field_retriever,
        CardFieldXmlExporter $xml_exporter,
        BackgroundColorDao $background_color_dao,
        CardsPreviewBuilder $cards_preview_builder
    ) {
        parent::__construct($tracker);

        $this->html_purifier                      = Codendi_HTMLPurifier::instance();
        $this->semantic_field_checker             = $field_used_in_semantic_object_checker;
        $this->background_color_presenter_builder = $background_color_presenter_builder;
        $this->background_field_saver             = $background_color_field_saver;
        $this->field_builder                      = $field_builder;
        $this->background_field_retriever         = $background_field_retriever;
        $this->xml_exporter                       = $xml_exporter;
        $this->background_color_dao               = $background_color_dao;
        $this->cards_preview_builder              = $cards_preview_builder;
    }

    public function display()
    {
        $html   = '';
        $fields = $this->getFields();
        $html   .= '<p>';
        if (! count($fields)) {
            $html .= $GLOBALS['Language']->getText('plugin_cardwall', 'semantic_cardFields_no_fields_defined');
        } else {
            $html .= $GLOBALS['Language']->getText('plugin_cardwall', 'semantic_cardFields_fields');
            $html .= '<ul>';
            foreach ($fields as $field) {
                $html .= '<li><strong>' . $this->html_purifier->purify($field->getLabel(), CODENDI_PURIFIER_CONVERT_HTML) . '</strong></li>';
            }
            $html .= '</ul>';
        }

        $html .= "</p><p>" . dgettext('tuleap-cardwall', 'Only static fields with values bound to new color picker can be chosen as background color');

        try {
            $html .= "</p><p>" . sprintf(
                dgettext(
                    'tuleap-cardwall',
                    '<b>%s</b> field will determine background color.'
                ),
                $this->getBackgroundColorField()->getLabel()
            );
        } catch (BackgroundColorSemanticFieldNotFoundException $exception) {
            $html .= "</p><p>" . dgettext('tuleap-cardwall', 'No field is chosen to determine backgorund color');
        }

        $html .= '</p>';

        echo $html;
    }

    /**
     * @return Tracker_FormElement_Field[]
     */
    public function getFields()
    {
        if (! $this->card_fields) {
            $this->loadFieldsFromTracker($this->tracker);
        }

        return $this->card_fields;
    }

    /**
     * @param Tracker_FormElement_Field[] $fields
     */
    public function setFields(array $fields)
    {
        $this->card_fields = $fields;
    }

    public function displayAdmin(
        Tracker_SemanticManager $semantic_manager,
        TrackerManager $tracker_manager,
        Codendi_Request $request,
        PFUser $current_user
    ) {
        $semantic_manager->displaySemanticHeader($this, $tracker_manager);

        $fields_presenter = $this->field_builder->build(
            $this->getFields(),
            $this->tracker->getFormElements()
        );

        $card_preview_details = $this->cards_preview_builder->build($this);

        $semantic_presenter = new SemanticCardPresenter(
            $fields_presenter,
            $this->background_color_presenter_builder->build(
                $this->tracker->getFormElementFields(),
                $this->tracker
            ),
            $this->tracker,
            $this->getCSRFToken(),
            $this->getUrl(),
            $card_preview_details,
            $current_user
        );

        $renderer = TemplateRendererFactory::build()->getRenderer(dirname(CARDWALL_BASE_DIR) . '/templates');

        echo $renderer->renderToString('semantic-card', $semantic_presenter);

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
    public function exportToXml(SimpleXMLElement $root, $xml_mapping)
    {
        $this->xml_exporter->exportToXml($root, $xml_mapping, $this);
    }

    public function getDescription()
    {
        return $GLOBALS['Language']->getText('plugin_cardwall', 'semantic_cardFields_description');
    }

    public function getLabel()
    {
        return $GLOBALS['Language']->getText('plugin_cardwall', 'semantic_cardFields_label');
    }

    public function getShortName()
    {
        return self::NAME;
    }

    public function isUsedInSemantics(Tracker_FormElement_Field $field)
    {
        return $this->semantic_field_checker->isUsedInSemantic($field, $this->getFields());
    }

    public function process(Tracker_SemanticManager $semantic_manager, TrackerManager $tracker_manager, Codendi_Request $request, PFUser $current_user)
    {
        if ($request->get('add') && (int) $request->get('field')) {
            $this->getCSRFToken()->check();
            $this->addField($request->get('field'));
        } elseif ((int) $request->get('remove')) {
            $this->getCSRFToken()->check();
            $this->removeField($request->get('remove'));
        } elseif ($request->get('unset-background-color-semantic')) {
            $this->getCSRFToken()->check();
            $this->background_field_saver->unsetBackgroundColorSemantic($this->tracker);
        } elseif ($request->get('choose-color-field')) {
            $this->getCSRFToken()->check();
            $this->background_field_saver->chooseBackgroundColorField($this->tracker, $request->get('choose-color-field'));
        }
        $this->displayAdmin($semantic_manager, $tracker_manager, $request, $current_user);
    }

    private function addField($field_id)
    {
        $field = Tracker_FormElementFactory::instance()->getUsedFormElementById($field_id);

        if (! $field) {
            return;
        }

        $this->getDao()->add($this->tracker->getId(), $field->getId(), 'end');
    }

    private function removeField($field_id)
    {
        $field = Tracker_FormElementFactory::instance()->getUsedFormElementById($field_id);

        if (! $field) {
            return;
        }

        $this->getDao()->remove($this->tracker->getId(), $field->getId());
    }


    public function save()
    {
        $dao = $this->getDao();
        foreach ($this->card_fields as $field) {
            $dao->add($this->tracker->getId(), $field->getId(), 'end');
        }
        $this->card_fields = [];

        try {
            $this->background_color_dao->save($this->tracker->getId(), $this->getBackgroundColorField()->getId());
        } catch (BackgroundColorSemanticFieldNotFoundException $exception) {
        }
    }

    private function getDao()
    {
        if (! $this->dao) {
            $this->dao = new Cardwall_Semantic_Dao_CardFieldsDao();
        }
        return $this->dao;
    }

    /**
     * Load an instance of a Cardwall_Semantic_CardFields
     *
     * @return Cardwall_Semantic_CardFields
     */
    public static function load(Tracker $tracker)
    {
        if (! isset(self::$_instances[$tracker->getId()])) {
            $background_color_dao               = new BackgroundColorDao();
            $background_color_presenter_builder = new BackgroundColorPresenterBuilder(
                Tracker_FormElementFactory::instance(),
                $background_color_dao
            );
            $tracker_form_element_factory       = Tracker_FormElementFactory::instance();
            $background_field_saver             = new BackgroundColorFieldSaver(
                $tracker_form_element_factory,
                $background_color_dao
            );

            $field_used_in_semantic_object_checker = new FieldUsedInSemanticObjectChecker(
                $background_color_dao
            );

            $field_builder = new CardFieldsPresenterBuilder();

            $background_field_retriever = new BackgroundColorFieldRetriever(
                $tracker_form_element_factory,
                $background_color_dao
            );

            $xml_exporter = new CardFieldXmlExporter($background_color_dao);

            $single_card_preview_details_builder = new SingleCardPreviewDetailsBuilder();

            $card_preview_builder = new CardsPreviewBuilder($single_card_preview_details_builder);

            self::$_instances[$tracker->getId()] = new Cardwall_Semantic_CardFields(
                $tracker,
                $field_used_in_semantic_object_checker,
                $background_color_presenter_builder,
                $background_field_saver,
                $field_builder,
                $background_field_retriever,
                $xml_exporter,
                $background_color_dao,
                $card_preview_builder
            );
        }

        return self::$_instances[$tracker->getId()];
    }

    private function loadFieldsFromTracker(Tracker $tracker)
    {
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
     * @return Tracker_FormElement_Field|null
     */
    public function instantiateFieldFromRow(array $row)
    {
        return Tracker_FormElementFactory::instance()->getFieldById($row['field_id']);
    }

    /**
     * @return Tracker_FormElement_Field_List
     * @throws BackgroundColorSemanticFieldNotFoundException
     */
    public function getBackgroundColorField()
    {
        if (! $this->background_color_field) {
            return $this->background_field_retriever->getField($this->tracker);
        }

        return $this->background_color_field;
    }

    public function setBackgroundColorField(?Tracker_FormElement_Field $background_color_field = null)
    {
        $this->background_color_field = $background_color_field;
    }
}
