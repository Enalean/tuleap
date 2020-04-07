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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink;

use Tracker_Artifact;
use Tracker_ArtifactLinkInfo;
use Tracker_Artifact_ChangesetValue_ArtifactLink;
use Tracker_ArtifactFactory;

/**
 * I convert submitted value into something that can be given to ArtifactLinkValueSaver.
 *
 * For example,
 *
 * $submitted_value = array(
 *    'new_values' => '123, 124'
 * );
 *
 * will result in:
 *
 * $submitted_value = array(
 *   'new_values' => …,
 *   'list_of_artifactlinkinfo' => array(
 *     Tracker_ArtifactLinkInfo(123),
 *     Tracker_ArtifactLinkInfo(124)
 *   )
 * );
 *
 * Furthermore it checks that the linking direction is correct. For example if one wants to link a story to a task
 * and there is a hierarchy "Story tracker is parent of Task tracker", then the link will be removed so that it is
 * the story that will reference the task instead of the inverse.
 */
class SubmittedValueConvertor
{

    /**
     * @var SourceOfAssociationDetector
     */
    private $source_of_association_detector;

    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;

    public function __construct(
        Tracker_ArtifactFactory $artifact_factory,
        SourceOfAssociationDetector $source_of_association_detector
    ) {
        $this->artifact_factory               = $artifact_factory;
        $this->source_of_association_detector = $source_of_association_detector;
    }

    /**
     * Verify (and update if needed) that the link between what submitted the user ($submitted_values) and
     * the current artifact is correct resp. the association definition.
     *
     * Given I defined following hierarchy:
     * Release
     * `-- Sprint
     *
     * If $artifact is a Sprint and I try to link a Release, this method detect
     * it and update the corresponding Release with a link toward current sprint
     *
     * @return mixed The submitted value expurged from updated links
     */
    public function convert(
        array $submitted_value,
        SourceOfAssociationCollection $source_of_association_collection,
        Tracker_Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue_ArtifactLink $previous_changesetvalue = null
    ) {
        $submitted_value['list_of_artifactlinkinfo'] = $this->getListOfArtifactLinkInfo(
            $source_of_association_collection,
            $artifact,
            $submitted_value,
            $previous_changesetvalue
        );

        return $submitted_value;
    }

    /** @return Tracker_ArtifactLinkInfo[] */
    private function getListOfArtifactLinkInfo(
        SourceOfAssociationCollection $source_of_association_collection,
        Tracker_Artifact $from_artifact,
        array $submitted_value,
        ?Tracker_Artifact_ChangesetValue_ArtifactLink $previous_changesetvalue = null
    ) {
        $list_of_artifactlinkinfo = array();
        if ($previous_changesetvalue != null) {
            $list_of_artifactlinkinfo = $previous_changesetvalue->getValue();
            $this->removeLinksFromSubmittedValue($list_of_artifactlinkinfo, $submitted_value);
            $this->changeNatureOfExistingLinks($list_of_artifactlinkinfo, $submitted_value);
        }
        $this->addLinksFromSubmittedValue($list_of_artifactlinkinfo, $submitted_value);
        $this->removeAlreadyLinkedParentArtifacts(
            $source_of_association_collection,
            $from_artifact,
            $list_of_artifactlinkinfo
        );

        return $list_of_artifactlinkinfo;
    }

    private function removeAlreadyLinkedParentArtifacts(
        SourceOfAssociationCollection $source_of_association_collection,
        Tracker_Artifact $from_artifact,
        array &$list_of_artifactlinkinfo
    ) {
        foreach ($list_of_artifactlinkinfo as $id => $artifactinfo) {
            $artifact_to_add = $artifactinfo->getArtifact();
            if ($this->source_of_association_detector->isChild($artifact_to_add, $from_artifact)) {
                $source_of_association_collection->add($artifact_to_add);
                unset($list_of_artifactlinkinfo[$id]);
            }
        }
    }

    private function removeLinksFromSubmittedValue(
        array &$list_of_artifactlinkinfo,
        array $submitted_value
    ) {
        $removed_values = $this->extractRemovedValuesFromSubmittedValue($submitted_value);

        if (empty($removed_values)) {
            return;
        }

        foreach ($list_of_artifactlinkinfo as $id => $noop) {
            if (isset($removed_values[$id])) {
                unset($list_of_artifactlinkinfo[$id]);
            }
        }
    }

    /**
     * @param Tracker_ArtifactLinkInfo[] $list_of_artifactlinkinfo
     * @param array $submitted_value
     */
    private function changeNatureOfExistingLinks(
        array &$list_of_artifactlinkinfo,
        array $submitted_value
    ) {
        $natures = $this->extractNaturesFromSubmittedValue($submitted_value);

        if (empty($natures)) {
            return;
        }

        foreach ($list_of_artifactlinkinfo as $id => $artifactlinkinfo) {
            if (isset($natures[$id]) && $artifactlinkinfo->getNature() != $natures[$id]) {
                $list_of_artifactlinkinfo[$id] = clone $artifactlinkinfo;
                $list_of_artifactlinkinfo[$id]->setNature($natures[$id]);
            }
        }
    }

    private function addLinksFromSubmittedValue(array &$list_of_artifactlinkinfo, array $submitted_value)
    {
        $new_values = $this->extractNewValuesFromSubmittedValue($submitted_value);

        foreach ($new_values as $new_artifact_id) {
            $nature = $this->extractNatureFromSubmittedValue($submitted_value, $new_artifact_id);
            if (isset($list_of_artifactlinkinfo[$new_artifact_id])) {
                continue;
            }

            $artifact = $this->artifact_factory->getArtifactById($new_artifact_id);
            if (! $artifact) {
                continue;
            }
            $list_of_artifactlinkinfo[$new_artifact_id] = Tracker_ArtifactLinkInfo::buildFromArtifact(
                $artifact,
                $nature
            );
        }
    }

    private function extractNatureFromSubmittedValue(array $submitted_value, $artifact_id): string
    {
        if (isset($submitted_value['natures'])) {
            $natures = $submitted_value['natures'];
            if (isset($natures[$artifact_id])) {
                return $natures[$artifact_id];
            }
        }

        return '';
    }

    private function extractNewValuesFromSubmittedValue(array $submitted_value)
    {
        $new_values          = (string) $submitted_value['new_values'];
        $removed_values      = $this->extractRemovedValuesFromSubmittedValue($submitted_value);
        $new_values_as_array = array_filter(array_map('intval', explode(',', $new_values)));

        return array_unique(array_diff($new_values_as_array, array_keys($removed_values)));
    }

    private function extractRemovedValuesFromSubmittedValue(array $submitted_value)
    {
        return $this->extractArrayFromSubmittedValue($submitted_value, 'removed_values');
    }

    private function extractNaturesFromSubmittedValue(array $submitted_value)
    {
        return $this->extractArrayFromSubmittedValue($submitted_value, 'natures');
    }

    private function extractArrayFromSubmittedValue(array $submitted_value, $key)
    {
        if (! isset($submitted_value[$key])) {
            return array();
        }

        $values = $submitted_value[$key];
        if (! is_array($values)) {
            return array();
        }

        return $values;
    }
}
