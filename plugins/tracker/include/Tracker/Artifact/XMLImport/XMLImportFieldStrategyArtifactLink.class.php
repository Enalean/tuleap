<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureCreator;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureDao;

class Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink implements Tracker_Artifact_XMLImport_XMLImportFieldStrategy
{
    /** @var Tracker_XML_Importer_ArtifactImportedMapping */
    private $artifact_id_mapping;

    /** @var Logger  */
    private $logger;

    /** @var Tracker_ArtifactFactory  */
    private $artifact_factory;

    /** @var NatureDao  */
    private $nature_dao;

    /** @var NatureCreator  */
    private $nature_creator;

    public function __construct(
        Tracker_XML_Importer_ArtifactImportedMapping $artifact_id_mapping,
        Logger $logger,
        Tracker_ArtifactFactory $artifact_factory,
        NatureDao $nature_dao,
        NatureCreator $nature_creator
    ) {
        $this->artifact_id_mapping = $artifact_id_mapping;
        $this->logger              = $logger;
        $this->artifact_factory    = $artifact_factory;
        $this->nature_dao          = $nature_dao;
        $this->nature_creator      = $nature_creator;
    }

    public function getFieldData(
        Tracker_FormElement_Field $field,
        SimpleXMLElement $field_change,
        PFUser $submitted_by,
        Tracker_Artifact $artifact
    ) {
        $new_values     = $this->extractArtifactLinkFromXml($field_change);
        $last_changeset = $artifact->getLastChangeset();

        $removed_values = array();
        if ($last_changeset) {
            $removed_values = $this->removeValuesIfDontExistInLastChangeset($new_values['new_values'], $last_changeset->getValues());
        }

        $add_values = implode(',', $new_values['new_values']);

        return array(
            'new_values'     => $add_values,
            'removed_values' => $removed_values,
            'natures'        => $new_values['natures']
        );
    }

    private function extractArtifactLinkFromXml(SimpleXMLElement $field_change)
    {
        $artifact_links = array();
        $natures        = array();

        foreach ($field_change->value as $artifact_link) {
            $linked_artifact_id = (int)$artifact_link;
            $linked_nature      = (string)$artifact_link['nature'];

            if ($this->artifact_id_mapping->containsSource($linked_artifact_id)) {
                $link = $this->artifact_id_mapping->get($linked_artifact_id);
                $artifact_links[] = $link;
                $natures[$link]   = $linked_nature;

                if ($linked_nature && $linked_nature !== Tracker_FormElement_Field_ArtifactLink::NATURE_IS_CHILD) {
                    $nature = $this->nature_dao->getNatureByShortname($linked_nature);
                    if ($nature->count() === 0) {
                        $this->logger->error("Nature $linked_nature not found on plateform. Artifact $linked_artifact_id added without nature.");
                    }
                }
            } else {
                $this->logger->error("Could not find artifact with id=$linked_artifact_id in xml.");
            }
        }

        return array("new_values" => $artifact_links, "natures" => $natures);
    }

    private function removeValuesIfDontExistInLastChangeset(
        array $artifact_links,
        array $changesets
    ) {
        $removed_artifacts = array();
        foreach($changesets as $changeset) {
            if (is_a($changeset, "Tracker_Artifact_ChangesetValue_ArtifactLink")) {
                foreach($changeset->getArtifactIds() as $artifact_id) {
                    if (! in_array($artifact_id, $artifact_links)) {
                        $removed_artifacts[$artifact_id] = $artifact_id;
                    }
                }
            }
        }

        return $removed_artifacts;
    }
}
