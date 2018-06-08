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

namespace Tuleap\Cardwall\Semantic;

use Cardwall_Semantic_CardFields;
use Cardwall_UserPreferences_UserPreferencesDisplayUser;
use Tracker_Artifact;
use Tracker_ArtifactFactory;
use Tracker_FormElement_Field;

class CardPreviewDetailsBuilder
{
    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var Cardwall_UserPreferences_UserPreferencesDisplayUser
     */
    private $card_preferences;

    public function __construct(
        Tracker_ArtifactFactory $artifact_factory,
        Cardwall_UserPreferences_UserPreferencesDisplayUser $card_preferences
    ) {
        $this->artifact_factory = $artifact_factory;
        $this->card_preferences = $card_preferences;
    }

    public function build(Cardwall_Semantic_CardFields $semantic_card)
    {
        $card_preview         = [];
        $artifact_id          = "XXX";
        $artifact_description = "Example";

        $artifact = $this->getARandomArtifactFromTracker($semantic_card);

        if ($artifact) {
            $artifact_id          = $artifact->getId();
            $artifact_description = $artifact->getTitle();
        }
        $fields_details = $this->extractDetailsFields($semantic_card, $artifact);

        $card_preview['artifact_id']          = $artifact_id;
        $card_preview['artifact_description'] = $artifact_description;
        $card_preview['card_preview_details'] = $fields_details;

        return $card_preview;
    }

    /**
     * @param Cardwall_Semantic_CardFields $semantic_card
     *
     * @return Tracker_Artifact
     */
    private function getARandomArtifactFromTracker(Cardwall_Semantic_CardFields $semantic_card)
    {
        $artifact  = null;
        $artifacts = $this->artifact_factory->getArtifactsByTrackerId($semantic_card->getTracker()->getId());

        if ($artifacts) {
            $artifact = array_shift(array_values($artifacts));
        }

        return $artifact;
    }


    private function extractArtifactValueForField(
        Tracker_FormElement_Field $used_field,
        Tracker_Artifact $artifact = null
    ) {
        $value = "XXX";

        if ($artifact && $artifact->getLastChangeset()) {
            $value = $used_field->fetchCardValue($artifact, $this->card_preferences);
        }

        return $value;
    }

    /**
     * @param Cardwall_Semantic_CardFields $semantic_card
     * @param                              $artifact
     *
     * @return array
     */
    private function extractDetailsFields(
        Cardwall_Semantic_CardFields $semantic_card,
        Tracker_Artifact $artifact = null
    ) {
        $fields_details = [];
        foreach ($semantic_card->getFields() as $used_field) {
            $fields_details[] = [
                'field_label'         => $used_field->getLabel(),
                'escaped_field_value' => $this->extractArtifactValueForField($used_field, $artifact)
            ];
        }
        return $fields_details;
    }
}
