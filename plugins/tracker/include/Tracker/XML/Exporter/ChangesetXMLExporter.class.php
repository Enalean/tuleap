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

class Tracker_XML_Exporter_ChangesetXMLExporter
{

    /**
     * @var Tracker_XML_Exporter_ChangesetValuesXMLExporter
     */
    private $values_exporter;

    /** @var UserXMLExporter */
    private $user_xml_exporter;

    public function __construct(
        Tracker_XML_Exporter_ChangesetValuesXMLExporter $values_exporter,
        UserXMLExporter $user_xml_exporter
    ) {
        $this->values_exporter   = $values_exporter;
        $this->user_xml_exporter = $user_xml_exporter;
    }

    public function exportWithoutComments(
        SimpleXMLElement $artifact_xml,
        Tracker_Artifact_Changeset $changeset
    ) {
        $changeset_xml = $artifact_xml->addChild('changeset');

        $cdata = new \XML_SimpleXMLCDATAFactory();
        $cdata->insertWithAttributes($changeset_xml, 'submitted_by', $changeset->getSubmittedBy(), ['format' => 'id']);
        $cdata->insertWithAttributes(
            $changeset_xml,
            'submitted_on',
            date('c', $changeset->getSubmittedOn()),
            ['format' => 'ISO8601']
        );

        $this->values_exporter->exportSnapshot($artifact_xml, $changeset_xml, $changeset->getArtifact(), $changeset->getValues());
    }

    public function exportFullHistory(
        SimpleXMLElement $artifact_xml,
        Tracker_Artifact_Changeset $changeset
    ) {
        $changeset_xml = $artifact_xml->addChild('changeset');

        if ($changeset->getSubmittedBy()) {
            $this->user_xml_exporter->exportUserByUserId(
                $changeset->getSubmittedBy(),
                $changeset_xml,
                'submitted_by'
            );
        } elseif ($changeset->getEmail()) {
            $this->user_xml_exporter->exportUserByMail(
                $changeset->getEmail(),
                $changeset_xml,
                'submitted_by'
            );
        }

        $submitted_on = $changeset_xml->addChild('submitted_on', date('c', $changeset->getSubmittedOn()));
        $submitted_on->addAttribute('format', 'ISO8601');

        $comments_node = $changeset_xml->addChild('comments');
        if ($changeset->getComment()) {
            $changeset->getComment()->exportToXML($comments_node, $this->user_xml_exporter);
        }

        $changeset->forceFetchAllValues();
        $changeset_values = array_filter($changeset->getValues());

        if ($changeset_values !== null) {
            $this->values_exporter->exportChangedFields(
                $artifact_xml,
                $changeset_xml,
                $changeset->getArtifact(),
                $changeset_values
            );
        }
    }
}
