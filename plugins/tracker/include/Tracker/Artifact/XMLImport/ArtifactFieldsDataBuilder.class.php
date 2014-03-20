<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

/**
 * I convert the xml changeset data into data structure in order to create changeset in one artifact
 */
class Tracker_Artifact_XMLImport_ArtifactFieldsDataBuilder {

    const FIELDTYPE_STRING            = 'string';
    const FIELDTYPE_TEXT              = 'text';
    const FIELDTYPE_INT               = 'integer';
    const FIELDTYPE_FLOAT             = 'float';
    const FIELDTYPE_DATE              = 'date';
    const FIELDTYPE_PERMS_ON_ARTIFACT = 'permissions_on_artifact';
    const FIELDTYPE_ATTACHEMENT       = 'file';
    const FIELDTYPE_OPENLIST          = 'open_list';

    /** @var Tracker_FormElementFactory */
    private $formelement_factory;

    /** @var UserManager */
    private $user_manager;

    /** @var Tracker */
    private $tracker;

    /** @var Tracker_Artifact_XMLImport_CollectionOfFilesToImportInArtifact */
    private $files_importer;

    /** @var string */
    private $extraction_path;

    public function __construct(
        Tracker_FormElementFactory $formelement_factory,
        UserManager $user_manager,
        Tracker $tracker,
        Tracker_Artifact_XMLImport_CollectionOfFilesToImportInArtifact $files_importer,
        $extraction_path

    ) {
        $this->formelement_factory  = $formelement_factory;
        $this->user_manager         = $user_manager;
        $this->tracker              = $tracker;
        $this->files_importer       = $files_importer;
        $this->extraction_path      = $extraction_path;
    }

    /**
     * @return array
     */
    public function getFieldsData(SimpleXMLElement $xml_field_change) {
        $data = array();

        foreach ($xml_field_change as $field_change) {
            $field = $this->formelement_factory->getFormElementByName(
                $this->tracker->getId(),
                (string) $field_change['field_name']
            );

            if ($field) {
                $data[$field->getId()] = $this->getFieldData($field, $field_change);
            }
        }
        return $data;
    }

    private function getFieldData(Tracker_FormElement_Field $field, SimpleXMLElement $field_change) {
        switch ((string)$field_change['type']) {
            case self::FIELDTYPE_STRING:
            case self::FIELDTYPE_TEXT:
            case self::FIELDTYPE_INT:
            case self::FIELDTYPE_FLOAT:
            case self::FIELDTYPE_DATE:
                $strategy = new Tracker_Artifact_XMLImport_XMLImportFieldStrategyAlphanumeric();
                break;
            case self::FIELDTYPE_PERMS_ON_ARTIFACT:
                $strategy = new Tracker_Artifact_XMLImport_XMLImportFieldStrategyPermissionsOnArtifact();
                break;
            case self::FIELDTYPE_ATTACHEMENT:
                $strategy = new Tracker_Artifact_XMLImport_XMLImportFieldStrategyAttachment(
                    $this->extraction_path,
                    $this->files_importer
                );
                break;
            case self::FIELDTYPE_OPENLIST:
                $strategy = new Tracker_Artifact_XMLImport_XMLImportFieldStrategyOpenList(
                    $field
                );
                break;
        }

        return $strategy->getFieldData($field_change);
    }
}
