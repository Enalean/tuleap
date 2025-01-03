<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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
namespace Tuleap\Cardwall\REST\v1;

use Cardwall_SingleCard;
use Luracast\Restler\RestException;
use PFUser;
use Tuleap\Tracker\Artifact\Artifact;

class CardUpdater
{
    public function updateCard(PFUser $user, Cardwall_SingleCard $card, $label, array $values, $column_id = null)
    {
        $artifact = $card->getArtifact();

        $this->checkArtifact($user, $artifact);
        $cards_resource_validator = new CardValidator();
        $fields_data              = $cards_resource_validator->getFieldsDataFromREST($user, $card, $label, $values, $column_id);
        $artifact->createNewChangeset($fields_data, '', $user);
    }

    private function checkArtifact(PFUser $user, Artifact $artifact)
    {
        if (! $artifact) {
            throw new RestException(404, 'Artifact not found');
        }
        if (! $artifact->userCanUpdate($user)) {
            throw new RestException(403, 'You have not the permission to update this card');
        }
    }
}
