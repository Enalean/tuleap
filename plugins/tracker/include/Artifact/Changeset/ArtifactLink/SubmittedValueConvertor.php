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

namespace Tuleap\Tracker\Artifact\Changeset\ArtifactLink;

use Tracker_ArtifactFactory;
use Tracker_ArtifactLinkInfo;

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
 */
final readonly class SubmittedValueConvertor
{
    public function __construct(private Tracker_ArtifactFactory $artifact_factory)
    {
    }

    /**
     * @return array The submitted value expurged from updated links
     */
    public function convert(
        array $submitted_value,
        ?ArtifactLinkChangesetValue $previous_changesetvalue,
    ): array {
        $submitted_value['list_of_artifactlinkinfo'] = $this->getListOfArtifactLinkInfo(
            $submitted_value,
            $previous_changesetvalue
        );

        return $submitted_value;
    }

    /** @return array<int, Tracker_ArtifactLinkInfo> */
    private function getListOfArtifactLinkInfo(
        array $submitted_value,
        ?ArtifactLinkChangesetValue $previous_changesetvalue,
    ): array {
        $list_of_artifactlinkinfo = [];
        if ($previous_changesetvalue != null) {
            $list_of_artifactlinkinfo = $previous_changesetvalue->getValue();
            $this->removeLinksFromSubmittedValue($list_of_artifactlinkinfo, $submitted_value);
            $this->changeTypeOfExistingLinks($list_of_artifactlinkinfo, $submitted_value);
        }
        $this->addLinksFromSubmittedValue($list_of_artifactlinkinfo, $submitted_value);

        return $list_of_artifactlinkinfo;
    }

    /**
     * @param array<int, Tracker_ArtifactLinkInfo> $list_of_artifactlinkinfo
     */
    private function removeLinksFromSubmittedValue(
        array &$list_of_artifactlinkinfo,
        array $submitted_value,
    ): void {
        $removed_values = $this->extractRemovedValuesFromSubmittedValue($submitted_value);

        if (empty($removed_values)) {
            return;
        }

        foreach (array_keys($list_of_artifactlinkinfo) as $id) {
            if (isset($removed_values[$id])) {
                unset($list_of_artifactlinkinfo[$id]);
            }
        }
    }

    /**
     * @param array<int, Tracker_ArtifactLinkInfo> $list_of_artifactlinkinfo
     */
    private function changeTypeOfExistingLinks(
        array &$list_of_artifactlinkinfo,
        array $submitted_value,
    ): void {
        $types = $this->extractTypesFromSubmittedValue($submitted_value);

        if (empty($types)) {
            return;
        }

        foreach ($list_of_artifactlinkinfo as $id => $artifactlinkinfo) {
            if (isset($types[$id]) && $artifactlinkinfo->getType() != $types[$id]) {
                $list_of_artifactlinkinfo[$id] = clone $artifactlinkinfo;
                $list_of_artifactlinkinfo[$id]->setType($types[$id]);
            }
        }
    }

    /**
     * @param array<int, Tracker_ArtifactLinkInfo> $list_of_artifactlinkinfo
     */
    private function addLinksFromSubmittedValue(array &$list_of_artifactlinkinfo, array $submitted_value): void
    {
        $new_values = $this->extractNewValuesFromSubmittedValue($submitted_value);

        foreach ($new_values as $new_artifact_id) {
            $type = $this->extractTypeFromSubmittedValue($submitted_value, (int) $new_artifact_id);
            if (isset($list_of_artifactlinkinfo[(int) $new_artifact_id])) {
                continue;
            }

            $artifact = $this->artifact_factory->getArtifactById($new_artifact_id);
            if (! $artifact) {
                continue;
            }
            $list_of_artifactlinkinfo[(int) $new_artifact_id] = Tracker_ArtifactLinkInfo::buildFromArtifact(
                $artifact,
                $type
            );
        }
    }

    private function extractTypeFromSubmittedValue(array $submitted_value, int $artifact_id): string
    {
        if (isset($submitted_value['types'])) {
            $types = $submitted_value['types'];
            if (isset($types[$artifact_id])) {
                return $types[$artifact_id];
            }
        }

        return '';
    }

    private function extractNewValuesFromSubmittedValue(array $submitted_value): array
    {
        $new_values          = (string) $submitted_value['new_values'];
        $removed_values      = $this->extractRemovedValuesFromSubmittedValue($submitted_value);
        $new_values_as_array = array_filter(array_map('intval', explode(',', $new_values)));

        return array_unique(array_diff($new_values_as_array, array_keys($removed_values)));
    }

    private function extractRemovedValuesFromSubmittedValue(array $submitted_value): array
    {
        return $this->extractArrayFromSubmittedValue($submitted_value, 'removed_values');
    }

    private function extractTypesFromSubmittedValue(array $submitted_value): array
    {
        return $this->extractArrayFromSubmittedValue($submitted_value, 'types');
    }

    private function extractArrayFromSubmittedValue(array $submitted_value, string $key): array
    {
        if (! isset($submitted_value[$key])) {
            return [];
        }

        $values = $submitted_value[$key];
        if (! is_array($values)) {
            return [];
        }

        return $values;
    }
}
