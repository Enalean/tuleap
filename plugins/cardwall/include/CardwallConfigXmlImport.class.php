<?php
/**
 * Copyright (c) Enalean, 2013 - 2016. All Rights Reserved.
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

class CardwallConfigXmlImport
{

    /**
     * @var Cardwall_OnTop_ColumnMappingFieldValueDao
     */
    private $mapping_field_value_dao;

    /**
     * @var Cardwall_OnTop_ColumnMappingFieldDao
     */
    private $mapping_field_dao;

    /** @var array */
    private $mapping;

    /** @var array */
    private $field_mapping;

    /** @var Cardwall_OnTop_Dao */
    private $cardwall_ontop_dao;

    /** @var int */
    private $group_id;

    /** @var EventManager */
    private $event_manager;

    /** @var XML_RNGValidator */
    private $xml_validator;

    /** @var Cardwall_OnTop_ColumnDao */
    private $column_dao;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var Tracker_XML_Importer_ArtifactImportedMapping
     */
    private $artifact_id_mapping;

    public function __construct(
        $group_id,
        array $mapping,
        array $field_mapping,
        Tracker_XML_Importer_ArtifactImportedMapping $artifact_id_mapping,
        Cardwall_OnTop_Dao $cardwall_ontop_dao,
        Cardwall_OnTop_ColumnDao $column_dao,
        Cardwall_OnTop_ColumnMappingFieldDao $mapping_field_dao,
        Cardwall_OnTop_ColumnMappingFieldValueDao $mapping_field_value_dao,
        EventManager $event_manager,
        XML_RNGValidator $xml_validator,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->mapping                 = $mapping;
        $this->field_mapping           = $field_mapping;
        $this->cardwall_ontop_dao      = $cardwall_ontop_dao;
        $this->column_dao              = $column_dao;
        $this->mapping_field_dao       = $mapping_field_dao;
        $this->mapping_field_value_dao = $mapping_field_value_dao;
        $this->group_id                = $group_id;
        $this->event_manager           = $event_manager;
        $this->xml_validator           = $xml_validator;
        $this->logger                  = $logger;
        $this->artifact_id_mapping     = $artifact_id_mapping;
    }

    /**
     * Import cardwall ontop from XML input
     *
     * @throws CardwallFromXmlImportCannotBeEnabledException
     */
    public function import(SimpleXMLElement $xml_input)
    {
        if (! $xml_input->{CardwallConfigXml::NODE_CARDWALL}) {
            return;
        }

        $rng_path = realpath(__DIR__ . '/../resources/xml_project_cardwall.rng');
        $this->xml_validator->validate($xml_input->{CardwallConfigXml::NODE_CARDWALL}, $rng_path);

        $this->cardwall_ontop_dao->startTransaction();
        $this->importCardwalls($xml_input->{CardwallConfigXml::NODE_CARDWALL});

        $this->event_manager->processEvent(
            Event::IMPORT_XML_PROJECT_CARDWALL_DONE,
            array(
                'project_id'          => $this->group_id,
                'xml_content'         => $xml_input,
                'mapping'             => $this->mapping,
                'logger'              => $this->logger,
                'artifact_id_mapping' => $this->artifact_id_mapping
            )
        );

        $this->cardwall_ontop_dao->commit();
    }

    private function importCardwalls(SimpleXMLElement $cardwalls)
    {
        foreach ($cardwalls->{CardwallConfigXml::NODE_TRACKERS}->children() as $cardwall_tracker) {
            $cardwall_tracker_xml_id = (String) $cardwall_tracker[CardwallConfigXml::ATTRIBUTE_TRACKER_ID];
            if (array_key_exists($cardwall_tracker_xml_id, $this->mapping)) {
                $tracker_id = $this->mapping[$cardwall_tracker_xml_id];
                $this->importOneCardwall($cardwall_tracker, $tracker_id);
            }
        }
    }

    private function importOneCardwall(SimpleXMLElement $cardwall_tracker, $cardwall_tracker_id)
    {
        $enabled = $this->cardwall_ontop_dao->enable($cardwall_tracker_id);

        if (! $enabled) {
            throw new CardwallFromXmlImportCannotBeEnabledException($cardwall_tracker_id);
        }

        $this->cardwall_ontop_dao->enableFreestyleColumns($cardwall_tracker_id);

        if ($cardwall_tracker->{CardwallConfigXml::NODE_COLUMNS}) {
            $column_mapping = $this->importColumns(
                $cardwall_tracker->{CardwallConfigXml::NODE_COLUMNS},
                $cardwall_tracker_id
            );

            if ($cardwall_tracker->{CardwallConfigXml::NODE_MAPPINGS}) {
                $this->importMappings(
                    $cardwall_tracker->{CardwallConfigXml::NODE_MAPPINGS},
                    $column_mapping,
                    $cardwall_tracker_id
                );
            }
        }
    }

    private function importMappings(SimpleXMLElement $xml_mappings, array $column_mapping, $cardwall_tracker_id)
    {
        foreach ($xml_mappings->{CardwallConfigXml::NODE_MAPPING} as $xml_mapping) {
            $new_tracker_id = $this->getNewTrackerId($xml_mapping);
            $new_field_id   = $this->getNewFieldId($xml_mapping);

            if ($new_tracker_id && $new_field_id) {
                $this->mapping_field_dao->create($cardwall_tracker_id, $new_tracker_id, $new_field_id);

                if ($xml_mapping->{CardwallConfigXml::NODE_VALUES}) {
                    $this->importMappingValues(
                        $xml_mapping->{CardwallConfigXml::NODE_VALUES},
                        $column_mapping,
                        $cardwall_tracker_id,
                        $new_tracker_id,
                        $new_field_id
                    );
                }
            }
        }
    }

    private function getNewFieldId(SimpleXMLElement $xml_mapping)
    {
        $field_xml_id = (string) $xml_mapping['field_id'];

        if (isset($this->field_mapping[$field_xml_id])) {
            $new_field = $this->field_mapping[$field_xml_id];

            return $new_field->getId();
        } else {
            $GLOBALS['Response']->addFeedback(
                Feedback::WARN,
                $GLOBALS['Language']->getText('plugin_cardwall', 'xml_import_field_error', array($field_xml_id))
            );
        }
    }

    private function getNewTrackerId(SimpleXMLElement $xml_mapping)
    {
        $tracker_id_xml = (string) $xml_mapping['tracker_id'];

        if (isset($this->mapping[$tracker_id_xml])) {
            return $this->mapping[$tracker_id_xml];
        } else {
            $GLOBALS['Response']->addFeedback(
                Feedback::WARN,
                $GLOBALS['Language']->getText('plugin_cardwall', 'xml_import_tracker_error', array($tracker_id_xml))
            );
        }
    }

    private function importMappingValues(
        SimpleXMLElement $xml_values,
        array $column_mapping,
        $cardwall_tracker_id,
        $tracker_id,
        $field_id
    ) {
        foreach ($xml_values->{CardwallConfigXml::NODE_VALUE} as $xml_value) {
            $new_value_id  = $this->getNewValueId($xml_value);
            $new_column_id = $this->getNewColumnId($xml_value, $column_mapping);

            if ($new_value_id && $new_column_id) {
                $this->mapping_field_value_dao->save(
                    $cardwall_tracker_id,
                    $tracker_id,
                    $field_id,
                    $new_value_id,
                    $new_column_id
                );
            }
        }
    }

    private function getNewColumnId(SimpleXMLElement $xml_value, array $column_mapping)
    {
        $xml_column_id = (string) $xml_value['column_id'];

        if (isset($column_mapping[$xml_column_id])) {
            return $column_mapping[$xml_column_id];
        } else {
            $GLOBALS['Response']->addFeedback(
                Feedback::WARN,
                $GLOBALS['Language']->getText('plugin_cardwall', 'xml_import_column_error', array($xml_column_id))
            );
        }
    }

    private function getNewValueId(SimpleXMLElement $xml_value)
    {
        $xml_value_id = (string) $xml_value['value_id'];

        if ($xml_value_id === Tracker_FormElement_Field_List_Bind_StaticValue_None::XML_VALUE_ID) {
            return Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID;
        }

        if (isset($this->field_mapping[$xml_value_id])) {
            $new_value = $this->field_mapping[$xml_value_id];

            return $new_value->getId();
        } else {
            $GLOBALS['Response']->addFeedback(
                Feedback::WARN,
                $GLOBALS['Language']->getText('plugin_cardwall', 'xml_import_value_error', array($xml_value_id))
            );
        }
    }

    /**
     *
     * @return array the column mapping like (XML_COLUMN_ID => added_column_id)
     */
    private function importColumns(SimpleXMLElement $xml_columns, $cardwall_tracker_id)
    {
        $column_mapping = array();

        foreach ($xml_columns->{CardwallConfigXml::NODE_COLUMN} as $xml_column) {
            $label         = (string) $xml_column[CardwallConfigXml::ATTRIBUTE_COLUMN_LABEL];
            $xml_column_id = (string) $xml_column[CardwallConfigXml::ATTRIBUTE_COLUMN_ID];

            $red       = $this->getColorValueFromXML($xml_column, CardwallConfigXml::ATTRIBUTE_COLUMN_BG_RED, $xml_column_id);
            $green     = $this->getColorValueFromXML($xml_column, CardwallConfigXml::ATTRIBUTE_COLUMN_BG_GREEN, $xml_column_id);
            $blue      = $this->getColorValueFromXML($xml_column, CardwallConfigXml::ATTRIBUTE_COLUMN_BG_BLUE, $xml_column_id);
            $tlp_color = $this->getTLPColorNameFromXML($xml_column, $xml_column_id);

            if ($tlp_color) {
                $added_column_id = $this->column_dao->createWithTLPColor($cardwall_tracker_id, $label, $tlp_color);
            } else {
                $added_column_id = $this->column_dao->createWithcolor($cardwall_tracker_id, $label, $red, $green, $blue);
            }

            $column_mapping[$xml_column_id] = $added_column_id;
        }

        return $column_mapping;
    }

    private function getTLPColorNameFromXml(SimpleXMLElement $xml_column, $xml_column_id)
    {
        $color_label = CardwallConfigXml::ATTRIBUTE_COLUMN_TLP_COLOR_NAME;

        if (! isset($xml_column[$color_label])) {
            return null;
        }
        $color_name  = $xml_column[$color_label];

        if ((string) $color_name === '') {
            $this->addColorImportErrorFeedback($color_label, $xml_column_id);
            return null;
        }

        return $color_name;
    }

    private function getColorValueFromXML(SimpleXMLElement $xml_column, $color_label, $xml_column_id)
    {
        if ($xml_column[$color_label]) {
            $color_value = (int) $xml_column[$color_label];

            if ($color_value >= 0 && $color_value <= 255) {
                return $color_value;
            } else {
                $this->addColorImportErrorFeedback($color_label, $xml_column_id);
            }
        }

        return '';
    }

    private function addColorImportErrorFeedback($color_label, $xml_column_id)
    {
        $GLOBALS['Response']->addFeedback(
            Feedback::WARN,
            $GLOBALS['Language']->getText(
                'plugin_cardwall',
                'xml_import_color_error',
                [
                    $color_label,
                    $xml_column_id
                ]
            )
        );
    }
}
