<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue;

use PFUser;
use SimpleXMLElement;
use Tracker_Artifact_ChangesetValue;
use Tracker_ArtifactLinkInfo;
use Tracker_XML_ChildrenCollector;
use Tuleap\Tracker\Artifact\Artifact;
use XML_SimpleXMLCDATAFactory;

class ChangesetValueArtifactLinkXMLExporter extends ChangesetValueXMLExporter
{
    public function __construct(
        private readonly Tracker_XML_ChildrenCollector $children_collector,
        private readonly PFUser $current_user,
    ) {
    }

    protected function getFieldChangeType(): string
    {
        return 'art_link';
    }

    public function export(
        SimpleXMLElement $artifact_xml,
        SimpleXMLElement $changeset_xml,
        Artifact $artifact,
        Tracker_Artifact_ChangesetValue $changeset_value,
    ): void {
        $field_xml = $this->createFieldChangeNodeInChangesetNode(
            $changeset_value,
            $changeset_xml
        );

        $children_trackers = $changeset_value->getField()->getTracker()->getChildren();
        $values            = $changeset_value->getValue();
        if ($values) {
            array_walk(
                $values,
                function (Tracker_ArtifactLinkInfo $artifact_link_info, $index, $userdata) {
                    $this->appendValueToFieldChangeNode($artifact_link_info, $userdata);
                },
                [
                    'field_xml' => $field_xml,
                    'children_trackers' => $children_trackers,
                    'artifact' => $artifact,
                ]
            );
        }
    }

    private function appendValueToFieldChangeNode(
        Tracker_ArtifactLinkInfo $artifact_link_info,
        $userdata,
    ): void {
        $field_xml = $userdata['field_xml'];
        $artifact  = $userdata['artifact'];

        if ($this->canExportLinkedArtifact($artifact_link_info)) {
            $cdata_factory = new XML_SimpleXMLCDATAFactory();
            $cdata_factory->insertWithAttributes(
                $field_xml,
                'value',
                (string) $artifact_link_info->getArtifactId(),
                ['nature' => (string) $artifact_link_info->getType()]
            );
            $this->children_collector->addChild($artifact_link_info->getArtifactId(), $artifact->getId());
        }
    }

    private function canExportLinkedArtifact(Tracker_ArtifactLinkInfo $artifact_link_info): bool
    {
        return $artifact_link_info->userCanView($this->current_user);
    }
}
