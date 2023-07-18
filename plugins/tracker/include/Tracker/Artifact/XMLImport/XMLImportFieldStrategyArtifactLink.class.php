<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationContext;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;

class Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink implements Tracker_Artifact_XMLImport_XMLImportFieldStrategy
{
    /**
     * Add customs types to tracker
     *
     * Parameters:
     *  - tracker_id: input int
     *  - error : output string
     */
    public const TRACKER_ADD_SYSTEM_TYPES = 'tracker_add_system_types';

    public function __construct(
        private Tracker_XML_Importer_ArtifactImportedMapping $artifact_id_mapping,
        private \Psr\Log\LoggerInterface $logger,
        private TypeDao $type_dao,
    ) {
    }

    public function getFieldData(
        Tracker_FormElement_Field $field,
        SimpleXMLElement $field_change,
        PFUser $submitted_by,
        Artifact $artifact,
        PostCreationContext $context,
    ) {
        $new_values     = $this->extractArtifactLinkFromXml($field_change);
        $last_changeset = $artifact->getLastChangeset();

        $removed_values = [];
        if ($last_changeset) {
            $removed_values = $this->removeValuesIfDontExistInLastChangeset($new_values['new_values'], $last_changeset->getValues());
        }

        $add_values = implode(',', $new_values['new_values']);

        return [
            'new_values'     => $add_values,
            'removed_values' => $removed_values,
            'types'          => $new_values['types'],
        ];
    }

    private function extractArtifactLinkFromXml(SimpleXMLElement $field_change)
    {
        $artifact_links = [];
        $types          = [];

        foreach ($field_change->value as $artifact_link) {
            $linked_artifact_id = (int) $artifact_link;

            if ($this->artifact_id_mapping->containsSource($linked_artifact_id)) {
                $link             = $this->artifact_id_mapping->get($linked_artifact_id);
                $linked_type      = $this->getTypeFromMappedArtifact($artifact_link);
                $types[$link]     = $linked_type;
                $artifact_links[] = $link;

                $this->checkTypeExistOnPlateform($linked_type, $linked_artifact_id);
            } else {
                $this->logger->error("Could not find artifact with id=$linked_artifact_id in xml.");
            }
        }

        return ["new_values" => $artifact_links, "types" => $types];
    }

    private function checkTypeExistOnPlateform($linked_type, $linked_artifact_id)
    {
        $system_types = [];
        $this->retrieveSystemTypes($system_types);

        if ($linked_type && ! in_array($linked_type, $system_types, true)) {
            $type = $this->type_dao->getTypeByShortname($linked_type);
            if (count($type) === 0) {
                $this->logger->error("Type $linked_type not found on plateform. Artifact $linked_artifact_id added without type.");
            }
        }
    }

    private function getTypeFromMappedArtifact(SimpleXMLElement $xml_element)
    {
        return (string) $xml_element['nature'];
    }

    private function retrieveSystemTypes(array &$types): void
    {
        $params['types']   = &$types;
        $params['types'][] = Tracker_FormElement_Field_ArtifactLink::TYPE_IS_CHILD;
        EventManager::instance()->processEvent(
            self::TRACKER_ADD_SYSTEM_TYPES,
            $params
        );
    }

    private function removeValuesIfDontExistInLastChangeset(
        array $artifact_links,
        array $changesets,
    ) {
        $removed_artifacts = [];
        foreach ($changesets as $changeset) {
            if ($changeset instanceof \Tracker_Artifact_ChangesetValue_ArtifactLink) {
                foreach ($changeset->getArtifactIds() as $artifact_id) {
                    if (! in_array($artifact_id, $artifact_links)) {
                        $removed_artifacts[$artifact_id] = $artifact_id;
                    }
                }
            }
        }

        return $removed_artifacts;
    }
}
