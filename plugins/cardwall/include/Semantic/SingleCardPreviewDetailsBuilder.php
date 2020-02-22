<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

class SingleCardPreviewDetailsBuilder
{
    public function build(Cardwall_Semantic_CardFields $semantic_card, array $possible_background)
    {
        $card_preview = [];

        $fields_details = $this->extractDetailsFields($semantic_card, $possible_background);

        $card_preview['card_preview_details'] = $fields_details;
        $card_preview['tracker_color']        = $semantic_card->getTracker()->getColor()->getName();

        return $card_preview;
    }

    private function extractDetailsFields(
        Cardwall_Semantic_CardFields $semantic_card,
        array $possible_background
    ) {
        $fields_details = [];
        foreach ($semantic_card->getFields() as $used_field) {
            $fields_details[] = [
                'field_label' => $used_field->getLabel(),
                'background'  => $possible_background['background_color']
            ];
        }
        return $fields_details;
    }
}
