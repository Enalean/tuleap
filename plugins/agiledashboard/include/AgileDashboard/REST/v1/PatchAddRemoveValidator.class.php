<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\AgileDashboard\REST\v1;

class PatchAddRemoveValidator
{

    /**
     * @var IValidateElementsToAdd
     */
    private $add_validator;

    /**
     * @var int[]
     */
    private $index;

    public function __construct(array $index, IValidateElementsToAdd $add_validator)
    {
        $this->index         = $index;
        $this->add_validator = $add_validator;
    }

    /**
     * @throws ArtifactIsNotInMilestoneContentException
     */
    public function validate($reference_id, $remove, $add)
    {
        $remove = $remove != null ? $remove : array();
        $add    = $add    != null ? $add    : array();

        $to_remove = $this->getIdsToRemoveThatAreNotInAddArray($remove, $add);
        $to_add    = $this->getIdsToAddThatAreNotInRemoveArray($remove, $add);
        foreach ($to_remove as $id) {
            if (! isset($this->index[$id])) {
                throw new ArtifactIsNotInMilestoneContentException($reference_id, $id);
            }
            unset($this->index[$id]);
        }
        if (count($to_add)) {
            $this->add_validator->validate($to_add);
        }

        return array_unique(array_merge(array_keys($this->index), $to_add));
    }

    private function getIdsToRemoveThatAreNotInAddArray($remove, $add)
    {
        return array_diff($remove, $add);
    }

    private function getIdsToAddThatAreNotInRemoveArray($remove, $add)
    {
        return array_diff($add, $remove);
    }
}
