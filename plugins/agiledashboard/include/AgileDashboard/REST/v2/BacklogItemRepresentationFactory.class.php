<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\AgileDashboard\REST\v2;

use AgileDashboard_Milestone_Backlog_IBacklogItem;
use UserManager;
use EventManager;

class BacklogItemRepresentationFactory
{

    public function createBacklogItemRepresentation(AgileDashboard_Milestone_Backlog_IBacklogItem $backlog_item)
    {
        return BacklogItemRepresentation::build($backlog_item, $this->getBacklogItemCardFields($backlog_item));
    }

    private function getBacklogItemCardFields($backlog_item)
    {
        $current_user         = UserManager::instance()->getCurrentUser();
        $card_fields_semantic = $this->getCardFieldsSemantic($backlog_item);
        $card_fields          = [];

        foreach ($card_fields_semantic->getFields() as $field) {
            if ($field->userCanRead($current_user)) {
                $card_fields[] = $field->getFullRESTValue($current_user, $backlog_item->getArtifact()->getLastChangeset());
            }
        }

        return $card_fields;
    }

    private function getCardFieldsSemantic($backlog_item)
    {
        $card_fields_semantic = null;

        EventManager::instance()->processEvent(
            AGILEDASHBOARD_EVENT_GET_CARD_FIELDS,
            [
                'tracker'              => $backlog_item->getArtifact()->getTracker(),
                'card_fields_semantic' => &$card_fields_semantic
            ]
        );

        return $card_fields_semantic;
    }
}
