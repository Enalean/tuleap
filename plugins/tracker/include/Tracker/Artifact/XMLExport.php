<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class Tracker_Artifact_XMLExport {

    const ARTIFACTS_RNG_PATH = '/www/resources/artifacts.rng';
    const THRESHOLD          = 9000;

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

    public function __construct(
        XML_RNGValidator $rng_validator,
        Tracker_ArtifactFactory $artifact_factory,
        $can_bypass_threshold
    ) {
        $this->rng_validator        = $rng_validator;
        $this->artifact_factory     = $artifact_factory;
        $this->can_bypass_threshold = $can_bypass_threshold;
    }

    public function export(Tracker $tracker, SimpleXMLElement $xml_content, PFUser $user, ZipArchive $archive) {
        $artifacts_node = $xml_content->addChild('artifacts');

        $all_artifacts = $this->artifact_factory->getArtifactsByTrackerId($tracker->getId());
        $this->checkThreshold(count($all_artifacts));
        foreach ($all_artifacts as $artifact) {
            $artifact->exportToXML($artifacts_node, $user, $archive);
        }
    }

    private function checkThreshold($nb_artifacts) {
        if ($this->can_bypass_threshold) {
            return;
        }

        if ($nb_artifacts > self::THRESHOLD) {
            throw new Tracker_Artifact_XMLExportTooManyArtifactsException(
                "Too many artifacts: $nb_artifacts (IT'S OVER ".self::THRESHOLD."!)"
            );
        }
    }
}
