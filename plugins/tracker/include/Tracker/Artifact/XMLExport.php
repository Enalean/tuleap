<?php
/**
 * Copyright (c) Enalean, 2015 - present. All Rights Reserved.
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

use Tuleap\Project\XML\Import\ExternalFieldsExtractor;

class Tracker_Artifact_XMLExport
{
    public const THRESHOLD = 9000;

    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;

    /**
     * @var XML_RNGValidator
     */
    private $rng_validator;

    /**
     * @var bool
     */
    private $can_bypass_threshold;

    /** @var UserXMLExporter */
    private $user_xml_exporter;

    /**
     * @var ExternalFieldsExtractor
     */
    private $external_fields_extractor;

    public function __construct(
        XML_RNGValidator $rng_validator,
        Tracker_ArtifactFactory $artifact_factory,
        $can_bypass_threshold,
        UserXMLExporter $user_xml_exporter,
        ExternalFieldsExtractor $external_fields_extractor
    ) {
        $this->rng_validator             = $rng_validator;
        $this->artifact_factory          = $artifact_factory;
        $this->can_bypass_threshold      = $can_bypass_threshold;
        $this->user_xml_exporter         = $user_xml_exporter;
        $this->external_fields_extractor = $external_fields_extractor;
    }

    public function export(
        Tracker $tracker,
        SimpleXMLElement $xml_content,
        PFUser $user,
        Tuleap\Project\XML\Export\ArchiveInterface $archive
    ) {
        $all_artifacts = $this->artifact_factory->getArtifactsByTrackerId($tracker->getId());
        $this->checkThreshold(count($all_artifacts));

        $is_in_archive_context = false;
        $this->exportBunchOfArtifacts($all_artifacts, $xml_content, $user, $archive, $is_in_archive_context);
    }

    private function checkThreshold($nb_artifacts)
    {
        if ($this->can_bypass_threshold) {
            return;
        }

        if ($nb_artifacts > self::THRESHOLD) {
            throw new Tracker_Artifact_XMLExportTooManyArtifactsException(
                "Too many artifacts: $nb_artifacts (IT'S OVER " . self::THRESHOLD . "!)"
            );
        }
    }

    /**
     * @param Tracker_Artifact[] $artifacts
     */
    private function exportBunchOfArtifacts(
        array $artifacts,
        SimpleXMLElement $xml_content,
        PFUser $user,
        Tuleap\Project\XML\Export\ArchiveInterface $archive,
        $is_in_archive_context
    ) {
        $artifacts_node = $xml_content->addChild('artifacts');

        foreach ($artifacts as $artifact) {
            $artifact->exportToXML(
                $artifacts_node,
                $archive,
                $this->getArtifactXMLExporter($user, $is_in_archive_context)
            );
        }

        $partial_element = new SimpleXMLElement((string) $artifacts_node->asXML());

        foreach ($partial_element->artifact as $artifact_xml) {
            $this->external_fields_extractor->extractExternalFieldsFromArtifact($artifact_xml);
        }

        $this->rng_validator->validate(
            $partial_element,
            realpath(__DIR__ . '/../../../resources/artifacts.rng')
        );
    }

    public function exportBunchOfArtifactsForArchive(
        array $artifacts,
        SimpleXMLElement $xml_content,
        PFUser $user,
        Tuleap\Project\XML\Export\ArchiveInterface $archive
    ) {
        $is_in_archive_context = true;

        $this->exportBunchOfArtifacts(
            $artifacts,
            $xml_content,
            $user,
            $archive,
            $is_in_archive_context
        );
    }

    /**
     * @return Tracker_XML_Exporter_ArtifactXMLExporter
     */
    private function getArtifactXMLExporter(PFUser $current_user, $is_in_archive_context)
    {
        $builder                = new Tracker_XML_Exporter_ArtifactXMLExporterBuilder();
        $children_collector     = new Tracker_XML_Exporter_NullChildrenCollector();
        $file_path_xml_exporter = new Tracker_XML_Exporter_InArchiveFilePathXMLExporter();

        return $builder->build(
            $children_collector,
            $file_path_xml_exporter,
            $current_user,
            $this->user_xml_exporter,
            $is_in_archive_context
        );
    }
}
