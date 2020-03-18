<?php
/**
 * Copyright (c) Enalean, 2014 - 2016. All Rights Reserved.
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

use Tuleap\Tracker\Artifact\Event\ExternalStrategiesGetter;
use Tuleap\Tracker\Artifact\XMLImport\XMLImportFieldStrategyComputed;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureDao;

/**
 * I convert the xml changeset data into data structure in order to create changeset in one artifact
 */
class Tracker_Artifact_XMLImport_ArtifactFieldsDataBuilder
{

    public const FIELDTYPE_STRING            = 'string';
    public const FIELDTYPE_TEXT              = 'text';
    public const FIELDTYPE_INT               = 'int';
    public const FIELDTYPE_FLOAT             = 'float';
    public const FIELDTYPE_DATE              = 'date';
    public const FIELDTYPE_PERMS_ON_ARTIFACT = 'permissions_on_artifact';
    public const FIELDTYPE_ATTACHEMENT       = 'file';
    public const FIELDTYPE_OPENLIST          = 'open_list';
    public const FIELDTYPE_LIST              = 'list';
    public const FIELDTYPE_ARTIFACT_LINK     = 'art_link';
    public const FIELDTYPE_COMPUTED          = 'computed';

    /** @var Tracker_FormElementFactory */
    private $formelement_factory;

    /** @var Tracker */
    private $tracker;

    /** @var Tracker_Artifact_XMLImport_CollectionOfFilesToImportInArtifact */
    private $files_importer;

    /** @var string */
    private $extraction_path;

    /** @var Tracker_Artifact_XMLImport_XMLImportFieldStrategy[] */
    private $strategies;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    public function __construct(
        Tracker_FormElementFactory $formelement_factory,
        User\XML\Import\IFindUserFromXMLReference $user_finder,
        Tracker $tracker,
        Tracker_Artifact_XMLImport_CollectionOfFilesToImportInArtifact $files_importer,
        $extraction_path,
        Tracker_FormElement_Field_List_Bind_Static_ValueDao $static_value_dao,
        \Psr\Log\LoggerInterface $logger,
        TrackerXmlFieldsMapping $xml_fields_mapping,
        Tracker_XML_Importer_ArtifactImportedMapping $artifact_id_mapping,
        Tracker_ArtifactFactory $tracker_artifact_factory,
        NatureDao $nature_dao
    ) {
        $this->formelement_factory  = $formelement_factory;
        $this->tracker              = $tracker;
        $this->files_importer       = $files_importer;
        $this->extraction_path      = $extraction_path;
        $this->logger               = $logger;
        $alphanum_strategy = new Tracker_Artifact_XMLImport_XMLImportFieldStrategyAlphanumeric();
        $this->strategies  = array(
            self::FIELDTYPE_PERMS_ON_ARTIFACT => new Tracker_Artifact_XMLImport_XMLImportFieldStrategyPermissionsOnArtifact(),
            self::FIELDTYPE_ATTACHEMENT => new Tracker_Artifact_XMLImport_XMLImportFieldStrategyAttachment(
                $this->extraction_path,
                $this->files_importer,
                $this->logger
            ),
            self::FIELDTYPE_OPENLIST => new Tracker_Artifact_XMLImport_XMLImportFieldStrategyOpenList(
                $xml_fields_mapping,
                $user_finder
            ),
            self::FIELDTYPE_STRING   => $alphanum_strategy,
            self::FIELDTYPE_TEXT     => new Tracker_Artifact_XMLImport_XMLImportFieldStrategyText(),
            self::FIELDTYPE_INT      => $alphanum_strategy,
            self::FIELDTYPE_FLOAT    => $alphanum_strategy,
            self::FIELDTYPE_DATE     => new Tracker_Artifact_XMLImport_XMLImportFieldStrategyDate(),
            self::FIELDTYPE_LIST     => new Tracker_Artifact_XMLImport_XMLImportFieldStrategyList(
                $static_value_dao,
                $user_finder,
                $xml_fields_mapping
            ),
            self::FIELDTYPE_ARTIFACT_LINK => new Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink(
                $artifact_id_mapping,
                $logger,
                $tracker_artifact_factory,
                $nature_dao,
            ),
            self::FIELDTYPE_COMPUTED => new XMLImportFieldStrategyComputed()
        );

        $this->getExternalStrategies();
    }

