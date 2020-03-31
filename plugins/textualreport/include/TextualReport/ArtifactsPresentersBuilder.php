<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\TextualReport;

use PFUser;
use Tracker_Artifact_ChangesetValue_Text;
use Tracker_ArtifactFactory;
use Tracker_Semantic_Description;

class ArtifactsPresentersBuilder
{
    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;

    public function __construct(Tracker_ArtifactFactory $artifact_factory)
    {
        $this->artifact_factory = $artifact_factory;
    }

    /**
     * @param array  $ordered_artifact_rows
     * @param string $server_url
     * @param int    $hard_limit
     *
     * @return array
     */
    public function getArtifactsPresenters(array $ordered_artifact_rows, PFUser $current_user, $server_url, $hard_limit)
    {
        array_splice($ordered_artifact_rows, $hard_limit);

        $artifacts = [];
        foreach ($ordered_artifact_rows as $row) {
            $artifact = $this->artifact_factory->getArtifactByIdUserCanView($current_user, $row['id']);
            if (! $artifact) {
                continue;
            }

            $artifacts[] = [
                'xref'                 => $artifact->getXRef(),
                'href'                 => $server_url . $artifact->getUri(),
                'title'                => $artifact->getTitle(),
                'purified_description' => $this->getPurifiedDescription($artifact)
            ];
        }

        return $artifacts;
    }

    private function getPurifiedDescription(\Tracker_Artifact $artifact)
    {
        $description_field = Tracker_Semantic_Description::load($artifact->getTracker())->getField();
        if (! $description_field) {
            return '';
        }

        if (! $description_field->userCanRead()) {
            return '';
        }

        $last_changeset = $artifact->getLastChangeset();
        if (! $last_changeset) {
            return '';
        }

        $changeset_value = $last_changeset->getValue($description_field);
        \assert($changeset_value instanceof Tracker_Artifact_ChangesetValue_Text);
        if (! $changeset_value) {
            return '';
        }

        return $changeset_value->getValue();
    }
}
