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

class Tracker_Artifact_XMLImport_XMLImportFieldStrategyOpenList implements Tracker_Artifact_XMLImport_XMLImportFieldStrategy {

    /** @var Tracker_FormElement_Field_OpenList */
    private $field;

    public function __construct(Tracker_FormElement_Field_OpenList $field) {
        $this->field = $field;
    }

    /**
     * Extract Field data from XML input
     *
     * @param SimpleXMLElement $field_change
     * @param SimpleXMLElement $xml_artifact
     *
     * @return mixed
     */
    public function getFieldData(SimpleXMLElement $field_change) {
        $values = array();
        foreach ($field_change->value as $value) {
            $values[] = (string) $value;
        }
        return $this->field->getFieldData(implode(',', $values));
    }
}