    protected function getExternalStrategies()
    {
        $event_manager     = EventManager::instance();
        $strategies_getter = new ExternalStrategiesGetter();
        $event_manager->processEvent($strategies_getter);
        $this->strategies = array_merge($this->strategies, $strategies_getter->getStrategies());
    }

    /**
     * @return array
     */
    public function getFieldsData(SimpleXMLElement $xml_field_change, PFUser $submitted_by, Tracker_Artifact $artifact)
    {
        $data = array();

        if (! $xml_field_change->field_change && ! $xml_field_change->external_field_change) {
            return $data;
        }

        $data = $this->getChangesetData($xml_field_change->field_change, $submitted_by, $artifact, $data);
        $data = $this->getChangesetData($xml_field_change->external_field_change, $submitted_by, $artifact, $data);

        return $data;
    }

    private function getChangesetData(
        SimpleXMLElement $xml_field_change,
        PFUser $submitted_by,
        Tracker_Artifact $artifact,
        array $data
    ) {
        foreach ($xml_field_change as $field_change) {
            $field = $this->formelement_factory->getUsedFieldByName(
                $this->tracker->getId(),
                (string) $field_change['field_name']
            );

            if ($field) {
                $this->forceTrackerSoThatFieldDoesNotLoadAFreshNewTrackerAndLooseTheDisabledStateOnWorkflow($field);
                $this->appendValidValue($data, $field, $field_change, $submitted_by, $artifact);
            } else {
                $this->logger->debug("Skipped unknown/unused field " . (string) $field_change['field_name']);
            }
        }
        return $data;
    }

    private function forceTrackerSoThatFieldDoesNotLoadAFreshNewTrackerAndLooseTheDisabledStateOnWorkflow(
        Tracker_FormElement_Field $field
    ) {
        $field->setTracker($this->tracker);
    }

    private function appendValidValue(
        array &$data,
        Tracker_FormElement_Field $field,
        SimpleXMLElement $field_change,
        PFUser $submitted_by,
        Tracker_Artifact $artifact
    ) {
        try {
            $submitted_value = $this->getFieldData($field, $field_change, $submitted_by, $artifact);
            if ($field->validateField($this->createFakeArtifact(), $submitted_value)) {
                $data[$field->getId()] = $submitted_value;
            } else {
                if (is_array($submitted_value)) {
                    $invalid_submitted_value = implode(', ', $submitted_value);
                } else {
                    $invalid_submitted_value = (string) $submitted_value;
                }
                $this->logger->warning("Skipped invalid value $invalid_submitted_value for field " . $field->getName());
            }
        } catch (Tracker_Artifact_XMLImport_Exception_NoValidAttachementsException $exception) {
            $this->logger->warning("Skipped invalid value for field " . $field->getName() . ': ' . $exception->getMessage());
        }
    }

    /**
     * A fake artifact is needed for validateField to work
     *
     * An artifact is needed by List type of field to do Workflow check
     * But as workflow is disabled we don't care
     *
     * @return Tracker_Artifact
     */
    private function createFakeArtifact()
    {
        return new Tracker_Artifact(-1, $this->tracker->getID(), -1, -1, -1);
    }

    private function getFieldData(
        Tracker_FormElement_Field $field,
        SimpleXMLElement $field_change,
        PFUser $submitted_by,
        Tracker_Artifact $artifact
    ) {
        $type = (string) $field_change['type'];

        if (! isset($this->strategies[$type])) {
            throw new Tracker_Artifact_XMLImport_Exception_StrategyDoesNotExistException();
        }

        return $this->strategies[$type]->getFieldData($field, $field_change, $submitted_by, $artifact);
    }
}
