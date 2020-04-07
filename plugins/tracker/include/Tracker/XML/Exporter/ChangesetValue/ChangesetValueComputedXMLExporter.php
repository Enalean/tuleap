<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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

namespace Tuleap\Tracker\XML\Exporter\ChangesetValue;

use PFUser;
use Tracker_XML_Exporter_ChangesetValue_ChangesetValueFloatXMLExporter;
use SimpleXMLElement;
use Tracker_Artifact;
use Tracker_Artifact_ChangesetValue;
use XML_SimpleXMLCDATAFactory;

class ChangesetValueComputedXMLExporter extends Tracker_XML_Exporter_ChangesetValue_ChangesetValueFloatXMLExporter
{
    /**
     * @var PFUser
     */
    private $current_user;

    /**
     * @var bool
     */
    private $is_in_archive_context;

    public function __construct(PFUser $current_user, $is_in_archive_context)
    {
        $this->current_user          = $current_user;
        $this->is_in_archive_context = $is_in_archive_context;
    }

    protected function getFieldChangeType()
    {
        return 'computed';
    }

    public function export(
        SimpleXMLElement $artifact_xml,
        SimpleXMLElement $changeset_xml,
        Tracker_Artifact $artifact,
        Tracker_Artifact_ChangesetValue $changeset_value
    ) {
        if ($this->isCurrentChangesetTheLastChangeset($artifact, $changeset_value)) {
            $this->exportLastChangeset($changeset_xml, $artifact, $changeset_value);
        } else {
            $this->exportInGlobalContext($changeset_xml, $changeset_value);
        }
    }

    private function isCurrentChangesetTheLastChangeset(
        Tracker_Artifact $artifact,
        Tracker_Artifact_ChangesetValue $current_changeset_value
    ) {
        $field          = $current_changeset_value->getField();
        $last_changeset = $artifact->getLastChangeset();

        if (! $last_changeset) {
            return false;
        }

        $last_changeset_value = $last_changeset->getValue($field);

        if (! $last_changeset_value) {
            return false;
        }

        return ($last_changeset_value->getId() === $current_changeset_value->getId());
    }

    private function exportLastChangeset(
        SimpleXMLElement $changeset_xml,
        Tracker_Artifact $artifact,
        Tracker_Artifact_ChangesetValue $changeset_value
    ) {
        if ($this->is_in_archive_context) {
            $this->exportLastChangesetInArchiveContext($changeset_xml, $artifact, $changeset_value);
        } else {
            $this->exportInGlobalContext($changeset_xml, $changeset_value);
        }
    }

    /**
     * @return SimpleXMLElement
     */
    private function createFieldChangeTag(
        SimpleXMLElement $changeset_xml,
        Tracker_Artifact_ChangesetValue $changeset_value
    ) {
        return $this->createFieldChangeNodeInChangesetNode(
            $changeset_value,
            $changeset_xml
        );
    }

    private function exportLastChangesetInArchiveContext(
        SimpleXMLElement $changeset_xml,
        Tracker_Artifact $artifact,
        Tracker_Artifact_ChangesetValue $changeset_value
    ) {
        $number_of_changeset = count($artifact->getChangesets());

        if (
            $number_of_changeset === 1 ||
            ($number_of_changeset > 1 && ! $changeset_value->isManualValue()) ||
            $this->previousChangesetIsNotInManualValue($artifact, $changeset_value, $number_of_changeset)
        ) {
            $field_change = $this->createFieldChangeTag($changeset_xml, $changeset_value);
            $this->exportManualValue($field_change, $this->getLastComputedValue($artifact, $changeset_value));
        }
    }

    private function previousChangesetIsNotInManualValue(
        Tracker_Artifact $artifact,
        Tracker_Artifact_ChangesetValue $changeset_value,
        $number_of_changeset
    ) {
        $previous_changeset = $artifact->getPreviousChangeset($changeset_value->getChangeset()->getId());

        if (! $previous_changeset) {
            return true;
        }

        $previous_changeset_value = $previous_changeset->getValue($changeset_value->getField());

        if (! $previous_changeset_value) {
            return true;
        }

        return $number_of_changeset > 1 && ! $previous_changeset_value->isManualValue();
    }

    private function exportInGlobalContext(
        SimpleXMLElement $changeset_xml,
        Tracker_Artifact_ChangesetValue $changeset_value
    ) {
        $field_change = $this->createFieldChangeTag($changeset_xml, $changeset_value);

        if ($changeset_value->isManualValue()) {
            $this->exportManualValue($field_change, $changeset_value->getValue());
        } else {
            $field_change->addChild('is_autocomputed', true);
        }
    }

    /**
     * @return float
     */
    private function getLastComputedValue(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $changeset_value)
    {
        $computed_value = $changeset_value->getField()->getComputedValue(
            $this->current_user,
            $artifact
        );

        if ($computed_value === null) {
            $computed_value = 0;
        }

        return $computed_value;
    }

    private function exportManualValue(\SimpleXMLElement $field_change, $manual_value)
    {
        $cdata_factory = new XML_SimpleXMLCDATAFactory();
        $cdata_factory->insert($field_change, 'manual_value', $manual_value);
    }
}
