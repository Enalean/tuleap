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

use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureDao;

class Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink implements Tracker_Artifact_XMLImport_XMLImportFieldStrategy
{
    /**
     * Add customs natures to tracker
     *
     * Parameters:
     *  - tracker_id: input int
     *  - error : output string
     */
    public const TRACKER_ADD_SYSTEM_NATURES = 'tracker_add_system_natures';

    /**
     * Check that nature is respects rules
     *
     * Parameters:
     *  - tracker_id: input in
     *  - error : output string
     *  - artifact: input Tracker_Artifact
     *  - children_id: input int
     *  - shortname: input string
     */
    public const TRACKER_IS_NATURE_VALID = 'tracker_is_nature_valid';

    /** @var Tracker_XML_Importer_ArtifactImportedMapping */
    private $artifact_id_mapping;

    /** @var \Psr\Log\LoggerInterface  */
    private $logger;

    /** @var Tracker_ArtifactFactory  */
    private $artifact_factory;

    /** @var NatureDao  */
    private $nature_dao;

    public function __construct(
        Tracker_XML_Importer_ArtifactImportedMapping $artifact_id_mapping,
        \Psr\Log\LoggerInterface $logger,
        Tracker_ArtifactFactory $artifact_factory,
        NatureDao $nature_dao
    ) {
        $this->artifact_id_mapping = $artifact_id_mapping;
        $this->logger              = $logger;
        $this->artifact_factory    = $artifact_factory;
        $this->nature_dao          = $nature_dao;
    }

    public function getFieldData(
        Tracker_FormElement_Field $field,
        SimpleXMLElement $field_change,
        PFUser $submitted_by,
        Tracker_Artifact $artifact
    ) {
        $new_values     = $this->extractArtifactLinkFromXml($field_change, $artifact);
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

    private function extractArtifactLinkFromXml(SimpleXMLElement $field_change, Tracker_Artifact $artifact)
    {
        $artifact_links = array();
        $natures        = array();

        foreach ($field_change->value as $artifact_link) {
            $linked_artifact_id = (int) $artifact_link;

            if ($this->artifact_id_mapping->containsSource($linked_artifact_id)) {
                $link             = $this->artifact_id_mapping->get($linked_artifact_id);
                $linked_nature    = $this->getNatureFromMappedArtifact($artifact, $artifact_link, $link);
                $natures[$link]   = $linked_nature;
                $artifact_links[] = $link;

                $this->checkNatureExistOnPlateform($linked_nature, $linked_artifact_id);
            } else {
                $this->logger->error("Could not find artifact with id=$linked_artifact_id in xml.");
            }
        }

        return array("new_values" => $artifact_links, "natures" => $natures);
    }

    private function checkNatureExistOnPlateform($linked_nature, $linked_artifact_id)
    {
        $system_nature = array();
        $this->retrieveSystemNatures($system_nature);

        if ($linked_nature && ! in_array($linked_nature, $system_nature)) {
            $nature = $this->nature_dao->getNatureByShortname($linked_nature);
            if ($nature->count() === 0) {
                $this->logger->error("Nature $linked_nature not found on plateform. Artifact $linked_artifact_id added without nature.");
            }
        }
    }

    private function getNatureFromMappedArtifact(
        Tracker_Artifact $artifact,
        SimpleXMLElement $xml_element,
        $mapped_artifact_id
    ) {
        $nature       = (string) $xml_element['nature'];
        $xml_artifact = $this->artifact_factory->getArtifactById($mapped_artifact_id);
        if ($xml_artifact) {
            $error_message = $this->isLinkValid($xml_artifact->getTrackerId(), $artifact, $mapped_artifact_id, $nature);
            if ($error_message !== "") {
                $this->logger->error($error_message);
                return "";
            }
        }

        return $nature;
    }

    private function retrieveSystemNatures(array &$natures)
    {
        $params['natures']   = &$natures;
        $params['natures'][] = Tracker_FormElement_Field_ArtifactLink::NATURE_IS_CHILD;
        EventManager::instance()->processEvent(
            self::TRACKER_ADD_SYSTEM_NATURES,
            $params
        );
    }

    private function removeValuesIfDontExistInLastChangeset(
        array $artifact_links,
        array $changesets
    ) {
        $removed_artifacts = array();
        foreach ($changesets as $changeset) {
            if (is_a($changeset, "Tracker_Artifact_ChangesetValue_ArtifactLink")) {
                foreach ($changeset->getArtifactIds() as $artifact_id) {
                    if (! in_array($artifact_id, $artifact_links)) {
                        $removed_artifacts[$artifact_id] = $artifact_id;
                    }
                }
            }
        }

        return $removed_artifacts;
    }

    private function isLinkValid($tracker_id, Tracker_Artifact $artifact, $children_id, $nature)
    {
        $error                 = "";
        $params['error']       = &$error;
        $params['tracker_id']  = &$tracker_id;
        $params['artifact']    = $artifact;
        $params['children_id'] = $children_id;
        $params['nature']      = $nature;
        EventManager::instance()->processEvent(
            self::TRACKER_IS_NATURE_VALID,
            $params
        );

        return $params['error'];
    }
}
